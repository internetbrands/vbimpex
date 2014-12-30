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
* Ubb Import Ignore List
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_009 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Ignore List';

	function ubb_classic_009()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_ignore_list'))
				{
					$this->_restart = false;
					$displayobject->display_now("<h4>Imported Ignore lists messages have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_ignore_list",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import Ignore Lists');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('ignorelist','working'));
			$displayobject->update_html($displayobject->make_table_header("Step 10: Import Ignore Lists"));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like the page to continute till all Ignore lists are done ? Can help clicking if there are a lot","autosubmit",1));
			$displayobject->update_html($displayobject->make_input_code("Users to process per cycle (must be greater than 1)","ignorelistperpage",1000));
			$displayobject->update_html($displayobject->do_form_footer("Import Ignore Lists Lists",""));

			$sessionobject->add_session_var('ignoreliststartat','0');


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
		if ($sessionobject->get_session_var('ignorelist') == 'working')
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


			if (intval($sessionobject->get_session_var('ignorelistperpage')) < 1)
			{
				$sessionobject->set_session_var('ignorelistperpage','500');
			}

			$ignore_per_page 		= $sessionobject->get_session_var('ignorelistperpage');
			$ignore_start_at		= $sessionobject->get_session_var('ignoreliststartat');

			$vbuserid 	= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$users 		= $this->get_user_array($Db_target, $target_database_type, $target_table_prefix, $ignore_start_at, $ignore_per_page);

			$finished = false;

			if (count($users)>0 && $users != FALSE)
			{
				reset($users);

				foreach ($users as $counter => $user)
				{
					$validimport = FALSE;
					$displayobject->display_now("<p>$user[importuserid] - $user[username] ....");
					$ignorefile = $sessionobject->get_session_var('ubbmemberspath') . "/pm_ignore/" . str_pad($user[import_userid],8,0,STR_PAD_LEFT) . ".cgi";

					if (file_exists($ignorefile))
					{
						$displayobject->display_now("importing ignore list ....");
						$user[ignorelist] = $this->makelist($ignorefile, $vbuserid);
						$validimport = TRUE;
					}
					else
					{
						$displayobject->display_now("<i>no ignore list</i> ....");
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
							$sessionobject->add_session_var($class_num . '_objects_failed',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
							$sessionobject->add_error('warning',
													  $this->_modulestring,
													  get_class($this) . "::import_buddy_ignore failed for :: " . $ignorefile . " :: user :: " . $user,
													  'Check database permissions');
							$displayobject->display_now("Error with the dB Import");
						}
					}
					unset($user);
				}
			}
			else
			{
				$finished = TRUE;
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

			$displayobject->display_now('Ignore list import done.');
			$displayobject->update_basic('displaymodules','FALSE');
			$sessionobject->set_session_var('ignorelist','done');
			$sessionobject->set_session_var('autosubmit','0');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');

			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$sessionobject->set_session_var('ignoreliststartat',($ignore_start_at + $ignore_per_page));
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*/
?>
