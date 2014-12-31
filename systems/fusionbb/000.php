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
* fusionbb
*
* @package 		ImpEx.fusionbb
* *
*/

class fusionbb_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.0.3';
	var $_tested_versions = array('1.0.3');
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'fusion BB';
	var $_homepage 	= 'http://www.fusionbb.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'aa_import', 'bad_emails', 'bad_usernames', 'banlist', 'buddies', 'cache', 'censorship', 'cp_auth', 'cp_perm_list', 'cron_scheduler',
		'display_names', 'email_verification', 'failed_logins', 'files', 'forum_auth', 'forum_mods', 'forum_perm_list', 'forum_visit', 'forums',
		'fusionbb_info', 'groups', 'ignores', 'logs', 'perm_inherit', 'poll_choices', 'poll_votes', 'polls', 'portal_quotes', 'portal_shoutbox',
		'portal_topicsposts', 'pt_participants', 'pt_posts', 'pt_topics', 'queue_mail', 'referrals', 'reported_posts', 'rss_channels', 'rss_items',
		'search', 'sessions', 'site_auth', 'site_perm_list', 'smilies', 'sql_queries', 'staff_mail', 'topic_subscriptions', 'topic_views', 'topics',
		'user_groups', 'user_info', 'user_titles', 'users'
	);

	function fusionbb_000()
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
	function fusionbb_html($text)
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
	function get_fusionbb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT user_id, user_login FROM {$tableprefix}users ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[user_id]"] = $row['user_login'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}


	function get_user_data(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT user.user_id, user.user_login, user.user_password, user_info.*
			FROM {$tableprefix}users as user
			LEFT JOIN {$tableprefix}user_info AS user_info ON (user.user_id = user_info.user_id)
			WHERE user.user_id > {$start_at}
			ORDER BY user.user_id
			LIMIT {$per_page}";

			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[user_id]"] = $row;
				$return_array['lastid'] = $row['user_id'];
			}

			$return_array['count'] = count($return_array['data']);

			return $return_array;
		}
		else
		{
			return false;
		}
	}


	function get_fusionbb_usergroup(&$Db_object, &$databasetype, &$tableprefix, &$userid)
	{
		$user_group = 0;

		// Check that there is not a empty value
		if(empty($userid)) { return $user_group; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT group_id FROM {$tableprefix}user_groups WHERE user_id={$userid}");

			return $user_group = $dataset['group_id'];
		}

		return $user_group;
	}


	function get_fusionbb_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$cats = array();

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE  forum_is_cat=1");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$cats["$row[forum_id]"] = $row;
			}
		}

		return $cats;
	}

	function get_fusionbb_forum_details(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$forums = array();

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE forum_is_cat=0 AND forum_id > {$start_at} ORDER BY forum_id LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$forums["$row[forum_id]"] = $row;
			}
		}

		return $forums;
	}

	function get_fusionbb_polls_details(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$polls = array();

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}polls WHERE poll_id > {$start_at} ORDER BY poll_id LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$polls["$row[poll_id]"] = $row;
			}
		}

		return $polls;
	}


	function get_fusionbb_poll_options(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$options = array(); // will implode to string

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}poll_choices WHERE poll_id={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$options[] = $row['poll_option_text'];
			}
		}

		return implode('|||', $options);
	}

	function get_fusionbb_poll_results_details(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$options = array(); // will implode to string

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}poll_votes WHERE poll_id={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$options[] = $row;
			}
		}

		return $options;
	}

	function get_fusionbb_threadid(&$Db_object, &$databasetype, &$tableprefix, &$postid)
	{
		$threadid = 0;

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT topic_id FROM {$tableprefix}posts WHERE post_id={$postid}");

			$threadid = $dataset['topic_id'];
		}

		return $threadid;
	}

	function get_fusionbb_moderator_details(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$mods = array();

		// Check that there is not a empty value
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forum_mods ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$id = $id + ($start_at+2);
				$mods[$id] = $row;
			}
		}

		return $mods;
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
