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
* @package			ImpEx.ubbthreads7
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class ubbthreads7_005 extends ubbthreads7_000
{
	var $_dependent = '004';

	function ubbthreads7_005(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_forum'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['forums_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['forum_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_forum']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("005_objects_done", '0');
			$sessionobject->add_session_var("005_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
			$sessionobject->add_session_var('categoriesfinished','FALSE');
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

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('categoriesfinished') == 'FALSE')
		{
			// Sort out the categories
			$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}CATEGORIES", 'CATEGORY_ID', 0, $start_at, $per_page);

			$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['categories'], $start_at);

			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($data_array['data'] as $import_id => $data)
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));

				$try->set_value('mandatory', 'title', 				$data['CATEGORY_TITLE']);
				$try->set_value('mandatory', 'displayorder',		$data['CATEGORY_SORT_ORDER']);
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				$try->set_value('mandatory', 'importcategoryid',	$import_id);

				$try->set_value('nonmandatory', 'description',		$data['CATEGORY_DESCRIPTION']);

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $t_db_type, $t_tb_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $data['CATEGORY_TITLE']);
						$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
					}
					else
					{
						$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}// $try->import_forum
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}
				unset($try);
			}
			$sessionobject->add_session_var('categoriesfinished','TRUE');
		}
		else
		{
			// Forums
			$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}FORUMS", 'FORUM_ID', 0, $start_at, $per_page);

			$cat_ids_array = $this->get_category_ids($Db_target, $t_db_type, $t_tb_prefix);

			// Display count and pass time
			$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['forums'], $start_at);

			$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($data_array['data'] as $import_id => $data)
			{
				$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

				// Mandatory
				if ($cat_ids_array["$data[CATEGORY_ID]"])
				{
					$try->set_value('mandatory', 'parentid',			$cat_ids_array["$data[CATEGORY_ID]"]);
				}
				else
				{
					$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix);
					$try->set_value('mandatory', 'parentid',			$forum_ids_array["$data[CATEGORY_ID]"]);
				}

				$try->set_value('mandatory', 'importforumid',			$import_id);
				$try->set_value('mandatory', 'importcategoryid',		"0");
				$try->set_value('mandatory', 'displayorder',			$data['FORUM_SORT_ORDER']+1);
				$try->set_value('mandatory', 'options',					$this->_default_forum_permissions);
				$try->set_value('mandatory', 'title',					$data['FORUM_TITLE']);

				// Non mandatory
				$try->set_value('nonmandatory', 'description',			$data['FORUM_DESCRIPTION']);
				$try->set_value('nonmandatory', 'threadcount',			$data['FORUM_TOPICS']);
				$try->set_value('nonmandatory', 'defaultsortorder',		$data['FORUM_SORT_ORDER']);

				/*
				$try->set_value('nonmandatory', 'parentlist',			$data['parentlist']);
				$try->set_value('nonmandatory', 'password',				$data['password']);
				$try->set_value('nonmandatory', 'link',					$data['link']);
				$try->set_value('nonmandatory', 'childlist',			$data['childlist']);
				$try->set_value('nonmandatory', 'defaultsortfield',		$data['defaultsortfield']);
				$try->set_value('nonmandatory', 'showprivate',			$data['showprivate']);
				$try->set_value('nonmandatory', 'lastpostid',			$data['lastpostid']);
				$try->set_value('nonmandatory', 'styleid',				$data['styleid']);
				$try->set_value('nonmandatory', 'newthreademail',		$data['newthreademail']);
				$try->set_value('nonmandatory', 'newpostemail',			$data['newpostemail']);
				$try->set_value('nonmandatory', 'replycount',			$data['replycount']);
				$try->set_value('nonmandatory', 'lastpost',				$data['lastpost']);
				$try->set_value('nonmandatory', 'lastposter',			$data['lastposter']);
				$try->set_value('nonmandatory', 'lastthread',			$data['lastthread']);
				$try->set_value('nonmandatory', 'lastthreadid',			$data['lastthreadid']);
				$try->set_value('nonmandatory', 'lasticonid',			$data['lasticonid']);
				$try->set_value('nonmandatory', 'daysprune',			$data['daysprune']);
				*/


				// Check if object is valid
				if($try->is_valid())
				{
					if($try->import_forum($Db_target, $t_db_type, $t_tb_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $data['FORUM_TITLE']);
						$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
					}
					else
					{
						$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}// $try->import_forum
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

				$this->build_forum_child_lists($Db_target, $t_db_type, $t_tb_prefix);

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num , 'FINISHED');
				$sessionobject->set_session_var('module', '000');
				$sessionobject->set_session_var('autosubmit', '0');
			}
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : January 19, 2007, 11:16 am
# By ImpEx-generator 2.0
/*======================================================================*/
?>
