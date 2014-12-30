<?php 
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.smf2
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class smf2_010 extends smf2_000
{
	var $_dependent = '007';

	function smf2_010(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_attachment'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_attachments'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['attachments_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['attachment_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_attachment']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder',$sessionobject->get_session_var('attachmentsfolder'),1,60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
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
		$dir 			= $sessionobject->get_session_var('attachmentsfolder');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);
		$idcache 		= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_smf_attach($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);

		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $t_db_type, $t_tb_prefix, $importuserid);
		#$this->get_one_username($Db_target, $t_db_type, $t_tb_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $t_db_type, $t_tb_prefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_ids_array = $this->get_user_ids($Db_target, $t_db_type, $t_tb_prefix, $do_int_val = false);
		#$user_name_array = $this->get_username_to_ids($Db_target, $t_db_type, $t_tb_prefix);
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
		#$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['attachments'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'attachment');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			if (!$data['file_hash'])
			{
				$cleanname = preg_replace(array('/\s/', '/[^\w_\.\-]/'), array('_', ''), $data['filename']);
				$encname = $import_id . '_' . strtr($cleanname, '.', '_') . md5($cleanname);
				$cleanname = preg_replace('~\.[\.]+~', '.', $cleanname);
				if (is_file($dir . '/' . $cleanname))
				{
					$raw_filename = $cleanname;
				}
				else
				{
					$raw_filename = $encname;
				}
			}
			else
			{
				$raw_filename = $import_id  . '_' . $data['file_hash'];
			}

			if(!is_file($dir . '/' . $raw_filename))
			{
				$displayobject->display_now("<br /><b>{$displayobject->phrases['source_file_not']} </b> :: {$data['filename']}");
				$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['attachment_not_imported'], $data['filename'] . ' - ' . $displayobject->phrases['attachment_not_imported_rem_1']);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				continue;
			}

			$file = $this->vb_file_get_contents($dir . '/' . $raw_filename);

			// Mandatory
			$try->set_value('mandatory', 'importattachmentid',	$import_id);
			$try->set_value('mandatory', 'filename',			$data['filename']);
			$try->set_value('mandatory', 'filedata',			$file);

			// Non mandatory
			$try->set_value('nonmandatory', 'extension',		substr($data['filename'], strrpos($data['filename'], '.') + 1));
			$try->set_value('nonmandatory', 'filehash',			md5($file));
			$try->set_value('nonmandatory', 'filesize',			$data['size']);
			$try->set_value('nonmandatory', 'counter',			$data['downloads']);
			$try->set_value('nonmandatory', 'visible',			$data['approved']);
			$try->set_value('nonmandatory', 'postid',           $data['id_msg']);

			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_attachment($Db_target, $t_db_type, $t_tb_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc">' . $import_id . ':: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['attachment'] . ' -> ' . $data['filename']);
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['attachment_not_imported'], $displayobject->phrases['attachment_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} {$import_id} :: {$displayobject->phrases['attachment_not_imported']}");
				}// $try->import_attachment
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
# Autogenerated on : January 2, 2008, 4:56 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
