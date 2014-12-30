<?php if (!defined('IDIR')) { die; }
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
* mercury_009 Import Attachments
*
* @package 		ImpEx.mercury
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout 	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class mercury_009 extends mercury_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Attachments';

	function mercury_009()
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
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import the attachments from your mercury board.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of attachments to import per cycle','perpage','200'));
			$displayobject->update_html($displayobject->make_input_code('Path to attachments upload folder','attachmentsfolder',$sessionobject->get_session_var('attachmentsfolder'),1,60));
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

			$attachment_array = $this->get_mercury_attachments_details($Db_source, $source_database_type, $source_table_prefix, $start_at, $per_page, 'attachment', 'attachmentid');

			$last_pass			= $sessionobject->get_session_var('last_pass');
			$attachment_object	= new ImpExData($Db_target, $sessionobject,'attachment');
			$dir 				= $sessionobject->get_session_var('attachmentsfolder');

			$displayobject->display_now("<h4>Importing " . count($attachment_array) . " attachments</h4><p><b>From</b> : " . $start_at . " ::  <b>To</b> : " . ($start_at + count($attachment_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");
			$start = time();
			foreach ($attachment_array as $attachment_id => $attachment_details)
			{
				$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));

				if(!is_file($dir . '/' . $attachment_details['attach_file']))
				{
					$displayobject->display_now('<br /><b>Source file not found </b> :: attachment -> ' . $attachment_details['attach_name']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					continue;
				}

				$file = $this->vb_file_get_contents( $dir . '/' . $attachment_details['attach_file']);

				$try->set_value('mandatory', 'importattachmentid',	$attachment_id);
				$try->set_value('mandatory', 'filename',			$attachment_details['attach_name']);
				$try->set_value('mandatory', 'filedata',			$file);

				#$try->set_value('nonmandatory', 'dateline',			$attachment['dateline']);
				$try->set_value('nonmandatory', 'visible',			'1');
				$try->set_value('nonmandatory', 'counter',			$attachment_details['attach_downloads']);
				$try->set_value('nonmandatory', 'filesize',			$attachment_details['attach_size']);
				$try->set_value('nonmandatory', 'postid',			$attachment_details['attach_post']);
				$try->set_value('nonmandatory', 'filehash',			md5($file));

				if($try->is_valid())
				{
					if($try->import_attachment($Db_target,$target_database_type,$target_table_prefix))
					{

						$displayobject->display_now('<br /><b><font color="green">' . $try->how_complete() . '% </font></b>Imported attachment : </b>' . $attachment_details['attach_name']);
						$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
					}
					else
					{
						$displayobject->display_now('<br /> Error with importing attachment');
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
				unset($try);
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
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
