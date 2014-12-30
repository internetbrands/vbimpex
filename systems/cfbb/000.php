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
* cfbb API module
*
* @package			ImpEx.cfbb
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class cfbb_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.3.1';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'CFBB';
	var $_homepage 	= 'http://www.adersoftware.com/index.cfm?page=cfbb';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'avatar', 'category', 'forum', 'forumModerator', 'message', 'private_message', 'ranktitle', 'settings',
		'subscription', 'topic', 'topicVisit', 'users', 'usersession'
	);


	function cfbb_000()
	{
	}


	/**
	* Parses and custom HTML for cfbb
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function cfbb_html($text)
	{
		$text = str_replace('<div class="QUOTE">', '[quote]', $text);
		$text = str_replace('</div id=quote>', '[/quote]', $text);
		
		
		$text = preg_replace('#<hr(.*)>#siU', '', $text);
		
		$text = preg_replace('#<font(.*)>#siU', '', $text);
		$text = preg_replace('#</font(.*)>#siU', '', $text);
	
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
	function get_cfbb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	id,
							username
					FROM {$tableprefix}users WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}users ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[id]"] = $user['username'];
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
	function get_cfbb_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }
		
		if ($databasetype == 'mssql')
		{		
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}forum");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	id,
							categoryID,
							title,
							description,
							createdOn,
							pos
					FROM {$tableprefix}forum WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}forum ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";

			$forum_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forum_list))
			{
				$return_array["$forum[id]"] = $forum;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_cfbb_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();
		
		if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	id,
							name,
							pos
					FROM {$tableprefix}category
					";

			$categories = $Db_object->query($sql);

			while ($cat = $Db_object->fetch_array($categories))
			{
				$return_array["$cat[id]"] = $cat;
			}

			return $return_array;
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
	function get_cfbb_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}private_message");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
			
			$sql = "SELECT 	id,
							fromUserID,
							fromUserName,
							createdOn,
							viewed,
							toUserID,
							toUserName,
							CAST([message] as TEXT) as message,
							subject,
							box,
							ip_address
					FROM {$tableprefix}private_message WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}private_message ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";

			$pm_list = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pm_list))
			{
				$return_array["$pm[id]"] = $pm;
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
	function get_cfbb_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}message");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
			
			$sql = "SELECT	id,
							forumID,
							topicID,
							createdOn,
							createdBy,
							message,
							ip_address
					FROM {$tableprefix}message WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}message ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";							
					
			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array["$pm[id]"] = $pm;
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
	function get_cfbb_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}topic");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
			
			$sql = "SELECT	id,
							forumID,
							replies,
							createdOn,
							createdBy,
							createdByName,
							views,
							subject,
							sticky,
							locked
					FROM {$tableprefix}topic WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}topic ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";							
					
			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array["$pm[id]"] = $pm;
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
	function get_cfbb_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
			
			$sql = "SELECT	id,
							username,
							password,
							firstname,
							lastname,
							email,
							signature,
							website,
							location,
							interests,
							occupation,
							aim_number,
							icq_number,
							msn_number,
							yahoo_number,
							last_visit,
							last_post,
							createdOn,
							num_posts,
							last_ip,
							custom_title,
							avatar
					FROM {$tableprefix}users WHERE id
						IN(SELECT TOP {$per_page} id
							FROM (SELECT TOP {$internal} id FROM {$tableprefix}users ORDER BY id)
						A ORDER BY id DESC)
					ORDER BY id";							
			
			$user_list = $Db_object->query($sql);
			
			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[id]"] = $user;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : May 18, 2006, 2:49 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
