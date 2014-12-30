<?php if (!defined('IDIR')) { die; }
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
* yabbse API module
*
* @package			ImpEx.yabbse
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yabbse_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.5.5';
	var $_tier = '1';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'YaBB SE';
	var $_homepage 	= 'http://www.yabbse.org';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'banned', 'boards', 'calendar', 'calendar_holiday', 'categories', 'censor', 'instant_messages', 'log_activity',
		'log_banned', 'log_boards', 'log_clicks', 'log_errors', 'log_floodcontrol', 'log_karma', 'log_mark_read', 'log_online',
		'log_topics', 'membergroups', 'members', 'messages', 'polls', 'reserved_names', 'settings', 'topics'
	);

	function yabbse_000()
	{
	}


	/**
	* Returns the cat_id => category array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_yabbse_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}categories");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_CAT]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
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
	function get_yabbse_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}boards ORDER BY ID_BOARD LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_BOARD]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_yabbse_members_list(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT ID_MEMBER, memberName FROM " . $tableprefix . "members ORDER BY ID_MEMBER LIMIT {$start_at}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$tempArray = array($user['ID_MEMBER'] => $user['memberName']);
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
	* Returns the pm_id => pm array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_yabbse_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}instant_messages ORDER BY ID_IM LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_IM]"] = $detail;
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
	function get_yabbse_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}polls ORDER BY ID_POLL LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_POLL]"] = $detail;
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
	function get_yabbse_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}topics ORDER BY ID_TOPIC LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_TOPIC]"] = $detail;
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
	function get_yabbse_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}members ORDER BY ID_MEMBER LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MEMBER]"] = $detail;
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
	function get_yabbse_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}membergroups ORDER BY ID_GROUP LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_GROUP]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_thread_info(&$Db_object, &$databasetype, &$tableprefix, $post_id)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query_first("SELECT * FROM {$tableprefix}messages WHERE ID_MSG={$post_id}");

			return $details_list;
		}
		else
		{
			return false;
		}
	}


	function get_yabbse_poll_thread_id(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query_first("SELECT ID_TOPIC FROM {$tableprefix}topics WHERE ID_POLL={$poll_id}");

			return $details_list[0];
		}
		else
		{
			return false;
		}
	}


	function get_yabbse_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM ".$tableprefix."messages WHERE attachmentFilename != '' ORDER BY ID_MSG LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MSG]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : May 17, 2004, 3:28 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
