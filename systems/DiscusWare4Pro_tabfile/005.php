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
* DiscusWare4Pro_tabfile_005Import Thread module
*
* @package			ImpEx.DiscusWare4Pro_tabfile
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class DiscusWare4Pro_tabfile_005 extends DiscusWare4Pro_tabfile_000
{
	var $_dependent 	= '004';


	function DiscusWare4Pro_tabfile_005($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_post'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				// Note, you have to start back from forums
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['post_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['post_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_post']);
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
			$sessionobject->add_session_var('pointer_position', '0');
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

	function resume($sessionobject, $displayobject, $Db_target, $Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');

		$messagesspath			= $sessionobject->get_session_var('messagesspath');

		$pointerposition		= $sessionobject->get_session_var('pointer_position');

		// Per page vars
		$thread_per_page			= $sessionobject->get_session_var('threadperpage');
		$class_num				= substr(get_class($this) , -3);
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		
		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of post details
		$posts_array = $this->get_DiscusWare4Pro_tabfile_post_details($messagesspath, $thread_per_page, $pointerposition);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} {$displayobject->phrases['posts']}</h4>");

		$post_object 	= new ImpExData($Db_target, $sessionobject, 'post');

		$thread_ids				= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);

		foreach ($posts_array as $post_id => $post)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));

			// Mandatory
			if ($thread_ids["$post[thread]"])
			{
				$try->set_value('mandatory', 'threadid', 		$thread_ids["$post[thread]"]);
			}
			else
			{
				continue;
			}
		
			$try->set_value('mandatory', 'userid', 				$idcache->get_id('usernametoid', $post['username']));
			$try->set_value('mandatory', 'importthreadid', 		$post['thread']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'visible', 			'1');
			$try->set_value('nonmandatory', 'dateline',			$post['timestamp']);
			$try->set_value('nonmandatory', 'allowsmilie', 		'1');
			$try->set_value('nonmandatory', 'showsignature', 	'1');
			$try->set_value('nonmandatory', 'username', 		$post['username']);

			$try->set_value('nonmandatory', 'title', 			substr($this->DiscusWare4Pro_tabfile_html($this->html_2_bb($post['posttext'])), 0, 30));
			$try->set_value('nonmandatory', 'pagetext', 		$this->DiscusWare4Pro_tabfile_html($this->html_2_bb($post['posttext'])));
			$try->set_value('nonmandatory', 'importpostid',		$post_id);

			if($try->is_valid())
			{
				if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// foreach

		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		$sessionobject->set_session_var('pointer_position', $posts_array['position']);


		if ($posts_array['position'] == 'finished')
		{
			if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now($displayobject->phrases['successful']);
			}
			else
			{
				$displayobject->display_now($displayobject->phrases['failed']);
			}

			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
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
