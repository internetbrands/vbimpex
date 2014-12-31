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
* DCFm Import Private Messages
*
* @package 		ImpEx.DCFm
*
*/
class DCFm_008 extends DCFm_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Private Messages';

	function DCFm_010()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_private_messages'))
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
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import private messages from your DCFm board.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of pms to import per cycle','pmsperpage', 2000));
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
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$pms_start_at 		= $sessionobject->get_session_var('pmsstartat');
			$pms_per_page 		= $sessionobject->get_session_var('pmsperpage');
			$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
			
			$class_num		= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($pms_per_page) == 0)
			{
				$pms_per_page = 150;
			}

			$pms_array 			= $this->get_DCFm_pms($Db_source, $source_database_type, $source_table_prefix, $pms_start_at, $pms_per_page);

			$to = $pms_start_at + count($pms_array);
			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($pms_array) . " private messages</h4><p><b>From</b> : " . $pms_start_at . " ::  <b>To</b> : " . $to ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$start = time();

			$pm_object = new ImpExData($Db_target, $sessionobject,'pm');
			$pm_text_object = new ImpExData($Db_target, $sessionobject,'pmtext');

			foreach ($pms_array as $pm_id => $pm)
			{
				$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

				$userid 	= $idcache->get_id('user', $pm['to_id']);
				$username	= $idcache->get_id('username', $pm['to_id']);
				unset($touserarray);
				$touserarray[$userid] = $username;

				$vB_pm_text->set_value('mandatory', 'importpmid',			$pm_id);
				$vB_pm_text->set_value('mandatory', 'fromuserid',			$idcache->get_id('user', $pm['from_id']));
				$vB_pm_text->set_value('mandatory', 'title',				$pm['subject']);
				$vB_pm_text->set_value('mandatory', 'message',				$this->html_2_bb($pm['message']));
				$vB_pm_text->set_value('mandatory', 'touserarray',			addslashes(serialize($touserarray)));

				$vB_pm_text->set_value('nonmandatory', 'fromusername',		$idcache->get_id('username', $pm['from_id']));
				$vB_pm_text->set_value('nonmandatory', 'dateline',			$this->time_to_stamp($pm["date"]));

				//$vB_pm_text->set_value('nonmandatory', 'iconid',			$pm['']);
				//$vB_pm_text->set_value('nonmandatory', 'showsignature',	$pm['']);
				//$vB_pm_text->set_value('nonmandatory', 'allowsmilie',		$pm['']);

				if($vB_pm_text->is_valid())
				{
					$pm_text_id = $vB_pm_text->import_pm_text($Db_target,$target_database_type,$target_table_prefix);

					if($pm_text_id)
					{
						$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
						$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));

						// The touser pm
						$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_to->set_value('mandatory', 'userid',				$idcache->get_id('user', $pm['to_id']));
						$vB_pm_to->set_value('mandatory', 'importpmid',			$pm_text_id);
						$vB_pm_to->set_value('nonmandatory', 'folderid',		'0');
						$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');
						
						// The fromuser pm
						$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_from->set_value('mandatory', 'userid',			$idcache->get_id('user', $pm['from_id']));
						$vB_pm_from->set_value('mandatory', 'importpmid',			$pm_text_id);
						$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
						$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');


						if($vB_pm_to->is_valid() AND $vB_pm_from->is_valid())
						{
							if($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND
								$vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->display_now('<br /><font color="green">' . $vB_pm_to->how_complete() . '% </font>Imported from : ' . $idcache->get_id('username', $pm['from_id']) . ' <b>to</b> ' . $idcache->get_id('username', $pm['to_id']) . '');
								$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
							}
							else
							{
								$displayobject->display_now('<br />Imported pm_text, Error with importing pm');
															 $sessionobject->add_error('warning',
															 $this->_modulestring,
															 get_class($this) . "::import_pm failed for " . $pm_id ,
															 'Check database permissions and pm table');
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
							}
						}
						else
						{
							$displayobject->display_now("<br />Invalid pm object, skipping.");
						}
					}
					else
					{
						$displayobject->display_now('<br />Error with importing pm_text');
													 $sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_pm_text failed for " . $pm['privmsgs_id'],
													 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid pm_text object, skipping." . $vB_pm_text->_failedon);
				}

				unset($vB_pm_text);
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
			}
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}
}
/*======================================================================*/
?>
