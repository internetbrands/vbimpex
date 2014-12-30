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
* wBB Import Forums and Categories
*
* @package 		ImpEx.wBB
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_006 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Forums and Categories';

	function wBB_006()
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

			$displayobject->update_basic('title','Import forums');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3) ,'WORKING'));
			$displayobject->update_html($displayobject->make_table_header('Import Forums'));
			$displayobject->update_html($displayobject->make_hidden_code('forums','working'));
			$displayobject->update_html($displayobject->make_description("<p>Import the wBB forums.</p>"));
			$displayobject->update_html($displayobject->make_description("Please note that private forums in wBB will need to be set back to private in vBulletin, as forum permissions are not imported due to the difference in systems between wBB and vBulletin"));
			$displayobject->update_html($displayobject->make_input_code("Forums to import per cycle (must be greater than 1)","forumsperpage",10));

			// TODO: Do we need to ask any questions here ?
			//$displayobject->update_html($displayobject->make_yesno_code("If the importer detects categories with no title, would you like to import those categories anyway?","doblankcats",0));
			$displayobject->update_html($displayobject->do_form_footer("Import Forums"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('forumsstartat','0');
			$sessionobject->add_session_var('categoriesfinished','FALSE');
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

		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		$forum_start_at			= $sessionobject->get_session_var('forumsstartat');
		$forum_per_page			= $sessionobject->get_session_var('forumsperpage');

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('categoriesfinished') == 'FALSE')
		{
			// Sort out the categories
			$categories_array = $this->get_wBB_categories_details($Db_source, $source_database_type, $source_table_prefix);

			$displayobject->display_now("<h4>Importing " . count($categories_array) . " caterories</h4>");

			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($categories_array as $cat_id => $cat)
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));

				$try->set_value('mandatory', 'title', 				$cat['title']);
				$try->set_value('mandatory', 'displayorder',		$cat['boardorder']);
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'importcategoryid',	$cat_id);
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);

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
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);

				}
				unset($try);
			}
			$sessionobject->add_session_var('categoriesfinished','TRUE');
		}
		else
		{
			// Sort out the forums
			$forum_array  	=  $this->get_wBB_forum_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at, $forum_per_page);
			$cat_ids 		=  $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->display_now("<h4>Importing " . count($forum_array) . " forums</h4><p><b>From</b> : " . $forum_start_at . " ::  <b>To</b> : " . ($forum_start_at + $forum_per_page) ."</p>");

			$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($forum_array as $forum_id => $forum)
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				$try->set_value('mandatory', 'title', 				$forum['title']);
				$try->set_value('mandatory', 'displayorder',		$forum['boardorder']);
				
				if($cat_ids[$forum['parentid']])
				{
					$try->set_value('mandatory', 'parentid',		$cat_ids[$forum['parentid']]);
				}
				else
				{
					$forum_ids = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
					$try->set_value('mandatory', 'parentid', 		$forum_ids["$forum[parentid]"]);		
				}
				
				if($try->get_value('mandatory', 'parentid') == 0)
				{
					$try->set_value('mandatory', 'parentid', 		'-1');
				}
				
				
				
				$try->set_value('mandatory', 'importforumid',		$forum['boardid']);
				$try->set_value('mandatory', 'importcategoryid',	'0');
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$new_parent_list = array();
				$parentids = explode(",",$forum['parentlist']);
				foreach($parentids as $wbb_parent_id)
				{
					$new_parent_list[] =  $cat_ids[$wbb_parent_id];
				}
				$new_parent_list = implode(",",$new_parent_list);

				$try->set_value('nonmandatory', 'parentlist',		$new_parent_list);

				$try->set_value('nonmandatory', 'description', 		$forum['description']);
				$try->set_value('nonmandatory', 'visible', 			$forum['invisible']);
				$try->set_value('nonmandatory', 'styleid',			$forum['styleid']);
				$try->set_value('nonmandatory', 'threadcount',		$forum['threadcount']);
				$try->set_value('nonmandatory', 'daysprune',		$forum['daysprune']);
				$try->set_value('nonmandatory', 'password',			$forum['password']);
				$try->set_value('nonmandatory', 'replycount',		$forum['postcount']);

				/*
				$try->set_value('mandatory', 'options',				$forum['']);
				$try->set_value('mandatory', 'lastpost',			$forum['']);
				$try->set_value('mandatory', 'lastposter',			$forum['']);
				$try->set_value('mandatory', 'lastthread',			$forum['']);
				$try->set_value('mandatory', 'lastthreadid',		$forum['']);
				$try->set_value('mandatory', 'lasticonid',			$forum['']);
				$try->set_value('mandatory', 'newpostemail',		$forum['']);
				$try->set_value('mandatory', 'newthreademail',		$forum['']);
				$try->set_value('mandatory', 'parentlist',			$forum['']);
				$try->set_value('mandatory', 'link',				$forum['']);
				$try->set_value('mandatory', 'childlist',			$forum['']);
				*/

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


			if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
			{
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
			}
			$sessionobject->set_session_var('forumsstartat',$forum_start_at+$forum_per_page);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
