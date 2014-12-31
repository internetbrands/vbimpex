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
* ipb_004 Import Users
*
*
* @package 		ImpEx.ipb
*
*/
class ipb_004 extends ipb_000
{
	var $_dependent 	= '003';

	function ipb_004(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_user'];
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
					$this->_restart = true;
					$displayobject->display_now("<h4>Imported users have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_non_admin_users",
											 'Check database permissions and user table');
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
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'avatarfolder',$sessionobject->get_session_var('avatarfolder'),1,60));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
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
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$start_at 			= $sessionobject->get_session_var('userstartat');
		$per_page 			= $sessionobject->get_session_var('userperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Check and clear the NON admin users
		if ($sessionobject->get_session_var('clear_non_admin_users') == 1)
		{
			if ($this->clear_non_admin_users($Db_target,$target_database_type,$target_table_prefix))
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

		// Get the arrays well need to sort the out
		$user_group_ids =  $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		$bannedgroup 	=  $this->get_banned_group($Db_target, $target_database_type, $target_table_prefix);
		$doneusers	 	=  $this->get_done_user_ids($Db_target, $target_database_type, $target_table_prefix);

		// Get a page worths of users
		$user_array  =  $this->get_ipb_user_details($Db_source, $source_database_type, $source_table_prefix, $start_at, $per_page);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($user_array) . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($start_at + count($user_array)) . "</p>");

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($user_array as $user_id => $user)
		{
			// Have we already associated them, or imported them by mistake etc.
			if (!array_key_exists ($user_id, $doneusers))
			{
				// Get a copy of a default user object
				$try = (phpversion() < '5' ? $user_object : clone($user_object));

				// Auto associate
				if ($sessionobject->get_session_var('email_match'))
				{
					$try->_auto_email_associate = true;
				}

				$usersettings = 0;

				if($user['view_sigs'])			{$usersettings + 1;}
				if($user['view_avs'])			{$usersettings + 2;}
				if($user['view_img'])			{$usersettings + 4;}
				if($user['view_sigs'])			{$usersettings + 1;}
				if($user['coppa_user'])			{$usersettings + 8;}
				if($user['allow_admin_mails'])	{$usersettings + 16;}
				if(!$user['hide_email']) 		{$usersettings + 256;}

				// Mandatory
				$try->set_value('mandatory', 'username',			addslashes(str_replace("&#124;","|",$user['name'])));
				$try->set_value('mandatory', 'email',				$user['email']);
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids[$user['mgroup']]);
				$try->set_value('mandatory', 'importuserid',		$user['id']);

				// Non mandatory
				$try->_password_md5_already = true;
				$try->set_value('nonmandatory', 'password',			$user['password']);
				$try->set_value('nonmandatory', 'joindate',			$user['joined']);

				// If import avatars
				if ($sessionobject->get_session_var('avatars_import'))
				{
					if($user['avatar'] != 'noavatar' AND $user['avatar'] != NULL)
					{
						if(substr($user['avatar'],0,7) == 'upload:')
						{
							$try->set_value('nonmandatory', 'avatar',	$sessionobject->get_session_var('avatarfolder') . substr($user['avatar'], 7));
						}
						else if(substr($user['avatar'],0,7) == 'http://')
						{
							// grab it from a URL
							$try->set_value('nonmandatory', 'avatar',	$user['avatar']);
						}
						else
						{
							// Wonder what it is then
							$try->set_value('nonmandatory', 'avatar',	$sessionobject->get_session_var('avatarfolder') . $user['avatar']);
						}
					}
				}

				$try->set_value('nonmandatory', 'homepage',			addslashes($user['website']));
				$try->set_value('nonmandatory', 'icq',				addslashes($user['icq_number']));
				$try->set_value('nonmandatory', 'aim',				addslashes($user['aim_name']));
				$try->set_value('nonmandatory', 'msn',				addslashes($user['msnname']));

				// Its pointless if its garbage
				if($user['bday_day'] != null AND $user['bday_month'] != null AND $user['bday_year'] != null)
				{

					$try->set_value('nonmandatory', 'birthday',			str_pad($user['bday_month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($user['bday_day'], 2, '0', STR_PAD_LEFT) . "-" . $user['bday_year']);
					$try->set_value('nonmandatory', 'birthday_search',	$user['bday_year'] . "-" . str_pad($user['bday_month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($user['bday_day'], 2, '0', STR_PAD_LEFT));
				}

				$try->set_value('nonmandatory', 'ipaddress',		$user['ip_address']);
				$try->set_value('nonmandatory', 'lastvisit',		$user['last_visit']);
				$try->set_value('nonmandatory', 'lastactivity',		$user['last_activity']);

				$try->set_value('nonmandatory', 'usertitle',		addslashes($user['title']));
				$try->set_value('nonmandatory', 'posts',			$user['posts']);
				$try->set_value('nonmandatory', 'lastpost',			$user['last_post']);

				$try->set_value('nonmandatory', 'passworddate',		'');
				$try->set_value('nonmandatory', 'membergroupids',	'');
				$try->set_value('nonmandatory', 'displaygroupid',	'');

				// Default values
				$try->add_default_value('signature', 				addslashes($this->html_2_bb($user['signature'])));
				$try->add_default_value('Location',		 			addslashes($this->html_2_bb($user['location'])));
				$try->add_default_value('Interests', 				addslashes($this->html_2_bb($user['interests'])));

				if($try->is_valid())
				{
					if($try->import_user($Db_target,$target_database_type,$target_table_prefix))
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
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try);
		}

		if (count($user_array) == 0 OR count($user_array) < $per_page)
		{
			// build_user_statistics();
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

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
		else
		{
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			$sessionobject->set_session_var('userstartat',(intval($start_at)+intval($sessionobject->get_session_var('userperpage'))));
		}
	}
}
/*======================================================================*/
?>
