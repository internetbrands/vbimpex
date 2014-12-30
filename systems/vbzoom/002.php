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
* vbzoom
/**
* vbzoom_001 Associate Users
*
* @package			ImpEx.vbzoom
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vbzoom_002 extends vbzoom_000
{
	var $_dependent 	= '001';

	function vbzoom_002(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['associate_users']; 
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject,$this->_dependent);
		if ($proceed)
		{
			if($sessionobject->get_session_var('associateperpage') == 0)
			{
				$sessionobject->add_session_var('associateperpage','25');
			}

			$displayobject->update_basic('title', $displayobject->phrases['associate_users']);
			$displayobject->update_html($displayobject->do_form_header('index','002'));
			$displayobject->update_html($displayobject->make_hidden_code('002','WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('associateusers','1'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['associate_users']));
			$displayobject->display_now($displayobject->phrases['assoc_desc_1']);
			$displayobject->display_now($displayobject->phrases['assoc_desc_2']);
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));

			$sessionobject->add_session_var('doassociate','0');
			$sessionobject->add_session_var('associatestartat','0');
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
		// Turn off the modules display
		$displayobject->update_basic('displaymodules','FALSE');


		// Get some more usable local vars
		$associate_start_at		= $sessionobject->get_session_var('associatestartat');
		$associate_per_page		= $sessionobject->get_session_var('associateperpage');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Get some usable variables
		$associate_users		= 	$sessionobject->get_session_var('associateusers');
		$do_associate			=	$sessionobject->get_session_var('doassociate');
		$class_num				= 	substr(get_class($this) , -3);


		// Start the timings
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}


		//	List from the start_at number
		if ($associate_users == 1)
		{
			// Get a list of the vbzoom members in this current selection
			$userarray = $this->get_vbzoom_members_list($Db_source, $source_database_type, $source_table_prefix, $associate_start_at, $associate_per_page);


			// Build a list of the ubb users with a box to enter a vB user id into
			$displayobject->update_html($displayobject->do_form_header('index','002'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['assoc_list']));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['assoc_match']));

			// Set up list variables
			$any_more = false;
			$counter  = 1;


			// Build the list
			foreach ($userarray as $userid => $username )
			{
				$displayobject->update_html($displayobject->make_input_code("$counter) {$displayobject->phrases['user_id']} - " . $userid . " :: " . $username ,'user_to_ass_' . $userid,'',10));
				$any_more = true;
				$counter++;
			}


			// If there are not any more, tell the user and quit out for them.
			if(!$any_more)
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_users']));
			}
			else
			{
				$sessionobject->set_session_var('associatestartat',$associate_start_at + $associate_per_page);
			}


			// Continue with the association
			$sessionobject->set_session_var('associateusers','0');
			$sessionobject->set_session_var('doassociate','1');
			$displayobject->update_html($displayobject->make_hidden_code('doassociate','1'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['associate'],''));

			// Quit button
			$displayobject->update_html($displayobject->do_form_header('index','002'));
			$displayobject->update_html($displayobject->make_hidden_code('associateusers','2'));
			$displayobject->update_html($displayobject->make_hidden_code('doassociate','0'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['quit'],''));
		}


		//	If there are some to assosiate
		if ($do_associate == 1)
		{
			$displayobject->update_html($displayobject->display_now($displayobject->phrases['associate_users']));


			$users_to_associate = $sessionobject->get_users_to_associate();


			foreach ($users_to_associate as $key => $value)
			{
				if($this->associate_user($Db_target, $target_database_type, $target_table_prefix, substr(key($value),12),  current($value)))
				{
					$displayobject->update_html($displayobject->display_now("<p align=\"center\">{$displayobject->phrases['user']} " .  substr(key($value),12) . $displayobject->phrases['successful'] . ".</p>"));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
					$displayobject->update_html($displayobject->display_now('<p align="center">' . substr(key($value),12) . '  ' . current($value) . $displayobject->phrases['failed'] . ' .</p>'));
				}
			}


			$sessionobject->delete_users_to_associate();


			// Continue with the association
			$sessionobject->set_session_var('associateusers','1');
			$sessionobject->set_session_var('doassociate','0');
			$displayobject->update_html($displayobject->display_now("<p align=\"center\">{$displayobject->phrases['continue']}.</p>"));
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		//	Finish the module
		if ($associate_users == 2)
		{
			$sessionobject->set_session_var('002','FINISHED');
			$sessionobject->set_session_var('module','000');

			$displayobject->update_html($displayobject->display_now("<p align=\"center\">{$displayobject->phrases['completed']}.</p>"));
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}
	}
}// End class
# Autogenerated on : April 12, 2005, 4:16 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
