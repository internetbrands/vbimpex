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
* @package			ImpEx.InvisionCommunityBlog
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class InvisionCommunityBlog_003 extends InvisionCommunityBlog_000
{
	var $_dependent = '001';

	function InvisionCommunityBlog_003(&$displayobject)
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
			# can't as this will never be installed on vBulletin
			#$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['userid_match'], "userid_match",0));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars', 0));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

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
		$data_array = $this->get_InvisionCommunityBlog_users($Db_source, $s_db_type, $s_tb_prefix, $start_at, $per_page);

		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $t_db_type, $t_tb_prefix);

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
			$vbulletin_user->set_value('mandatory', 'usergroupid',				$user_group_ids_array[69]);
			$vbulletin_user->set_value('mandatory', 'username',					$data['name']);
			$vbulletin_user->set_value('mandatory', 'email',					$data['email']);
			$vbulletin_user->set_value('mandatory', 'importuserid',				$import_id);

			// User options
			$options = 0;

			if($data['view_sigs'])												{ $options += 1;}
			if($data['view_avs'])												{ $options += 2;}
			if($data['view_img'])												{ $options += 4;}
			if($data['coppa_user'])												{ $options += 8;}
			if($data['allow_admin_mails'])										{ $options += 16;}
			#if($usergroup_details['showvcard'])								{ $options += 32;}
			if($data['dst_in_use'])												{ $options += 64;}
			#if($usergroup_details['dstonoff'])									{ $options += 128;}
			if($data['hide_email'])	{} else										{ $options += 256;}
			#if($usergroup_details['invisible'])								{ $options += 512;}
			#if($usergroup_details['showreputation'])							{ $options += 1024;}
			#if($usergroup_details['receivepm'])								{ $options += 2048;}
			#if($usergroup_details['email_pm'])									{ $options += 4096;}
			#if($usergroup_details['hasaccessmask'])							{ $options += 8192;}
			#if($usergroup_details['postorder'])								{ $options += 32768;}

			// Non Mandatory
			#$vbulletin_user->set_value('nonmandatory', 'membergroupids',		$data['membergroupids']);
			#$vbulletin_user->set_value('nonmandatory', 'displaygroupid',		$data['displaygroupid']);

			if($data['legacy_password'])
			{
				$vbulletin_user->_password_md5_already = true;
				$vbulletin_user->set_value('nonmandatory', 'password',			$data['legacy_password']);
			}
			else
			{
				$vbulletin_user->set_value('nonmandatory', 'password',			$this->fetch_user_salt());
			}

			#$vbulletin_user->set_value('nonmandatory', 'passworddate',			$data['passworddate']);
			#$vbulletin_user->set_value('nonmandatory', 'styleid',				$data['styleid']);
			#$vbulletin_user->set_value('nonmandatory', 'parentemail',			$data['parentemail']);
			$vbulletin_user->set_value('nonmandatory', 'homepage',				$data['website']);
			$vbulletin_user->set_value('nonmandatory', 'icq',					$data['icq_number']);
			$vbulletin_user->set_value('nonmandatory', 'aim',					$data['aim_name']);
			$vbulletin_user->set_value('nonmandatory', 'yahoo',					$data['yahoo']);
			$vbulletin_user->set_value('nonmandatory', 'showvbcode',			'2');
			$vbulletin_user->set_value('nonmandatory', 'usertitle',				$data['title']);
			#$vbulletin_user->set_value('nonmandatory', 'customtitle',			$data['customtitle']);
			$vbulletin_user->set_value('nonmandatory', 'joindate',				$data['joined']);
			#$vbulletin_user->set_value('nonmandatory', 'daysprune',			$data['daysprune']);
			$vbulletin_user->set_value('nonmandatory', 'lastvisit',				$data['last_visit']);
			$vbulletin_user->set_value('nonmandatory', 'lastactivity',			$data['last_activity']);
			$vbulletin_user->set_value('nonmandatory', 'lastpost',				$data['last_post']);
			$vbulletin_user->set_value('nonmandatory', 'posts',					$data['posts']);
			#$vbulletin_user->set_value('nonmandatory', 'reputation',			$data['reputation']);
			#$vbulletin_user->set_value('nonmandatory', 'reputationlevelid',	$data['reputationlevelid']);
			$vbulletin_user->set_value('nonmandatory', 'timezoneoffset',		$data['time_offset']);
			#$vbulletin_user->set_value('nonmandatory', 'pmpopup',				$data['pmpopup']);
			#$vbulletin_user->set_value('nonmandatory', 'avatarid',				$data['avatarid']);
			#$vbulletin_user->set_value('nonmandatory', 'avatarrevision',		$data['avatarrevision']);
			$vbulletin_user->set_value('nonmandatory', 'options',				$options);

			#$vbulletin_user->set_value('nonmandatory', 'maxposts',				$data['maxposts']);
			#$vbulletin_user->set_value('nonmandatory', 'startofweek',			$data['startofweek']);
			$vbulletin_user->set_value('nonmandatory', 'ipaddress',				$data['ip_address']);
			#$vbulletin_user->set_value('nonmandatory', 'referrerid',			$data['referrerid']);
			#$vbulletin_user->set_value('nonmandatory', 'languageid',			$data['languageid']);
			$vbulletin_user->set_value('nonmandatory', 'msn',					$data['msnname']);
			#$vbulletin_user->set_value('nonmandatory', 'emailstamp',			$data['emailstamp']);
			#$vbulletin_user->set_value('nonmandatory', 'threadedmode',			$data['threadedmode']);
			$vbulletin_user->set_value('nonmandatory', 'pmtotal',				$data['msg_total']);
			$vbulletin_user->set_value('nonmandatory', 'pmunread',				$data['new_msg']);
			#$vbulletin_user->set_value('nonmandatory', 'salt',					$data['salt']);
			#$vbulletin_user->set_value('nonmandatory', 'autosubscribe',		$data['autosubscribe']);
			#$vbulletin_user->set_value('nonmandatory', 'avatar',				$data['avatar']);


			if($data['bday_day'] OR $data['bday_month'] OR $data['bday_year'])
			{
				$day 	= str_pad($data['bday_day'], 2, "0", STR_PAD_LEFT);
				$month 	= str_pad($data['bday_month'], 2, "0", STR_PAD_LEFT);
				$year 	= $data['bday_year'];

				$vbulletin_user->set_value('nonmandatory', 'birthday',			"{$month}-{$day}-{$year}");
				$vbulletin_user->set_value('nonmandatory', 'birthday_search',	"{$year}-{$month}-{$day}");

				unset($year, $month, $day);
			}
			else
			{
				$vbulletin_user->set_value('nonmandatory', 'birthday',			"00-00-0000");
				$vbulletin_user->set_value('nonmandatory', 'birthday_search',	"0000-00-00");
			}

			$path = $sessionobject->get_session_var('get_avatars_path');
			$avatar = $data['avatar_location'];

			// Ensure there is a trailing slash
			if($path{strlen($path)-1} != '/')  $path .= '/';

			// It would appear that blanks are allowed in the database
			if($sessionobject->get_session_var('get_avatars') AND $avatar != 'noavatar' AND $avatar != '')
			{
				if(substr($avatar, 0, 7) == 'upload:')
				{
					// Its a localy uploaded file.
					$vbulletin_user->set_value('nonmandatory', 'avatar',	$path . substr($avatar, strpos($avatar, ':')+1));
				}
				elseif (substr($avatar, 0, 7) == 'http://')
				{
					// Must be a URL, give it a go.
					$vbulletin_user->set_value('nonmandatory', 'avatar', 	$avatar);
				}
				elseif (is_file($path . $avatar))
				{
					// Well its there, so try it.
					$vbulletin_user->set_value('nonmandatory', 'avatar', 	$path . $avatar);
				}
				else
				{
					// Dunno what it could be.
				}
			}

			$vbulletin_user->add_default_value('Location', 				$data['location']);
			$vbulletin_user->add_default_value('Interests', 			$data['interests']);
			$vbulletin_user->add_default_value('signature', 			$this->html_2_bb(html_entity_decode($data['signature'])));


			// Mandatory set during user import as well
			$blog_user->set_value('mandatory', 'importbloguserid',		$import_id);

			// Non mandatory
			$blog_user->set_value('nonmandatory', 'entries',				$data['posts']);

			$blog_user->set_value('nonmandatory', 'title',				html_entity_decode($data['blog_name']));
			$blog_user->set_value('nonmandatory', 'description',		html_entity_decode($data['blog_desc']));

			#$blog_user->set_value('nonmandatory', 'subscribeothers',	$data['subscribeothers']);
			#$blog_user->set_value('nonmandatory', 'moderation',		$data['moderation']);
			#$blog_user->set_value('nonmandatory', 'deleted',			$data['deleted']);
			#$blog_user->set_value('nonmandatory', 'draft',				$data['draft']);
			#$blog_user->set_value('nonmandatory', 'options_everyone',	$data['options_everyone']);
			#$blog_user->set_value('nonmandatory', 'options_buddy',		$data['options_buddy']);
			#$blog_user->set_value('nonmandatory', 'options_ignore',	$data['options_ignore']);
			#$blog_user->set_value('nonmandatory', 'ratingnum',			$data['ratingnum']);
			#$blog_user->set_value('nonmandatory', 'ratingtotal',		$data['ratingtotal']);
			#$blog_user->set_value('nonmandatory', 'rating',			$data['rating']);
			#$blog_user->set_value('nonmandatory', 'pending',			$data['pending']);
			#$blog_user->set_value('nonmandatory', 'subscribeown',		$data['subscribeown']);
			#$blog_user->set_value('nonmandatory', 'allowsmilie',		$data['allowsmilie']);
			#$blog_user->set_value('nonmandatory', 'options',			$data['options']);
			#$blog_user->set_value('nonmandatory', 'viewoption',		$data['viewoption']);
			#$blog_user->set_value('nonmandatory', 'comments',			$data['comments']);
			#$blog_user->set_value('nonmandatory', 'lastblog',			$data['lastblog']);
			#$blog_user->set_value('nonmandatory', 'lastblogid',		$data['lastblogid']);
			#$blog_user->set_value('nonmandatory', 'lastblogtitle',		$data['lastblogtitle']);
			#$blog_user->set_value('nonmandatory', 'lastcomment',		$data['lastcomment']);
			#$blog_user->set_value('nonmandatory', 'lastcommenter',		$data['lastcommenter']);
			#$blog_user->set_value('nonmandatory', 'lastblogtextid',	$data['lastblogtextid']);
			#$blog_user->set_value('nonmandatory', 'uncatentries',		$data['uncatentries']);

			// Check if object is valid
			if($vbulletin_user->is_valid())
			{
				if ($sessionobject->get_session_var('email_match'))
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
							$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $blog_user->how_complete() . '%</b></span> ' . $displayobject->phrases['blog_user'] . ' -> ' .$data['name']);
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
# Autogenerated on : August 29, 2007, 2:02 pm
# By ImpEx-generator 2.0
/*======================================================================*/
?>
