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
* ipb3_009 Import Pm module
*
* @package			ImpEx.ipb3
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2008-10-29 15:46:01 -0700 (Wed, 29 Oct 2008) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ipb3_009 extends ipb3_000
{
	var $_dependent 	= '004';

	function ipb3_009($displayobject)
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
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['pms_per_page'],'pmperpage',250));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

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
		$results = $this->get_ipb3_pm_details($Db_source, $source_database_type, $source_table_prefix, $pm_start_at, $pm_per_page);
		$pm_array = $results['pms'];
		$count = $results['count'];
		$idcache = new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $count . " {$displayobject->phrases['pm']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $pm_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($pm_start_at + $count) . "</p>");

		$pm_text_object = new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object		= new ImpExData($Db_target, $sessionobject, 'pm');

		foreach ($pm_array as $pm_id => $pm)
		{
			$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

			$pm_text = $this->get_ipb3_pm_text($Db_source, $source_database_type, $source_table_prefix, $pm['msg_id']);

			$userid 	= $idcache->get_id('user', $pm['mt_to_member_id']);
			$username	= $idcache->get_id('username', $pm['mt_to_member_id']);

//			echo "title: {$pm['mt_title']}<br />";
//			echo "to userid: $userid<br />";
//			echo "to username: $username<br />";
//			echo "from: " . $idcache->get_id('user', $pm['mt_starter_id']) . '<br />';
//			echo "from username: " . $idcache->get_id('username', $pm['mt_starter_id']) . '<br />';

			unset($touserarray);
			$touserarray[$userid] = $username;

			$vB_pm_text->set_value('mandatory', 'fromuserid',		$idcache->get_id('user', $pm['mt_starter_id']));
			$vB_pm_text->set_value('mandatory', 'title',			$pm['mt_title']);
			$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($pm_text));
			$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);

			$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
			$vB_pm_text->set_value('nonmandatory', 'fromusername',	$idcache->get_id('username', $pm['mt_starter_id']));
			$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
			$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['mt_date']);
			$vB_pm_text->set_value('nonmandatory', 'showsignature',	'1');
			$vB_pm_text->set_value('nonmandatory', 'allowsmilie',	'1');

			if($vB_pm_text->is_valid())
			{
				$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

				if($pm_text_id)
				{
					$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
					$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));

					if ($pm['mt_vid_folder'] == 'in')
					{
						$from_folder_id = -1;
						$to_folder_id = 0;
					}
					else if ($pm['mt_vid_folder'] == 'sent')
					{
						$to_folder_id = -1;
						$from_folder_id = 0;
					}
					else
					{
						// Hmmm, what to do, might as well stick them in the inbox as unread.
						$pm['mt_read'] = 0;
					}

					// The fromuser pm
					$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_from->set_value('mandatory', 'userid',			$idcache->get_id('user', $pm['mt_starter_id']));
					$vB_pm_from->set_value('nonmandatory', 'folderid',		$from_folder_id);
					$vB_pm_from->set_value('nonmandatory', 'messageread',	$pm['mt_read']);

					// The touser pm
					$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_to->set_value('mandatory', 'userid',				$idcache->get_id('user', $pm['mt_to_member_id']));
					$vB_pm_to->set_value('nonmandatory', 'folderid',		$to_folder_id);
					$vB_pm_to->set_value('nonmandatory', 'messageread',		$pm['mt_read']);

					if($vB_pm_text->is_valid())
					{
						$topm = $vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix);
						if (!$pm['mt_is_system'])
						{	// Don't create a "from" pm for system messages
							$frompm = $vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix);
						}
						else
						{
							echo " - System Message, No FROM pm created.";
							$frompm = true;
						}

						if ($topm AND $frompm)
						{
							if(shortoutput)
							{
								$displayobject->display_now('.');
							}
							else
							{
								$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_from->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $vB_pm_text->get_value('nonmandatory', 'fromusername'));
							}

							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $pm_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_1']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
						}
					}
					else
					{
						$sessionobject->add_error($Db_target, 'invalid', $class_num, $pm_id, $displayobject->phrases['invalid_object'], $vB_pm_text->_failedon);
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $vB_pm_from->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $pm_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_2']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $vB_pm_text->_failedon);
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $pm_id, $displayobject->phrases['invalid_object'], $vB_pm_text->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($vB_pm_text, $vB_pm_from, $vB_pm_to);
		}// End foreach
//exit;
		// Check for page end
		if ($count == 0 OR $count < $pm_per_page)
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
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('pmstartat',$pm_start_at+$pm_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 20, 2004, 2:31 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
