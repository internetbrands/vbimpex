<?php
if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* ubbthreads7
*
* @package 		ImpEx.ubbthreads7
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class ubbthreads7_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '7.5';
	var $_tested_versions = array('7.0.1');
	var $_tier = '1';
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'Infopop UBB.threads';
	var $_homepage 	= 'http://www.ubbdev.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'ADDRESS_BOOK', 'ADMIN_SEARCHES', 'ANNOUNCEMENTS', 'BANNED_EMAILS', 'BANNED_HOSTS', 'CACHE', 'CACHED_PERMISSIONS', 'CALENDAR_EVENTS', 'CATEGORIES',
		'CENSOR_LIST', 'DISPLAY_NAMES', 'FILES', 'FORUMS', 'FORUM_LAST_VISIT', 'FORUM_PERMISSIONS', 'GRAEMLINS', 'GROUPS', 'IMPORT_MAP', 'LANGUAGES', 'MAILER',
		'MEMBER_SEARCHES', 'MODERATORS', 'MODERATOR_NOTIFICATIONS', 'MODERATOR_PERMISSIONS', 'ONLINE', 'POLL_DATA', 'POLL_OPTIONS', 'POLL_VOTES', 'PORTAL_BOXES',
		'POSTS', 'PRIVATE_MESSAGE_POSTS', 'PRIVATE_MESSAGE_TOPICS', 'PRIVATE_MESSAGE_USERS', 'RATINGS', 'REGISTRATION_FIELDS', 'RESERVED_NAMES', 'RSS_FEEDS',
		'SAVED_QUERIES', 'SEARCH_RESULTS', 'SHOUT_BOX', 'STYLES', 'TOPICS', 'TOPIC_VIEWS', 'USERS', 'USER_DATA', 'USER_GROUPS', 'USER_NOTES', 'USER_PROFILE',
		'USER_TITLES', 'VERSION', 'WATCH_LISTS'
	);

	function ubbthreads7_000()
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
	function ubbthreads7_html($text)
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
	function get_ubbthreads7_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT  USER_ID, USER_LOGIN_NAME FROM {$tableprefix}USERS ORDER BY USER_ID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[USER_ID]"] = $row['USER_LOGIN_NAME'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_ubb7_user_profile($Db_object, $databasetype, $tableprefix, $import_id)
	{
		// Check that there is not a empty value
		if(empty($import_id)) { return false; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT * FROM {$tableprefix}USER_PROFILE WHERE USER_ID={$import_id}");

			return $dataset;
		}
		else
		{
			return false;
		}
	}

	function get_ubb7_usergroupid($Db_object, $databasetype, $tableprefix, $import_id)
	{
		$return_array = array();	
	
		// Check that there is not a empty value
		if(empty($import_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT GROUP_ID FROM {$tableprefix}USER_GROUPS WHERE USER_ID={$import_id} ORDER BY GROUP_ID");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array[] = $row['GROUP_ID'];
			}
		}
		
		return $return_array;
	}

	function get_ubb7_PM_topic($Db_object, $databasetype,&$tableprefix, $import_id)
	{
		$return_title = 'Title';	
	
		// Check that there is not a empty value
		if(empty($import_id)) { return $return_title; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT TOPIC_SUBJECT FROM {$tableprefix}PRIVATE_MESSAGE_TOPICS WHERE TOPIC_ID={$import_id}");

			$return_title = $dataset['TOPIC_SUBJECT'];
		}
		
		return $return_title;
	}	
	
	function get_ubb7_PM_tousers($Db_object, $databasetype, $tableprefix, $import_id)
	{
		$return_array = array();	
	
		// Check that there is not a empty value
		if(empty($import_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT USER_ID FROM {$tableprefix}PRIVATE_MESSAGE_USERS WHERE TOPIC_ID={$import_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array[] = $row['USER_ID'];
			}
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
