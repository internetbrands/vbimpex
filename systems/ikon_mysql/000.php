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
* ikon_mysql API module
*
* @package			ImpEx.ikon_mysql
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ikon_mysql_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.x';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Ikonboard (MySQL)';
	var $_homepage 	= 'http://www.ikonboard.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'active_sessions', 'address_books', 'attachments', 'authorisation', 'calendar', 'categories',
		'email_templates', 'forum_info', 'forum_moderators', 'forum_poll_voters', 'forum_polls',
		'forum_posts', 'forum_rules', 'forum_subscriptions', 'forum_topics', 'help', 'mem_groups',
		'member_notepads', 'member_profiles', 'member_titles', 'message_data', 'message_stats', 'mod_email',
		'mod_posts', 'moderator_logs', 'search_log', 'ssi_templates', 'templates', 'topic_views'
	);


	function ikon_mysql_000()
	{
	}


	function ikon_mysql_html_2_bb($text)
	{
		$text = preg_replace('#<font(.*)>#U', '', $text);
		$text = str_replace("</font>", '', $text);

		// QUOTES
		$text = preg_replace('#<table border="0" align="center" width="95%" cellpadding="0" cellspacing="0"><tr><td>(.*)</td></tr><tr><td id="QUOTE">#iU', '[QUOTE=$1]', $text);
		$text = str_replace("</td></tr></table>", '[/QUOTE]', $text);

		$text = preg_replace('#<u>(.*)</u>#iU', '[u]$1[/u]', $text);
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
	function get_ikon_mysql_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT MEMBER_ID,MEMBER_NAME
			FROM " . $tableprefix . "member_profiles
			ORDER BY MEMBER_ID
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$userid = str_replace('-', '', $detail['MEMBER_ID']);

					$tempArray = array($userid => $user['MEMBER_NAME']);
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
	function get_ikon_mysql_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_info
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
	* Returns the cat_id => cat array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_ikon_mysql_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."categories
			ORDER BY CAT_ID";


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
	function get_ikon_mysql_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_moderators
			ORDER BY MODERATOR_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[MODERATOR_ID]"] = $detail;
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
	function get_ikon_mysql_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."message_data
			ORDER BY MESSAGE_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[MESSAGE_ID]"] = $detail;
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
	function get_ikon_mysql_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_polls
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ID]"] = $detail;
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
	function get_ikon_mysql_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_posts
			ORDER BY POST_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[POST_ID]"] = $detail;
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
	function get_ikon_mysql_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_topics
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
	function get_ikon_mysql_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT p.*, c.DAY, c.MONTH, c.YEAR FROM " .
			$tableprefix."member_profiles AS p
			LEFT JOIN ".$tableprefix."calendar AS c ON (p.MEMBER_ID = c.MEMBER_ID)
			ORDER BY MEMBER_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$userid = str_replace('-', '', $detail['MEMBER_ID']);
					$return_array[$userid] = $detail;
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
	function get_ikon_mysql_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."mem_groups
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ikon_mysql_threads_ids($Db_object, $databasetype, $tableprefix)
	{

		if ($databasetype == 'mysql')
		{

			$sql = "SELECT threadid, importthreadid, importforumid FROM " .	$tableprefix . "thread";

			$ids = $Db_object->query($sql);

			while ($id = $Db_object->fetch_array($ids))
			{
				$return_array[$id['importforumid']][$id['importthreadid']] = $id['threadid'];
			}

		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ikon_mysql_poll_state($Db_object, $databasetype, $tableprefix, $poll_thread_id)
	{
		$state = 0;
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT  POLL_STATE FROM " .	$tableprefix . "forum_topics
			WHERE TOPIC_ID =" . $poll_thread_id;

			$ids = $Db_object->query_first($sql);

			if($ids['POLL_STATE'] == 'open')
			{
				$state = 1;
			}

			if($ids['POLL_STATE'] == 'closed' OR $ids['POLL_STATE'] == '0')
			{
				$state = 0;
			}
			return $state;
		}
		else
		{
			return false;
		}
	}

	function get_ikon_mysql_vote_voters($Db_object, $databasetype, $tableprefix, $poll_thread_id)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT  MEMBER_ID FROM " .	$tableprefix . "forum_poll_voters  WHERE POLL_ID=" . $poll_thread_id;

			$ids = $Db_object->query($sql);

			while ($id = $Db_object->fetch_array($ids))
			{
				$return_array["$id[MEMBER_ID]"] = $id['MEMBER_ID'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ikon_mysql_attachment_rows(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachments
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ikon_mysql_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $attach_id)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT POST_DATE, ATTACH_HITS, ATTACH_TYPE, POST_ID FROM " .
			$tableprefix."forum_posts
			WHERE ATTACH_ID LIKE " . $attach_id
			;

			$details_list = $Db_object->query_first($sql);

			return $details_list;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ikon_mysql_attachment($path, $file_name)
	{
		$file_address = $path . "/" . $file_name;

		if($file_name == '' OR !is_file($file_address))
		{
			return false;
		}


		$the_file = array();
		$file = fopen($file_address,'rb');

		if($file AND filesize($file_address) > 0)
		{
			$the_file['data']		= fread($file, filesize($file_address));
			$the_file['filesize']	= filesize($file_address);
			$the_file['filehash']	= md5($the_file['data']);
		}

		return $the_file;
	}


	function alter_table_for_attachments(&$Db_object, &$databasetype, &$tableprefix)
	{
		if ($databasetype == 'mysql')
		{
			$sql ="ALTER TABLE `" .$tableprefix."forum_posts` ADD INDEX `attachid_index` ( `ATTACH_ID` )";

			$details_list = $Db_object->query_first($sql);


		}
		else
		{
			return false;
		}
		//hmmmm
		return true;
	}

	function get_ikon_mysql_attachment_details_array(&$Db_object, &$databasetype, &$tableprefix)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT POST_DATE, ATTACH_HITS, ATTACH_TYPE, POST_ID, ATTACH_ID FROM " .
			$tableprefix."forum_posts
			WHERE ATTACH_ID != ''
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ATTACH_ID]"] = $detail;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}




} // Class end
# Autogenerated on : May 27, 2004, 1:49 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
