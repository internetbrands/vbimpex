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
* fudforum_004 Import User module
*
* @package			ImpEx.fudforum
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class fudforum_004 extends fudforum_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';


	function fudforum_004()
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
		$user_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page, 'users', 'id');

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));
			
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}
			
			// Mandatory
			$try->set_value('mandatory', 'importuserid',		$user_id);
			$try->set_value('mandatory', 'email',				$user_details['email']);
			$try->set_value('mandatory', 'username',			$user_details['login']);
			$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);

			// Non Mandatory
			if($user_details['bday'] != '0')
			{
				$year 	= substr($bday, 0, 4);
				$month 	= substr($bday, 4, 2);
				$day 	= substr($bday, 6, 2);
				
				$try->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
				$try->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");				
			}
			
			$options = $this->_default_user_permissions;

			if($user_details['coppa'] == 'Y')
			{
				$options -= 8;
			}

			$try->set_value('nonmandatory', 'options',			$options);
			if ($user_details['referrer_id'])
			{
				// TODO: Get it
				#$try->set_value('nonmandatory', 'referrerid',		$user_details['referrerid']);
			}
			
			$try->set_value('nonmandatory', 'msn',				$user_details['msnm']);
			$try->set_value('nonmandatory', 'aim',				$user_details['aim']);
			$try->set_value('nonmandatory', 'icq',				$user_details['icq']);
			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password',			$user_details['passwd']);
			$try->set_value('nonmandatory', 'yahoo',			$user_details['yahoo']);
			$try->set_value('nonmandatory', 'joindate',			$user_details['join_date']);
			$try->set_value('nonmandatory', 'lastactivity',		$user_details['last_read']);
			$try->set_value('nonmandatory', 'lastvisit',		$user_details['last_visit']);
			
			$try->set_value('nonmandatory', 'postcount',		$user_details['posted_msg_count']);
			
			$try->set_value('nonmandatory', 'customtitle',		$user_details['custom_status']);
			$try->set_value('nonmandatory', 'usertitle',		$user_details['usertitle']);
			$try->set_value('nonmandatory', 'homepage',			$user_details['home_page']);
			$try->set_value('nonmandatory', 'pmpopup',			$this->option2bin($user_details['show_im']));
			
			$try->add_default_value('signature', 				addslashes($this->html_2_bb($user_details['sig'])));
			
			$try->add_default_value('Biography', 				substr(addslashes($user_details['bio']), 0, 250));
			$try->add_default_value('Location', 				substr(addslashes($user_details['location']), 0, 250));
			$try->add_default_value('Interests', 				substr(addslashes($user_details['interests']), 0, 250));
			$try->add_default_value('Occupation', 				substr(addslashes($user_details['occupation']), 0, 250));
			
			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['login']);
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
# Autogenerated on : July 5, 2005, 3:53 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
