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
* DCFm_000
*
* @package 		ImpEx.DCFm
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class DCFm_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '1.27';
	

	/**
	* Module string
	*
	* Class string for phpUnit header
	*
	* @var    array
	*/
	var $_modulestring 	= 'DCForum+ MySQL';
	var $_homepage 	= 'http://www.dcscripts.com/';
	var $_tier = '2';
	
	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
					'announcement', 'badip', 'bookmark', 'buddy', 'event', 'eventrepeat', 'forum',
					'forumsub', 'forumtype', 'group', 'inbox', 'inboxlog', 'ip', 'log', 'moderator',
					'notice', 'pflist', 'pollchoices', 'pollvotes', 'searchcache', 'searchparam',
					'security', 'session', 'setup', 'task', 'topicrating', 'topicsub', 'upload',
					'user', 'userrating', 'usertimemark'
					);


	function DCFm_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed			The string to parse
	* @param	boolean					Truncate smilies
	*
	* @return	array
	*/
	function DCFm_html($text)
	{
		return $text;
	}


	/**
	* Returns unix timestamp from a timestamp(14)
	*
	* @param	string	mixed			The string to parse
	*
	* @return	array
	*/
	function time_to_stamp($old_date)
	{
		return $this->do_dcf_date($old_date);
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
	function get_DCFm_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT id,username FROM {$tableprefix}user ORDER BY id LIMIT {$start}, {$per_page}");

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
	* Returns the usergroup details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_DCFm_user_group_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$group_list = $Db_object->query("SELECT * FROM {$tableprefix}group ORDER BY id");

			while ($group = $Db_object->fetch_array($group_list))
			{
				$return_array["$group[id]"] = $group;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}


	/**
	* Returns the user details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_user_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$users = $DB_object->query("SELECT * FROM {$table_prefix}user ORDER BY id LIMIT {$start}, {$per_page}");

			while ($user = $DB_object->fetch_array($users))
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


	/**
	* Returns the forum details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_forum_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$forums = $DB_object->query("SELECT * FROM {$table_prefix}forum ORDER BY id LIMIT {$start}, {$per_page}");

			while ($forum = $DB_object->fetch_array($forums))
			{
				$return_array["$forum[id]"] = $forum;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the threads details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_threads_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page, &$table_number)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$its_always_blank = '';

		if(!$this->check_table($DB_object, $database_type, $its_always_blank, "{$table_number}_mesg"))
		{
			return $return_array;
		}

		if ($database_type == 'mysql')
		{
			$threads = $DB_object->query("SELECT * FROM {$table_number}_mesg WHERE top_id = 0 AND parent_id = 0 AND `type` != 98 ORDER BY id LIMIT {$start}, {$per_page}");

			while ($thread = $DB_object->fetch_array($threads))
			{
				$return_array["$thread[id]"] = $thread;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the posts details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_posts_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page, &$table_number)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$its_always_blank = '';

		if(!$this->check_table($DB_object, $database_type, $its_always_blank, "{$table_number}_mesg"))
		{
			return $return_array;
		}

		if ($database_type == 'mysql')
		{
			$posts = $DB_object->query("SELECT * FROM {$table_number}_mesg WHERE `type` != 98 AND id > {$start} ORDER BY id LIMIT {$per_page}");

			while ($post = $DB_object->fetch_array($posts))
			{
				$return_array["$post[id]"] = $post;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the *_mesg table number
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			'start' or 'end'
	*
	* @return	int
	*/
	function get_forum_number(&$DB_object, &$database_type, &$table_prefix, $type)
	{
		if ($database_type == 'mysql')
		{
			if($type == 'start')
			{
				$sql = "SELECT min(id) AS id FROM {$table_prefix}forum";
			}

			if($type == 'end')
			{
				$sql = "SELECT max(id) AS id FROM {$table_prefix}forum";
			}

			$id = $DB_object->query_first($sql);
		}
		else
		{
			return false;
		}

		if (do_mysql_fetch_assoc)
		{
			return $id['id'];
		}
		else
		{
			return $id[0];
		}
	}


	/**
	* Returns the poll details as an array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_polls_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$poll_start_at = $this->iif($poll_start_at == '','0',$poll_start_at);

			$polls = $DB_object->query("SELECT * FROM {$table_prefix}pollchoices ORDER BY id LIMIT {$start}, {$per_page}");

			while ($poll = $DB_object->fetch_array($polls))
			{
				$return_array["$poll[id]"] = $poll;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the poll question
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			importthreadid
	*
	* @return	array
	*/
	function get_DCFm_poll_question(&$DB_object, &$database_type, &$table_prefix, $thread_id)
	{
		if ($database_type == 'mysql')
		{
			$title = $DB_object->query_first("SELECT title FROM {$table_prefix}thread	WHERE importthreadid={$thread_id}");

			return $title['title'];
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the post result details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			The DCFm post id
	*
	* @return	array
	*/
	function get_DCFm_poll_results_details(&$DB_object, &$database_type, &$table_prefix, &$poll_id)
	{

		if ($database_type == 'mysql')
		{
			$sql = "
				SELECT CASE
					WHEN vote='1'	THEN '1'
					WHEN vote='2'	THEN '2'
					WHEN vote='3'	THEN '3'
					WHEN vote='4'	THEN '4'
					WHEN vote='5'	THEN '5'
					WHEN vote='6'	THEN '6'
					ELSE 'error' END AS choice,
				count(vote) AS votes
				FROM {$table_prefix}pollvotes
				WHERE poll_id={$poll_id}
				GROUP by vote
			";

			$polls = $DB_object->query($sql);
			$options = 0;
			$voters = 0;
			while ($poll = $DB_object->fetch_array($polls))
			{
				$options++;
				$string['votes'] .= $poll['votes'] . '|||';
				$voters += intval($poll['votes']);
			}
			$string['votes'] = substr($string['votes'], 0, -3);
			$string['numoptions'] = $options;
			$string['voters'] = $voters;
		}
		else
		{
			return false;
		}
		return $string;
	}


	/**
	* Returns the poll voter details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			The DCFm post id
	*
	* @return	array
	*/
	function get_DCFm_vote_voters(&$DB_object, &$database_type, &$table_prefix, &$poll_id)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{
			$polls = $DB_object->query("SELECT u_id, vote FROM {$table_prefix}pollvotes WHERE poll_id={$poll_id}");

			while ($poll = $DB_object->fetch_array($polls))
			{
				$return_array["$poll[u_id]"] = $poll['vote'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the pm details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_pms(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$pms = $DB_object->query("SELECT * FROM {$table_prefix}inbox ORDER BY id LIMIT {$start}, {$per_page}");

			while ($pm = $DB_object->fetch_array($pms))
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
	* Returns the moderators details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_DCFm_moderators_details(&$DB_object, &$database_type, &$table_prefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$mods = $DB_object->query("SELECT * FROM {$table_prefix}moderator ORDER BY id LIMIT {$start}, {$per_page}");

			while ($mod = $DB_object->fetch_array($mods))
			{
				$return_array["$mod[id]"] = $mod;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function do_dcf_date($old_date)
	{
		if(!$old_date)
		{
			return 0;
		}

		if (strlen($old_date) == 14)
		{
			//YYYYMMDDHHMMSS
			return mktime (substr($old_date, 8, 2), substr($old_date, 10, 2), substr($old_date, 12, 2), substr($old_date, 4, 2), substr($old_date, 6, 2), substr($old_date, 0, 4));
		}
		else
		{
			return strtotime($old_date);
		}
	}

}
/*======================================================================*/
?>
