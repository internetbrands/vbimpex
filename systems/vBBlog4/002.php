<?php
if (!defined('IDIR')) { die; }
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
* vBulletin Suite Blog 4.x
*
* @package 		ImpEx.vBulletinBlog4
* @version
* @author
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class vBBlog4_002 extends vBBlog4_000
{
	var $_dependent = '001';

	function vBBlog4_002(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_blog_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_blog_users'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['blog_users_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['blog_user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title' , $displayobject->phrases['import_blog_user']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring ));

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
		$ImpExData_blog_user = new ImpExData($Db_target, $sessionobject, 'blog_user', 'blog');

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}blog_user", 'bloguserid', 0, $start_at, $per_page);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['blog_users'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'blog_user');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$blog_user 		= (phpversion() < '5' ? $ImpExData_blog_user : clone($ImpExData_blog_user));

			// Mandatory set during user import as well
			$blog_user->set_value('mandatory', 'importbloguserid',		$import_id);

			// Non mandatory

			$blog_user->set_value('nonmandatory', 'title',				$data['title']);

			$blog_user->set_value('nonmandatory', 'title',				$data['title']);
			$blog_user->set_value('nonmandatory', 'description',		$data['description']);
			$blog_user->set_value('nonmandatory', 'allowsmilie',		$data['allowsmilie']);
			$blog_user->set_value('nonmandatory', 'options',			$data['options']);
			$blog_user->set_value('nonmandatory', 'viewoption',			$data['viewoption']);
			$blog_user->set_value('nonmandatory', 'comments',			$data['comments']);
			#$blog_user->set_value('nonmandatory', 'lastblog',			$data['lastblog']);
			#$blog_user->set_value('nonmandatory', 'lastblogid',		$data['lastblogid']);
			#$blog_user->set_value('nonmandatory', 'lastblogtitle',		$data['lastblogtitle']);
			#$blog_user->set_value('nonmandatory', 'lastcomment',		$data['lastcomment']);
			#$blog_user->set_value('nonmandatory', 'lastcommenter',		$data['lastcommenter']);
			#$blog_user->set_value('nonmandatory', 'lastblogtextid',	$data['lastblogtextid']);
			$blog_user->set_value('nonmandatory',  'entries',			$data['entries']);
			$blog_user->set_value('nonmandatory', 'deleted',			$data['deleted']);
			$blog_user->set_value('nonmandatory', 'moderation',			$data['moderation']);
			$blog_user->set_value('nonmandatory', 'draft',				$data['draft']);
			$blog_user->set_value('nonmandatory', 'pending',			$data['pending']);
			$blog_user->set_value('nonmandatory', 'ratingnum',			$data['ratingnum']);
			$blog_user->set_value('nonmandatory', 'ratingtotal',		$data['ratingtotal']);
			$blog_user->set_value('nonmandatory', 'rating',				$data['rating']);
			$blog_user->set_value('nonmandatory', 'subscribeown',		$data['subscribeown']);
			$blog_user->set_value('nonmandatory', 'subscribeothers',	$data['subscribeothers']);
			$blog_user->set_value('nonmandatory', 'uncatentries',		$data['uncatentries']);
			$blog_user->set_value('nonmandatory', 'options_member',		$data['options_member']);
			$blog_user->set_value('nonmandatory', 'options_guest',		$data['options_guest']);
			$blog_user->set_value('nonmandatory', 'options_buddy',		$data['options_buddy']);
			$blog_user->set_value('nonmandatory', 'options_ignore',		$data['options_ignore']);
			$blog_user->set_value('nonmandatory', 'isblogmoderator',	$data['isblogmoderator']);
			$blog_user->set_value('nonmandatory', 'comments_moderation',	$data['comments_moderation']);
			$blog_user->set_value('nonmandatory', 'comments_deleted',	$data['comments_deleted']);
			#$blog_user->set_value('nonmandatory', 'tagcloud',			$data['tagcloud']);
			$blog_user->set_value('nonmandatory', 'sidebar',			$data['sidebar']);
			$blog_user->set_value('nonmandatory', 'custompages',		$data['custompages']);
			$blog_user->set_value('nonmandatory', 'customblocks',		$data['customblocks']);

			$userid = $idcache->get_id('user', $data['bloguserid']);
			if (!$data['memberids'])
			{
				$blog_user->set_value('nonmandatory', 'memberids',		$userid);
			}
			else
			{
				$ids = $this->get_import_ids_from_list($Db_target, $t_db_type, $t_tb_prefix, $data['memberids']);
				$memberids = implode(',', $ids);
				$blog_user->set_value('nonmandatory', 'memberids', $memberids ? $memberids : $userid);
			}

			if (!$data['memberblogids'])
			{
				$blog_user->set_value('nonmandatory', 'memberblogids',	$userid);
			}
			else
			{
				$ids = $this->get_import_ids_from_list($Db_target, $t_db_type, $t_tb_prefix, $data['memberblogids']);
				$memberblogids = implode(',', $ids);
				$blog_user->set_value('nonmandatory', 'memberblogids', $memberblogids ? $memberblogids : $userid);
			}
			$blog_user->set_value('mandatory', 'bloguserid', $userid);

			// Check if object is valid
			if($blog_user->is_valid())
			{
				if($blog_user->import_blog_user($Db_target, $t_db_type, $t_tb_prefix))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $blog_user->how_complete() . '%</b></span> ' . $displayobject->phrases['blog_user'] . ' -> ' . $idcache->get_id('username', $data['bloguserid']));
					}
					$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['blog_user_not_imported'], $displayobject->phrases['blog_user_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['blog_user_not_imported']}");
				}// $blog_user->import_blog_user
			} // $blog_user->is_valid()
			else
			{
				// $vbulletin_user->is_valid()
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $blog_user->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $blog_user->_failedon);
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
# Autogenerated on : August 29, 2007, 2:02 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
