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
* DCFm Import Threads
*
* @package 		ImpEx.DCFm
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class DCFm_006 extends DCFm_000
{
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Threads';
	
	function DCFm_006()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Threads have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_threads",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import threads');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('threads','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Threads'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import threads from your DCF board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of threads to import per cycle","threadsperpage", 2000));
			$displayobject->update_html($displayobject->do_form_footer("Import Threads"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			// Get the beginning and end
			$sessionobject->add_session_var('forum_start_id', $this->get_forum_number($Db_source, $sessionobject->get_session_var('sourcedatabasetype'), $sessionobject->get_session_var('sourcetableprefix'), 'start'));
			$sessionobject->add_session_var('forum_end_id', $this->get_forum_number($Db_source, $sessionobject->get_session_var('sourcedatabasetype'), $sessionobject->get_session_var('sourcetableprefix'), 'end'));

			$sessionobject->add_session_var('threadsstartat','0');
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

		$thread_start_at 		= $sessionobject->get_session_var('threadsstartat');
		$threads_per_page 		= $sessionobject->get_session_var('threadsperpage');

		$forum_start_id 		= $sessionobject->get_session_var('forum_start_id');
		$forum_end_id 			= $sessionobject->get_session_var('forum_end_id');
		$class_num				= substr(get_class($this) , -3);
		$idcache 		= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$threads_array 			= $this->get_DCFm_threads_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $threads_per_page, $forum_start_id);
		$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$cat_ids				= $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

		// Sort out the threads
		$displayobject->display_now("<h4>Importing " . count($threads_array) . " threads, from forum $forum_start_id of $forum_end_id</h4><p><b>From</b> : " . $thread_start_at . " ::  <b>To</b> : " . ($thread_start_at + count($threads_array)) ."</p>");

		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

		foreach ($threads_array as $thread_id => $thread)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

			$try->set_value('mandatory', 'title', 			$thread['subject']);

			if($forum_ids[$forum_start_id])
			{
				$try->set_value('mandatory', 'forumid', 		$forum_ids[$forum_start_id]);
			}

			if($cat_ids[$forum_start_id])
			{
				$try->set_value('mandatory', 'forumid', 		$cat_ids[$forum_start_id]);
			}

			$try->set_value('mandatory', 'importthreadid', 	$forum_start_id . '000000' . $thread['id']);
			$try->set_value('mandatory', 'importforumid', 	$forum_start_id);


			$try->set_value('nonmandatory', 'dateline', 	$this->do_dcf_date($thread['mesg_date']));
			$try->set_value('nonmandatory', 'views', 		$thread['views']);

			if($this->option2bin($thread['topic_lock']))
			{
				$try->set_value('nonmandatory', 'open',		'0');
			}
			else
			{
				$try->set_value('nonmandatory', 'open',		'1');
			}

			$try->set_value('nonmandatory', 'sticky',		$thread['topic_pin']);
			$try->set_value('nonmandatory', 'replycount', 	$thread['replies']);
			$try->set_value('nonmandatory', 'postusername',	$idcache->get_id('username', $thread['author_id']));
			$try->set_value('nonmandatory', 'postuserid', 	$idcache->get_id('user', $thread['topic_poster']));

			if($thread['topic_hidden'] == 'off')
			{
				$try->set_value('nonmandatory', 'visible', '1');
			}

			if($thread['topic_hidden'] == 'on')
			{
				$try->set_value('nonmandatory', 'visible', '0');
			}

			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->add_error('warning',
											 $this->_modulestring,
											 get_class($this) . "::import_thread failed for " . $try->get_value('mandatory','title') . " get_DCFm_categories_details was ok.",
											 'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$displayobject->display_now("<br />Got thread " . $try->get_value('mandatory','title') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
			}
			unset($try);
		}


		if (count($threads_array) == 0 OR count($threads_array) < $threads_per_page)
		{
			if($forum_start_id < $forum_end_id)
			{
				$sessionobject->set_session_var('forum_start_id', $forum_start_id +1);
				$sessionobject->add_session_var('threadsstartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
			else
			{

				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('threads','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('threadsstartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
		}
		else
		{
			$sessionobject->set_session_var('threadsstartat',$thread_start_at+$threads_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}
}
/*======================================================================*/
?>
