<?php 
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.mvnforum
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/ 

class mvnforum_004 extends mvnforum_000
{
	var $_dependent = '003';

	function mvnforum_004($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_users'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['users_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 2000));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',1));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'] . ' mvnForumHome/memberavatars', 'get_avatars_path_1',$sessionobject->get_session_var('get_avatars_path_1'),1,60));
			$displayobject->update_html($displayobject->make_input_code("What is the full path to your avatars directory ? . mvnforum/upload/memberavatars/ ", 'get_avatars_path_2',$sessionobject->get_session_var('get_avatars_path_2'),1,60));
			
			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);
		
		$ava_path_1 = $sessionobject->get_session_var('get_avatars_path_1');		
		$ava_path_2 = $sessionobject->get_session_var('get_avatars_path_2');
		
		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}Member", 'MemberID', 0, $start_at, $per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);
		 
		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['users'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'user');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Mandatory
			// Can member group them from MemberGroup
			$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array[2]);
			$try->set_value('mandatory', 'username',				$data['MemberName']);
			$try->set_value('mandatory', 'importuserid',			$import_id);
			$try->set_value('mandatory', 'email',					$data['MemberEmail']);

			// Non mandatory
			// Crypt, can't do it, just use it for rand()
			$try->set_value('nonmandatory', 'password',				$data['MemberPassword']);
			$try->set_value('nonmandatory', 'ipaddress',			$data['MemberFirstIP']);
			$try->set_value('nonmandatory', 'posts',				$data['MemberPostCount']);
			$try->set_value('nonmandatory', 'joindate',				strtotime($data['MemberCreationDate']));
			$try->set_value('nonmandatory', 'options',				$this->_default_user_permissions);
			$try->set_value('nonmandatory', 'lastvisit',			strtotime($data['MemberLastLogon']));
			$try->set_value('nonmandatory', 'pmtotal',				$data['MemberMessageCount']);
			$try->set_value('nonmandatory', 'homepage',				$data['MemberHomepage']);
			$try->set_value('nonmandatory', 'msn',					$data['MemberMsn']);  	  
			$try->set_value('nonmandatory', 'yahoo',				$data['MemberYahoo']);
			$try->set_value('nonmandatory', 'aim',					$data['MemberAol']);
			$try->set_value('nonmandatory', 'icq',					$data['MemberIcq']);			  	  
			  	  
			$try->add_default_value('Occupation',					$data['MemberCareer']);
			$try->add_default_value('Location', 					$data['MemberAddress'] . $data['MemberCity'] . $data['MemberState'] . $data['MemberCountry']);
			
			if ($data['MemberBirthday'])
			{
			   $year 	= substr($data['MemberBirthday'], 0, 4);
			   $month 	= substr($data['MemberBirthday'], 4, 2);
			   $day 	= substr($data['MemberBirthday'], 6, 2);

			   $try->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
			   $try->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");
			}			
			
			if($data['MemberSignature'] != '')
			{
				$try->add_default_value('signature', 				$this->html_2_bb($data['MemberSignature']));
			}
			
			if ($sessionobject->get_session_var('get_avatars') AND $data['MemberAvatar'] != NULL)
			{
				$filename = NULL_EMPTY_STRING;
				if ($data['MemberAvatar'] == 'uploaded') // It's path _1 add the name and .jpg
				{
					$filename = $ava_path_1 . $data['MemberName'] . '.jpg';
				}
				else 
				{
					$filename = $ava_path_2 . substr($data['MemberAvatar'], strrpos($data['MemberAvatar'], "/")); // Keep the /
				}
				
				if (is_file($filename))
				{
					$try->set_value('nonmandatory', 'avatar', $filename);
				}				
			}			
			        

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $t_db_type, $t_tb_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"> ' . $import_id . ' :: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $data['MemberName']);
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
				}// $try->import_user
			}
			else
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}// is_valid
			unset($try);
		}// End foreach

		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			$this->build_user_statistics($Db_target, $t_db_type, $t_tb_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : December 11, 2007, 11:50 am
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
