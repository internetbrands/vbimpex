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
* phpBB2_004 Import Users module
*
* @package 		ImpEx.phpBB2
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB2_004 extends phpBB2_000
{
	var $_dependent 	= '003';

	function phpBB2_004(&$displayobject)
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
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',0));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

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

		// Get the banned and done (associated users)
		$bannedgroup =  $this->get_banned_group($Db_target, $target_database_type, $target_table_prefix);
		$usergroups	 =	$this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Get a page worths of users
		$user_array  =  $this->get_phpbb2_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($user_array) . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $user_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($user_start_at + count($user_array)) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}


			$try->set_value('mandatory', 'username',		$user['username']);
			$try->set_value('mandatory', 'email',			$user['user_email']);

			if ($user['user_active'])
			{
				// Active group
				$try->set_value('mandatory', 'usergroupid',		$usergroups[69]);
			}
			else
			{
				// Inactive group
				$try->set_value('mandatory', 'usergroupid',		$usergroups[70]);
			}

			$try->set_value('mandatory', 'importuserid',		$user_id);

			$old_group_ids = $this->get_phpbb2_usergroupids($Db_source, $source_database_type, $source_table_prefix, $user_id);

			if (count($old_group_ids))
			{
				$new_ids = array();

				if (is_array($old_group_ids))
				{
					foreach ($old_group_ids as $old_id)
					{
						if ($usergroups[$old_id])
						{
							$new_ids[] = $usergroups[$old_id];
						}
					}
				}

				$try->set_value('nonmandatory', 'membergroupids', implode(',', $new_ids));
			}

			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password', 	$user['user_password']);

			$try->set_value('nonmandatory', 'aim',			addslashes($user['user_aim']));
			$try->set_value('nonmandatory', 'icq',			addslashes($user['user_icq']));


			if(strpos($user['user_regdate'],','))
			{
				$try->set_value('nonmandatory', 'joindate',		strtotime($user['user_regdate']));
			}
			else
			{
				$try->set_value('nonmandatory', 'joindate',		$user['user_regdate']);
			}

			$try->set_value('nonmandatory', 'homepage',		addslashes($user['user_website']));
			$try->set_value('nonmandatory', 'lastactivity',	$user['user_lastvisit']);
			$try->set_value('nonmandatory', 'yahoo',		addslashes($user['user_yim']));
			$try->set_value('nonmandatory', 'msn',			addslashes($user['user_msnm']));
			$try->set_value('nonmandatory', 'posts',		$user['user_posts']);

			$try->add_default_value('Occupation',			$user['user_occ']);
			$try->add_default_value('Location', 			$user['user_from']);
			$try->add_default_value('Interests',			$user['user_interests']);

			$try->set_value('nonmandatory', 'options',		$this->_default_user_permissions);

			// Common birthday hack
			if ($user['user_birthday'] AND $user['user_birthday'] != 999999)
			{
				/* Old hack
			   $bd = $user['user_birthday']*86400+86400;
			   $year	= @date("Y", $bd); // Errors off for windows
			   $month	= @date("m", $bd);
			   $day		= @date("d", $bd);
			   */

			   $year 	= substr($user['user_birthday'], 0, 4);
			   $month 	= substr($user['user_birthday'], 4, 2);
			   $day 	= substr($user['user_birthday'], 6, 2);

			   $try->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
			   $try->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");
			}

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

					if(substr($user['user_avatar'],-1) == '/')
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
				$try->add_default_value('signature', $this->phpbb_html($this->html_2_bb($user['user_sig'])));
			}

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
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $user_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $user_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try, $ava_path);
		}

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
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
