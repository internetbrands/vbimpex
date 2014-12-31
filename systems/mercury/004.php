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
* mercury_004 Import User module
*
* @package			ImpEx.mercury
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class mercury_004 extends mercury_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';


	function mercury_004()
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
			$displayobject->update_html($displayobject->make_input_code('What is the full path to your avatars directory ? (make sure the web server has access to read them)','get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));
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
		$user_array 	= $this->get_mercury_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');


		foreach ($user_array as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			$try->set_value('mandatory', 'username',		$user['user_name']);
			$try->set_value('mandatory', 'email',			$user['user_email']);
			$try->set_value('mandatory', 'usergroupid',		$user_group_ids_array["$user[user_group]"]);
			$try->set_value('mandatory', 'importuserid',	$user_id);

			$try->set_value('nonmandatory', 'joindate',		$user['user_joined']);
			$try->set_value('nonmandatory', 'customtitle',	$user['user_title']);

			if($user['user_birthday'] != null)
			{
				$bits = explode('-', $user['user_birthday']);
				$try->set_value('nonmandatory', 'birthday',			$bits[1] . "-" . $bits[2] . "-" . $bits[0]);
				$try->set_value('nonmandatory', 'birthday_search',	$user['user_birthday']);
			}

			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password', 	$user['user_password']);

			$try->set_value('nonmandatory', 'options',		$this->_default_user_permissions);

			$try->set_value('nonmandatory', 'homepage',		addslashes($user['user_homepage']));

			$try->set_value('nonmandatory', 'aim',			addslashes($user['user_aim']));
			$try->set_value('nonmandatory', 'icq',			addslashes($user['user_icq']));
			$try->set_value('nonmandatory', 'msn',			addslashes($user['user_msn']));
			$try->set_value('nonmandatory', 'yahoo',		addslashes($user['user_yim']));
			$try->set_value('nonmandatory', 'lastactivity',	$user['user_lastpost']);
			$try->set_value('nonmandatory', 'lastvisit',	$user['user_lastvisit']);
			$try->set_value('nonmandatory', 'posts',		$user['user_posts']);

			$try->add_default_value('Location', 			addslashes($user['user_location']));
			$try->add_default_value('Interests',			addslashes($user['user_interests']));

			if($user['user_signature'] != '')
			{
				$try->add_default_value('signature', 	addslashes($user['user_signature']));
			}	

			if ($ava_path = $sessionobject->get_session_var('get_avatars_path') AND $user['user_avatar_type'] != 'none')
			{
				// ITS A LOCAL FILE
				if($user['user_avatar_type'] == 'local')
				{
					if(substr($ava_path,-1) == '/')
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . trim($user['user_avatar']));
					}
					else
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . '/' . trim($user['user_avatar']));
					}
				}
				
				// ITS AN UPLOADED FILE
				if($user['user_avatar_type'] == 'uploaded')
				{
					if(substr($ava_path,-1) == '/')
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . 'uploaded/' . trim($user['user_avatar']));
					}
					else
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . '/uploaded/' . trim($user['user_avatar']));
					}
				}				

				// ITS AN URL
				if($user['user_avatar_type'] == 'url')
				{
						$try->set_value('nonmandatory', 'avatar',	trim($user['user_avatar']));
				}				
			}

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user['user_name']);
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
# Autogenerated on : June 9, 2005, 7:21 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
