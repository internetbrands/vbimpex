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
* discus_file Import Threads
*
* @package 		ImpEx.discus_file
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class discus_file_007 extends discus_file_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Posts';

	function discus_file_007()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
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
											 'Check database permissions and posts table');
				}
			}
			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('posts','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Posts'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import threads from your Discuss board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of threads worth of posts to import per cycle (whole html files)","threadsperpage","500"));
			$displayobject->update_html($displayobject->do_form_footer("Import Posts"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');

			$sessionobject->add_session_var('currentforum','0');
			$sessionobject->add_session_var('threadsstartat','0');
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
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$thread_start_at 		= $sessionobject->get_session_var('threadsstartat');
		$threads_per_page 		= $sessionobject->get_session_var('threadsperpage');

		$current_forum			= $sessionobject->get_session_var('currentforum');

		if($current_forum == 0)
		{
			$current_forum 		= $this->get_first_cat_number($Db_target, $target_database_type, $target_table_prefix);
		}


		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$posts_array 			= $this->get_discus_file_post_details($sessionobject->get_session_var('messagesspath'), $current_forum, $thread_start_at, $threads_per_page);

		if (!$posts_array)
		{
			$posts_array = array();
			$displayobject->display_now("</ br>Empty or no Forum {$current_forum}, skipping<br>");
		}

		$thread_ids				= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
		$users_ids 				= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_names				= $this->get_username($Db_target, $target_database_type, $target_table_prefix);
		$username_to_ids		= $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);
		$email_to_ids			= $this->get_email_to_ids($Db_target, $target_database_type, $target_table_prefix);
		$display_name_to_id 	= $this->select_profilefield_list($Db_target, $target_database_type, $target_table_prefix, 'displayname');

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		foreach ($posts_array as $forum_id => $thread_list)
		{
			$displayobject->display_now("<h4>Importing from forum : " . $forum_id . "</h4>");

			foreach($thread_list as $thread_id => $post_list)
			{
				$displayobject->display_now("<h4>Importing from thread : " . $thread_id . "</h4>");

				foreach($post_list as $key => $posts)
				{
					foreach($posts as $post)
					{

						/*
						1 = postid
						2 = timestamp
						3 = email or 'none'
						4 = username (possibly)
						5 = page text
						*/


						$origional = trim($post[4]);
						$post[4] = trim(strtolower($post[4]));

						if(substr($post[4], '(') AND substr($post[4], '('))
						{
							$try = explode(' ',$post[4]);
							$post[4] = $try[0];
						}

						$try = (phpversion() < '5' ? $post_object : clone($post_object));
						$try->set_value('mandatory', 'threadid', 			$thread_ids[$thread_id]);

						$catch = true;

						if($username_to_ids[$post[4]])
						{
							$try->set_value('nonmandatory', 'username',		$post[4]);
							$try->set_value('mandatory', 'userid', 			$username_to_ids[$post[4]]);
						}
						else if($username_to_ids[$origional])
						{
							$try->set_value('nonmandatory', 'username',		$origional);
							$try->set_value('mandatory', 'userid', 			$username_to_ids[$origional]);
						}
						else if ($email_to_ids[$post[3]]['userid'])
						{
							$id = $email_to_ids[$post[3]];
							$try->set_value('mandatory', 'userid', 			$user_names[$id]['userid']);
						}
						else if($display_name_to_id[$post[4]])
						{
							$try->set_value('mandatory', 'userid', 			$display_name_to_id[$post[4]]);
						}
						else
						{
							// No idea, guest post it
							if($post[4])
							{
								$try->set_value('nonmandatory', 'username',	$post[4]);
							}
							$try->set_value('mandatory', 'userid', 			'0');
						}

						$try->set_value('mandatory', 'importthreadid', 		$thread_id);

						$try->set_value('nonmandatory', 'visible', 			'1');
						$try->set_value('nonmandatory', 'importpostid',		$post[2]);
						$try->set_value('nonmandatory', 'dateline',			$post[2]);
						$try->set_value('nonmandatory', 'allowsmilie', 		'1');
						$try->set_value('nonmandatory', 'showsignature', 	'1');
						$try->set_value('nonmandatory', 'pagetext', 		$this->html_2_bb($post[5]));




						if($try->is_valid())
						{
							if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: From user -> " . $post[4]);
								$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
								$imported = true;
							}
							else
							{
								$sessionobject->add_error('warning',
														 $this->_modulestring,
														 get_class($this) . "::import_post failed",
														 'Check database permissions and user table');
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
								$displayobject->display_now("<br />Got post  and <b>DID NOT</b> imported to the " . $target_database_type . " database");
							}
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$displayobject->display_now("<br />Invalid post object, skipping. Failed on not getting the - " . $try->_failedon);
						}
						unset($try);
					}
				}
			}
			#22903
		}

		$redirect_time = 1;

		if (count($posts_array) == 0 OR count($posts_array) < $threads_per_page)
		{
			// Check for the next forum to import threads from

			$next_forum = $this->get_next_cat_id($Db_target, $target_database_type, $target_table_prefix, $current_forum);

			if($next_forum == NULL)
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
																			)
											);

				$sessionobject->set_session_var($class_num,'FINISHED');
				$sessionobject->set_session_var('threads','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('threadsstartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
			}
			else
			{
				$sessionobject->set_session_var('currentforum',$next_forum);
				$sessionobject->add_session_var('threadsstartat','0');
				$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
			}
		}
		else
		{
			$sessionobject->set_session_var('threadsstartat',$thread_start_at+$threads_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php', '1'));
		}

	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
