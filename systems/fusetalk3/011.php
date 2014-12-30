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
* fusetalk3_011 Import Author Icons module
*
* @package			ImpEx.fusetalk
* @date				$Date: 2006-04-03 01:46:42 -0700 (Mon, 03 Apr 2006) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class fusetalk3_011 extends fusetalk3_000
{
	var $_version 		= '0.0.1';
	var $_modulestring 	= 'Import User Avatar Settings';
	var $_dependent 	= '010';

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
				{
					$displayobject->display_now('<h4>Imported icons have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_attachments','Check database permissions');
				}
			}
	

			// Start up the table
			$displayobject->update_basic('title','Import User Avatars');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('User Icons to import per cycle (must be greater than 1)','usersperpage',2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('usersdone','0');
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Per page vars
		$user_start_at			= $sessionobject->get_session_var('userstartat');
		$user_lastid			= $sessionobject->get_session_var('user_lastid');
		$users_per_page			= $sessionobject->get_session_var('usersperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of icon details
		$user_array = $this->get_fusetalk_authoricon_usage($Db_source, $source_database_type, $source_table_prefix, $user_lastid, $users_per_page);
		
		foreach ($user_array as $iuserid => $iiconid)
		{
			$result = $this->update_user_avatar($Db_target, $target_database_type, $target_table_prefix,  $iuserid, $iiconid);
			
			if ($result)
			{
				$displayobject->display_now("<br />\nSet avatar for imported user #$iuserid, $result");
				$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num. '_objects_done') + 1 );
			}
			else
			{
				$displayobject->display_now("<br />\nUnable to set avatar for imported user #$iuserid, $result");
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			
		}// End foreach

		// Check for page end
		if (count($user_array) == 0 OR count($user_array) < $attachment_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));
			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_usericon','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}

		$sessionobject->add_session_var('user_lastid', $iuserid);
		$sessionobject->set_session_var('userstartat',$user_start_at + $users_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}
/*======================================================================*/
?>
