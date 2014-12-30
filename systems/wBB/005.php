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
* wBB Import Ban List
*
* @package 		ImpEx.wBB
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_005 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Ban List';

	function wBB_005()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_ban_list'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Ban Lists have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_ban_list",
											 'Check database permissions and ban table');
				}
			}

			$displayobject->update_basic('title','Import ban List');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header('Import Ban List(s)'));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the banlist?","importbanlist",1));
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var('004','FALSE');
			$sessionobject->set_session_var('module','000');
			$sessionobject->add_session_var('enablebanning','0');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($sessionobject->get_session_var('importbanlist'))
		{
			// Some working variables
			$sdt = $sessionobject->get_session_var('sourcedatabasetype');
			$stp = $sessionobject->get_session_var('sourcetableprefix');
			$class_num		= substr(get_class($this) , -3);

			$users_ids	= $this->get_user_ids($Db_target, $sessionobject->get_session_var('targetdatabasetype'), $sessionobject->get_session_var('targettableprefix'));

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			// Get the lists
			$userid 		= $this->get_list($Db_source, $sdt, $stp, 'userid');
			$iplist 		= $this->get_list($Db_source, $sdt, $stp, 'ip');
			$email 			= $this->get_list($Db_source, $sdt, $stp, 'email');
			$namefull		= $this->get_list($Db_source, $sdt, $stp, 'namebansfull');


			// If there is any thing
			if(count($userid) > 0)
			{
				$this->do_list($Db_target, $displayobject, $sessionobject, $userid, 'userid');
				$displayobject->display_now("<br /><b>Importing</b> userid ban list ....");
				$done++;
			}
			else
			{
				$displayobject->display_now("<br /><b>NO</b> userid ban list ....");
			}

			if(count($iplist) > 0)
			{
				$this->do_list($Db_target, $displayobject, $sessionobject, $iplist,'iplist');
				$displayobject->display_now("<br /><b>Importing</b> iplist ban list ....");
				$done++;
			}
			else
			{
				$displayobject->display_now("<br /><b>NO</b> iplist ban list ....");
			}

			if(count($email) > 0)
			{
				$this->do_list($Db_target, $displayobject, $sessionobject, $email,'emaillist');
				$displayobject->display_now("<br /><b>Importing</b> email ban list ....");
				$done++;
			}
			else
			{
				$displayobject->display_now("<br /><b>NO</b> email ban list ....");
			}

			if(count($namefull) > 0)
			{
				$this->do_list($Db_target, $displayobject, $sessionobject, $email,'namebansfull');
				$displayobject->display_now("<br /><b>Importing</b> name ban list ....");
				$done++;
			}
			else
			{
				$displayobject->display_now("<br /><b>NO</b> name ban list ....");
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
			$displayobject->update_html($displayobject->print_redirect('index.php','2'));
		}
		else
		{
			$displayobject->update_basic('displaymodules','FALSE');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('importbanlist','done');
			$sessionobject->set_session_var('module','000');

			$displayobject->display_now("You have skipped the Importing of the ban list");

			$displayobject->update_html($displayobject->print_redirect('index.php','2'));
		}
	}
}
/*======================================================================*/
?>
