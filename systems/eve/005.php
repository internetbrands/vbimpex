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
* eve_005 Import Thread module
*
* @package			ImpEx.eve
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class eve_005 extends eve_000
{
	var $_dependent 	= '004';

	function eve_005(&$displayobject)
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
			$displayobject->update_html($displayobject->make_hidden_code('import_thread','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'],'threadperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code('Do you want to close archived threads ?', 'close_archive',0));
			
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

		$forum_ids_array 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

		// Get an array of thread details
		$data_array			= $this->get_eve_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($data_array) . " {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($thread_start_at + count($data_array)) . "</p>");
			
		#http://thailand-uk.com/eve/forums/a/frm/f/30110002
		foreach ($data_array as $thread_id => $data)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

		// Mandatory
			// Legacy <NOSCRIPT ?
			$try->set_value('mandatory', 'title',			$data['SUBJECT']);
			if ($forum_ids_array["$data[FORUM_OID]"]) {
				$try->set_value('mandatory', 'forumid',			$forum_ids_array["$data[FORUM_OID]"]);
			} else {
				echo "Form " . $data['FORUM_OID'];
				#var_dump($data);
				#die;
			}
			
			// Private messaging forum skip it
			if ($try->get_value('mandatory', 'forumid') == 0)
				continue;

			$try->set_value('mandatory', 'importthreadid',	$data['TOPIC_OID']);
			$try->set_value('mandatory', 'importforumid',	$data['FORUM_OID']);

		// Non Mandatory
			$try->set_value('nonmandatory', 'open',			($data['IS_TOPIC_CLOSED'] == 0 ? 1 : 0));
			if ($sessionobject->get_session_var('close_archive'))
				$try->set_value('nonmandatory', 'open',		($data['IS_TOPIC_ARCHIVED'] == 0 ? 0 : 1));
			
			$try->set_value('nonmandatory', 'views',		$data['MESSAGE_PAGE_VIEW_COUNT']);
			$try->set_value('nonmandatory', 'dateline',		strtotime($data['TOPIC_POSTED_DATETIME']));
			$try->set_value('nonmandatory', 'visible',		'1');
			$try->set_value('nonmandatory', 'sticky',		'0');
			$try->set_value('nonmandatory', 'replycount', 	$data['TOPIC_POST_COUNT']);
			$try->set_value('nonmandatory', 'views', 		$data['MESSAGE_PAGE_VIEW_COUNT']);
			
			// Check if thread object is valid
			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
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
		}// End foreach
			
		// Check for page end
		if (count($data_array) == 0 OR count($data_array) < $thread_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_thread','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : March 8, 2005, 12:17 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
