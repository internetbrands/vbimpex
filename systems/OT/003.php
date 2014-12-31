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
* OT Import Forums
*
* @package 		ImpEx.OT
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class OT_003 extends OT_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '002';
	var $_modulestring 	= 'Import Forums and Categories';

	function OT_003()
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
			$displayobject->update_html($displayobject->make_table_header('Import OpenTopic Categorys and Forums'));
			$displayobject->update_html($displayobject->make_hidden_code('forums','working'));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like to skip inactive forums ?","skip_inavtive",1));

			// TODO: Do we need to ask any questions here ?
			//$displayobject->update_html($displayobject->make_yesno_code("If the importer detects categories with no title, would you like to import those categories anyway?","doblankcats",0));
			$displayobject->update_html($displayobject->do_form_footer("Import Forums"));

			$sessionobject->add_session_var('forumsperpage', '10');
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

		$forumsxmlfile 			= $sessionobject->get_session_var('forumsxmlfile');
		$membersxmlfile 		= $sessionobject->get_session_var('membersxmlfile');

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$file_handle = fopen($sessionobject->get_session_var('forumsxmlfile'), 'r');

		$categories_array = array();
		$type = 'category';
		$categories_array = $this->get_OT_details($file_handle, $forum_start_at, $forum_per_page, $categories_array, $type);
		$sessionobject->set_session_var('forumsstartat', $categories_array['pointer_position']);

		#$displayobject->display_now("<h4>Importing " . count($categories_array)-1 . " caterories</h4>");


		$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

		foreach ($categories_array as $cat_id => $cat)
		{
			if($cat_id == 'pointer_position')
			{
				break;
			}

			if(!$this->category_exsists($Db_target, $target_database_type, $target_table_prefix, trim($cat['CATEGORY_NAME'])))
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				$try->set_value('mandatory', 'title', 				$cat['CATEGORY_NAME']);
				$try->set_value('mandatory', 'displayorder',		intval(substr($cat['THREADING_ORDER'], 0, 6)));
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				// Not reall the import id though
				$try->set_value('mandatory', 'importcategoryid',	$cat['FORUM_OID']);

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> Category :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['CATEGORY_NAME'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['CATEGORY_NAME'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);

				}
				unset($try);
			}

			// Sort out the forums
			$cat_ids =  $this->get_OT_category_ids($Db_target, $target_database_type, $target_table_prefix);

			$try = (phpversion() < '5' ? $forum_object : clone($forum_object));


			$try->set_value('mandatory', 'title', 				$cat['FORUM_NAME']);
			$try->set_value('mandatory', 'displayorder',		intval(substr($cat['THREADING_ORDER'],6)));

			$try->set_value('mandatory', 'parentid',			$cat_ids["$cat[CATEGORY_NAME]"]);
			$try->set_value('mandatory', 'importforumid',		$cat['FORUM_OID']);
			$try->set_value('mandatory', 'importcategoryid',	'0');
			$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);


			$try->set_value('nonmandatory', 'description', 		substr($cat['FORUM_DESCRIPTION'], 9, -3));

			/*
			/home/jerry/sql_back_up/OT/monsters/www.massmonsters.com_members_export.xml
			/home/jerry/sql_back_up/OT/monsters
			$try->set_value('mandatory', 'styleid','');
			$try->set_value('mandatory', 'options','');
			$try->set_value('mandatory', 'replycount','');
			$try->set_value('mandatory', 'lastpost','');
			$try->set_value('mandatory', 'lastposter','');
			$try->set_value('mandatory', 'lastthread','');
			$try->set_value('mandatory', 'lastthreadid','');
			$try->set_value('mandatory', 'lasticonid','');
			$try->set_value('mandatory', 'threadcount','');
			$try->set_value('mandatory', 'daysprune','');
			$try->set_value('mandatory', 'newpostemail','');
			$try->set_value('mandatory', 'newthreademail','');
			$try->set_value('mandatory', 'parentlist','');
			$try->set_value('mandatory', 'password','');
			$try->set_value('mandatory', 'link','');
			$try->set_value('mandatory', 'childlist','');
			*/

			// Options
			/*
			[IS_MESSAGE_FEEDBACK_ENABLED] => Y
			[IS_SHOWING_MESSAGE_VIEW_COUNTS] => Y
			[IS_FORUM_ENABLED] => Y
			[IS_FORUM_READ_ONLY] => N
			[IS_UBB_CODE_ALLOWED] => Y
			[IS_UBB_CODE_IMAGES_ALLOWED] => Y
			[IS_TOPICS_ALLOWED] => Y
			[IS_TOPICS_MODERATED] => N
			[IS_REPLIES_MODERATED] => N
			[IS_ATTACHMENTS_MODERATED] => N
			[IS_MODERATION_LIVE] => N
			[IS_SIGNATURE_ENABLED] => Y
			[IS_ICON_POSTING_ENABLED] => Y
			[IS_POLLING_ENABLED] => Y
			[CONVERT_SMILIE_TO_GRAPHIC] => Y
			[IS_MESSAGE_ATTACHMENT_ALLOWED] => N
			[IS_IMAGE_SHOWN_WITH_POSTS] => Y
			[IS_ANY_ATTACHMENT_ALLOWED] => N
			[IS_IMAGE_ATTACHMENT_ALLOWED] => N
			[IS_ZIP_ATTACHMENT_ALLOWED] => N
			[IS_TEXT_ATTACHMENT_ALLOWED] => Y
			[ATTACHMENT_BYTE_LIMIT] => 307200
			[IS_USER_ABLE_TO_EDIT] => Y
			[IS_USER_ABLE_TO_DELETE] => Y
			[IS_USER_ABLE_TO_CLOSE_TOPICS] => N
			[MINUTE_LIMIT_ON_CHANGES] => 60
			[ENABLE_T_NOTIF_FOR_AUTHORS] => Y
			[MAX_TOPICS_PER_PAGE] => 50
			[MAX_MESSAGES_PER_PAGE] => 20
			*/
			if($sessionobject->get_session_var('skip_inavtive') AND $this->option2bin($cat['IS_FORUM_ENABLED']) == 'N')
			{
				$displayobject->display_now("<p><b>Skipping</b> :: Inactive forum.</ p>");
			}
			else
			{
				if($try->is_valid())
				{
					if($try->import_forum($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> Forum :: " . $try->get_value('mandatory','title'));
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
		} // foreach

		if (count($categories_array) == 0 OR count($categories_array) < $forum_per_page)
		{
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

		$sessionobject->add_session_var('totalusersdone',($sessionobject->get_session_var('totalusersdone') + $doneperpass));
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}
}
/*======================================================================*/
?>
