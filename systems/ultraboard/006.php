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
* @package			ImpEx.ultraboard
* @date				$Date: $
*
*/

class ultraboard_006 extends ultraboard_000
{
	var $_dependent = '005';

	function ultraboard_006($displayobject)
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
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_posts') and 
					$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
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
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 2000));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder',$sessionobject->get_session_var('attachmentsfolder'),1,60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
			$sessionobject->add_session_var('stepping', -1);
			$sessionobject->add_session_var('currentboard', -1);
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
		$stepping 		= $sessionobject->get_session_var('stepping');
		$idcache 		= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);
		$dir 			= $sessionobject->get_session_var('attachmentsfolder');
		
		$current_board = $this->get_next_ultra_forum($Db_source, $s_db_type, $s_tb_prefix, $stepping);
		$sessionobject->set_session_var('currentboard', $current_board);
				
		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$data_array = array();
		
		// Get an array data
		if ($current_board)
		{
			$data_array = $this->get_ultraboard_posts($Db_source, $s_db_type, "{$s_tb_prefix}B{$current_board}", $start_at, $per_page);
		}
		
		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['posts'] . " {$s_tb_prefix}B{$current_board}", $start_at);

		$ImpExData_object 	= new ImpExData($Db_target, $sessionobject, 'post');
		$attachment_object 	= new ImpExData($Db_target, $sessionobject, 'attachment');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			($data['ROOT'] == 0 ? $data['ROOT'] = $data['ID'] : true );
			
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('threadandforum', $data['ROOT'], $current_board));
			$try->set_value('mandatory', 'importthreadid',		$data['ROOT']);
			$try->set_value('mandatory', 'userid',				$idcache->get_id('usernametoid', $data['USERNAME']));

			// Non mandatory
			$try->set_value('nonmandatory', 'visible',			'1');
			$try->set_value('nonmandatory', 'ipaddress',		$data['IP']);
			$try->set_value('nonmandatory', 'showsignature',	$data['SIGNATURE']);
			$try->set_value('nonmandatory', 'allowsmilie',		'1');
			$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($data['MESSAGE']));
			$try->set_value('nonmandatory', 'dateline',			$data['POST_SECOND']);
			$try->set_value('nonmandatory', 'title',			$data['SUBJECT']);
			$try->set_value('nonmandatory', 'username',			$data['USERNAME']);
			$try->set_value('nonmandatory', 'parentid',			$idcache->get_id('post', $data['ROOT']));
			$try->set_value('nonmandatory', 'importpostid',		$data['ID']);

			// Check if object is valid
			if($try->is_valid())
			{
				if($new_post_id = $try->import_post($Db_target, $t_db_type, $t_tb_prefix))
				{
					$fullname = $dir . "/Post-{$current_board}-" . $data['ID'] . '-' . $data['ATTACHMENT'];
					
					// Is there an attachment ?
					if (!empty($data['ATTACHMENT']))
					{
						$try_attach = (phpversion() < '5' ? $attachment_object : clone($attachment_object));
						
						if(!is_file($fullname))
						{
							continue;
						}
						else
						{
							echo "<br>FULLNAME : $fullname ";
							
							$file = $this->vb_file_get_contents($fullname);
							if ($file)
							{
								// Mandatory
								$try_attach->set_value('mandatory', 'filename',				addslashes($data['ATTACHMENT']));
								$try_attach->set_value('mandatory', 'filedata',				$file);
								$try_attach->set_value('mandatory', 'importattachmentid',	$data['ID']);
				
								// Non Mandatory
								$try_attach->set_value('nonmandatory', 'dateline',			$data['POST_SECOND']);
								$try_attach->set_value('nonmandatory', 'visible',			'1');
								$try_attach->set_value('nonmandatory', 'counter',			'0');
								$try_attach->set_value('nonmandatory', 'filesize',			filesize($fullname));
								$try_attach->set_value('nonmandatory', 'postid',			$new_post_id);
								$try_attach->set_value('nonmandatory', 'filehash',			md5($file));
								$try_attach->set_value('nonmandatory', 'extension',			substr($data['ATTACHMENT'],strpos($data['ATTACHMENT'], ".")+1));
								$try_attach->import_attachment($Db_target, $t_db_type, $t_tb_prefix, FALSE);
							}
						}
					}// If attach
					
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $data['SUBJECT']);
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
			if ($stepping < 0)
			{
				$sessionobject->set_session_var('stepping', 1);
			}
			else
			{
				$stepping++;
				$sessionobject->set_session_var('stepping', $stepping);
			}
			
			if (!$current_board)
			{	
				if ($this->update_post_parent_ids($Db_target, $t_db_type, $t_tb_prefix))
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
			
			$sessionobject->set_session_var('startat', 0);
		}
		else
		{
			$sessionobject->set_session_var('startat', $start_at + $per_page);
		}
		
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : February 28, 2008, 11:56 am
# By ImpEx-generator 2.0
/*======================================================================*/
?>
