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
* ubb_threads_007 Import Post module
*
* @package			ImpEx.ubb_threads
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ubb_threads_007 extends ubb_threads_000
{
	var $_dependent 	= '006';


	function ubb_threads_007(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_post'];
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['post_restart_ok']}</h4>");
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
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_post']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'],'postperpage',2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
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
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$class_num				= substr(get_class($this) , -3);

		// Clone and cache
		$post_object 			= new ImpExData($Db_target, $sessionobject, 'post');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of post details
		$post_array 	= $this->get_ubb_threads_post_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page);

		// Check for page end
		if ($source_database_type == 'mysql')
		{
			$count_number = count($post_array['data']);
		}
		else
		{
			$count_number = count($post_array);
		}
		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} {$count_number} {$displayobject->phrases['posts']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $post_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($post_start_at + $count_number) . "</p>");



		foreach ($post_array['data'] as $post_id => $post_details)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));
			// Mandatory
			$try->set_value('mandatory', 'userid',			$idcache->get_id('user', $post_details['B_PosterId']));
			$try->set_value('mandatory', 'importthreadid',	$post_details['B_Main']);
			$try->set_value('mandatory', 'threadid',	 	$idcache->get_id('thread', $post_details['B_Main']));

			// Non Mandatory
			$try->set_value('nonmandatory', 'parentid',	 	$idcache->get_id('post', $post_details['B_Parent']));
			$try->set_value('nonmandatory', 'username',		$idcache->get_id('username', $post_details['B_PosterId']));
			$try->set_value('nonmandatory', 'title',		addslashes($this->html_2_bb($post_details['B_Subject'])));
			$try->set_value('nonmandatory', 'dateline',		$post_details['B_Posted']);
			$try->set_value('nonmandatory', 'pagetext',		$this->ubb_threads_html_2_bb($this->html_2_bb($post_details['B_Body'])));
			$try->set_value('nonmandatory', 'ipaddress',	$post_details['B_IP']);
			$try->set_value('nonmandatory', 'visible',		'1');
			$try->set_value('nonmandatory', 'allowsmilie',	'1');
			$try->set_value('nonmandatory', 'importpostid',	$post_details['B_Number']);


			// Check if post object is valid
			if($try->is_valid())
			{
				if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','username'));
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
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
					}

					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $post_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				}
			}
			else
			{
				if(shortoutput)
				{
					$displayobject->display_now('X');
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End resume


		$sessionobject->add_session_var('threadcachesize', count($temp_threadid));
		$sessionobject->add_session_var('postcachesize', count($temp_parentid));
		#$sessionobject->add_session_var('memorymegs',  number_format((memory_get_usage() / 1024), 2, ',', ' '));

		// Check for page end
		if ($count_number == 0 OR $count_number < $post_per_page)
		{
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
		}


		$sessionobject->set_session_var('poststartat',$post_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : May 17, 2004, 10:34 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
