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
* @package			ImpEx.expressionengine
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class expressionengine_006 extends expressionengine_000
{
	var $_dependent = '005';

	function expressionengine_006($displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_thread'];
	}

	function init($sessionobject, $displayobject, $Db_target, $Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['threads_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['thread_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_thread']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

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
		$idcache 		= new ImpExCache($Db_target, $t_db_type, $t_tb_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}forum_topics", 'topic_id', 0, $start_at, $per_page);
		$forum_ids_array = $this->get_forum_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['threads'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'thread');
		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));

			// Mandatory
			$try->set_value('mandatory', 'title',				$data['title']);
			$try->set_value('mandatory', 'importforumid',		$data['forum_id']);
			$try->set_value('mandatory', 'importthreadid',		$import_id);
			$try->set_value('mandatory', 'forumid',				$forum_ids_array["$data[forum_id]"]);

			// Non mandatory
			$try->set_value('nonmandatory', 'visible',			'1');
			$try->set_value('nonmandatory', 'sticky',			($data['sticky'] == 'n' ? '0' : '1'));
			$try->set_value('nonmandatory', 'views',			$data['thread_views']);
			$try->set_value('nonmandatory', 'open',				($data['status'] == 'o' ? '1' : '0'));
			$try->set_value('nonmandatory', 'replycount',		$data['thread_total']);
			$try->set_value('nonmandatory', 'postusername',		$idcache->get_id('username', $data['author_id']));
			$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $data['author_id']));
			$try->set_value('nonmandatory', 'lastposter',		$idcache->get_id('username', $data[' last_post_author_id']));
			$try->set_value('nonmandatory', 'dateline',			$data['topic_date']);
			

			// Check if object is valid
			if($try->is_valid())
			{
				if($new_thread_id = $try->import_thread($Db_target, $t_db_type, $t_tb_prefix))
				{
					// Create and import the first post.
					$post_try = (phpversion() < '5' ? $post_object : clone($post_object));
					
					// Mandatory
					$post_try->set_value('mandatory', 'threadid',			$new_thread_id);
					$post_try->set_value('mandatory', 'importthreadid',		$import_id);
					$post_try->set_value('mandatory', 'userid',				$idcache->get_id('user', $data['author_id']));
		
					// Non mandatory
					$post_try->set_value('nonmandatory', 'visible',			'1');
					$post_try->set_value('nonmandatory', 'ipaddress',		$data['ip_address']);
					$post_try->set_value('nonmandatory', 'showsignature',	'1');
					$post_try->set_value('nonmandatory', 'allowsmilie',		($data['parse_smilieys'] == 'y' ? '1' : '0'));
					$post_try->set_value('nonmandatory', 'pagetext',		$this->html_2_bb($data['body']));
					$post_try->set_value('nonmandatory', 'dateline',		$data['topic_date']);
					$post_try->set_value('nonmandatory', 'title',			$data['title']);
					$post_try->set_value('nonmandatory', 'username',		$idcache->get_id('username', $data['author_id']));
					$post_try->set_value('nonmandatory', 'parentid',		'0');
					$post_try->set_value('nonmandatory', 'importpostid',	$import_id);
					
					$post_try->import_post($Db_target, $t_db_type, $t_tb_prefix);
					
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $data['title']);
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['thread_not_imported']}");
				}// $try->import_thread
			}
			else
			{
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}// is_valid
			unset($try);
		}// End foreach

		// Check for page end
		if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var("{$class_num}_start");

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num , 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
		}

		$sessionobject->set_session_var('startat', $data_array['lastid']);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : March 18, 2008, 10:20 am
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
