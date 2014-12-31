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
* ikon_mysql_003 Import Usergroup module
*
* @package			ImpEx.ikon_mysql
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ikon_mysql_003 extends ikon_mysql_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Usergroup';


	function ikon_mysql_003()
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
		$usergroup_array 	= $this->get_ikon_mysql_usergroup_details($Db_source, $source_database_type, $source_table_prefix, $usergroup_start_at, $usergroup_per_page);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($usergroup_array) . ' usergroups</h4><p><b>From</b> : ' . $usergroup_start_at . ' ::  <b>To</b> : ' . ($usergroup_start_at + count($usergroup_array)) . '</p>');


		$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');


		foreach ($usergroup_array as $usergroup_id => $usergroup_details)
		{
			$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
			// Mandatory
			$try->set_value('mandatory', 'importusergroupid',			$usergroup_id);


			// Non Mandatory
			$try->set_value('nonmandatory', 'title',				$usergroup_details['TITLE']);
			$try->set_value('nonmandatory', 'description',			$usergroup_details['TITLE']);
			$try->set_value('nonmandatory', 'usertitle',			$usergroup_details['TITLE']);
			$try->set_value('nonmandatory', 'passwordexpires',		'0');
			$try->set_value('nonmandatory', 'passwordhistory',		'0');
			$try->set_value('nonmandatory', 'pmquota',				$usergroup_details['MAX_MESSAGES']);
			$try->set_value('nonmandatory', 'pmsendmax',			'5');
			$try->set_value('nonmandatory', 'pmforwardmax',			'5');
			#$try->set_value('nonmandatory', 'opentag',				$usergroup_details['opentag']);
			#$try->set_value('nonmandatory', 'closetag',			$usergroup_details['closetag']);
			#$try->set_value('nonmandatory', 'canoverride',			$usergroup_details['canoverride']);
			$try->set_value('nonmandatory', 'ispublicgroup',		'1');

			$forum_permissions = 0;

			#'canview'           => 1,
			if($usergroup_details['VIEW_BOARD'])					{ $forum_permissions += 1; }

			#'canviewothers'     => 2,
			// Not by default

			#'canview'           => 4,
			if($usergroup_details['USE_SEARCH'])					{ $forum_permissions += 4; }

			#'canemail'          => 8,
			if($usergroup_details['EMAIL_FRIEND'])					{ $forum_permissions += 8; }

			#'canpostnew'        => 16,
			$forum_permissions += 16;

			#'canreplyown'       => 32,
			if($usergroup_details['REPLY_OWN_TOPICS']) 				{ $forum_permissions += 32; }

			#'canreplyothers'    => 64,
			if($usergroup_details['REPLY_OTHER_TOPICS']) 			{ $forum_permissions += 64; }

			#'caneditpost'       => 128,
			if($usergroup_details['EDIT_OWN_POSTS']) 				{ $forum_permissions += 128; }

			#'candeletepost'     => 256,
			if($usergroup_details['DELETE_OWN_POSTS']) 				{ $forum_permissions += 256; }

			#'candeletethread'   => 512,
			if($usergroup_details['DELETE_OWN_TOPICS']) 			{ $forum_permissions += 512; }

			#'canopenclose'      => 1024,
			if($usergroup_details['OPEN_CLOSE_TOPICS']) 			{ $forum_permissions += 1024; }

			#'canmove'           => 2048,
			if($usergroup_details['CAN_REMOVE']) 					{ $forum_permissions += 2048; }

			#'cangetattachment'  => 4096,
			$forum_permissions += 4096;

			#'canpostattachment' => 8192,
			$forum_permissions += 8192;

			#'canpostpoll'       => 16384,
			if($usergroup_details['POST_POLLS']) 					{ $forum_permissions += 16384; }

			#'canvote'           => 32768,
			if($usergroup_details['VOTE_POLLS']) 					{ $forum_permissions += 32768; }

			#'canthreadrate'     => 65536,
			$forum_permissions += 8192;

			#'isalwaysmoderated' => 131072,
			// Not by default

			#'canseedelnotice'   => 262144
			// Not by default

			$try->set_value('nonmandatory', 'forumpermissions',		$forum_permissions);
			// Defaults
			$try->set_value('nonmandatory', 'pmpermissions',		'3');
			$try->set_value('nonmandatory', 'calendarpermissions',	'19');
			$try->set_value('nonmandatory', 'wolpermissions',		'1');
			$try->set_value('nonmandatory', 'adminpermissions',		'0');
			$try->set_value('nonmandatory', 'genericpermissions',	'5319');
			$try->set_value('nonmandatory', 'genericoptions',		'30');
			$try->set_value('nonmandatory', 'pmpermissions_bak',	'7');
			$try->set_value('nonmandatory', 'attachlimit',			'0');
			$try->set_value('nonmandatory', 'avatarmaxwidth',		'80');
			$try->set_value('nonmandatory', 'avatarmaxheight',		'80');
			$try->set_value('nonmandatory', 'avatarmaxsize',		'20000');
			$try->set_value('nonmandatory', 'profilepicmaxwidth',	'100');
			$try->set_value('nonmandatory', 'profilepicmaxheight',	'100');
			$try->set_value('nonmandatory', 'profilepicmaxsize',	'65535');


			// Check if usergroup object is valid
			if($try->is_valid())
			{
				if($try->import_usergroup($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: usergroup -> ' . $usergroup_details['TITLE']);
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
		}// End resume


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
# Autogenerated on : May 27, 2004, 1:49 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
