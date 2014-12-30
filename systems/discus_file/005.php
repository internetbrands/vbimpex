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
* discus_file Import Forums
*
* @package 		ImpEx.discus_file
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class discus_file_005 extends discus_file_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Categories';

	function discus_file_005()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Forums and Categories have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 $class_num . "::restart failed , clear_imported_forums",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import categories');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3) ,'WORKING'));
			$displayobject->update_html($displayobject->make_table_header('Import categories'));
			$displayobject->update_html($displayobject->make_hidden_code('forums','working'));
			$displayobject->update_html($displayobject->make_description("<p>Import the discus categories.</p>"));
			$displayobject->update_html($displayobject->make_description("Please note that private forums in discus will need to be set back to private in vBulletin, as forum permissions are not imported due to the difference in systems between discus and vBulletin"));

			$displayobject->update_html($displayobject->do_form_footer("Import categories"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('forumsstartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var($class_num ,'FALSE');
			$sessionobject->set_session_var('module','000');
		}

	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');


		$categories_start_at	= $sessionobject->get_session_var('categoriesstartat');
		$categories_per_page	= $sessionobject->get_session_var('categoriesperpage');

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Sort out the categories
		$categories_array = $this->get_discus_file_categories_details($sessionobject->get_session_var('messagesspath'));


		$displayobject->display_now("<h4>Importing " . count($categories_array) . " caterories</h4>");

		$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');
		
		foreach ($categories_array as $cat_id => $cat)
		{
			$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
			
			$try->set_value('mandatory', 'title', 				$cat['title']);
			$try->set_value('mandatory', 'displayorder',		$cat['displayorder']);
			$try->set_value('mandatory', 'parentid',			'-1');
			$try->set_value('mandatory', 'importforumid',		'0');
			$try->set_value('mandatory', 'importcategoryid',	$cat['catid']);
			$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);

			$try->set_value('nonmandatory', 'description', 		"Imported discus forum, origional id = {$forum_id} " .  $forum['description']);

			if($try->is_valid())
			{
				$vb_cat_id = $try->import_category($Db_target, $target_database_type, $target_table_prefix);
				
				if($vb_cat_id)
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$imported = true;
				}
				else
				{
					$sessionobject->add_error('warning',
											 $this->_modulestring,
											 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
											 'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);die;

			}
			unset($try);
			
			// Now get the forums for that cat
			
			$forums_array = $this->get_discus_file_forum_details($sessionobject->get_session_var('messagesspath'), $cat['catid']);

			foreach ($forums_array as $forum_id => $forum)
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				$try->set_value('mandatory', 'title', 				$forum['title']);
				$try->set_value('mandatory', 'displayorder',		$forum['displayorder']);
				
				$try->set_value('mandatory', 'parentid',			$vb_cat_id);
				
				$try->set_value('mandatory', 'importforumid',		$forum['forumid']);
				$try->set_value('mandatory', 'importcategoryid',	'0');
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$try->set_value('nonmandatory', 'description', 		"Imported discus forum, origional id = {$forum_id} " .  $forum['description']);
				$try->set_value('nonmandatory', 'visible', 			'1');

				if($try->is_valid())
				{
					if($try->import_forum($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
				}
				unset($try);
			}
			unset($vb_cat_id);
		}

		$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);

		$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

		$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																	$sessionobject->return_stats($class_num, '_time_taken'),
																	$sessionobject->return_stats($class_num, '_objects_done'),
																	$sessionobject->return_stats($class_num, '_objects_failed')
																	));

		$sessionobject->set_session_var($class_num, 'FINISHED');
		$sessionobject->set_session_var('forums', 'done');
		$sessionobject->set_session_var('module', '000');
		$sessionobject->set_session_var('autosubmit', '0');

		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}
}
/*======================================================================*/
?>
