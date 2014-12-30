<?php
if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
*
* @package			ImpEx.mysmartbb
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class mysmartbb_006 extends mysmartbb_000
{
	var $_dependent = '005';

	function mysmartbb_006(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_thread'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['threads_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['thread_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_thread']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("006_objects_done", '0');
			$sessionobject->add_session_var("006_objects_failed", '0');
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

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
										   #(&$Db_object, $databasetype, $tablename, $id_field, $fields, $start_at, $per_page)
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}subject", 'id', 0, $start_at, $per_page);

		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $t_db_type, $t_tb_prefix, $importuserid);
		#$this->get_one_username($Db_target, $t_db_type, $t_tb_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $t_db_type, $t_tb_prefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_ids_array = $this->get_user_ids($Db_target, $t_db_type, $t_tb_prefix, $do_int_val = false);
		$user_name_array = $this->get_username_to_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Groups info
		#$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $t_db_type, $t_tb_prefix);
		#$bannded_groupid = $this->get_banned_group($Db_target, $t_db_type, $t_tb_prefix);
		// Thread info
		#$this->get_thread_id($Db_target, $t_db_type, $t_tb_prefix, &$importthreadid, &$forumid); // & left to show refrence
		#$thread_ids_array = $this->get_threads_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Post info
		#$this->get_posts_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Category info
		#$cat_ids_array = $this->get_category_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$style_ids_array = $this->get_style_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);
		// Forum info
		$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['threads'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'thread');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			$try->set_value('mandatory', 'title',			$data["title"]);
			$try->set_value('mandatory', 'importforumid',	$data["section"]);
			$try->set_value('mandatory', 'importthreadid',	$import_id);
			$try->set_value('mandatory', 'forumid',			$forum_ids_array["$data[section]"]);

			// Non mandatory
			$try->set_value('nonmandatory', 'visible',		"1");
			$try->set_value('nonmandatory', 'sticky',		$data["stick"]);
			$try->set_value('nonmandatory', 'views',		$data["visitor"]);
			$try->set_value('nonmandatory', 'open',			($data["close"] == 0 ? 1 : 0));
			$try->set_value('nonmandatory', 'replycount',	$data["reply_number"]);
			$try->set_value('nonmandatory', 'postusername',	$data["writer"]);
			$try->set_value('nonmandatory', 'dateline',		$data["write_time"]);
			$try->set_value('nonmandatory', 'postuserid',	$user_name_array["$data[writer]"]);

			/*
			$try->set_value('nonmandatory', 'votenum',		$data[""]);
			$try->set_value('nonmandatory', 'votetotal',		$data[""]);
			$try->set_value('nonmandatory', 'attach',		$data[""]);
			$try->set_value('nonmandatory', 'similar',		$data[""]);
			$try->set_value('nonmandatory', 'hiddencount',		$data[""]);
			$try->set_value('nonmandatory', 'notes',		$data[""]);
			$try->set_value('nonmandatory', 'iconid',		$data[""]);
			$try->set_value('nonmandatory', 'firstpostid',		$data[""]);
			$try->set_value('nonmandatory', 'lastpost',		$data[""]);
			$try->set_value('nonmandatory', 'pollid',		$data[""]);
			$try->set_value('nonmandatory', 'lastposter',		$data[""]);
			$try->set_value('nonmandatory', 'deletedcount',		$data[""]);
			*/

			// Check if object is valid
			if($try->is_valid())
			{
				if($vb_thread_id = $try->import_thread($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $data['title']);
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );

					// Do the first post.
					// Mandatory
					$try->set_value('mandatory', 'threadid',				$vb_thread_id);
					$try->set_value('mandatory', 'importthreadid',			$import_id);
					$try->set_value('mandatory', 'userid',					$user_name_array["$data[writer]"]);

					// Non mandatory
					$try->set_value('nonmandatory', 'attach',				"0");
					$try->set_value('nonmandatory', 'visible',				"1");
					$try->set_value('nonmandatory', 'iconid',				"0");
					$try->set_value('nonmandatory', 'ipaddress',			"000.000.000.000");
					$try->set_value('nonmandatory', 'showsignature',		1);
					$try->set_value('nonmandatory', 'allowsmilie',			1);
					$try->set_value('nonmandatory', 'pagetext',				$data["text"]);
					$try->set_value('nonmandatory', 'dateline',				$data["write_time"]);
					$try->set_value('nonmandatory', 'title',				$data["title"]);
					$try->set_value('nonmandatory', 'username',				$data["writer"]);
					$try->set_value('nonmandatory', 'parentid',				"0");
					$try->set_value('nonmandatory', 'importpostid',			$import_id);

					// Check if object is valid
					if($try->is_valid())
					{
						if($try->import_post($Db_target, $t_db_type, $t_tb_prefix))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $data['title']);
							$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
						}
						else
						{
							$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
							$sessionobject->add_error($Db_target, 'warning', "007", $import_id, "On thread " .$displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
						}// $try->import_post
					}
					else
					{
						$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
						$sessionobject->add_error($Db_target, 'invalid', "007", $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					}// is_valid

				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', "007", $import_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
				}// $try->import_thread
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
# Autogenerated on : January 18, 2007, 1:49 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
