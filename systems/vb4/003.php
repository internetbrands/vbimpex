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
* vb4 Import Users groups and ranks
*
* @package 		ImpEx.vb4
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2008-07-30 10:00:00 -0700 (Wed, 30 Jul 2008) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb4_003 extends vb4_000
{
	var $_dependent 	= '001';

	function vb4_003($displayobject)
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
			$displayobject->update_basic('title',$displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['usergroups_all']));

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
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// *************
		// Usergroups
		// *************

		// Get all the user groups
		$usergroup_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, 0, -1, 'usergroup', 'usergroupid');


		$usergroup_object = new ImpExData($Db_target, $sessionobject,'usergroup');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($usergroup_array) . " {$displayobject->phrases['usergroups']}</h4>");

		// Do the user group array
		foreach ($usergroup_array as $user_group_id => $user_group)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));

			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',		$user_group_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'title',				'ImpEx - ' . $user_group['title']);
			$try->set_value('nonmandatory', 'description',			$user_group['description']);
			$try->set_value('nonmandatory', 'usertitle',			$user_group['usertitle']);
			$try->set_value('nonmandatory', 'passwordexpires',		$user_group['passwordexpires']);
			$try->set_value('nonmandatory', 'passwordhistory',		$user_group['passwordhistory']);
			$try->set_value('nonmandatory', 'pmquota',				$user_group['pmquota']);
			$try->set_value('nonmandatory', 'pmsendmax',			$user_group['pmsendmax']);
			$try->set_value('nonmandatory', 'pmforwardmax',			$user_group['pmforwardmax']);
			$try->set_value('nonmandatory', 'opentag',				$user_group['opentag']);
			$try->set_value('nonmandatory', 'closetag',				$user_group['closetag']);
			$try->set_value('nonmandatory', 'canoverride',			$user_group['canoverride']);
			$try->set_value('nonmandatory', 'ispublicgroup',		$user_group['ispublicgroup']);
			$try->set_value('nonmandatory', 'forumpermissions',		$user_group['forumpermissions']);
			$try->set_value('nonmandatory', 'pmpermissions',		$user_group['pmpermissions']);
			$try->set_value('nonmandatory', 'calendarpermissions',	$user_group['calendarpermissions']);
			$try->set_value('nonmandatory', 'wolpermissions',		$user_group['wolpermissions']);
			$try->set_value('nonmandatory', 'adminpermissions',		$user_group['adminpermissions']);
			$try->set_value('nonmandatory', 'genericpermissions',	$user_group['genericpermissions']);
			$try->set_value('nonmandatory', 'genericoptions',		$user_group['genericoptions']);
			$try->set_value('nonmandatory', 'pmpermissions_bak',	$user_group['pmpermissions_bak']);
			$try->set_value('nonmandatory', 'attachlimit',			$user_group['attachlimit']);
			$try->set_value('nonmandatory', 'avatarmaxwidth',		$user_group['avatarmaxwidth']);
			$try->set_value('nonmandatory', 'avatarmaxheight',		$user_group['avatarmaxheight']);
			$try->set_value('nonmandatory', 'avatarmaxsize',		$user_group['avatarmaxsize']);
			$try->set_value('nonmandatory', 'profilepicmaxwidth',	$user_group['profilepicmaxwidth']);
			$try->set_value('nonmandatory', 'profilepicmaxheight',	$user_group['profilepicmaxheight']);
			$try->set_value('nonmandatory', 'profilepicmaxsize',	$user_group['profilepicmaxsize']);


			if($try->is_valid())
			{
				if($try->import_user_group($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $user_group['title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($user_group_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['usergroup_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
		}// foreach

		// *************
		// Ranks
		// *************

		$rank_object = new ImpExData($Db_target, $sessionobject,'ranks');


		// Get all the user group details from the target dB so we can match them to the
		$usergroup_array = $this->get_details($Db_target, $targete_database_type, $target_table_prefix, 0, -1, 'usergroup', 'importusergroupid');

		// Get the ranks from the source database to import
		$ranks_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, 0, -1, 'ranks', 'rankid');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($ranks_array) . " {$displayobject->phrases['ranks']}</h4>");


		// Do the ranks array
		foreach ($ranks_array as $rank_id => $rank)
		{
			$try = (phpversion() < '5' ? $rank_object : clone($rank_object));

			// Mandatory
			$try->set_value('mandatory', 'importrankid',	$rank_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'minposts',		$rank['minposts']);
			$try->set_value('nonmandatory', 'ranklevel',	$rank['ranklevel']);
			$try->set_value('nonmandatory', 'rankimg',		$rank['rankimg']);
			$try->set_value('nonmandatory', 'usergroupid',	$usergroup_array["$rank[usergroupid]"]);
			$try->set_value('nonmandatory', 'type',			$rank['type']);


			if($try->is_valid())
			{
				if($try->import_rank($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span>' . $displayobject->phrases['rank'] . ' -> ' . $try->get_value('nonmandatory','type'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($user_id, $displayobject->phrases['rank_not_imported'], $displayobject->phrases['rank_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['rank_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
		}// foreach


		// All on one page, no page count
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
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zeveuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
