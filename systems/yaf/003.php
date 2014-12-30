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
* @package			ImpEx.yaf
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class yaf_003 extends yaf_000
{
	var $_dependent = '001';

	function yaf_003(&$displayobject)
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
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
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
		$data_array = $this->get_yaf_usergroups($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);

		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $t_db_type, $t_tb_prefix, $importuserid);
		#$this->get_one_username($Db_target, $t_db_type, $t_tb_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $t_db_type, $t_tb_prefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_ids_array = $this->get_user_ids($Db_target, $t_db_type, $t_tb_prefix, $do_int_val = false);
		#$user_name_array = $this->get_username_to_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Groups info
		#$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $t_db_type, $t_tb_prefix);
		#$bannded_groupid = $this->get_banned_group($Db_target, $t_db_type, $t_tb_prefix);
		// Thread info
		#$this->get_thread_id($Db_target, $t_db_type, $t_tb_prefix, &$importthreadid, &$forumid); // & left to show refrence
		#$thread_ids_array = $this->get_threads_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Post info
		#$this->get_posts_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Category info
		#$cat_ids_array = $this->get_category_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$style_ids_array = $this->get_style_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);
		// Forum info
		#$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['usergroups'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',	$import_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'title',			$data['Name']);
			/*
			$try->set_value('nonmandatory', 'attachlimit',		$data['attachlimit']);
			$try->set_value('nonmandatory', 'avatarmaxwidth',		$data['avatarmaxwidth']);
			$try->set_value('nonmandatory', 'avatarmaxheight',		$data['avatarmaxheight']);
			$try->set_value('nonmandatory', 'avatarmaxsize',		$data['avatarmaxsize']);
			$try->set_value('nonmandatory', 'profilepicmaxwidth',		$data['profilepicmaxwidth']);
			$try->set_value('nonmandatory', 'profilepicmaxheight',		$data['profilepicmaxheight']);
			$try->set_value('nonmandatory', 'profilepicmaxsize',		$data['profilepicmaxsize']);
			$try->set_value('nonmandatory', 'signaturepermissions',		$data['signaturepermissions']);
			$try->set_value('nonmandatory', 'sigpicmaxwidth',		$data['sigpicmaxwidth']);
			$try->set_value('nonmandatory', 'sigpicmaxheight',		$data['sigpicmaxheight']);
			$try->set_value('nonmandatory', 'sigpicmaxsize',		$data['sigpicmaxsize']);
			$try->set_value('nonmandatory', 'sigmaximages',		$data['sigmaximages']);
			$try->set_value('nonmandatory', 'sigmaxsizebbcode',		$data['sigmaxsizebbcode']);
			$try->set_value('nonmandatory', 'sigmaxchars',		$data['sigmaxchars']);
			$try->set_value('nonmandatory', 'sigmaxrawchars',		$data['sigmaxrawchars']);
			$try->set_value('nonmandatory', 'pmpermissions_bak',		$data['pmpermissions_bak']);
			$try->set_value('nonmandatory', 'genericoptions',		$data['genericoptions']);
			$try->set_value('nonmandatory', 'genericpermissions',		$data['genericpermissions']);
			$try->set_value('nonmandatory', 'description',		$data['description']);
			$try->set_value('nonmandatory', 'usertitle',		$data['usertitle']);
			$try->set_value('nonmandatory', 'passwordexpires',		$data['passwordexpires']);
			$try->set_value('nonmandatory', 'passwordhistory',		$data['passwordhistory']);
			$try->set_value('nonmandatory', 'pmquota',		$data['pmquota']);
			$try->set_value('nonmandatory', 'pmsendmax',		$data['pmsendmax']);
			$try->set_value('nonmandatory', 'pmforwardmax',		$data['pmforwardmax']);
			$try->set_value('nonmandatory', 'opentag',		$data['opentag']);
			$try->set_value('nonmandatory', 'closetag',		$data['closetag']);
			$try->set_value('nonmandatory', 'canoverride',		$data['canoverride']);
			$try->set_value('nonmandatory', 'ispublicgroup',		$data['ispublicgroup']);
			$try->set_value('nonmandatory', 'forumpermissions',		$data['forumpermissions']);
			$try->set_value('nonmandatory', 'pmpermissions',		$data['pmpermissions']);
			$try->set_value('nonmandatory', 'calendarpermissions',		$data['calendarpermissions']);
			$try->set_value('nonmandatory', 'wolpermissions',		$data['wolpermissions']);
			$try->set_value('nonmandatory', 'adminpermissions',		$data['adminpermissions']);
			$try->set_value('nonmandatory', 'sigmaxlines',		$data['sigmaxlines']);
			*/

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $data['Name']);
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

		#$sessionobject->set_session_var('startat', $data_array['lastid']);
		$sessionobject->set_session_var('startat', $start_at + $per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : January 21, 2007, 10:00 am
# By ImpEx-generator 2.0
/*======================================================================*/
?>
