<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com 
|| ####################################################################
\*======================================================================*/
/**
* DCFm Import posts
*
* @package 		ImpEx.DCFm
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class DCFm_007 extends DCFm_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Posts';
	
	function DCFm_007()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_posts'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Posts have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_posts",
											 'Check database permissions and post table');
				}
			}
			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('posts','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Posts'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import posts from your DCF board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of posts to import per cycle","postsperpage", 2000));
			$displayobject->update_html($displayobject->do_form_footer("Import Posts"));

			$sessionobject->add_session_var('forum_start_id', $this->get_forum_number($Db_source, $sessionobject->get_session_var('sourcedatabasetype'), $sessionobject->get_session_var('sourcetableprefix'), 'start'));
			$sessionobject->add_session_var('forum_end_id',   $this->get_forum_number($Db_source, $sessionobject->get_session_var('sourcedatabasetype'), $sessionobject->get_session_var('sourcetableprefix'), 'end'));

			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			$sessionobject->add_session_var('poststartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}


	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$post_start_at 			= $sessionobject->get_session_var('poststartat');
		$post_per_page 			= $sessionobject->get_session_var('postsperpage');

		$forum_start_id 		= $sessionobject->get_session_var('forum_start_id');
		$forum_end_id 			= $sessionobject->get_session_var('forum_end_id');

		$idcache 		= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$posts_array = $this->get_DCFm_posts_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page, $forum_start_id);

		// Sort out the threads
		$displayobject->display_now("<h4>Importing " . count($posts_array) . " posts, from forum $forum_start_id of $forum_end_id</h4><p><b>From</b> : " . $post_start_at . " ::  <b>To</b> : " . ($post_start_at + count($posts_array)) ."</p>");

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		foreach ($posts_array as $post_id => $details)
		{

			$try = (phpversion() < '5' ? $post_object : clone($post_object));

			if($details['top_id'])
			{
				$thread_num = $forum_start_id . '000000' . $details['top_id'];
			}
			else
			{
				$thread_num = $forum_start_id . '000000' . $details['id'];
			}

			// Mandatory
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $thread_num));
			$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $details['author_id']));
			$try->set_value('mandatory', 'importthreadid',		$thread_num);

			// Non Mandatory
			if($details['parent_id'] != 0)
			{
				// {FORUM}000000{post_id}
				#$temp = "{$forum_start_id}000000" . $details["parent_id"];
				#$try->set_value('nonmandatory', 'parentid', $this->get_vb_post_id($Db_target, $target_database_type, $target_table_prefix, $temp));
			}

			$try->set_value('nonmandatory', 'parentid',			'0');
			$try->set_value('nonmandatory', 'username',			$details['author_name']);
			$try->set_value('nonmandatory', 'title',			$details['subject']);
			$try->set_value('nonmandatory', 'dateline',			$this->do_dcf_date($details['mesg_date']));
			$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($details['message']));
			$try->set_value('nonmandatory', 'showsignature',	$details['use_signature']);
			$try->set_value('nonmandatory', 'importpostid',		"{$forum_start_id}000000{$post_id}");

			if($details['disable_smilies'])
			{
				$try->set_value('nonmandatory', 'allowsmilie',	'1');
			}
			else
			{
				$try->set_value('nonmandatory', 'allowsmilie',	'0');
			}

			if($this->option2bin($details['topic_hidden']))
			{
				$try->set_value('nonmandatory', 'visible',		'0');
			}
			else
			{
				$try->set_value('nonmandatory', 'visible',		'1');
			}

			// There isn't one
			#$try->set_value('nonmandatory', 'ipaddress',		$details['ipaddress']);
			#$try->set_value('nonmandatory', 'iconid',			$details['iconid']);
			#$try->set_value('nonmandatory', 'attach',			$details['attach']);


			if($try->is_valid())
			{
				if($try->import_post($Db_target,$target_database_type,$target_table_prefix))
				{
					// Get the post id with the thread order, because the children will need it

					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Post from -> " . $try->get_value('nonmandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$imported = true;
				}
				else
				{
					$sessionobject->add_error('warning',
											 $this->_modulestring,
											 get_class($this) . "::import_post failed for " . $details['author_name'],
											 'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$displayobject->display_now("<br />Got post " . $details['author_name'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}

		if (count($posts_array) == 0 OR count($posts_array) < $post_per_page)
		{
			if($forum_start_id < $forum_end_id)
			{
				$sessionobject->set_session_var('forum_start_id', $forum_start_id +1);
				$sessionobject->add_session_var('poststartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php', '0'));
			}
			else
			{
				// TODo: Remove when threding is imported
				$displayobject->display_now('Updateing parent ids to allow for a threaded view....');

				if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('Done !');
				}
				else
				{
					$displayobject->display_now('Error updating parent ids');
				}

				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html(
					$displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('posts','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('threadsstartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
			}
		}
		else
		{
			$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*/
?>

