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
* fusionbb2
*
* @package 		ImpEx.fusionbb2
* @date 		$Date: $
*
*/

class fusionbb2_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '2.1';
	var $_tested_versions = array('2.1');
	var $_tier = '1';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'fusionBB 2';
	var $_homepage 	= 'http://www.fusionbb.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'advancement','bad_emails','bad_usernames','banlist','buddies','cache','calendar','censorship','cp_auth','cp_perm_list',
		'cron_scheduler','display_names','email_verification','failed_logins','files','forum_auth','forum_mods','forum_perm_list','forum_visit',
		'forums','fusionbb_info','groups','ignores','logs','mailbox','page_cache','perm_inherit','poll_choices','poll_votes','polls','portal_quotes',
		'portal_shoutbox','portal_topics','posts','pt_participants','pt_posts','pt_topics','queue_mail','referrals','reported_posts','rss_channels',
		'rss_items','search','sessions','site_auth','site_perm_list','smilies','sql_queries','staff_mail','sub_data','sub_groups','sub_users',
		'topic_subscriptions','topic_views','topics','upgrade_info','user_groups','user_info','user_titles','users'
	);

	function fusionbb2_000()
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
	function fusionbb2_html($text)
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
	function get_fusionbb2_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
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
	
	function get_fusionbb2_user_data($Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			
			$sql = "SELECT users.*, user_info.*, user_groups.group_id 
					FROM {$tableprefix}users as users
					LEFT JOIN {$tableprefix}user_info AS user_info ON (users.user_id = user_info.user_id)
					LEFT JOIN {$tableprefix}user_groups AS user_groups ON (user_info.user_id = user_groups.user_id)
					WHERE users.user_id > {$start_at}
					ORDER by users.user_id
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
	
	function get_fusionbb2_cat($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();
 
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE forum_is_cat=1 ORDER by forum_id");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[forum_id]"] = $row;
				$return_array['lastid'] = $row['forum_id'];
			}
			
			$return_array['count'] = count($return_array['data']);
			return $return_array;
		}
		 
		else
		{
			return false;
		}
	}
			
	function get_fusionbb2_forum_details($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE forum_is_cat=0 ORDER by forum_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[forum_id]"] = $row;
				$return_array['lastid'] = $row['forum_id'];
			}
			
			$return_array['count'] = count($return_array['data']);
			return $return_array;
		}
		else
		{
			return false;
		}

	}		

	function get_fusionbb2_pm($Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT posts.*,	topics.*, participants.user_id AS participants_user_id 
					FROM {$tableprefix}pt_posts as posts
					LEFT JOIN {$tableprefix}pt_topics AS topics ON (posts.topic_id = topics.topic_id)
					LEFT JOIN {$tableprefix}pt_participants AS participants ON (posts.topic_id = participants.topic_id)
					WHERE posts.post_id > {$start_at}
					ORDER by posts.post_id
					LIMIT {$per_page}";
					
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[post_id]"] = $row;
				$return_array['lastid'] = $row['post_id'];
			}
			
			$return_array['count'] = count($return_array['data']);
			return $return_array;
		}
		else
		{
			return false;
		}

	}		
	
	function get_fusionbb2_mods	($Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forum_mods ORDER by user_id LIMIT {$start_at}, {$per_page}");

			$id=1;
			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][$id++] = $row;
				$return_array['lastid'] = $per_page;
			}
			
			$return_array['count'] = count($return_array['data']);
			return $return_array;
		}
		else
		{
			return false;
		}

	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
