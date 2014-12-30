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
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class yaf_004 extends yaf_000
{
	var $_dependent = '003';

	function yaf_004(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_users'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['users_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',1));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

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
		$data_array = $this->get_yaf_users($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['users'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Mandatory
			if ($user_group_ids_array["$data[GroupID]"])
			{
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array["$data[GroupID]"]);
			}
			else
			{
					$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);
			}

			$try->set_value('mandatory', 'importuserid',		$import_id);
			$try->set_value('mandatory', 'username',			$data['Name']);
			$try->set_value('mandatory', 'email',				$data['Email']);

			// Non mandatory
			$try->set_value('nonmandatory', 'joindate',			strtotime($data['Joined']));
			$try->set_value('nonmandatory', 'lastvisit',		strtotime($data['LastVisit']));
			$try->set_value('nonmandatory', 'posts',			$data['NumPosts']);
			$try->set_value('nonmandatory', 'msn',				$data['MSN']);
			$try->set_value('nonmandatory', 'yahoo',			$data['YIM']);
			$try->set_value('nonmandatory', 'aim',				$data['AIM']);
			$try->set_value('nonmandatory', 'icq',				$data['ICQ']);
			$try->set_value('nonmandatory', 'options',			$this->_default_user_permissions);

			$try->set_value('nonmandatory', 'password',			$this->fetch_user_salt()); // Random it, they can't be imported.

			if(is_file($data['Avatar']))
			{
				$try->set_value('nonmandatory', 'avatar',trim($data['Avatar']));
			}

			$try->add_default_value('signature', 				$this->html_2_bb($data["Signature"]));
			$try->add_default_value('Location', 				$data["Location"]);
			$try->add_default_value('Occupation',				$data["Occupation"]);
			$try->add_default_value('Interests',				$data["Interests"]);


			/*
			$try->set_value('nonmandatory', 'pmunread',			$data['pmunread']);
			$try->set_value('nonmandatory', 'pmtotal',			$data['pmtotal']);
			$try->set_value('nonmandatory', 'threadedmode',		$data['threadedmode']);
			$try->set_value('nonmandatory', 'emailstamp',		$data['emailstamp']);
			$try->set_value('nonmandatory', 'languageid',		$data['languageid']);
			$try->set_value('nonmandatory', 'referrerid',		$data['referrerid']);
			$try->set_value('nonmandatory', 'ipaddress',		$data['ipaddress']);
			$try->set_value('nonmandatory', 'startofweek',		$data['startofweek']);
			$try->set_value('nonmandatory', 'maxposts',			$data['maxposts']);
			$try->set_value('nonmandatory', 'salt',				$data['salt']);
			$try->set_value('nonmandatory', 'autosubscribe',		$data['autosubscribe']);
			$try->set_value('nonmandatory', 'profilepicrevision',		$data['profilepicrevision']);
			$try->set_value('nonmandatory', 'adminoptions',		$data['adminoptions']);
			$try->set_value('nonmandatory', 'infractiongroupid',		$data['infractiongroupid']);
			$try->set_value('nonmandatory', 'infractiongroupids',		$data['infractiongroupids']);
			$try->set_value('nonmandatory', 'warnings',		$data['warnings']);
			$try->set_value('nonmandatory', 'infractions',		$data['infractions']);
			$try->set_value('nonmandatory', 'ipoints',		$data['ipoints']);
			$try->set_value('nonmandatory', 'sigpicrevision',		$data['sigpicrevision']);
			$try->set_value('nonmandatory', 'usernote',		$data['usernote']);
			$try->set_value('nonmandatory', 'birthday_search',		$data['birthday_search']);
			$try->set_value('nonmandatory', 'birthday',		$data['birthday']);
			$try->set_value('nonmandatory', 'avatarrevision',		$data['avatarrevision']);
			$try->set_value('nonmandatory', 'showvbcode',		$data['showvbcode']);
			$try->set_value('nonmandatory', 'homepage',		$data['homepage']);
			$try->set_value('nonmandatory', 'parentemail',		$data['parentemail']);
			$try->set_value('nonmandatory', 'styleid',		$data['styleid']);
			$try->set_value('nonmandatory', 'passworddate',		$data['passworddate']);
			$try->set_value('nonmandatory', 'displaygroupid',		$data['displaygroupid']);
			$try->set_value('nonmandatory', 'usertitle',		$data['usertitle']);
			$try->set_value('nonmandatory', 'customtitle',		$data['customtitle']);
			$try->set_value('nonmandatory', 'avatarid',		$data['avatarid']);
			$try->set_value('nonmandatory', 'pmpopup',		$data['pmpopup']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$data['timezoneoffset']);
			$try->set_value('nonmandatory', 'reputationlevelid',		$data['reputationlevelid']);
			$try->set_value('nonmandatory', 'reputation',		$data['reputation']);
			$try->set_value('nonmandatory', 'lastpost',		$data['lastpost']);
			$try->set_value('nonmandatory', 'lastactivity',		$data['lastactivity']);
			$try->set_value('nonmandatory', 'daysprune',		$data['daysprune']);
			$try->set_value('nonmandatory', 'membergroupids',		$data['membergroupids']);
			*/

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $data['Name']);
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
				}// $try->import_user
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

			$this->build_user_statistics($Db_target, $t_db_type, $t_tb_prefix);

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
