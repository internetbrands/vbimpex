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
* ikon_mysql_010 Import Moderator module
*
* @package			ImpEx.ikon_mysql
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ikon_mysql_010 extends ikon_mysql_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Moderator';


	function ikon_mysql_010()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_moderators'))
				{
					$displayobject->display_now('<h4>Imported moderators have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_moderators','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Moderator');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_moderator','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Moderators to import per cycle (must be greater than 1)','moderatorperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('moderatorstartat','0');
			$sessionobject->add_session_var('moderatordone','0');
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
		$moderator_start_at			= $sessionobject->get_session_var('moderatorstartat');
		$moderator_per_page			= $sessionobject->get_session_var('moderatorperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of moderator details
		$moderator_array 	= $this->get_ikon_mysql_moderator_details($Db_source, $source_database_type, $source_table_prefix, $moderator_start_at, $moderator_per_page);


		// User info
		$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_name_array = $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		// Forum info
		$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($moderator_array) . ' moderators</h4><p><b>From</b> : ' . $moderator_start_at . ' ::  <b>To</b> : ' . ($moderator_start_at + count($moderator_array)) . '</p>');


		$moderator_object = new ImpExData($Db_target, $sessionobject, 'moderator');


		foreach ($moderator_array as $moderator_id => $moderator_details)
		{
			$user_id =  str_replace('-', '', $moderator_details['MEMBER_ID']);

			$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));

			$permissions = 0;

			# 'caneditposts'           => 1,
			if($moderator_details['EDIT_POST']) 				{ $permissions += 1; }

			# 'caneditthreads'         => 8,
			if($moderator_details['EDIT_POST']) 				{ $permissions += 8; }

			// EDIT_TOPIC

			// DELETE_TOPIC


			#'candeleteposts'         => 2,
			if($moderator_details['DELETE_POST']) 				{ $permissions += 2; }

			#'canviewips'             => 1024,
			if($moderator_details['VIEW_IP']) 					{ $permissions += 1024; }

			#'canopenclose'           => 4, // OPEN_TOPIC
			if($moderator_details['CLOSE_TOPIC']) 				{ $permissions += 4; }


			#'canmassmove'             => 256,
			if($moderator_details['MASS_MOVE']) 				{ $permissions += 1024; }

			#'canmassprune'             => 512,
			if($moderator_details['MASS_PRUNE']) 				{ $permissions += 512; }

			#'canremoveposts'             => 131072,	 // Not quite but close
			if($moderator_details['MOVE_TOPIC']) 				{ $permissions += 512; }

			/*
			PIN_TOPIC
			UNPIN_TOPIC
			POST_Q
			TOPIC_Q
			ALLOW_WARN
			ADD_TOPIC_WATCH
			REMOVE_TOPIC_WATCH
			'canmanagethreads'       => 16,
			'canannounce'            => 32,
			'canmoderateposts'       => 64,
			'canmoderateattachments' => 128,
			'canviewprofile'         => 2048,
			'canbanusers'            => 4096,
			'canunbanusers'          => 8192,
			'newthreademail'         => 16384,
			'newpostemail'           => 32768,
			'cansetpassword'         => 65536,
			'caneditsigs'            => 262144,
			'caneditavatar'          => 524288,
			'caneditpoll'            => 1048576,
			'caneditprofilepic'      => 2097152,
			'caneditreputation'      => 4194304
			*/

				// Mandatory
			$try->set_value('mandatory', 'userid',					$user_ids_array[$user_id]);
			$try->set_value('mandatory', 'forumid',					$forum_ids_array["$moderator_details[FORUM_ID]"]);
			$try->set_value('mandatory', 'importmoderatorid',		$moderator_id);


			// Non Mandatory
			$try->set_value('nonmandatory', 'permissions',			$permissions);


			// Check if moderator object is valid
			if($try->is_valid())
			{
				if($try->import_moderator($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: moderator -> ' . $user_name_array[$user_id]);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar moderator and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid moderator object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End resume


		// Check for page end
		if (count($moderator_array) == 0 OR count($moderator_array) < $moderator_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_moderator','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('moderatorstartat',$moderator_start_at+$moderator_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : May 27, 2004, 1:49 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
