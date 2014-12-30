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
* thwboard API module
*
* @package			ImpEx.thwboard
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class thwboard_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.00';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'ThWboard';
	var $_homepage 	= 'http://www.thwboard.de/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
			'adminlog', 'avatar', 'ban', 'bannedwords', 'board', 'calendar', 'category', 'group',
			'groupboard', 'lastvisited', 'news', 'online', 'pm', 'post', 'qlink', 'rank', 'registry',
			'registrygroup', 'session', 'style', 'thread', 'user'
	);

	function thwboard_000()
	{
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
	function get_thwboard_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT userid,username FROM {$tableprefix}user ORDER BY userid LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
					$tempArray = array($user['userid'] => $user['username']);
					$return_array = $return_array + $tempArray;
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
	function get_thwboard_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}board ORDER BY boardid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[boardid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_thwboard_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}category ORDER BY categoryid");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[categoryid]"] = $detail;
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
	function get_thwboard_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}pm ORDER BY pmid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[pmid]"] = $detail;
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
	function get_thwboard_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}post ORDER BY postid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[postid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the ranks_id => ranks array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_thwboard_ranks_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}rank ORDER BY rankid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[rankid]"] = $detail;
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
	function get_thwboard_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}thread ORDER BY threadid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[threadid]"] = $detail;
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
	function get_thwboard_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}user ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[userid]"] = $detail;
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
	function get_thwboard_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}group ORDER BY groupid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[groupid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : June 24, 2004, 2:27 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
