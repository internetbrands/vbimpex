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
* ipb2_004 Import User module
*
* @package			ImpEx.ipb2
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ipb2_004 extends ipb2_000
{
	var $_dependent 	= '003';

	function ipb2_004(&$displayobject)
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
			$displayobject->update_basic('title',$displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match", 0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars', 0));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('userdone','0');
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

		// Get an array of user details
		$user_array 	= $this->get_ipb2_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		// Groups info
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

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
			$try->set_value('mandatory', 'usergroupid',					$user_group_ids_array["$user_details[mgroup]"]);
			$try->set_value('mandatory', 'username',					$user_details['name']);
			$try->set_value('mandatory', 'email',						$user_details['email']);
			$try->set_value('mandatory', 'importuserid',				$user_id);

			// User options
			$options = 0;

			if($user_details['view_sigs'])								{ $options += 1;}
			if($user_details['view_avs'])								{ $options += 2;}
			if($user_details['view_img'])								{ $options += 4;}
			if($user_details['coppa_user'])								{ $options += 8;}
			if($user_details['allow_admin_mails'])						{ $options += 16;}
			#if($usergroup_details['showvcard'])						{ $options += 32;}
			if($user_details['dst_in_use'])								{ $options += 64;}
			#if($usergroup_details['dstonoff'])							{ $options += 128;}
			if($user_details['hide_email'])	{} else						{ $options += 256;}
			#if($usergroup_details['invisible'])						{ $options += 512;}
			#if($usergroup_details['showreputation'])					{ $options += 1024;}
			#if($usergroup_details['receivepm'])						{ $options += 2048;}
			#if($usergroup_details['email_pm'])							{ $options += 4096;}
			#if($usergroup_details['hasaccessmask'])					{ $options += 8192;}
			#if($usergroup_details['postorder'])						{ $options += 32768;}

			// Non Mandatory
			#$try->set_value('nonmandatory', 'membergroupids',		$user_details['membergroupids']);
			#$try->set_value('nonmandatory', 'displaygroupid',		$user_details['displaygroupid']);

			if($user_details['legacy_password'])
			{
				$try->_password_md5_already = true;
				$try->set_value('nonmandatory', 'password',				$user_details['legacy_password']);
			}
			else
			{
				$try->set_value('nonmandatory', 'password',				$this->fetch_user_salt());
			}

			#$try->set_value('nonmandatory', 'passworddate',		$user_details['passworddate']);
			#$try->set_value('nonmandatory', 'styleid',				$user_details['styleid']);
			#$try->set_value('nonmandatory', 'parentemail',			$user_details['parentemail']);
			$try->set_value('nonmandatory', 'homepage',				$user_details['website']);
			$try->set_value('nonmandatory', 'icq',					$user_details['icq_number']);
			$try->set_value('nonmandatory', 'aim',					$user_details['aim_name']);
			$try->set_value('nonmandatory', 'yahoo',				$user_details['yahoo']);
			$try->set_value('nonmandatory', 'showvbcode',			'2');
			$try->set_value('nonmandatory', 'usertitle',			$user_details['title']);
			#$try->set_value('nonmandatory', 'customtitle',			$user_details['customtitle']);
			$try->set_value('nonmandatory', 'joindate',				$user_details['joined']);
			#$try->set_value('nonmandatory', 'daysprune',			$user_details['daysprune']);
			$try->set_value('nonmandatory', 'lastvisit',			$user_details['last_visit']);
			$try->set_value('nonmandatory', 'lastactivity',			$user_details['last_activity']);
			$try->set_value('nonmandatory', 'lastpost',				$user_details['last_post']);
			$try->set_value('nonmandatory', 'posts',				$user_details['posts']);
			#$try->set_value('nonmandatory', 'reputation',			$user_details['reputation']);
			#$try->set_value('nonmandatory', 'reputationlevelid',	$user_details['reputationlevelid']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['time_offset']);
			#$try->set_value('nonmandatory', 'pmpopup',				$user_details['pmpopup']);
			#$try->set_value('nonmandatory', 'avatarid',			$user_details['avatarid']);
			#$try->set_value('nonmandatory', 'avatarrevision',		$user_details['avatarrevision']);
			$try->set_value('nonmandatory', 'options',				$options);

			#$try->set_value('nonmandatory', 'maxposts',			$user_details['maxposts']);
			#$try->set_value('nonmandatory', 'startofweek',			$user_details['startofweek']);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['ip_address']);
			#$try->set_value('nonmandatory', 'referrerid',			$user_details['referrerid']);
			#$try->set_value('nonmandatory', 'languageid',			$user_details['languageid']);
			$try->set_value('nonmandatory', 'msn',					$user_details['msnname']);
			#$try->set_value('nonmandatory', 'emailstamp',			$user_details['emailstamp']);
			#$try->set_value('nonmandatory', 'threadedmode',		$user_details['threadedmode']);
			$try->set_value('nonmandatory', 'pmtotal',				$user_details['msg_total']);
			$try->set_value('nonmandatory', 'pmunread',				$user_details['new_msg']);
			#$try->set_value('nonmandatory', 'salt',				$user_details['salt']);
			#$try->set_value('nonmandatory', 'autosubscribe',		$user_details['autosubscribe']);
			#$try->set_value('nonmandatory', 'avatar',				$user_details['avatar']);


			if($user_details['bday_day'] OR $user_details['bday_month'] OR $user_details['bday_year'])
			{
				$day 	= str_pad($user_details['bday_day'], 2, "0", STR_PAD_LEFT);
				$month 	= str_pad($user_details['bday_month'], 2, "0", STR_PAD_LEFT);
				$year 	= $user_details['bday_year'];

				$try->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
				$try->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");

				unset($year, $month, $day);
			}
			else
			{
				$try->set_value('nonmandatory', 'birthday',			"00-00-0000");
				$try->set_value('nonmandatory', 'birthday_search',	"0000-00-00");
			}

			$path = $sessionobject->get_session_var('get_avatars_path');
			$avatar = $user_details['avatar_location'];

			// Ensure there is a trailing slash
			if($path{strlen($path)-1} != '/')  $path .= '/';

			// It would appear that blanks are allowed in the database
			if($sessionobject->get_session_var('get_avatars') AND $avatar != 'noavatar' AND $avatar != '')
			{
				if(substr($avatar, 0, 7) == 'upload:')
				{
					// Its a localy uploaded file.
					$try->set_value('nonmandatory', 'avatar',		$path . substr($avatar, strpos($avatar, ':')+1));
				}
				elseif (substr($avatar, 0, 7) == 'http://')
				{
					// Must be a URL, give it a go.
					$try->set_value('nonmandatory', 'avatar', 		$avatar);
				}
				elseif (is_file($path . $avatar))
				{
					// Well its there, so try it.
					$try->set_value('nonmandatory', 'avatar', 		$path . $avatar);
				}
				else
				{
					// Dunno what it could be.
				}
			}

			$try->add_default_value('Location', 					$user_details['location']);
			$try->add_default_value('Interests', 					$user_details['interests']);
			$try->add_default_value('signature', 					$this->ipb2_html($this->html_2_bb(html_entity_decode($user_details['signature']))));

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{					
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory','username'));
					}
					
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $user_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $user_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

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
# Autogenerated on : August 20, 2004, 2:31 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
