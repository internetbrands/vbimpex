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
* OT_002 Import Users module
*
* @package 		ImpEx.OT
*
*/

class OT_002 extends OT_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Users';

	function OT_002()
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
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to empty your existing vBulletin members database?","clear_non_admin_users",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));

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

		$user_start_at			= $sessionobject->get_session_var('usersstartat');
		$user_per_page			= $sessionobject->get_session_var('usersperpage');
		$class_num				= substr(get_class($this) , -3);

		$membersxmlfile 		= $sessionobject->get_session_var('membersxmlfile');

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

		// Get a page worths of users
		$user_array  =  $this->get_OT_user_details($membersxmlfile, $user_start_at, $user_per_page);

		$displayobject->display_now("<h4>Importing " . count($user_array) . " users</h4><p><b>From</b> : " . $user_start_at . " ::  <b>To</b> : " . ($user_start_at + count($user_array)) . "</p>");


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}					
			
			$try->set_value('mandatory', 'username',			$user['username']);
			$try->set_value('mandatory', 'email',				$user['email']);
			$try->set_value('mandatory', 'usergroupid',			$user['usergroupid']);
			$try->set_value('mandatory', 'importuserid',		$user['importuserid']);

			$try->_password_md5_already = false;
			$try->set_value('nonmandatory', 'password', 		$user['password']);

			$try->set_value('nonmandatory', 'joindate',			$user['joindate']);
			$try->set_value('nonmandatory', 'homepage',			addslashes($user['homepage']));
			$try->set_value('nonmandatory', 'lastactivity',		$user['lastactivity']);
			$try->set_value('nonmandatory', 'lastvisit',		$user['lastvisit']);
			$try->set_value('nonmandatory', 'posts',			$user['posts']);

			if($user['birthday'])
			{
				$try->set_value('nonmandatory', 'birthday',	$user['birthday']);
			}

			$try->set_value('nonmandatory', 'parentemail',		$user['parentemail']);
			$try->set_value('nonmandatory', 'daysprune',		$user['daysprune']);
			$try->set_value('nonmandatory', 'ipaddress',		$user['ipaddress']);

			$try->add_default_value('Occupation',				$user['occupation']);
			$try->add_default_value('Location', 				$user['location']);
			$try->add_default_value('Interests',				$user['interests']);

			$try->add_default_value('signature', 				addslashes($user['signature']));

			$try->set_value('nonmandatory', 'options',			$this->_default_user_permissions);
 

			// If its not blank slash it and get it
			if($user['user_sig'] != '')
			{
				$try->add_default_value('signature', 	addslashes($this->OT_html($this->html_2_bb($user['user_sig']))));
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
								 get_class($this) . "::import_user failed for " . $user['username'],
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
			$sessionobject->add_session_var('totalusersdone',($sessionobject->get_session_var('totalusersdone') + $user_per_page));
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			$sessionobject->set_session_var('usersstartat',(intval($user_start_at)+intval($sessionobject->get_session_var('usersperpage'))));
		}
	}
}
/*======================================================================*/
?>
