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
* ipb_003 Import Users groups
*
*
* @package 		ImpEx.ipb
*
*/
if (!class_exists('ipb_000')) { die('Direct class access violation'); }

class ipb_003 extends ipb_000
{
	var $_dependent 	= '001';

	function ipb_003(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_usergroup'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_user_groups'))
				{
					$this->_restart = true;
					$displayobject->display_now("<h4>Imported user groups have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_non_admin_users",
											 'Check database permissions and user table');
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			$displayobject->update_html($displayobject->make_description($displayobject->phrases['start_import']));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));

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
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get a page worths of users groups
		$user_groups_array  =  $this->get_ipb_user_group_details($Db_source, $source_database_type, $source_table_prefix);

		// Got some numbers, lets try putting it in the dB
		$usergroup_object = new ImpExData($Db_target, $sessionobject,'usergroup');

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($user_groups_array) . " {$displayobject->phrases['usergroups']}</h4>");

		foreach ($user_groups_array as $user_group_id => $user_group)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
			/*
			g_invite_friend
			g_use_pm
			g_is_supmod
			g_can_remove
			g_append_edit
			g_access_offline
			g_avoid_q
			g_avoid_flood
			g_icon
			g_attach_max
			g_search_flood
			g_edit_cutoff
			g_promotion
			g_hide_from_list
			g_post_closed
			g_view_shoutbox
			g_post_shoutbox
			g_perm_id
			g_dohtml
			g_edit_topic
			g_email_limit
			*/

			$forumpermissions = 0;
			// And they were in order as well.
			if($user_group['g_view_board'])			{ $forumpermissions + 1; }
			if($user_group['g_other_topics'])		{ $forumpermissions + 2; }
			if($user_group['g_use_search'])			{ $forumpermissions + 4; }
			if($user_group['g_email_friend'])		{ $forumpermissions + 8; }
			if($user_group['g_post_new_topics'])	{ $forumpermissions + 16; }
			if($user_group['g_reply_own_topics'])	{ $forumpermissions + 32; }
			if($user_group['g_reply_other_topics'])	{ $forumpermissions + 64; }
			if($user_group['g_edit_posts'])			{ $forumpermissions + 128; }
			if($user_group['g_delete_own_posts'])	{ $forumpermissions + 256; }
			if($user_group['g_delete_own_topics'])	{ $forumpermissions + 512; }
			if($user_group['g_open_close_posts'])	{ $forumpermissions + 1024; }
			if($user_group['g_post_polls'])			{ $forumpermissions + 16384; }
			if($user_group['g_vote_polls'])			{ $forumpermissions + 32768; }

			$calendarpermissions = 0;
			// If they can post they can see it .....
			if($user_group['g_calendar_post'])		{ $calendarpermissions + 3; }

			$adminpermissions = 0;

			if($user_group['g_access_cp'])			{ $adminpermissions + 2; }

			$genericpermissions = 0;

			if($user_group['g_mem_info'])			{ $genericpermissions + 1; }
			if($user_group['g_edit_profile'])		{ $genericpermissions + 2; }
			if($user_group['g_avatar_upload'])		{ $genericpermissions + 512; }



			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',			$user_group_id);

			// Non mandatory
			$try->set_value('nonmandatory', 'title',					$user_group['g_title']);

			$try->set_value('nonmandatory', 'usertitle',				$user_group['g_title']);

			$try->set_value('nonmandatory', 'pmsendmax',				$user_group['g_max_mass_pm']);
			$try->set_value('nonmandatory', 'pmforwardmax',				$user_group['g_max_mass_pm']);
			$try->set_value('nonmandatory', 'opentag',					$user_group['prefix']);
			$try->set_value('nonmandatory', 'closetag',					$user_group['suffix']);

			$try->set_value('nonmandatory', 'forumpermissions',			$forumpermissions);

			$try->set_value('nonmandatory', 'adminpermissions',			$adminpermissions);
			$try->set_value('nonmandatory', 'genericpermissions',		$genericpermissions);
			$try->set_value('nonmandatory', 'genericoptions',			$genericpermissions);

			$image_attribs = explode(':',$user_group['g_photo_max_vars']);
			$try->set_value('nonmandatory', 'avatarmaxwidth',			$image_attribs[1]);
			$try->set_value('nonmandatory', 'avatarmaxheight',			$image_attribs[2]);
			$try->set_value('nonmandatory', 'avatarmaxsize',			$image_attribs[0]);

			//$try->set_value('nonmandatory', 'description',			$user_group['']);
			//$try->set_value('nonmandatory', 'passwordexpires',		$user_group['']);
			//$try->set_value('nonmandatory', 'passwordhistory',		$user_group['']);
			//$try->set_value('nonmandatory', 'pmquota',				$user_group['']);
			//$try->set_value('nonmandatory', 'canoverride',			$user_group['']);
			//$try->set_value('nonmandatory', 'ispublicgroup',			$user_group['']);
			//$try->set_value('nonmandatory', 'pmpermissions',			$user_group['']);
			//$try->set_value('nonmandatory', 'calendarpermissions',	$user_group['']);
			//$try->set_value('nonmandatory', 'wolpermissions',			$user_group['']);
			//$try->set_value('nonmandatory', 'attachlimit',			$user_group['']);
			//$try->set_value('nonmandatory', 'profilepicmaxwidth',		$user_group['']);
			//$try->set_value('nonmandatory', 'profilepicmaxheight',	$user_group['']);
			//$try->set_value('nonmandatory', 'profilepicmaxsize',		$user_group['']);



			if($try->is_valid())
			{
				if($try->import_user_group($Db_target,$target_database_type,$target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $user_group['g_title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($usergroup_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['usergroup_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
			}
		}

		$sessionobject->timing($class_num ,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

		$sessionobject->set_session_var($class_num ,'FINISHED');
		$sessionobject->set_session_var('module','000');
		$sessionobject->set_session_var('autosubmit','0');
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*/
?>
