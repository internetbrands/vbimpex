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
* wBB_004 Import Users module
*
* @package 		ImpEx.wBB
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/

class wBB_004 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Users';

	function wBB_004()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users'))
				{
					$displayobject->display_now("<h4>Imported users have been cleared</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_users",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import users');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_users','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Users'));
			$displayobject->update_html($displayobject->make_input_code("Users to import per cycle (must be greater than 1)","usersperpage",500));
	#		$displayobject->update_html($displayobject->make_input_code('What is the full path to your avatars directory ? (make sure the web server has access to read them)','get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to empty your existing vBulletin members database?","clear_non_admin_users",0));
	#		$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the avatars, this can take some time if they are remotely linked","get_avatars",1));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to associated imported users with existing users if the email address matches ?","email_match",0));

			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('usersstartat','0');
			$sessionobject->add_session_var('totalusersdone','0');
		}
		else
		{
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

		$user_start_at			= $sessionobject->get_session_var('usersstartat');
		$user_per_page			= $sessionobject->get_session_var('usersperpage');
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
		$usergroups	 =	$this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		// Get a page worths of users
		$user_array  =  $this->get_wBB2_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$displayobject->display_now("<h4>Importing " . count($user_array) . " users</h4><p><b>From</b> : " . $user_start_at . " ::  <b>To</b> : " . ($user_start_at + count($user_array)) . "</p>");


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			$try->set_value('mandatory', 'username',		$user['username']);
			$try->set_value('mandatory', 'email',			$user['email']);
			$try->set_value('mandatory', 'importuserid',	$user['userid']);

			// Usergroups, if they have one map it, if there is more, add the membergroups other wise default import group
			$old_groups = $this->get_wbb_user_groups($Db_source, $source_database_type, $source_table_prefix, $user_id);

			if($old_groups[0])
			{
				$try->set_value('mandatory', 'usergroupid',		$usergroups[$old_groups[0]]);

				if(count($old_groups) > 1)
				{
					$old_groups = array_slice($old_groups,1);

					foreach($old_groups as $id)
					{
						$new_groups[] = $usergroups[$id];
					}

					$try->set_value('nonmandatory', 'membergroupids',	implode(',', $new_groups));
				}
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid',		$usergroups[69]);
			}

			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password', 		$user['password']);
			$try->set_value('nonmandatory', 'options',			$this->_default_user_permissions);

			$try->set_value('nonmandatory', 'aim',				addslashes($user['aim']));
			$try->set_value('nonmandatory', 'icq',				addslashes($user['icq']));
			$try->set_value('nonmandatory', 'yahoo',			addslashes($user['yim']));

			$try->set_value('nonmandatory', 'homepage',			addslashes($user['homepage']));
			$try->set_value('nonmandatory', 'joindate',			$user['regdate']);
			$try->set_value('nonmandatory', 'lastactivity',		$user['lastactivity']);
			$try->set_value('nonmandatory', 'lastvisit',		$user['lastvisit']);
			$try->set_value('nonmandatory', 'styleid',			$user['styleid']);
			$try->set_value('nonmandatory', 'usertitle',		$user['title']);
			$try->set_value('nonmandatory', 'daysprune',		$user['daysprune']);
			$try->set_value('nonmandatory', 'posts',			$user['userposts']);
			$try->set_value('nonmandatory', 'timezoneoffset',	$user['timezoneoffset']);
			$try->set_value('nonmandatory', 'pmpopup',			$user['pmpopup']);
			$try->set_value('nonmandatory', 'avatarid',		 	$user['avatarid']);
			$try->set_value('nonmandatory', 'maxposts',			$user['umaxposts']);
			$try->set_value('nonmandatory', 'startofweek',	 	$user['startweek']);
			$try->set_value('nonmandatory', 'threadedmode',	 	$user['threadview']);
			$try->set_value('nonmandatory', 'birthday_search', 	$user['birthday']);

			if($user['birthday'] != '0000-00-00')
			{
				$date_bits = explode('-', $user['birthday']);
				$try->set_value('nonmandatory', 'birthday',		 	"{$date_bits[1]}-{$date_bits[2]}-{$date_bits[0]}");
			}


			// TODO: get them from the user fields
			$user_fields = $this->get_wBB_user_fields($Db_target, $target_database_type, $target_table_prefix, $user_id);

			$try->add_default_value('Occupation',			$user_fields[1]);
			$try->add_default_value('Location', 			$user_fields[2]);
			$try->add_default_value('Interests',			$user_fields[3]);


			if ($sessionobject->get_session_var('get_avatars') AND $user['user_avatar'] != NULL AND $user['user_avatar'] != '/')
			{
				if(strstr($user['user_avatar'],'http://'))
				{
					//Its a url
					$try->set_value('nonmandatory', 'avatar',trim($user['user_avatar']));
				}
				else
				{
					// Its going to be in the images/avatars/ dir somewhere hopefully.
					$ava_path = $sessionobject->get_session_var('get_avatars_path');

					if(substr($string,-1) == '/')
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . trim($user['user_avatar']));

					}
					else
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . '/' . trim($user['user_avatar']));

					}
				}
			}

			// If its not blank slash it and get it
			if($user['user_sig'] != '')
			{
				$try->add_default_value('signature', 	addslashes($this->wBB_html($this->html_2_bb($user['user_sig']))));
			}

			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$imported = true;
				}
				else
				{
					$sessionobject->add_error('warning', $this->_modulestring,
								 get_class($this) . "::import_user failed for $ubbmemberspath/$userfile. getUserDetails was ok.",
								 'Check database permissions and user table');
					$displayobject->display_now("<br />Got user " . $try->get_value('mandatory','username') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid user object, skipping." . $try->_failedon);
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
			$sessionobject->set_session_var('import_users','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$sessionobject->add_session_var('totalusersdone',($sessionobject->get_session_var('totalusersdone') + $doneperpass));
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			$sessionobject->set_session_var('usersstartat', ($user_start_at+$user_per_page));
		}
	}
}
/*======================================================================*/
?>

