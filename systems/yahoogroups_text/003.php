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
* yahoogroups_text_003 Import Forum module
*
* @package			ImpEx.yahoogroups_text
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yahoogroups_text_003 extends yahoogroups_text_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '002';
	var $_modulestring 	= 'Import Forum';


	function yahoogroups_text_003()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_forums'))
				{
					$displayobject->display_now('<h4>Imported forums have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_forums','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Forum');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_forum','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			#$displayobject->update_html($displayobject->make_input_code('Forums to import per cycle (must be greater than 1)','forumperpage',50));
			$displayobject->update_html($displayobject->make_description("<p>ImpEx will import a default forum for the data.</p>"));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('forumstartat','0');
			$sessionobject->add_session_var('forumdone','0');
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
		$forum_start_at			= $sessionobject->get_session_var('forumstartat');
		$forum_per_page			= $sessionobject->get_session_var('forumperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		######################################
		#	Temp
		######################################

		$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

		$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
		
		$try->set_value('mandatory', 'title', 				'Default Yahoo Category');
		$try->set_value('mandatory', 'displayorder',		'1');
		$try->set_value('mandatory', 'parentid',			'-1');
		$try->set_value('mandatory', 'importforumid',		'0');
		$try->set_value('mandatory', 'importcategoryid',	'1');
		$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
		$try->set_value('nonmandatory', 'description', 		'Default Yahoo Category Description');

		$cat_id = $try->import_category($Db_target, $target_database_type, $target_table_prefix);

		$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
		$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );

		unset($try);

		$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

		$try->set_value('mandatory', 'title', 				'Default Yahoo Forum');
		$try->set_value('mandatory', 'displayorder',		'1');
		$try->set_value('mandatory', 'parentid',			$cat_id);
		$try->set_value('mandatory', 'importforumid',		'1');
		$try->set_value('mandatory', 'importcategoryid',	'0');


		$try->set_value('nonmandatory', 'description', 		'Default Yahoo Forum');
		$try->set_value('nonmandatory', 'visible', 			'1');
		$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

		$forum_id = $try->import_forum($Db_target, $target_database_type, $target_table_prefix);

		$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
		$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );

		unset($try);
		
		$try = new ImpExData($Db_target, $sessionobject, 'thread');

		$try->set_value('mandatory', 'title',				'Yahoo catchments thread');
		$try->set_value('mandatory', 'forumid',				$forum_id);
		$try->set_value('mandatory', 'importthreadid',		'2');
		$try->set_value('mandatory', 'importforumid',		'1');


		// Non Mandatory
		$try->set_value('nonmandatory', 'visible',			'1');
		$try->set_value('nonmandatory', 'open',				'1');
		$try->set_value('nonmandatory', 'dateline',			time());

		$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
		$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );

		$thread_id = $try->import_thread($Db_target, $target_database_type, $target_table_prefix);

		$sessionobject->set_session_var('catch_thread', $thread_id);

		######################################
		#	Temp
		######################################

		// Check for page end
		if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');


			$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			$this->clean_nested_forums($Db_target, $target_database_type, $target_table_prefix,$forum_ids_array);
			$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
			));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_forum','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : November 25, 2004, 12:31 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

