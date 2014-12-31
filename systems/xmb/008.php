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
* xmb_008 Import Ranks module
*
* @package			ImpEx.xmb
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class xmb_008 extends xmb_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Ranks';


	function xmb_008()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_ranks'))
				{
					$displayobject->display_now('<h4>Imported rankss have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_ranks','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Ranks');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_ranks','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Rankss to import per cycle (must be greater than 1)','ranksperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('ranksstartat','0');
			$sessionobject->add_session_var('ranksdone','0');
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
		$ranks_start_at			= $sessionobject->get_session_var('ranksstartat');
		$ranks_per_page			= $sessionobject->get_session_var('ranksperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of ranks details
		$ranks_array 	= $this->get_xmb_ranks_details($Db_source, $source_database_type, $source_table_prefix, $ranks_start_at, $ranks_per_page);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($ranks_array) . ' rankss</h4><p><b>From</b> : ' . $ranks_start_at . ' ::  <b>To</b> : ' . ($ranks_start_at + count($ranks_array)) . '</p>');


		$ranks_object = new ImpExData($Db_target, $sessionobject, 'ranks');


		foreach ($ranks_array as $ranks_id => $ranks_details)
		{
			$try = (phpversion() < '5' ? $ranks_object : clone($ranks_object));

			// Mandatory
			$try->set_value('mandatory', 'importrankid',		$ranks_details['id']);


			// Non Mandatory
			$try->set_value('nonmandatory', 'minposts',			'0');
			$try->set_value('nonmandatory', 'ranklevel',		'0');
			$try->set_value('nonmandatory', 'rankimg',			'0');
			$try->set_value('nonmandatory', 'usergroupid',		'0');
			$try->set_value('nonmandatory', 'type',				'0');


			// Check if ranks object is valid
			if($try->is_valid())
			{
				if($try->import_rank($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: ranks -> ' . $ranks_details['title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar ranks and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid ranks object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End resume


		// Check for page end
		if (count($ranks_array) == 0 OR count($ranks_array) < $ranks_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_ranks','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}


		$sessionobject->set_session_var('ranksstartat',$ranks_start_at+$ranks_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : June 23, 2004, 8:54 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
