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
* wBB Import Private Messages
*
* @package 		ImpEx.wBB
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_011 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Private Messages';

	function wBB_011()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_private_messages'))
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
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import private messages from your wBB board.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of pms to import per cycle','pmsperpage','500'));
			$displayobject->update_html($displayobject->do_form_footer('Import Private Messages'));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


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
			$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

			// Start the timings.
			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($pms_per_page) == 0)
			{
				$pms_per_page = 150;
			}

			// Get the PM's for this pass and some refrence arrays
			$pms_array 			= $this->get_wBB_pm_details($Db_source, $source_database_type, $source_table_prefix, $pms_start_at, $pms_per_page);
			#$users_ids 			= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			#$user_names			= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			// Give the user some info
			$to = $pms_start_at + count($pms_array);
			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($pms_array) . " private messages</h4><p><b>From</b> : " . $pms_start_at . " ::  <b>To</b> : " . $to ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$pm_text_object = new ImpExData($Db_target, $sessionobject, 'pmtext');
			$pm_object 		= new ImpExData($Db_target, $sessionobject, 'pm');

			// Get on with it
			$start = time();
			foreach ($pms_array as $pm_id => $pm)
			{
				$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

				$userid 	= $idcache->get_id('user', $pm["recipientid"]);
				$username	= $idcache->get_id('username', $pm["recipientid"]);
				unset($touserarray);
				$touserarray[$userid] = $username;				
				
				$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);
				$vB_pm_text->set_value('mandatory', 'fromuserid',		$idcache->get_id('user', $pm["senderid"]));
				$vB_pm_text->set_value('mandatory', 'title',			$pm['subject']);
				$vB_pm_text->set_value('mandatory', 'message',			$pm['message']);
				$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));

				$vB_pm_text->set_value('nonmandatory', 'fromusername',	$idcache->get_id('username', $pm["senderid"]));
				$vB_pm_text->set_value('nonmandatory', 'iconid',		$pm['iconid']);
				$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['sendtime']);
				$vB_pm_text->set_value('nonmandatory', 'showsignature',	$pm['showsignature']);
				$vB_pm_text->set_value('nonmandatory', 'allowsmilie',	$pm['allowsmilies']);

				if($vB_pm_text->is_valid())
				{
					$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

					if($pm_text_id)
					{
						$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
						$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));

						// it's multi though just do one recipt for the first one
						if ($pm['recipientlist'])
						{
							$array_catch = unserialize($pm['recipientlist']);
							if (is_array($array_catch))
							{
								$recipt_id = key($array_catch);
							}
						}
						else
						{
							$recipt_id = $pm['recipientid'];
						}						
												
						// The touser pm
						$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_to->set_value('mandatory', 'userid',				$idcache->get_id('user', $recipt_id));
						$vB_pm_to->set_value('nonmandatory', 'folderid',		'0');
						$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');

						// The fromuser pm
						$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_from->set_value('mandatory', 'userid',			$idcache->get_id('user', $pm["senderid"]));
						$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
						$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');

						if($vB_pm_text->is_valid())
						{
							if($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND
								$vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_to->how_complete() . '% </span>Imported from : <b>' . $idcache->get_id('username', $pm["senderid"]) . '</b> , to : <b>' . $idcache->get_id('username', $recipt_id) . '</b>');
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


			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($pms_array) == 0 OR count($pms_array) < $pms_per_page)
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
