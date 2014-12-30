<?php
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
* vb_36_006 Import Thread module
*
* @package			ImpEx.vb_36
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb_36_006 extends vb_36_000
{
	var $_dependent 	= '005';

	function vb_36_006(&$displayobject)
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
			$displayobject->update_basic('title', $displayobject->phrases['import_thread']);
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$thread_start_at		= $sessionobject->get_session_var('threadstartat');
		$thread_per_page		= $sessionobject->get_session_var('threadperpage');
		$class_num				= substr(get_class($this) , -3);
		$thread_object 			= new ImpExData($Db_target, $sessionobject, 'thread');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}
		$thread_array = $this->get_source_data($Db_source, $source_database_type, "{$source_table_prefix}thread", 'threadid', 0, $thread_start_at, $thread_per_page);

		#$thread_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page, 'thread', 'threadid');

		$forum_ids = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $thread_array['count'] . " {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $thread_array['lastid'] . "</p>");

		foreach ($thread_array['data'] as $thread_id => $thread_details)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
			// Mandatory
			$try->set_value('mandatory', 'importforumid',	$thread_details['forumid']);
			$try->set_value('mandatory', 'title',			$thread_details['title']);
			$try->set_value('mandatory', 'importthreadid',	$thread_id);
			$try->set_value('mandatory', 'forumid',			$forum_ids["$thread_details[forumid]"]);

			// Non Mandatory
			$try->set_value('nonmandatory', 'notes',		$thread_details['notes']);
			$try->set_value('nonmandatory', 'visible',		$thread_details['visible']);
			$try->set_value('nonmandatory', 'sticky',		$thread_details['sticky']);
			$try->set_value('nonmandatory', 'votenum',		$thread_details['votenum']);
			$try->set_value('nonmandatory', 'votetotal',	$thread_details['votetotal']);
			$try->set_value('nonmandatory', 'attach',		$thread_details['attach']);
			$try->set_value('nonmandatory', 'similar',		$thread_details['similar']);
			$try->set_value('nonmandatory', 'hiddencount',	$thread_details['hiddencount']);
			$try->set_value('nonmandatory', 'iconid',		$thread_details['iconid']);
			$try->set_value('nonmandatory', 'views',		$thread_details['views']);
			$try->set_value('nonmandatory', 'firstpostid',	$thread_details['firstpostid']);
			$try->set_value('nonmandatory', 'lastpost',		$thread_details['lastpost']);
			$try->set_value('nonmandatory', 'pollid',		$thread_details['pollid']);
			$try->set_value('nonmandatory', 'open',			$thread_details['open']);
			$try->set_value('nonmandatory', 'replycount',	$thread_details['replycount']);
			$try->set_value('nonmandatory', 'postusername',	$thread_details['postusername']);
			if ($thread_details['postuserid'] == 0)
			{
				$try->set_value('nonmandatory', 'postuserid',	0);
			}
			else
			{
				$try->set_value('nonmandatory', 'postuserid',	$idcache->get_id('user', $thread_details['postuserid']));
			}
			$try->set_value('nonmandatory', 'lastposter',	$thread_details['lastposter']);
			$try->set_value('nonmandatory', 'dateline',		$thread_details['dateline']);
			$try->set_value('nonmandatory', 'deletedcount',	$thread_details['dateline']);

			// Check if thread object is valid
			if($try->is_valid())
			{
				if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
					}

					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					if(shortoutput)
					{
						$displayobject->display_now('X');
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $thread_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']} :-> " . $try->_failedon);
					}
				}
			}
			else
			{
				if(shortoutput)
				{
					$displayobject->display_now('D');
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $thread_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
			}
			unset($try);
		}// End foreach

		// Check for page end
		if ($thread_array['count'] == 0 OR $thread_array['count'] < $thread_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$this->update_poll_ids($Db_target, $target_database_type, $target_table_prefix);

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

		$sessionobject->set_session_var('threadstartat', $thread_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
