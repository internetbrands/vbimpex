<?php
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
* vb_36_007 Import Post module
*
* @package			ImpEx.vb_36
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb_36_007 extends vb_36_000
{
	var $_dependent 	= '006';

	function vb_36_007(&$displayobject)
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
		$post_object 			= new ImpExData($Db_target, $sessionobject, 'post');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		#$post_array	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page, 'post', 'postid');
		$post_array = $this->get_source_data($Db_source, $source_database_type, "{$source_table_prefix}post", 'postid', 0, $post_start_at, $post_per_page);


		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $post_array['count'] . " {$displayobject->phrases['posts']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $post_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " .  $post_array['lastid'] . "</p>");



		foreach ($post_array['data'] as $post_id => $details)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));

			// Mandatory
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $details['threadid']));
			$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $details['userid']));
			$try->set_value('mandatory', 'importthreadid',		$details['threadid']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'parentid',			$idcache->get_id('post', $details['parentid']));
			$try->set_value('nonmandatory', 'username',			$details['username']);
			$try->set_value('nonmandatory', 'title',			$details['title']);
			$try->set_value('nonmandatory', 'dateline',			$details['dateline']);
			$try->set_value('nonmandatory', 'pagetext',			$details['pagetext']);
			$try->set_value('nonmandatory', 'allowsmilie',		$details['allowsmilie']);
			$try->set_value('nonmandatory', 'showsignature',	$details['showsignature']);
			$try->set_value('nonmandatory', 'ipaddress',		$details['ipaddress']);
			$try->set_value('nonmandatory', 'iconid',			$details['iconid']);
			$try->set_value('nonmandatory', 'visible',			$details['visible']);
			$try->set_value('nonmandatory', 'attach',			$details['attach']);
			$try->set_value('nonmandatory', 'importpostid',		$details['postid']);

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
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','title'));
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

						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']} :-> " . $try->_failedon);
					}
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
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

				$sessionobject->add_error($Db_target, 'invalid', $class_num, $post_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End foreach


		// Check for page end
		if ($post_array['count'] == 0 OR $post_array['count'] < $post_per_page)
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
		}

		$sessionobject->set_session_var('poststartat', $post_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
