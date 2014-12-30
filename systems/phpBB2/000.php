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
* phpBB2_000
*
* @package 		ImpEx.phpBB2
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name:  $
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB2_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '2.0.22';
	var $_tier = '1';

	/**
	* Module string
	*
	* Class string for phpUnit header
	*
	* @var    array
	*/
	var $_modulestring 	= 'phpBB2';
	var $_homepage 	= 'http://www.phpbb.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'attachments', 'attachments_desc', 'auth_access','banlist','categories','config','confirm','disallow','forum_prune','forums','groups','posts','posts_text',
		'privmsgs','privmsgs_text','ranks','search_results','search_wordlist','search_wordmatch','sessions','smilies',
		'themes','themes_name','topics','topics_watch','user_group','users','vote_desc','vote_results','vote_voters','words'
	);


	function phpBB2_000()
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
	function phpbb_html($text, $truncate_smilies = false)
	{
		// Quotes
		// With name

		for($i=0;$i<10;$i++)
		{
			$text = preg_replace('#\[quote:([a-z0-9]+)="(.*)"\](.*)\[/quote:\\1\]#siU', '[quote=$2]$3[/quote]', $text);
		}
			// Without
		for($i=0;$i<10;$i++)
		{
			$text = preg_replace('#\[quote:([a-z0-9]+)\](.*)\[/quote:\\1\]#siU', '[quote]$2[/quote]', $text);
		}

		$text = preg_replace('#\[code:([0-9]+):([a-z0-9]+)\](.*)\[/code:\\1:\\2\]#siU', '[code]$3[/code]', $text);

		// Bold , Underline, Italic
		$text = preg_replace('#\[b:([a-z0-9]+)\](.*)\[/b:\\1\]#siU', '[b]$2[/b]', $text);
		$text = preg_replace('#\[u:([a-z0-9]+)\](.*)\[/u:\\1\]#siU', '[u]$2[/u]', $text);
		$text = preg_replace('#\[i:([a-z0-9]+)\](.*)\[/i:\\1\]#siU', '[i]$2[/i]', $text);

		// Images
		$text = preg_replace('#\[img:([a-z0-9]+)\](.*)\[/img:\\1\]#siU', '[img]$2[/img]', $text);

		// Lists
		$text = preg_replace('#\[list(=1|=a)?:([a-z0-9]+)\](.*)\[/list:(u:|o:)?\\2\]#siU', '[list$1]$3[/list]', $text);

		// Lists items
		$text = preg_replace('#\[\*:([a-z0-9]+)\]#siU', '[*]', $text);

		// Color
		$text = preg_replace('#\[color=([^:]*):([a-z0-9]+)\](.*)\[/color:\\2\]#siU', '[color=$1]$3[/color]', $text);

		// Font
		$text = preg_replace('#\[font=([^:]*):([a-z0-9]+)\](.*)\[/font:\\2\]#siU', '[font=$1]$3[/font]', $text);

		// Text size
		$text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);

		// center
		$text = preg_replace('#\[align=center:([a-z0-9]+)\](.*)\[/align:\\1\]#siU', '[center]$2[/center]', $text);

		// Smiles
		// Get just truncated phpBB smilies for this one to do the replacments

		if($truncate_smilies)
		{
			$text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
		}

		$text = html_entity_decode($text);

		return $text;
	}

	/**
	* Regex call back
	*
	* @param	string	mixed			The origional size
	* @param	string	mixed			The content text
	*
	* @return	array
	*/
	function pixel_size_mapping($size, $text)
	{
		$text = str_replace('\"', '"', $text);

		if ($size <= 8)
		{
		   $outsize = 1;
		}
		else if ($size <= 10)
		{
		   $outsize = 2;
		}
		else if ($size <= 12)
		{
		   $outsize = 3;
		}
		else if ($size <= 14)
		{
		   $outsize = 4;
		}
		else if ($size <= 16)
		{
		   $outsize = 5;
		}
		else if ($size <= 18)
		{
		   $outsize = 6;
		}
		else
		{
		   $outsize = 7;
		}

		return '[size=' . $outsize . ']' . $text .'[/size]';
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
	function get_phpbb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'user_id' 	=> 'mandatory',
			'username'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "users", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT user_id, username FROM {$tableprefix}users ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[user_id]"] = $user['username'];
			}
			return $return_array;
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	user_id,
							username
					FROM {$tableprefix}users WHERE user_id
						IN(SELECT TOP {$per_page} user_id
							FROM (SELECT TOP {$internal} user_id FROM {$tableprefix}users ORDER BY user_id)
						A ORDER BY user_id DESC)
					ORDER BY user_id";

			$user_list = $Db_object->query($sql);

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
	function get_phpBB2_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'group_single_user'	=> 'mandatory',
			'group_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "groups", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}groups WHERE group_single_user=0 ORDER BY group_id LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[group_id]"] = $detail;
			}
		}
		elseif ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}groups");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	group_id,
							group_name,
							group_description
					FROM {$tableprefix}groups WHERE group_id
						IN(SELECT TOP {$per_page} group_id
							FROM (SELECT TOP {$internal} group_id FROM {$tableprefix}groups ORDER BY group_id)
						A ORDER BY group_id DESC)
						WHERE group_single_user=0
					ORDER BY group_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array["$details[group_id]"] = $details['username'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_phpbb2_usergroupids(&$Db_object, &$databasetype, &$tableprefix, $user_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($user_id)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'group_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "user_group", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}user_group WHERE user_id={$user_id} ORDER BY group_id");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($detail['group_id'] != $user_id AND $detail['group_id'])
				{
					$return_array[] = $detail['group_id'];
				}
			}
		}
		else
		{
			return false;
		}
		return $return_array;
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
	function get_phpbb2_user_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'user_id' 	=> 'mandatory',
			'username'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "users", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$users = $Db_object->query("SELECT * FROM {$tableprefix}users ORDER BY user_id LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($users))
			{
				switch ($user['user_level'])
				{
					case "0":
						$user['usergroupid'] = "2"; //Registered
						break;
					case "1":
						$user['usergroupid'] = "6"; //Administrator
						break;
					case "2":
						$user['usergroupid'] = "7"; //Moderator
						break;
				}

				if (!$user['usergroupid'])
				{
					$user['usergroupid'] = "1"; //Unregistered
				}

				$user['user_sig'] = $this->html_2_bb($user['user_sig'], $user['user_sig_bbcode_uid']);

				$return_array["$user[user_id]"] = $user;
				unset($user);
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	user_id,
							username,
							user_email,
							user_password,
							user_aim,
							user_icq,
							user_regdate,
							user_website,
							user_lastvisit,
							user_yim,
							user_msnm,
							user_posts,
							user_occ,
							user_from,
							user_interests,
							user_avatar,
							user_sig
					FROM {$tableprefix}users WHERE user_id
						IN(SELECT TOP {$per_page} user_id
							FROM (SELECT TOP {$internal} user_id FROM {$tableprefix}users ORDER BY user_id)
						A ORDER BY user_id DESC)
					ORDER BY user_id";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[user_id]"] = $user;
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
	* Returns a type of ban list
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	object	displayobject	The display object
	* @param	object	sessionobject	The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The type of list to be returned
	*
	* @return	array
	*/
	function get_list(&$Db_object, &$databasetype, &$tableprefix, $type)
	{
		$return_array = array();

		// Check Mandatory Fields.
		$req_fields = array(
			'ban_id' 		=> 'mandatory',
			"ban_{$type}"	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "banlist", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$ban_list = $Db_object->query("SELECT ban_id, ban_{$type} AS ban FROM {$tableprefix}banlist");

			while ($ban = $Db_object->fetch_array($ban_list))
			{
				if ($ban['ban'])
				{
					$return_array["$ban[ban_id]"] = $ban['ban'];
				}
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}banlist");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ban_id,
							ban_" . $type . "
					FROM {$tableprefix}banlist WHERE ban_id
						IN(SELECT TOP {$per_page} ban_id
							FROM (SELECT TOP {$internal} ban_id FROM {$tableprefix}banlist ORDER BY ban_id)
						A ORDER BY ban_id DESC)
					ORDER BY ban_id";

			$ban_list = $Db_object->query($sql);

			while ($ban = $Db_object->fetch_array($ban_list))
			{
				$return_array["$ban[ban_id]"] = $ban[1];
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
	* Imports a list with error handeling
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	object	displayobject	The display object
	* @param	object	sessionobject	The prefix to the table name i.e. 'vb3_'
	* @param	array	mixed			The list to be imported
	* @param	string	mixed			The type of list being imported
	*
	* @return	none
	*/
	function do_list(&$Db_target, &$displayobject, &$sessionobject, &$list, $type)
	{
		$result = $this->import_ban_list($Db_target,
										$sessionobject->get_session_var('targetdatabasetype'),
										$sessionobject->get_session_var('targettableprefix'),
										$list,
										$type);
	}

	/**
	* Returns the categories details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_phpbb2_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		// Check Mandatory Fields.
		$req_fields = array(
			'cat_id'	=> 'mandatory',
			'cat_title'	=> 'mandatory',
			'cat_order'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "categories", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$categories = $Db_object->query("SELECT * FROM {$tableprefix}categories");

			while ($cat = $Db_object->fetch_array($categories))
			{
				$return_array["$cat[cat_id]"] = $cat;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	cat_id,
							cat_title,
							cat_order
					FROM {$tableprefix}categories
					";

			$categories = $Db_object->query($sql);

			while ($cat = $Db_object->fetch_array($categories))
			{
				$return_array["$cat[cat_id]"] = $cat;
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
	function get_phpbb2_forum_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'forum_id'		=> 'mandatory',
			'forum_name'	=> 'mandatory',
			'forum_order'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "forums", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$forums = $Db_object->query("SELECT * FROM {$tableprefix}forums ORDER BY forum_id LIMIT {$start}, {$per_page}");

			while ($forum = $Db_object->fetch_array($forums))
			{
				$return_array["$forum[forum_id]"] = $forum;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}forums");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	forum_id,
							cat_id,
							forum_name,
							forum_order,
							forum_id,
							forum_desc
					FROM {$tableprefix}forums WHERE forum_id
						IN(SELECT TOP {$per_page} forum_id
							FROM (SELECT TOP {$internal} forum_id FROM {$tableprefix}forums ORDER BY forum_id)
						A ORDER BY forum_id DESC)
					ORDER BY forum_id";

			$forum_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forum_list))
			{
				$return_array["$forum[forum_id]"] = $forum;
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
	function get_phpbb2_threads_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'topic_title'		=> 'mandatory',
			'forum_id'	=> 'mandatory',
			'topic_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "topics", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$threads = $Db_object->query("SELECT * FROM {$tableprefix}topics WHERE topic_moved_id=0 ORDER BY topic_id LIMIT {$start},{$per_page}");

			while ($thread = $Db_object->fetch_array($threads))
			{
				$return_array[$thread['topic_id']] = $thread;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}topics");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	topic_id,
							topic_title,
							forum_id,
							topic_id,
							forum_id,
							topic_first_post_id,
							topic_last_post_id,
							topic_replies,
							topic_poster,
							topic_poster,
							topic_time,
							topic_views
					FROM {$tableprefix}topics WHERE topic_id
						IN(SELECT TOP {$per_page} topic_id
							FROM (SELECT TOP {$internal} topic_id FROM {$tableprefix}topics ORDER BY topic_id)
						A ORDER BY topic_id DESC)
					ORDER BY topic_id";

			$forum_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forum_list))
			{
				$return_array["$forum[topic_id]"] = $forum;
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
	* Returns the smilie details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_phpbb2_smilie_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		$req_fields = array('code' => 'mandatory');

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "smilies", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$smilies = $Db_object->query("SELECT * FROM {$tableprefix}smilies");

			while ($smilie = $Db_object->fetch_array($smilies))
			{
				$return_array["$smilie[smilies_id]"] = $smilie;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	smilies_id,
							code,
							smile_url,
							emoticon
					FROM {$tableprefix}smilies
					";

			$smilies = $Db_object->query($sql);

			while ($smilie = $Db_object->fetch_array($smilies))
			{
				$return_array["$smilie[smilies_id]"] = $smilie;
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
	* Returns the post details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_phpbb2_posts_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page) OR !is_numeric($start)) { return $return_array; }

		$req_fields = array(
			'post_text'		=> 'mandatory',
			'post_subject'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "posts_text", $req_fields))
		{
			return $return_array;
		}

		unset($req_fields);

		// Check Mandatory Fields.
		$req_fields = array(
			'poster_id'	=> 'mandatory',
			'topic_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "posts", $req_fields))
		{
			return $return_array;
		}



		if ($databasetype == 'mysql')
		{
			$sql = "SELECT post.post_id, post.topic_id, post.forum_id, post.poster_id, post.post_time, post.poster_ip, post.post_username, post.enable_smilies, post.enable_sig,
			post_text.post_id, post_text.post_subject, post_text.post_text
			FROM {$tableprefix}posts as post
			LEFT JOIN {$tableprefix}posts_text AS post_text ON (post.post_id = post_text.post_id)
			WHERE post.post_id > {$start}
			ORDER by post.post_id
			LIMIT {$per_page}";

			$posts = $Db_object->query($sql);

			while ($post = $Db_object->fetch_array($posts))
			{
				if ($return_array['data']["$post[post_id]"])
				{
					// Dupe id
					$return_array['data'][] = $post;
				}
				else
				{
					$return_array['data']["$post[post_id]"] = $post;
				}
				$return_array['lastid'] = $post['post_id'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}posts");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	post_id,
							topic_id,
							poster_id,
							topic_id,
							post_time,
							enable_smilies,
							enable_sig,
							post_username,
							poster_ip
					FROM {$tableprefix}posts WHERE post_id
						IN(SELECT TOP {$per_page} post_id
							FROM (SELECT TOP {$internal} post_id FROM {$tableprefix}posts ORDER BY post_id)
						A ORDER BY post_id DESC)
					ORDER BY post_id";

			$posts = $Db_object->query($sql);

			while ($post = $Db_object->fetch_array($posts))
			{
				$return_array['data']["$post[post_id]"] = $post;
				$return_array['lastid'] = $post['post_id'];
			}
		}
		else
		{
			return false;
		}

		$return_array['count'] = count($return_array['data']);

		return $return_array;
	}

	/**
	* Returns the post details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			The phpBB post id
	*
	* @return	array
	*/
	function get_phpbb_post_text(&$Db_object, &$databasetype, &$tableprefix, &$post_id)
	{
		$return_array = array();

		if(!is_numeric($post_id))
		{
			return $return_array;
		}

		if ($databasetype == 'mssql')
		{
			$sql = "
			SELECT
				CAST([post_text] as TEXT) as post_text,
				post_subject
			FROM {$tableprefix}posts_text
			WHERE post_id={$post_id}
			";

			$posts_text = $Db_object->query_first($sql);

			$return_array['post_text'] 		= $posts_text['post_text'];
			$return_array['post_subject'] 	= $posts_text['post_subject'];
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	/**
	* Returns the truncated smilies
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_phpbb_truncated_smilies(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{
			$req_fields = array('code' => 'mandatory');

			if(!$this->check_table($Db_object, $databasetype, $tableprefix, "smilies", $req_fields))
			{
				return $return_array;
			}

			$smilies = $DB_object->query("SELECT code FROM {$table_prefix}smilies");

			while ($smilie = $DB_object->fetch_array($smilies))
			{
				if(strlen($smilie['code']) > 20)
				{
					 $return_array[$smilie['code']] =  substr($smilie['code'],0,19) . ':';
				}
			}
		}
		else
		{
			return false;
		}

		return $return_array;
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
	function get_phpbb2_polls_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'vote_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "vote_desc", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}vote_desc ORDER BY vote_id LIMIT {$start}, {$per_page}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_id]"] = $poll;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}vote_desc");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	vote_id,
							topic_id,
							vote_text,
							vote_start,
							vote_length
					FROM {$tableprefix}vote_desc WHERE vote_id
						IN(SELECT TOP {$per_page} vote_id
							FROM (SELECT TOP {$internal} vote_id FROM {$tableprefix}vote_desc ORDER BY vote_id)
						A ORDER BY vote_id DESC)
					ORDER BY vote_id";

			$polls = $Db_object->query($sql);

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_id]"] = $poll;
			}
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
	* @param	int		mixed			The phpBB post id
	*
	* @return	array
	*/
	function get_phpbb2_poll_results_details(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();

		$req_fields = array(
			'vote_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "vote_results", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}vote_results WHERE vote_id ={$poll_id}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_option_id]"] = $poll;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}vote_results");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	vote_id,
							vote_option_id,
							vote_option_text,
							vote_result
					FROM {$tableprefix}vote_results WHERE vote_id =" .	$poll_id;

			$polls = $Db_object->query($sql);

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_id]"] = $poll;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the poll voter details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			The phpBB post id
	*
	* @return	array
	*/
	function get_phpbb2_vote_voters(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();

		$req_fields = array(
			'vote_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "vote_voters", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}vote_voters WHERE vote_id={$poll_id}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_user_id]"] = 0;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT	vote_id,
							vote_user_id,
							vote_user_ip
					FROM {$tableprefix}vote_voters
					";

			$polls = $Db_object->query($sql);

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_user_id]"] = 0;
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
	function get_phpbb2_pm_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		/*
		define('PRIVMSGS_READ_MAIL', 0);
		define('PRIVMSGS_NEW_MAIL', 1);
		define('PRIVMSGS_SENT_MAIL', 2);
		define('PRIVMSGS_SAVED_IN_MAIL', 3);
		define('PRIVMSGS_SAVED_OUT_MAIL', 4);
		define('PRIVMSGS_UNREAD_MAIL', 5);
		*/

		$req_fields = array(
			'privmsgs_type'	=> 'mandatory',
			'privmsgs_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "privmsgs", $req_fields))
		{
			return $return_array;
		}


		if ($databasetype == 'mysql')
		{
			$pms = $Db_object->query("SELECT * FROM {$tableprefix}privmsgs WHERE privmsgs_type IN (0,1,3,4,5) ORDER BY privmsgs_id LIMIT {$start}, {$per_page}");

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array["$pm[privmsgs_id]"] = $pm;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}privmsgs");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	privmsgs_id,
							privmsgs_to_userid,
							privmsgs_from_userid,
							privmsgs_subject,
							privmsgs_date,
							privmsgs_attach_sig,
							privmsgs_enable_smilies,
							privmsgs_type
					FROM {$tableprefix}privmsgs WHERE privmsgs_id
						IN(SELECT TOP {$per_page} privmsgs_id
							FROM (SELECT TOP {$internal} privmsgs_id FROM {$tableprefix}privmsgs ORDER BY privmsgs_id)
						A ORDER BY privmsgs_id DESC)
					ORDER BY privmsgs_id";

			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array["$pm[privmsgs_id]"] = $pm;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the pm text
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			pm id
	*
	* @return	array
	*/
	function get_phpbb2_pm_text(&$Db_object, &$databasetype, &$tableprefix, &$pm_id)
	{
		if ($databasetype == 'mysql' OR $databasetype == 'mssql')
		{
			$req_fields = array('privmsgs_text' => 'mandatory');

			$this->check_table($Db_object, $databasetype, $tableprefix, "privmsgs_text", $req_fields);

			$pms = $Db_object->query_first("SELECT privmsgs_text FROM {$tableprefix}privmsgs_text WHERE privmsgs_text_id={$pm_id}");

			return $pms;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the rank details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_phpbb2_ranks_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'rank_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "ranks", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$ranks = $Db_object->query("SELECT * FROM {$tableprefix}ranks ORDER BY rank_id LIMIT {$start}, {$per_page}");

			while ($rank = $Db_object->fetch_array($ranks))
			{
				$return_array["$rank[rank_id]"] = $rank;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}ranks");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	rank_id,
							rank_title,
							rank_min,
							rank_special,
							rank_image
					FROM {$tableprefix}ranks WHERE rank_id
						IN(SELECT TOP {$per_page} rank_id
							FROM (SELECT TOP {$internal} rank_id FROM {$tableprefix}ranks ORDER BY rank_id)
						A ORDER BY rank_id DESC)
					ORDER BY rank_id";

			$ranks = $Db_object->query($sql);

			while ($rank = $Db_object->fetch_array($ranks))
			{
				$return_array["$pm[rank_id]"] = $rank;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function reverse_ip($ip)
	{
		$hexy_thing = explode('.', chunk_split($ip, 2, '.'));
		return hexdec($hexy_thing[0]). '.' . hexdec($hexy_thing[1]) . '.' . hexdec($hexy_thing[2]) . '.' . hexdec($hexy_thing[3]);
	}

	function get_phpBB2_attachment_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'attach_id'		=> 'mandatory',
			'physical_filename'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, 'attachments_desc', $req_fields))
		{
			return $return_array;
		}

		unset($req_fields);

		$req_fields = array(
			'user_id_1'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, 'attachments', $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details = $Db_object->query("SELECT * FROM {$tableprefix}attachments_desc ORDER BY attach_id LIMIT {$start}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details))
			{
				$file_info = $Db_object->query_first("SELECT * FROM " . $tableprefix . "attachments WHERE attach_id=" . $detail['attach_id']);

				$return_array["$detail[attach_id]"]['postid'] 		= $file_info['post_id'];
				$return_array["$detail[attach_id]"]['userid'] 		= $file_info['user_id_1'];
				$return_array["$detail[attach_id]"]['filename'] 	= $detail['physical_filename'];
				$return_array["$detail[attach_id]"]['downloads']	= $detail['download_count'];
				$return_array["$detail[attach_id]"]['filesize']		= $detail['filesize'];
				$return_array["$detail[attach_id]"]['filetime']		= $detail['filetime'];

			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}attachments_desc");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	attach_id,
							userid,
							filetime,
							downloads,
							filesize,
							filename,
							attach_pid,
							postid
					FROM {$tableprefix}attachments_desc WHERE attach_id
						IN(SELECT TOP {$per_page} attach_id
							FROM (SELECT TOP {$internal} attach_id FROM {$tableprefix}attachments_desc ORDER BY attach_id)
						A ORDER BY attach_id DESC)
					ORDER BY attach_id";

			$details = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details))
			{
				$file_info = $Db_object->query_first("SELECT * FROM {$tableprefix}attachments WHERE attach_id=" . $detail['attach_id']);

				$return_array["$detail[attach_id]"]['postid'] 		= $file_info['post_id'];
				$return_array["$detail[attach_id]"]['userid'] 		= $file_info['user_id_1'];
				$return_array["$detail[attach_id]"]['filename'] 	= $detail['physical_filename'];
				$return_array["$detail[attach_id]"]['downloads']	= $detail['download_count'];
				$return_array["$detail[attach_id]"]['filesize']		= $detail['filesize'];
				$return_array["$detail[attach_id]"]['filetime']		= $detail['filetime'];

			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_out_of_sync_parentids(&$Db_object, &$databasetype, &$tableprefix)
	{
		if ($databasetype == 'mysql')
		{
			$cat_ids = $this->get_category_ids($Db_object, $databasetype, $tableprefix);

			$Db_object->query_first("UPDATE {$tableprefix}forum SET parentid=" . $cat_ids[99999] . "  WHERE parentid=0");
		}
		else
		{
			return false;
		}
	}

}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile: 000.php,v $ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
