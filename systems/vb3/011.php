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
* vb3 Import Private Messages
*
* @package 		ImpEx.vb3
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb3_011 extends vb3_000
{
	var $_dependent 	= '004';

	function vb3_011(&$displayobject)
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
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['pm_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_pm']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['pms_per_page'],'pmperpage',250));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pmstartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
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

		$pm_start_at 			= $sessionobject->get_session_var('pmstartat');
		$pm_per_page 			= $sessionobject->get_session_var('pmperpage');
		$class_num				= substr(get_class($this) , -3);

		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$pm_text_object 		= new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object 				= new ImpExData($Db_target, $sessionobject, 'pm');

		// Start the timings.
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if(intval($pm_per_page) == 0)
		{
			$pm_per_page = 150;
		}

		// Get the PM's for this pass and some refrence arrays
		$pm_array 			= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $pm_start_at, $pm_per_page, 'pmtext', 'pmtextid');

		// Give the user some info
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($pm_array) . " {$displayobject->phrases['pm']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $pm_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($pm_start_at + count($pm_array)) . "</p>");



		foreach ($pm_array as $pm_id => $details)
		{
			$pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

			unset($touserarray);

			$old_array = unserialize($details['touserarray']);

			if (!is_array($old_array))
			{
				continue;
			}

			foreach($old_array as $old_user_id => $username)
			{
				$userid = $idcache->get_id('user', $old_user_id);
				$touserarray[$userid] = $username;
			}

			// Mandatory
			# cache
			$pm_text->set_value('mandatory', 'fromuserid',			$idcache->get_id('user', $details['fromuserid']));
			$pm_text->set_value('mandatory', 'title',				$details['title']);
			$pm_text->set_value('mandatory', 'message',				$details['message']);
			$pm_text->set_value('mandatory', 'touserarray',			addslashes(serialize($touserarray)));
			$pm_text->set_value('mandatory', 'importpmid',			$pm_id);

			// Non Mandatory
			$pm_text->set_value('nonmandatory', 'fromusername',		$details['fromusername']);
			$pm_text->set_value('nonmandatory', 'iconid',			$details['iconid']);
			$pm_text->set_value('nonmandatory', 'dateline',			$details['dateline']);
			$pm_text->set_value('nonmandatory', 'showsignature',	$details['showsignature']);
			$pm_text->set_value('nonmandatory', 'allowsmilie',		$details['allowsmilie']);

			if($pm_text->is_valid())
			{
				$pm_text_id = $pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

				if($pm_text_id)
				{
					$pms = $this->get_vb3_pms($Db_source, $source_database_type, $source_table_prefix, $pm_id);

					foreach ($pms as $pm => $details)
					{
						$pm = $pm_object;

						$pm->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$pm->set_value('mandatory', 'userid',			$idcache->get_id('user', $details['userid']));
						$pm->set_value('mandatory', 'importpmid',		$pm_id);

						// Not creating default folders atm, if its not default stuff it in the inbox
						if ($details['folderid'] == 0 OR $details['folderid'] == -1)
						{
							$pm->set_value('nonmandatory', 'folderid',	$details['folderid']);
						}
						else
						{
							$pm->set_value('nonmandatory', 'folderid',	'-1');
						}
						$pm->set_value('nonmandatory', 'messageread',	$details['messageread']);


						if($pm->is_valid())
						{
							if($pm->import_pm($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->display_now('<br /><span class="isucc"><b>' . $pm->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $pm_text->get_value('nonmandatory', 'fromusername'));
								$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
							}
							else
							{
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
								$sessionobject->add_error($pmtext_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_2']);
								$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
							}
						}
						else
						{
							$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						}
						unset($pm);
					}
				}
			}
			unset($pm_text);
		}

		if (count($pm_array) == 0 OR count($pm_array) < $pm_per_page)
		{
			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			if ($this->update_user_pm_count($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now('Count updated');
			}
			else
			{
				$displayobject->display_now('Count update error');
			}

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num , '_time_taken'),
				$sessionobject->return_stats($class_num , '_objects_done'),
				$sessionobject->return_stats($class_num , '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('pmstartat',$pm_start_at+$pm_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
