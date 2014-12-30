<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* vb_36_004 Import User module
*
* @package			ImpEx.vb_36
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb_36_004 extends vb_36_000
{
	var $_dependent 	= '003';

	function vb_36_004(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users'))
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
			$displayobject->update_basic('title', $displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
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
		$user_start_at			= $sessionobject->get_session_var('userstartat');
		$user_per_page			= $sessionobject->get_session_var('userperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get a page worths of users and their various details
		$user_array 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'user', 'userid');
		$signatures 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'usertextfield', 'userid');
		$userfield 		= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'userfield', 'userid');
		$usernote 		= $this->get_details($Db_target, $target_database_type, $target_table_prefix, $user_start_at, $user_per_page,  'usernote', 'usernoteid');
		$usergroup 		= $this->get_details($Db_target, $target_database_type, $target_table_prefix, 0, -1, 'usergroup', 'importusergroupid');

		$usergroups	 	=	$this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($user_array) . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $user_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($user_start_at + count($user_array)) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Mandatory
			$try->set_value('mandatory', 'usergroupid',			$usergroups["$user_details[usergroupid]"]);
			$try->set_value('mandatory', 'username',			$user_details['username']);
			$try->set_value('mandatory', 'email',				$user_details['email']);
			$try->set_value('mandatory', 'importuserid',		$user_id);

			// Non Mandatory
			if($user['referrerid'])
			{
				$try->set_value('nonmandatory', 'referrerid',	$this->get_vb_userid($Db_target, $target_database_type, $target_table_prefix, $user['referrerid']));
			}

			$try->set_value('nonmandatory', 'displaygroupid',	$usergroups["$user_details[displaygroupid]"]);

			# if only it didn't go over 9 ........
			#$try->set_value('nonmandatory', 'membergroupids',	str_replace(array_keys($usergroups), array_values($usergroups), $user_details['membergroupids']));

			$old_groups 		= ''; // Empty it.
			$new_membergroups 	= ''; // Empty it.
			$old_groups 		= explode (',', $user_details['membergroupids']);

			foreach ($old_groups AS $old_id)
			{
				$new_membergroups[] = $moo[$old_id];
			}

			$try->set_value('nonmandatory', 'membergroupids',	implode(',', $new_membergroups));
			$try->set_value('nonmandatory', 'ipaddress',		$user_details['ipaddress']);
			$try->set_value('nonmandatory', 'startofweek',		$user_details['startofweek']);
			$try->set_value('nonmandatory', 'maxposts',			$user_details['maxposts']);
			$try->set_value('nonmandatory', 'birthday',			$user_details['birthday']);
			$try->set_value('nonmandatory', 'usernote',			$user_details['usernote']);
			$try->set_value('nonmandatory', 'options',			$user_details['options']);
			$try->set_value('nonmandatory', 'avatarrevision',	$user_details['avatarrevision']);
			$try->set_value('nonmandatory', 'avatarid',			$user_details['avatarid']);
			$try->set_value('nonmandatory', 'languageid',		$user_details['languageid']);
			$try->set_value('nonmandatory', 'msn',				$user_details['msn']);
			$try->set_value('nonmandatory', 'emailstamp',		$user_details['emailstamp']);
			$try->set_value('nonmandatory', 'birthday_search',	$user_details['birthday_search']);
			$try->set_value('nonmandatory', 'avatar',			$user_details['avatar']);
			$try->set_value('nonmandatory', 'profilepicrevision', $user_details['profilepicrevision']);
			$try->set_value('nonmandatory', 'autosubscribe',	$user_details['autosubscribe']);
			$try->set_value('nonmandatory', 'salt',				$user_details['salt']);
			$try->set_value('nonmandatory', 'pmunread',			$user_details['pmunread']);
			$try->set_value('nonmandatory', 'pmtotal',			$user_details['pmtotal']);
			$try->set_value('nonmandatory', 'threadedmode',		$user_details['threadedmode']);
			$try->set_value('nonmandatory', 'pmpopup',			$user_details['pmpopup']);
			$try->set_value('nonmandatory', 'timezoneoffset',	$user_details['timezoneoffset']);
			$try->set_value('nonmandatory', 'reputationlevelid', $user_details['reputationlevelid']);
			$try->set_value('nonmandatory', 'icq',				$user_details['icq']);
			$try->set_value('nonmandatory', 'homepage',			$user_details['homepage']);
			$try->set_value('nonmandatory', 'parentemail',		$user_details['parentemail']);
			$try->set_value('nonmandatory', 'styleid',			$user_details['styleid']);
			$try->set_value('nonmandatory', 'passworddate',		$user_details['passworddate']);
			$try->set_value('nonmandatory', 'password',			$user_details['password']);
			$try->set_value('nonmandatory', 'yahoo',			$user_details['yahoo']);
			$try->set_value('nonmandatory', 'showvbcode',		$user_details['showvbcode']);
			$try->set_value('nonmandatory', 'usertitle',		$user_details['usertitle']);
			$try->set_value('nonmandatory', 'reputation',		$user_details['reputation']);
			$try->set_value('nonmandatory', 'posts',			$user_details['posts']);
			$try->set_value('nonmandatory', 'lastpost',			$user_details['lastpost']);
			$try->set_value('nonmandatory', 'lastactivity',		$user_details['lastactivity']);
			$try->set_value('nonmandatory', 'lastvisit',		$user_details['lastvisit']);
			$try->set_value('nonmandatory', 'daysprune',		$user_details['daysprune']);
			$try->set_value('nonmandatory', 'joindate',			$user_details['joindate']);
			$try->set_value('nonmandatory', 'customtitle',		$user_details['customtitle']);
			$try->set_value('nonmandatory', 'aim',				$user_details['aim']);
			$try->set_value('nonmandatory', 'lastpostid',		$user_details['lastpostid']);
			$try->set_value('nonmandatory', 'sigpicrevision', 	$user_details['sigpicrevision']);
			$try->set_value('nonmandatory', 'ipoints', 			$user_details['ipoints']);
			$try->set_value('nonmandatory', 'infractions', 		$user_details['infractions']);
			$try->set_value('nonmandatory', 'warnings', 		$user_details['warnings']);
			$try->set_value('nonmandatory', 'infractiongroupids', $user_details['infractiongroupids']);
			$try->set_value('nonmandatory', 'infractiongroupid', $user_details['infractiongroupid']);
			$try->set_value('nonmandatory', 'adminoptions', 	$user_details['adminoptions']);

			$this->_has_default_values = true;

			/*
			$try->add_default_value('Biography',				$userfield[$user_id]['field1']);
			$try->add_default_value('Location', 				$userfield[$user_id]['field2']);
			$try->add_default_value('Interests',				$userfield[$user_id]['field3']);
			$try->add_default_value('Occupation',				$userfield[$user_id]['field4']);
			*/
			
			$old_groups = explode(",", $user_details['membergroupids']);
			$new_groups = '';

			foreach($old_groups as $old_id)
			{
				$new_groups .= $usergroup[$old_id]['usergroupid'] . ',';
			}

			$new_groups = substr($new_groups, 0, -1);
			$try->set_value('nonmandatory', 'membergroupids',	$new_groups);


			if($signatures[$user_id] != '')
			{
				$try->add_default_value('signature', 			$signatures[$user_id]['signature']);
			}

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_vb3_user($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory','username'));
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

						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					}

					$sessionobject->add_error($Db_target, 'warning', $class_num, $user_id, $displayobject->phrases['user_not_imported'],  $displayobject->phrases['user_not_imported_rem']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
			}
			else
			{
				if(shortoutput)
				{
					$displayobject->display_now('D');
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}

				$sessionobject->add_error($Db_target, 'invalid', $class_num, $user_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_user','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
