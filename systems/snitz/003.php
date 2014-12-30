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
* snitz_003 Import User module
*
* @package			ImpEx.snitz
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class snitz_003 extends snitz_000
{
	var $_dependent 	= '001';

	function snitz_003(&$displayobject)
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
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

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
		$user_array 	= $this->get_snitz_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

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
			$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array[69]);
			$try->set_value('mandatory', 'username',				$user_details['M_NAME']);
			$try->set_value('mandatory', 'email',					$user_details['M_EMAIL']);
			$try->set_value('mandatory', 'importuserid',			$user_details['MEMBER_ID']);

			// Non Mandatory
			// If its their it probally plain text
			if ($user_details['M_PASSWORD'])
			{
				$try->set_value('nonmandatory', 'password', 		$user_details['M_PASSWORD']);
			}
			else
			{
				$try->set_value('nonmandatory', 'password',			addslashes($this->fetch_user_salt()));
			}

			$try->set_value('nonmandatory', 'homepage',				addslashes($user_details['M_HOMEPAGE']));
			$try->set_value('nonmandatory', 'icq',					addslashes($user_details['M_ICQ']));
			$try->set_value('nonmandatory', 'aim',					addslashes($user_details['M_AIM']));
			$try->set_value('nonmandatory', 'yahoo',				addslashes($user_details['M_YAHOO']));
			$try->set_value('nonmandatory', 'msn',					addslashes($user_details['M_MSN']));
			$try->set_value('nonmandatory', 'usertitle',			addslashes($user_details['M_TITLE']));

			$try->set_value('nonmandatory', 'joindate',				$this->time_to_stamp($user_details['M_DATE']));
			$try->set_value('nonmandatory', 'lastvisit',			$this->time_to_stamp($user_details['M_LASTPOSTDATE']));
			$try->set_value('nonmandatory', 'lastactivity',			$this->time_to_stamp($user_details['M_LASTPOSTDATE']));
			$try->set_value('nonmandatory', 'lastpost',				$this->time_to_stamp($user_details['M_LASTPOSTDATE']));
			$try->set_value('nonmandatory', 'posts',				$user_details['M_POSTS']);

			unset($dob);
			unset($coppa);
			$dob = substr($user_details['M_DOB'], 0, 4) . "-" . substr($user_details['M_DOB'], 4, 2) . "-" . substr($user_details['M_DOB'], 6, 2);
			$birthday = substr($user_details['M_DOB'], 4, 2) . "-" . substr($user_details['M_DOB'], 6, 2) . "-" . substr($user_details['M_DOB'], 0, 4);
			$coppa = $this->is_coppa($dob);

			$options = 3159;

			if($coppa['status'])
			{
				if($coppa['is_coppa'])
				{
					$options -= 8;
				}
			}

			$try->set_value('nonmandatory', 'options',				$options);
			$try->set_value('nonmandatory', 'birthday',				$birthday);
			$try->set_value('nonmandatory', 'birthday_search',		$dob);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['M_IP']);

			unset($location);
			if($user_details['M_CITY'])
			{
				$location .= $user_details['M_CITY'] . ", ";
			}
			if($user_details['M_STATE'])
			{
				$location .= $user_details['M_STATE'] . ", ";
			}
			if($user_details['M_COUNTRY'])
			{
				$location .= $user_details['M_COUNTRY'] . ", ";
			}

			$location = substr($location, 0, -2);
			$location = substr($location, 0, 248);
			$location .=  ".";

			$try->add_default_value('Location', 					addslashes(trim($location)));
			$try->add_default_value('Interests', 					addslashes(trim($user_details['M_HOBBIES'])));
			$try->add_default_value('Occupation', 					addslashes(trim($user_details['M_OCCUPATION'])));

			$try->add_default_value('signature', 					addslashes(trim($user_details['M_SIG'])));

			if($user_details['avatar'])
			{
				echo "<br><b>getting avatar</b>";
				$try->set_value('nonmandatory', 'avatar',trim($user_details['avatar']));
			}

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
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try);
		}// End resume


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
# Autogenerated on : May 20, 2004, 12:45 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
