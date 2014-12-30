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
* vb3 Import Moderators
*
* @package 		ImpEx.vb3
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb3_012 extends vb3_000
{
	var $_dependent 	= '007';

	function vb3_012(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_moderator'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_moderators'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['moderators_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['moderator_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_moderator']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['moderators_per_page'],'moderatorperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('moderatorstartat','0');
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
		$displayobject->update_basic('displaymodules','FALSE');

		// Set up working variables.
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$moderator_start_at		= $sessionobject->get_session_var('moderatorstartat');
		$moderator_per_page		= $sessionobject->get_session_var('moderatorperpage');

		$class_num				= 	substr(get_class($this) , -3);
		$moderator_object 		= new ImpExData($Db_target, $sessionobject,'moderator');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		if(intval($moderator_per_page) == 0)
		{
			$moderator_per_page = 200;
		}

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$moderator_array 		= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $moderator_start_at, $moderator_per_page, 'moderator', 'moderatorid');
		$forumids_array			= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		$last_pass 				= $sessionobject->get_session_var('last_pass');


		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($moderator_array) . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $moderator_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($moderator_start_at + count($moderator_array)) . "</p>");

		foreach ($moderator_array as $mod_id => $details)
		{
			$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));

			// Mandatory
			# cache
			$try->set_value('mandatory', 'userid',					$idcache->get_id('user', $details['userid']));
			$try->set_value('mandatory', 'forumid',					$forumids_array["$details[forumid]"]);
			$try->set_value('mandatory', 'importmoderatorid',		$mod_id);

			// Non Mandatory
			$try->set_value('nonmandatory', 'permissions',			$details['permissions']);

			if($try->is_valid())
			{
				if($try->import_moderator($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['moderator'] . ' -> ' . $idcache->get_id('user', $details['username']));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$sessionobject->add_error($mod_id, $displayobject->phrases['moderator_not_imported'], $displayobject->phrases['moderator_not_imported_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['moderator_not_imported']}");
				}
			}
			else
			{
			$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}

		if (count($moderator_array) == 0 OR count($moderator_array) < $moderator_per_page)
		{
			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num , '_time_taken'),
				$sessionobject->return_stats($class_num , '_objects_done'),
				$sessionobject->return_stats($class_num , '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		$sessionobject->set_session_var('moderatorstartat',$moderator_start_at+$moderator_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zDCFmuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
