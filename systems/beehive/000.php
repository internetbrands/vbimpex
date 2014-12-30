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
* beehive API module
*
* @package			ImpEx.beehive
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class beehive_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0.5';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'beehive';
	var $_homepage 	= 'http://beehiveforum.sourceforge.net/';
	var $_tier = '2';
	
	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'DEDUPE', 'DICTIONARY', 'FORUMS', 'FORUM_SETTINGS', 'PM', 'PM_ATTACHMENT_IDS', 'PM_CONTENT', 'SESSIONS', 'USER',
		'USER_FORUM', 'USER_PREFS', 'ADMIN_LOG', 'BANNED_IP', 'FILTER_LIST', 'FOLDER', 'FORUM_LINKS', 'GROUPS', 'GROUP_PERMS',
		'GROUP_USERS', 'LINKS', 'LINKS_COMMENT', 'LINKS_FOLDERS', 'LINKS_VOTE', 'POLL', 'POLL_VOTES', 'POST', 'POST_ATTACHMENT_FILES',
		'POST_ATTACHMENT_IDS', 'POST_CONTENT', 'PROFILE_ITEM', 'PROFILE_SECTION', 'STATS', 'THREAD', 'USER_FOLDER', 'USER_PEER',
		'USER_POLL_VOTES', 'USER_PREFS', 'USER_PROFILE', 'USER_SIG', 'USER_THREAD', 'VISITOR_LOG'
	);

	function beehive_000()
	{
	}

	/**
	* Parses and custom HTML for beehive
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function beehive_html($text)
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
	function get_beehive_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT UID, LOGON
			FROM USER 
			ORDER BY UID
			LIMIT " . $start . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[UID]"] = $user['LOGON'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* Returns the forum_id => forum array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."FOLDER
			ORDER BY FID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[FID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the poll_id => poll array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."POLL
			ORDER BY TID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the post_id => post array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."POST
			ORDER BY PID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);
			$place=1;
			while ($detail = $Db_object->fetch_array($details_list))
			{
				
				$return_array[$place] = $detail;
				
				// Get post content.
				$post = $Db_object->query_first("SELECT CONTENT FROM " . $tableprefix . "POST_CONTENT WHERE PID = " . $detail['PID']);
				$return_array[$place]['content'] = $post['CONTENT'];
				$place++;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the thread_id => thread array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."THREAD
			ORDER BY TID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the user_id => user array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM USER ORDER BY UID LIMIT " . $start_at . "," .	$per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[UID]"] = $detail;
				
				// Get signature.
				$signature = $Db_object->query_first("SELECT CONTENT FROM " . $tableprefix . "USER_SIG WHERE UID = " . $detail['UID']);
				$return_array["$detail[UID]"]['signature'] = $signature['CONTENT'];
				
				// Get User group.
				$signature = $Db_object->query_first("SELECT GID FROM " . $tableprefix . "GROUP_USERS WHERE UID = " . $detail['UID']);
				$return_array["$detail[UID]"]['usergroupid'] = $signature['GID'];
				
				unset($signature);
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the usergroup_id => usergroup array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_beehive_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."GROUPS
			ORDER BY GID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[GID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : May 4, 2005, 3:12 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
