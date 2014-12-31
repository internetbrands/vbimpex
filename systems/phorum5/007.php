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
* phorum5_007 Import Pm module
*
* @package			ImpEx.phorum5
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class phorum5_007 extends phorum5_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Pm';

	function phorum5_007()
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
					$displayobject->display_now('<h4>Imported pms have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_private_messages','Check database permissions');
				}
			}

			// Start up the table
			$displayobject->update_basic('title','Import Pm');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_pm','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Pms to import per cycle (must be greater than 1)','pmperpage',500));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pmstartat','0');
			$sessionobject->add_session_var('pmdone','0');
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
		$pm_start_at			= $sessionobject->get_session_var('pmstartat');
		$pm_per_page			= $sessionobject->get_session_var('pmperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of pm details
		$pms_array 	= $this->get_phorum5_pm_details($Db_source, $source_database_type, $source_table_prefix, $pm_start_at, $pm_per_page);

		$users_ids = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names = $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($pm_array) . ' pms</h4><p><b>From</b> : ' . $pm_start_at . ' ::  <b>To</b> : ' . ($pm_start_at + count($pm_array)) . '</p>');

		$pm_text_object = new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object 		= new ImpExData($Db_target, $sessionobject, 'pm');

		foreach ($pms_array as $pm_id => $pm)
		{
			$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

			$userid 	= $users_ids["$pm[to_user_id]"];
			$username	= $user_names["$pm[to_user_id]"];
			unset($touserarray);
			$touserarray[$userid] = $username;

			$vB_pm_text->set_value('mandatory', 'fromuserid',		$users_ids["$pm[from_user_id]"]);
			$vB_pm_text->set_value('mandatory', 'title',			$pm['subject']);
			$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb(($pm['message'])));
			$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);

			$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
			$vB_pm_text->set_value('nonmandatory', 'fromusername',	$user_names["$pm[from_user_id]"]);
			$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
			$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['datestamp']);
			$vB_pm_text->set_value('nonmandatory', 'showsignature',	'1');
			$vB_pm_text->set_value('nonmandatory', 'allowsmilie',	'1');

			if($vB_pm_text->is_valid())
			{
				$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

				if($pm_text_id)
				{
					$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
					$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));

					// The touser pm
					$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_to->set_value('mandatory', 'userid',				$users_ids["$pm[to_user_id]"]);
					$vB_pm_to->set_value('nonmandatory', 'folderid',		'0');
					$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');

					// The fromuser pm
					$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_from->set_value('mandatory', 'userid',			$users_ids["$pm[from_user_id]"]);
					$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
					$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');

					if($vB_pm_text->is_valid())
					{
						if($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND
							$vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_text->how_complete() . '%,</b></span> Imported from : <b>' . $user_names[$pm['from_user_id']] . '</b> , to : <b>' . $user_names[$pm['to_user_id']] . '</b>');
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
			unset($vB_pm_text, $vB_pm_to, $vB_pm_from);
		}// End foreach


		// Check for page end
		if (count($pm_array) == 0 OR count($pm_array) < $pm_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_pm','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}

		$sessionobject->set_session_var('pmstartat',$pm_start_at+$pm_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : May 27, 2005, 12:56 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
