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
* ikon_mysql Import Attachments
*
*
* @package 		ImpEx.ikon_mysql
*
*/
class ikon_mysql_011 extends ikon_mysql_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '007';
	var $_modulestring 	= 'Import Attachments';

	// Due to there being no import id's
	var $_dupe_checking = false;

	function ikon_mysql_011()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported attachments have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_attachments",
											 'Check database permissions and attachemnts table');
				}
			}
			$displayobject->update_basic('title','Import attachments');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('attachment','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Attachments'));
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import the attachments from your Ikonboard.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of attachemts to import per cycle','perpage','100'));
			$displayobject->update_html($displayobject->make_input_code('Path to attachemts upload folder','uploadfolder',$sessionobject->get_session_var('uploadfolder'),1,60));
			$displayobject->update_html($displayobject->do_form_footer('Import Attachments',''));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($sessionobject->get_session_var('attachment') == 'working')
		{
			$displayobject->update_basic('displaymodules','FALSE');


			// Set up working variables.
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$start_at				= $sessionobject->get_session_var('startat');
			$per_page				= $sessionobject->get_session_var('perpage');

			$class_num		= 	substr(get_class($this) , -3);

			if(intval($per_page) == 0)
			{
				$per_page = 200;
			}

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$attachment_array 		= $this->get_ikon_mysql_attachment_rows($Db_source, $source_database_type, $source_table_prefix, $start_at, $per_page);
			// Might be faster ......
			$attach_details_array		= $this->get_ikon_mysql_attachment_details_array($Db_source, $source_database_type, $source_table_prefix);

			$last_pass 				= $sessionobject->get_session_var('last_pass');
			$attachment_object 		= new ImpExData($Db_target, $sessionobject,'attachment');


			$displayobject->display_now("<h4>Importing " . count($attachment_array) . " attachments</h4><p><b>From</b> : " . $start_at . " ::  <b>To</b> : " . ($start_at + count($attachment_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");
			$start = time();
			foreach ($attachment_array as $attachment_id => $attachment)
			{
				$the_file = $this->get_ikon_mysql_attachment($sessionobject->get_session_var('uploadfolder') , $attachment['FILE_NAME']);

				if($the_file AND $attachment['FILE_NAME'] != '')
				{
					$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));

					if(empty($attach_details_array[$attachment_id]['POST_ID']))
					{
						$displayobject->display_now("<br />Skipping, no post for file -> " . $attachment['FILE_NAME']);
						continue;
					}

					$try->set_value('mandatory', 'importattachmentid',	time()); # No real id, just needed an int
					$bits = explode('-', $attachment['FILE_NAME']);
					if($bits[3])
					{
						$try->set_value('mandatory', 'filename',		$bits[3]);
					}
					else
					{
						$try->set_value('mandatory', 'filename',		$attachment['FILE_NAME']);
					}
					$try->set_value('mandatory', 'filedata',			$the_file['data']);

					$try->set_value('nonmandatory', 'dateline',			$attachment['POST_DATE']);
					$try->set_value('nonmandatory', 'visible',			'1');
					$try->set_value('nonmandatory', 'counter',			$attachment['ATTACH_HITS']);
					$try->set_value('nonmandatory', 'filesize',			$the_file['filesize']);
					#$try->set_value('nonmandatory', 'postid',			$attach_details['POST_ID']);
					$try->set_value('nonmandatory', 'postid',			$attach_details_array[$attachment_id]['POST_ID']);


					if(empty($attach_details_array[$attachment_id]['POST_ID']))
					{
						$displayobject->display_now("<h4>Importing " . count($attachment_array) . " attachments</h4><p><b>From</b> : " . $start_at . " ::  <b>To</b> : " . ($start_at + count($attachment_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");
					}

					$try->set_value('nonmandatory', 'filehash',			$the_file['filehash']);

					if($try->is_valid())
					{
						if($try->import_attachment($Db_target,$target_database_type,$target_table_prefix))
						{
							$displayobject->display_now('<br /><b><font color="green">' . $try->how_complete() . '% </font></b>Imported attachment : </b>' . $attachment['FILE_NAME']);
							$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
						}
						else
						{
							$displayobject->display_now('<br />Imported pm_text, Error with importing attachment');
							$sessionobject->add_error('warning', $this->_modulestring,
										get_class($this) . "::import_attachment failed " . $filename[$i],
										'Check database permissions and attachment table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid object, skipping. Faild on " . $try->_failedon);
					}
					unset($the_file);
					unset($try);
				}
				else
				{
					$displayobject->display_now("<br />Skipping, no source file -> " . $attachment['FILE_NAME']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
				}
				$i++;
			}

			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($attachment_array) == 0 OR count($attachment_array) < $per_page)
			{
				$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
								$sessionobject->return_stats($class_num , '_time_taken'),
								$sessionobject->return_stats($class_num , '_objects_done'),
								$sessionobject->return_stats($class_num , '_objects_failed')
								)
							);

				$sessionobject->set_session_var($class_num ,'FINISHED');
				$sessionobject->set_session_var('attachment','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
			$sessionobject->set_session_var('startat',$start_at+$per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('attachment','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',''));
		}
	}
}
/*======================================================================*/
?>
