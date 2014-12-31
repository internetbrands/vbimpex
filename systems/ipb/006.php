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
* ipb Import Threads
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ipb
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ipb_006 extends ipb_000
{
	var $_dependent 	= '005';

	function ipb_006(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_thread'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['threads_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['thread_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_thread']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'],'threadperpage', 2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');
			
			$thread_start_at 		= $sessionobject->get_session_var('threadstartat');
			$threads_per_page 		= $sessionobject->get_session_var('threadperpage');

			$class_num				= substr(get_class($this) , -3);
			$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

			// Start at and timing
			if(!$sessionobject->get_session_var('threadperpage'))
			{
				$threads_per_page = 0;
			}

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			// Get all the first messagese that make up the beginning of the threads in that forum.
			#$threads_array			= $this->get_ipb_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $threads_per_page);
			$threads_array = $this->get_source_data($Db_source, $source_database_type, "{$source_table_prefix}topics", 'tid', 0, $thread_start_at, $threads_per_page);
			$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		#	$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		#	$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);


			// Sort out the threads
			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $threads_array['count'] . " {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($thread_start_at + $threads_array['count']) . "</p>");

			$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

			foreach ($threads_array['data'] as $thread_id => $thread)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

				$try->set_value('mandatory', 'title', 			$thread['title']);
				$try->set_value('mandatory', 'forumid', 		$forum_ids[$thread['forum_id']]);
				$try->set_value('mandatory', 'importthreadid', 	$thread_id);
				$try->set_value('mandatory', 'importforumid', 	$thread['forum_id']);
				
				$try->set_value('nonmandatory', 'notes', 		$thread['description']);
				$try->set_value('nonmandatory', 'replycount', 	$thread['posts']);
				$try->set_value('nonmandatory', 'postusername',	$idcache->get_id('username', $thread['starter_id']));
				$try->set_value('nonmandatory', 'postuserid', 	$idcache->get_id('user', $thread['starter_id']));
				$try->set_value('nonmandatory', 'dateline', 	$thread['start_date']);
				$try->set_value('nonmandatory', 'lastpost', 	$thread['last_poster_name']);
				$try->set_value('nonmandatory', 'views', 		$thread['views']);
				$try->set_value('nonmandatory', 'sticky', 		$thread['pinned']);
				$try->set_value('nonmandatory', 'votetotal', 	$thread['total_votes']);

				if ($thread['state'] == 'open')
				{
					$try->set_value('nonmandatory', 'open', 	'1');
				}
				else if ($thread['state'] == 'closed')
				{
					$try->set_value('nonmandatory', 'open', 	'0');
				}/*
				else if ($thread['state'] == 'link')
				{	// What to do ?
					$try->set_value('nonmandatory', 'open', 	'');
				}
				*/
				$try->set_value('nonmandatory', 'visible', '1');



				if($try->is_valid())
				{
					if($try->import_thread($Db_target,$target_database_type,$target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($thread_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}

			if ($threads_array['count'] == 0 OR $threads_array['count'] < $threads_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('threadstartat','0');
			}

		$sessionobject->set_session_var('threadstartat', $threads_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
