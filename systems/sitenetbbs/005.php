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
* sitenetbbs_005 Import Thread module
*
* @package			ImpEx.sitenetbbs
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class sitenetbbs_005 extends sitenetbbs_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Thread';


	function sitenetbbs_005()
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

			$sessionobject->add_session_var('path_to_forum_file',	'starting');
			$sessionobject->add_session_var('current_cat', 			'');
			$sessionobject->add_session_var('current_forum',		'');
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
		$class_num					= substr(get_class($this) , -3);
		$path 						= $sessionobject->get_session_var('admindata');
		$idcache 					= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Thread path finding stuff
		$current_cat			= $sessionobject->get_session_var('current_cat');
		$current_forum			= $sessionobject->get_session_var('current_forum');
		$path_to_forum_file		= $sessionobject->get_session_var('path_to_forum_file');

		if ($path_to_forum_file == 'starting')
		{
			$details = $this->get_next_forum($Db_target, $target_database_type, $target_table_prefix, $current_cat, $current_forum);

			$sessionobject->add_session_var('path_to_forum_file',	$path_to_forum_file = $details['path']);
			$sessionobject->add_session_var('current_cat', 			$current_cat = $details['current_cat']);
			$sessionobject->add_session_var('current_forum',		$current_forum = $details['current_forum']);
		}

		// Get an array of thread details
		$thread_array 	= $this->get_sitenetbbs_thread_details($path . '/' . $path_to_forum_file,  $thread_start_at, $thread_per_page);

		$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($thread_array) . ' threads</h4><p><b>From</b> : ' . $thread_start_at . ' ::  <b>To</b> : ' . ($thread_start_at + count($thread_array)) . " from {$current_cat}::{$current_forum}</p>");

		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

		foreach ($thread_array as $thread_id => $thread_details)
		{
			/*
				[2682] => Array
				(
					[0] => 2682
					[1] => Dolphin
					[2] => Dolphin6900@yahoo.com
					[3] => Newbie here
					[4] => cheers.gif
					[5] =>
					[6] => 1
					[7] => 1103145051
					[8] => Me
					[9] =>
					[10] =>

				)
			*/
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
			// Mandatory
			$try->set_value('mandatory', 'importforumid',		$current_forum);
			$try->set_value('mandatory', 'title',				$thread_details[3]);
			$try->set_value('mandatory', 'importthreadid',		$thread_details[0]);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array[$current_forum]);

			// Non Mandatory
			$try->set_value('nonmandatory', 'notes',			"{$current_cat}/{$current_forum}/{$thread_details[0]}");
			$try->set_value('nonmandatory', 'visible',			'1');
			$try->set_value('nonmandatory', 'sticky',			'0');
			$try->set_value('nonmandatory', 'views',			'0');
			$try->set_value('nonmandatory', 'dateline',			$thread_details[7]);
			$try->set_value('nonmandatory', 'open',				'1');
			$try->set_value('nonmandatory', 'postusername',		$thread_details[1]);
			$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('usernametoid', $thread_details[1]));

			// Check if thread object is valid
			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: thread -> ' . $thread_details[3]);
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
			if($details = $this->get_next_forum($Db_target, $databasetype, $tableprefix, $current_cat, $current_forum))
			{
				$sessionobject->set_session_var('path_to_forum_file',	$details['path']);
				$sessionobject->set_session_var('current_cat', 			$details['current_cat']);
				$sessionobject->set_session_var('current_forum',		$details['current_forum']);
				$sessionobject->set_session_var('threadstartat',		'0');
			}
			else
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
			}
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
		else
		{
			$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}// End resume
}//End Class
# Autogenerated on : June 13, 2005, 1:57 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
