<?php if (!defined('IDIR')) { die; }
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
* vb2_005 Import Forum module
*
* @package			ImpEx.vb2
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb2_005 extends vb2_000
{
	var $_dependent 	= '004';

	function vb2_005(&$displayobject)
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

		// Get an array of forum details
		$forum_array 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $forum_start_at, $forum_per_page, 'forum', 'forumid');
		$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($forum_array) . ' forums</h4><p><b>From</b> : ' . $forum_start_at . ' ::  <b>To</b> : ' . ($forum_start_at + count($forum_array)) . '</p>');

		$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

		foreach ($forum_array as $forum_id => $forum_details)
		{
			$try = (phpversion() < '5' ? $forum_object : clone($forum_object));
			// Mandatory
			$try->set_value('mandatory', 'title',					$forum_details['title']);
			$try->set_value('mandatory', 'displayorder',			$forum_details['displayorder']);
			$try->set_value('mandatory', 'parentid',				$forum_details['parentid']);
			$try->set_value('mandatory', 'importforumid',			$forum_id);
			$try->set_value('mandatory', 'importcategoryid',		'0'); // N/A Though here for completeness

			// Non Mandatory
			$try->set_value('nonmandatory', 'styleid',				$forum_details['styleid']);
			$try->set_value('nonmandatory', 'description',			$forum_details['description']);

			// OPTIONS
				$options = 0;
				if($forum_details['active'])					{ $options += 1; }
				if($forum_details['allowposting'])				{ $options += 2; }
				if($forum_details['cancontainthreads'])			{ $options += 4; }
				if($forum_details['moderatenew'])
				{
						$options += 8; 	// post
						$options += 16; // thread
				}

				if($forum_details['moderateattach'])			{ $options += 32; }
				if($forum_details['allowbbcode'])				{ $options += 64; }
				if($forum_details['allowimages'])				{ $options += 128; }
				if($forum_details['allowhtml'])					{ $options += 256; }
				if($forum_details['allowsmilies'])				{ $options += 512; }
				if($forum_details['allowicons'])				{ $options += 1024; }
				if($forum_details['allowratings'])				{ $options += 2048; }
				if($forum_details['countposts'])				{ $options += 4096; }
				#'canhavepassword'   => 8192,
				#'indexposts'        => 16384,
				if($forum_details['styleoverride'])				{ $options += 32768; }
				#'showonforumjump'   => 65536,
				#'warnall'           => 131072
			// OPTIONS.

			$try->set_value('mandatory', 'options',					$options);
			$try->set_value('nonmandatory', 'replycount',			$forum_details['replycount']);
			#$try->set_value('nonmandatory', 'lastpost',			$forum_details['lastpost']);
			$try->set_value('nonmandatory', 'lastposter',			$forum_details['lastposter']);
			#$try->set_value('nonmandatory', 'lastthread',			$forum_details['lastthread']);
			#$try->set_value('nonmandatory', 'lastthreadid',		$forum_details['lastthreadid']);
			#$try->set_value('nonmandatory', 'lasticonid',			$forum_details['lasticonid']);
			$try->set_value('nonmandatory', 'threadcount',			$forum_details['threadcount']);
			$try->set_value('nonmandatory', 'daysprune',			$forum_details['daysprune']);
			$try->set_value('nonmandatory', 'newpostemail',			$forum_details['newpostemail']);
			$try->set_value('nonmandatory', 'newthreademail',		$forum_details['newthreademail']);
			$try->set_value('nonmandatory', 'parentlist',			$forum_details['parentlist']);
			#$try->set_value('nonmandatory', 'password',			$forum_details['password']);
			$try->set_value('nonmandatory', 'link',					$forum_details['link']);
			$try->set_value('nonmandatory', 'childlist',			$forum_details['childlist']);

			// Check if forum object is valid
			if($try->is_valid())
			{
				if($try->import_vb2_forum($Db_target, $target_database_type, $target_table_prefix))
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
		}// End foreach

		// Check for page end
		if (count($forum_array) == 0 OR count($forum_array) < $forum_per_page)
		{

			// parentid && parentlist updating mapping
			$this->update_vb2_imported_parent_forum_ids($Db_target, $target_database_type, $target_table_prefix);
			$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);
			
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : July 6, 2004, 4:33 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>

