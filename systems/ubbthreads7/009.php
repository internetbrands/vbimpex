<?php
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.ubbthreads7
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class ubbthreads7_009 extends ubbthreads7_000
{
	var $_dependent = '005';

	function ubbthreads7_009(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_moderator'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_moderators'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['moderators_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['moderator_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_moderator']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("009_objects_done", '0');
			$sessionobject->add_session_var("009_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}MODERATORS", 'USER_ID', 0, $start_at, $per_page);

		$user_ids_array = $this->get_user_ids($Db_target, $t_db_type, $t_tb_prefix);
		$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['moderators'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'moderator');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			$try->set_value('mandatory', 'userid',				$user_ids_array["$data[USER_ID]"]);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array["$data[FORUM_ID]"]);
			$try->set_value('mandatory', 'importmoderatorid',	$import_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'permissions',		$this->_default_mod_permissions);

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_moderator($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['moderator'] . ' -> ' . $data['USER_ID']);
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['moderator_not_imported'], $displayobject->phrases['moderator_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['moderator_not_imported']}");
				}// $try->import_moderator
			}
			else
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}// is_valid
			unset($try);
		}// End foreach

		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : January 19, 2007, 11:16 am
# By ImpEx-generator 2.0
/*======================================================================*/
?>
