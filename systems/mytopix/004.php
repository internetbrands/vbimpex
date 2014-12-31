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
* mytopix_004 Import User module
*
* @package			ImpEx.mytopix
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class mytopix_004 extends mytopix_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';


	function mytopix_004()
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
		$user_array 	= $this->get_mytopix_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');


		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Mandatory
			if($user_group_ids_array["$user_details[members_class]"])
			{
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array["$user_details[members_class]"]);
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);
			}

			$try->set_value('mandatory', 'username',				$user_details['members_name']);
			$try->set_value('mandatory', 'email',					$user_details['members_email']);
			$try->set_value('mandatory', 'importuserid',			$user_id);


			// Non Mandatory

			// Not imported, this is done as a radom string because it is salted.
			$try->set_value('nonmandatory', 'password',				$user_details['members_pass']);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['members_ip']);
			$try->set_value('nonmandatory', 'options',				'2135');
			$try->set_value('nonmandatory', 'homepage',				addslashes($user_details['members_homepage']));
			$try->set_value('nonmandatory', 'joindate',				$user_details['members_registered']);
			$try->set_value('nonmandatory', 'lastactivity',			$user_details['members_lastaction']);
			$try->set_value('nonmandatory', 'lastvisit',			$user_details['members_lastvisit']);
			$try->set_value('nonmandatory', 'posts',				$user_details['members_posts']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['members_timeZone']);
			$try->set_value('nonmandatory', 'icq',					$user_details['members_icq']);
			$try->set_value('nonmandatory', 'aim',					$user_details['members_aim']);
			$try->set_value('nonmandatory', 'yahoo',				$user_details['members_yahoo']);
			$try->set_value('nonmandatory', 'msn',					$user_details['members_msn']);

			$try->add_default_value('signature', 					addslashes($user_details['members_sig']));
			$try->add_default_value('Location',		 				addslashes($user_details['members_location']));

			if ($user_details['members_birth_day'] && $user_details['members_birth_month'] && $user_details['members_birth_year'])
			{
				$try->set_value('nonmandatory', 'birthday',			$user_details['members_birth_month'] . "-" . $user_details['members_birth_day'] . "-" . $user_details['members_birth_year']);
				$try->set_value('nonmandatory', 'birthday_search',	$user_details['members_birth_year'] . "-" . $user_details['members_birth_month'] . "-" . $user_details['members_birth_day']);
			}


			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['members_name']);
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
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : February 27, 2005, 12:33 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
