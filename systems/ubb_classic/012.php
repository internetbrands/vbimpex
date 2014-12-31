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
* Ubb Import Moderators
*
* @package 		ImpEx.ubb_classic
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ubb_classic_012 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Moderators';

	function ubb_classic_012()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_moderators'))
				{
					$this->_restart = true;
					$displayobject->display_now("<h4>Imported moderators have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_users",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import users');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_users','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Users'));
			$displayobject->update_html($displayobject->make_input_code("Forums to check for moderators per cycle (must be greater than 1)","modsperpage",50));

			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			$sessionobject->add_session_var('modsperpage','0');
			$sessionobject->add_session_var('totalmodsdone','0');


			$sessionobject->add_session_var('modsstartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
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

		$user_start_at		= $sessionobject->get_session_var('modsstartat');
		$user_per_page		= $sessionobject->get_session_var('modsperpage');
		$idcache 			= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$ubbcgipath 		= $sessionobject->get_session_var('ubbcgipath');

		$class_num				= substr(get_class($this) , -3);


		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
		}
		
		$mod_array 		= $this->get_moderators_list($ubbcgipath, $user_start_at, $user_per_page);
		$forum_ids_array 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
	
		
		$moderator_object 	= new ImpExData($Db_target, $sessionobject, 'moderator');

		$displayobject->display_now("<h4>Importing  " . (count($mod_array)-1) . " Forums</h4><p><b>From</b> : " . $user_start_at . " ::  <b>To</b> : " . ($user_start_at + count($mod_array))  . "</p>");

		foreach ($mod_array as $forumid => $user_array)
		{
			foreach($user_array AS $userid)
			{
				$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));
				$userid = (intval($userid) == 0 ? 1 : intval($userid)); 

				$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $userid));
				$try->set_value('mandatory', 'forumid',				$forum_ids_array[$forumid]);
				$try->set_value('mandatory', 'importmoderatorid',	$userid);
				$try->set_value('nonmandatory', 'permissions',		$this->_default_mod_permissions);

				if($try->is_valid())
				{
					if($result = $try->import_moderator($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $idcache->get_id('username', $userid));
						flush;
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->add_error('warning', $this->_modulestring,
									 get_class($this) . "::import_user failed for " . trim($userarray[0]). ". getUserDetails was ok.",
									 'Check database permissions and user table');
						$displayobject->display_now("<br />Got user " . $try->get_value('mandatory','username') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid user object, skipping." . $try->_failedon);
				}
				unset($try);
			}
		}
		
		
		$timetaken = date('s' ,(time() - $start));

		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{

			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished(
				$this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('import_users','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$sessionobject->add_session_var('totalusersdone',($sessionobject->get_session_var('totalusersdone') + $doneperpass));
			$displayobject->update_html($displayobject->print_redirect('index.php',''));
			$sessionobject->set_session_var('usersstartat',$user_start_at+$user_per_page);
		}
	}

}
/*======================================================================*/
?>

