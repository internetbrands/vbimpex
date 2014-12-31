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
* PNphpBB_009 Import Pmtext module
*
* @package			ImpEx.PNphpBB
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class PNphpBB_009 extends PNphpBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Private Messages';


	function PNphpBB_009()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_private_messages'))
				{
					$displayobject->display_now('<h4>Imported pmtexts have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_private_messages','Check database permissions');
				}
			}

			if($sessionobject->get_session_var('added_default_pm_folder') != 'yup')
			{
				$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
				$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
				// Add the default PM folder
				$this->add_pm_folder_for_all_users($Db_target, $target_database_type, $target_table_prefix, 'Imported Saved Received Messages');
				$this->add_pm_folder_for_all_users($Db_target, $target_database_type, $target_table_prefix, 'Imported Saved Sent Messages');
				$sessionobject->add_session_var('added_default_pm_folder', 'yup');
			}			

			// Start up the table
			$displayobject->update_basic('title','Import Pmtext');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_pmtext','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Private messages to import per cycle (must be greater than 1)','pmtextperpage',500));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pmtextstartat','0');
			$sessionobject->add_session_var('pmtextdone','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
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
		$pmtext_start_at			= $sessionobject->get_session_var('pmtextstartat');
		$pmtext_per_page			= $sessionobject->get_session_var('pmtextperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of pmtext details
		$pms_array 	= $this->get_PNphpBB_pmtext_details($Db_source, $source_database_type, $source_table_prefix, $pmtext_start_at, $pmtext_per_page);

		$users_ids 			= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names			= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		// Give the user some info
		
		$last_pass = $sessionobject->get_session_var('last_pass');
		$displayobject->display_now("<h4>Importing " . count($pms_array) . " private messages</h4><p><b>From</b> : " . $pmtext_start_at . " ::  <b>To</b> : " . ($pmtext_start_at + $pmtext_per_page) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

		$pm_text_object = new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object 		= new ImpExData($Db_target, $sessionobject, 'pm');

		// Get on with it
		$start = time();
		foreach ($pms_array as $pm_id => $pm)
		{
			$done_pm_ids[$pm_id] = $pm_id;
			
			// 0,1,3,4,5 allowed, skip an all others
			switch($pm['privmsgs_type'])
			{
				case 0:
				case 1:
				case 3:
				case 4:
				case 5:
					break;
				default:
					continue;
			}

			$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

			$pm_text = $this->get_PNphpBB_pm_text($Db_source, $source_database_type, $source_table_prefix, $pm['privmsgs_id']);

			$userid 	= $users_ids["$pm[privmsgs_to_userid]"];
			$username	= $user_names["$pm[privmsgs_to_userid]"];
			unset($touserarray);
			$touserarray[$userid] = $username;

			$vB_pm_text->set_value('mandatory', 'fromuserid',		$users_ids["$pm[privmsgs_from_userid]"]);
			$vB_pm_text->set_value('mandatory', 'title',			$pm['privmsgs_subject']);
			$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($this->PNphpBB_html($pm_text['privmsgs_text'])));
			$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);

			$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
			$vB_pm_text->set_value('nonmandatory', 'fromusername',	$user_names["$pm[privmsgs_from_userid]"]);
			$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
			$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['privmsgs_date']);
			$vB_pm_text->set_value('nonmandatory', 'showsignature',	$pm['privmsgs_attach_sig']);
			$vB_pm_text->set_value('nonmandatory', 'allowsmilie',	$pm['privmsgs_enable_smilies']);

			if($vB_pm_text->is_valid())
			{
				$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

				if($pm_text_id)
				{
					$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
					$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));


					// The touser pm
					$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_to->set_value('mandatory', 'userid',				$users_ids["$pm[privmsgs_to_userid]"]);
					
					if ($pm['privmsgs_type'] == 3)
					{
						$vB_pm_to->set_value('nonmandatory', 'folderid',	$this->get_custom_pm_folder_id($Db_target, $target_database_type, $target_table_prefix, $users_ids["$pm[privmsgs_to_userid]"], 'Imported Saved Recived Messages'));
					}
					elseif ($pm['privmsgs_type'] == 4)
					{
						$vB_pm_to->set_value('nonmandatory', 'folderid',	$this->get_custom_pm_folder_id($Db_target, $target_database_type, $target_table_prefix, $users_ids["$pm[privmsgs_to_userid]"], 'Imported Saved Sent Messages'));
					}
					else
					{
						$vB_pm_to->set_value('nonmandatory', 'folderid',	'0');
					}
					
					$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');

					// The fromuser pm
					$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_from->set_value('mandatory', 'userid',			$users_ids["$pm[privmsgs_from_userid]"]);
					$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
					$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');

					if($vB_pm_text->is_valid())
					{
						if($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND
							$vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_text->how_complete() . '%,</b></span> Imported from : <b>' . $user_names[$pm['privmsgs_from_userid']] . '</b> , to : <b>' . $user_names[$pm['privmsgs_to_userid']] . '</b>');
							$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
						}
						else
						{
							$displayobject->display_now('<br />Imported pm_text, Error with importing pm');
														 $sessionobject->add_error('warning',
														 $this->_modulestring,
														 get_class($this) . "::import_pm failed for " . $pm_text_id,
														 'Check database permissions and pmtext table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid pm object, skipping." . $vB_pm->_failedon);
					}
				}
				else
				{
					$displayobject->display_now('<br />Error with importing pm');
												 $sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_pm failed for " . $pm['privmsgs_id'],
												 'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid pm text object, skipping." . $vB_pm_text->_failedon);
			}
			unset($vB_pm_text);
			unset($vB_pm);
		}


		// Check for page end
		if (count($pms_array) == 0 OR count($pms_array) < $pmtext_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->display_now('Updateing user Private message count');

			if ($this->update_user_pm_count($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now('Done !');
			}
			else
			{
				$displayobject->display_now('Error updating count');
			}


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_pmtext','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('pmtextstartat',$pmtext_start_at+$pmtext_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : September 16, 2004, 12:14 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>

