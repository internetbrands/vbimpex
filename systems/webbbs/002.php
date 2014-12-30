<?php 
if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
*
* @package			ImpEx.webbbs
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class webbbs_002 extends webbbs_000
{
	var $_dependent = '001';

	function webbbs_002($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_forum'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums') and 
					$this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads') and 
					$this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_posts')
				)
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['forums_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['forum_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_forum']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			#$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);
		$rootpath		= $sessionobject->get_session_var('rootpath');

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}
 
		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $t_db_type, $t_tb_prefix, $importuserid);
		#$this->get_one_username($Db_target, $t_db_type, $t_tb_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $t_db_type, $t_tb_prefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_ids_array = $this->get_user_ids($Db_target, $t_db_type, $t_tb_prefix, $do_int_val = false);
		#$user_name_array = $this->get_username_to_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Groups info
		#$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $t_db_type, $t_tb_prefix);
		#$bannded_groupid = $this->get_banned_group($Db_target, $t_db_type, $t_tb_prefix);
		// Thread info
		#$this->get_thread_id($Db_target, $t_db_type, $t_tb_prefix, &$importthreadid, &$forumid); // & left to show refrence
		#$thread_ids_array = $this->get_threads_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Post info
		#$this->get_posts_ids($Db_target, $t_db_type, $t_tb_prefix);
		// Category info
		#$cat_ids_array = $this->get_category_ids($Db_target, $t_db_type, $t_tb_prefix);
		#$style_ids_array = $this->get_style_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);
		// Forum info
		#$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix, $pad=0);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['forums'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'forum');
		$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');
		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		$data_cat_array = $this->get_cats($rootpath);
		$cat_display_id = 1;
		foreach ($data_cat_array as $name => $forum_full_path)
		{
			$cat = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));
 
			$cat->set_value('mandatory', 'title', 				$name);
			$cat->set_value('mandatory', 'displayorder',		$cat_display_id++);
			$cat->set_value('mandatory', 'parentid',			'-1');
			$cat->set_value('mandatory', 'options',				$this->_default_cat_permissions);
			$cat->set_value('mandatory', 'importforumid',		'0');
			$cat->set_value('mandatory', 'importcategoryid',	$cat_display_id);
			$cat->set_value('nonmandatory', 'description',		$forum_full_path);

			if ($cat->is_valid())
			{
				if($cat_id = $cat->import_category($Db_target, $t_db_type, $t_tb_prefix))
				{			
					$displayobject->display_now('<br /><span class="isucc"><b>' . $cat->how_complete() . '%</b></span> ' . $displayobject->phrases['category'] . ' -> ' . $cat->get_value('mandatory', 'title'));
					$sessionobject->add_session_var("{$class_num}_objects_done", intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1);
					
// Do the forums now we have the cat
					$data_forum_array = $this->get_forums($forum_full_path);
					$forum_display_id = 1;

					foreach ($data_forum_array as $forum_name => $forum_path)
					{
						$forum = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));
						
						$forum_thread_ids = array();
						
						$forum->set_value('mandatory', 'parentid',			$cat_id);
						$forum->set_value('mandatory', 'importforumid',		$forum_display_id++);
						$forum->set_value('mandatory', 'importcategoryid',	'0');
						$forum->set_value('mandatory', 'displayorder',		$forum_display_id);
						$forum->set_value('mandatory', 'options',			$this->_default_forum_permissions);
						$forum->set_value('mandatory', 'title',				$forum_name);
						$forum->set_value('nonmandatory', 'description',	$forum_path);
						
						if ($forum->is_valid())
						{
							if($forum_id = $forum->import_forum($Db_target, $t_db_type, $t_tb_prefix))
							{
								$displayobject->display_now('<br /><span class="isucc">' . $forum_display_id . ' :: <b>' . $forum->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $forum_name);
								$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
// Do the threads & posts
								$forum_posts = $this->get_forum_posts($forum_path);
								$done_thread 	= array();
								$done_post		= array(); 
								$is_thread 		= false;
								
								foreach ($forum_posts as $file_id => $data)
								{
									$data['PREVIOUS'] = trim($data['PREVIOUS']);  
									if (empty($data['PREVIOUS']))
									{
// It's a thread starting post.
										$is_thread = true;
										$thread = (phpversion() < '5' ? $thread_object : clone($thread_object));
										
										$thread->set_value('mandatory', 'title',				$data['SUBJECT']);
										$thread->set_value('mandatory', 'importforumid',		$cat_id);
										$thread->set_value('mandatory', 'importthreadid',		$file_id);
										$thread->set_value('mandatory', 'forumid',				$forum_id);
							
										// Non mandatory
										$thread->set_value('nonmandatory', 'visible',			'1');
										$thread->set_value('nonmandatory', 'sticky',			'0');
										$thread->set_value('nonmandatory', 'notes',				"{$forum_path}/{$file_id}");
										$thread->set_value('nonmandatory', 'open',				'1');
										$thread->set_value('nonmandatory', 'postusername',		$data['POSTER']);
										$thread->set_value('nonmandatory', 'dateline',			$data['DATE']);
										
										
										if($thread->is_valid())
										{
											if($thread_id = $thread->import_thread($Db_target, $t_db_type, $t_tb_prefix))
											{
												$forum_thread_ids[$file_id] = $thread_id;
												
												$displayobject->display_now('<br /><span class="isucc">' . $file_id . ' :: <b>' . $thread->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $data['SUBJECT']);
												$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
											}
											else
											{
												$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
												$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
												$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
											}// $thread->import_thread
										}
										else
										{
											$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
											$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $thread->_failedon, $displayobject->phrases['invalid_object_rem']);
											$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $thread->_failedon);
										}// is_valid
										unset($thread);
									}// If it's a thread
										
// Even if it is a thread, it's going to be a post as well, and if it isn't it's just a post
									
									$post = (phpversion() < '5' ? $post_object : clone($post_object));
									
									// Mandatory
									if ($is_thread)
									{
										$post->set_value('mandatory', 'threadid',		$thread_id);
									}
									else if ($forum_thread_ids["$data[PREVIOUS]"])
									{
										$post->set_value('mandatory', 'threadid',		$forum_thread_ids["$data[PREVIOUS]"]);
									}
									else 
									{
										echo "<br> Parent of {$forum_path}/{$file_id} missing";
									}
									
									$post->set_value('mandatory', 'importthreadid',		$file_id);
									$post->set_value('mandatory', 'userid',				'0');
						
									// Non mandatory
									$post->set_value('nonmandatory', 'visible',			'1');
									$post->set_value('nonmandatory', 'showsignature',	'1');
									$post->set_value('nonmandatory', 'allowsmilie',		'1');
									$post->set_value('nonmandatory', 'pagetext',		$this->webbbs_html($this->html_2_bb($data['PAGETEXT'])));
									$post->set_value('nonmandatory', 'dateline',		$data['DATE']);
									$post->set_value('nonmandatory', 'title',			$data['SUBJECT']);
									$post->set_value('nonmandatory', 'username',		$data['POSTER']);
#$post->set_value('nonmandatory', 'parentid',		$data['parentid']);
									$post->set_value('nonmandatory', 'importpostid',	$file_id);

									// Check if object is valid
									if($post->is_valid())
									{
										if($post->import_post($Db_target, $t_db_type, $t_tb_prefix))
										{
											$forum_thread_ids[$file_id] = $thread_id;
											if(shortoutput)
											{
												$displayobject->display_now('.');
											}
											else
											{
												$displayobject->display_now('<br /><span class="isucc">' . $file_id . ' :: <b>' . $post->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $data['SUBJECT']);
											}
											$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
										}
										else
										{
											$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
											$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
											$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
										}// $post->import_post
									}
									else
									{
										$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
										$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $post->_failedon, $displayobject->phrases['invalid_object_rem']);
										$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $post->_failedon);
									}// is_valid
									unset($post);
									$is_thread = false;
								}						
							}
							else
							{
								$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
								$sessionobject->add_error($Db_target, 'warning', $class_num, $forum_display_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
								$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
							}// $forum->import_forum
						}
						else
						{
							$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
							$sessionobject->add_error($Db_target, 'invalid', $class_num, $forum_display_id, $displayobject->phrases['invalid_object'] . ' ' . $forum->_failedon, $displayobject->phrases['invalid_object_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $forum->_failedon);
						}// is_valid
						unset($try);
					}// End forum foreach
				}	
				else
				{
					// The cat didn't import
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					
				}
			}
			else 
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);				
			}
		}// End cat foreach

		// Check for page end
#		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
#		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			$this->build_forum_child_lists($Db_target, $t_db_type, $t_tb_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
#		}

#		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : April 28, 2008, 2:48 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
