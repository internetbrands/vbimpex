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
* eve_009 Import Pmtext module
*
* @package			ImpEx.eve
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class eve_009 extends eve_000
{
	var $_dependent 	= '001';

	function eve_009(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_pm']; 
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_private_messages'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['pm_restart_ok']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['pm_restart_failed'], $displayobject->phrases['check_db_permissions']);				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_pm']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_pmtext','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['pms_per_page'],'pmtextperpage',250));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pmtextstartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
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
		$pmtext_start_at		= $sessionobject->get_session_var('pmtextstartat');
		$pmtext_per_page		= $sessionobject->get_session_var('pmtextperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of pmtext details
		$pmtext_array 	= $this->get_eve_pmtext_details($Db_source, $source_database_type, $source_table_prefix, $pmtext_start_at, $pmtext_per_page);

		$users_ids = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names = $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($pmtext_array) . " {$displayobject->phrases['pms']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $pmtext_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($pmtext_start_at + count($pmtext_array)) . "</p>");

		$pmtext_object 	= new ImpExData($Db_target, $sessionobject, 'pmtext');
		$vB_pm_object 	= new ImpExData($Db_target,$sessionobject,'pm');

		foreach ($pmtext_array as $pmtext_id => $pm_text)
		{
			$vB_pm_text = (phpversion() < '5' ? $pmtext_object : clone($pmtext_object));

			//$pm_text = $this->get_eve_pm_text($Db_source, $source_database_type, $source_table_prefix, $pm['TOPIC_MESSAGE_OID']);

			$vB_pm_text->set_value('mandatory', 'fromuserid',		$users_ids["$pm_text[AUTHOR_OID]"]);
			$vB_pm_text->set_value('mandatory', 'title',			$pm_text['SUBJECT']);
			$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($pm_text['BODY']));
			$vB_pm_text->set_value('mandatory', 'touserarray',		$users_ids["$pm_text[AUTHOR_OID]"]);

			$vB_pm_text->set_value('nonmandatory', 'fromusername',	$user_names["$pm_text[AUTHOR_OID]"]);
			$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
			$vB_pm_text->set_value('nonmandatory', 'dateline',		strtotime($pm_text['DATETIME_CREATED']));
			$vB_pm_text->set_value('nonmandatory', 'showsignature',	'1');

			$pm_text_id = $vB_pm_text->import_pm_text($Db_target,$target_database_type,$target_table_prefix);

			if($pm_text_id)
			{
				$vB_pm_to = (phpversion() < '5' ? $vB_pm_object : clone($vB_pm_object));

				$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
				$vB_pm_to->set_value('mandatory', 'userid',			$users_ids["$pm_text[AUTHOR_OID]"]);

				$vB_pm_to->set_value('nonmandatory', 'folderid',	'0');
				$vB_pm_to->set_value('nonmandatory', 'messageread',	'0');

				
				$recived = $this->get_eve_pm_recipitent_details($Db_source, $source_database_type, $source_table_prefix, $pm_text['PRIVATE_TOPIC_OID']);

				foreach ($recived AS $space => $source_userid)
				{
					$vB_pm_from = (phpversion() < '5' ? $vB_pm_object : clone($vB_pm_object));
					if ($source_userid != $pm_text['AUTHOR_OID'])
					{
						$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_from->set_value('mandatory', 'userid',			@$users_ids[$source_userid]);

						$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
						$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');	
						
						$vB_pm_from->import_pm($Db_target,$target_database_type,$target_table_prefix);
					}
					
					unset($vB_pm_from);
				}
				
				
				if($vB_pm_to->import_pm($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_to->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $vB_pm_text->get_value('mandatory', 'title'));
					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($pmtext_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_1']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$sessionobject->add_error($pmtext_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_2']);
				$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
			}
			unset($vB_pm_to);
			unset($vB_pm_text);
		}// End foreach

		// Check for page end
		if (count($pmtext_array) == 0 OR count($pmtext_array) < $pmtext_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			if ($this->update_user_pm_count($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now($displayobject->phrases['completed']);
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

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_pmtext','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		$sessionobject->set_session_var('pmtextstartat',$pmtext_start_at+$pmtext_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : March 8, 2005, 12:17 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

