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
* webwiz_004 Import User module
*
* @package			ImpEx.webwiz
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class webwiz_004 extends webwiz_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import User';


	function webwiz_004()
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
			$displayobject->update_html($displayobject->make_input_code('Users to import per cycle (must be greater than 1)','userperpage',250));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to associated imported users with existing users if the email address matches ?","email_match",0));
			
			#$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',1));
			#$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));
			
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
		$user_array 	= $this->get_webwiz_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);


		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');


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
			$try->set_value('mandatory', 'usergroupid',					$user_group_ids_array["$user_details[Group_ID]"]);
			$try->set_value('mandatory', 'username',					$user_details['Username']);
			$try->set_value('mandatory', 'email',						$user_details['Author_email']);
			$try->set_value('mandatory', 'importuserid',				$user_id);


			// Non Mandatory
			// Its salted, import it anyway as a random string to be reset by the user
			$try->set_value('nonmandatory', 'password',					$user_details['Password']);
			$try->set_value('nonmandatory', 'homepage',					addslashes($user_details['Homepage']));
			$try->set_value('nonmandatory', 'icq',						addslashes($user_details['ICQ']));
			$try->set_value('nonmandatory', 'aim',						addslashes($user_details['AIM']));
			$try->set_value('nonmandatory', 'yahoo',					addslashes($user_details['Yahoo']));
			$try->set_value('nonmandatory', 'msn',						addslashes($user_details['MSN']));
			$try->set_value('nonmandatory', 'joindate',					$this->fix_time($user_details['Join_date']));
			$try->set_value('nonmandatory', 'lastvisit',				$this->fix_time($user_details['Last_visit']));
			$try->set_value('nonmandatory', 'lastactivity',				$this->fix_time($user_details['Last_visit']));
			$try->set_value('nonmandatory', 'posts',					$user_details['No_of_posts']);
			$try->set_value('nonmandatory', 'timezoneoffset',			$user_details['Time_offset_hours']);
			$try->set_value('nonmandatory', 'pmpopup',					$user_details['PM_notify']);
			$try->set_value('nonmandatory', 'options',					$this->_default_user_permissions);
			
			if($user_details['DOB'])
			{
				$bits_array = $this->fix_time($user_details['DOB'], false); #YYYYMMDDHHMMSS 
				
				$try->set_value('nonmandatory', 'birthday',			$bits_array['month'] . "-" . $bits_array['day'] . "-" . $bits_array['year']); # MM-DD-YYYY
				$try->set_value('nonmandatory', 'birthday_search',	$bits_array['year'] . "-" . $bits_array['month'] . "-" . $bits_array['day']); # YYYY-MM-DD
			}
			
			// Its there, just need path / URL checking.
			#$try->set_value('nonmandatory', 'avatar',					$user_details['Avatar']);

			$try->add_default_value('Location', 						addslashes($user_details['Location']));
			$try->add_default_value('Interests', 						addslashes($user_details['Interests']));
			$try->add_default_value('Occupation', 						addslashes($user_details['Occupation']));

			$try->add_default_value('signature', 						addslashes($this->html_2_bb($user_details['Signature'])));

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['Username']);
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
# Autogenerated on : November 8, 2004, 2:52 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
