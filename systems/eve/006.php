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
* eve_006 Import Post module
*
* @package			ImpEx.eve
*
*/
class eve_006 extends eve_000
{
	var $_dependent 	= '005';

	function eve_006(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_post'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['post_restart_ok']}</h4>");
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
			$displayobject->update_html($displayobject->make_hidden_code('import_post','working'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_post']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'],'postperpage',2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat','0');
			$sessionobject->add_session_var('nonarchivefinished','FALSE');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
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
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		if($sessionobject->get_session_var('nonarchivefinished') == 'FALSE') # NOW DO THE NORMAL POSTS
		{
			// Get an array of post details
			$post_array 	= $this->get_eve_post_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page);

			// Display count and pass time
			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " .$post_array['count'] . " {$displayobject->phrases['posts']}</h4>");

			foreach ($post_array['data'] as $post_id => $data)
			{
				$try = (phpversion() < '5' ? $post_object : clone($post_object));
				
				// Deleted thread OR Private message
				if (!$idcache->get_id('thread', $data['TOPIC_OID'])) 
				{
					continue;
				}

			// Mandatory
				$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $data['TOPIC_OID']));
				$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $data['AUTHOR_OID']));
				$try->set_value('mandatory', 'importthreadid',		$data['TOPIC_OID']);

			// Non Mandatory
				$try->set_value('nonmandatory', 'parentid',			'0');
				$try->set_value('nonmandatory', 'username',			$idcache->get_id('username', $data['AUTHOR_OID']));
				$try->set_value('nonmandatory', 'dateline',			@strtotime($data['DATETIME_CREATED']));
				$try->set_value('nonmandatory', 'pagetext',			$this->eve_html($this->html_2_bb($data['BODY'])));
				$try->set_value('nonmandatory', 'allowsmilie',		'1');
				$try->set_value('nonmandatory', 'showsignature',	$data['HAS_SIGNATURE']);
				$try->set_value('nonmandatory', 'ipaddress',		$data['POSTER_IP']);
				$try->set_value('nonmandatory', 'visible',			$data['IS_MESSAGE_VISIBLE']);
				$try->set_value('nonmandatory', 'importpostid',		$post_id);

			// Check if post object is valid
				if($try->is_valid())
				{
					if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						if(shortoutput)
						{
							$displayobject->display_now('.');
						}
						else
						{						
							$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','username'));
						}
						
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($post_details['MESSAGE_OID'], $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
					}
				}
				else
				{
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}// End foreach

			// Check for page end
			if ($post_array['count'] == 0 OR $post_array['count'] < $post_per_page)
			{
				$sessionobject->add_session_var('nonarchivefinished','TRUE');
				$sessionobject->add_session_var('poststartat','0');
				$sessionobject->add_session_var('postdone','0');
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
			else
			{
				$sessionobject->set_session_var('poststartat',	$post_array['lastid']);
				#$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else # NOW DO THE ARCHIVE POSTS
		{
			// Get an array of post details
			$post_array 	= $this->get_eve_archive_post_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page);

			// Display count and pass time
			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $post_array['count'] . " archive {$displayobject->phrases['posts']}</h4>");

			foreach ($post_array['data'] as $post_id => $data)
			{
				$try = (phpversion() < '5' ? $post_object : clone($post_object));
				
			// Mandatory
				$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $data['TOPIC_OID']));
				$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $data['AUTHOR_OID']));
				$try->set_value('mandatory', 'importthreadid',		$data['TOPIC_OID']);
				
			// Non Mandatory
				$try->set_value('nonmandatory', 'parentid',			'0');
				$try->set_value('nonmandatory', 'username',			$idcache->get_id('username', $data['AUTHOR_OID']));
				$try->set_value('nonmandatory', 'dateline',			@strtotime($data['DATETIME_CREATED']));
				$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($data['BODY']));
				$try->set_value('nonmandatory', 'allowsmilie',		'1');
				$try->set_value('nonmandatory', 'showsignature',	'1');
				$try->set_value('nonmandatory', 'ipaddress',		$data['POSTER_IP']);
				$try->set_value('nonmandatory', 'visible',			$data['IS_VISIBLE']);
				$try->set_value('nonmandatory', 'importpostid',		$post_id);

				// Check if post object is valid
				if($try->is_valid())
				{
					if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $user_name_array["$post_details[AUTHOR_OID]"]);
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($post_details['MESSAGE_OID'], $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}// End foreach

			// The real end
			if ($post_array['count'] == 0 OR $post_array['count'] < $post_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->display_now($displayobject->phrases['updating_parent_id']);

				if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now($displayobject->phrases['successful']);
				}
				else
				{
					$displayobject->display_now($displayobject->phrases['failed']);
				}

				$displayobject->update_html($displayobject->module_finished(
					"{$displayobject->phrases['import']} {$displayobject->phrases['posts']}",
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num ,'FINISHED');
				$sessionobject->set_session_var('import_post','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
			else
			{
				$sessionobject->set_session_var('poststartat',	$post_array['lastid']);
				$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
			}
		}// Else Archive
	}// End resume
}//End Class
# Autogenerated on : March 8, 2005, 12:17 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

