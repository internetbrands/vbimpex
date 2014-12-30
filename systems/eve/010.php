<?php
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
* eve Import Private Messages
*
*
* @package 		ImpEx.eve
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class eve_010 extends eve_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Private Messages';

	function eve_010()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject,$displayobject,$Db_target,$Db_source,'clear_imported_private_messages'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Private messages have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_private_messages",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import PMs');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('pms','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Private Messages'));
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import private messages from your EVE board.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of pms to import per cycle','pmsperpage','100'));
			$displayobject->update_html($displayobject->do_form_footer('Import Private Messages'));

			$sessionobject->add_session_var('pmsstartat','0');
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
		if ($sessionobject->get_session_var('pms') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$pms_start_at 			= $sessionobject->get_session_var('pmsstartat');
			$pms_per_page 			= $sessionobject->get_session_var('pmsperpage');

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($pms_per_page) == 0)
			{
				$pms_per_page = 150;
			}

			$pms_array 			= $this->get_eve_pmtext_details($Db_source, $source_database_type, $source_table_prefix, $pms_start_at, $pms_per_page);
			$users_ids 			= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names			= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			$to = $pms_start_at + count($pms_array);
			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($pms_array) . " private messages</h4><p><b>From</b> : " . $pms_start_at . " ::  <b>To</b> : " . $to ."</p>");

			$vB_pm_text_object = new ImpExData($Db_target,$sessionobject,'pmtext');
			$vB_pm_object = new ImpExData($Db_target,$sessionobject,'pm');
			foreach ($pms_array as $pm_id => $pm)
			{
				$vB_pm_text = $vB_pm_text_object;

				//$pm_text = $this->get_eve_pm_text($Db_source, $source_database_type, $source_table_prefix, $pm['TOPIC_MESSAGE_OID']);
				#$pm_text = $this->get_eve_pm_text($Db_source, $source_database_type, $source_table_prefix, $pm);

				$vB_pm_text->set_value('mandatory', 'fromuserid',		$users_ids[$pm_text['AUTHOR_OID']]);
				$vB_pm_text->set_value('mandatory', 'title',			$pm_text['SUBJECT']);
				$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($pm_text['MESSAGE_BODY']));
				$vB_pm_text->set_value('mandatory', 'touserarray',		$users_ids[$pm_text['AUTHOR_OID']]);

				$vB_pm_text->set_value('nonmandatory', 'fromusername',	$user_names[$pm_text['AUTHOR_OID']]);
				$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
				$vB_pm_text->set_value('nonmandatory', 'dateline',		strtotime($pm_text['DATETIME_POSTED']));
				$vB_pm_text->set_value('nonmandatory', 'showsignature',	$this->option2bin($pm_text['ENABLE_SIGNATURE']));


				$pm_text_id = $vB_pm_text->import_pm_text($Db_target,$target_database_type,$target_table_prefix);

				if($pm_text_id)
				{
					$vB_pm = $vB_pm_object;

					$vB_pm->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm->set_value('mandatory', 'userid',			$users_ids[$pm_text['AUTHOR_OID']]);

					$vB_pm->set_value('nonmandatory', 'folderid',		'0');
					$vB_pm->set_value('nonmandatory', 'messageread',	'0');

					if($vB_pm->import_pm($Db_target,$target_database_type,$target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm->how_complete() . '%</b></span> Imported from : <b>' . $user_names[$pm_text['AUTHOR_OID']] . '</b> , to : <b>' . $user_names[$pm_text['AUTHOR_OID']] . '</b>');
						$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
					}
					else
					{
						$displayobject->display_now('<br />Imported pm_text, Error with importing pm');
													 $sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_poll failed for " . $poll['topic_id'] . " Have to check 3 tables",
													 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
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
				unset($vB_pm);
				unset($vB_pm_text);
			}


			if (count($pms_array) == 0 OR count($pms_array) < $pms_per_page)
			{
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
								$sessionobject->return_stats($class_num , '_time_taken'),
								$sessionobject->return_stats($class_num , '_objects_done'),
								$sessionobject->return_stats($class_num , '_objects_failed')));

				$sessionobject->set_session_var($class_num ,'FINISHED');
				$sessionobject->set_session_var('pms','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
			$sessionobject->set_session_var('pmsstartat',$pms_start_at+$pms_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$displayobject->display_now('Going to the main page...');
			$displayobject->update_html($displayobject->print_redirect('index.php'));
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
