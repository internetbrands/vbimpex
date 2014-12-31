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
* wBB Import Posts
*
* @package 		ImpEx.wBB
*
*/
class wBB_009 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '007';
	var $_modulestring 	= 'Import Posts';

	function wBB_009()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
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
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('posts','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Posts'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import posts from your wBB board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of posts to import per cycle","postsperpage","2000"));
			$displayobject->update_html($displayobject->do_form_footer("Import posts"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('postsstartat','0');
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
		if ($sessionobject->get_session_var('posts') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$posts_start_at 		= $sessionobject->get_session_var('postsstartat');
			$posts_per_page 		= $sessionobject->get_session_var('postsperpage');
			
			$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($posts_per_page) == 0)
			{
				$posts_per_page = 150;
			}

			$posts_array 			= $this->get_wBB_posts_details($Db_source, $source_database_type, $source_table_prefix, $posts_start_at, $posts_per_page);

			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($posts_array) . " posts</h4><p><b>From</b> : " . $posts_start_at . " ::  <b>To</b> : " . ($posts_start_at + count($posts_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$start = time();

			$post_object = new ImpExData($Db_target, $sessionobject, 'post');

			foreach ($posts_array as $post_id => $post)
			{
				$try = (phpversion() < '5' ? $post_object : clone($post_object));
				$try->set_value('mandatory', 'threadid', 			$idcache->get_id('thread', $post['threadid']));
				$try->set_value('mandatory', 'userid', 				$idcache->get_id('user', $post['userid']));
				$try->set_value('mandatory', 'importthreadid', 		$post['threadid']);

				if($post['parentpostid '] == 0)
				{
					$try->set_value('nonmandatory', 'parentid',			'0');
				}
				else
				{
					$try->set_value('nonmandatory', 'parentid',
							$this->get_vb_post_id($Db_target, $target_database_type, $target_table_prefix, $post['parentpostid']));
				}

				$try->set_value('nonmandatory', 'importpostid',		$post_id);
				$try->set_value('nonmandatory', 'iconid',			$post['iconid']);
				$try->set_value('nonmandatory', 'dateline',			$post['posttime']);
				$try->set_value('nonmandatory', 'allowsmilie', 		$post['allowsmilies']);
				$try->set_value('nonmandatory', 'showsignature', 	$post['showsignature']);
				$try->set_value('nonmandatory', 'username', 		$post['username']);
				$try->set_value('nonmandatory', 'visible',			$post['visible']);
				$try->set_value('nonmandatory', 'pagetext', 		$this->wbb_html($post['message']));
				$try->set_value('nonmandatory', 'ipaddress',		$post['ipaddress']);
				$try->set_value('nonmandatory', 'title', 			$post['posttopic']);

				// TODO: edited details i.e. edit log
				// TODO: attachemnts, will ned importattachmentid etc, maybe clean up in a later module


				if($try->is_valid())
				{
					if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Post from -> " . $try->get_value('nonmandatory','username'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_thread failed for " . $post['posttopic'] . " get_wbb_post was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got thread " . $post['posttopic'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
				}
				unset($try);
			}

			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($posts_array) == 0 OR count($posts_array) < $posts_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																			$sessionobject->return_stats($class_num, '_time_taken'),
																			$sessionobject->return_stats($class_num, '_objects_done'),
																			$sessionobject->return_stats($class_num, '_objects_failed')
																			));
				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('posts','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
			$sessionobject->set_session_var('postsstartat',$posts_start_at+$posts_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$displayobject->display_now('Going to the main page...');
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*/
?>
