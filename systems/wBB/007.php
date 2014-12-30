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
* wBB Import Threads
*
* @package 		ImpEx.wBB
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_007 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Threads';

	function wBB_007()
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
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import threads from your phpBB board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of threads to import per cycle","threadsperpage","500"));
			$displayobject->update_html($displayobject->do_form_footer("Import Threads"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


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
		if ($sessionobject->get_session_var('threads') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$thread_start_at 		= $sessionobject->get_session_var('threadsstartat');
			$threads_per_page 		= $sessionobject->get_session_var('threadsperpage');

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$threads_array 			= $this->get_wBB_threads_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $threads_per_page);
			$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			// Sort out the threads
			$displayobject->display_now("<h4>Importing " . count($threads_array) . " threads</h4><p><b>From</b> : " . $thread_start_at . " ::  <b>To</b> : " . ($thread_start_at + $threads_per_page) ."</p>");

			$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

			foreach ($threads_array as $thread_id => $thread)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

				$try->set_value('mandatory', 'title', 			$thread['topic']);
				$try->set_value('mandatory', 'forumid', 		$forum_ids[$thread['boardid']]);
				$try->set_value('mandatory', 'importthreadid', 	$thread['threadid']);
				$try->set_value('mandatory', 'importforumid', 	$thread['boardid']);

				$try->set_value('nonmandatory', 'iconid', 		$thread['iconid']);
				$try->set_value('nonmandatory', 'visible', 		$thread['visible']);
				$try->set_value('nonmandatory', 'postusername',	$user_names["$thread[lastposterid]"]);
				$try->set_value('nonmandatory', 'postuserid', 	$users_ids["$thread[starterid]"]);
				$try->set_value('nonmandatory', 'open', 		$this->iif($thread['closed'] == 0,'1','0'));
				$try->set_value('nonmandatory', 'starterid', 	$users_ids["$thread[starterid]"]);
				$try->set_value('nonmandatory', 'lastposter', 	$users_ids["$thread[lastposterid]"]);
				$try->set_value('nonmandatory', 'views', 		$thread['views']);
				$try->set_value('nonmandatory', 'replycount', 	$thread['replycount']);
				$try->set_value('nonmandatory', 'dateline', 	$thread['starttime']);
				$try->set_value('nonmandatory', 'votetotal', 	$thread['voted']);
				$try->set_value('nonmandatory', 'lastpost', 	$thread['lastposttime']);

				/*
				$try->set_value('nonmandatory', 'pollid', );
				$try->set_value('nonmandatory', 'lastpost', 	$thread['topic_last_post_id']);
				$try->set_value('nonmandatory', 'notes', );
				$try->set_value('nonmandatory', 'visible', );
				$try->set_value('nonmandatory', 'sticky', );
				$try->set_value('nonmandatory', 'votenum', );

				$try->set_value('nonmandatory', 'attach', );
				$try->set_value('nonmandatory', 'similar', );

				votepoints
				attachments
				pollid
				important
				*/


				if($try->is_valid())
				{
					if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_thread failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got thread " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
				}
				unset($try);
			}

			$redirect_time = 0;

			if (count($threads_array) == 0 OR count($threads_array) < $threads_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																			$sessionobject->return_stats($class_num, '_time_taken'),
																			$sessionobject->return_stats($class_num, '_objects_done'),
																			$sessionobject->return_stats($class_num, '_objects_failed')
																			)
											);

				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('threads','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('threadsstartat','0');
				$redirect_time = 1;
			}
			$sessionobject->set_session_var('threadsstartat',$thread_start_at+$threads_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php', $redirect_time));
		}
	}


	function get_phpbb2_threads_details(&$DB_object, &$database_type, &$table_prefix, &$thread_start_at, &$threads_per_page)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$table_prefix."topics
			LIMIT " .
			$thread_start_at .",".
			$threads_per_page
			;

			$threads = $DB_object->query($sql);

			while ($thread = $DB_object->fetch_array($threads))
			{
				$return_array[$thread['topic_id']] = $thread;
				unset($thread);
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
}
/*======================================================================*/
?>
