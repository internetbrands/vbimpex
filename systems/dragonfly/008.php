<?php 
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.dragonfly
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class dragonfly_008 extends dragonfly_000
{
	var $_dependent = '004';

	function dragonfly_008($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_pm'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_private_messages'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['pms_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['pm_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_pm']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);
		$idcache 		= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);
		$pm_text_object = new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object 		= new ImpExData($Db_target, $sessionobject, 'pm');
		
		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}bbprivmsgs", 'privmsgs_id', 0, $start_at, $per_page);
	
		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['pms'], $start_at);

		foreach ($data_array['data'] as $pm_id => $pm)
		{
			$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

			$pm_text = $this->get_dragonfly_text($Db_source, $s_db_type, $s_tb_prefix, $pm['privmsgs_id']);

			$userid 	= $idcache->get_id('user', $pm['privmsgs_to_userid']);
			$username	= $idcache->get_id('username', $pm['privmsgs_to_userid']);
			unset($touserarray);
			$touserarray[$userid] = $username;

			$vB_pm_text->set_value('mandatory', 'fromuserid',		$idcache->get_id('user', $pm['privmsgs_from_userid']));
			$vB_pm_text->set_value('mandatory', 'title',			$pm['privmsgs_subject']);
			$vB_pm_text->set_value('mandatory', 'message',			$this->html_2_bb($pm_text['privmsgs_text']));
			$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);

			$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
			$vB_pm_text->set_value('nonmandatory', 'fromusername',	$idcache->get_id('username', $pm['privmsgs_from_userid']));
			$vB_pm_text->set_value('nonmandatory', 'iconid',		'');
			$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['privmsgs_date']);
			$vB_pm_text->set_value('nonmandatory', 'showsignature',	$pm['privmsgs_attach_sig']);
			$vB_pm_text->set_value('nonmandatory', 'allowsmilie',	$pm['privmsgs_enable_smilies']);

			if($vB_pm_text->is_valid())
			{
				$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $t_db_type, $t_tb_prefix);

				if($pm_text_id)
				{
					$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
					$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));


					// The touser pm
					$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_to->set_value('mandatory', 'userid',    			$idcache->get_id('user', $pm['privmsgs_to_userid']));
					$vB_pm_to->set_value('mandatory', 'importpmid',  		$pm_id);
					$vB_pm_to->set_value('nonmandatory', 'folderid',		'0');
					$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');

					// The fromuser pm
					$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
					$vB_pm_from->set_value('mandatory', 'userid',   		$idcache->get_id('user', $pm['privmsgs_from_userid']));
					$vB_pm_from->set_value('mandatory', 'importpmid', 		 $pm_id);
					$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
					$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');

					if($vB_pm_text->is_valid())
					{
						if($vB_pm_to->import_pm($Db_target, $t_db_type, $t_tb_prefix) AND
							$vB_pm_from->import_pm($Db_target, $t_db_type, $t_tb_prefix))
						{
							if(shortoutput)
							{
								$displayobject->display_now('.');
							}
							else
							{
								$displayobject->display_now('<br /><span class="isucc"><b>' . $vB_pm_to->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $vB_pm_text->get_value('nonmandatory', 'fromusername'));
							}

							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $pm_text_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_1']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
						}
					}
					else
					{
						$sessionobject->add_error($Db_target, 'invalid', $class_num, $pm_text_id, $displayobject->phrases['invalid_object'], $vB_pm_text->_failedon);
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $vB_pm_text->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $pm_text_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_2']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['pm_not_imported']}");
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $pm_id, $displayobject->phrases['invalid_object'], $vB_pm_text->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $vB_pm_text->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($vB_pm_text, $vB_pm_to, $vB_pm_from);
		}

		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			if ($this->update_user_pm_count($Db_target, $t_db_type, $t_tb_prefix))
			{
				$displayobject->display_now($displayobject->phrases['successful']);
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

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : February 19, 2008, 12:53 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
