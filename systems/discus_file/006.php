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
* discus_file Import Threads
*
* @package 		ImpEx.discus_file
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class discus_file_006 extends discus_file_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Threads';

	function discus_file_006()
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
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import threads from your Discuss board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of threads to import per cycle","threadsperpage","100"));
			$displayobject->update_html($displayobject->do_form_footer("Import Threads"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			$sessionobject->add_session_var('currentforum','0');
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

			$current_forum			= $sessionobject->get_session_var('currentforum');

			if($current_forum == 0)
			{
				$current_forum 		= $this->get_first_cat_number($Db_target, $target_database_type, $target_table_prefix);
			}


			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$threads_array 			= $this->get_discus_file_threads_details($sessionobject->get_session_var('messagesspath'), $current_forum, $thread_start_at, $threads_per_page);
			$cat_ids 				= $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
			$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			// Sort out the threads
			$displayobject->display_now("<h4>Importing from forum : " . $current_forum . "</h4>");
			$displayobject->display_now("<h4>Importing " . count($threads_array) . " threads</h4><p><b>From</b> : " . $thread_start_at . " ::  <b>To</b> : " . ($thread_start_at + count($threads_array)) ."</p>");

			$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

			foreach ($threads_array as $thread_id => $thread)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

				$try->set_value('mandatory', 'title', 			$thread['title']);
				
				if($forum_ids[$current_forum])
				{
					$try->set_value('mandatory', 'forumid', 	$forum_ids[$current_forum]);
				}
				else
				{
					if($cat_ids[$current_forum])
					{
						$try->set_value('mandatory', 'forumid', 		$cat_ids[$current_forum]);
					}
				}
				
				$try->set_value('mandatory', 'importthreadid', 	$thread_id);
				$try->set_value('mandatory', 'importforumid', 	$current_forum);

				$try->set_value('nonmandatory', 'dateline', 	$thread['dateline']);
				$try->set_value('nonmandatory', 'visible', 		'1');
				$try->set_value('nonmandatory', 'open', 		'1');


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

			$redirect_time = 1;

			if (count($threads_array) == 0 OR count($threads_array) < $threads_per_page)
			{
				// Check for the next forum to import threads from

				$next_forum = $this->get_next_cat_id($Db_target, $target_database_type, $target_table_prefix, $current_forum);

				if($next_forum == NULL)
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
					$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
				}
				else
				{
					$sessionobject->set_session_var('currentforum',$next_forum);
					$sessionobject->add_session_var('threadsstartat','0');
					$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
				}
			}
			else
			{
				$sessionobject->set_session_var('threadsstartat',$thread_start_at+$threads_per_page);
				$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
			}
		}
	}
}
/*======================================================================*/
?>
