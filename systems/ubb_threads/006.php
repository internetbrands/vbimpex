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
* ubb_threads_006 Import Thread module
*
* @package			ImpEx.ubb_threads
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ubb_threads_006 extends ubb_threads_000
{
	var $_dependent 	= '005';

	function ubb_threads_006(&$displayobject)
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


		// Per page vars
		$thread_start_at		= $sessionobject->get_session_var('threadstartat');
		$thread_per_page		= $sessionobject->get_session_var('threadperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of thread details
		$thread_array 	= $this->get_ubb_threads_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);

		$forum_ids_array 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$forum_word_array 	= $this->get_forum_by_keyword($Db_source, $source_database_type, $source_table_prefix);

		$idcache = new ImpExCache($Db_target, $target_database_type, $target_table_prefix);


		// Check for page end
		if ($source_database_type == 'mysql')
		{
			$count_number = count($thread_array['data']);
		}
		else
		{
			$count_number = count($thread_array);
		}

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} {$count_number} {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($thread_start_at + $count_number) . "</p>");

		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');


		foreach ($thread_array['data'] as $thread_id => $thread_details)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
			// Mandatory
			$try->set_value('mandatory', 'title',				$thread_details['B_Subject']);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array[$forum_word_array[$thread_details['B_Board']]]);
			$try->set_value('mandatory', 'importthreadid',		$thread_details['B_Number']);
			$try->set_value('mandatory', 'importforumid',		$forum_word_array[$thread_details['B_Board']]);


			// Non Mandatory
			$try->set_value('nonmandatory', 'pollid',			$thread_details['B_Poll']);

			switch ($thread_details['B_Status']) // M = moved ?
			{
				case 'O' :
					$try->set_value('nonmandatory', 'open',		'1');
					break;

				case 'C' :
					$try->set_value('nonmandatory', 'open',		'0');
					break;

				default :
					$try->set_value('nonmandatory', 'open',		'1');
					break;
			}

			$try->set_value('nonmandatory', 'replycount',		$thread_details['B_Counter']);
			$try->set_value('nonmandatory', 'postusername',		$idcache->get_id('username', $thread_details['B_PosterId']));
			$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $thread_details['B_PosterId']));
			$try->set_value('nonmandatory', 'lastposter',		$idcache->get_id('user', $thread_details['LastPosterId']));
			$try->set_value('nonmandatory', 'dateline',			$thread_details['B_Posted']);
			$try->set_value('nonmandatory', 'sticky',			$thread_details['B_Sticky']);
			$try->set_value('nonmandatory', 'visible',			'1');

			// Check if thread object is valid
			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
					}

					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					if(shortoutput)
					{
						$displayobject->display_now('X');
					}
					else
					{
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
					}

					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $thread_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				}
			}
			else
			{
				if(shortoutput)
				{
					$displayobject->display_now('X');
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}

				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End resume

		if ($count_number == 0 OR $count_number < $thread_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring, $sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'), $sessionobject->return_stats($class_num, '_objects_failed')));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}


		$sessionobject->set_session_var('threadstartat',$thread_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : May 17, 2004, 10:34 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
