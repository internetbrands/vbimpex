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
* lud API module
*
* @package			ImpEx.lud
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ldu_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '121';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Seditio (LDU)';
	var $_homepage 	= 'http://www.neocrome.net/forums.php';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'auth', 'banlist', 'cache', 'com', 'config', 'core', 'forum_posts', 'forum_sections', 'forum_structure',
		'forum_topics', 'forummodkit_users', 'forummodkit_warns', 'groups', 'groups_users', 'logger', 'moderation',
		'moderation_warnings', 'moviegallery', 'newsletter', 'notifier', 'online', 'pages', 'pfs', 'pfs_folders',
		'pfs_potw', 'pfs_tags', 'plugins', 'pm', 'polls', 'polls_options', 'polls_voters', 'rated', 'ratings',
		'referers', 'smilies', 'stats', 'structure', 'tags', 'trash', 'users', 'visitorscounter'
	);

	function ldu_000()
	{
	}

	/**
	* Parses and custom HTML for lud
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function ldu_html($text)
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
	function get_ldu_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT user_id,user_name FROM {$tableprefix}users ORDER BY user_id LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[user_id]"] = $user['user_name'];
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
	function get_ldu_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}forum_sections ORDER BY fs_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[fs_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
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
	function get_ldu_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}pm ORDER BY pm_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[pm_id]"] = $detail;
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
	function get_ldu_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}polls ORDER BY poll_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[poll_id]"] = $detail;
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
	function get_ldu_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}forum_posts ORDER BY fp_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[fp_id]"] = $detail;
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
	function get_ldu_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}forum_topics ORDER BY ft_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ft_id]"] = $detail;
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
	function get_ldu_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}users ORDER BY user_id LIMIT {$start_at},  {$per_page}";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[user_id]"] = $user;
				$group_id = $Db_object->query_first("SELECT gru_groupid from {$tableprefix}groups_users WHERE gru_userid = " . $user['user_id']);
				$return_array["$user[user_id]"]['groupid'] = $group_id;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
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
	function get_ldu_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}groups ORDER BY grp_id LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[grp_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : May 19, 2006, 7:40 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
