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
* txt_file_002 Import User module
*
* @package			ImpEx.txt_file
*
*/
class txt_file_002 extends txt_file_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import User';


	function txt_file_002()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users'))
				{
					$displayobject->display_now('<h4>Imported users have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_users','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import User');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_user','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_description('A correctly formed email address is mandatory, the rest of the details are optional'));
			$displayobject->update_html($displayobject->make_description('The username can be a string, if it not present the email address will be used'));
			$displayobject->update_html($displayobject->make_description('The user password can be plain text or an MD5 hash'));
			$displayobject->update_html($displayobject->make_description('The id will be read as an number and stored is a refrence is needed to the user from a 3rd party system, otherwise the line number in the source file will be used.'));

			$displayobject->update_html($displayobject->make_yesno_code("Is the password a MD5 hash ? (leave as no if its plain text)","email_hashed",0));
			$displayobject->update_html($displayobject->make_input_code('Users to import per cycle (must be greater than 1)','userperpage',500));
			$displayobject->update_html($displayobject->make_description('Select the delimitator in the file.' . $displayobject->make_select($this->_seperator, 'seperator')));

			$counter = 1;
			foreach ($this->_layout AS $id => $field)
			{
				if($id != 0)
				{
					$displayobject->update_html($displayobject->make_description("Select data type in position $counter  " . $displayobject->make_select($this->_layout, "pos_$counter")));
					$counter++;
				}
			}

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('userdone','0');
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

		$filepath		= $sessionobject->get_session_var('filepath');
		$sep_d 			= $sessionobject->get_session_var('seperator');
		$seperator		= $this->_seperator[$sep_d];

		// Per page vars
		$user_start_at		= $sessionobject->get_session_var('userstartat');
		$user_per_page		= $sessionobject->get_session_var('userperpage');
		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of user details
		$user_array 	= $this->get_txt_file_user_details($filepath, $user_start_at, $user_per_page);
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		$order_in_file = array();

		$pos_1 = $sessionobject->get_session_var('pos_1');
		$pos_2 = $sessionobject->get_session_var('pos_2');
		$pos_3 = $sessionobject->get_session_var('pos_3');
		$pos_4 = $sessionobject->get_session_var('pos_4');
		$pos_5 = $sessionobject->get_session_var('pos_5');
		$pos_6 = $sessionobject->get_session_var('pos_6');

		for ($i = 1; $i <= 6; $i++)
		{
			$var = 'pos_' . $i;
			if ($this->_layout[$$var] != 'NONE')
			{
				$order_in_file[$this->_layout[$$var]] = ($i-1);
			}
		} 
		
		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			if (substr_count($user_details, $seperator) == 0)
			{
				$displayobject->display_now("<br />Line has invalid seperator, skipping." );
				continue;
			}

			if ($order_in_file['email'] == NULL AND $order_in_file['email'] !== 0)
			{
				$displayobject->display_now("<br />You must have an email address." );
				continue;
			}


			if ($sessionobject->get_session_var('email_hashed'))
			{
				$try->_password_md5_already = true;
			}


			$bits = explode ($seperator,  $user_details);

			// Mandatory
			$try->set_value('mandatory', 'usergroupid',		$user_group_ids_array[69]);

			if ($bits["$order_in_file[username]"])
			{
				$try->set_value('mandatory', 'username',	trim($bits["$order_in_file[username]"]));
			}
			else
			{
				$try->set_value('mandatory', 'username',	trim($bits["$order_in_file[email]"]));
			}

			$try->set_value('mandatory', 'email',			trim($bits["$order_in_file[email]"]));

			if ($bits["$order_in_file[id]"])
			{
				$try->set_value('mandatory', 'importuserid',	trim($bits["$order_in_file[id]"]));
			}
			else
			{
				$try->set_value('mandatory', 'importuserid',	trim($user_id+1));
			}

			// Non Mandatory
			if ($bits["$order_in_file[password]"])
			{
				$try->set_value('nonmandatory', 'password',	trim($bits["$order_in_file[password]"]));
			}
			else
			{
				$try->set_value('nonmandatory', 'password',	trim($this->fetch_user_salt(10)));
			}

			$try->set_value('nonmandatory', 'joindate',		time());
			$try->set_value('nonmandatory', 'options',		'2135');



			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $try->get_value('mandatory', 'username'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found user and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid user object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_user','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}

		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : December 17, 2004, 4:43 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
