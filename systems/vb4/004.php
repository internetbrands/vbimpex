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
* vb4 Import Users module
*
* @package 		ImpEx.vb4
* @date 		$Date: 2008-11-10 13:56:59 -0800 (Mon, 10 Nov 2008) $
*
*/
class vb4_004 extends vb4_000
{
	var $_dependent = '003';

	function vb4_004(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_users'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['users_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'perpage',500));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('startat','0');
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

		$user_start_at			= $sessionobject->get_session_var('startat');
		$user_per_page			= $sessionobject->get_session_var('perpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Check and clear the NON admin users
		if ($sessionobject->get_session_var('clear_non_admin_users') == 1)
		{
			if ($this->clear_non_admin_users($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now("<b>All users deleted</b>");
				$sessionobject->set_session_var('clear_non_admin_users','0');
			}
			else
			{
				$sessionobject->add_error('fatal', $this->_modulestring,
							get_class($this) . "::resume failed , clear_non_admin_users",
							'Check database permissions and user table');
			}
		}

		// Get the banned and done (associated users)
		$bannedgroup =  $this->get_banned_group($Db_target, $target_database_type, $target_table_prefix);
		$doneusers	 =  $this->get_done_user_ids($Db_target, $target_database_type, $target_table_prefix);

		// Get a page worths of users and their various details
		$user_array 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'user', 'userid');
		$signatures 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'usertextfield', 'userid');
		$userfield 		= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'userfield', 'userid');
		$usergroup 		= $this->get_details($Db_target, $target_database_type, $target_table_prefix, 0, -1, 'usergroup', 'importusergroupid');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($user_array) . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $user_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($user_start_at + count($user_array)) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// ID mapping

			$try->set_value('mandatory', 'usergroupid',			$usergroup["$user[usergroupid]"]['usergroupid']);
			$try->set_value('mandatory', 'username',			$user['username']);
			$try->set_value('mandatory', 'email',				$user['email']);
			$try->set_value('mandatory', 'importuserid',		$user_id);


			if($user['referrerid'])
			{
				$try->set_value('nonmandatory', 'referrerid',	$this->get_vb_userid($Db_target, $target_database_type, $target_table_prefix, $user['referrerid']));
			}
			$try->set_value('nonmandatory', 'password',			$user['password']);
			$try->set_value('nonmandatory', 'passworddate',		$user['passworddate']);
			$try->set_value('nonmandatory', 'parentemail',		$user['parentemail']);
			$try->set_value('nonmandatory', 'homepage',			$user['homepage']);
			$try->set_value('nonmandatory', 'icq',				$user['icq']);
			$try->set_value('nonmandatory', 'aim',				$user['aim']);
			$try->set_value('nonmandatory', 'yahoo',			$user['yahoo']);
			$try->set_value('nonmandatory', 'showvbcode',		$user['showvbcode']);
			$try->set_value('nonmandatory', 'usertitle',		$user['usertitle']);
			$try->set_value('nonmandatory', 'customtitle',		$user['customtitle']);
			$try->set_value('nonmandatory', 'joindate',			$user['joindate']);
			$try->set_value('nonmandatory', 'daysprune',		$user['daysprune']);
			$try->set_value('nonmandatory', 'lastvisit',		$user['lastvisit']);
			$try->set_value('nonmandatory', 'lastactivity',		$user['lastactivity']);
			$try->set_value('nonmandatory', 'lastpost',			$user['lastpost']);
			$try->set_value('nonmandatory', 'posts',			$user['posts']);
			$try->set_value('nonmandatory', 'reputation',		$user['reputation']);
			$try->set_value('nonmandatory', 'timezoneoffset',	$user['timezoneoffset']);
			$try->set_value('nonmandatory', 'pmpopup',			$user['pmpopup']);
			$try->set_value('nonmandatory', 'avatarrevision',	$user['avatarrevision']);
			$try->set_value('nonmandatory', 'options',			$user['options']);
			$try->set_value('nonmandatory', 'birthday',			$user['birthday']);
			$try->set_value('nonmandatory', 'maxposts',			$user['maxposts']);
			$try->set_value('nonmandatory', 'startofweek',		$user['startofweek']);
			$try->set_value('nonmandatory', 'ipaddress',		$user['ipaddress']);
			$try->set_value('nonmandatory', 'languageid',		$user['languageid']);
			$try->set_value('nonmandatory', 'msn',				$user['msn']);
			$try->set_value('nonmandatory', 'emailstamp',		$user['emailstamp']);
			$try->set_value('nonmandatory', 'threadedmode',		$user['threadedmode']);
			$try->set_value('nonmandatory', 'pmtotal',			$user['pmtotal']);
			$try->set_value('nonmandatory', 'pmunread',			$user['pmunread']);
			$try->set_value('nonmandatory', 'salt',				$user['salt']);
			$try->set_value('nonmandatory', 'autosubscribe',	$user['autosubscribe']);
			$try->set_value('nonmandatory', 'avatar',			$user['avatar']);
			$try->set_value('nonmandatory', 'birthday_search',	$user['birthday_search']);

			$this->_has_default_values = true;

			$try->add_default_value('Biography',			$userfield[$user_id]['field1']);
			$try->add_default_value('Location', 			$userfield[$user_id]['field2']);
			$try->add_default_value('Interests',			$userfield[$user_id]['field3']);
			$try->add_default_value('Occupation',			$userfield[$user_id]['field4']);


			// Explode the memeber groups and map them to the new membergroup id's
			$old_groups = explode(",", $user['membergroupids']);
			$new_groups = '';

			foreach($old_groups as $old_id)
			{
				$new_groups .= $usergroup[$old_id]['usergroupid'] . ',';
			}

			$new_groups = substr($new_groups, 0, -1);
			$try->set_value('nonmandatory', 'membergroupids',	$new_groups);

			// ID mapping needed
			// TODO: Mapping
			$try->set_value('nonmandatory', 'reputationlevelid',$user['reputationlevelid']);
			$try->set_value('nonmandatory', 'displaygroupid',	$user['displaygroupid']);
			$try->set_value('nonmandatory', 'styleid',			$user['styleid']);

			// Mapped in later
			# $try->set_value('nonmandatory', 'avatarid',			$user['avatarid']);

			// If its not blank slash it and get it
			if($signatures[$user_id] != '')
			{
				$try->add_default_value('signature', 			$signatures[$user_id]['signature']);
			}

			if($try->is_valid())
			{
				if($try->import_vb3_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($user_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try);
		}

		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{
			// build_user_statistics();
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

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
		else
		{
			$sessionobject->set_session_var('startat',$user_start_at+$user_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}
}
/*======================================================================*/
?>

