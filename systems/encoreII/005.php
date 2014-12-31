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
* encoreII_005 Import Thread module
*
* @package			ImpEx.encoreII
*
*/
class encoreII_005 extends encoreII_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Thread';


	function encoreII_005()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads'))
				{
					$displayobject->display_now('<h4>Imported threads have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_threads','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Thread');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_thread','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Threads to import per cycle (must be greater than 1)','threadperpage',500));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat','0');
			$sessionobject->add_session_var('threaddone','0');
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
		$thread_start_at			= $sessionobject->get_session_var('threadstartat');
		$thread_per_page			= $sessionobject->get_session_var('threadperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of thread details
		$thread_array 	= $this->get_encoreII_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);


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
		$displayobject->display_now('<h4>Importing ' . count($thread_array) . ' threads</h4><p><b>From</b> : ' . $thread_start_at . ' ::  <b>To</b> : ' . ($thread_start_at + count($thread_array)) . '</p>');


		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');


		foreach ($thread_array as $thread_id => $thread_details)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
			// Mandatory
			$try->set_value('mandatory', 'title',				$thread_details['Title']);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array[intval($thread_details['TopicID'])+1]);
			$try->set_value('mandatory', 'importthreadid',		$thread_details['ID']+1);
			$try->set_value('mandatory', 'importforumid',		$thread_details['TopicID']+1);


			// Non Mandatory
			#$try->set_value('nonmandatory', 'firstpostid',		$thread_details['firstpostid']);
			#$try->set_value('nonmandatory', 'lastpost',		$thread_details['lastpost']);
			#$try->set_value('nonmandatory', 'pollid',			$thread_details['pollid']);
			$try->set_value('nonmandatory', 'open',				$this->iif($thread_details['Locked'],0,1));
			$try->set_value('nonmandatory', 'replycount',		$thread_details['NumOfPosts']);
			$try->set_value('nonmandatory', 'postusername',		$thread_details['RegUser']);
			$try->set_value('nonmandatory', 'postuserid',		$user_name_array["$thread_details[RegUser]"]);
			$try->set_value('nonmandatory', 'lastposter',		$thread_details['UserLastPost']);
			$try->set_value('nonmandatory', 'dateline',			$thread_details['DatePosted']);
			$try->set_value('nonmandatory', 'views',			$thread_details['Views']);
			#$try->set_value('nonmandatory', 'iconid',			$thread_details['iconid']);
			$try->set_value('nonmandatory', 'notes',			$thread_details['PrivateNotes'] . $thread_details['PublicNotes']);
			$try->set_value('nonmandatory', 'visible',			'1');
			$try->set_value('nonmandatory', 'sticky',			$thread_details['Persistent']);
			#$try->set_value('nonmandatory', 'votenum',			$thread_details['votenum']);
			#$try->set_value('nonmandatory', 'votetotal',		$thread_details['votetotal']);
			#$try->set_value('nonmandatory', 'attach',			$thread_details['attach']);
			#$try->set_value('nonmandatory', 'similar',			$thread_details['similar']);


			// Check if thread object is valid
			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: thread -> ' . $thread_details['Title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar thread and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid thread object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($thread_array) == 0 OR count($thread_array) < $thread_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');




			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_thread','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : February 1, 2005, 8:41 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
