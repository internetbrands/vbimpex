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
* zeroforum_003 Import Forum module
*
* @package			ImpEx.zeroforum
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class zeroforum_003 extends zeroforum_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '002';
	var $_modulestring 	= 'Import Forums';


	function zeroforum_003()
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
					
					$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads');
					$displayobject->display_now('<h4>Imported threads have been cleared</h4>');
					
					$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts');
					$displayobject->display_now('<h4>Imported posts have been cleared</h4>');
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
			$displayobject->update_html($displayobject->make_description("<p>All the Forums will now be imported one per page, this may take some time due to the size of the files.</p>"));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('forumstartat','0');
			$sessionobject->add_session_var('forumdone','0');
			$sessionobject->add_session_var('current_forum', '');
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

		// If there is one get it.
		$current_forum = $sessionobject->get_session_var('current_forum');

		// It will either be blank and get the first or next from current or return "FALSE"
		$current_forum = $this->get_current_forum_file($sessionobject->get_session_var('forumpath'), $current_forum);
		$sessionobject->add_session_var('current_forum', $current_forum);
		
		// Get an array of forum details
		#$file = implode(' ', file($sessionobject->get_session_var('forumpath') . '/' .  $current_forum));
		$file = $sessionobject->get_session_var('forumpath') . '/' .  $current_forum;
		$forum_array = $this->get_zeroforum_forum_details($file);
		unset($file);
		
		if ($current_forum)
		{
			$displayobject->display_now("<h4>Importing forum {$current_forum} </h4>");
		}
		
		$forum_object 	= new ImpExData($Db_target, $sessionobject, 'forum');
		$thread_object 	= new ImpExData($Db_target, $sessionobject, 'thread');
		$post_object 	= new ImpExData($Db_target, $sessionobject, 'post');

		$users_ids		= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names		= $this->get_username($Db_target, $target_database_type, $target_table_prefix);
		
		if($sessionobject->get_session_var('added_default_cat') != 'yup')
		{
			// Default cat
			$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
			$try->set_value('mandatory', 'title', 				'Zeroforum');
			$try->set_value('mandatory', 'displayorder',		'1');
			$try->set_value('mandatory', 'parentid',			'-1');
			$try->set_value('mandatory', 'importforumid',		'0');
			$try->set_value('mandatory', 'importcategoryid',	'1');
			$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
			$try->set_value('nonmandatory', 'description', 		'This is a default category for imported forums');
			$try->import_category($Db_target, $target_database_type, $target_table_prefix);
			$sessionobject->add_session_var('added_default_cat', 'yup');
			unset($try);
		}
		
		$cat_ids_array = $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
		
		$cat_o = (phpversion() < '5' ? $forum_object : clone($forum_object));
		
		if($forum_array['forum_title'])
		{
			// Mandatory
			$cat_o->set_value('mandatory', 'importcategoryid',		'0');
			$cat_o->set_value('mandatory', 'importforumid',			$forum_array['ID']);
			$cat_o->set_value('mandatory', 'title',					$forum_array['forum_title']);
			$cat_o->set_value('mandatory', 'displayorder',			'1');
			$cat_o->set_value('mandatory', 'parentid',				$cat_ids_array['1']);
			$cat_o->set_value('mandatory', 'options',				$this->_default_forum_permissions);
	
			// Non Mandatory
			$cat_o->set_value('nonmandatory', 'threadcount',		$forum_array['total_threads']);
			$cat_o->set_value('nonmandatory', 'description',		$forum_array['forum_desc']);
			$cat_o->set_value('nonmandatory', 'replycount',			$forum_array['total_posts']);
	
	
			// Check if forum object is valid
			if($cat_o->is_valid())
			{
				if($currernt_forum_id = $cat_o->import_forum($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $cat_o->how_complete() . '%</b></span> :: - forum -> ' . $forum_array['forum_title']);
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
			unset($cat_o);
				
				// For each the threads.
			if($forum_array['thread'])
			{
				foreach ($forum_array['thread'] AS $thread)
				{
					// Set up the thread to import.
					$thread_o = (phpversion() < '5' ? $thread_object : clone($thread_object));
					
					$thread_o->set_value('mandatory', 'title', 				$thread['thread_title']);
					$thread_o->set_value('mandatory', 'forumid', 			$currernt_forum_id);
					$thread_o->set_value('mandatory', 'importthreadid', 	$thread['ID']);
					$thread_o->set_value('mandatory', 'importforumid', 		$thread['thread2forum']);
	
					$thread_o->set_value('nonmandatory', 'replycount', 		$thread['posts']);
					$thread_o->set_value('nonmandatory', 'postusername',	$thread['thread_creator']);
					$thread_o->set_value('nonmandatory', 'dateline', 		strtotime($thread['thread_date']));
					$thread_o->set_value('nonmandatory', 'views', 			$thread['views']);
					$thread_o->set_value('nonmandatory', 'visible', 		'1');
					$thread_o->set_value('nonmandatory', 'open', 			'1');
					$thread_o->set_value('nonmandatory', 'sticky',			$this->option2bin($thread['sticky']));
	
					if($thread_o->is_valid())
					{
						if($current_thread_id = $thread_o->import_thread($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $thread_o->how_complete() . "%</b></span> :: -- thread -> " . $thread_o->get_value('mandatory','title'));
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_thread failed for " . $thread_o['thread_title'],
													 'Check database permissions and user table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$displayobject->display_now("<br />Got thread " . $thread['thread_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
					}
					unset($thread_o);
				
					// For each the posts.
					
					if (!isset($thread['post'][0])) { $thread['post'] = array(0 => $thread['post']); }
					
					foreach ($thread['post'] as $post)
					{
						$post_o = (phpversion() < '5' ? $post_object : clone($post_object));
						#$name = strval($post['post2user']);
						
						$post_o->set_value('mandatory', 'threadid', 			$current_thread_id);
						
						if ($users_ids[$name])
						{
							$post_o->set_value('mandatory', 'userid', 			$users_ids[$name]);
						}
						else
						{
							$post_o->set_value('mandatory', 'userid',			'0');
						}
						
						if ($post['post_user'])
						{
							#echo "<h1>'" . $post['post_user'] . "'</h1>";die;
						}
						
						$post_o->set_value('mandatory', 'importthreadid', 		$post['post2thread']);
						
						if ($post['post_date'])
						{
							$post_o->set_value('nonmandatory', 'dateline',		strtotime($post['post_date']));
						}
						
						
						$post_o->set_value('nonmandatory', 'allowsmilie', 		'1');
						$post_o->set_value('nonmandatory', 'showsignature', 	$this->option2bin($post['show_signature']));
						
						if($user_names[$name])
						{
							$post_o->set_value('nonmandatory', 'username', 		$user_names[$name]);
						}
						else
						{
							$post_o->set_value('nonmandatory', 'username', 		'Guest');
						}
						$post_o->set_value('nonmandatory', 'ipaddress',			$post['ip_addr']);
						$post_o->set_value('nonmandatory', 'title', 			$post['post_title']);
						$post_o->set_value('nonmandatory', 'pagetext', 			$this->zeroforum_html($this->html_2_bb($post['post_text'])));
						$post_o->set_value('nonmandatory', 'importpostid',		$post['ID']);
						$post_o->set_value('nonmandatory', 'parentid',			'0'); #$post['parent']
						$post_o->set_value('nonmandatory', 'visible',			'1');
	
						if($post_o->is_valid())
						{
							if($post_o->import_post($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $post_o->how_complete() . "%</b></span> :: --- post from -> " . $post_o->get_value('nonmandatory','username'));
								$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
								$imported = true;
							}
							else
							{
								$sessionobject->add_error('warning',
														 $this->_modulestring,
														 get_class($this) . "::import_post failed for " . $post_o->get_value('nonmandatory','username'),
														 'Check database permissions and user table');
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
								$displayobject->display_now("<br />Got post from " . $post_o->get_value('nonmandatory','username'));
							}
						}
						else
						{
							$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
						}
						unset($post_o);
					}// End foreach for posts 
				}//End foreach for threads
			}// End if there are threads
		}// There is a title.

		$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

		$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
		$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);
		$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);
		
		
		if(!$current_forum)
		{
			$displayobject->display_now('Updating post parents and forum childlists');
			
			if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now('Done !');
			}
			else
			{
				$displayobject->display_now('Error updating parent ids');
			}
			
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
		
		$displayobject->update_html($displayobject->print_redirect('index.php','3'));
	}// End resume
}//End Class
# Autogenerated on : May 23, 2005, 2:32 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
