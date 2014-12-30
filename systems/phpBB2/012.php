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
* phpBB2 Import Ranks
*
* @package 		ImpEx.phpBB2
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB2_012 extends phpBB2_000
{
	var $_dependent 	= '001';

	function phpBB2_012(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_rank'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_ranks'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['ranks_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['ranks_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_rank']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['ranks_per_page'],'ranksperpage', 500));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('ranksstartat','0');
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
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$ranks_start_at			= $sessionobject->get_session_var('ranksstartat');
		$ranks_per_page			= $sessionobject->get_session_var('ranksperpage');

		$class_num				= 	substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if(intval($ranks_per_page) == 0)
		{
			$ranks_per_page = 200;
		}

		$ranks_array = $this->get_phpbb2_ranks_details($Db_source, $source_database_type, $source_table_prefix, $ranks_start_at, $ranks_per_page);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($ranks_array) . " {$displayobject->phrases['ranks']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $ranks_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($ranks_start_at + count($ranks_array)) . "</p>");

		$rank_object 		= new ImpExData($Db_target, $sessionobject, 'ranks');
		#$usergroup_object	= new ImpExData($Db_target, $sessionobject, 'usergroup');

		foreach ($ranks_array as $rank_id => $rank)
		{
			$new_rank 		= $rank_object;
			#$new_usergroup	= $usergroup_object;

			#$new_usergroup->set_value('mandatory', 'importusergroupid',	$rank['rank_id']);
			#$new_usergroup->set_value('nonmandatory', 'title',			$rank['rank_title']);
			#$new_usergroup->set_value('nonmandatory', 'description',	$rank['rank_title']);

			$new_rank->set_value('mandatory', 'importrankid',			$rank_id);

			$new_rank->set_value('nonmandatory', 'minposts',			$rank['rank_min']);
			$new_rank->set_value('nonmandatory', 'rankimg',				'<b>' . $rank['rank_title'] . '</b>'); #$rank['rank_image']);
			$new_rank->set_value('nonmandatory', 'type',				'1');
			$new_rank->set_value('nonmandatory', 'ranklevel',			'1');
			$new_rank->set_value('nonmandatory', 'usergroupid',			'0');

			#if($new_usergroup->is_valid())
			#{
				#$user_group_id = $new_usergroup->import_usergroup($Db_target, $target_database_type, $target_table_prefix);
				#if($user_group_id)
				#{
					#$new_rank->set_value('nonmandatory', 'usergroupid',	$user_group_id);
					if($new_rank->is_valid())
					{
						if($new_rank->import_rank($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $new_rank->how_complete() . '%</b></span>' . $displayobject->phrases['rank'] . ' -> ' . $new_rank->get_value('nonmandatory','rankimg'));
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $rank_id, $displayobject->phrases['rank_not_imported'], $displayobject->phrases['rank_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['rank_not_imported']}");
						}
					}
					else
					{
						$sessionobject->add_error($Db_target, 'invalid', $class_num, $rank_id, $displayobject->phrases['invalid_object'], $try->_failedon);
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					}
				#}
				#else
				#{
				#	$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				#	$sessionobject->add_error($user_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
				#	$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['rank_not_imported']}");
				#}
			#}
			#else
			#{
			#	$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			#	$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			#}
		}// End foreach

		// Check for page end
		if (count($ranks_array) == 0 OR count($ranks_array) < $ranks_per_page)
		{
			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num , '_time_taken'),
				$sessionobject->return_stats($class_num , '_objects_done'),
				$sessionobject->return_stats($class_num , '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('ranks','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('ranksstartat',$ranks_start_at+$ranks_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
