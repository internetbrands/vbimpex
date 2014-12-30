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
* @package			ImpEx.mysmartbb
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class mysmartbb_003 extends mysmartbb_000
{
	var $_dependent = '001';

	function mysmartbb_003(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_usergroup'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_usergroups'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['usergroups_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['usergroup_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("003_objects_done", '0');
			$sessionobject->add_session_var("003_objects_failed", '0');
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
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}group", 'id', 0, $start_at, $per_page);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['usergroups'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',			$import_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'title',					$data["title"]);

			/*
  ["id"]=>
  ["title"]=>
  ["username_style"]=>
  ["user_title"]=>
  ["forum_team"]=>
  ["banned"]=>
  ["view_section"]=>
  ["download_attach"]=>
  ["write_subject"]=>
  ["write_reply"]=>
  ["upload_attach"]=>
  ["edit_own_subject"]=>
  ["edit_own_reply"]=>
  ["del_own_subject"]=>
  ["del_own_reply"]=>
  ["write_poll"]=>
  ["vote_poll"]=>
  ["use_pm"]=>
  ["send_pm"]=>
  ["resive_pm"]=>
  ["max_pm"]=>
  ["sig_allow"]=>
  ["sig_len"]=>
  ["group_mod"]=>
  ["del_subject"]=>
  ["del_reply"]=>
  ["edit_subject"]=>
  ["edit_reply"]=>
  ["stick_subject"]=>
  ["unstick_subject"]=>
  ["move_subject"]=>
  ["close_subject"]=>
  ["usercp_allow"]=>
  ["admincp_allow"]=>
  ["search_allow"]=>
  ["memberlist_allow"]=>
  ["vice"]=>
  ["show_hidden"]=>
  ["view_usernamestyle"]=>
  ["usertitle_change"]=>
  ["onlinepage_allow"]=>
  ["admincp_section"]=>
  ["admincp_option"]=>
  ["admincp_member"]=>
  ["admincp_membergroup"]=>
  ["admincp_membertitle"]=>
  ["admincp_admin"]=>
  ["admincp_adminstep"]=>
  ["admincp_subject"]=>
  ["admincp_database"]=>
  ["admincp_fixup"]=>
  ["admincp_ads"]=>
  ["admincp_template"]=>
  ["admincp_adminads"]=>
  ["admincp_attach"]=>
  ["admincp_page"]=>
  ["admincp_block"]=>
  ["admincp_style"]=>
  ["admincp_toolbox"]=>
  ["admincp_smile"]=>
  ["admincp_icon"]=>
  ["admincp_avater"]=>
  ["group_order"]=>
  ["admincp_contactus"]=>
  ["lp_admin"]=>
  ["download_attach_number"]=>
  ["upload_attach_num"]=>
  ["min_send_pm"]=>
  ["allow_see_offstyles"]=>

			/*
			$try->set_value('nonmandatory', 'attachlimit',		$data[""]);
			$try->set_value('nonmandatory', 'avatarmaxwidth',		$data[""]);
			$try->set_value('nonmandatory', 'avatarmaxheight',		$data[""]);
			$try->set_value('nonmandatory', 'avatarmaxsize',		$data[""]);
			$try->set_value('nonmandatory', 'profilepicmaxwidth',		$data[""]);
			$try->set_value('nonmandatory', 'profilepicmaxheight',		$data[""]);
			$try->set_value('nonmandatory', 'profilepicmaxsize',		$data[""]);
			$try->set_value('nonmandatory', 'signaturepermissions',		$data[""]);
			$try->set_value('nonmandatory', 'sigpicmaxwidth',		$data[""]);
			$try->set_value('nonmandatory', 'sigpicmaxheight',		$data[""]);
			$try->set_value('nonmandatory', 'sigpicmaxsize',		$data[""]);
			$try->set_value('nonmandatory', 'sigmaximages',		$data[""]);
			$try->set_value('nonmandatory', 'sigmaxsizebbcode',		$data[""]);
			$try->set_value('nonmandatory', 'sigmaxchars',		$data[""]);
			$try->set_value('nonmandatory', 'sigmaxrawchars',		$data[""]);
			$try->set_value('nonmandatory', 'pmpermissions_bak',		$data[""]);
			$try->set_value('nonmandatory', 'genericoptions',		$data[""]);
			$try->set_value('nonmandatory', 'genericpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'description',		$data[""]);
			$try->set_value('nonmandatory', 'usertitle',		$data[""]);
			$try->set_value('nonmandatory', 'passwordexpires',		$data[""]);
			$try->set_value('nonmandatory', 'passwordhistory',		$data[""]);
			$try->set_value('nonmandatory', 'pmquota',		$data[""]);
			$try->set_value('nonmandatory', 'pmsendmax',		$data[""]);
			$try->set_value('nonmandatory', 'pmforwardmax',		$data[""]);
			$try->set_value('nonmandatory', 'opentag',		$data[""]);
			$try->set_value('nonmandatory', 'closetag',		$data[""]);
			$try->set_value('nonmandatory', 'canoverride',		$data[""]);
			$try->set_value('nonmandatory', 'ispublicgroup',		$data[""]);
			$try->set_value('nonmandatory', 'forumpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'pmpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'calendarpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'wolpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'adminpermissions',		$data[""]);
			$try->set_value('nonmandatory', 'sigmaxlines',		$data[""]);
			*/

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $data['']);
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['usergroup_not_imported']}");
				}// $try->import_usergroup
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
# Autogenerated on : January 18, 2007, 1:49 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
