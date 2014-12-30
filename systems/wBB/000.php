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
* wBB_000
*
* @package 		ImpEx.wBB
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '2.3.3';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'WoltLab Burning Board';
	var $_homepage 	= 'http://www.woltlab.de';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'access','acpmenuitemgroups','acpmenuitemgroupscount','acpmenuitems','acpmenuitemscount',
		'adminsessions','announcements','applications','attachments','avatars','bbcodes','boards',
		'boardvisit','designelements','designpacks','events','folders','gal_cats','gal_main',
		'groupcombinations','groupleaders','groups','groupvalues','groupvariablegroups','groupvariables',
		'icons','languagecats','languagepacks','languages','links_cats','links_entries','links_options',
		'moderators','optiongroups','options','permissions','polloptions','polls','posts','privatemessage',
		'profilefields','ranks','searchs','sessions','smilies','stats','styles','subscribeboards',
		'subscribethreads','templatepacks','templates','threads','threadvisit','user2groups','userfields',
		'users','votes','wlw','wordlist','wordmatch', 'mailqueue', 'mails', 'postcache',
		'privatemessagereceipts', 'register_keys'
	);


	function wBB_000()
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
	function wbb_html($text)
	{
		// Nfo
		$text = str_replace('[nfo]', '[code]', $text);
		$text = str_replace('[/nfo]', '[/code]', $text);

		// Text size
		$text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);

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
	function get_wBB_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT userid,username FROM {$tableprefix}users LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
					$tempArray = array($user['userid'] => $user['username']);
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
	function get_wBB2_user_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$users = $DB_object->query("SELECT * FROM {$table_prefix}users ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($user = $DB_object->fetch_array($users))
			{
				$return_array["$user[userid]"] = $user;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	/**
	* Returns the user groups details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_wBB_user_group_details(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();

		if ($database_type == 'mysql')
		{
			$groups = $DB_object->query("SELECT * FROM {$table_prefix}groups ORDER BY groupid");

			while ($group = $DB_object->fetch_array($groups))
			{
				$return_array["$group[groupid]"] = $group;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	/**
	* Returns the user group permissions array for the forums
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_wBB_user_group_permissions(&$DB_object, &$database_type, &$table_prefix, $group_id)
	{
		$return_array = array();

		if(empty($group_id)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			// TODO: only gets the first one at the moment
			$groups = $DB_object->query("SELECT * FROM {$table_prefix}permissions WHERE groupid={$group_id} ORDER BY boardid LIMIT 0,1");

			while ($group = $DB_object->fetch_array($groups))
			{
				$return_array["$group[groupid]"] = $group;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	/**
	* Returns the user group permissions array for the forums
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_wBB_user_fields(&$Db_target, &$target_database_type, &$target_table_prefix, &$user_id)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$userfields = $DB_object->query("SELECT * FROM {$table_prefix}userfields WHERE userid={$user_id}");

			while ($userfield = $DB_object->fetch_array($userfields))
			{
				$return_array["$userfield[profilefieldid]"] = $userfield;
			}
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
	function get_list(&$Db_object, &$database_type, &$table_prefix, $type)
	{
		$return_array = array();

		if(empty($type)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			if ( $type == 'userid')
			{
				return $return_array;
			}
			else if ($type == 'ip')
			{
				$sql = "SELECT value FROM {$table_prefix}options WHERE varname='ban_ip'";
			}
			else if ($type == 'email')
			{
				$sql = "SELECT value FROM {$table_prefix}options WHERE varname='ban_email'";
			}
			else if ($type == 'namebansfull')
			{
				$sql = "SELECT value FROM {$table_prefix}options WHERE varname='ban_name'";
			}

			$ban_list = $Db_object->query_first($sql);
			$return_array = explode(" ",$ban_list[0]);
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
		if(empty($type)) { return $return_array; }

		$result = $this->import_ban_list($Db_target,
										$sessionobject->get_session_var('targetdatabasetype'),
										$sessionobject->get_session_var('targettableprefix'),
										$list,
										$type);

		if (!$result)
		{
			$sessionobject->add_session_var($class_num . '_objects_faild',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
			$sessionobject->add_error('warning', $this->_modulestring,
						$class_num . "::import_ban_list failed on $type - $list",
						 "Check for format of the $type $list");
			$displayobject->update_html("<b>There was an error with the import of the $type ban list.</b>");
		}
		else
		{
			$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
			$displayobject->update_html("<br /><b>$type</b> Ban list <i>imported.</i>");
		}
	}


	/**
	* Returns the sytles array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_wBB_styles(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();

		if ($database_type == 'mysql')
		{
			$designpacks = $DB_object->query("SELECT * FROM {$table_prefix}designpacks");

			while ($designpack = $DB_object->fetch_array($designpacks))
			{
				$sql = "SELECT * FROM {$table_prefix}designelements	WHERE designpackid=" . $designpack['designpackid'];

				while ($designelement = $DB_object->fetch_array($designelements))
				{
					$return_array["$designpack[designpackname]"]["$designelement[element]"] = $designelement['value'];
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
	* Returns the boards details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_wBB_categories_details(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();

		if ($database_type == 'mysql')
		{
			$boards = $DB_object->query("SELECT * FROM {$table_prefix}boards	WHERE isboard = 0 ORDER BY boardid");

			while ($board = $DB_object->fetch_array($boards))
			{
				$return_array["$board[boardid]"] = $board;
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
	function get_wBB_forum_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$forums = $DB_object->query("SELECT * FROM {$table_prefix}boards WHERE isboard != 0 ORDER BY boardid LIMIT {$start_at}, {$per_page}");

			while ($forum = $DB_object->fetch_array($forums))
			{
				$return_array["$forum[boardid]"] = $forum;
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
	function get_wBB_threads_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$threads = $DB_object->query("SELECT * FROM {$table_prefix}threads ORDER BY threadid LIMIT {$start_at}, {$per_page}");

			while ($thread = $DB_object->fetch_array($threads))
			{
				$return_array["$thread[threadid]"] = $thread;
			}
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
	function get_wBB_smilie_details(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();

		if ($database_type == 'mysql')
		{
			$smilies = $DB_object->query("SELECT * FROM {$table_prefix}smilies");

			while ($smilie = $DB_object->fetch_array($smilies))
			{
				$return_array["$smilie[smilieid]"] = $smilie;
			}
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
	function get_wBB_posts_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$posts = $DB_object->query("SELECT * FROM {$table_prefix}posts ORDER BY postid LIMIT {$start_at}, {$per_page}");

			while ($post = $DB_object->fetch_array($posts))
			{
				$return_array["$post[postid]"] = $post;
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
	function get_wBB_polls_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			// TODO: WTF is this here for ?
			$poll_start_at = $this->iif($poll_start_at == '','0',$poll_start_at);

			$polls = $DB_object->query("SELECT * FROM {$table_prefix}polls ORDER BY pollid LIMIT {$start_at}, {$per_page}");

			while ($poll = $DB_object->fetch_array($polls))
			{
				$return_array["$poll[pollid]"] = $poll;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the poll questions as an array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_wBB_polls_questions(&$DB_object, &$database_type, &$table_prefix, &$poll_id)
	{
		$return_array = array();

		if(empty($poll_id)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$polls = $DB_object->query("SELECT * FROM {$table_prefix}polloptions WHERE pollid={$poll_id}");

			while ($poll = $DB_object->fetch_array($polls))
			{
				$return_array["$poll[polloptionid]"] = $poll;
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
	function get_wBB_poll_results_details(&$DB_object, &$database_type, &$table_prefix, &$poll_id)
	{
		$return_array = array();

		if(empty($poll_id)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$polls = $DB_object->query("SELECT * FROM {$table_prefix}votes WHERE id={$poll_id}");

			while ($poll = $DB_object->fetch_array($polls))
			{
				$return_array[] = $poll;
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
	function get_wBB_pm_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$pms = $DB_object->query("SELECT * FROM {$table_prefix}privatemessage ORDER BY privatemessageid LIMIT {$start_at}, {$per_page}");

			while ($pm = $DB_object->fetch_array($pms))
			{
				$return_array["$pm[privatemessageid]"] = $pm;
			}
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
	function get_wBB_ranks_details(&$DB_object, &$database_type, &$table_prefix, &$start_at, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$ranks = $DB_object->query("SELECT * FROM {$table_prefix}ranks	ORDER BY rankid	LIMIT {$start_at}, {$per_page}");

			while ($rank = $DB_object->fetch_array($ranks))
			{
				$return_array["$rank[rankid]"] = $rank;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the wBB moderators details
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_wBB_moderators_details($DB_object, $database_type, $table_prefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$moderators = $DB_object->query("SELECT * FROM {$table_prefix}moderators ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($mod = $DB_object->fetch_array($moderators))
			{
				$return_array[] = $mod;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_wBB_attachment_details($DB_object, $database_type, $table_prefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$attachemnts = $DB_object->query("SELECT * FROM {$table_prefix}attachments ORDER BY attachmentid LIMIT {$start_at}, {$per_page}");

			while ($attach = $DB_object->fetch_array($attachemnts))
			{
				$return_array["$attach[attachmentid]"] = $attach;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_wbb_user_groups($DB_object, $database_type, $table_prefix, $user_id)
	{
		$return_array = array();

		if($this->check_table($Db_object, $databasetype, $tableprefix, 'user2groups'))
		{
			return $return_array;
		}

		if(empty($user_id)) { return $return_array; }

		if ($database_type == 'mysql')
		{
			$groups = $DB_object->query("SELECT groupid FROM {$table_prefix}user2groups WHERE userid={$user_id}");

			while ($group = $DB_object->fetch_array($groups))
			{
				$return_array[] = $group['groupid'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}
}
/*======================================================================*/
?>
