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
* phpBB2_005 Import Ban List
*
* @package 		ImpEx.phpBB2
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB2_005 extends phpBB2_000
{
	var $_dependent 	= '004';

	function phpBB2_005(&$displayobject)
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
					$displayobject->display_now("<h4>{$displayobject->phrases['banlist_cleared']}</h4>");
					$this->_restart = true;
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

			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['banlists_per_page'], "importbanlist",1));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
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
		// Some working variables
		$sdt = $sessionobject->get_session_var('sourcedatabasetype');
		$stp = $sessionobject->get_session_var('sourcetableprefix');
		$tdt = $sessionobject->get_session_var('targetdatabasetype');
		$ttp = $sessionobject->get_session_var('targettableprefix');
		$class_num = substr(get_class($this) , -3);

		$users_ids = $this->get_user_ids($Db_target, $tdt, $ttp);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get the lists
		$userid 		= $this->get_list($Db_source, $sdt, $stp, 'userid');
		$iplist 		= $this->get_list($Db_source, $sdt, $stp, 'ip');
		$email 			= $this->get_list($Db_source, $sdt, $stp, 'email');

		// If there is any thing
		if(count($userid) > 0)
		{
			$vBulletin_userid = array();
			$users_ids = $this->get_user_ids($Db_target, $tdt, $ttp);

			foreach ($userid AS $ban_id => $phpBB_userid)
			{
				$new_user = $users_ids[$phpBB_userid];
				$vBulletin_userid[$new_user] = 0;
			}

			$this->do_list($Db_target, $displayobject, $sessionobject, $userid, 'userid');
			$displayobject->display_now("<br><br>{$displayobject->phrases['continue']} {$displayobject->phrases['useridban']}");
			$done++;
		}
		else
		{
			$displayobject->display_now("<br><br>{$displayobject->phrases['failed']} {$displayobject->phrases['useridban']}");
		}

		if(count($iplist) > 0)
		{
			foreach ($iplist as $id => $old_ip)
			{
				$new_ip = $this->reverse_ip($old_ip);
				$new_ips[] = $new_ip;
			}

			$this->do_list($Db_target, $displayobject, $sessionobject, $new_ips,'iplist');
			$displayobject->display_now("<br><br>{$displayobject->phrases['continue']} {$displayobject->phrases['ipban']}");
			$done++;
		}
		else
		{
			$displayobject->display_now("<br><br>{$displayobject->phrases['failed']} {$displayobject->phrases['ipban']}");
		}

		if(count($email) > 0)
		{
			$this->do_list($Db_target, $displayobject, $sessionobject, $email,'emaillist');
			$displayobject->display_now("<br><br>{$displayobject->phrases['continue']} {$displayobject->phrases['emailban']}");
			$done++;
		}
		else
		{
			$displayobject->display_now("<br><br>{$displayobject->phrases['failed']} {$displayobject->phrases['emailban']}");
		}

		$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + $done);

		$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');
		$displayobject->update_basic('displaymodules','FALSE');

		$displayobject->update_html($displayobject->module_finished($this->_modulestring,
			$sessionobject->return_stats($class_num,'_time_taken'),
			$sessionobject->return_stats($class_num,'_objects_done'),
			$sessionobject->return_stats($class_num,'_objects_failed')
		));

		$displayobject->update_html($displayobject->make_hidden_code('importbanlist','done'));
		$sessionobject->set_session_var($class_num,'FINISHED');
		$sessionobject->set_session_var('module','000');

		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
