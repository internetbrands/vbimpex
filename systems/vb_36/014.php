<?php
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
* vb_36_014 Import Subscription module
*
* @package			ImpEx.vb_36
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb_36_014 extends vb_36_000
{
	var $_dependent 	= '004';

	function vb_36_014(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_subscription'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_subscriptions'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['subscriptions_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['subscription_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}


			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_subscription']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['subscriptions_per_page'],'subscriptionperpage',500));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('subscriptionstartat','0');
			$sessionobject->add_session_var('subscriptionfinished','FALSE');
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Per page vars
		$subscription_start_at	= $sessionobject->get_session_var('subscriptionstartat');
		$subscription_per_page	= $sessionobject->get_session_var('subscriptionperpage');
		$class_num				= substr(get_class($this) , -3);
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		$usergroups	= $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		$forum_ids	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('subscriptionfinished') == 'FALSE')
		{
			// Get table of subscription
			$subscription_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, 0, -1, 'subscription', 'subscriptionid');

			$subscription_object = new ImpExData($Db_target, $sessionobject, 'subscription');

			foreach ($subscription_array as $subscription_id => $subscription_details)
			{
				$try = (phpversion() < '5' ? $subscription_object : clone($subscription_object));

				$try->set_value('mandatory', 'importsubscriptionid', 	$subscription_id);
				$try->set_value('mandatory', 'cost', 					$subscription_details['cost']);

				if ($subscription_details['nusergroupid'] != '-1')
				{
					$try->set_value('nonmandatory', 'nusergroupid', 		$usergroups["$subscription_details[nusergroupid]"]);
				}
				else
				{
					$try->set_value('nonmandatory', 'nusergroupid', 		'-1');
				}

				unset($old_group_ids, $new_ids);

				if ($subscription_details['membergroupids'])
				{
					if (strpos($subscription_details['membergroupids'], ','))
					{
						$old_group_ids = explode(',', $subscription_details['membergroupids']);
						$new_ids = array();

						foreach ($old_group_ids as $old_id)
						{
							if ($usergroups[$old_id])
							{
								$new_ids[] = $usergroups[$old_id];
							}
						}

						$try->set_value('mandatory', 'membergroupids', 			implode(',', $new_ids));
					}
					else
					{
						$try->set_value('mandatory', 'membergroupids', 			$usergroups["$subscription_details[membergroupids]"]);
					}
				}

				unset($old_forum_ids, $new_ids);
				if ($subscription_details['forums'])
				{
					if (strpos($subscription_details['forums'], ','))
					{
						$old_forum_ids = explode($subscription_details['forums'], ',');
						$new_ids = array();

						foreach ($old_forum_ids as $old_id)
						{
							if ($forum_ids[$old_id])
							{
								$new_ids[] = $usergroups[$old_id];
							}
						}

						$try->set_value('nonmandatory', 'forums', 			implode(',', $new_ids));
					}
					else
					{
						$try->set_value('nonmandatory', 'forums', 			$forum_ids["$subscription_details[forums]"]);
					}
				}

				$try->set_value('mandatory', 'active', 					$subscription_details['active']);
				$try->set_value('mandatory', 'options', 				$subscription_details['options']);
				$try->set_value('mandatory', 'varname', 				$subscription_details['varname']);
				$try->set_value('mandatory', 'adminoptions', 			$subscription_details['adminoptions']);
				$try->set_value('nonmandatory', 'displayorder', 		$subscription_details['displayorder']);

				// Check if subscription object is valid
				if($try->is_valid())
				{
					if($try->import_subscription($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span> ' . $displayobject->phrases['subscription'] . ' -> ' . $try->get_value('mandatory','varname'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $subscription_id, $displayobject->phrases['subscription_not_imported'], $displayobject->phrases['subscription_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['subscription_not_imported']} :-> " . $try->_failedon);
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $subscription_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				}
				unset($try);
			}// End foreach

			$sessionobject->add_session_var('subscriptionfinished','TRUE');
		}
		else
		{
			$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($forum_array) . " {$displayobject->phrases['subscriptionlogs']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $forum_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($forum_start_at + count($forum_array)) . "</p>");

			// Get the users subscriptionlog
			$subscriptionlog_array	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $subscription_start_at, $subscription_per_page, 'subscriptionlog', 'subscriptionlogid');
			$subscription_ids 	= $this->get_subscription_ids($Db_target, $target_database_type, $target_table_prefix);

			$subscriptionlog_object = new ImpExData($Db_target, $sessionobject, 'subscriptionlog');

			foreach ($subscriptionlog_array as $subscriptionlog_id => $subscriptionlog_details)
			{
				$try = (phpversion() < '5' ? $subscriptionlog_object : clone($subscriptionlog_object));

				$try->set_value('mandatory', 'importsubscriptionlogid',	$subscriptionlog_details['subscriptionlogid']);
				$try->set_value('mandatory', 'subscriptionid', 			$subscription_ids["$subscriptionlog_details[subscriptionid]"]);
				$try->set_value('mandatory', 'userid', 					$idcache->get_id('user', $subscriptionlog_details['userid']));
				$try->set_value('mandatory', 'pusergroupid', 			$usergroups["$subscriptionlog_details[pusergroupid]"]);
				$try->set_value('mandatory', 'status', 					$subscriptionlog_details['status']);
				$try->set_value('mandatory', 'regdate', 				$subscriptionlog_details['regdate']);
				$try->set_value('mandatory', 'expirydate', 				$subscriptionlog_details['expirydate']);

				if($try->is_valid())
				{
					if($try->import_subscriptionlog($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['subscription'] . ' -> ' . $subscription_ids["$subscriptionlog_details[subscriptionid]"]);
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $subscription_id, $displayobject->phrases['subscription_not_imported'], $displayobject->phrases['subscription_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['subscription_not_imported']} :-> " . $try->_failedon);
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $subscription_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				}
			}

			if (count($subscription_array) == 0 OR count($subscription_array) < $subscription_per_page)
			{
				$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));

				$sessionobject->set_session_var($class_num, 'FINISHED');
				$sessionobject->set_session_var('module', '000');
				$sessionobject->set_session_var('autosubmit', '0');
			}
			$sessionobject->set_session_var('forumstartat',$forum_start_at+$forum_per_page);
		}

		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
