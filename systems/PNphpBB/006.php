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
* PNphpBB_006 Import Thread module
*
* @package			ImpEx.PNphpBB
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class PNphpBB_006 extends PNphpBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Thread';


	function PNphpBB_006()
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


		$threads_array 			= $this->get_PNphpBB_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);
		$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		// Sort out the threads
		$displayobject->display_now("<h4>Importing " . count($threads_array) . " threads</h4><p><b>From</b> : " . $thread_start_at . " ::  <b>To</b> : " . ($thread_start_at + count($threads_array)) ."</p>");

		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

		foreach ($threads_array as $thread_id => $thread)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

			$try->set_value('mandatory', 'title', 			$thread['topic_title']);
			$try->set_value('mandatory', 'forumid', 		$forum_ids[$thread['forum_id']]);
			$try->set_value('mandatory', 'importthreadid', 	$thread['topic_id']);
			$try->set_value('mandatory', 'importforumid', 	$thread['forum_id']);

			$try->set_value('nonmandatory', 'firstpostid', 	$thread['topic_first_post_id']);
			$try->set_value('nonmandatory', 'lastpost', 	$thread['topic_last_post_id']);
			$try->set_value('nonmandatory', 'replycount', 	$thread['topic_replies']);
			$try->set_value('nonmandatory', 'postusername',	$user_names[$thread['topic_poster']]);
			$try->set_value('nonmandatory', 'postuserid', 	$thread['topic_poster']);
			$try->set_value('nonmandatory', 'dateline', 	$thread['topic_time']);
			$try->set_value('nonmandatory', 'views', 		$thread['topic_views']);
			$try->set_value('nonmandatory', 'visible', 		'1');
			$try->set_value('nonmandatory', 'open', 		'1');

			if($thread['topic_status'] == 0)
			{
				$try->set_value('nonmandatory', 'open', '1');
			}

			if($thread['topic_status'] == 1)
			{
				$try->set_value('nonmandatory', 'open', '0');
			}

			/*
			topic_status
			topic_vote
			topic_type
			topic_moved_id

			$try->set_value('nonmandatory', 'pollid', );
			$try->set_value('nonmandatory', 'open', );
			$try->set_value('nonmandatory', 'lastposter', );
			$try->set_value('nonmandatory', 'iconid', );
			$try->set_value('nonmandatory', 'notes', );
			$try->set_value('nonmandatory', 'visible', );
			$try->set_value('nonmandatory', 'sticky', );
			$try->set_value('nonmandatory', 'votenum', );
			$try->set_value('nonmandatory', 'votetotal', );
			$try->set_value('nonmandatory', 'attach', );
			$try->set_value('nonmandatory', 'similar', );
			*/


			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$imported = true;
				}
				else
				{
					$sessionobject->add_error('warning',
											 $this->_modulestring,
											 get_class($this) . "::import_thread failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
											 'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$displayobject->display_now("<br />Got thread " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
			}
			unset($try);
		}

		// Check for page end
		if (count($threads_array) == 0 OR count($threads_array) < $thread_per_page)
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
# Autogenerated on : September 16, 2004, 12:14 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
