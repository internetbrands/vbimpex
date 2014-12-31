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
* jForum API module
*
* @package			ImpEx.jForum
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class jForum_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.1.5';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'JForum';
	var $_homepage 	= 'http://www.jforum.net';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('attach', 'attach_desc', 'attach_quota', 'banlist', 'banner', 'bookmarks', 'categories', 'config', 'extension_groups',
		'extensions', 'forums', 'groups', 'karma', 'posts', 'posts_text', 'privmsgs', 'privmsgs_text', 'quota_limit', 'ranks', 'role_values', 'roles',
		'search_results', 'search_topics', 'search_wordmatch', 'search_words', 'sessions', 'smilies', 'themes', 'topics', 'topics_watch', 'user_groups',
		'users', 'vote_desc', 'vote_results', 'vote_voters', 'words'
	);


	function jForum_000()
	{
	}


	/**
	* Parses and custom HTML for jForum
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function jForum_html($text)
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
	function get_jForum_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT user_id, username FROM " . $tableprefix . "users ORDER BY user_id LIMIT " . $start . "," . $per_page);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[user_id]"] = $user['username'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	/**
	* Returns the attachment_id => attachment array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_jForum_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT attach.attach_id, attach.post_id, attach.privmsgs_id, attach.user_id,
			attach_desc.attach_id, attach_desc.physical_filename, attach_desc.real_filename, attach_desc.download_count, attach_desc.description, attach_desc.filesize, attach_desc.upload_time
			FROM " . $tableprefix . "attach AS attach
			LEFT JOIN " .$tableprefix . "attach_desc AS attach_desc ON (attach.attach_id = attach_desc.attach_id)
			WHERE attach.privmsgs_id = 0
			ORDER BY attach.attach_id
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[attach_id]"] = $detail;
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
	function get_jForum_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . "forums ORDER BY forum_id LIMIT " . $start_at . "," . $per_page);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[forum_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_jForum_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . "categories ORDER BY categories_id");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[categories_id]"] = $detail;
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
	function get_jForum_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT privmsgs.privmsgs_id, privmsgs.privmsgs_type, privmsgs.privmsgs_subject, privmsgs.privmsgs_from_userid, privmsgs.privmsgs_to_userid, privmsgs.privmsgs_date,
				privmsgs.privmsgs_ip, privmsgs.privmsgs_enable_bbcode, privmsgs.privmsgs_enable_html, privmsgs.privmsgs_enable_smilies, privmsgs.privmsgs_attach_sig,
			privmsgs_text.privmsgs_id, privmsgs_text.privmsgs_text
			FROM " . $tableprefix . "privmsgs AS privmsgs
			LEFT JOIN " .$tableprefix . "privmsgs_text AS privmsgs_text ON(privmsgs.privmsgs_id = privmsgs_text.privmsgs_id)
			ORDER BY privmsgs.privmsgs_id
			LIMIT " . $start_at . "," .	$per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[privmsgs_id]"] = $detail;
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
	function get_jForum_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT posts.post_id, posts.topic_id, posts.forum_id, posts.user_id, posts.post_time, posts.poster_ip, posts.enable_bbcode, posts.enable_html,
			posts.enable_smilies, posts.enable_sig,
			posts_text.post_id, posts_text.post_text, posts_text.post_subject
			FROM " . $tableprefix . "posts AS posts
			LEFT JOIN " . $tableprefix . "posts_text AS posts_text ON (posts.post_id = posts_text.post_id)
			ORDER BY posts.post_id
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[post_id]"] = $detail;
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
	function get_jForum_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . "topics ORDER BY topic_id LIMIT " . $start_at . "," . $per_page);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[topic_id]"] = $detail;
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
	function get_jForum_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . "users ORDER BY user_id LIMIT " . $start_at . "," . $per_page);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$groups = array();

				$jgroups = $Db_object->query("SELECT group_id FROM " . $tableprefix . "user_groups WHERE user_id=" . $detail['user_id'] . " ORDER BY group_id ASC");

				while ($group = $Db_object->fetch_array($jgroups))
				{
					$groups[] = $group['group_id'];
				}

				$return_array["$detail[user_id]"] = $detail;
				$return_array["$detail[user_id]"]['usergroups'] = $groups;
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
	function get_jForum_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . "groups ORDER BY group_id LIMIT " . $start_at . "," . $per_page);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[group_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


} // Class end
# Autogenerated on : February 15, 2006, 6:20 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
