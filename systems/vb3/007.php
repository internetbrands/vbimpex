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
* vb3 Import Avatars
*
* @package 		ImpEx.vb3
*
*/
class vb3_007 extends vb3_000
{
	var $_dependent 	= '004';

	function vb3_007(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_forum'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['forums_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['forum_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_forum']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['forums_per_page'],'forumperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			$sessionobject->add_session_var('forumstartat','0');
			$sessionobject->add_session_var('categoriesfinished','FALSE');
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

		$forum_start_at		= $sessionobject->get_session_var('forumstartat');
		$forum_per_page		= $sessionobject->get_session_var('forumperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		$forum_array 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at, $forum_per_page, 'forum', 'forumid');
		$users_ids 		= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

		$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($forum_array) . " {$displayobject->phrases['forums']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $forum_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($forum_start_at + count($forum_array)) . "</p>");

		foreach ($forum_array as $forum_id => $details)
		{
			$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
			// Nasty, but we need the parent ids
			$forum_ids = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

			// Mandatory
			$try->set_value('mandatory', 'title',				$details['title']);
			$try->set_value('mandatory', 'displayorder',		$details['displayorder']);

			if(!$forum_ids["$details[parentid]"])
			{
				$try->set_value('mandatory', 'parentid',		'-1');
			}
			else
			{
				$try->set_value('mandatory', 'parentid',		$forum_ids["$details[parentid]"]);
			}

			$try->set_value('mandatory', 'importforumid',		$forum_id);
			$try->set_value('mandatory', 'importcategoryid',	'0');
			$try->set_value('mandatory', 'options',				$details['options']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'styleid',			$details['styleid']);
			$try->set_value('nonmandatory', 'description',		addslashes($details['description']));
			$try->set_value('nonmandatory', 'replycount',		$details['replycount']);
			$try->set_value('nonmandatory', 'lastpost',			$details['lastpost']);
			$try->set_value('nonmandatory', 'lastposter',		addslashes($details['lastposter']));
			$try->set_value('nonmandatory', 'lastthread',		addslashes($details['lastthread']));
			$try->set_value('nonmandatory', 'lasticonid',		$details['lasticonid']);
			$try->set_value('nonmandatory', 'threadcount',		$details['threadcount']);
			$try->set_value('nonmandatory', 'daysprune',		$details['daysprune']);
			$try->set_value('nonmandatory', 'newpostemail',		$details['newpostemail']);
			$try->set_value('nonmandatory', 'newthreademail',	$details['newthreademail']);

			$old_list = explode(",",$details['parentlist']);
			foreach ($old_list as $key => $value)
			{
				$old_list[$key] = $forum_ids["$old_list[$key]"];
			}
			$new_list = implode(",",$old_list);

			$try->set_value('nonmandatory', 'parentlist',		$new_list);
			$try->set_value('nonmandatory', 'password',			$details['password']);
			$try->set_value('nonmandatory', 'link',				$details['link']);

			// Can't get the id's for things that haven't been imported yet, will need to clean up afterwards
			#$try->set_value('nonmandatory', 'childlist',		$details['childlist']);
			#$try->set_value('nonmandatory', 'lastthreadid',		$details['lastthreadid']);


			if($try->is_valid())
			{
				if($try->import_forum($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $try->get_value('mandatory', 'title'));
					$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($try->get_value('mandatory', 'importforumid'), $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}


		if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
		{
			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num ,'_time_taken'),
				$sessionobject->return_stats($class_num ,'_objects_done'),
				$sessionobject->return_stats($class_num ,'_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}
	}
}
/*======================================================================*/
?>

