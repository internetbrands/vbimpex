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
* wowBB_004 Import User module
*
* @package			ImpEx.wowBB
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class wowBB_004 extends wowBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';

	function wowBB_004()
	{
		// Constructor
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users'))
				{
					$displayobject->display_now('<h4>Imported users have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_users','Check database permissions');
				}
			}

			// Start up the table
			$displayobject->update_basic('title','Import User');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_user','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Users to import per cycle (must be greater than 1)','userperpage',500));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to associated imported users with existing users if the email address matches ?","email_match",0));
			$displayobject->update_html($displayobject->make_input_code('What is the full path to your board ? (where images/avatars/ is)','get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));

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
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
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
		$user_array 			= $this->get_wowBB_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$user_group_ids_array 	= $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');
		$ava_path = $sessionobject->get_session_var('get_avatars_path');

		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Mandatory
			$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array["$user_details[user_group_id]"]);
			$try->set_value('mandatory', 'username',				$user_details['user_name']);
			$try->set_value('mandatory', 'email',					$user_details['user_email']);
			$try->set_value('mandatory', 'importuserid',			$user_id);

			// Non Mandatory
			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password', 			$user_details['user_password']);
			$try->set_value('nonmandatory', 'icq',					$user_details['user_icq']);
			$try->set_value('nonmandatory', 'aim',					$user_details['user_aim']);
			$try->set_value('nonmandatory', 'yahoo',				$user_details['user_ym']);
			$try->set_value('nonmandatory', 'msn',					$user_details['user_msnm']);
			$try->set_value('nonmandatory', 'options',				'2135');
			$try->set_value('nonmandatory', 'homepage',				addslashes($user_details['user_homepage']));
			$bday_bits = explode('-', $user_details['user_birthday']);
			$try->set_value('nonmandatory', 'birthday',				$bday_bits[1] . "-" . $bday_bits[2] . "-" . $bday_bits[0]);
			$try->set_value('nonmandatory', 'birthday_search',		$user_details['user_birthday']);
			$try->set_value('nonmandatory', 'posts',				$user_details['user_posts']);
			$try->set_value('nonmandatory', 'joindate',				strtotime($user_details['user_joined']));
			if(is_file($ava_path . trim($user_details['user_avatar'])))
			{
				$try->set_value('nonmandatory', 'avatar',			$ava_path . trim($user_details['user_avatar']));
			}
			$try->add_default_value('Occupation',					$user_details['user_occupation']);
			$try->add_default_value('Location', 					$user_details['user_city'] . ', ' . $user_details['user_region'] . ', ' . $user_details['user_country']);
			$try->add_default_value('Interests',					$user_details['user_interests']);
			$try->add_default_value('signature', 					addslashes($this->html_2_bb($user_details['user_signature'])));

			/*
			$try->set_value('nonmandatory', 'membergroupids',		$user_details['membergroupids']);
			$try->set_value('nonmandatory', 'displaygroupid',		$user_details['displaygroupid']);
			$try->set_value('nonmandatory', 'styleid',				$user_details['styleid']);
			$try->set_value('nonmandatory', 'parentemail',			$user_details['parentemail']);
			$try->set_value('nonmandatory', 'showvbcode',			$user_details['showvbcode']);
			$try->set_value('nonmandatory', 'usertitle',			$user_details['usertitle']);
			$try->set_value('nonmandatory', 'customtitle',			$user_details['customtitle']);
			$try->set_value('nonmandatory', 'daysprune',			$user_details['daysprune']);
			$try->set_value('nonmandatory', 'lastvisit',			$user_details['lastvisit']);
			$try->set_value('nonmandatory', 'lastactivity',			$user_details['lastactivity']);
			$try->set_value('nonmandatory', 'lastpost',				$user_details['lastpost']);
			$try->set_value('nonmandatory', 'reputation',			$user_details['reputation']);
			$try->set_value('nonmandatory', 'reputationlevelid',	$user_details['reputationlevelid']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['timezoneoffset']);
			$try->set_value('nonmandatory', 'pmpopup',				$user_details['pmpopup']);
			$try->set_value('nonmandatory', 'avatarid',				$user_details['avatarid']);
			$try->set_value('nonmandatory', 'avatarrevision',		$user_details['avatarrevision']);
			$try->set_value('nonmandatory', 'maxposts',				$user_details['maxposts']);
			$try->set_value('nonmandatory', 'startofweek',			$user_details['startofweek']);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['ipaddress']);
			$try->set_value('nonmandatory', 'referrerid',			$user_details['referrerid']);
			$try->set_value('nonmandatory', 'languageid',			$user_details['languageid']);
			$try->set_value('nonmandatory', 'emailstamp',			$user_details['emailstamp']);
			$try->set_value('nonmandatory', 'threadedmode',			$user_details['threadedmode']);
			$try->set_value('nonmandatory', 'pmtotal',				$user_details['pmtotal']);
			$try->set_value('nonmandatory', 'pmunread',				$user_details['pmunread']);
			$try->set_value('nonmandatory', 'salt',					$user_details['salt']);
			$try->set_value('nonmandatory', 'autosubscribe',		$user_details['autosubscribe']);
			$try->set_value('nonmandatory', 'avatar',				$user_details['avatar']);
			*/

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['user_name']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar user and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid user object, skipping." . $try->_failedon);
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
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : January 4, 2005, 4:48 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
