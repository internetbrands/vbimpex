<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* yabb_gold_001 Check system module
*
* @package			ImpEx.yabb_gold
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yabb_gold_001 extends yabb_gold_000
{
	var $_version = "0.0.1";
	var $_modulestring 	= 'Check and update database';


	function yabb_gold_001()
	{
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$displayobject->update_basic('title','Get database information');
		$displayobject->update_html($displayobject->do_form_header('index','001'));
		$displayobject->update_html($displayobject->make_table_header('Get database information'));
		$displayobject->update_html($displayobject->make_hidden_code('database','working'));


		$displayobject->update_html($displayobject->make_description('This module modify the target tables in the database as well as check for required YaBB files.'));
		$displayobject->update_html($displayobject->make_input_code('Full Path to YaBB forum folder (The one with Members, Variables etc)','forumspath',$sessionobject->get_session_var('forumspath'),1,60));
		$displayobject->update_html($displayobject->make_input_code('Full Path to YaBB uploads folder.','uploadspath',$sessionobject->get_session_var('uploadspath'),1,60));

		$displayobject->update_html($displayobject->do_form_footer('Check database',''));
		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
	}


	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Setup some working variables
		$displayobject->update_basic('displaymodules','FALSE');
		$target_db_type 		= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix 	= $sessionobject->get_session_var('targettableprefix');
		$source_db_type			= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix 	= $sessionobject->get_session_var('sourcetableprefix');


		$class_num        = substr(get_class($this) , -3);
		$databasedone     = true;


		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		$displayobject->update_basic('title','Modifying database');
		$displayobject->display_now("<h4>Altering tables</h4>");
		$displayobject->display_now("<p>ImpEx will now Alter the tables in the vB database to include <i>import id numbers</i>.</p>");
		$displayobject->display_now("This is needed during the import process for maintaining refrences between the tables during an import.");
		$displayobject->display_now("If you have large tables (i.e. lots of posts) this can take some time.</p>");
		$displayobject->display_now("<p> They will also be left after the import if you need to link back to the origional vB userid.</p>");


		// Add an importids now
		foreach ($this->_import_ids as $id => $table_array)
		{
			foreach ($table_array as $tablename => $column)
			{
				if ($this->add_import_id($Db_target, $target_db_type, $target_table_prefix, $tablename, $column))
				{
					$displayobject->display_now("\n<br /><b>$tablename</b> - $column <i>OK</i>");
				}
				else
				{
					$sessionobject->add_error('fatal',
								$this->_modulestring,
								get_class($this) . "::resume failed trying to modify table $tablename to add $column",
								'Check database permissions');
					$databasedone = false;
				}
			}
		}


		// Set up a default group to put all the users into so the admin can do something
		// with them all later
		if($sessionobject->get_session_var('added_default_group') != 'yup')
		{
			$try = new ImpExData($Db_target, $sessionobject, 'usergroup');
			$try->set_value('mandatory', 'importusergroupid',		'69');
			$try->set_value('nonmandatory', 'title',				'Imported Users');
			$try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
			$sessionobject->add_session_var('added_default_group', 'yup');
			unset($try);
		}


		// Check the paths
		$fpath = $sessionobject->get_session_var('forumspath');
		if(substr($fpath, -1) == '/')
		{
			$fpath = substr($fpath,0,strlen($fpath)-1);
		}
		$sessionobject->add_session_var('forumspath',$fpath);

		$upath = $sessionobject->get_session_var('uploadspath');
		if(substr($upath, -1) == '/')
		{
			$upath = substr($upath,0,strlen($upath)-1);
		}
		$sessionobject->add_session_var('uploadspath',$upath);
		
		$forumspath = $sessionobject->get_session_var('forumspath');
		$uploadspath = $sessionobject->get_session_var('uploadspath');
		
		$databasedone =	$this->check_path($displayobject, $sessionobject, $forumspath);
		$databasedone =	$this->check_path($displayobject, $sessionobject, $uploadspath);

		if ($databasedone)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num,'_time_taken'),
				$sessionobject->return_stats($class_num,'_objects_done'),
				$sessionobject->return_stats($class_num,'_objects_failed')
			));

			$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FINISHED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_basic('displaymodules','FALSE');
			$displayobject->update_html($displayobject->print_redirect_001('index.php',$sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$sessionobject->add_session_var($class_num . '_objects_failed',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
			$displayobject->update_html($displayobject->make_description("{$displayobject->phrases['failed']} {$displayobject->phrases['check_db_permissions']}"));
			$sessionobject->set_session_var('001','FAILED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_html($displayobject->print_redirect_001('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}
}// End class
# Autogenerated on : August 12, 2004, 1:41 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
