<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* DCFm_002 Import Users module
*
* @package 		ImpEx.DCFm
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class DCFm_004 extends DCFm_000
{
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Users';

	function DCFm_004()
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
			$displayobject->update_html($displayobject->make_input_code("Users to import per cycle (must be greater than 1)","usersperpage", 2000));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to empty your existing vBulletin members database?","clear_non_admin_users",0));

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
		$group_ids		= $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Get a page worths of users
		$user_array  =  $this->get_DCFm_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$displayobject->display_now("<h4>Importing " . count($user_array) . " users</h4><p><b>From</b> : " . $user_start_at . " ::  <b>To</b> : " . ($user_start_at + count($user_array)) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Mandatory
			$try->set_value('mandatory', 'usergroupid',				$group_ids["$details[g_id]"]);
			$try->set_value('mandatory', 'username',				$details['username']);
			$try->set_value('mandatory', 'email',					$details['email']);
			$try->set_value('mandatory', 'importuserid',			$details['id']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'password',				$details['password']);
			$try->set_value('nonmandatory', 'homepage',				$details['ph']);
			$try->set_value('nonmandatory', 'joindate',				$this->do_dcf_date($details['reg_date']));
			$try->set_value('nonmandatory', 'posts',				$details['num_posts']);
			$try->set_value('nonmandatory', 'icq',					addslashes($details['pa']));
			$try->set_value('nonmandatory', 'aim',					addslashes($details['pb']));

			#case 'pc':    // Avatar image file name
			#case 'pd':    // Gender

			unset($location);
			if($details['pe'])
			{
				$location .= $details['pe'] . ", ";
			}
			if($details['pf'])
			{
				$location .= $details['pf'] . ", ";
			}
			if($details['pg'])
			{
				$location .= $details['pg'] . ", ";
			}

			$location = substr($location, 0, -2);
			$location .=  ".";
			$location = substr($location, 0, 248);
			$try->add_default_value('Location', addslashes($location));

			$try->set_value('nonmandatory', 'options',				$this->_default_user_permissions);

			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
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
			$sessionobject->set_session_var('usersstartat',(intval($user_start_at)+intval($sessionobject->get_session_var('usersperpage'))));
		}
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
