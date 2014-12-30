<?php if (!defined('IDIR')) { die; }
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
* eve_003 Import User module
*
* @package			ImpEx.eve
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class eve_003 extends eve_000
{
	var $_dependent 	= '001';

	function eve_003(&$displayobject)
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
			$displayobject->update_html($displayobject->make_hidden_code('import_user','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['username_email'], "username_email",1));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',0));

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

		// Get an array of user details
		$user_array 	= $this->get_eve_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $user_array['count'] . " {$displayobject->phrases['users']}</h4>");
	
		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array['data'] as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));
			
			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
					$try->_auto_email_associate = true;

			// Email 
			$user['EMAIL'] = trim($user['EMAIL']);
			$try->set_value('mandatory', 'email',					$user['EMAIL']);
			
			// Use email for username ? 1 = username 2 = email
			$use_username = $sessionobject->get_session_var('username_email');
			$try->set_value('mandatory', 'username', ($use_username == 1 ? $user['DISPLAY_NAME'] : $user['EMAIL']));

			// Custom ban group
			if ($user['BANNER_OID'] == NULL)
			{
				$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array[1]);
			}
			else
			{	// they are banned
				$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array[2]);
				$try->set_value('nonmandatory', 'usernote',				$user['BAN_REASON']);
			}
			
			// Use the refrence ID opposed to the OID
			$try->set_value('mandatory', 'importuserid',			$user_id);

			// Password cannot be imported to just scrable it
			$try->set_value('nonmandatory', 'password',				$this->fetch_user_salt(5)); # $user['PASSWORD']
			
			// Format :: 2005-04-21 09:02:13
			if($user['REGISTRATION_DATE'])
				$try->set_value('nonmandatory', 'joindate',			strtotime($user['REGISTRATION_DATE']));

			// Not default in eve				
			if ($user['HOME_PAGE_URL'])
				$try->set_value('nonmandatory', 'homepage',				$user['HOME_PAGE_URL']);
			
			// Not default in eve
			if ($user['DOB'])
			{
				// EVE = YYYY-MM-DD
				// VB = birthday MM-DD-YYY 	
				// VB = birthday_search YYY-MM-DD
				
				$date_bits = explode('-',$user['DOB']);
				$try->set_value('nonmandatory', 'birthday',			$date_bits[1] . "-" . $date_bits[2] . "-" . $date_bits[0]);
				$try->set_value('nonmandatory', 'birthday_search',	$user['DOB']);
				unset($date_bits);
			}
			
			// Use defaults
			#adminemail">16
			if ($user['HAS_OPTED_OUT_OF_EMAIL'])
			{
				$try->set_value('nonmandatory', 'options',				intval($this->_default_user_permissions - 16));
			}
			else 
			{
				$try->set_value('nonmandatory', 'options',				$this->_default_user_permissions);
			}
			
			// Not sure if this is REG or LAST LOG ON
			$try->set_value('nonmandatory', 'ipaddress',			$user['USER_IP']);
			
			// Set both and it will update as soon as they do anything
			if ($user['LAST_LOGIN_DATETIME'])
			{
				$try->set_value('nonmandatory', 'lastvisit',		strtotime($user['LAST_LOGIN_DATETIME']));
				$try->set_value('nonmandatory', 'lastactivity',		strtotime($user['LAST_LOGIN_DATETIME']));
			}
			
			// Format :: 2005-04-21 09:02:13
			if ($user['REGISTRATION_DATE'])
				$try->set_value('nonmandatory', 'joindate',			strtotime($user['REGISTRATION_DATE']));

			// Seems default
			if ($user['CUMULATIVE_USER_POST_COUNT'])
				$try->set_value('nonmandatory', 'posts',			intval($user['CUMULATIVE_USER_POST_COUNT']));
			
			// Mapping of the KAMA
			if ($user['KARMA_POINTS'])
				$try->set_value('nonmandatory', 'reputation',		intval($user['KARMA_POINTS']));
			
			// Not default
			$try->set_value('nonmandatory', 'usertitle',			$user['USER_TITLE']);
			
			// It's always going to be a URL
			if ($sessionobject->get_session_var('get_avatars'))
			{
					$user['AVATAR_URL'] = trim($user['AVATAR_URL']);
					$user['PICTURE_URL'] = trim($user['PICTURE_URL']);
					$user['AVATAR_URL'] = (empty($user['AVATAR_URL']) ? $user['PICTURE_URL'] : $user['AVATAR_URL']);
					
					$try->set_value('nonmandatory', 'avatar',			trim($user['AVATAR_URL']));
			}

			// Default values
			$try->add_default_value('signature', 					substr($this->html_2_bb($user['SIGNATURE']), 0, 250));			
			$try->add_default_value('Biography', 					addslashes(substr($user['BIO'], 0, 250)));
			$try->add_default_value('Location',		 				addslashes(substr($user['LOCATION'], 0, 250)));
			$try->add_default_value('Occupation',	 				addslashes(substr($user['OCCUPATION'], 0, 250)));
			$try->add_default_value('Interests', 					addslashes(substr($user['INTERESTS'], 0, 250)));

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
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
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End foreach

		// Check for page end
		if ($user_array['count'] == 0 OR $user_array['count'] < $user_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html(
				$displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_user','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('userstartat', $user_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : March 8, 2005, 12:17 am
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
