<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* ubb_threads_004 Import User module
*
* @package			ImpEx.ubb_threads
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ubb_threads_004 extends ubb_threads_000
{
	var $_dependent 	= '003';

	function ubb_threads_004(&$displayobject)
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

			$which_email = array ( '1' => 'U_Email', '2' => 'U_RegEmail');
			$which_username = array ( '1' => 'U_Username', '2' => 'U_LoginName');

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['which_email'] . $displayobject->make_select($which_email, 'which_email')));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['which_username'] . $displayobject->make_select($which_username, 'which_username')));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['avatar_size'] . $displayobject->make_select($this->_avatar_size, 'avatar_size')));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('userdone','0');

			$tdt = $sessionobject->get_session_var('targetdatabasetype');
			$ttp = $sessionobject->get_session_var('targettableprefix');

			$this->add_custom_field($Db_target, $tdt, $ttp, 'user_name_impex','The user name');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'extra_1','extra_1');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'extra_2','extra_2');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'extra_3','extra_3');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'extra_4','extra_4');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'extra_5','extra_5');
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
		$count_number			= 0;

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('avatar_size') != '-1')
		{
			$sessionobject->add_session_var('importcustomavatars','1');
		}

		$importcustomavatars	= $sessionobject->get_session_var('importcustomavatars');

		// Get an array of user details
		$user_array 	= $this->get_ubb_threads_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		// Groups info
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $target_database_type, $target_table_prefix);

		// Check for page end
		if ($source_database_type == 'mysql')
		{
			$count_number = count($user_array['data']);
		}
		else
		{
			$count_number = count($user_array);
		}

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} {$count_number}" . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $user_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($user_start_at + $count_number) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array['data'] as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Member groups
			unset($old_group_array, $new_group_array, $new_group_string, $usergroupid);
			$old_group_array = explode('-',$user_details['U_Groups']);

			foreach($old_group_array as $old_group_id)
			{
				if($old_group_id AND $user_group_ids_array[$old_group_id])
				{
					$new_group_array[] = $user_group_ids_array[$old_group_id];
				}
			}

			if($new_group_array)
			{
				$try->set_value('mandatory', 'usergroupid', 		$new_group_array[0]);
				array_splice($new_group_array, 0, 1);
				$new_group_string = implode(',', $new_group_array);
				$try->set_value('nonmandatory', 'membergroupids', 	$new_group_string);
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid', 		$user_group_ids_array[69]);
			}


			if ($user_details['U_Banned'])
			{
				$current = $try->get_value('nonmandatory', 'membergroupids');
				if(is_array($current))
				{
					$current = explode($current);
					$current[] = $user_group_ids_array[70];
					$new_group_string = implode(',', $current);
					$try->set_value('nonmandatory', 'membergroupids', 	$new_group_string);
				}
				else
				{
					$try->set_value('nonmandatory', 'membergroupids', $user_group_ids_array[70]);
				}
			}


			// Mandatory
			if($sessionobject->get_session_var('which_username') == '1')
			{
				$try->set_value('mandatory', 'username',			$user_details['U_Username']);
			}
			else
			{
				$try->set_value('mandatory', 'username',			$user_details['U_LoginName']);
			}

			if($sessionobject->get_session_var('which_email') == '1')
			{
				$try->set_value('mandatory', 'email',				$user_details['U_Email']);
			}
			else
			{
				$try->set_value('mandatory', 'email',				$user_details['U_RegEmail']);
			}

			$try->set_value('mandatory', 'importuserid',			$user_details['U_Number']);


			// Non Mandatory
			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password',				$user_details['U_Password']);
			$try->set_value('nonmandatory', 'passworddate',			time());

			if($user_details['U_Homepage'])
			{
				if (strpos($user_details['U_Homepage'], 'http://') === false)
				{
					$user_details['U_Homepage'] = "http://" . $user_details['U_Homepage'];
				}

				$try->set_value('nonmandatory', 'homepage',			$user_details['U_Homepage']);
			}

			$try->set_value('nonmandatory', 'joindate',				$user_details['U_Registered']);
			$try->set_value('nonmandatory', 'lastvisit',			$user_details['U_Laston']);
			$try->set_value('nonmandatory', 'lastactivity',			$user_details['U_Laston']);
			$try->set_value('nonmandatory', 'posts',				$user_details['U_Totalposts']);

			$try->set_value('nonmandatory', 'usernote',				$user_details['usernote']);


			if($user_details['U_Birthday'])
			{
				$bits = explode('/',$user_details['U_Birthday']); # DD/MM/YYYY
				$try->set_value('nonmandatory', 'birthday',			$bits[1] . "-" . $bits[0] . "-" . $bits[2]); # MM-DD-YYYY
				$try->set_value('nonmandatory', 'birthday_search',	$bits[2] . "-" . $bits[1] . "-" . $bits[0]); # YYYY-MM-DD
			}


			$options = 3159;

			if($user_details['U_CoppaUser'])
			{
				$options -= 8;
			}

			$try->set_value('nonmandatory', 'options',				$options);
			$try->set_value('nonmandatory', 'ipaddress',			$user_details['U_RegIP']);

			$try->add_default_value('signature', 					$this->html_2_bb($user_details['U_Signature']));

			if($user_details['U_Name'])
			{
				$try->add_custom_value('user_name_impex', 				$user_details['U_Name']);
			}
			if($user_details['U_Extra1'])
			{
				$try->add_custom_value('extra_1', 					$user_details['U_Extra1']);
			}
			if($user_details['U_Extra2'])
			{
				$try->add_custom_value('extra_2', 					$user_details['U_Extra2']);
			}
			if($user_details['U_Extra3'])
			{
				$try->add_custom_value('extra_3', 					$user_details['U_Extra3']);
			}
			if($user_details['U_Extra4'])
			{
				$try->add_custom_value('extra_4', 					$user_details['U_Extra4']);
			}
			if($user_details['U_Extra5'])
			{
				$try->add_custom_value('extra_5', 					$user_details['U_Extra5']);
			}

			if ($importcustomavatars AND $user_details['U_Picture'])
			{
				$result = $this->check_avatar_size($user_details['U_Picture'], $sessionobject->get_session_var('avatar_size'));

				if ($result === true)
				{
					$displayobject->display_now(" :: <b>Avatar OK.</b>");
					$try->set_value('nonmandatory', 'avatar',		$user_details['U_Picture']);
				}
				elseif ($result === false)
				{
					// Not there or couldn't open it
				}
				else
				{
					$displayobject->display_now(" :: <b>Avatar too big its {$result} bytes, skipping.</b>");
				}
			}


			$try->add_default_value('Biography', 					substr(addslashes($user_details['U_Bio']), 0, 250));
			$try->add_default_value('Location', 					substr(addslashes($user_details['U_Location']), 0, 250));
			$try->add_default_value('Interests', 					substr(addslashes($user_details['U_Hobbies']), 0, 250));
			$try->add_default_value('Occupation', 					substr(addslashes($user_details['U_Occupation']), 0, 250));


			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory','username'));
					}

					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					if(shortoutput)
					{
						$displayobject->display_now('X');
					}
					else
					{
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
					}

					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $user_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				}
			}
			else
			{
				if(shortoutput)
				{
					$displayobject->display_now('X');
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}

				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End resume


		if ($count_number == 0 OR $count_number < $user_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring, $sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'), $sessionobject->return_stats($class_num, '_objects_failed')));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('userstartat',$user_array['lastid']);

		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : May 17, 2004, 10:34 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
