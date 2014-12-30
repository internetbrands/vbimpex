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
*
* @package			ImpEx.wordpress
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class wordpress_003 extends wordpress_000
{
	var $_dependent = '001';

	function wordpress_003(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_blog_user'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_blog_users'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['blog_users_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['blog_user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_blog_user']);
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

		$ImpExData_blog_user = new ImpExData($Db_target, $sessionobject, 'blog_user', 'blog');
		$ImpExData_user = new ImpExData($Db_target, $sessionobject, 'user');


		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}users", 'ID', 0, $start_at, $per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['blog_users'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'blog_user');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$vbulletin_user = (phpversion() < '5' ? $ImpExData_user : clone($ImpExData_user));
			$blog_user 		= (phpversion() < '5' ? $ImpExData_blog_user : clone($ImpExData_blog_user));

			// Auto associate
			if ($sessionobject->get_session_var('email_match'))
			{
				$vbulletin_user->_auto_email_associate = true;
			}

			// Mandatory
			$vbulletin_user->set_value('mandatory', 'usergroupid',			$user_group_ids_array[69]);
			$vbulletin_user->set_value('mandatory', 'username',				$data['display_name']);
			$vbulletin_user->set_value('mandatory', 'email',				$data['user_email']);
			$vbulletin_user->set_value('mandatory', 'importuserid',			$import_id);


			// non
			$try->_password_md5_already = true;
			$vbulletin_user->set_value('nonmandatory', 'password', 			$user['user_pass']);
			$vbulletin_user->set_value('nonmandatory', 'options',			$this->_default_user_permissions);
			$vbulletin_user->set_value('nonmandatory', 'joindate',			strtotime($data['user_registered']));
			$vbulletin_user->set_value('nonmandatory', 'homepage',			$data['user_url']);


			// Mandatory set during user import as well
			$blog_user->set_value('mandatory', 'importbloguserid',		$import_id);


			// Check if object is valid
			if($vbulletin_user->is_valid())
			{

				$vb_id = $vbulletin_user->import_user($Db_target, $t_db_type, $t_tb_prefix);

				$blog_user->set_value('mandatory', 'bloguserid', $vb_id);

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
							$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $blog_user->how_complete() . '%</b></span> ' . $displayobject->phrases['blog_user'] . ' -> ' .$data['display_name']);
						}
						$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
					}
					else
					{
						$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['blog_user_not_imported'], $displayobject->phrases['blog_user_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['blog_user_not_imported']}");
						echo "<h1>here</h1>";
					}// $blog_user->import_blog_user
				} // $blog_user->is_valid()
				else
				{
					// $vbulletin_user->is_valid()
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $blog_user->_failedon, $displayobject->phrases['invalid_object_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $blog_user->_failedon);
				}// is_valid
			}
			else
			{
				// $vbulletin_user->is_valid()
				$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $vbulletin_user->_failedon, $displayobject->phrases['invalid_object_rem']);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $vbulletin_user->_failedon);
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
# Autogenerated on : August 30, 2007, 1:38 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
