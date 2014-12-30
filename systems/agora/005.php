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
* agora_006 Import Threads module
*
* @package			ImpEx.agora
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class agora_005 extends agora_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Threads';


	function agora_005()
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
											 get_class($this) . '::restart failed , clear_imported_theads','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Threads');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_post','working'));
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
			$sessionobject->add_session_var('forum_table','start');
			$sessionobject->add_session_var('forum_end','true');
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
		$forum_table			= $sessionobject->get_session_var('forum_table');

		// Get the forum table name or end
		if ($sessionobject->get_session_var('forum_end') == 'true')
		{
			$forum_table = $this->get_next_agora_forum($Db_source, $source_database_type, $source_table_prefix, $sessionobject->get_session_var('forum_table'));
			$sessionobject->add_session_var('forum_end', 'false');
			$sessionobject->add_session_var('forum_table', $forum_table);
			$sessionobject->set_session_var('threadstartat', 0);
		}

		if (!$forum_table)
		{
			$forum_table = 'end';
		}
		
		// Per page vars
		$thread_start_at = $sessionobject->get_session_var('threadstartat');
		$thread_per_page = $sessionobject->get_session_var('threadperpage');
		$class_num		 = substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}
			
		if($forum_table != 'end')
		{
			// Get an array of post details
			$thread_array		= $this->get_agora_thread_details($Db_source, $source_database_type, $source_table_prefix, $forum_table, $thread_start_at, $thread_per_page);
			
			$user_name_array 	= $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);
			$import_forum_id 	= $this->get_agora_import_forumid($Db_source, $source_database_type, $source_table_prefix, $forum_table);
			$forum_ids_array	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			// Display count and pass time
			$displayobject->display_now('<h4>Importing ' . count($thread_array) . ' threads from ' . $forum_table . '</h4><p><b>From</b> : ' . $thread_start_at . ' ::  <b>To</b> : ' . ($thread_start_at + count($thread_array)) . '</p>');


			$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

			foreach ($thread_array as $thread_id => $thread_details)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
				// Mandatory
				$try->set_value('mandatory', 'title',				$thread_details['subject']);
				$try->set_value('mandatory', 'forumid',				$forum_ids_array[$import_forum_id]);
				$try->set_value('mandatory', 'importthreadid',		$thread_details['thread']);
				$try->set_value('mandatory', 'importforumid',		$import_forum_id);
	
	
				// Non Mandatory
	
				$try->set_value('nonmandatory', 'postuserid',		$user_name_array["$thread_details[userid]"]);
				$try->set_value('nonmandatory', 'dateline',			$thread_details['unixdate']);
				$try->set_value('nonmandatory', 'views',			$thread_details['hits']);
				$try->set_value('nonmandatory', 'postusername',		$thread_details['userid']);
				$try->set_value('nonmandatory', 'notes',			$this->html_2_bb($thread_details['summary']));
	
				$try->set_value('nonmandatory', 'open',				$this->iif($thread_details['closed'],0,1));
				$try->set_value('nonmandatory', 'visible',			$this->iif($thread_details['hidden'],0,1));
	
				/*
				$try->set_value('nonmandatory', 'firstpostid',		$thread_details['firstpostid']);
				$try->set_value('nonmandatory', 'lastpost',			$thread_details['lastpost']);
				$try->set_value('nonmandatory', 'pollid',			$thread_details['pollid']);
				$try->set_value('nonmandatory', 'replycount',		$thread_details['replycount']);
				$try->set_value('nonmandatory', 'lastposter',		$thread_details['lastposter']);
				$try->set_value('nonmandatory', 'iconid',			$thread_details['iconid']);
				$try->set_value('nonmandatory', 'sticky',			$thread_details['sticky']);
				$try->set_value('nonmandatory', 'votenum',			$thread_details['votenum']);
				$try->set_value('nonmandatory', 'votetotal',		$thread_details['votetotal']);
				$try->set_value('nonmandatory', 'attach',			$thread_details['attach']);
				$try->set_value('nonmandatory', 'similar',			$thread_details['similar']);
				*/
	
				// Check if thread object is valid
				if($try->is_valid())
				{
					if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: thread -> ' . $thread_details['subject']);
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

				$sessionobject->set_session_var('threadstartat', 0);

				$sessionobject->add_session_var('forum_end','true');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}


			$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
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

			$sessionobject->set_session_var('threadstartat', 0);
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('threads','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
	}// End resume
}//End Class
# Autogenerated on : February 24, 2005, 1:57 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
