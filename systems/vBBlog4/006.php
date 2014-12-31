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
* vBulletin Suite Blog 4.x
*
* @package 		ImpEx.vBulletinBlog4
* @date 		$Date: $
*
*/

class vBBlog4_006 extends vBBlog4_000
{
	var $_dependent = '004';

	function vBBlog4_006(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_blog_attachment'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				$contentinfo = array(
					'productid' => 'vbblog',
					'class'     => 'BlogEntry',
				);
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_attachments', $contentinfo))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['blog_attachments_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['attachment_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_blog_attachment']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['attachments_per_page'], 'attachmentperpage', 250));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder', $sessionobject->get_session_var('attachmentsfolder'), 1, 60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('attachmentstartat','0');
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
		$attachment_start_at	= $sessionobject->get_session_var('attachmentstartat');
		$attachment_per_page	= $sessionobject->get_session_var('attachmentperpage');
		$class_num		= substr(get_class($this) , -3);
		$attachment_object = new ImpExData($Db_target, $sessionobject, 'attachment');

		if(intval($attachment_per_page) == 0)
		{
			$attachment_per_page = 200;
		}

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$attachment_array = $this->get_vb4_attachment_details($Db_source, $s_db_type, $s_tb_prefix, $attachment_start_at, $attachment_per_page, 'vbblog', 'BlogEntry');

		// Display count and pass time
		$displayobject->print_per_page_pass($attachment_array['count'], $displayobject->phrases['blog_attachments'], $start_at);

		foreach ($attachment_array as $attachment_id => $attachment)
		{
			$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));

			// If its null its stored in the source file system
			if (strlen($sessionobject->get_session_var('attachmentsfolder')) > 1)
			{
				$id_string = strval($attachment['userid']);
				$attach_path = '/';

				for ($i=0; $i <= strlen($id_string); $i++)
				{
					$attach_path .= $id_string[$i] . '/';
				}

				$attach_path = substr($attach_path, 0, -1);
				$attach_path = $sessionobject->get_session_var('attachmentsfolder') . $attach_path . $attachment['filedataid'] . '.attach';

				if (!is_file($attach_path))
				{
					$displayobject->display_now("<br /><b>{$displayobject->phrases['source_file_not']} </b> :: $attach_path");
					$sessionobject->add_error($attachment_id, $displayobject->phrases['attachment_not_imported'], $attach_path . ' - ' . $displayobject->phrases['attachment_not_imported_rem_1']);
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
					continue;
				}

				$attachment['filedata'] = $this->vb_file_get_contents($attach_path);
			}

			$try->set_value('mandatory', 'importattachmentid',	$attachment_id);
			$try->set_value('mandatory', 'filename',			$attachment['filename']);
			$try->set_value('mandatory', 'filedata',			$attachment['filedata']);

			$try->set_value('nonmandatory', 'dateline',			$attachment['dateline']);
			$try->set_value('nonmandatory', 'visible',			$attachment['state']);
			$try->set_value('nonmandatory', 'counter',			$attachment['counter']);
			$try->set_value('nonmandatory', 'filesize',			$attachment['filesize']);
			$try->set_value('nonmandatory', 'postid',			$attachment['contentid']);
			$try->set_value('nonmandatory', 'filehash',			$attachment['filehash']);
			$try->set_value('nonmandatory', 'settings',			$attachment['settings']);
			$try->set_value('nonmandatory', 'width',			$attachment['width']);
			$try->set_value('nonmandatory', 'height',			$attachment['height']);
			$try->set_value('nonmandatory', 'displayorder',		$attachment['displayorder']);
			$try->set_value('nonmandatory', 'caption',			$attachment['caption']);

			// Check that if there is some file data
			if($try->is_valid() AND !empty($attachment['filedata']))
			{
				if($try->import_vb4_attachment($Db_target, $t_db_type, $t_tb_prefix, true, 'blog'))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['attachment'] . ' -> ' . $try->get_value('mandatory','filename'));
					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($attachment_id, $displayobject->phrases['attachment_not_imported'], $displayobject->phrases['attachment_not_imported_rem_2']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['attachment_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				if (empty($attachment['filedata']))
				{
					$displayobject->display_now(" <b>{$displayobject->phrases['source_file_not']} </b>");
				}
				$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}

		// Check for page end
		if (count($attachment_array) == 0 OR count($attachment_array) < $attachment_per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('attachmentstartat', $attachment_start_at + $attachment_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 29, 2007, 2:02 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
