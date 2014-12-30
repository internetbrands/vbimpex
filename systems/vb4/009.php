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
* vb4 Import Posts
*
* @package 		ImpEx.vb4
* @version		$Revision: 1782 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2007-07-23 14:13:50 -0700 (Mon, 23 Jul 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb4_009 extends vb4_000
{
	var $_dependent 	= '007';

	function vb4_009(&$displayobject)
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
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_post']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'],'postperpage',2000));

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
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
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

		$post_start_at 			= $sessionobject->get_session_var('poststartat');
		$post_per_page 			= $sessionobject->get_session_var('postperpage');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		if(intval($post_per_page) == 0)
		{
			$post_per_page = 150;
		}

		$post_array	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page, 'post', 'postid');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($post_array) . " {$displayobject->phrases['posts']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $post_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($post_start_at + count($post_array)) . "</p>");

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		foreach ($post_array as $post_id => $details)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));

			// Mandatory
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $details['threadid']));
			$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $details['userid']));
			$try->set_value('mandatory', 'importthreadid',		$details['threadid']);

			// Non Mandatory
			$try->set_value('nonmandatory', 'parentid', 		$idcache->get_id('post', $details['parentid']));
			$try->set_value('nonmandatory', 'username',			$details['username']);
			$try->set_value('nonmandatory', 'title',			$details['title']);
			$try->set_value('nonmandatory', 'dateline',			$details['dateline']);
			$try->set_value('nonmandatory', 'pagetext',			$details['pagetext']);
			$try->set_value('nonmandatory', 'allowsmilie',		$details['allowsmilie']);
			$try->set_value('nonmandatory', 'showsignature',	$details['showsignature']);
			$try->set_value('nonmandatory', 'ipaddress',		$details['ipaddress']);
			$try->set_value('nonmandatory', 'iconid',			$details['iconid']);
			$try->set_value('nonmandatory', 'visible',			$details['visible']);
			$try->set_value('nonmandatory', 'attach',			$details['attach']);
			$try->set_value('nonmandatory', 'importpostid',		$details['postid']);

			if($try->is_valid())
			{
				if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory','username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['post_not_imported']}");
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try);
		}



		if (count($post_array) == 0 OR count($post_array) < $post_per_page)
		{

			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

	$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
	$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1782 $
|| ####################################################################
\*======================================================================*/
?>
