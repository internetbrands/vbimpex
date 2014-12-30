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
* DCFm_003 Import Users groups
*
*
* @package 		ImpEx.DCFm
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class DCFm_003 extends DCFm_000
{
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import User Groups';

	function DCFm_003()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_user_groups'))
				{
					$this->_restart = true;
					$displayobject->display_now("<h4>Imported user groups have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_non_admin_users",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import user groups');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_user_groups','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Users Groups'));
			$displayobject->update_html($displayobject->make_description('The importer will now import all the user groups.'));

			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
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

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get a page worths of users groups
		$user_groups_array  =  $this->get_DCFm_user_group_details($Db_source, $source_database_type, $source_table_prefix);

		// Got some numbers, lets try putting it in the dB
		$usergroup_object = new ImpExData($Db_target, $sessionobject,'usergroup');

		$displayobject->display_now("<h4>Importing " . count($user_groups_array) . " users groups</h4>");

		foreach ($user_groups_array as $user_group_id => $user_group)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));

			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',			$user_group_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'title',					$user_group['name']);

			if($try->is_valid())
			{
				if($try->import_user_group($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('nonmandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->add_error('warning', $this->_modulestring,
								 get_class($this) . "::import_user_group failed for " . $user_group['g_title'],
								 'Check database permissions and user table');
					$displayobject->display_now("<br />Got user " . $try->get_value('mandatory','username') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid object, skipping.");
			}
		}

		$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

		$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																	$sessionobject->return_stats($class_num ,'_time_taken'),
																	$sessionobject->return_stats($class_num ,'_objects_done'),
																	$sessionobject->return_stats($class_num ,'_objects_failed')
																	));
		$sessionobject->set_session_var($class_num ,'FINISHED');
		$sessionobject->set_session_var('import_user_groups','done');
		$sessionobject->set_session_var('module','000');
		$sessionobject->set_session_var('autosubmit','0');
		$displayobject->update_html($displayobject->print_redirect('index.php','1'));
	}
}
/*======================================================================*/
?>
