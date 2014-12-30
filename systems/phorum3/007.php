<?php if (!defined('IDIR')) { die; }
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
* phorum3_007 Import Attachments module
*
* @package			ImpEx.phorum3
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class phorum3_007 extends phorum3_000
{
	var $_dependent = '006';

	function phorum3_007(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_attachment']; 
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['attachment_restart_ok']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['attachment_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_attachment']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['attachments_per_page'],'attachmentperpage',250));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder',$sessionobject->get_session_var('attachmentsfolder'),1,60));
		
			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('attachmentstartat','0');
			
			$sdt = $sessionobject->get_session_var('sourcedatabasetype');
			$stp = $sessionobject->get_session_var('sourcetableprefix');
			$sessionobject->set_session_var('currentforumloop', '1');

			$details = $this->get_first_forum_name($Db_source,$sdt,$stp);

			$sessionobject->add_session_var('sourceattachmenttablename', $details['name']);
			$sessionobject->add_session_var('sourceforumid',  $details['id']);			
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
		$attachment_start_at	= $sessionobject->get_session_var('attachmentstartat');
		$attachment_per_page	= $sessionobject->get_session_var('attachmentperpage');
		$class_num				= substr(get_class($this) , -3);
		
		$sourceattachmenttablename	= $sessionobject->get_session_var('sourceattachmenttablename');
		
		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of attachment details
		$attachment_array 	= $this->get_phorum3_attachment_details($Db_source, $source_database_type, $source_table_prefix, $attachment_start_at, $attachment_per_page, $sourceattachmenttablename);


		$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);


		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($attachment_array) . " {$displayobject->phrases['attachmnets']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $attachment_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($attachment_start_at + count($attachment_array)) . "</p>");


		$attachment_object = new ImpExData($Db_target, $sessionobject, 'attachment');

		if(is_array($attachment_array))
		{
			foreach ($attachment_array as $attachment_id => $attachment_details)
			{
				$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));
				$dir 		= $sessionobject->get_session_var('attachmentsfolder');
				$extension	= substr($attachment_details['filename'], (strrpos($attachment_details['filename'], '.')));
				
				$full_path 	= "{$dir}/{$sourceattachmenttablename}/{$attachment_id}{$extension}";
				
				if(!is_file($full_path))
				{
					$displayobject->display_now("<br /><b>{$displayobject->phrases['source_file_not']} </b> :: {$attachment_details['filename']}");
					$sessionobject->add_error($attachment_id, $displayobject->phrases['attachment_not_imported'], $attachment_details['filename'] . ' - ' . $displayobject->phrases['attachment_not_imported_rem_1']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					continue;
				}
	
				$file = $this->vb_file_get_contents($full_path);

				// Mandatory
				$try->set_value('mandatory', 'filename',				addslashes($attachment_details['filename']));
				$try->set_value('mandatory', 'filedata',				$file);
				$try->set_value('mandatory', 'importattachmentid',		$attachment_id);
	
	
				// Non Mandatory
				#$try->set_value('nonmandatory', 'userid',				$user_ids_array["$attachment_details[userid]"]);
				#$try->set_value('nonmandatory', 'dateline',				$attachment_details['filetime']);
				$try->set_value('nonmandatory', 'visible',				'1');
				$try->set_value('nonmandatory', 'counter',				'0');
				$try->set_value('nonmandatory', 'filesize',				filesize($full_path));
				$try->set_value('nonmandatory', 'postid',				$attachment_details['message_id']);
				$try->set_value('nonmandatory', 'filehash',				md5($file));
	
				// Check if attachment object is valid
				if($try->is_valid())
				{
					if($try->import_attachment($Db_target, $target_database_type, $target_table_prefix))
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
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}// End foreach
		}
		
		// Check for page end
		if (count($attachment_array) == 0 OR count($attachment_array) < $attachment_per_page)
		{
			// If we are here the attachment count is less that the per page
			// Though that could mean that be are moving between forums


			// Set to the next id
			$sessionobject->add_session_var('currentforumloop',intval($sessionobject->get_session_var('currentforumloop'))+1);


			// Get the details for the next pass
			$next_forum_details = $this->get_phorum3_forum_step($Db_source, $source_database_type, $source_table_prefix, $sessionobject->get_session_var('currentforumloop'));

			// Start back at the beginning
			$sessionobject->set_session_var('attachmentstartat','0');

			$sessionobject->add_session_var('sourceforumid', $next_forum_details['id']);
			$sessionobject->add_session_var('sourceforumtablename', $next_forum_details['table_name']);


			if($sessionobject->get_session_var('currentforumloop') > intval($next_forum_details['count']))
			{
				// We have done as many that are in the dB
				// Done so we don't have to rely on the id in the database as they could be out of sync

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
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
				$jumping_forums = true;

			}
		}

		if($jumping_forums)
		{
			$sessionobject->set_session_var('attachmentstartat','0');
		}
		else
		{
			$sessionobject->set_session_var('attachmentstartat',$attachment_start_at+$attachment_per_page);
		}

		$displayobject->update_html($displayobject->print_redirect('index.php'));		
		
	}// End resume
}//End Class
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
