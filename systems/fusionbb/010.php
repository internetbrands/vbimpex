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
* fusionbb_010 Import Moderator module
*
* @package			ImpEx.fusionbb
*
*/
class fusionbb_010 extends fusionbb_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Moderator';

	function fusionbb_010()
	{
		// Constructor
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_moderators'))
				{
					$displayobject->display_now('<h4>Imported moderators have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_moderators','Check database permissions');
				}
			}

			// Start up the table
			$displayobject->update_basic('title','Import Moderator');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_moderator','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Moderators to import per cycle ','perpage', 500));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('startat','0');
			$sessionobject->add_session_var('moderatordone','0');
		}
		else
		{
			// Dependant has not been run
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
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at	= $sessionobject->get_session_var('startat');
		$per_page	= $sessionobject->get_session_var('perpage');
		$class_num	= substr(get_class($this) , -3);
		$idcache	= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of moderator details
		$moderator_array = $this->get_fusionbb_moderator_details($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);
		$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($moderator_array) . ' moderators</h4><p><b>From</b> : ' . $start_at . ' ::  <b>To</b> : ' . ($start_at + count($moderator_array)) . '</p>');

		$moderator_object = new ImpExData($Db_target, $sessionobject, 'moderator');

		foreach ($moderator_array as $moderator_id => $moderator_details)
		{
			$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));
			// Mandatory
			$try->set_value('mandatory', 'userid',					$idcache->get_id('user', $moderator_details['user_id']));
			$try->set_value('mandatory', 'forumid',					$forum_ids_array["$moderator_details[forum_id]"]);
			$try->set_value('mandatory', 'importmoderatorid',		$moderator_id);

			// Non Mandatory
			$try->set_value('nonmandatory', 'permissions',			$this->_default_mod_permissions);

			// Check if moderator object is valid
			if($try->is_valid())
			{
				if($try->import_moderator($Db_target, $t_db_type, $t_tb_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: moderator -> ' . $idcache->get_id('user', $moderator_details['user_id']));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar moderator and <b>DID NOT</b> imported to the  {$t_db_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid moderator object, skipping." . $try->_failedon);die;
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($moderator_array) == 0 OR count($moderator_array) < $per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_moderator','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('startat',$start_at+$per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : November 22, 2004, 6:52 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
