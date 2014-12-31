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
* ipb3_003 Import Usergroup module
*
* @package			ImpEx.ipb3
* @date				$Date: 2007-08-16 19:01:27 -0700 (Thu, 16 Aug 2007) $
*
*/
class ipb3_003 extends ipb3_000
{
	var $_dependent 	= '001';

	function ipb3_003(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_usergroup'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_usergroups'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['usergroups_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['usergroup_restart_failed'], $displayobject->phrases['check_db_permissions']);				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['usergroups_per_page'],'usergroupperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('usergroupstartat','0');
			$sessionobject->add_session_var('usergroupdone','0');
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
		$displayobject->update_basic('displaymodules', 'FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$usergroup_start_at		= $sessionobject->get_session_var('usergroupstartat');
		$usergroup_per_page		= $sessionobject->get_session_var('usergroupperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of usergroup details
		$usergroup_array 	= $this->get_ipb3_usergroup_details($Db_source, $source_database_type, $source_table_prefix, $usergroup_start_at, $usergroup_per_page);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($usergroup_array) . " {$displayobject->phrases['usergroups']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $usergroup_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($usergroup_start_at + count($usergroup_array)) . "</p>");

		$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

		foreach ($usergroup_array as $usergroup_id => $usergroup_details)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',		$usergroup_id);

			// Set them
			$forumpermissions 		= 0;
			$pmpermissions 			= 0;
			$adminpermissions 		= 0;
			$calendarpermissions	= 0;
			$adminpermissions		= 0;
			$genericpermissions		= 0;
			$genericoptions			= 0;

			// Forum permissions
			if($usergroup_details['g_view_board'])					{ $forumpermissions += 1;}
			if($usergroup_details['g_mem_info'])					{ $forumpermissions += 2;}
			if($usergroup_details['g_use_search'])					{ $forumpermissions += 4;}
			if($usergroup_details['g_email_friend'])				{ $forumpermissions += 8;}

			if($usergroup_details['g_post_new_topics'])				{ $forumpermissions += 16;}
			if($usergroup_details['g_reply_own_topics'])			{ $forumpermissions += 32;}
			if($usergroup_details['g_reply_other_topics'])			{ $forumpermissions += 64;}
			if($usergroup_details['g_edit_posts'])					{ $forumpermissions += 128;}
			if($usergroup_details['g_delete_own_posts'])			{ $forumpermissions += 256;}
			if($usergroup_details['g_delete_own_topics'])			{ $forumpermissions += 512;}
			if($usergroup_details['g_open_close_posts'])			{ $forumpermissions += 1024;}

			#if($usergroup_details['canmove'])						{ $forumpermissions += 2048;}
			#if($usergroup_details['cangetattachment'])				{ $forumpermissions += 4096;}
			if($usergroup_details['g_can_msg_attach'])				{ $forumpermissions += 8192;}

			if($usergroup_details['g_post_polls'])					{ $forumpermissions += 16384;}
			if($usergroup_details['g_vote_polls'])					{ $forumpermissions += 32768;}

			#if($usergroup_details['canthreadrate'])				{ $forumpermissions += 65536;}
			#if($usergroup_details['isalwaysmoderated'])			{ $forumpermissions += 131072;}
			#if($usergroup_details['canseedelnotice'])				{ $forumpermissions += 262144

			// PM permissions
			# Not there yet
			#if($usergroup_details['cantrackpm'])					{ $pmpermissions += 1;}
			#if($usergroup_details['candenypmreceipts'])			{ $pmpermissions += 2;}

			// Calendar permissions
			#if($usergroup_details['canviewcalendar'])				{ $calendarpermissions += 1;}
			if($usergroup_details['g_calendar_post'])				{ $calendarpermissions += 2;$calendarpermissions += 1;}
			#if($usergroup_details['caneditevent'])					{ $calendarpermissions += 4;}
			#if($usergroup_details['candeleteevent'])				{ $calendarpermissions += 8;}
			#if($usergroup_details['canviewothersevent'])			{ $calendarpermissions += 16;}

			// Admin permissions
			if($usergroup_details['g_is_supmod'])					{ $adminpermissions += 1;}
			if($usergroup_details['g_access_cp'])					{ $adminpermissions += 2;}
			#if($usergroup_details['canadminsettings'])				{ $adminpermissions += 4;}
			#if($usergroup_details['canadminstyles'])				{ $adminpermissions += 8;}
			#if($usergroup_details['canadminlanguages'])			{ $adminpermissions += 16;}
			#if($usergroup_details['canadminforums'])				{ $adminpermissions += 32;}
			#if($usergroup_details['canadminthreads'])				{ $adminpermissions += 64;}
			#if($usergroup_details['canadmincalendars'])			{ $adminpermissions += 128;}
			#if($usergroup_details['canadminusers'])				{ $adminpermissions += 256;}
			#if($usergroup_details['canadminpermissions'])			{ $adminpermissions += 512;}
			#if($usergroup_details['canadminfaq'])					{ $adminpermissions += 1024;}
			#if($usergroup_details['canadminimages'])				{ $adminpermissions += 2048;}
			#if($usergroup_details['canadminbbcodes'])				{ $adminpermissions += 4096;}
			#if($usergroup_details['canadmincron'])					{ $adminpermissions += 8192;}
			#if($usergroup_details['canadminmaintain'])				{ $adminpermissions += 16384;}
			#if($usergroup_details['canadminupgrade'])				{ $adminpermissions += 32768;}

			// Generic permissions
			#if($usergroup_details['canviewmembers'])				{ $genericpermissions += 1;}
			#if($usergroup_details['canmodifyprofile'])				{ $genericpermissions += 2;}
			if($usergroup_details['g_hide_from_list'])				{ $genericpermissions += 4;}
			#if($usergroup_details['canviewothersusernotes'])		{ $genericpermissions += 8;}
			#if($usergroup_details['canmanageownusernotes'])		{ $genericpermissions += 16;}
			#if($usergroup_details['canseehidden'])					{ $genericpermissions += 32;}
			#if($usergroup_details['canbeusernoted'])				{ $genericpermissions += 64;}
			#if($usergroup_details['canprofilepic'])				{ $genericpermissions += 128;}
			#if($usergroup_details['canuseavatar'])					{ $genericpermissions += 512;}
			#if($usergroup_details['canusesignature'])				{ $genericpermissions += 1024;}
			#if($usergroup_details['canusecustomtitle'])			{ $genericpermissions += 2048;}
			#if($usergroup_details['canseeprofilepic'])				{ $genericpermissions += 4096;}
			#if($usergroup_details['canviewownusernotes'])			{ $genericpermissions += 8192;}
			#if($usergroup_details['canmanageothersusernotes'])		{ $genericpermissions += 16384;}
			#if($usergroup_details['canpostownusernotes'])			{ $genericpermissions += 32768;}
			#if($usergroup_details['canpostothersusernotes'])		{ $genericpermissions +=  65536;}
			#if($usergroup_details['caneditownusernotes'])			{ $genericpermissions += 131072;}
			#if($usergroup_details['canseehiddencustomfields'])		{ $genericpermissions += 262144;}
			// Reputation
			#if($usergroup_details['canseeownrep'])					{ $genericpermissions += 256;}
			#if($usergroup_details['canuserep'])					{ $genericpermissions += 524288;}
			#if($usergroup_details['canhiderep'])					{ $genericpermissions += 1048576;}
			#if($usergroup_details['cannegativerep'])				{ $genericpermissions += 2097152;}
			#if($usergroup_details['canseeothersrep'])				{ $genericpermissions += 4194304;}
			#if($usergroup_details['canhaverepleft'])				{ $genericpermissions += 8388608;}

			// Generic options
			#if($usergroup_details['showgroup'])					{ $genericoptions += 1;}
			#if($usergroup_details['showbirthday'])					{ $genericoptions += 2;}
			#if($usergroup_details['showmemberlist'])				{ $genericoptions += 4;}
			#if($usergroup_details['showeditedby'])					{ $genericoptions += 8;}
			#if($usergroup_details['allowmembergroups'])			{ $genericoptions += 16;}
			#if($usergroup_details['isbannedgroup'])				{ $genericoptions += 32;}

			// Non Mandatory
			$try->set_value('nonmandatory','title',						$usergroup_details['g_title']);
			$try->set_value('nonmandatory','description',				'Imported group');
			#$try->set_value('nonmandatory','usertitle',				$usergroup_details['usertitle']);
			#$try->set_value('nonmandatory','passwordexpires',			$usergroup_details['passwordexpires']);
			#$try->set_value('nonmandatory','passwordhistory',			$usergroup_details['passwordhistory']);
			$try->set_value('nonmandatory','pmquota',					$usergroup_details['g_max_messages']);
			$try->set_value('nonmandatory','pmsendmax',					$usergroup_details['g_max_mass_pm']);
			#$try->set_value('nonmandatory','pmforwardmax',				$usergroup_details['pmforwardmax']);
			$try->set_value('nonmandatory','opentag',					addslashes($usergroup_details['prefix']));
			$try->set_value('nonmandatory','closetag',					addslashes($usergroup_details['suffix']));
			$try->set_value('nonmandatory','canoverride',				$usergroup_details['canoverride']);
			$try->set_value('nonmandatory','ispublicgroup',				$usergroup_details['ispublicgroup']);
			$try->set_value('nonmandatory','forumpermissions',			$forumpermissions);
			$try->set_value('nonmandatory','pmpermissions',				$pmpermissions);
			$try->set_value('nonmandatory','calendarpermissions',		$calendarpermissions);
			#$try->set_value('nonmandatory','wolpermissions',			$usergroup_details['wolpermissions']);
			$try->set_value('nonmandatory','adminpermissions',			$usergroup_details['adminpermissions']);
			$try->set_value('nonmandatory','genericpermissions',		$genericpermissions);
			$try->set_value('nonmandatory','genericoptions',			$genericoptions);
			#$try->set_value('nonmandatory','pmpermissions_bak',		$usergroup_details['pmpermissions_bak']);
			$try->set_value('nonmandatory','attachlimit',				$usergroup_details['g_attach_per_post']);
			#$try->set_value('nonmandatory','avatarmaxwidth',			$usergroup_details['avatarmaxwidth']);
			#$try->set_value('nonmandatory','avatarmaxheight',			$usergroup_details['avatarmaxheight']);
			#$try->set_value('nonmandatory','avatarmaxsize',			$usergroup_details['avatarmaxsize']);
			#$try->set_value('nonmandatory','profilepicmaxwidth',		$usergroup_details['profilepicmaxwidth']);
			#$try->set_value('nonmandatory','profilepicmaxheight',		$usergroup_details['profilepicmaxheight']);
			#$try->set_value('nonmandatory','profilepicmaxsize',		$usergroup_details['profilepicmaxsize']);

			// Check if usergroup object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $usergroup_details['g_title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $usergroup_id,$displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['usergroup_not_imported']}");
				}
			}
			else
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $usergroup_id, $displayobject->phrases['invalid_object'], $try->_failedon);
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($usergroup_array) == 0 OR count($usergroup_array) < $usergroup_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_usergroup','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('usergroupstartat',$usergroup_start_at+$usergroup_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 20; 2004; 2:31 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
