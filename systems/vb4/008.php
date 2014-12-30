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
* vb4 Import Threads
*
* @package 		ImpEx.vb4
* @date 		$Date: 2007-07-23 14:13:50 -0700 (Mon, 23 Jul 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb4_008 extends vb4_000
{
	var $_dependent 	= '007';

	function vb4_008(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_thread'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['threads_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['thread_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_thread']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'],'threadperpage', 2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		$thread_start_at		= $sessionobject->get_session_var('threadstartat');
		$thread_per_page		= $sessionobject->get_session_var('threadperpage');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		$thread_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page, 'thread', 'threadid');
		$forum_ids = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($thread_array) . " {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($thread_start_at + count($thread_array)) . "</p>");

		foreach ($thread_array as $thread_id => $details)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

			// Mandatory
			$try->set_value('mandatory', 'title',				$details['title']);
			$try->set_value('mandatory', 'forumid',				$forum_ids["$details[forumid]"]);
			$try->set_value('mandatory', 'importthreadid',		$thread_id);
			$try->set_value('mandatory', 'importforumid',		$details['forumid']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'firstpostid',		$details['firstpostid']);
			$try->set_value('nonmandatory', 'lastpost',			$details['lastpost']);
			$try->set_value('nonmandatory', 'pollid',			$details['pollid']);
			$try->set_value('nonmandatory', 'open',				$details['open']);
			$try->set_value('nonmandatory', 'replycount',		$details['replycount']);
			$try->set_value('nonmandatory', 'postusername',		$details['postusername']);
			$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $details['postuserid']));
			$try->set_value('nonmandatory', 'lastposter',		$details['lastposter']);
			$try->set_value('nonmandatory', 'dateline',			$details['dateline']);
			$try->set_value('nonmandatory', 'views',			$details['views']);
			$try->set_value('nonmandatory', 'iconid',			$details['iconid']); // Might need changing on custom boards
			$try->set_value('nonmandatory', 'notes',			$details['notes']);
			$try->set_value('nonmandatory', 'visible',			$details['visible']);
			$try->set_value('nonmandatory', 'sticky',			$details['sticky']);
			$try->set_value('nonmandatory', 'votenum',			$details['votenum']);
			$try->set_value('nonmandatory', 'votetotal',		$details['votetotal']);
			$try->set_value('nonmandatory', 'attach',			$details['attach']);
			$try->set_value('nonmandatory', 'similar',			$details['similar']);


			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($thread_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}


		if (count($thread_array) == 0 OR count($thread_array) < $thread_per_page)
		{
			$displayobject->display_now("<p>Updating pollids for new threads</p>");
			$this->update_poll_ids($Db_target, $target_database_type, $target_table_prefix);

			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num ,'_time_taken'),
				$sessionobject->return_stats($class_num ,'_objects_done'),
				$sessionobject->return_stats($class_num ,'_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}
}
/*======================================================================*/
?>
