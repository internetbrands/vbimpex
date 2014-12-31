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
* punbb_007 Import Moderator module
*
* @package			ImpEx.punbb
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class punbb_008 extends punbb_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Moderator';


	function punbb_008()
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
		$moderator_array 	= $this->get_punbb_moderator_details($Db_source, $source_database_type, $source_table_prefix, $moderator_start_at, $moderator_per_page);


		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $target_database_type, $target_table_prefix, $importuserid);
		#$this->get_one_username($Db_target, $target_database_type, $target_table_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $databasetype, $tableprefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $target_database_type, $target_table_prefix);
		#$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix, $do_int_val = false);
		$user_name_array = $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);
		// Groups info
		#$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		#$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $target_database_type, $target_table_prefix);
		#$bannded_groupid = $this->get_banned_group($Db_target, $target_database_type, $target_table_prefix);
		// Thread info
		#$this->get_thread_id($Db_target, $target_database_type, $target_table_prefix, &$importthreadid, &$forumid); // & left to show refrence
		#$thread_ids_array = $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
		// Post info
		#$this->get_posts_ids($Db_target, $target_database_type, $target_table_prefix);
		#$this->get_vb_post_id($Db_target, $target_database_type, $target_table_prefix, $import_post_id);
		// Category info
		#$cat_ids_array = $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
		#$style_ids_array = $this->get_style_ids($Db_target, $target_database_type, $target_table_prefix, $pad=0);
		// Forum info
		$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix, $pad=0);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($moderator_array) . ' Forums\'s moderators</h4><p><b>From</b> : ' . $moderator_start_at . ' ::  <b>To</b> : ' . ($moderator_start_at + count($moderator_array)) . '</p>');


		$moderator_object = new ImpExData($Db_target, $sessionobject, 'moderator');


		foreach ($moderator_array as $moderator_id => $moderator_details)
		{
			
			$moo = unserialize($moderator_details['moderators']);
			
			if (!is_array($moo))
			{
				continue;
			}
			
			foreach($moo AS $username => $forumid)
			{
				
				$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));
				// Mandatory
				$try->set_value('mandatory', 'userid',			$user_name_array[$username]);
				$try->set_value('mandatory', 'forumid',			$forum_ids_array[$forumid]);
				$try->set_value('mandatory', 'importmoderatorid',	$moderator_id);
	
	
				// Non Mandatory
				#$try->set_value('nonmandatory', 'permissions',		$moderator_details['permissions']);
	
	
				// Check if moderator object is valid
				if($try->is_valid())
				{
					if($try->import_moderator($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: moderator -> ' . $username);
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
			}
		}// End foreach

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
# Autogenerated on : December 9, 2004, 2:32 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

