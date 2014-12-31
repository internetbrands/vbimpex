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
* yahoo_access_005 Import Post module
*
* @package			ImpEx.yahoo_access
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class yahoo_access_005 extends yahoo_access_000
{
	var $_dependent 	= '004';


	function yahoo_access_005(&$displayobject)
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
					$displayobject->display_now("<h4>{$displayobject->phrases['posts_cleared']}</h4>");
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
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'],'postperpage', 2000));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat','0');
			$sessionobject->add_session_var('currentforum','none');
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
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$current_forum = $sessionobject->get_session_var('currentforum');
		$current_forum = $this->get_yahoo_access_nextforum($Db_source, $source_database_type, $source_table_prefix, $current_forum);

		// Get an array of post details
		$post_array = $this->get_yahoo_access_post_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page, $current_forum);

		$email_to_ids = $this->get_email_to_ids($Db_target, $target_database_type, $target_table_prefix);


		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($post_array) . " {$displayobject->phrases['posts']} - {$current_forum}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $post_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($post_start_at + count($post_array)) . "</p>");


		$post_object = new ImpExData($Db_target, $sessionobject, 'post');


		foreach ($post_array as $post_id => $post_details)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));

			$thread_id = $this->get_yahoo_access_get_threadid($Db_target, $target_database_type, $target_table_prefix, $post_details['SubjectSrt']);

			if (!$thread_id)
			{
				// Didn't get the threadid match
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now('<br /><b>Did not find thread parent</b>');
			}

			// Mandatory
			$try->set_value('mandatory', 'threadid',			$thread_id);
			$try->set_value('mandatory', 'importthreadid',		'1');
			if($email_to_ids["$post_details[FromEmail]"]['userid'])
			{
				$try->set_value('mandatory', 'userid',				$email_to_ids["$post_details[FromEmail]"]['userid']);
			}
			else
			{
				$try->set_value('mandatory', 'userid',				'0');
			}

			// Non Mandatory
			$try->set_value('nonmandatory', 'importpostid',		$post_id);
			$try->set_value('nonmandatory', 'visible',			'1');
			$try->set_value('nonmandatory', 'showsignature',	'1');
			$try->set_value('nonmandatory', 'allowsmilie',		'1');
			$try->set_value('nonmandatory', 'pagetext',			$this->yahoo_access_html($this->html_2_bb($post_details['Message'])));
			$try->set_value('nonmandatory', 'dateline',			strtotime($post_details['RecDate']));
			$try->set_value('nonmandatory', 'title',			$post_details['Subject']);
			$try->set_value('nonmandatory', 'username',			$post_details['From']);
			$try->set_value('nonmandatory', 'parentid',			'0');


			// Check if post object is valid
			if($try->is_valid())
			{
				if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span>' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
					$displayobject->display_now("<br />{$impex_phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$impex_phrases['invalid_object']}" . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($post_array) == 0 OR count($post_array) < $post_per_page)
		{
			if ($current_forum == 'finished')
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');


				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));


				$sessionobject->set_session_var($class_num ,'FINISHED');
				$sessionobject->set_session_var('import_post','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
			else
			{
				$sessionobject->set_session_var('currentforum', $current_forum);
				$sessionobject->set_session_var('poststartat', '0');
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
		}
		else
		{
			$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}// End resume
}//End Class
# Autogenerated on : November 11, 2005, 2:04 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
