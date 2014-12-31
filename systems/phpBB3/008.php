<?php
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.phpBB3
*
*/

class phpBB3_008 extends phpBB3_000
{
	var $_dependent = '007';

	function phpBB3_008(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_post'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_posts'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['posts_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['post_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_post']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("007_objects_done", '0');
			$sessionobject->add_session_var("007_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);
		$idcache 		= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);
		$ImpExData		= new ImpExData($Db_target, $sessionobject, 'post');
		$truncated_smilies	= $this->get_phpbb_truncated_smilies($Db_source, $s_db_type, $t_tb_prefix);
		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		if ($s_db_type == 'mysql')
		{
			$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}posts", 'post_id', 0, $start_at, $per_page);
		}
		else 
		{
			$data_array = $this->get_phpbb3_posts($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);
		}
		
		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['posts'], $start_at);


		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData : clone($ImpExData));

			// Mandatory
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $data['topic_id']));
			$try->set_value('mandatory', 'importthreadid',		$data["topic_id"]);
			$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $data['poster_id']));

			// Non mandatory
			$try->set_value('nonmandatory', 'visible',			$data["post_approved"]);
			$try->set_value('nonmandatory', 'iconid',			$data["icon_id"]); // phpBB id's though, need SQL swap about
			$try->set_value('nonmandatory', 'ipaddress',		$data["poster_ip"]);
			$try->set_value('nonmandatory', 'allowsmilie',		$data["enable_smilies"]);
			$try->set_value('nonmandatory', 'title',			$data["post_subject"]);
			$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($this->phpbb3_html($data["post_text"], $truncated_smilies)));
			$try->set_value('nonmandatory', 'dateline',			$data["post_time"]);
			$try->set_value('nonmandatory', 'username',			$idcache->get_id('username', $data['poster_id']));

			$try->set_value('nonmandatory', 'parentid',			"0"); // Wheres the threadding gone ?
			$try->set_value('nonmandatory', 'importpostid',		$import_id);

			$try->set_value('nonmandatory', 'showsignature',	"1");

			/*
			$try->set_value('nonmandatory', 'attach',			$data[""]);
			*/

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_post($Db_target, $t_db_type, $t_tb_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $import_id . ' ' . $displayobject->phrases['post'] . ' -> ' . $data['post_subject']);
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
				}// $try->import_post
			}
			else
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}// is_valid
			unset($try);
		}// End foreach

		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			$displayobject->display_now($displayobject->phrases['updating_parent_id']);

			if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now($displayobject->phrases['successful']);
			}
			else
			{
				$displayobject->display_now($displayobject->phrases['failed']);
			}

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : January 16, 2007, 10:47 am
# By ImpEx-generator 2.0
/*======================================================================*/
?>
