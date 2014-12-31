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
* fusetalk_005 Import Thread module
*
* @package			ImpEx.fusetalk
*
*/
class fusetalk_005 extends fusetalk_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Thread';


	function fusetalk_005()
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
			$displayobject->update_html($displayobject->make_input_code('Threads to import per cycle (must be greater than 1)','threadperpage', 2000));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat','0');
			$sessionobject->add_session_var('threaddone','0');
			$sessionobject->add_session_var('archivethreads', 'notdone');
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
		$thread_start_at		= $sessionobject->get_session_var('threadstartat');
		$thread_per_page		= $sessionobject->get_session_var('threadperpage');
		$class_num				= substr(get_class($this) , -3);
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$thread_object 			= new ImpExData($Db_target, $sessionobject, 'thread');
		$forum_ids_array 		= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if ($sessionobject->get_session_var('archivethreads') != 'done')
		{
			// Get an array of thread details
			$thread_array 	= $this->get_fusetalk_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);

			// Display count and pass time
			$displayobject->display_now('<h4>Importing ' . count($thread_array) . ' threads</h4><p><b>From</b> : ' . $thread_start_at . ' ::  <b>To</b> : ' . ($thread_start_at + count($thread_array)) . '</p>');

			foreach ($thread_array as $thread_id => $thread_details)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
				// Mandatory
				$try->set_value('mandatory', 'title',				$thread_details['title']);
				$try->set_value('mandatory', 'forumid',				$forum_ids_array["$thread_details[forumid]"]);
				$try->set_value('mandatory', 'importthreadid',		$thread_id);
				$try->set_value('mandatory', 'importforumid',		$thread_details['forumid']);

				// Non Mandatory
				$try->set_value('nonmandatory', 'open',				'1');
				$try->set_value('nonmandatory', 'replycount',		$thread_details['replycount']);
				$try->set_value('nonmandatory', 'postusername',		$thread_details['postusername']);
				$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $thread_details['userid']));
				$try->set_value('nonmandatory', 'lastposter',		$thread_details['lastposter']);
				$try->set_value('nonmandatory', 'dateline',			strtotime($thread_details['dateline']));
				$try->set_value('nonmandatory', 'views',			$thread_details['views']);
				$try->set_value('nonmandatory', 'notes',			$thread_details['description']);
				$try->set_value('nonmandatory', 'visible',			'1');

				// Check if thread object is valid
				if($try->is_valid())
				{
					if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: thread -> ' . $thread_details['title']);
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

			if (count($thread_array) == 0 OR count($thread_array) < $thread_per_page)
			{
				$sessionobject->set_session_var('archivethreads', 'done');
				$sessionobject->set_session_var('threadstartat', 0);
			}
			else
			{
				$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
			}
		}
		else
		{
			// Get an array of thread details
			$thread_array 	= $this->get_fusetalk_archive_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);
			$user_name_array = $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			// Display count and pass time
			$displayobject->display_now('<h4>Importing ' . count($thread_array) . ' archive threads</h4><p><b>From</b> : ' . $thread_start_at . ' ::  <b>To</b> : ' . ($thread_start_at + count($thread_array)) . '</p>');

			foreach ($thread_array as $thread_id => $thread_details)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
				// Mandatory
				$try->set_value('mandatory', 'title',				$thread_details['title']);
				$try->set_value('mandatory', 'forumid',				$forum_ids_array["$thread_details[forumid]"]);
				$try->set_value('mandatory', 'importthreadid',		$thread_details['threadid']);
				$try->set_value('mandatory', 'importforumid',		$thread_details['forumid']);

				// Non Mandatory
				$try->set_value('nonmandatory', 'open',				'1');
				$try->set_value('nonmandatory', 'replycount',		$thread_details['replycount']);
				$try->set_value('nonmandatory', 'postusername',		$idcache->get_id('username', $thread_details['userid']));
				$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $thread_details['userid']));
				$try->set_value('nonmandatory', 'dateline',			strtotime($thread_details['dateline']));
				$try->set_value('nonmandatory', 'views',			$thread_details['views']);
				$try->set_value('nonmandatory', 'visible',			'1');


				// Check if thread object is valid
				if($try->is_valid())
				{
					if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: thread -> ' . $thread_details['title']);
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
				$sessionobject->set_session_var('import_post','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
			}

			$sessionobject->set_session_var('threadstartat', $thread_id);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : November 3, 2004, 3:01 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
