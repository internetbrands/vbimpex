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
* OT Import Posts
*
* @package 		ImpEx.OT
*
*/
class OT_004 extends OT_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Posts';

	function OT_004()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_posts'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Posts have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_posts",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('posts','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Posts'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import posts from your OT board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of posts to import per cycle","postsperpage","100"));
			$displayobject->update_html($displayobject->do_form_footer("Import posts"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('postsstartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($sessionobject->get_session_var('posts') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$posts_start_at 		= $sessionobject->get_session_var('postsstartat');
			$posts_per_page 		= $sessionobject->get_session_var('postsperpage');

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}


			if(intval($posts_per_page) == 0)
			{
				$posts_per_page = 150;
			}

			$file_handle = fopen($sessionobject->get_session_var('forumsxmlfile'), 'r');
			$posts_array = array();
			$type = 'message';
			$posts_array 			= $this->get_OT_details($file_handle, $posts_start_at, $posts_per_page, $posts_array, $type);
			$sessionobject->set_session_var('postsstartat', $posts_array['pointer_position']);

			$thread_ids 			= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
			$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
			$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);
			$forum_ids 				= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

			$last_pass = $sessionobject->get_session_var('last_pass');

			$displayobject->display_now("<h4>Importing " . count($posts_array) . " posts</h4><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$start = time();

			$post_object 	= new ImpExData($Db_target, $sessionobject, 'post');
			$thread_object 	= new ImpExData($Db_target, $sessionobject, 'thread');

			foreach ($posts_array as $post_id => $post)
			{
				if($post['IS_TOPIC'] == 'Y')
				{
					// Its a thread starting post, make the thread as well as importing it as a post
					$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

					$try->set_value('mandatory', 'title', 			$post['TOPIC_SUBJECT']);
					$try->set_value('mandatory', 'forumid', 		$forum_ids[$post['FORUM_OID']]);
					$try->set_value('mandatory', 'importthreadid', 	$post['TOPIC_MESSAGE_OID']);
					$try->set_value('mandatory', 'importforumid', 	$post['FORUM_OID']);

					#$try->set_value('nonmandatory', 'firstpostid', 	$post['topic_first_post_id']);
					#$try->set_value('nonmandatory', 'lastpost', 	$post['topic_last_post_id']);
					#$try->set_value('nonmandatory', 'replycount', 	$post['topic_replies']);
					$try->set_value('nonmandatory', 'postusername',	$user_names["$post[AUTHOR_OID]"]);
					$try->set_value('nonmandatory', 'postuserid', 	$users_ids["$post[AUTHOR_OID]"]);
					$try->set_value('nonmandatory', 'dateline', 	strtotime($post['DATETIME_POSTED']));
					$try->set_value('nonmandatory', 'views', 		$post['MESSAGE_PAGE_VIEW_COUNT']);
					$try->set_value('nonmandatory', 'visible', 		'1');

					if($this->option2bin($post['IS_TOPIC_CLOSED']))
					{
						$try->set_value('nonmandatory', 'open', 	'0');
					}
					else
					{
						$try->set_value('nonmandatory', 'open', 	'1');
					}

					/*
					$try->set_value('nonmandatory', 'pollid', );
					$try->set_value('nonmandatory', 'lastposter', );
					$try->set_value('nonmandatory', 'iconid', );
					$try->set_value('nonmandatory', 'notes', );
					$try->set_value('nonmandatory', 'visible', );
					$try->set_value('nonmandatory', 'sticky', );
					$try->set_value('nonmandatory', 'votenum', );
					$try->set_value('nonmandatory', 'votetotal', );
					$try->set_value('nonmandatory', 'attach', );
					$try->set_value('nonmandatory', 'similar', );
					}
					*/


					if($try->is_valid())
					{
						if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Thread ->  " . $try->get_value('mandatory','title'));
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							$imported = true;
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_thread failed for " . $cat['TOPIC_SUBJECT'] . " get_OT_details was ok.",
													 'Check database permissions and user table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$displayobject->display_now("<br />Got thread " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
					}
					unset($try);
					// Going to have to rebuild it if we have added one.
					// Could append the array though this is eaiser
					$thread_ids = $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
				}// Is it a thread


				$try = (phpversion() < '5' ? $post_object : clone($post_object));

				$try->set_value('mandatory', 'threadid', 			$thread_ids[$post['TOPIC_MESSAGE_OID']]);
				$try->set_value('mandatory', 'userid', 				$users_ids["$post[AUTHOR_OID]"]);
				$try->set_value('mandatory', 'importthreadid', 		$post['TOPIC_MESSAGE_OID']);

				$try->set_value('nonmandatory', 'allowsmilie',		'1');
				$try->set_value('nonmandatory', 'visible', 			'1');
				$try->set_value('nonmandatory', 'username', 		$user_names["$post[AUTHOR_OID]"]);
				$try->set_value('nonmandatory', 'dateline',			@strtotime($post['DATETIME_POSTED']));
				$try->set_value('nonmandatory', 'pagetext', 		$this->html_2_bb(substr($post['MESSAGE_BODY'], 9, -3)));
				$try->set_value('nonmandatory', 'title', 			$post['SUBJECT']);
				$try->set_value('nonmandatory', 'ipaddress',		$post['POSTER_IP']);

				/*
				VB3 fields
				$try->set_value('nonmandatory', 'parentid',);
				$try->set_value('nonmandatory', 'iconid',);
				$try->set_value('nonmandatory', 'attach',);
				$try->set_value('nonmandatory', 'allowsmilie', 		$post['enable_smilies']);
				$try->set_value('nonmandatory', 'showsignature', 	$post['enable_sig']);

				*/

				if($try->is_valid())
				{
					if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Post from -> ". $try->get_value('nonmandatory','username'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_thread failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got thread " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
				}
				unset($try);
			}

			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);


			if (count($posts_array) == 0 OR count($posts_array) < $posts_per_page)
			{
				$displayobject->display_now('Updateing parent ids to allow for a threaded view....');

				if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('Done !');
				}
				else
				{
					$displayobject->display_now('Error updating parent ids');
				}
 
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																			$sessionobject->return_stats($class_num, '_time_taken'),
																			$sessionobject->return_stats($class_num, '_objects_done'),
																			$sessionobject->return_stats($class_num, '_objects_failed')
																			));
				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('posts','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$displayobject->display_now('Going to the main page...');
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*/
?>
