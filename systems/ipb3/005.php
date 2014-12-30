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
* ipb3_005 Import Forum module
*
* @package			ImpEx.ipb3
* @version			$Revision: 1825 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2007-08-16 19:01:27 -0700 (Thu, 16 Aug 2007) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ipb3_005 extends ipb3_000
{
	var $_dependent 	= '001';

	function ipb3_005(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_forum'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['forums_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['forum_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_forum']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['forums_per_page'],'forumperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

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
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
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
			$categories_array = $this->get_ipb3_categories_details($Db_source, $source_database_type, $source_table_prefix);

			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($categories_array) . " {$displayobject->phrases['categories']}</h4>");

			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($categories_array as $cat_id => $cat)
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));

				$try->set_value('mandatory', 'title', 				$cat['name']);
				$try->set_value('mandatory', 'displayorder',		$cat['position']);
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'importcategoryid',	$cat_id);
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				$try->set_value('nonmandatory', 'description', 		$cat['description']);

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $try->get_value('mandatory', 'title'));
						$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $cat_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $cat_id, $displayobject->phrases['invalid_object'], $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}

			// Private Category
			$try = (phpversion() < '5' ? $category_object : clone($category_object));
			$try->set_value('mandatory', 'title', 				'Order the forums found here.');
			$try->set_value('mandatory', 'displayorder',		'1');
			$try->set_value('mandatory', 'parentid',			'-1');
			$try->set_value('mandatory', 'importforumid',		'0');
			$try->set_value('mandatory', 'importcategoryid',	'99999');
			$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
			$try->set_value('nonmandatory', 'description', 		'This is a default category for forums that need ordering');
			$try->import_category($Db_target, $target_database_type, $target_table_prefix);
			unset($try);

			$sessionobject->add_session_var('categoriesfinished','TRUE');
		}
		else
		{
			// Sort out the forums
			$forum_array  	=  $this->get_ipb3_forum_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at, $forum_per_page);
			$cat_ids 		=  $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($forum_array) . " {$displayobject->phrases['forums']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $forum_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($forum_start_at + count($forum_array)) . "</p>");

			$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($forum_array as $forum_id => $forum)
			{
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				$forumoptions = 0;
				// Need to parse the permissions array
				if($usergroup_details['status'])					{ $forumoptions += 1;}
				#if($usergroup_details['allowposting'])				{ $forumoptions += 2;}
				#if($usergroup_details['cancontainthreads'])		{ $forumoptions += 4;}
				#if($usergroup_details['moderatenewpost'])			{ $forumoptions += 8;}
				#if($usergroup_details['moderatenewthread'])		{ $forumoptions += 16;}
				#if($usergroup_details['moderateattach'])			{ $forumoptions += 32;}
				if($usergroup_details['use_ibc'])					{ $forumoptions += 64;}
				#if($usergroup_details['allowimages'])				{ $forumoptions += 128;}
				if($usergroup_details['use_html'])					{ $forumoptions += 256;}
				#if($usergroup_details['allowsmilies'])				{ $forumoptions += 512;}
				#if($usergroup_details['allowicons'])				{ $forumoptions += 1024;}
				#if($usergroup_details['allowratings'])				{ $forumoptions += 2048;}
				#if($usergroup_details['countposts'])				{ $forumoptions += 4096;}
				#if($usergroup_details['canhavepassword'])			{ $forumoptions += 8192;}
				#if($usergroup_details['indexposts'])				{ $forumoptions += 16384;}
				#if($usergroup_details['styleoverride'])			{ $forumoptions += 32768;}
				#if($usergroup_details['showonforumjump'])			{ $forumoptions += 65536;}
				#if($usergroup_details['warnall'])					{ $forumoptions += 131072;}

				$try->set_value('mandatory', 'title', 				$forum['name']);
				$try->set_value('mandatory', 'displayorder',		$forum['position']);

				if ($cat_ids["$forum[parent_id]"])
				{
					$try->set_value('mandatory', 'parentid',		$cat_ids["$forum[parent_id]"]);
				}
				else
				{
					$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

					if($forum_ids_array["$forum[parent_id]"])
					{
						$try->set_value('mandatory', 'parentid',	$forum_ids_array["$forum[parent_id]"]);
					}
					else
					{
						// Invalid parent
						$try->set_value('mandatory', 'parentid',	$cat_ids[99999]);
					}
				}

				$try->set_value('mandatory', 'importforumid',		$forum['id']);

				// We might be importing a thread where we can't get the parent so store it here for later
				$try->set_value('mandatory', 'importcategoryid',	'0');

				$try->set_value('nonmandatory', 'description', 		$forum['description']);
				$try->set_value('nonmandatory', 'visible', 			'1');

				if ($forumoptions)
				{
					$try->set_value('mandatory', 'options',			$forumoptions);
				}
				else
				{
					$try->set_value('mandatory', 'options',			$this->_default_forum_permissions);
				}

				if($try->is_valid())
				{
					if($try->import_forum($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $try->get_value('mandatory', 'title'));
						$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $forum_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $forum_id, $displayobject->phrases['invalid_object'], $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
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
				$sessionobject->set_session_var('module', '000');
				$sessionobject->set_session_var('autosubmit', '0');
			}
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 20, 2004, 2:31 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1825 $
|| ####################################################################
\*======================================================================*/
?>
