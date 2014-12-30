<?php if (!defined('IDIR')) { die; }
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
* vbzoom_005 Import Thread module
*
* @package			ImpEx.vbzoom
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vbzoom_005 extends vbzoom_000
{
	var $_dependent 	= '004';

	function vbzoom_005(&$displayobject)
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
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'],'threadperpage',500));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

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

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of thread details
		$thread_array 	= $this->get_vbzoom_thread_details($Db_source, $source_database_type, $source_table_prefix, $thread_start_at, $thread_per_page);

		$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$username_to_ids_array = $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($thread_array) . " {$displayobject->phrases['threads']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $thread_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($thread_start_at + count($thread_array)) . "</p>");

		$thread_object 	= new ImpExData($Db_target, $sessionobject, 'thread');
		$post_object 	= new ImpExData($Db_target, $sessionobject, 'post');
		
		foreach ($thread_array as $thread_id => $thread_details)
		{
			$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
			// Mandatory
			$try->set_value('mandatory', 'title',				$thread_details['SubjectTitle']);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array["$thread_details[MainID]"]);
			$try->set_value('mandatory', 'importthreadid',		$thread_id);
			$try->set_value('mandatory', 'importforumid',		$thread_details['MainID']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'visible',			$this->iif($thread_details['Hidden'], 0, 1));
			$try->set_value('nonmandatory', 'open',				$this->iif($thread_details['Locked'], 0, 1));
			$try->set_value('nonmandatory', 'views',			$thread_details['VisitorNo']);
			$try->set_value('nonmandatory', 'dateline',			strtotime($thread_details['SubjectDate'] . ' ' .$thread_details['SubjectTime']));
		

			// Check if thread object is valid
			if($try->is_valid())
			{
				if($vb_thread_id = $try->import_thread($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					
					// Have to do first post while we are here.
					$post = (phpversion() < '5' ? $post_object : clone($post_object));

					$post->set_value('mandatory', 'threadid',		$vb_thread_id);
					$post->set_value('mandatory', 'userid',			$username_to_ids_array["$thread_details[Writer]"]);
					$post->set_value('mandatory', 'importthreadid',	$thread_id);

					// Non Mandatory
					$post->set_value('nonmandatory', 'parentid',		'0');
					$post->set_value('nonmandatory', 'username',		$thread_details['Writer']);
					$post->set_value('nonmandatory', 'title',			$thread_details['SubjectTitle']);
					$post->set_value('nonmandatory', 'dateline',		strtotime($thread_details['SubjectDate'] . ' ' .$thread_details['SubjectTime']));
					$post->set_value('nonmandatory', 'pagetext',		$this->html_2_bb($thread_details['SubjectText']));
					$post->set_value('nonmandatory', 'allowsmilie',		'1');
					$post->set_value('nonmandatory', 'showsignature',	$thread_details['ShowSignature']);
					$post->set_value('nonmandatory', 'visible',			$this->iif($thread_details['Hidden'], 0, 1));
					$post->set_value('nonmandatory', 'importpostid',	$thread_id);
					
					if ($post->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $post->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $post->get_value('nonmandatory','username'));
					}
					else
					{
						$sessionobject->add_error($post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");		
					}
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
			unset($try, $post);
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
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		$sessionobject->set_session_var('threadstartat',$thread_start_at+$thread_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : April 12, 2005, 4:16 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
