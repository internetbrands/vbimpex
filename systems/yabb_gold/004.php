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
* yabb_gold_004 Import User module
*
* @package			ImpEx.yabb_gold
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yabb_gold_004 extends yabb_gold_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import User';

	function yabb_gold_004()
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
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the avatars, this can take some time if they are remotely linked","get_avatars",1));
			$displayobject->update_html($displayobject->make_input_code('Full Path to YaBB avatar folder (if you have one, leave blank for just URLs).','avatarpath',$sessionobject->get_session_var('avatarpath'),1,60));
			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('userdone','0');

			// Add all the custom fields we want to import
			$tdt = $sessionobject->get_session_var('targetdatabasetype');
			$ttp = $sessionobject->get_session_var('targettableprefix');

			$this->add_custom_field($Db_target, $tdt, $ttp, 'displayname','the YaBB - gold displayname');

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
		$memberspath = $sessionobject->get_session_var('forumspath') . '/Members';
		$user_array 	= $this->get_yabb_gold_user_details($memberspath, $user_start_at, $user_per_page);
		$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $target_database_type, $target_table_prefix);
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
			if($user_group_ids_by_name_array["$user_details[rank]"])
			{
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids_by_name_array["$user_details[rank]"]);
			}
			else
			{
				$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);
			}

			$try->set_value('mandatory', 'username',				$user_details['username']);
			$try->set_value('mandatory', 'email',					$user_details['emailaddress']);
			$try->set_value('mandatory', 'importuserid',				$user_id);

			# Not used yet
			# =======================
			# homepagetitle
			# gender
			# usertext
			# avatar - seems to be a local relative path ........ could do it.
			# timeformat
			# hideemail

			// Non Mandatory
			$try->set_value('nonmandatory', 'homepage',				$user_details['homepage']);
			$try->set_value('nonmandatory', 'password',				trim($user_details['password']));
			$try->set_value('nonmandatory', 'icq',					$user_details['icq']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['timeoffset']);
			$try->set_value('nonmandatory', 'options',				$this->_default_user_permissions);
			$try->set_value('nonmandatory', 'birthday',				$user_details['birthday']);
			if ($user_details['regdate'])
			{
				$try->set_value('nonmandatory', 'joindate',			$this->dodate($user_details['regdate']));
			}
			
			if ($user_details['birthday'])
			{
				$try->set_value('nonmandatory', 'birthday_search',	$this->dodate($user_details['birthday']));
			}

			// Avatar
			$avatar = trim($user_details['avatar']);
			
			if ($sessionobject->get_session_var('get_avatars') AND $avatar != NULL)
			{
				if(strstr($avatar,'http://'))
				{
					//Its a url
					$try->set_value('nonmandatory', 'avatar',		$avatar);
				}
				else
				{
					// Ensure a trailing path slash
					$ava_path = $sessionobject->get_session_var('avatarpath');
					$ava_path = (substr($avatar, -1) == '/' ? $ava_path : $ava_path . '/');
					
					// Its going to be in the images/avatars/ dir somewhere hopefully.
					
					if(is_file($ava_path . $avatar))
					{
						$try->set_value('nonmandatory', 'avatar',	$ava_path . $avatar);
					}
					else if (is_file($ava_path . 'useravatars/' . $avatar)) // NOTE: Is this defafult ?
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . '/' . trim($user['avatar']));
					}
					else
					{
						$displayobject->display_now(' :: Cannot find local avatar file.');
					}
				}
			}			
			
			// Custom
			$try->add_custom_value('displayname', 					addslashes($user_details['displayname']));

			// Default
			$try->add_default_value('signature', 					addslashes($user_details['signature']));
			$try->add_default_value('Occupation', 					addslashes($user_details['occupation']));
			$try->add_default_value('Interests', 					addslashes($user_details['hobby']));
			$try->add_default_value('Location', 					addslashes($user_details['location']));

			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['username']);
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
# Autogenerated on : August 12, 2004, 1:41 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
