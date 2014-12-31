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
* DCFm Import Moderators
*
* @package 		ImpEx.DCFm
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class DCFm_010 extends DCFm_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Moderators';

	function DCFm_012()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_moderators'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported moderators have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_moderators",
											 'Check database permissions and moderators table');
				}
			}
			$displayobject->update_basic('title','Import moderators');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('moderators','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Ranks'));
			$displayobject->update_html($displayobject->make_description('The importer will now start to import the moderators from your DCF board.'));
			$displayobject->update_html($displayobject->make_description('If the userid 0 is detected (the old admin) it will be set to user id 1, if this is incorrect please update afterwards.'));
			$displayobject->update_html($displayobject->make_input_code('Number of moderators to import per cycle','moderatorsperpage', 1000));
			$displayobject->update_html($displayobject->do_form_footer('Import Moderators',''));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('moderatorsstartat','0');
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
		if ($sessionobject->get_session_var('moderators') == 'working')
		{
			$displayobject->update_basic('displaymodules','FALSE');


			// Set up working variables.
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$moderators_start_at	= $sessionobject->get_session_var('moderatorsstartat');
			$moderators_per_page	= $sessionobject->get_session_var('moderatorsperpage');

			$class_num				= substr(get_class($this) , -3);
			$this->_dupe_checking 	= false;
			
			if(intval($moderators_per_page) == 0)
			{
				$moderators_per_page = 200;
			}

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$moderators_array 		= $this->get_DCFm_moderators_details($Db_source, $source_database_type, $source_table_prefix, $moderators_start_at, $moderators_per_page);
			$forumids_array			= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

			$last_pass 				= $sessionobject->get_session_var('last_pass');
			$moderator_object 		= new ImpExData($Db_target, $sessionobject,'moderator');


			$displayobject->display_now("<h4>Importing " . count($moderators_array) . " moderators</h4><p><b>From</b> : " . $moderators_start_at . " ::  <b>To</b> : " . ($moderators_start_at + count($moderators_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");
			$start = time();

			foreach ($moderators_array as $mod_id => $mod)
			{
				$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));

				if($mod['u_id'])
				{
					$try->set_value('mandatory', 'userid',				$users_ids["$mod[u_id]"]);
				}
				else
				{
					$try->set_value('mandatory', 'userid',				'1');
				}
				$try->set_value('mandatory', 'forumid',				$forumids_array["$mod[forum_id]"]);
				$try->set_value('mandatory', 'importmoderatorid',	$mod_id);

				if($try->is_valid())
				{
					if($try->import_moderator($Db_target,$target_database_type,$target_table_prefix))
					{
						$displayobject->display_now('<br /><b><font color="green">' . $try->how_complete() . '% </font></b>Imported moderator : </b>' . $user_names["$mod[u_id]"]);
						$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num . '_objects_done') + 1 );
					}
					else
					{
						$displayobject->display_now('<br />Imported pm_text, Error with importing moderator');
						$sessionobject->add_error('warning', $this->_modulestring,
									get_class($this) . "::import_moderator failed for " . $user_names["$mod[member_id]"],
									'Check database permissions and moderators table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid object, skipping.");
				}
				unset($try);
			}

			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($moderators_array) == 0 OR count($moderators_array) < $moderators_per_page)
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
				$sessionobject->set_session_var('moderators','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
			$sessionobject->set_session_var('moderatorsstartat',$moderators_start_at+$moderators_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('moderators','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',''));
		}
	}
}
/*======================================================================*/
?>
