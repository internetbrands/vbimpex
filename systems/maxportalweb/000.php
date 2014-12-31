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
* maxportalweb API module
*
* @package			ImpEx.maxportalweb
*
*/
class maxportalweb_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Max Web Portal';
	var $_homepage 	= 'http://www.maxwebportal.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'PORTAL_ALLOWED_MEMBERS', 'PORTAL_ANNOUNCE', 'PORTAL_ARCHIVE_REPLY', 'PORTAL_ARCHIVE_TOPICS', 'PORTAL_AVATAR',
		'PORTAL_AVATAR2', 'PORTAL_BOOKMARKS', 'PORTAL_CATEGORY', 'PORTAL_COLORS', 'PORTAL_COMPLAINTS', 'PORTAL_CONFIG',
		'PORTAL_CP_CONFIG', 'PORTAL_EVENTS', 'PORTAL_FORUM', 'PORTAL_MEMBERS', 'PORTAL_MEMBERS_PENDING', 'PORTAL_MODERATOR',
		'PORTAL_MODS', 'PORTAL_ONLINE', 'PORTAL_PM', 'PORTAL_POLLS', 'PORTAL_POLL_ANS', 'PORTAL_PROJECT',
		'PORTAL_PROJECT_ALLOWED_MEMBERS', 'PORTAL_PROJECT_TASKS', 'PORTAL_REPLY', 'PORTAL_SPAM', 'PORTAL_TOPICS', 'PORTAL_TOTALS'
	);


	function maxportalweb_000()
	{
	}


	/**
	* Parses and custom HTML for maxportalweb
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function maxportalweb_html($text)
	{
		$text = str_replace('<BLOCKQUOTE id=quote><font size=1 face="Verdana, Arial, Helvetica" id=quote>quote:<hr height=1 noshade id=quote>', '[quote]', $text);
		$text = str_replace('<hr height=1 noshade id=quote></BLOCKQUOTE id=quote></font id=quote><font face="Verdana, Arial, Helvetica" size=2 id=quote>', '[/quote]', $text);

		$text = str_replace('</p>', '', $text);
		
		$text = str_replace('<span class="quote">', '[quote]', $text);
		$text = preg_replace('#<span style=(.*)>#siU', '', $text);
		$text = str_replace('</span>', '[/quote]', $text);
		
		$text = str_replace('&lsquo;', '‘', $text);
		$text = str_replace('&rsquo;', '’', $text);
		$text = str_replace('&ldquo;', '“', $text);
		$text = str_replace('&rdquo;', '”', $text);
		$text = str_replace('&quot;', "'",  $text);
		
		$text = preg_replace('#<font(.*)>#siU', '', $text);
		$text = str_replace('</font>', '', $text);
		
		$text = preg_replace('#<a href="(.*)"(.*)>(.*)<./a>#siU', '[url=$1]$3[/url]', $text);
		
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
	function get_maxportalweb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT MEMBER_ID, M_NAME
			FROM " . $tableprefix . "PORTAL_MEMBERS
			ORDER BY MEMBER_ID
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[MEMBER_ID]"] = $user['M_NAME'];
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
	function get_maxportalweb_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_FORUM
			ORDER BY FORUM_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[FORUM_ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the moderator_id => moderator array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_maxportalweb_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_MODERATOR
			ORDER BY MOD_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[MOD_ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the pmtext_id => pmtext array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_maxportalweb_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_PM
			ORDER BY M_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[M_ID]"] = $detail;
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
	function get_maxportalweb_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_POLLS
			ORDER BY POLL_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[POLL_ID]"] = $detail;
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
	function get_maxportalweb_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_REPLY
			ORDER BY REPLY_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					// There can be duplicate id's in this so we can't use the REPLY_ID
					$return_array[] = $detail;
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
	function get_maxportalweb_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_TOPICS
			ORDER BY TOPIC_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[TOPIC_ID]"] = $detail;
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
	function get_maxportalweb_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_MEMBERS
			ORDER BY MEMBER_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[MEMBER_ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_maxportalweb_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PORTAL_CATEGORY
			ORDER BY CAT_ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[CAT_ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function clean_date($date)
	{
		$year	= substr($date, 0, 4);
		$month	= substr($date, 4, 2);
		$day	= substr($date, 6, 2);
		$hour	= substr($date, 8, 2);
		$min	= substr($date, 10, 2);
		$sec	= substr($date, 12, 2);

		$time =  mktime ($hour, $min, $sec, $month, $day, $year);

		return $time;
	}


	function get_maxportalweb_vote_voters(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."POLL_ANS
			WHERE POLL_ID = " . $poll_id . "
			ORDER BY ANS_ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[MEMBER_ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_maxportalweb_poll_threadid(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT TOPIC_ID FROM " .
			$tableprefix."PORTAL_TOPICS
			WHERE T_POLL = " . $poll_id;

			$details_list = $Db_object->query_first($sql);

			return $details_list['TOPIC_ID'];
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : September 28, 2004, 7:20 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
