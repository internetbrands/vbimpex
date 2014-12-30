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
* ipb Import Buddy lists
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
class ipb_010 extends ipb_000
{
	var $_dependent 	= '004';

	function ipb_010(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_banlist'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_ban_list'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>{$displayobject->phrases['banlist_cleared']}</h4>");
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_banlist']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['banlists_number'],'listperpage',500));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['banlists_per_page'], "importbanlist",1));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('liststartat', '0');
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

		$list_start_at 			= $sessionobject->get_session_var('liststartat');
		$list_per_page 			= $sessionobject->get_session_var('listperpage');

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if(intval($list_per_page) == 0)
		{
			$list_per_page = 150;
		}

		$list_array 		= $this->get_ipb_buddy_ignore_lists($Db_source, $source_database_type, $source_table_prefix, $list_start_at, $list_per_page);
		$users_ids 			= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names			= $this->get_username($Db_target, $target_database_type, $target_table_prefix);

		$buddy_ignore_array = array();

		// Put them all in an array
		foreach ($list_array as $buddy_id => $buddy)
		{
			if ($buddy['allow_msg'] == 1)
			{
				$buddy_ignore_array[$users_ids["$buddy[member_id]"]]['buddylist'] .= ' ' . $users_ids["$buddy[contact_id]"];
			}

			if ($buddy['allow_msg'] == 0)
			{
				$buddy_ignore_array[$users_ids["$buddy[member_id]"]]['ignorelist'] .= ' ' . $users_ids["$buddy[contact_id]"];
			}
		}

		// Go through it importing for each of the users.
		foreach ($buddy_ignore_array as $key => $value)
		{

			$value['userid'] = $key;

			if($this->import_buddy_ignore($Db_target, $target_database_type, $target_table_prefix, $value))
			{
				$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				$displayobject->display_now("<br /><font color=\"green\">{$displayobject->phrases['imported']} </font> <b>" . $user_names[$key] . "</b>");
			}
			else
			{
				// No error logging
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['failed']}");
			}
		}

		if (count($list_array) == 0 OR count($list_array) < $list_per_page)
		{
			$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num , '_time_taken'),
				$sessionobject->return_stats($class_num , '_objects_done'),
				$sessionobject->return_stats($class_num , '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		$sessionobject->set_session_var('liststartat',$list_start_at+$list_per_page);
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
