<?php
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
* eshare API module
*
* @package			ImpEx.eshare
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class eshare_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0.0';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'eshare';
	var $_homepage 	= 'www.media3.net/eshare.htm';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'PubClassDefinitions', ' PubContent', 'PubContentTypes', 'PubDataClasses', 'PubInstances', 'PubObjects', 'PubPages', 'PubUsers', 'PubUsersInterests', 'Threads'
	);


	function eshare_000()
	{
	}


	/**
	* Parses and custom HTML for eshare
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function eshare_html($text)
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
	function get_eshare_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT UserID, Username FROM " . $tableprefix . "PubUsers ORDER BY UserID LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[UserID]"] = $user['Username'];
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
	function get_eshare_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}PubContent ORDER BY ContentID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ContentID]"] = $detail;
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
	function get_eshare_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Threads ORDER BY ThreadID  LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ThreadID]"] = $detail;
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
	function get_eshare_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Threads WHERE ParentID=0 ORDER BY ThreadID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ThreadID]"] = $detail;
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
	function get_eshare_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}PubUsers ORDER BY UserID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[UserID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : October 10, 2006, 7:37 am
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
