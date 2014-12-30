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
* phpBB2 Import Polls
*
* @package 		ImpEx.phpBB2
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB2_010 extends phpBB2_000
{
	var $_dependent 	= '007';

	function phpBB2_010(&$displayobject)
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$poll_start_at 			= $sessionobject->get_session_var('pollstartat');
		$poll_per_page 			= $sessionobject->get_session_var('pollperpage');
		$class_num				= substr(get_class($this) , -3);

		// Clone and cache
		$poll_object 			= new ImpExData($Db_target, $sessionobject, 'poll');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if(intval($poll_per_page) == 0)
		{
			$poll_per_page = 150;
		}

		// Get an array of poll details
		$poll_array 			= $this->get_phpbb2_polls_details($Db_source, $source_database_type, $source_table_prefix, $poll_start_at, $poll_per_page);
		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($poll_array) . " {$displayobject->phrases['polls']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $poll_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($poll_start_at + count($poll_array)) . "</p>");

		foreach ($poll_array as $poll_id => $poll)
		{
			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));

			unset($polls_results_array, $poll_voters, $numberoptions, $voters, $options, $votes);

			$polls_results_array	= $this->get_phpbb2_poll_results_details($Db_source, $source_database_type, $source_table_prefix, $poll_id);
			$poll_voters			= $this->get_phpbb2_vote_voters($Db_source, $source_database_type, $source_table_prefix, $poll_id);

			foreach($poll_voters AS $phpBB_user_id => $vote)
			{
				$voter = $idcache->get_id('user', $phpBB_user_id);
				$poll_voters_array[$voter] = $vote;
			}

			foreach ($polls_results_array as $vote_option_id => $option)
			{
				$options 	.= $option['vote_option_text'] 	. '|||';
				$votes 		.= $option['vote_result'] 		. '|||';
				$voters		+= intval($option['vote_result']);
				$numberoptions++;
			}

			$options = substr($options, 0, -3);
			$votes = substr($votes, 0, -3);

			// Mandatory
			$try->set_value('mandatory', 'importpollid',		$poll_id);
			$try->set_value('mandatory', 'question',			$poll['vote_text']);
			$try->set_value('mandatory', 'dateline',			$poll['vote_start']);
			$try->set_value('mandatory', 'options',				$options);
			$try->set_value('mandatory', 'votes',				$votes);

			// Non Mandatory
			$try->set_value('nonmandatory', 'active',			'1');
			$try->set_value('nonmandatory', 'numberoptions',	intval($numberoptions));
			$try->set_value('nonmandatory', 'timeout',			'0');  // TODO: Is it ? $poll['vote_length']
			$try->set_value('nonmandatory', 'multiple',			'0');
			$try->set_value('nonmandatory', 'voters',			$voters);
			$try->set_value('nonmandatory', 'public',			'1');


			if($try->is_valid())
			{
				$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);
				$vb_poll_id = $Db_target->insert_id();

				if($result)
				{
					if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $poll['topic_id']))
					{
						if($try->import_poll_voters($Db_target, $target_database_type, $target_table_prefix, $poll_voters_array, $vb_poll_id))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['poll'] . ' -> ' . $try->get_value('mandatory', 'question'));
							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $poll_id, $displayobject->phrases['poll_not_imported_3'], $displayobject->phrases['poll_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
						}
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $poll_id, $displayobject->phrases['poll_not_imported_1'], $displayobject->phrases['poll_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $poll_id, $displayobject->phrases['poll_not_imported_2'], $displayobject->phrases['poll_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['poll_not_imported']}");
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $poll_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}// End foreach

		// The real end
		if (count($poll_array) == 0 OR count($poll_array) < $poll_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
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
