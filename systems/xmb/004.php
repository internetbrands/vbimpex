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
* xmb_004 Import Forum module
*
* @package			ImpEx.xmb
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class xmb_004 extends xmb_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Forum';


	function xmb_004()
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
			$displayobject->update_html($displayobject->make_input_code('Forums to import per cycle (must be greater than 1)','forumperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('forumstartat','0');
			$sessionobject->add_session_var('forumdone','0');

			$sessionobject->add_session_var('categoriesfinished','FALSE');
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


		if($sessionobject->get_session_var('categoriesfinished') == 'FALSE')
		{
			// Sort out the categories
			$categories_array = $this->get_xmb_categories_details($Db_source, $source_database_type, $source_table_prefix);

			$displayobject->display_now("<h4>Importing " . count($categories_array) . " caterories</h4>");

			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($categories_array as $cat_id => $cat)
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));

				$try->set_value('mandatory', 'title', 				stripslashes($cat['name']));
				$try->set_value('mandatory', 'displayorder',		$cat['displayorder']);
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				$try->set_value('mandatory', 'importcategoryid',	$cat_id);

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_xmb_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}
			$sessionobject->add_session_var('categoriesfinished','TRUE');

			// Private Category
			$try = (phpversion() < '5' ? $category_object : clone($category_object));
			$try->set_value('mandatory', 'title', 				'Default XMB import Category');
			$try->set_value('mandatory', 'displayorder',		'1');
			$try->set_value('mandatory', 'parentid',			'-1');
			$try->set_value('mandatory', 'importforumid',		'0');
			$try->set_value('mandatory', 'importcategoryid',	'99999');
			$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
			$try->set_value('nonmandatory', 'description', 		'This is a default category for XMB imported forums');
			$try->import_category($Db_target, $target_database_type, $target_table_prefix);
			unset($try);

		}
		else
		{
			$cat_ids 		=  $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
			$forum_array 	= $this->get_xmb_forum_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at, $forum_per_page);


			$displayobject->display_now('<h4>Importing ' . count($forum_array) . ' forums</h4><p><b>From</b> : ' . $forum_start_at . ' ::  <b>To</b> : ' . ($forum_start_at + count($forum_array)) . '</p>');

			$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($forum_array as $forum_id => $forum_details)
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
				// Mandatory
				$try->set_value('mandatory', 'title',				stripslashes($forum_details['name']));
				$try->set_value('mandatory', 'displayorder',		$forum_details['displayorder']);
				if ($cat_ids["$forum_details[fup]"])
				{
					$try->set_value('mandatory', 'parentid',		$cat_ids["$forum_details[fup]"]);
				}
				else
				{
					$try->set_value('mandatory', 'parentid',		$cat_ids[99999]);
				}

				$try->set_value('mandatory', 'importforumid',		$forum_details['fid']);
				$try->set_value('mandatory', 'importcategoryid',	'0');
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				// Non Mandatory
				$try->set_value('nonmandatory', 'description',		stripslashes($forum_details['description']));
				$try->set_value('nonmandatory', 'replycount',		$forum_details['posts']);

				$last_stuff = explode('|',$forum_details['lastpost']);

				$try->set_value('nonmandatory', 'lastpost',			$last_stuff[0]);
				$try->set_value('nonmandatory', 'lastposter',		$last_stuff[1]);

				$try->set_value('nonmandatory', 'threadcount',		$forum_details['threads']);


				// Check if forum object is valid
				if($try->is_valid())
				{
					if($import_forum_id = $try->import_forum($Db_target, $target_database_type, $target_table_prefix))
					{
						$cat_ids[$forum_details['fid']] = $import_forum_id;
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: forum -> ' . $forum_details['name']);
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
						$displayobject->display_now("<br />Found avatar forum and <b>DID NOT</b> imported to the  {$target_database_type} database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
				}
				unset($try);
			}

			// Check for page end
			if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');


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
			}
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : June 23, 2004, 8:54 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
