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
* Ubb Import Users module
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_003 extends ubb_classic_000
{
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Users';

	function ubb_classic_003()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_users'))
				{
					$this->_restart = true;
					$displayobject->display_now("<h4>Imported users have been cleared</h4>");
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
			$displayobject->update_html($displayobject->make_input_code("Users to import per cycle (must be greater than 1)","usersperpage",5000));

			$displayobject->update_html($displayobject->make_description("<b>Note</b> - The <i>username, displayname, forename</i> and <i>surname</i> are imported and stored as userfields in the database if you wish to use them at a later date."));
			$displayobject->update_html($displayobject->make_description("UBB has two names, a display name and a login name, vB has one username. Both are imported, though the loginname is used for the vB username, you have to modify the templates to make use of both. "));
			$displayobject->update_html($displayobject->make_yesno_code("Click yes to use the disaply name as the vB username, or no to use the login name.","use_display_name",1));
			$displayobject->update_html($displayobject->make_description("<b>If the display name is chosen and dosn't exsist the importer will default to the login name</b> . "));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the custom avatars ","importcustomavatars",0));
			$displayobject->update_html($displayobject->make_description('Select largest avatar size allowed (setting this will force ImpEx to import them). ' . $displayobject->make_select($this->_avatar_size, 'avatar_size')));
			$displayobject->update_html($displayobject->make_description('<b>WARNING</b> If the importer has to retrieve images from slow or servers that it can connect to it WILL time out the script. If you really want to change the php.ini timeout to 0 if you can, then change is back after you have finished the import.'));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to associated imported users with existing users if the email address matches ?","email_match",0));
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			$sessionobject->add_session_var('usersstartat','0');
			$sessionobject->add_session_var('totalusersdone','0');


			// Add all the custom fields we want to import
			$tdt = $sessionobject->get_session_var('targetdatabasetype');
			$ttp = $sessionobject->get_session_var('targettableprefix');

			$this->add_custom_field($Db_target, $tdt, $ttp, 'displayname','the ubb displayname');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'loginname','the ubb loginname');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'forename','the ubb forename');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'surname','the ubb surname');

			$this->add_custom_field($Db_target, $tdt, $ttp, 'custom1','Custom Field 1');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'custom2','Custom Field 2');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'custom3','Custom Field 3');
			$this->add_custom_field($Db_target, $tdt, $ttp, 'custom4','Custom Field 4');

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

	function resume(&$sessionobject, &$displayobject, &$Db_target, $Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
		$user_start_at			= $sessionobject->get_session_var('usersstartat');
		$user_per_page			= $sessionobject->get_session_var('usersperpage');
		$ubbmemberspath 		= $sessionobject->get_session_var('ubbmemberspath');
		$user_object 			= new ImpExData($Db_target, $sessionobject, 'user');
		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('avatar_size') != '-1')
		{
			$sessionobject->add_session_var('importcustomavatars','1');
		}

		//Data 
		$user_array 			= $this->get_members_list($ubbmemberspath, $user_start_at, $user_per_page);
		
		// Refrence
		$user_group_ids_array 	= $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		$displayobject->display_now("<h4>Importing " . (count($user_array)-1) . " users</h4><p><b>From</b> : " . $user_start_at . " ::  <b>To</b> : " . ($user_start_at + count($user_array) -1) . "</p>");

		foreach ($user_array as $usernumber => $userfile)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			$userarray = file("$ubbmemberspath/$userfile");

			$datebits = explode("-",trim($userarray[10]));
			$date = mktime(0,0,0,$datebits[0],$datebits[1],$datebits[2]);


			if($sessionobject->get_session_var('use_display_name'))
			{
				// If the display name is null, use the loginname
				$try->set_value('mandatory', 'username', $this->clean_name($this->iif(trim($userarray[15])== '',trim($userarray[0]),trim($userarray[15]))));
			}
			else
			{
				$try->set_value('mandatory', 'username', $this->clean_name(trim($userarray[0])));
			}

			$try->set_value('mandatory', 'email', 		trim($userarray[2]));

			$group_id = $this->get_ubb_usergroup(trim($userarray[4]));

			if($group_id AND $user_group_ids_array[$group_id])
			{
				$try->set_value('mandatory', 'usergroupid', $user_group_ids_array[$group_id]);
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid', $user_group_ids_array[73]);
			}

			unset($group_id);
			$try->set_value('mandatory', 'importuserid', 	intval($usernumber));

			$try->set_value('nonmandatory', 'password', 	addslashes(trim($userarray[1])));
			$try->set_value('nonmandatory', 'homepage',		addslashes($this->iif (trim($userarray[3]) == "http://",'',trim($userarray[3]))));
			$try->set_value('nonmandatory', 'posts',		trim($userarray[7]));
			$try->set_value('nonmandatory', 'joindate', 	$date);
			$try->set_value('nonmandatory', 'icq', 			addslashes(trim($userarray[13])));
			$try->set_value('nonmandatory', 'daysprune',	trim($userarray[21]));
			$try->set_value('nonmandatory', 'aim', 			addslashes(trim($userarray[22])));

			if($userarray[31] AND strlen($title = trim($userarray[31])))
			{
				$try->set_value('nonmandatory', 'customtitle',	'1');
				$try->set_value('nonmandatory', 'usertitle',	$title);
			}
			else
			{
				$try->set_value('nonmandatory', 'customtitle',	'0');
			}
			// $userarray[20] is the profile image
			$options = 2135;

			// If there is a birthday, get it and set the coppa
			if ($userarray[32])
			{
				unset($coppa_result, $date);
				
				$date = substr($userarray[32],0 , 4) . '-' . substr($userarray[32], 4, 2) . '-' . substr($userarray[32], 6, 2);

				if(checkdate(intval(substr($userarray[32], 4, 2)), intval(substr($userarray[32], 6, 2)), intval(substr($userarray[32],0 , 4))))
				{
					$coppa_result = $this->is_coppa($date);

					if($coppa_result['is_coppa'])
					{
						$options += 8;
						$try->set_value('nonmandatory', 'membergroupids',	$user_group_ids_array[72]);
					}

					$try->set_value('nonmandatory', 'birthday',			substr($userarray[32], 4, 2) . "-" . substr($userarray[32], 6, 2) . "-" . substr($userarray[32],0 , 4));
					$try->set_value('nonmandatory', 'birthday_search',	$date);
				}
			}

			$try->set_value('nonmandatory', 'options',		$options);

			if ($sessionobject->get_session_var('importcustomavatars'))
			{
				$path = $this->iif(trim($userarray[37]) == "http://",'',trim($userarray[37]));
				$result = $this->check_avatar_size($path, $sessionobject->get_session_var('avatar_size'));

				if ($result === true)
				{
					$displayobject->display_now(" :: <b>Avatar OK.</b>");
					$try->set_value('nonmandatory', 'avatar',	$path);
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

			$try->add_custom_value('loginname', 	 	addslashes(trim($userarray[15])));
			$try->add_custom_value('displayname', 		addslashes(trim($userarray[0])));
			$try->add_custom_value('forename', 		addslashes(trim($userarray[16])));
			$try->add_custom_value('surname', 		addslashes(trim($userarray[17])));

			$try->add_custom_value('custom1', 		addslashes(trim($userarray[18])));
			$try->add_custom_value('custom2', 		addslashes(trim($userarray[19])));
			$try->add_custom_value('custom3', 		addslashes(trim($userarray[20])));
			$try->add_custom_value('custom4', 		addslashes(trim($userarray[21])));

			$try->add_default_value('Location', 		addslashes(trim($userarray[6])));
			$try->add_default_value('Interests', 		addslashes(trim($userarray[9])));
			$try->add_default_value('Occupation', 		addslashes(trim($userarray[5])));

			$try->add_default_value('signature', 		addslashes(trim($userarray[12])));

			if($try->is_valid())
			{
				if($result = $try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					if($result['automerge'])
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>Auto-merged</b></span> :: " . $try->get_value('mandatory','username'));
					}
					else
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','username'));
					}
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
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

			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																		$sessionobject->return_stats($class_num, '_time_taken'),
																		$sessionobject->return_stats($class_num, '_objects_done'),
																		$sessionobject->return_stats($class_num, '_objects_failed')
																		));
			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('import_users','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$displayobject->update_html($displayobject->print_redirect('index.php',''));
			$sessionobject->set_session_var('usersstartat',$user_start_at+$user_per_page);
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

