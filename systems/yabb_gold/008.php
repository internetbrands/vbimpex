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
* yabb_gold Import Private Messages
*
* @package 		ImpEx.phpBB2
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class yabb_gold_008 extends yabb_gold_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Private Messages';

	function yabb_gold_008()
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
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import private messages from your YaBB board.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of users message boxes to import per cycle','pmsperpage','100'));
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

			$pms_start_at		= $sessionobject->get_session_var('pmsstartat');
			$pms_per_page		= $sessionobject->get_session_var('pmsperpage');

			$class_num		= substr(get_class($this) , -3);


			// Start the timings.
			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($pms_per_page) == 0)
			{
				$pms_per_page = 100;
			}

			// Get the PM's for this pass and some refrence arrays
			$pm_path 		= $sessionobject->get_session_var('forumspath') . '/Members';
			$pms_array		= $this->get_yabb_gold_pm_details($pm_path, $pms_start_at, $pms_per_page);

			$users_ids		= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names		= $this->get_username($Db_target, $target_database_type, $target_table_prefix);
			$user_names_to_ids	= $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);

			// Give the user some info
			$to = $pms_start_at + count($pms_array);
			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($pms_array) . " private messages</h4><p><b>From</b> : " . $pms_start_at . " ::  <b>To</b> : " . $to ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$pm_text_object 	= new ImpExData($Db_target, $sessionobject, 'pmtext');
			$pm_object		= new ImpExData($Db_target, $sessionobject, 'pm');

			// Get on with it
			$start = time();
			foreach ($pms_array as $user_file => $pm_array)
			{
				$sending_user_name = substr($user_file,0, -4);
				
				foreach($pm_array AS $pm_line)
				{
					$pm_bits = explode('|',$pm_line);
					$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

					$userid 	= $user_names_to_ids[$sending_user_name];
					$username	= $sending_user_name;
					unset($touserarray);
					$touserarray[$userid] = $username;

					$vB_pm_text->set_value('mandatory', 'fromuserid',		$user_names_to_ids[$pm_bits[0]]);
					$vB_pm_text->set_value('mandatory', 'title',			$pm_bits[1]);
					$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($pm_bits[3]));
					$vB_pm_text->set_value('mandatory', 'importpmid',		'1'); // This will do for now
	
					$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
					$vB_pm_text->set_value('nonmandatory', 'fromusername',		$pm_bits[0]);
					$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
					$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm_bits[4]); // Hmm or 2 ?
					$vB_pm_text->set_value('nonmandatory', 'showsignature',		'1');
					$vB_pm_text->set_value('nonmandatory', 'allowsmilie',		'1');
	
					if($vB_pm_text->is_valid())
					{
						$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);
	
						if($pm_text_id)
						{
							$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
							$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));
	
							// The touser pm
							$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
							$vB_pm_to->set_value('mandatory', 'userid',			$user_names_to_ids[$pm_bits[0]]);
							$vB_pm_to->set_value('nonmandatory', 'folderid',		'-1');
							$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');
	
							// The fromuser pm
							$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
							$vB_pm_from->set_value('mandatory', 'userid',			$user_names_to_ids[$sending_user_name]);
							$vB_pm_from->set_value('nonmandatory', 'folderid',		'0');
							$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');
	
							if($vB_pm_text->is_valid())
							{
								if($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND
									$vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
								{
									$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_text->how_complete() . '%,</b></span> Imported from : <b>' . $pm_bits[0] . '</b> , to : <b>' . $sending_user_name . '</b>');
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
					unset($vB_pm_text, $vB_pm, $pm_bits);
				}
			}

			$sessionobject->add_session_var('done_pm_ids', serialize($done_pm_ids));
			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($pms_array) == 0 OR count($pms_array) < $pms_per_page)
			{
				$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
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
