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
* ipb Import Forums
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ipb
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ipb_005 extends ipb_000
{
	var $_dependent 	= '004';

	function ipb_005(&$displayobject)
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

	function resume(&$sessionobject,&$displayobject,&$Db_target,&$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$forum_start_at 		= $sessionobject->get_session_var('forumstartat');
		$forum_per_page 		= $sessionobject->get_session_var('forumperpage');

		$class_num				= substr(get_class($this) , -3);

		$displayobject->update_basic('displaymodules','FALSE');

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Import the categories first
		if($sessionobject->get_session_var('categoriesfinished') == 'FALSE')
		{
			// Get all the details
			$categories_array  	=  $this->get_ipb_category_details($Db_source, $source_database_type, $source_table_prefix);
			$category_object	= new ImpExData($Db_target, $sessionobject,'forum');

			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($categories_array) . " {$displayobject->phrases['categories']}</h4>");

			foreach ($categories_array as $category_id => $category)
			{
				// If its not the first blank one
				if($category_id != '-1')
				{
					$try = (phpversion() < '5' ? $category_object : clone($category_object));

					$try->set_value('mandatory', 'title', 				$category['name']);
					$try->set_value('mandatory', 'displayorder',		$category['position']);
					$try->set_value('mandatory', 'parentid',			'-1');
					$try->set_value('mandatory', 'importforumid',		'0');
					$try->set_value('mandatory', 'importcategoryid',	$category_id);
					$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
					
					if($category['description'] != '')
					{
						$try->set_value('nonmandatory', 'description',	$category['description']);
					}
					else
					{
						$try->set_value('nonmandatory', 'description',		"Please update the description for : " . $category['name']);
					}

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
						$sessionobject->add_error($try->get_value('mandatory', 'importcategoryid'), $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
						}
					}
					else
					{
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					}
					unset($try);
				}
			}
			$sessionobject->add_session_var('categoriesfinished','TRUE');
		}
		else
		{
			// Weve done the categories have a go at the forums
			// Get all the details
			$forum_array  	= $this->get_ipb_forum_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at,$forum_per_page);

			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($forum_array) . " {$displayobject->phrases['forums']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $forum_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($forum_start_at + count($forum_array)) . "</p>");

			$forum_object = new ImpExData($Db_target,$sessionobject,'forum');
			foreach ($forum_array as $forum_id => $forum)
			{
				$forum_ids	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
				$cat_ids	= $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
				
				$try = (phpversion() < '5' ? $forum_object : clone($forum_object));

				$try->set_value('mandatory', 'title', 				$forum['name']);
				$try->set_value('mandatory', 'displayorder',		$forum['position']);
				$try->set_value('mandatory', 'importforumid',		$forum_id);
				$try->set_value('mandatory', 'importcategoryid',	'0');
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);
				
				if(!$forum_ids["$forum[parent_id]"])
				{
					$try->set_value('mandatory', 'parentid',		'-1');
				}
				else
				{
					$try->set_value('mandatory', 'parentid',		$forum_ids["$forum[parent_id]"]);
				}

				if(!$try->get_value('mandatory', 'parentid'))
				{
					$try->set_value('mandatory', 'parentid',		$$cat_ids["$forum[parent_id]"]);
				}

				$try->set_value('nonmandatory', 'description', 		$forum['description']);

				$try->set_value('nonmandatory', 'replycount',		$forum['psots']);
				$try->set_value('nonmandatory', 'lastpost',			$forum['last_post']);
				$try->set_value('nonmandatory', 'lastposter',		$forum['last_poster_name']);
				$try->set_value('nonmandatory', 'threadcount',		$forum['topics']);
				$try->set_value('nonmandatory', 'daysprune',		$forum['prune']);

				if($try->is_valid())
				{
					if($try->import_forum($Db_target,$target_database_type,$target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $try->get_value('mandatory', 'title'));
						$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($try->get_value('mandatory', 'importforumid'), $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}

				unset($try);
			}

			// If we are all finished
			if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
			{
				$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);

				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
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
			// Only update this when in the forums loop
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
