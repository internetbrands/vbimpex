<?php
if (!defined('IDIR')) { die; }
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
*
* @package			ImpEx.vBforum2blog
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class vBforum2blog_003 extends vBforum2blog_000
{
	var $_dependent = '001';

	function vBforum2blog_003(&$displayobject)
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
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['userid_match'], "userid_match",0));
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

		$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}user", 'userid', 0, $start_at, $per_page);
		$signatures = $this->get_details($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page, 'usertextfield', 'userid');

		$usergroups = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);

		// Display count and pass time
		$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['blog_users'], $start_at);

		$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'blog_user');

		foreach ($data_array['data'] as $import_id => $data)
		{
			$vbulletin_user = (phpversion() < '5' ? $ImpExData_user : clone($ImpExData_user));
			$blog_user 		= (phpversion() < '5' ? $ImpExData_blog_user : clone($ImpExData_blog_user));

			// Auto associate
			if ($sessionobject->get_session_var('userid_match'))
			{
				$vbulletin_user->_auto_userid_associate = true;
			}
			elseif ($sessionobject->get_session_var('email_match'))
			{
				$vbulletin_user->_auto_email_associate = true;
			}

			// Mandatory
			$vbulletin_user->set_value('mandatory', 'usergroupid',			$usergroups[69]);
			$vbulletin_user->set_value('mandatory', 'username',				$data['username']);
			$vbulletin_user->set_value('mandatory', 'email',				$data['email']);
			$vbulletin_user->set_value('mandatory', 'importuserid',			$import_id);

			// Non Mandatory
			if($user['referrerid'])
			{
				$vbulletin_user->set_value('nonmandatory', 'referrerid',	$this->get_vb_userid($Db_target, $t_db_type, $t_tb_prefix, $data['referrerid']));
			}

			$vbulletin_user->set_value('nonmandatory', 'ipaddress',			$data['ipaddress']);
			$vbulletin_user->set_value('nonmandatory', 'startofweek',		$data['startofweek']);
			$vbulletin_user->set_value('nonmandatory', 'birthday',			$data['birthday']);
			$vbulletin_user->set_value('nonmandatory', 'usernote',			$data['usernote']);
			$vbulletin_user->set_value('nonmandatory', 'options',			$data['options']);
			$vbulletin_user->set_value('nonmandatory', 'avatarrevision',	$data['avatarrevision']);
			$vbulletin_user->set_value('nonmandatory', 'avatarid',			$data['avatarid']);
			$vbulletin_user->set_value('nonmandatory', 'languageid',		$data['languageid']);
			$vbulletin_user->set_value('nonmandatory', 'msn',				$data['msn']);
			$vbulletin_user->set_value('nonmandatory', 'emailstamp',		$data['emailstamp']);
			$vbulletin_user->set_value('nonmandatory', 'birthday_search',	$data['birthday_search']);
			$vbulletin_user->set_value('nonmandatory', 'avatar',			$data['avatar']);
			$vbulletin_user->set_value('nonmandatory', 'profilepicrevision', $data['profilepicrevision']);
			$vbulletin_user->set_value('nonmandatory', 'autosubscribe',		$data['autosubscribe']);
			$vbulletin_user->set_value('nonmandatory', 'salt',				$data['salt']);
			$vbulletin_user->set_value('nonmandatory', 'pmunread',			$data['pmunread']);
			$vbulletin_user->set_value('nonmandatory', 'pmtotal',			$data['pmtotal']);
			$vbulletin_user->set_value('nonmandatory', 'threadedmode',		$data['threadedmode']);
			$vbulletin_user->set_value('nonmandatory', 'pmpopup',			$data['pmpopup']);
			$vbulletin_user->set_value('nonmandatory', 'timezoneoffset',	$data['timezoneoffset']);
			$vbulletin_user->set_value('nonmandatory', 'reputationlevelid', $data['reputationlevelid']);
			$vbulletin_user->set_value('nonmandatory', 'icq',				$data['icq']);
			$vbulletin_user->set_value('nonmandatory', 'homepage',			$data['homepage']);
			$vbulletin_user->set_value('nonmandatory', 'parentemail',		$data['parentemail']);
			$vbulletin_user->set_value('nonmandatory', 'passworddate',		$data['passworddate']);
			$vbulletin_user->set_value('nonmandatory', 'password',			$data['password']);
			$vbulletin_user->set_value('nonmandatory', 'yahoo',				$data['yahoo']);
			$vbulletin_user->set_value('nonmandatory', 'showvbcode',		$data['showvbcode']);
			$vbulletin_user->set_value('nonmandatory', 'usertitle',			$data['usertitle']);
			$vbulletin_user->set_value('nonmandatory', 'reputation',		$data['reputation']);
			$vbulletin_user->set_value('nonmandatory', 'entries',			$data['posts']);
			$vbulletin_user->set_value('nonmandatory', 'lastpost',			$data['lastpost']);
			$vbulletin_user->set_value('nonmandatory', 'lastactivity',		$data['lastactivity']);
			$vbulletin_user->set_value('nonmandatory', 'lastvisit',			$data['lastvisit']);
			$vbulletin_user->set_value('nonmandatory', 'daysprune',			$data['daysprune']);
			$vbulletin_user->set_value('nonmandatory', 'joindate',			$data['joindate']);
			$vbulletin_user->set_value('nonmandatory', 'customtitle',		$data['customtitle']);
			$vbulletin_user->set_value('nonmandatory', 'aim',				$data['aim']);
			$vbulletin_user->set_value('nonmandatory', 'lastpostid',		$data['lastpostid']);
			$vbulletin_user->set_value('nonmandatory', 'sigpicrevision', 	$data['sigpicrevision']);
			$vbulletin_user->set_value('nonmandatory', 'ipoints', 			$data['ipoints']);
			$vbulletin_user->set_value('nonmandatory', 'infractions', 		$data['infractions']);
			$vbulletin_user->set_value('nonmandatory', 'warnings', 			$data['warnings']);
			$vbulletin_user->set_value('nonmandatory', 'infractiongroupids', $data['infractiongroupids']);
			$vbulletin_user->set_value('nonmandatory', 'infractiongroupid', $data['infractiongroupid']);
			$vbulletin_user->set_value('nonmandatory', 'adminoptions', 		$data['adminoptions']);

			// Map over ?

			#$vbulletin_user->set_value('nonmandatory', 'styleid',			$data['styleid']);
			#$vbulletin_user->set_value('nonmandatory', 'displaygroupid',	$data['displaygroupid']);
			#$vbulletin_user->set_value('nonmandatory', 'membergroupids',	$data['membergroupids']);

			/*
			$this->_has_default_values = true;
			$vbulletin_user->add_default_value('Biography',					$userfield[$user_id]['field1']);
			$vbulletin_user->add_default_value('Location', 					$userfield[$user_id]['field2']);
			$vbulletin_user->add_default_value('Interests',					$userfield[$user_id]['field3']);
			$vbulletin_user->add_default_value('Occupation',				$userfield[$user_id]['field4']);
			*/

			if($signatures[$user_id] != '')
			{
				$vbulletin_user->add_default_value('signature', 			$signatures[$import_id]['signature']);
			}


			// Mandatory set during user import as well
			$blog_user->set_value('mandatory', 'importbloguserid',		$import_id);

			// Non mandatory
			$blog_user->set_value('nonmandatory', 'entries',			$data['posts']);

			#$blog_user->set_value('nonmandatory', 'title',				$data['blogdata']['title']);
			#$blog_user->set_value('nonmandatory', 'subscribeothers',	$data['blogdata']['subscribeothers']);
			#$blog_user->set_value('nonmandatory', 'moderation',		$data['blogdata']['moderation']);
			#$blog_user->set_value('nonmandatory', 'deleted',			$data['blogdata']['deleted']);
			#$blog_user->set_value('nonmandatory', 'draft',				$data['blogdata']['draft']);
			#$blog_user->set_value('nonmandatory', 'options_everyone',	$data['blogdata']['options_everyone']);
			#$blog_user->set_value('nonmandatory', 'options_buddy',		$data['blogdata']['options_buddy']);
			#$blog_user->set_value('nonmandatory', 'options_ignore',	$data['blogdata']['options_ignore']);
			#$blog_user->set_value('nonmandatory', 'ratingnum',			$data['blogdata']['ratingnum']);
			#$blog_user->set_value('nonmandatory', 'ratingtotal',		$data['blogdata']['ratingtotal']);
			#$blog_user->set_value('nonmandatory', 'rating',			$data['blogdata']['rating']);
			#$blog_user->set_value('nonmandatory', 'pending',			$data['blogdata']['pending']);
			#$blog_user->set_value('nonmandatory', 'subscribeown',		$data['blogdata']['subscribeown']);
			#$blog_user->set_value('nonmandatory', 'allowsmilie',		$data['blogdata']['allowsmilie']);
			#$blog_user->set_value('nonmandatory', 'description',		$data['blogdata']['description']);
			#$blog_user->set_value('nonmandatory', 'options',			$data['blogdata']['options']);
			#$blog_user->set_value('nonmandatory', 'viewoption',		$data['blogdata']['viewoption']);
			#$blog_user->set_value('nonmandatory', 'comments',			$data['blogdata']['comments']);
			#$blog_user->set_value('nonmandatory', 'lastblog',			$data['blogdata']['lastblog']);
			#$blog_user->set_value('nonmandatory', 'lastblogid',		$data['blogdata']['lastblogid']);
			#$blog_user->set_value('nonmandatory', 'lastblogtitle',		$data['blogdata']['lastblogtitle']);
			#$blog_user->set_value('nonmandatory', 'lastcomment',		$data['blogdata']['lastcomment']);
			#$blog_user->set_value('nonmandatory', 'lastcommenter',		$data['blogdata']['lastcommenter']);
			#$blog_user->set_value('nonmandatory', 'lastblogtextid',	$data['blogdata']['lastblogtextid']);
			#$blog_user->set_value('nonmandatory', 'uncatentries',		$data['blogdata']['uncatentries']);

			// Check if object is valid
			if($vbulletin_user->is_valid())
			{
				if ($sessionobject->get_session_var('userid_match'))
				{
					$vb_id = $import_id;
					// Opposed to inserting or creating the vb3_user just going to assosiate here.

					$vbulletin_user->associate_user($Db_target, $t_db_type, $t_tb_prefix, $vb_id, $vb_id); # it's the same id on both sides now anyway.
				}
				else if ($sessionobject->get_session_var('email_match'))
				{
					$moo = $vbulletin_user->import_vb3_user($Db_target, $t_db_type, $t_tb_prefix);
					$vb_id = $moo['userid'];
				}
				else
				{
					$vb_id = $vbulletin_user->import_vb3_user($Db_target, $t_db_type, $t_tb_prefix);
				}

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
							$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $blog_user->how_complete() . '%</b></span> ' . $displayobject->phrases['blog_user'] . ' -> ' .$data['username']);
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
# Autogenerated on : August 31, 2007, 2:33 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
