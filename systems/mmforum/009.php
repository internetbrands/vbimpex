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
* @package			ImpEx.mmforum
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class mmforum_009 extends mmforum_000
{
	var $_dependent = '006';

	function mmforum_009($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_poll'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_polls'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['polls_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['poll_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_poll']);
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

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}tx_mmforum_polls", 'uid', 0, $start_at, $per_page);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['polls'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'poll');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			$options 		= $this->get_mmforum_poll_options($Db_source, $s_db_type, $s_tb_prefix, $import_id);
			$votes_ret 		= $this->get_mmforum_poll_votes($Db_source, $s_db_type, $s_tb_prefix, $import_id);
			$poll_thread_id	= $this->get_mmforum_poll_threadid($Db_source, $s_db_type, $s_tb_prefix, $import_id);
			
			$numberoptions 		= 0;
			$voters 			= 0;
			$votes 				= array();
			$poll_voters_array 	= array();
			
			foreach ($votes_ret as $option)
			{
				$votes["$option[answer_id]"]++;
				$userid = $idcache->get_id('user', $option['userid']);
				$poll_voters_array[$userid]= $option['answer_id'];
				$numberoptions++;
			}	

			$votes = implode('|||', $votes);
			
			// Mandatory
			$try->set_value('mandatory', 'question',		$data['question']);
			$try->set_value('mandatory', 'importpollid',	$import_id);
			$try->set_value('mandatory', 'dateline',		$data['tstamp']);
			$try->set_value('mandatory', 'votes',			$votes);
			$try->set_value('mandatory', 'options',			$options);

			// Non mandatory
			$try->set_value('nonmandatory', 'timeout',		$data['endtime']);
			$try->set_value('nonmandatory', 'voters',		$voters);
			$try->set_value('nonmandatory', 'active',		'1');
			$try->set_value('nonmandatory', 'numberoptions',$numberoptions);
			$try->set_value('nonmandatory', 'multiple',		'0');
			$try->set_value('nonmandatory', 'public',		'1');
			
			if($try->is_valid())
			{
				$result = $try->import_poll($Db_target, $t_db_type, $t_tb_prefix);
				$vb_poll_id = $Db_target->insert_id();

				if($result)
				{
					if($try->import_poll_to_thread($Db_target, $t_db_type, $t_tb_prefix, $vb_poll_id, $poll_thread_id))
					{
						if($try->import_poll_voters($Db_target, $t_db_type, $t_tb_prefix, $poll_voters_array, $vb_poll_id))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['poll'] . ' -> ' . $try->get_value('mandatory', 'question'));
							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['poll_not_imported_3'], $displayobject->phrases['poll_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
						}
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['poll_not_imported_1'], $displayobject->phrases['poll_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['poll_not_imported_2'], $displayobject->phrases['poll_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End foreach			
			
			/*
			
			// Check if object is valid
			if($try->is_valid())
			{
				if($try->import_poll($Db_target, $t_db_type, $t_tb_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc">' . '$import_id ' . ' :: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['poll'] . ' -> ' . $data['']);
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['poll_not_imported'], $displayobject->phrases['poll_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
				}// $try->import_poll
			}
			else
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}// is_valid
			unset($try);
		}// End foreach
*/
		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

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
# Autogenerated on : February 13, 2008, 4:56 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
