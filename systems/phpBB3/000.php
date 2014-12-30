<?php
if (!defined('IDIR')) { die; }
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
* phpBB3
*
* @package 		ImpEx.phpBB3
* * @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB3_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '3.0.3';
	var $_tested_versions = array();

	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'phpBB3';
	var $_homepage 	= 'http://www.phpbb.com/';
	var $_tier = '1';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'acl_groups', 'acl_options', 'acl_roles', 'acl_roles_data', 'acl_users', 'attachments', 'banlist', 'bbcodes',
		'bookmarks', 'bots', 'config', 'confirm', 'disallow', 'drafts', 'extension_groups', 'extensions', 'forums',
		'forums_access', 'forums_track', 'forums_watch', 'groups', 'icons', 'lang', 'log', 'moderator_cache',
		'modules', 'poll_options', 'poll_votes', 'posts', 'privmsgs', 'privmsgs_folder', 'privmsgs_rules', 'privmsgs_to',
		'profile_fields', 'profile_fields_data', 'profile_fields_lang', 'profile_lang', 'ranks', 'reports', 'reports_reasons',
		'search_results', 'search_wordlist', 'search_wordmatch', 'sessions', 'sessions_keys', 'sitelist', 'smilies',
		'styles', 'styles_imageset', 'styles_template', 'styles_template_data', 'styles_theme', 'topics', 'topics_posted',
		'topics_track', 'topics_watch', 'user_group', 'users', 'warnings', 'words', 'zebra'
	);

	function phpBB3_000()
	{
	}

	function phpbb3_html($text, $truncate_smilies = false)
	{
		$text = html_entity_decode($text);
		
		// Quotes
		// With name
		for($i=0;$i<4;$i++)
		{
			$text = preg_replace('#\[quote="([a-z0-9]+)":(.*)\](.*)\[/quote:\\2\]#siU', '[quote=$1]$3[/quote]', $text);
		}

		
		
		// Without
		$text = preg_replace('#\[quote="([a-z0-9]+)"\](.*)\[/quote\]#siU', '[quote]$2[/quote]', $text);

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
		$text = preg_replace('#<!-- s(.*) --><img src="{SMILIES_PATH}/(.*) /><!-- s\\1 -->#siU', '$1', $text);

		$text = preg_replace('#\[quote="(.*)":([a-z0-9]+)\](.*)\[/quote:\\2\]#siU', '[quote=$1]$3[/quote]', $text);
		$text = preg_replace('#\[quote:([a-z0-9]+)\](.*)\[/quote:\\1\]#siU', '[quote]$2[/quote]', $text);
		#[url=http://www.mediafire.com/?dlchvdq0aop:0ddc4]AAAA[/url:0ddc4]
		$text = preg_replace('#\[url=(.*):([a-z0-9]+)\](.*)\[/url=\\2\]#siU', '[url=$1]$3[/url]', $text);
		$text = preg_replace('#\[url:([a-z0-9]+)\](.*)\[/url:\\1\]#siU', '[url]$2[/url]', $text);
		$text = preg_replace('#\[url=(.*):([a-z0-9]+)\](.*)\[/url:\\2\]#siU', '[url=$1]$3[/url]', $text);
		$text = preg_replace('#\[url=(.*);([a-z0-9]+)\](.*)\[/url;\\2\]#siU', '[url=$1]$3[/url]', $text);
		
		// embed 
		$text = preg_replace('#\[embed:([a-z0-9]+)\](.*)\[/embed:\\1\]#siU', '[embed]$2[/embed]', $text);
		
		$text = preg_replace('#\[code:([a-z0-9]+)\](.*)\[/code:\\1\]#siU', '[code]$2[/code]', $text);

		if($truncate_smilies)
		{
			$text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
		}

		// html_entity_decode error in IMG ?
		#$text = str_replace(':', '&#58;', $text);
		#$text = str_replace('.', '&#46;', $text);

		if (is_array($truncate_smilies))
		{
			$text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
		}

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
	function get_phpBB3_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT user_id,username FROM {$tableprefix}users ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[user_id]"] = $row['username'];
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
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
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_phpbb_truncated_smilies($DB_object, $database_type, $table_prefix)
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
			$return_array = array();
		}

		return $return_array;
	}

	function get_phpbb3_smilie_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		$req_fields = array('code' => 'mandatory');

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "smilies", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql' OR $databasetype == 'mssql' OR 'odbc')
		{

			$smilies = $Db_object->query("SELECT smiley_id, emotion, code, smiley_url FROM {$tableprefix}smilies");

			while ($smilie = $Db_object->fetch_array($smilies))
			{
				$return_array["$smilie[smiley_id]"] = $smilie;
			}
		}

		return $return_array;
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
	function get_phpbb3_categories_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql' OR $databasetype == 'mssql' OR 'odbc')
		{
			$cats = $Db_object->query("SELECT forum_id, forum_name, forum_desc FROM {$tableprefix}forums WHERE parent_id = 0");

			while ($cat = $Db_object->fetch_array($cats))
			{
				$return_array["$cat[forum_id]"] = $cat;
			}
		}

		return $return_array;
	}

	function get_phpbb3_forum_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if ($databasetype == 'mysql'OR $databasetype == 'mssql' OR 'odbc')
		{
			$forums= $Db_object->query("SELECT forum_id, forum_name, forum_desc, parent_id FROM {$tableprefix}forums WHERE parent_id != 0 ORDER BY forum_id");

			while ($forum = $Db_object->fetch_array($forums))
			{
				$return_array['data']["$forum[forum_id]"] = $forum;
			}

			$return_array['count'] = count($return_array['data']);
		}

		return $return_array;
	}


	function get_phpbb3_pms($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if(empty($start_at)) { $start_at = 0; }

		// Check that there isn't a empty value
		if ($databasetype == 'mysql')
		{
			$pms= $Db_object->query("SELECT * FROM {$tableprefix}privmsgs ORDER BY msg_id LIMIT {$start_at}, {$per_page}");

			while ($pm = $Db_object->fetch_array($pms))
			{
				// No unique
				$return_array['data'][] = $pm;
			}
		}
		elseif($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}privmsgs_to");

			$internal 	= $start + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	msg_id,
							author_id,
							user_id,
							folder_id
					FROM {$tableprefix}privmsgs_to WHERE msg_id
						IN(SELECT TOP {$per_page} msg_id
							FROM (SELECT TOP {$internal} msg_id FROM {$tableprefix}privmsgs_to ORDER BY msg_id)
						A ORDER BY msg_id DESC)
					ORDER BY msg_id";

			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array['data']["$pm[msg_id]"] = $pm;
				$return_array['lastid'] = $pm['msg_id'];
			}
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_pm_text($Db_object, $databasetype, $tableprefix, $id)
	{
		$return_array = array();

		if(empty($id)) { return $return_array; }

		// Check that there isn't a empty value
		if ($databasetype == 'mysql')
		{
			return  $Db_object->query_first("SELECT * FROM {$tableprefix}privmsgs WHERE msg_id={$id}");
		}
		elseif ($databasetype == 'mssql' OR 'odbc')
		{
			$sql = "SELECT message_subject, message_text, msg_id, author_id, message_time, enable_sig, enable_smilies
					FROM {$tableprefix}privmsgs
					WHERE msg_id={$id}";

			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				return $pm;

			}
		}
	}

	function get_pm_text_id($Db_object, $databasetype, $tableprefix, $id)
	{
		// Check that there isn't a empty value
		if ($databasetype == 'mysql')
		{
			$id = $Db_object->query_first("SELECT pmtextid FROM {$tableprefix}pmtext WHERE importpmid={$id}");
			return $id['pmtextid'];
		}

		return false;
	}

	function get_pm_to_id($Db_object, $databasetype, $tableprefix, $id)
	{
		// Check that there isn't a empty value
		if ($databasetype == 'mysql')
		{
			$id = $Db_object->query_first("SELECT user_id FROM {$tableprefix}privmsgs_to WHERE msg_id={$id}");
			return $id['user_id'];
		}

		return false;
	}

	function get_phpbb3_polls_details($Db_object, $databasetype, $tableprefix, $source_thread_id)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($source_thread_id)) { return $return_array; }

		$req_fields = array(
			'poll_option_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "poll_options", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}poll_options WHERE topic_id={$source_thread_id}");

			$i=0;

			while ($poll = $Db_object->fetch_array($polls))
			{
				$text .= $poll['poll_option_text'] . '|||';
				$option .= $poll['poll_option_total'] . '|||';
				$votes = $votes +  $poll['poll_option_total'];
				$i++;
			}

			$return_array['options']  = substr($text, 0, -3);
			$return_array['votes']  = substr($option, 0, -3);
			$return_array['numberoptions']  = $i;
			$return_array['totalvotes']  = $votes;
		}

		return $return_array;
	}

	function get_phpbb3_poll_voters($Db_object, $databasetype, $tableprefix, $source_thread_id)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($source_thread_id)) { return $return_array; }

		$req_fields = array(
			'topic_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "poll_votes", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}poll_votes WHERE topic_id={$source_thread_id}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_user_id]"]  = $poll['poll_option_id'];
			}
		}

		return $return_array;
	}

	function get_phpBB3_mods($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT forum_id, user_id, username FROM {$tableprefix}moderator_cache ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][] = $row;
				$return_array['lastid'] = $row['user_id'];
			}
		}
		elseif ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}moderator_cache");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT  user_id,
							forum_id
					FROM {$tableprefix}moderator_cache WHERE user_id
						IN(SELECT TOP {$per_page} user_id
							FROM (SELECT TOP {$internal} user_id FROM {$tableprefix}moderator_cache ORDER BY user_id)
						A ORDER BY user_id DESC)
					ORDER BY user_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data'][] = $details;
				$return_array['lastid'] = $details['user_id'];
			}
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	////
	// MSSQL add
	////


	function get_phpbb3_usergroups($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

 		if ($databasetype == 'mssql' OR 'odbc')
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
							group_desc
					FROM {$tableprefix}groups WHERE group_id
						IN(SELECT TOP {$per_page} group_id
							FROM (SELECT TOP {$internal} group_id FROM {$tableprefix}groups ORDER BY group_id)
						A ORDER BY group_id DESC)
					ORDER BY group_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[group_id]"] = $details;
				$return_array['lastid'] = $details['group_id'];
			}
		}
		else
		{
			return false;
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_phpbb3_usergroupids(&$Db_object, &$databasetype, &$tableprefix, $user_id)
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

	function get_phpbb3_users($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

 		if ($databasetype == 'mssql' OR 'odbc')
		{

			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT  user_id,
							group_id,
							username,
							user_email,
							user_password,
							user_regdate,
							user_ip,
							user_lastvisit,
							user_lastvisit,
							user_birthday,
							user_posts,
							user_msnm,
							user_yim,
							user_aim,
							user_icq,
							user_website,
							user_occ,
							user_from,
							user_interests,
							user_avatar
					FROM {$tableprefix}users WHERE user_id
						IN(SELECT TOP {$per_page} user_id
							FROM (SELECT TOP {$internal} user_id FROM {$tableprefix}users ORDER BY user_id)
						A ORDER BY user_id DESC)
					ORDER BY user_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[user_id]"] = $details;
				$return_array['lastid'] = $details['user_id'];
			}
		}
		else
		{
			return false;
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_phpbb3_threads($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

 		if ($databasetype == 'mssql' OR 'odbc')
		{

			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}topics");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT  topic_id,
							topic_title,
							forum_id,
							topic_status,
							topic_type,
							topic_replies,
							topic_first_poster_name,
							topic_poster,
							topic_views
					FROM {$tableprefix}topics WHERE topic_id
						IN(SELECT TOP {$per_page} topic_id
							FROM (SELECT TOP {$internal} topic_id FROM {$tableprefix}topics ORDER BY topic_id)
						A ORDER BY topic_id DESC)
					ORDER BY topic_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[topic_id]"] = $details;
				$return_array['lastid'] = $details['topic_id'];
			}
		}
		else
		{
			return false;
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_phpbb3_posts($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

 		if ($databasetype == 'mssql' OR 'odbc')
		{

			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}posts");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT  post_id,
							topic_id,
							poster_id,
							post_approved,
							icon_id,
							poster_ip,
							enable_smilies,
							post_subject,
							post_text,
							post_time
					FROM {$tableprefix}posts WHERE post_id
						IN(SELECT TOP {$per_page} post_id
							FROM (SELECT TOP {$internal} post_id FROM {$tableprefix}posts ORDER BY post_id)
						A ORDER BY post_id DESC)
					ORDER BY post_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[post_id]"] = $details;
				$return_array['lastid'] = $details['post_id'];
			}
		}
		else
		{
			return false;
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}


	function get_phpbb3_attach($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

 		if ($databasetype == 'mssql' OR 'odbc')
		{

			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}attachments");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT  attach_id,
							physical_filename,
							real_filename,
							poster_id,
							filetime,
							download_count,
							filesize,
							post_msg_id
					FROM {$tableprefix}attachments WHERE attach_id
						IN(SELECT TOP {$per_page} attach_id
							FROM (SELECT TOP {$internal} attach_id FROM {$tableprefix}attachments ORDER BY attach_id)
						A ORDER BY attach_id DESC)
					ORDER BY attach_id";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[attach_id]"] = $details;
				$return_array['lastid'] = $details['attach_id'];
			}
		}
		else
		{
			return $return_array;
		}

		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function parent_id_update($Db_object, $databasetype, $tableprefix)
	{
		if ($databasetype == 'mysql')
		{
			$cat_ids = $this->get_category_ids($Db_object, $databasetype, $tableprefix);
			$forum_ids = $this->get_forum_ids($Db_object, $databasetype, $tableprefix);
			
			$old_ids = $Db_object->query("SELECT forumid, description FROM {$tableprefix}forum WHERE importforumid <> 0");
			
			while ($details = $Db_object->fetch_array($old_ids))
			{
				if (strpos($details['description'], '||X|X||'))
				{	
					$bits 	= explode('||X|X||', $details['description']);
					$new_id = $cat_ids[99999];
					$old_id = intval($bits[0]);
					
					if ($forum_ids[$old_id])
					{
						$new_id = $forum_ids[$old_id];
					}
					elseif ($cat_ids[$old_id])
					{
						$new_id = $cat_ids[$old_id];
					}

					$Db_object->query("UPDATE {$tableprefix}forum 
					SET description = '" . addslashes($bits[1]) . "',
					parentid = " . intval($new_id) . "
					WHERE 
					forumid = " . intval($details['forumid']));
				}		
			}
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
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
