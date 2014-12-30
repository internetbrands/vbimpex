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
* yabb2_005 Import Post module
*
* @package			ImpEx.yabb2
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yabb2_005 extends yabb2_000
{
	var $_dependent 	= '004';

	function yabb2_005($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_post'];
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts')
				AND $this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['posts_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['post_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}


			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_post']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'],'postperpage',500));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder',$sessionobject->get_session_var('attachmentsfolder'),1,60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat','0');
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

	function resume($sessionobject, $displayobject, $Db_target, $Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');

		// Per page vars
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$class_num				= substr(get_class($this) , -3);
		$cat_dir 				= $sessionobject->get_session_var('forumspath') . '/Messages';

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of post details
		$post_array 	= $this->get_yabb2_post_details($cat_dir, $post_start_at, $post_per_page);

		// Get some refrence arrays (use and delete as nessesary).
		$thread_ids				= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_name_array 		= $this->get_username_to_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($post_array) . " {$displayobject->phrases['posts']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $post_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($post_start_at + count($post_array)) . "</p>");


		$post_object = new ImpExData($Db_target, $sessionobject, 'post');
		$attachment_object = new ImpExData($Db_target, $sessionobject, 'attachment');

		if(count($post_array) > 1)
		{
			foreach ($post_array as $post_id => $post_details_array)
			{
				foreach ($post_details_array AS $id => $post_details)
				{
					$try = (phpversion() < '5' ? $post_object : clone($post_object));
					// Mandatory
					$try->set_value('mandatory', 'threadid',			$thread_ids["$post_details[threadid]"]);
					if ($user_name_array["$post_details[username]"])
					{
						$try->set_value('mandatory', 'userid',				$user_name_array["$post_details[username]"]);
					}
					else // Allow guest
					{
						$try->set_value('mandatory', 'userid',				"0");
					}
					$try->set_value('mandatory', 'importthreadid',		$post_details['threadid']);

					// Non Mandatory
					#$try->set_value('nonmandatory', 'parentid',		$post_details['']);
					$try->set_value('nonmandatory', 'username',			$post_details['username']);
					$try->set_value('nonmandatory', 'title',			$post_details['title']);
					$try->set_value('nonmandatory', 'dateline',			$post_details['dateline']);
					$try->set_value('nonmandatory', 'pagetext',			$this->yabb2_html($this->html_2_bb($post_details['pagetext'])));
					$try->set_value('nonmandatory', 'allowsmilie',		'1');
					$try->set_value('nonmandatory', 'showsignature',	'1');
					$try->set_value('nonmandatory', 'ipaddress',		$post_details['ipaddress']);
					#$try->set_value('nonmandatory', 'iconid',			$post_details['iconid']);
					$try->set_value('nonmandatory', 'visible',			'1');
					#$try->set_value('nonmandatory', 'attach',			$post_details['attach']);
					$try->set_value('nonmandatory', 'importpostid',		$id); // Just mark it as an imported post

					// Check if post object is valid
					if($try->is_valid())
					{
						if($imported_post_id = $try->import_post($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: post -> ' . $post_details['title']);
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
							$displayobject->display_now("<br />Found avatar post and <b>DID NOT</b> imported to the  {$target_database_type} database");
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
					}

					// Don't need the post try any more
					unset($try);

					// Is there an attachment ?
					if($post_details['attachment'])
					{
							$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));

							$dir = $sessionobject->get_session_var('attachmentsfolder');

							$fullpath = $dir . '/' . trim($post_details['attachment']);
							
							if(!is_file($fullpath))
							{
								$displayobject->display_now('<br /><b>Source file not found </b> :: attachment -> ' . $post_details['attachment']);
								$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
								continue;
							}

							$file = $this->vb_file_get_contents($fullpath);

							// Mandatory
							$try->set_value('mandatory', 'filename',				$post_details['attachment']);
							$try->set_value('mandatory', 'filedata',				$file);
							$try->set_value('mandatory', 'importattachmentid',		$id);

							// Non Mandatory
							$try->set_value('nonmandatory', 'userid',				'');
							$try->set_value('nonmandatory', 'dateline',				'');
							$try->set_value('nonmandatory', 'visible',				'1');
							$try->set_value('nonmandatory', 'counter',				'1');
							$try->set_value('nonmandatory', 'filesize',				filesize($fullpath));
							$try->set_value('nonmandatory', 'postid',				$imported_post_id);
							$try->set_value('nonmandatory', 'filehash',				md5($file));

							#$try->set_value('nonmandatory', 'posthash',			$attachment_details['posthash']);
							#$try->set_value('nonmandatory', 'thumbnail',			$attachment_details['thumbnail']);
							#$try->set_value('nonmandatory', 'thumbnail_dateline',	$attachment_details['thumbnail_dateline']);


							// Check if attachment object is valid
							if($try->is_valid())
							{
								// Passing false as we have the real vB post id
								if($try->import_attachment($Db_target, $target_database_type, $target_table_prefix, false))
								{
									$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: attachment -> ' . $post_details['attachment']);
									$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
								}
								else
								{
									$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
									$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
									$displayobject->display_now("<br />Found  attachment and <b>DID NOT</b> imported to the  {$target_database_type} database possibly the origional post is missing");
								}
							}
							else
							{
								$displayobject->display_now("<br />Invalid attachment object, skipping." . $try->_failedon);
							}
							unset($try);
					}// End attachment
				}
			}// End foreach
		}

		// Check for page end
		if (count($post_array) == 0)
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

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_post','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : March 20, 2006, 7:38 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
