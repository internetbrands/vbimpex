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
* deluxeportal_003 Import Usergroup module
*
* @package			ImpEx.deluxeportal
*
*/
class deluxeportal_003 extends deluxeportal_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Usergroup';


	function deluxeportal_003()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_usergroups'))
				{
					$displayobject->display_now('<h4>Imported usergroups have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_usergroups','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Usergroup');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_usergroup','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Usergroups to import per cycle (must be greater than 1)','usergroupperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


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
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
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
		$usergroup_start_at			= $sessionobject->get_session_var('usergroupstartat');
		$usergroup_per_page			= $sessionobject->get_session_var('usergroupperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of usergroup details
		$usergroup_array 	= $this->get_deluxeportal_usergroup_details($Db_source, $source_database_type, $source_table_prefix, $usergroup_start_at, $usergroup_per_page);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($usergroup_array) . ' usergroups</h4><p><b>From</b> : ' . $usergroup_start_at . ' ::  <b>To</b> : ' . ($usergroup_start_at + count($usergroup_array)) . '</p>');


		$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');


		foreach ($usergroup_array as $usergroup_id => $usergroup_details)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));


			// Set them
			$forumpermissions 		= 0;
			$pmpermissions 			= 0;
			$adminpermissions 		= 0;
			$calendarpermissions	= 0;
			$adminpermissions		= 0;
			$genericpermissions		= 0;
			$genericoptions			= 0;


			// Admin permissions
			if($usergroup_details['moderators'])					{ $adminpermissions += 1;}
			#if($usergroup_details['cancontrolpanel'])				{ $adminpermissions += 2;}
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

			// Forum Permissions
			if($usergroup_details['viewforums'])					{ $forumpermissions += 1;}
			if($usergroup_details['view_profile'])					{ $forumpermissions += 2;}
			if($usergroup_details['search'])						{ $forumpermissions += 4;}
			#if($usergroup_details['canemail'])						{ $forumpermissions += 8;}

			if($usergroup_details['postthreads'])					{ $forumpermissions += 16;}
			if($usergroup_details['replytoown'])					{ $forumpermissions += 32;}
			if($usergroup_details['replytoother'])					{ $forumpermissions += 64;}
			if($usergroup_details['editposts'])						{ $forumpermissions += 128;}
			if($usergroup_details['deleteposts'])					{ $forumpermissions += 256;}
			if($usergroup_details['deletethreads'])					{ $forumpermissions += 512;}
			if($usergroup_details['close'])							{ $forumpermissions += 1024;}

			if($usergroup_details['copymove'])						{ $forumpermissions += 2048;}
			if($usergroup_details['viewattachments'])				{ $forumpermissions += 4096;}
			if($usergroup_details['postattachments'])				{ $forumpermissions += 8192;}

			if($usergroup_details['startpolls'])					{ $forumpermissions += 16384;}
			if($usergroup_details['votepolls'])						{ $forumpermissions += 32768;}

			#if($usergroup_details['canthreadrate'])				{ $forumpermissions += 65536;}
			if($usergroup_details['moderators'])					{ $forumpermissions += 131072;}
			#if($usergroup_details['canseedelnotice'])				{ $forumpermissions += 262144

			// Generic permissions
			if($usergroup_details['view_memberlist'])				{ $genericpermissions += 1;}
			if($usergroup_details['edit_profile'])					{ $genericpermissions += 2;}
			if($usergroup_details['whos_online'])				{ $genericpermissions += 4;}
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

			// Generic options
			#if($usergroup_details['showgroup'])					{ $genericoptions += 1;}
			#if($usergroup_details['showbirthday'])					{ $genericoptions += 2;}
			#if($usergroup_details['showmemberlist'])				{ $genericoptions += 4;}
			#if($usergroup_details['showeditedby'])					{ $genericoptions += 8;}
			#if($usergroup_details['allowmembergroups'])			{ $genericoptions += 16;}
			#if($usergroup_details['isbannedgroup'])				{ $genericoptions += 32;}

			//*************

			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',		$usergroup_id);

			// Non Mandatory
			$try->set_value('nonmandatory', 'title',				$usergroup_details['name']);
			$try->set_value('nonmandatory', 'description',			$usergroup_details['name']);
			#$try->set_value('nonmandatory', 'usertitle',			$usergroup_details['usertitle']);
			#$try->set_value('nonmandatory', 'passwordexpires',		$usergroup_details['passwordexpires']);
			#$try->set_value('nonmandatory', 'passwordhistory',		$usergroup_details['passwordhistory']);
			$try->set_value('nonmandatory', 'pmquota',				$usergroup_details['maxpm']);
			$try->set_value('nonmandatory', 'pmsendmax',			$usergroup_details['max_recipients']);
			$try->set_value('nonmandatory', 'pmforwardmax',			$usergroup_details['max_recipients']);
			#$try->set_value('nonmandatory', 'opentag',				$usergroup_details['opentag']);
			#$try->set_value('nonmandatory', 'closetag',			$usergroup_details['closetag']);
			#$try->set_value('nonmandatory', 'canoverride',			$usergroup_details['canoverride']);
			#$try->set_value('nonmandatory', 'ispublicgroup',		$usergroup_details['ispublicgroup']);
			$try->set_value('nonmandatory', 'forumpermissions',		$forumpermissions);
			$try->set_value('nonmandatory', 'pmpermissions',		$pmpermissions);
			$try->set_value('nonmandatory', 'calendarpermissions',	$calendarpermissions);
			#$try->set_value('nonmandatory', 'wolpermissions',		$usergroup_details['wolpermissions']);
			$try->set_value('nonmandatory', 'adminpermissions',		$adminpermissions);
			$try->set_value('nonmandatory', 'genericpermissions',	$genericpermissions);

			$try->set_value('nonmandatory', 'genericoptions',		$genericoptions);
			$try->set_value('nonmandatory', 'pmpermissions_bak',	$usergroup_details['pmpermissions_bak']);
			#$try->set_value('nonmandatory', 'attachlimit',			$usergroup_details['attachlimit']);
			#$try->set_value('nonmandatory', 'avatarmaxwidth',		$usergroup_details['avatarmaxwidth']);
			#$try->set_value('nonmandatory', 'avatarmaxheight',		$usergroup_details['avatarmaxheight']);
			#$try->set_value('nonmandatory', 'avatarmaxsize',		$usergroup_details['avatarmaxsize']);
			#$try->set_value('nonmandatory', 'profilepicmaxwidth',	$usergroup_details['profilepicmaxwidth']);
			#$try->set_value('nonmandatory', 'profilepicmaxheight',	$usergroup_details['profilepicmaxheight']);
			#$try->set_value('nonmandatory', 'profilepicmaxsize',	$usergroup_details['profilepicmaxsize']);


			// Check if usergroup object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: usergroup -> ' . $usergroup_details['name']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar usergroup and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid usergroup object, skipping." . $try->_failedon);
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
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('usergroupstartat',$usergroup_start_at+$usergroup_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : September 11, 2004, 2:50 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
