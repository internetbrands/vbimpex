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
* smf2
*
* @package 		ImpEx.smf2
* *
*/

class smf2_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '2.0 beta';
	var $_tested_versions = array();
	var $_tier = '1';

	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'SMF';
	var $_homepage 	= 'http://www.simplemachines.org';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'admin_info_files', 'approval_queue', 'attachments', 'ban_groups', 'ban_items', 'board_permissions', 'boards', 'calendar',
	 	'calendar_holidays', 'categories', 'collapsed_categories', 'custom_fields', 'fc_bans', 'fc_config', 'fc_config_chats',
	 	'fc_config_instances', 'fc_config_values', 'fc_connections', 'fc_ignors', 'fc_messages', 'fc_rooms', 'global_announcements',
	 	'global_announcements_boards', 'group_moderators', 'ignore', 'invites', 'log_actions', 'log_activity', 'log_banned', 'log_boards',
	 	'log_comments', 'log_digest', 'log_errors', 'log_floodcontrol', 'log_group_requests', 'log_karma', 'log_mark_read', 'log_member_notices',
	 	'log_notify', 'log_online', 'log_packages', 'log_polls', 'log_reported', 'log_reported_comments', 'log_scheduled_tasks',
	 	'log_search_messages', 'log_search_results', 'log_search_subjects', 'log_search_topics', 'log_topics', 'log_treasury',
	 	'mail_queue', 'membergroups', 'members', 'message_icons', 'messages', 'moderators', 'ob_googlebot_stats', 'openid_assoc',
	 	'package_servers', 'permission_profiles', 'permissions', 'personal_messages', 'pm_recipients', 'pm_rules', 'poll_choices',
	 	'polls', 'postmoderation', 'scheduled_tasks', 'sessions', 'settings', 'smileys', 'smileys1', 'themes', 'topics', 'treas_cfg',
	 	'treas_currency', 'treas_finance', 'treas_trans', 'vwarnings'
	);


	 function smf2_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed	The string to parse
	* @param	boolean			Truncate smilies
	*
	* @return	array
	*/
	function smf2_html($text)
	{
		return $text;
	}

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_smf2_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT id_member, member_name FROM {$tableprefix}members ORDER BY id_member LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id_member]"] = $row['member_name'];
			}
			return $return_array;
		}

		return $return_array;
	}


	function get_smf2_thread_title($Db_object, $databasetype, $tableprefix, $msg_id)
	{
		$return_title = 'Thread title';

		// Check that there is not a empty value
		if(empty($msg_id)) { return $return_title; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT subject FROM {$tableprefix}messages WHERE id_msg={$msg_id}");

			$return_title = $dataset['subject'];
		}

		return $return_title;
	}

	function get_smf2_pm_toid($Db_object, $databasetype, $tableprefix, $import_id)
	{
		$return_id = 0;

		// Check that there is not a empty value
		if(empty($import_id)) { return $return_id; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT id_member FROM {$tableprefix}pm_recipients WHERE id_pm={$import_id}");

			$return_id = $dataset['id_member'];
		}

		return $return_id;
	}

	function get_smf2_vote_voters($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT id_member, id_choice FROM {$tableprefix}log_polls WHERE id_poll={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id_member]"] = $row['id_choice'];
			}
			return $return_array;
		}

		return $return_array;
	}

	function get_smf2_poll_options($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT  id_choice, label, votes FROM {$tableprefix}poll_choices WHERE id_poll={$poll_id} ORDER BY id_choice");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array[] = $row;
			}
			return $return_array;
		}

		return $return_array;
	}

	function get_smf2_pollthreadid($Db_object, $databasetype, $tableprefix, $import_id)
	{
		$return_id = 0;

		// Check that there is not a empty value
		if(empty($import_id)) { return $return_id; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT id_topic FROM {$tableprefix}topics WHERE id_poll={$import_id}");

			$return_id = $dataset['id_topic'];
		}

		return $return_id;
	}

	function get_smf_mods($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}moderators ORDER BY id_member LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][] = $row;
			}

			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);
		}

		return $return_array;
	}

	function get_smf_attach($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("
				SELECT *
				FROM {$tableprefix}attachments
				WHERE
					id_attach > {$start_at}
						AND
					attachment_type = 0
						AND
					id_msg <> 0
				ORDER BY id_attach
				LIMIT {$per_page}
			");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[id_attach]"] = $row;
			}

			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);
		}

		return $return_array;
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
