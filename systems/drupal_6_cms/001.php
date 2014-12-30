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
* drupal6_cms_001
*
* @package 		ImpEx.drupal6_cms
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
if (!defined('IDIR'))
{
	die;
}

class drupal_6_cms_001 extends drupal_6_cms_000
{
	function drupal_6_cms_001(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['check_update_db'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$displayobject->update_basic('title', $displayobject->phrases['get_db_info']);
		$displayobject->update_html($displayobject->do_form_header('index','001'));
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['get_db_info']));
		$displayobject->update_html($displayobject->make_hidden_code('database','working'));

		$displayobject->update_html($displayobject->make_description($displayobject->phrases['check_tables']));

		$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['check_update_db'],''));
		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
		$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if (!$sessionobject->get_session_var('sourceexists'))
		{
			$displayobject->display_error($displayobject->phrases['sourceexists_is_false']);
			exit;
		}

		// Setup some working variables
		$displayobject->update_basic('displaymodules','FALSE');
		$target_db_type 		= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix 	= $sessionobject->get_session_var('targettableprefix');
		$source_db_type			= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix 	= $sessionobject->get_session_var('sourcetableprefix');

		$class_num        = substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		$displayobject->update_basic('title',$displayobject->phrases['altering_tables']);
		$displayobject->display_now("<h4>{$displayobject->phrases['altering_tables']}</h4>");
		$displayobject->display_now($displayobject->phrases['alter_desc_1']);
		$displayobject->display_now($displayobject->phrases['alter_desc_2']);
		$displayobject->display_now($displayobject->phrases['alter_desc_3']);
		$displayobject->display_now($displayobject->phrases['alter_desc_4']);

		
		
		// Only need these for CMS imports atm
		unset($this->_import_ids);
		$this->_import_ids[] = array ('cms_article' => 'importcontentid');
		$this->_import_ids[] = array ('user' 		=> 'importuserid');
		$this->_import_ids[] = array ('usernote'	=> 'importusernoteid');
		$this->_import_ids[] = array ('usergroup'	=> 'importusergroupid');

		// Add an importids now
		foreach ($this->_import_ids as $id => $table_array)
		{
			foreach ($table_array as $tablename => $column)
			{
				if ($this->add_import_id($Db_target, $target_db_type, $target_table_prefix, $tablename, $column))
				{
					$displayobject->display_now("\n<br /><b>$tablename</b> - $column <i>{$displayobject->phrases['completed']}</i>");
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['table_alter_fail'], $displayobject->phrases['table_alter_fail_rem']);
				}
			}
		}

		// Check the database connection
		$result = $this->check_database($Db_source, $source_db_type, $source_table_prefix, $sessionobject->get_session_var('sourceexists'));

		$displayobject->display_now($result['text']);

		if ($result['code'])
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num,'_time_taken'),
				$sessionobject->return_stats($class_num,'_objects_done'),
				$sessionobject->return_stats($class_num,'_objects_failed')
			));

			$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FINISHED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_basic('displaymodules','FALSE');
			$displayobject->update_html($displayobject->print_redirect_001('index.php',$sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$sessionobject->add_session_var($class_num . '_objects_failed',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
			$displayobject->update_html($displayobject->make_description("{$displayobject->phrases['failed']} {$displayobject->phrases['check_db_permissions']}"));
			$sessionobject->set_session_var('001','FAILED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_html($displayobject->print_redirect_001('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}
}
/*======================================================================*/
?>
