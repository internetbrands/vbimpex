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
* Ubb Get paths and system data
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
class ubb_classic_001 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_modulestring 	= 'Get paths and system data';

	function ubb_classic_001()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$displayobject->update_basic('title','Get paths and data information');
		$displayobject->update_html($displayobject->do_form_header('index','001'));
		$displayobject->update_html($displayobject->make_table_header('Get paths and alter database'));
		$displayobject->update_html($displayobject->make_hidden_code('pathdata','working'));
		$displayobject->update_html($displayobject->make_input_code('Full Path to UBB.Classic (non-cgi) folder','ubbpath',$sessionobject->get_session_var('ubbpath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('Full Path to UBB.Classic CGI variables folder','ubbcgipath',$sessionobject->get_session_var('ubbcgipath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('Full Path to UBB.Classic Members folder','ubbmemberspath',$sessionobject->get_session_var('ubbmemberspath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('Full Path to UBB.Classic custom smilies/graemlins folder','ubbgraemlinspath',$sessionobject->get_session_var('ubbgraemlinspath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('Full Path to UBB.Classic poll folder','pollspath',$sessionobject->get_session_var('pollspath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('<b>Relative</b> Path from vBulletin folder to UBB.Classic non-cgi folder','ubbrelativepath',$sessionobject->get_session_var('ubbrelativepath'),1,60));
		$displayobject->update_html($displayobject->do_form_footer('Start Import','Reset Paths'));

		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done','0');
		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed','0');

	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$displayobject->update_basic('displaymodules','FALSE');
		if ($sessionobject->get_session_var('pathdata')=='working')
		{
			// Setup some working variables
			$target_db_type 		= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix 	= $sessionobject->get_session_var('targettableprefix');
			$source_db_type			= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix 	= $sessionobject->get_session_var('sourcetableprefix');

			$class_num				= substr(get_class($this) , -3);
			$databasedone 			= true;

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			$displayobject->update_basic('title','Modifying database');
			$displayobject->display_now("<h4>Altering tables</h4>");
			$displayobject->display_now("<p>ImpEx will now Alter the tables in the vB database to include <i>import id numbers</i>.</p>");
			$displayobject->display_now("This is needed during the import process for maintaining refrences between the tables during an import.");
			$displayobject->display_now("If you have large tables (i.e. lots of posts) this can take some time.</p>");
			$displayobject->display_now("<p> They will also be left after the import if you need to link back to the origional ubb userid.</p>");


			// Add an importids now
			foreach ($this->_import_ids as $id => $table_array)
			{
				foreach ($table_array as $tablename => $column)
				{
					if ($this->add_import_id($Db_target, $target_db_type, $target_table_prefix, $tablename, $column))
					{
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$displayobject->display_now("\n<br /><b>$tablename</b> - $column <i>OK</i>");
					}
					else
					{
						$sessionobject->add_session_var($class_num . '_objects_failed',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
						$sessionobject->add_error('fatal',
									$this->_modulestring,
									get_class($this) . "::resume failed trying to modify table poll to add importpollid",
									'Check database permissions and forum table');
						$databasedone = false;
					}
				}
			}

			$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

			// Set up a default group to put all the users into so the admin can do something
			// with them all later
			if($sessionobject->get_session_var('added_default_banned_group') != 'yup')
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
				$try->set_value('mandatory', 'importusergroupid',		'69');
				$try->set_value('nonmandatory', 'title',				'Imported Banned Users');
				$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
				$sessionobject->add_session_var('added_default_banned_group', 'yup');
				unset($try);
			}

			if($sessionobject->get_session_var('added_default_admin_group') != 'yup')
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
				$try->set_value('mandatory', 'importusergroupid',		'70');
				$try->set_value('nonmandatory', 'title',				'Imported Admin Users');
				$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
				$sessionobject->add_session_var('added_default_banned_group', 'yup');
				unset($try);
			}

			if($sessionobject->get_session_var('added_default_registered_group') != 'yup')
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
				$try->set_value('mandatory', 'importusergroupid',		'71');
				$try->set_value('nonmandatory', 'title',				'Imported Registered Users');
				$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
				$sessionobject->add_session_var('added_default_banned_group', 'yup');
				unset($try);
			}

			if($sessionobject->get_session_var('added_default_coppa_group') != 'yup')
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
				$try->set_value('mandatory', 'importusergroupid',		'72');
				$try->set_value('nonmandatory', 'title',				'Imported COPPA Users');
				$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
				$sessionobject->add_session_var('added_default_coppa_group', 'yup');
				unset($try);
			}

			if($sessionobject->get_session_var('added_default_unknown_group') != 'yup')
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
				$try->set_value('mandatory', 'importusergroupid',		'73');
				$try->set_value('nonmandatory', 'title',				'Imported Unknown Users');
				$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
				$sessionobject->add_session_var('added_default_unknown_group', 'yup');
				unset($try);
			}

			$ubbpath 			= $sessionobject->get_session_var('ubbpath');
			$ubbcgipath 		= $sessionobject->get_session_var('ubbcgipath');
			$ubbmemberspath 	= $sessionobject->get_session_var('ubbmemberspath');
			$ubbrelativepath 	= $sessionobject->get_session_var('ubbrelativepath');
			$ubbgraemlinspath 	= $sessionobject->get_session_var('ubbgraemlinspath');
			$pollspath 			= $sessionobject->get_session_var('pollspath');

			 
			$databasedone =	$this->check_path($displayobject, $sessionobject, $ubbpath);
			$databasedone =	$this->check_path($displayobject, $sessionobject, $ubbcgipath);
			$databasedone =	$this->check_path($displayobject, $sessionobject, $ubbmemberspath);
			$databasedone =	$this->check_path($displayobject, $sessionobject, $ubbrelativepath);
			$databasedone =	$this->check_path($displayobject, $sessionobject, $ubbgraemlinspath);
			$databasedone =	$this->check_path($displayobject, $sessionobject, $pollspath);

			if(is_file($sessionobject->get_session_var('ubbpath') . '/categories.file'))
			{
				$databasedone =	$this->check_file($displayobject, $sessionobject, $ubbpath . '/categories.file');
			}
			else
			{
				$databasedone = $this->check_file($displayobject, $sessionobject, $ubbcgipath .'/vars_cats.cgi');
			}

			$databasedone =	$this->check_file($displayobject, $sessionobject, $ubbcgipath .'/vars_display.cgi');
			$databasedone =	$this->check_file($displayobject, $sessionobject, $ubbcgipath .'/vars_template_match.cgi');
			$databasedone =	$this->check_file($displayobject, $sessionobject, $ubbcgipath .'/vars_forums.cgi');


			if ($databasedone)
			{
				$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num, 'FINISHED');
				$sessionobject->set_session_var('pathdata','done');
				$sessionobject->set_session_var('module','000');
				$displayobject->update_basic('displaymodules','FALSE');
				$displayobject->update_html($displayobject->print_redirect_001('index.php','10'));
			}
			else
			{
				$displayobject->update_html($displayobject->make_description("<p><b>ERROR</b> with the database or file system, please check config.</p>"));
				$displayobject->update_html($displayobject->make_hidden_code('pathdata','done'));
				$sessionobject->set_session_var('001','FAILED');
				$sessionobject->set_session_var('module','000');
			}
		}
		else
		{
			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('pathdata','done');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_basic('displaymodules','FALSE');
			$displayobject->update_html($displayobject->print_redirect_001('index.php','1'));
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
