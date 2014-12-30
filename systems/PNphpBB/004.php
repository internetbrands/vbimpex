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
* PNphpBB_004 Import User module
*
* @package			ImpEx.PNphpBB
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class PNphpBB_004 extends PNphpBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';


	function PNphpBB_004()
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
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the avatars, this can take some time if they are remotely linked","get_avatars",1));
			$displayobject->update_html($displayobject->make_input_code('What is the full path to your avatars directory ?','get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));
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
		$user_array 	= $this->get_PNphpBB_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');


		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			$old_group_ids = $this->get_PNphpBB_usergroupids($Db_source, $source_database_type, $source_table_prefix, $user_id);
			
			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$try->_auto_email_associate = true;
			}

			// Mandatory
			$try->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);
			
			if (count($old_group_ids))
			{
				$new_ids = array();
				
				foreach ($old_group_ids as $old_id)
				{
					if ($user_group_ids_array[$old_id])
					{
						$new_ids[] = $user_group_ids_array[$old_id];
					}
				}
				
				$try->set_value('nonmandatory', 'membergroupids', implode(',', $new_ids));
			}
			
			$try->set_value('mandatory', 'username',			$user_details['username']);
			$try->set_value('mandatory', 'email',				$user_details['user_email']);
			$try->set_value('mandatory', 'importuserid',		$user_id);

			$try->set_value('mandatory', 'username',			$user_details['username']);
			$try->set_value('mandatory', 'email',				$user_details['user_email']);
			$try->set_value('mandatory', 'importuserid',		$user_details['user_id']);

			$try->_password_md5_already = true;
			$try->set_value('nonmandatory', 'password', 		$user_details['user_password']);

			$try->set_value('nonmandatory', 'aim',				addslashes($user_details['user_aim']));
			$try->set_value('nonmandatory', 'icq',				addslashes($user_details['user_icq']));


			if(strpos($user_details['user_regdate'],','))
			{
				$try->set_value('nonmandatory', 'joindate',		strtotime($user_details['user_regdate']));
			}
			else
			{
				$try->set_value('nonmandatory', 'joindate',		$user_details['user_regdate']);
			}

			$try->set_value('nonmandatory', 'homepage',			addslashes($user_details['user_website']));
			$try->set_value('nonmandatory', 'lastactivity',		$user_details['user_lastvisit']);
			$try->set_value('nonmandatory', 'yahoo',			addslashes($user_details['user_yim']));
			$try->set_value('nonmandatory', 'msn',				addslashes($user_details['user_msnm']));
			$try->set_value('nonmandatory', 'posts',			$user_details['user_posts']);

			$try->add_default_value('Occupation',				$user_details['user_occ']);
			$try->add_default_value('Location', 				$user_details['user_from']);
			$try->add_default_value('Interests',				$user_details['user_interests']);

			$options = 1367;


			if($user_details['user_viewemail'])				{ $options += 256; }
			if($user_details['user_allow_viewonline'])		{ $options += 512; }

			$try->set_value('nonmandatory', 'options',		$options);

			if ($sessionobject->get_session_var('get_avatars') AND $user_details['user_avatar'] != NULL AND $user_details['user_avatar'] != '/')
			{
				if(strstr($user_details['user_avatar'],'http://'))
				{
					//Its a url
					$try->set_value('nonmandatory', 'avatar',trim($user_details['user_avatar']));
				}
				else
				{
					// Its going to be in the images/avatars/ dir somewhere hopefully.
					$ava_path = $sessionobject->get_session_var('get_avatars_path');

					if(substr($string,-1) == '/')
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . trim($user_details['user_avatar']));

					}
					else
					{
						$try->set_value('nonmandatory', 'avatar',$ava_path . '/' . trim($user_details['user_avatar']));

					}
				}
			}

			// If its not blank slash it and get it
			if($user_details['user_sig'] != '')
			{
				$try->add_default_value('signature', 	addslashes($this->pnphpbb_html($this->html_2_bb($user_details['user_sig']))));
			}

			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$imported = true;
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
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
# Autogenerated on : September 16, 2004, 12:14 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
