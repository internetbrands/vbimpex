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
* smf_004 Import User module
*
* @package			ImpEx.smf
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class smf_004 extends smf_000
{
	var $_dependent 	= '003';


	function smf_004(&$displayobject)
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
			$displayobject->update_basic('title',$displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match", 0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars', 0));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'avatarfolder',$sessionobject->get_session_var('avatarfolder'),1,60));

			// End the table
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
		$user_array = $this->get_smf_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		// Groups info
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

			if($user_group_ids_array["$user_details[ID_GROUP]"])
			{
				$try->set_value('mandatory', 'usergroupid', 		$user_group_ids_array["$user_details[ID_GROUP]"]);
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid', 		$user_group_ids_array[69]);
			}

			
			
			$try->set_value('mandatory', 'username',				$user_details['memberName']);
			$try->set_value('mandatory', 'email',					$user_details['emailAddress']);
			$try->set_value('mandatory', 'importuserid',			$user_id);

			// Non Mandatory
			if ($user_details['additionalGroups'])
			{
				$old_group_ids = explode(',', $user_details['additionalGroups']);
				$new_ids = array();

				if (is_array($old_group_ids))
				{
					foreach ($old_group_ids as $old_id)
					{
						if ($user_group_ids_array[$old_id])
						{
							$new_ids[] = $user_group_ids_array[$old_id];
						}
					}
				}
				$try->set_value('nonmandatory', 'membergroupids', implode(',', $new_ids));
			}				
			
			$try->set_value('nonmandatory', 'displaygroupid',		'');
			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password',				$user_details['passwd']);

			if($user_details['birthdate'])
			{
				$year 	= substr($user_details['birthdate'], 0, 4);
				$month 	= substr($user_details['birthdate'], 5, 2);
				$day 	= substr($user_details['birthdate'], 8, 2);

				$try->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
				$try->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");
			}

			$try->set_value('nonmandatory', 'homepage',				$user_details['websiteUrl']);
			$try->set_value('nonmandatory', 'icq',					$user_details['ICQ']);
			$try->set_value('nonmandatory', 'aim',					$user_details['AIM']);
			$try->set_value('nonmandatory', 'yahoo',				$user_details['YIM']);
			$try->set_value('nonmandatory', 'joindate',				$user_details['dateRegistered']);

			if ($user_details['lastLogin'])
			{
				$try->set_value('nonmandatory', 'lastvisit',		$user_details['lastLogin']);
				$try->set_value('nonmandatory', 'lastactivity',		$user_details['lastLogin']);
			}
			else
			{
				$try->set_value('nonmandatory', 'lastvisit',		$user_details['dateRegistered']);
				$try->set_value('nonmandatory', 'lastactivity',		$user_details['dateRegistered']);
			}

			$try->set_value('nonmandatory', 'posts',				$user_details['posts']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['timeOffset']);
			$try->set_value('nonmandatory', 'options',				$this->_default_user_permissions);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['memberIP']);
			$try->set_value('nonmandatory', 'msn',					$user_details['MSN']);
			$try->set_value('nonmandatory', 'emailstamp',			$user_details['emailstamp']);

			$try->add_default_value('signature', 					$user_details['signature']);

			$path = $sessionobject->get_session_var('avatarfolder');

			if ($sessionobject->get_session_var('get_avatars'))
			{
				if ($user_details['avatar'] AND $user_details['avatar'] != 'noavatar')
				{
					// Its hosted
					if (strtolower(substr($user_details['avatar'], 0, 7)) == 'http://')
					{
						$try->set_value('nonmandatory', 'avatar',	$user_details['avatar']);
					}
					else if (is_file($path . '/' . $user_details['avatar']))
					{
						$try->set_value('nonmandatory', 'avatar',	$path . '/' . $user_details['avatar']);
					}
				}
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
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : June 24, 2004, 11:07 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
