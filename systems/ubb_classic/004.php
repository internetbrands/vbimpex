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
* Ubb Import Ban List
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_004 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Ban List';

	function ubb_classic_004()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_ban_list'))
				{
					$this->_restart = FALSE;
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
			$displayobject->update_html($displayobject->do_form_header('index',"004"));
			$displayobject->update_html($displayobject->make_hidden_code('004','WORKING'));
			$displayobject->update_html($displayobject->make_table_header('Step 4: Import Ban List(s)'));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to import the banlist?","importbanlist",1));
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));
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
			$class_num	= substr(get_class($this) , -3);
			$displayobject->update_basic('displaymodules','FALSE');

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$emaillist 		= $this->get_list($sessionobject->get_session_var('ubbpath'). "/BanLists/EmailBan.cgi");
			$iplist    		= $this->get_list($sessionobject->get_session_var('ubbpath'). "/BanLists/IPBan.cgi");
			$namebansfull   = $this->get_list($sessionobject->get_session_var('ubbpath'). "/BanLists/NameBansFull.cgi");

			$done = 0;

			if($this->if_list($displayobject, $sessionobject,$emaillist,'emaillist'))
			{
				$this->do_list($Db_target, $displayobject, $sessionobject,$emaillist,'emaillist');
				$done++;
			}

			if($this->if_list($displayobject, $sessionobject,$iplist,'iplist'))
			{
				$this->do_list($Db_target, $displayobject, $sessionobject,$iplist,'iplist');
				$done++;
			}

			if($this->if_list($displayobject, $sessionobject,$namebansfull,'namebansfull'))
			{
				$this->do_list($Db_target, $displayobject, $sessionobject,$namebansfull,'namebansfull');
				$done++;
			}

			$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + $done );

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


	function get_list($file)
	{
		$return_array = array();
		if (file_exists($file))
		{
			$workingfile = file($file);
			while (list($key,$data)=each($workingfile))
			{
				$return_array[$data]= $data;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function if_list($displayobject,$sessionobject,$list,$type)
	{
		if ($list)
		{
			$displayobject->display_now("<br />Found and importing $type ban list ....");
			return true;
		}
		else
		{
			$displayobject->display_now("<br /><b>NO</b> $type ban list ....");
			$sessionobject->add_error('alert',
									  $this->_modulestring,
									  get_class($this) . "::doList failed , getting $type",
									  "This should be located at : " . $sessionobject->get_session_var('ubbpath'). "/BanLists/.....");
			return false;
		}
	}


	function do_list($Db_target,$displayobject,$sessionobject,$list,$type)
	{
		 $targetdatabasetype	= $sessionobject->get_session_var('targetdatabasetype');
		 $targettableprefix		= $sessionobject->get_session_var('targettableprefix');
		 
		$result = $this->import_ban_list($Db_target,
										 $targetdatabasetype,
										 $targettableprefix,
										 $list,
										 $type);
				if (!$result)
				{
					$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_faild',intval($sessionobject->get_session_var(substr(get_class($this) , -3) . '_objects_failed')) + 1 );
					$sessionobject->add_error('warning',
											 $this->_modulestring,
											 get_class($this) . "::import_ban_list failed on $type - $list",
											 "Check for format of the $type $list");
					$displayobject->update_html("<b>There was an error with the import of the $type ban list.</b>");
				}
				else
				{
					$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done',intval($sessionobject->get_session_var(substr(get_class($this) , -3) . '_objects_done')) + 1 );
					$displayobject->update_html("<br /><b>$type</b> Ban list <i>imported.</i>");
				}
	}
}
/*======================================================================*/
?>
