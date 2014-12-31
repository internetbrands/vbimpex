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
* DiscusWare4Pro_tabfile_004 Import Thread module
*
* @package			ImpEx.DiscusWare4Pro_tabfile
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class DiscusWare4Pro_tabfile_004 extends DiscusWare4Pro_tabfile_000
{
	var $_dependent 	= '003';


	function DiscusWare4Pro_tabfile_004(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_thread'];
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				// Note, you have to start back from forums
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
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'],'threadperpage',250));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat','0');
			$sessionobject->add_session_var('currentforum', '0');
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

		$messagesspath			= $sessionobject->get_session_var('messagesspath');
		$adminpath				= $sessionobject->get_session_var('adminpath');
		$currentforum			= $sessionobject->get_session_var('currentforum');

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
		$threads_file = $this->get_DiscusWare4Pro_next_forum($Db_target, $target_database_type, $target_table_prefix, $currentforum);

		if ($threads_file != 'finished')
		{
			$thread_array = $this->get_DiscusWare4Pro_tabfile_thread_details($messagesspath, $thread_per_page, $adminpath, $threads_file);

			$username_to_ids 		= $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_group_ids_array 	= $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
			$cat_ids 				=  $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

			// Display count and pass time
			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($thread_array['forums']) . " {$displayobject->phrases['forums']}</h4>");

			$forum_object 	= new ImpExData($Db_target, $sessionobject, 'forum');
			$thread_object 	= new ImpExData($Db_target, $sessionobject, 'thread');

			//
			// Do the sub forums first
			//
			foreach ($thread_array['forums'] as $forum_id => $forum)
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				if($cat_ids["$forum[parentid]"])
				{
					$try->set_value('mandatory', 'parentid',		$cat_ids["$forum[parentid]"]);
				}
				else
				{
					$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
					$try->set_value('mandatory', 'parentid',		$forum_ids_array["$forum[parentid]"]);
				}

				$try->set_value('mandatory', 'title', 				$forum['title']);
				$try->set_value('mandatory', 'displayorder',		'1');
				$try->set_value('mandatory', 'importforumid',		$forum['id']);
				$try->set_value('mandatory', 'importcategoryid',	'0');
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$try->set_value('nonmandatory', 'description', 		$forum['title']);
				$try->set_value('nonmandatory', 'visible', 			'1');

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

			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($thread_array['threads']) . " {$displayobject->phrases['threads']}</h4>");

			$forum_ids = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

			//
			// Do all the threads in that category
			//
			foreach ($thread_array['threads'] as $thread_id => $thread)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

				$try->set_value('mandatory', 'title', 			$thread['title']);

				if ($forum_ids["$thread[parentid]"])
				{
					$try->set_value('mandatory', 'forumid', 	$forum_ids["$thread[parentid]"]);
				}
				else if ($forum_ids["$thread[categoryid]"])
				{
					$try->set_value('mandatory', 'forumid', 	$forum_ids["$thread[categoryid]"]);
				}
				else
				{
					// Shouldn't get here.
					#die;
				}
				$try->set_value('mandatory', 'importthreadid', 	$thread_id);
				$try->set_value('mandatory', 'importforumid', 	$thread['parentid']);

				#$try->set_value('nonmandatory', 'firstpostid', 	$thread['topic_first_post_id']);
				#$try->set_value('nonmandatory', 'lastpost', 	$thread['topic_last_post_id']);
				#$try->set_value('nonmandatory', 'replycount', 	$thread['topic_replies']);
				$try->set_value('nonmandatory', 'postusername',	$thread['username']);
				$try->set_value('nonmandatory', 'visible', 		'1');
				$try->set_value('nonmandatory', 'open', 		'1');
				$try->set_value('nonmandatory', 'sticky',		'0');

				if($try->is_valid())
				{
					if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
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

			$sessionobject->add_session_var('currentforum', $threads_file);
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
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
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}// End resume
}//End Class
# Autogenerated on : October 23, 2005, 4:32 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
