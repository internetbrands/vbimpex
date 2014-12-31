<?php
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
* vb_36_003 Import Usergroup module
*
* @package			ImpEx.vb_36
*
*/
class vb_36_003 extends vb_36_000
{
	var $_dependent 	= '001';

	function vb_36_003(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_usergroup'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_usergroups'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['usergroups_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['usergroup_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['usergroups_per_page'],'usergroupperpage',500));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('usergroupstartat','0');
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
		$usergroup_start_at			= $sessionobject->get_session_var('usergroupstartat');
		$usergroup_per_page			= $sessionobject->get_session_var('usergroupperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$usergroup_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $usergroup_start_at, $usergroup_per_page, 'usergroup', 'usergroupid');

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($usergroup_array) . " {$displayobject->phrases['usergroups']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $usergroup_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($usergroup_start_at + count($usergroup_array)) . "</p>");

		$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

		foreach ($usergroup_array as $usergroup_id => $usergroup_details)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',		$usergroup_id);

			// Non Mandatory
			$try->set_value('nonmandatory', 'wolpermissions',		$usergroup_details['wolpermissions']);
			$try->set_value('nonmandatory', 'adminpermissions',		$usergroup_details['adminpermissions']);
			$try->set_value('nonmandatory', 'genericpermissions',	$usergroup_details['genericpermissions']);
			$try->set_value('nonmandatory', 'genericoptions',		$usergroup_details['genericoptions']);
			$try->set_value('nonmandatory', 'pmpermissions_bak',	$usergroup_details['pmpermissions_bak']);
			$try->set_value('nonmandatory', 'attachlimit',			$usergroup_details['attachlimit']);
			$try->set_value('nonmandatory', 'avatarmaxwidth',		$usergroup_details['avatarmaxwidth']);
			$try->set_value('nonmandatory', 'avatarmaxheight',		$usergroup_details['avatarmaxheight']);
			$try->set_value('nonmandatory', 'avatarmaxsize',		$usergroup_details['avatarmaxsize']);
			$try->set_value('nonmandatory', 'profilepicmaxwidth',	$usergroup_details['profilepicmaxwidth']);
			$try->set_value('nonmandatory', 'profilepicmaxheight',	$usergroup_details['profilepicmaxheight']);
			$try->set_value('nonmandatory', 'profilepicmaxsize',	$usergroup_details['profilepicmaxsize']);
			$try->set_value('nonmandatory', 'calendarpermissions',	$usergroup_details['calendarpermissions']);
			$try->set_value('nonmandatory', 'pmpermissions',		$usergroup_details['pmpermissions']);
			$try->set_value('nonmandatory', 'forumpermissions',		$usergroup_details['forumpermissions']);
			$try->set_value('nonmandatory', 'title',				'ImpEx - ' . $usergroup_details['title']);
			$try->set_value('nonmandatory', 'description',			$usergroup_details['description']);
			$try->set_value('nonmandatory', 'usertitle',			$usergroup_details['usertitle']);
			$try->set_value('nonmandatory', 'passwordexpires',		$usergroup_details['passwordexpires']);
			$try->set_value('nonmandatory', 'passwordhistory',		$usergroup_details['passwordhistory']);
			$try->set_value('nonmandatory', 'pmquota',				$usergroup_details['pmquota']);
			$try->set_value('nonmandatory', 'pmsendmax',			$usergroup_details['pmsendmax']);
			$try->set_value('nonmandatory', 'pmforwardmax',			$usergroup_details['pmforwardmax']);
			$try->set_value('nonmandatory', 'opentag',				$usergroup_details['opentag']);
			$try->set_value('nonmandatory', 'closetag',				$usergroup_details['closetag']);
			$try->set_value('nonmandatory', 'canoverride',			$usergroup_details['canoverride']);
			$try->set_value('nonmandatory', 'ispublicgroup',		$usergroup_details['ispublicgroup']);
			$try->set_value('nonmandatory', 'signaturepermissions',	$usergroup_details['signaturepermissions']);
			$try->set_value('nonmandatory', 'sigpicmaxwidth',		$usergroup_details['sigpicmaxwidth']);
			$try->set_value('nonmandatory', 'sigpicmaxheight',		$usergroup_details['sigpicmaxheight']);
			$try->set_value('nonmandatory', 'sigpicmaxsize',		$usergroup_details['sigpicmaxsize']);
			$try->set_value('nonmandatory', 'sigmaximages',			$usergroup_details['sigmaximages']);
			$try->set_value('nonmandatory', 'sigmaxsizebbcode',		$usergroup_details['sigmaxsizebbcode']);
			$try->set_value('nonmandatory', 'sigmaxchars',			$usergroup_details['sigmaxchars']);
			$try->set_value('nonmandatory', 'sigmaxrawchars',		$usergroup_details['sigmaxrawchars']);
			$try->set_value('nonmandatory', 'sigmaxlines',			$usergroup_details['sigmaxlines']);

			// Check if usergroup object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span>' . $displayobject->phrases['usergroup'] . ' -> ' . $try->get_value('nonmandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $usergroup_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);

					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['usergroup_not_imported']} :-> " . $try->_failedon);
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $usergroup_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$impex_phrases['invalid_object']} :-> " . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($usergroup_array) == 0 OR count($usergroup_array) < $usergroup_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_usergroup','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('usergroupstartat',$usergroup_start_at+$usergroup_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
