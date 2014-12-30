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
* smf API module
*
* @package			ImpEx.smf
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class smf_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.9';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Simple Machines Forum';
	var $_homepage 	= 'http://www.simplemachines.org/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'attachments', 'board_permissions', 'boards', 'calendar', 'calendar_holidays', 'categories',
		'collapsed_categories', 'log_actions', 'log_activity', 'log_banned', 'log_boards', 'log_errors',
		'log_floodcontrol', 'log_karma', 'log_mark_read', 'log_notify', 'log_online', 'log_polls',
		'log_topics', 'membergroups', 'members', 'messages', 'moderators', 'permissions', 'poll_choices',
		'polls', 'settings', 'smileys', 'themes', 'topics', 'sessions'
	);


	function smf_000()
	{
	}

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

	function smf_html($text)
	{
		// Text size
		$text = preg_replace('#\[size=([0-9]+)pt\](.*)\[/size]#siUe', "\$this->pixel_size_mapping('\\1', '\\2')", $text);

		// Glow
		$text = preg_replace('#\[glow=(.*),(.*)\](.*)\[/glow\]#siU', '[color=$1]$3[/color]', $text);

		// Quotes
		$text = preg_replace('#\[quote author=(.*)link(.*)\]#siU', '[quote=$1]', $text);
		$text = preg_replace('#\[quote author=(.*)\]#siU', '[quote=$1]', $text);

		// ftp to url
		$text = preg_replace('#\[ftp=(.*)\](.*)\[/ftp\]#siU', '[url=$1]$2[/url]', $text);

		// Flash removal
		$text = preg_replace('#\[flash=(.*)\](.*)\[/flash\]#siU', '$2', $text);

		// Remove all table data
		$text = str_replace('[table]', 	'', $text);
		$text = str_replace('[tr]', 	'', $text);
		$text = str_replace('[td]', 	'', $text);
		$text = str_replace('[/table]', 	'', $text);
		$text = str_replace('[/tr]', 	'', $text);
		$text = str_replace('[/td]', 	'', $text);

		// Shadow removal
		$text = preg_replace('#\[shadow=(.*)\](.*)\[/shadow\]#siU', '$2', $text);

		$text = str_replace('[pre]', 	'[code]', $text);
		$text = str_replace('[/pre]', 	'[/code]', $text);

		$text = str_replace('[move]', 	'', $text);
		$text = str_replace('[/move]', 	'', $text);
		$text = str_replace('[sup]', 	'', $text);
		$text = str_replace('[/sup]', 	'', $text);
		$text = str_replace('[sub]', 	'', $text);
		$text = str_replace('[/sub]', 	'', $text);
		$text = str_replace('[tt]', 	'', $text);
		$text = str_replace('[/tt]', 	'', $text);
		$text = str_replace('[s]', 		'', $text);
		$text = str_replace('[/s]', 	'', $text);
		$text = str_replace('[hr]', 	'_________________________________________________', $text);

		$text = str_replace('[li]', 	'[*]', $text);
		$text = str_replace('[/li]', 	'', $text);


		$text = str_replace('&gt;', 	'>', $text);
		$text = str_replace('&lt;', 	'<', $text);
		$text = str_replace('&quot;', 	'"', $text);
		$text = str_replace('&#039;', 	"'", $text);
		$text = str_replace('&amp;', 	'&', $text);

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
	function get_smf_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_MEMBER'		=> 'mandatory',
			'memberName'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "members", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT ID_MEMBER, memberName FROM {$tableprefix}members ORDER BY ID_MEMBER LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$tempArray = array($user['ID_MEMBER'] => $user['memberName']);
				$return_array = $return_array + $tempArray;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}


	function get_smf_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		$req_fields = array(
			'name'		=> 'mandatory',
			'catOrder'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "categories", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}categories ORDER BY ID_CAT");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_CAT]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_smf_topic_subject(&$Db_object, &$databasetype, &$tableprefix, &$msg_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($msg_id)) { return $return_array; }

		$req_fields = array(
			'subject'		=> 'mandatory',
			'posterTime'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "messages", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$subject = $Db_object->query_first("SELECT subject, posterTime FROM {$tableprefix}messages WHERE ID_MSG ={$msg_id}");

			return $subject;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_smf_pm_recipients(&$Db_object, &$databasetype, &$tableprefix, &$pm_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($pm_id)) { return $return_array; }

		$table = null;

		if($this->check_table($Db_object, $databasetype, $tableprefix, 'pm_recipients'))
		{
			$table = 'pm_recipients';
		}

		if($this->check_table($Db_object, $databasetype, $tableprefix, 'im_recipients'))
		{
			$table = 'im_recipients';
		}

		if (!$table)
		{
			return false;
		}

		if ($databasetype == 'mysql')
		{
			$subject = $Db_object->query_first("SELECT * FROM {$tableprefix}{$table} WHERE ID_PM= {$pm_id}");

			return $subject;
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
	function get_smf_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_BOARD'		=> 'mandatory',
			'name'			=> 'mandatory',
			'boardOrder'	=> 'mandatory',
			'ID_CAT'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "boards", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}boards ORDER BY ID_BOARD LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_BOARD]"] = $detail;
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
	function get_smf_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_BOARD'		=> 'mandatory',
			'ID_MEMBER'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "moderators", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}moderators ORDER BY ID_BOARD LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				// In case of dupes, though why not just use [] I'm not sure .... hmmm spooky
				$id = $detail['ID_BOARD'] . $detail['ID_MEMBER'];
				$return_array[$id] = $detail;
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
	function get_smf_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$table = null;

		if($this->check_table($Db_object, $databasetype, $tableprefix, 'personal_messages'))
		{
			$table = 'personal_messages';
		}

		if($this->check_table($Db_object, $databasetype, $tableprefix, 'instant_messages'))
		{
			$table = 'instant_messages';
		}

		if (!$table)
		{
			return false;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}{$table} ORDER BY ID_PM LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_PM]"] = $detail;
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
	function get_smf_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_POLL'		=> 'mandatory',
			'question'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "polls", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}polls ORDER BY ID_POLL LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_POLL]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_smf_poll_voters(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		$req_fields = array(
			'ID_POLL'		=> 'mandatory',
			'ID_MEMBER'		=> 'mandatory',
			'ID_CHOICE'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "log_polls", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}log_polls WHERE ID_POLL={$poll_id}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
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
	function get_smf_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_TOPIC'		=> 'mandatory',
			'ID_MEMBER'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "messages", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}messages ORDER BY ID_MSG LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MSG]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the smilie_id => smilie array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_smf_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'code' => 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "smileys", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}smileys ORDER BY ID_SMILEY LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_SMILEY]"] = $detail;
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
	function get_smf_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_BOARD'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "topics", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}topics ORDER BY ID_TOPIC LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$sql = "SELECT subject FROM {$tableprefix}messages WHERE ID_MSG=" . $detail['ID_FIRST_MSG'];

				$subject = $Db_object->query_first($sql);

				$return_array["$detail[ID_TOPIC]"] = $detail;
				$return_array["$detail[ID_TOPIC]"]['subject'] = $subject['subject'];
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
	function get_smf_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'ID_GROUP'		=> 'mandatory',
			'memberName'	=> 'mandatory',
			'emailAddress'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "members", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}members ORDER BY ID_MEMBER LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MEMBER]"] = $detail;
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
	function get_smf_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "membergroups"))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}membergroups ORDER BY ID_GROUP LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_GROUP]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_smf_thread_poll_info(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{

		// Check that there is not a empty value
		if(empty($poll_id)) { return false; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query_first("SELECT ID_FIRST_MSG , ID_TOPIC FROM {$tableprefix}topics WHERE ID_POLL={$poll_id}");

			if ($details_list['ID_FIRST_MSG'])
			{
				$sql = "SELECT posterTime FROM {$tableprefix}messages WHERE ID_MSG = " . $details_list['ID_FIRST_MSG'];
			}
			else
			{
				return false;
			}

			$time = $Db_object->query_first($sql);

			$details_list['dateline'] = $time['posterTime'];

			return $details_list;
		}
		else
		{
			return false;
		}
	}


	function get_smf_poll_options(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{

		// Check that there is not a empty value
		if(empty($poll_id)) { return false; }

 		$req_fields = array(
			'ID_POLL'	=> 'mandatory',
			'ID_CHOICE'	=> 'mandatory',
			'label'		=> 'mandatory',
			'votes'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "poll_choices", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}poll_choices WHERE ID_POLL={$poll_id}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_CHOICE]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_smf_012_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

 		$req_fields = array(
			'filename' => 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "attachments", $req_fields))
		{
			return $return_array;
		}
		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}attachments ORDER BY ID_ATTACH LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_ATTACH]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_name($dir, $prefix)
	{
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if (substr($file, 0, strlen($prefix)) == ($prefix))
					{
						closedir($dh);
						return $file;
					}
				}
			}
		}
		return false;
	}

	function update_out_of_order_forums(&$Db_object, &$databasetype, &$tableprefix)
	{
		$f_ids =  $this->get_forum_ids($Db_object, $databasetype, $tableprefix);

		$outof_order = $Db_object->query("SELECT forumid, parentid FROM {$tableprefix}forum WHERE parentid < 0 AND importcategoryid=0 AND importforumid > 0");

		while($old = $Db_object->fetch_array($outof_order))
		{
			// Reverse the negative
			$old['parentid'] = $old['parentid'] - ($old['parentid'] * 2);

			$Db_object->query("UPDATE {$tableprefix}forum SET parentid=" . $f_ids[$old['parentid']] . " WHERE forumid=" . $old['forumid']);
		}

	}
} // Class end
# Autogenerated on : June 24, 2004, 11:07 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
