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
* Ubb Import buddy List
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_008 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Buddy List';

	function ubb_classic_008()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_buddy_list'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Buddy lists messages have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_buddy_list",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('buddylist','working'));
			$displayobject->update_html($displayobject->make_table_header("Step 9: Import Buddy Lists"));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like the page to continute till all Buddy lists are done ?","autosubmit",1));
			$displayobject->update_html($displayobject->make_input_code("Users to process per cycle (must be greater than 1)","buddylistperpage",1000));
			$displayobject->update_html($displayobject->do_form_footer("Import Buddy Lists' Lists"));

			$sessionobject->add_session_var('buddyliststartat','0');
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
		if ($sessionobject->get_session_var('buddylist') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}


			if (intval($sessionobject->get_session_var('buddylistperpage')) < 1)
			{
				$sessionobject->set_session_var('buddylistperpage','500');
			}

			$buddy_per_page 		= $sessionobject->get_session_var('buddylistperpage');
			$buddy_start_at			= $sessionobject->get_session_var('buddyliststartat');

			$vbuserid = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$users = $this->get_user_array($Db_target, $target_database_type, $target_table_prefix, $buddy_start_at, $buddy_per_page);


			$finished = false;

			if (count($users)>0 && $users != FALSE)
			{
				reset($users);

				foreach ($users as $counter => $user)
				{
					$validimport = FALSE;
					$displayobject->display_now("<p>$user[importuserid] - $user[username] ....");
					$buddyfile = $sessionobject->get_session_var('ubbmemberspath') . "/pm_buddy/" . str_pad($user[import_userid],8,0,STR_PAD_LEFT) . ".cgi";

					if (file_exists($buddyfile))
					{
						$displayobject->display_now("importing buddy list ....");
						$user[buddylist] = $this->makelist($buddyfile,$vbuserid);
						$validimport = TRUE;
					}
					else
					{
						$displayobject->display_now("<i>no buddy list</i> ....");
					}

					if ($validimport)
					{
						if ($this->import_buddy_ignore($Db_target, $target_database_type, $target_table_prefix, $user))
						{
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							$displayobject->display_now("<b>Imported in the the dB correctly</b>");
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
							# TODO : JERRY Error handeling
							$displayobject->display_now("Error with the dB Import");
						}
					}

					unset($user);
				}
			}
			else
			{
				$finished = true;
			}
		}

		if ($finished)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num,'_time_taken'),
				$sessionobject->return_stats($class_num,'_objects_done'),
				$sessionobject->return_stats($class_num,'_objects_failed')
			));

			$displayobject->display_now('Buddy list import done.');
			$displayobject->update_basic('displaymodules','FALSE');
			$sessionobject->set_session_var('buddylist','done');
			$sessionobject->set_session_var('autosubmit','0');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');

			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$sessionobject->add_session_var('buddyliststartat',($sessionobject->get_session_var('buddyliststartat') + $sessionobject->get_session_var('buddylistperpage')));
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
