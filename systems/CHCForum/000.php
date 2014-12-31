<?php if (!defined('IDIR')) { die; } if (!defined('IDIR')) { die; }
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
* CHCForum API module
*
* @package			ImpEx.CHCForum
*
*/
class CHCForum_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0.0';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'CHC Forum';
	var $_homepage 	= 'http://typo3.org/documentation/document-library/extension-manuals/chc_forum/current/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'fe_groups', 'fe_users', 'tx_chcforum_category', 'tx_chcforum_conference', 'tx_chcforum_f_conf', 'tx_chcforum_forumgroup', 'tx_chcforum_mail_log', 
		'tx_chcforum_mail_queue', 'tx_chcforum_post', 'tx_chcforum_posts_read', 'tx_chcforum_ratings', 'tx_chcforum_thread', 'tx_chcforum_user_conf', 
		'tx_chcforum_user_thread', 'tx_cwtcommunity_buddylist', 'tx_cwtcommunity_buddylist_approval', 'tx_cwtcommunity_guestbook', 'tx_cwtcommunity_guestbook_data', 
		'tx_cwtcommunity_icons', 'tx_cwtcommunity_message'		
	);

	function CHCForum_000()
	{
	}

	/**
	* Parses and custom HTML for CHCForum
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function CHCForum_html($text)
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
	function get_CHCForum_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT uid, username FROM {$tableprefix}fe_users ORDER BY uid LIMIT {$start}, {$per_page}";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user['username'];
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
	function get_CHCForum_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_chcforum_conference ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_CHCForum_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_chcforum_category ORDER BY uid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
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
	function get_CHCForum_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, 'tx_cwtcommunity_message'))
		{
			return $return_array;		
		}
		
		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_cwtcommunity_message ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
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
	function get_CHCForum_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_chcforum_post ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
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
	function get_CHCForum_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_chcforum_thread ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
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
	function get_CHCForum_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}fe_users ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user;
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
	function get_CHCForum_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}tx_chcforum_forumgroup ORDER BY uid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : May 12, 2006, 4:44 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
