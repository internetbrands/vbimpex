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
* ipb Import Polls
*
*
* @package 		ImpEx.ipb
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ipb_008 extends ipb_000
{
	var $_dependent 	= '006';

	function ipb_008(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_poll'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_polls'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['poll_restart_ok']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['poll_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_poll']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_poll']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['polls_per_page'],'pollperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pollstartat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type = $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix   = $sessionobject->get_session_var('targettableprefix');
		$source_database_type  = $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix   = $sessionobject->get_session_var('sourcetableprefix');
		$poll_start_at    = $sessionobject->get_session_var('pollstartat');
		$poll_per_page    = $sessionobject->get_session_var('pollperpage');
		$class_num    = substr(get_class($this), -3);
  
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}
		
		if(intval($poll_per_page) == 0)
		{
			$poll_per_page = 100;
		}
		
		$polls_array	= $this->get_ipb_polls_details($Db_source, $source_database_type, $source_table_prefix, $poll_start_at, $poll_per_page);
		$thread_ids		= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
		$users_ids		= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$last_pass		= $sessionobject->get_session_var('last_pass');
		
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($polls_array) . " {$displayobject->phrases['polls']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $poll_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($poll_start_at + count($polls_array)) . "</p>");
		$poll_object = new ImpExData( $Db_target, $sessionobject,'poll');
		
		foreach ($polls_array as $poll_id => $poll)
		{
			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));
			$poll_answers = unserialize(stripslashes($poll['choices']));
			
			$optionsstring	= '';
			$votesstring	= '';
			$numberoptions	= 0;
			$voters			= 0;
			
			unset($poll_voters_array);
			
			
			while ($pollinfo = each($poll_answers))
			{
				$optionsstring .= "|||" . $this->unhtmlspecialchars($pollinfo['1']['1']);
				// sanity check for votes count
				$votesbit = intval($pollinfo['1']['2']);
				if ($votesbit < 0)
				{
					$votesbit = 0;
				}
				$voters += $votesbit;
				$votesstring .= "|||".$votesbit;
				$numberoptions++;
			}
			
			unset($poll_answers);
			
			if (substr($optionsstring,0,3)=="|||")
			{
				$poll['options']=substr($optionsstring,3);
			}
			if (substr($votesstring,0,3)=="|||")
			{
				$poll['votes']=substr($votesstring,3);
			}
			
			$try->set_value('mandatory', 'importpollid',  $poll_id);
			if(!$poll['poll_question'])
			{
				$try->set_value('mandatory', 'question',   'No question avaiable to import.');
			}
			else
			{
				$try->set_value('mandatory', 'question',   $poll['poll_question']);
			}
			
			$try->set_value('mandatory', 'dateline',		$poll['start_date']);
			$try->set_value('mandatory', 'options',			$poll['options']);
			$try->set_value('mandatory', 'votes',			$poll['votes']);
			$try->set_value('nonmandatory', 'active',		'1');
			$try->set_value('nonmandatory', 'numberoptions', $numberoptions);
			$try->set_value('nonmandatory', 'timeout',		'0');
			$try->set_value('nonmandatory', 'multiple',		'0');
			$try->set_value('nonmandatory', 'voters',		$voters);
			$try->set_value('nonmandatory', 'public',		'1');

			// If "forum_poll_voters"
			$poll_voters = $this->get_ipb_poll_voters($Db_source, $source_database_type, $source_table_prefix, $poll_id, 'type1');

			if(count($poll_voters) == 0)
			{
				// Must be the other kind "voters"
				$poll_voters = $this->get_ipb_poll_voters($Db_source, $source_database_type, $source_table_prefix, $poll['tid'], 'type2');
			}
   
			foreach($poll_voters AS $ipb_user_id)
			{
				$voter = $users_ids[$ipb_user_id[member_id]];
				$poll_voters_array[$voter] = 0;
			}
			
			$vb_poll_id = $Db_target->insert_id();
			
			if($try->is_valid())
			{
				$result = $try->import_poll($Db_target,$target_database_type,$target_table_prefix);
				
				if($result)
				{
					if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $result, $poll['tid']))
					{
						if($try->import_poll_voters($Db_target, $target_database_type, $target_table_prefix, $poll_voters_array ,$vb_poll_id))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['poll'] . ' -> ' . $try->get_value('mandatory', 'question'));
							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($poll_id, $displayobject->phrases['poll_not_imported_3'], $displayobject->phrases['poll_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported_1']}");
						}
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($poll_id, $displayobject->phrases['poll_not_imported_1'], $displayobject->phrases['poll_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($poll_id, $displayobject->phrases['poll_not_imported_2'], $displayobject->phrases['poll_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			
			unset($try);
		}
		
		// The real end
		if (count($polls_array) == 0 OR count($polls_array) < $poll_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));
			
			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('polls','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
		
		$sessionobject->set_session_var('pollstartat',$poll_start_at+$poll_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
