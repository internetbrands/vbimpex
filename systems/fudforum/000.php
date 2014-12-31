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
* fudforum API module
*
* @package			ImpEx.fudforum
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class fudforum_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.x';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'FUD Forum';
	var $_homepage 	= 'http://www.fudforum.org/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'action_log', 'ann_forums', 'announce', 'attach', 'avatar', 'blocked_logins', 'buddy', 'cat', 'custom_tags', 'email_block',
		'ext_block', 'fc_view', 'forum', 'forum_notify', 'forum_read', 'group_cache', 'group_members', 'group_resources', 'groups',
		'index', 'ip_block', 'level', 'mime', 'mlist', 'mod', 'mod_que', 'msg', 'msg_report', 'nntp', 'pmsg', 'poll', 'poll_opt',
		'poll_opt_track', 'read', 'replace', 'search', 'search_cache', 'ses', 'smiley', 'stats_cache', 'themes', 'thr_exchange', 'thread',
		'thread_notify', 'thread_rate_track', 'thread_view', 'title_index', 'tmp_consist', 'user_ignore', 'users'
	);


	function fudforum_000()
	{
	}


	/**
	* Parses and custom HTML for fudforum
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function fudforum_html($text)
	{
		$text = preg_replace('#\<table border=(.*)class="SmallText">\[b\](.*) wrote on (.*)</td></tr><tr><td class="quote">(.*)</td></tr></table>#siU', '[quote=$2]$4[/quote]', $text);

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
	function get_fudforum_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT id, login
			FROM " . $tableprefix . "users
			ORDER BY id
			LIMIT " . $start . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[id]"] = $user['login'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}



	function get_thread_title(&$Db_object, &$databasetype, &$tableprefix, $root_msg_id)
	{
		// Check that there is not a empty value
		if(empty($root_msg_id)) { return 'empty'; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT subject, post_stamp FROM " . $tableprefix . "msg WHERE id={$root_msg_id}";

			$subject = $Db_object->query_first($sql);

			return $subject;
		}
		else
		{
			return false;
		}
	}

	function get_poll_options(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return false; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . $tableprefix . "poll_opt WHERE poll_id=" . $poll_id;

			$poll_opts = $Db_object->query($sql);

			while ($poll_opt = $Db_object->fetch_array($poll_opts))
			{
				$return_array["$poll_opt[id]"] = $poll_opt;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_vote_voters(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return false; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . $tableprefix . "poll_opt_track WHERE poll_id=" . $poll_id;

			$poll_opts = $Db_object->query($sql);

			while ($poll_opt = $Db_object->fetch_array($poll_opts))
			{
				$return_array["$poll_opt[id]"] = $poll_opt;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_threadid_for_poll(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return false; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT thread_id FROM " . $tableprefix . "msg WHERE poll_id=" . $poll_id;

			$thread_id = $Db_object->query_first($sql);

			return $thread_id['thread_id'];
		}
		else
		{
			return false;
		}
	}

	function get_post($file, &$offset, $lenght)
	{
		if(!$lenght)
		{
			return false;
		}

		$fp = fopen($file, 'rb');
		fseek($fp, $offset);

		return fread($fp, $lenght);
	}

} // Class end
# Autogenerated on : July 5, 2005, 3:53 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
