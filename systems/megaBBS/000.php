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
* megaBBS API module
*
* @package			ImpEx.megaBBS
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class megaBBS_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.69-2.2';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'megaBBS';
	var $_homepage 	= 'http://www.pd9soft.com/megabbs-support/index.asp';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'album-tool-variables', 'albumnotifications', 'albums', 'attachments', 'bannedemails', 'bannedips', 'bbsconfiguration',
		'calendarnotifications', 'calendars', 'calendarsevents', 'calendarsmoderators', 'categories', 'colorschememoderators',
		'colorschemes', 'customranks', 'dbconfigs', 'decorations', 'emoticons', 'filebases', 'filteredwords', 'forums', 'groupmembers',
		'groups', 'massnotifications', 'mbbscode', 'memberphotos', 'members', 'messageiconid', 'messages', 'notifications', 'online',
		'permissions', 'photocomments', 'photos', 'polloptions', 'polls', 'pollvoted', 'private', 'randomquotes', 'schemes',
		'searchresults', 'sentprivate', 'signups', 'templates', 'threads', 'userlevelmembers', 'visitorhistory'
	);


	function megaBBS_000()
	{
	}


	/**
	* Parses and custom HTML for megaBBS
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function megaBBS_html($text)
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
	function get_megaBBS_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT memberid ,username
			FROM " . $tableprefix . "members
			ORDER BY memberid
			LIMIT " . $start_at . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[memberid]"] = $user['username'];
			}
			return $return_array;
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}members");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	memberid, username
					FROM {$tableprefix}members WHERE memberid
						IN(SELECT TOP {$per_page} memberid
							FROM (SELECT TOP {$internal} memberid FROM {$tableprefix}members ORDER BY memberid)
						A ORDER BY memberid DESC)
					ORDER BY memberid";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[memberid]"] = $user['username'];
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
	function get_megaBBS_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums
			ORDER BY forumid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[forumid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}forums");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	forumid,
							categoryid,
							forumname,
							URL,
							CAST([forumdescription] as TEXT) as forumdescription,
							datecreated,
							lastactivity,
							sortbyactivity,
							ShowNavbars,
							ForceUnregistered,
							anonymous,
							DefaultThreadView,
							DefaultForumView,
							ShowQuotes,
							HideEmailLink,
							ShowProfilePicture,
							sortorder,
							threadcount,
							postcount,
							disableprinter,
							sortmessagesbyactivity,
							showonline,
							showranks,
							hidereplybutton,
							lastactivethread,
							showips,
							emoticons
					FROM {$tableprefix}forums WHERE forumid
						IN(SELECT TOP {$per_page} forumid
							FROM (SELECT TOP {$internal} forumid FROM {$tableprefix}forums ORDER BY forumid)
						A ORDER BY forumid DESC)
					ORDER BY forumid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[forumid]"] = $detail;
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
	function get_megaBBS_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."sentprivate
			ORDER BY prvmessageid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[prvmessageid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}sentprivate");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	prvmessageid,
							toname,
							fromname,
							subject,
							CAST([body] as TEXT) as body,
							datesent,
							dateread
					FROM {$tableprefix}sentprivate WHERE prvmessageid
						IN(SELECT TOP {$per_page} prvmessageid
							FROM (SELECT TOP {$internal} prvmessageid FROM {$tableprefix}sentprivate ORDER BY prvmessageid)
						A ORDER BY prvmessageid DESC)
					ORDER BY prvmessageid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[prvmessageid]"] = $detail;
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
	function get_megaBBS_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."polls
			ORDER BY pollid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[pollid]"] = $detail;

				$options_list = $Db_object->query("SELECT * FROM " . $tableprefix . "polloptions WHERE pollid = " . $detail['pollid']);

				while ($option = $Db_object->fetch_array($options_list))
				{
					$return_array["$detail[pollid]"]['options'][] = $option;
				}
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}polls");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	pollid,
							unregisteredcanvote,
							unregisteredcanaddoptions,
							registeredcanaddoptions
							displaynames,
							closed,
							MultiVoting,
							hideresults
					FROM {$tableprefix}polls WHERE pollid
						IN(SELECT TOP {$per_page} pollid
							FROM (SELECT TOP {$internal} pollid FROM {$tableprefix}polls ORDER BY pollid)
						A ORDER BY pollid DESC)
					ORDER BY pollid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[pollid]"] = $detail;
				$return_array["$detail[pollid]"]['datecreated'] = time();
				$options_list = $Db_object->query("SELECT * FROM " . $tableprefix . "polloptions WHERE pollid = " . $detail['pollid']);

				while ($option = $Db_object->fetch_array($options_list))
				{
					$return_array["$detail[pollid]"]['options'][] = $option;
				}
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_poll_thread_details(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql' OR $databasetype = 'mssql')
		{
			$sql = "SELECT threadid, threadsubject, datecreated FROM " . $tableprefix . "threads WHERE pollid = " . $poll_id;

			$details_list = $Db_object->query_first($sql);

			return $details_list;
		}
		else
		{
			return false;
		}
		return false;
	}

	function get_poll_voter_details(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql' OR $databasetype = 'mssql')
		{
			$sql = "SELECT memberid FROM " . $tableprefix . "pollvoted WHERE pollid = " . $poll_id;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail['memberid'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return false;
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
	function get_megaBBS_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."messages
			ORDER BY messageid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[messageid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}messages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	messageid,
							threadid,
							inreplyto,
							subject,
							CAST([body] as TEXT) as body,
							anonymous,
							hostname,
							dateposted,
							messageicon,
							emoticons,
							replyorder,
							replylevel,
							signature,
							filterhtml,
							lastediteddate,
							edited,
							hasattachment,
							isregistered,
							memberid,
							guestname,
							forcelinebreaks
					FROM {$tableprefix}messages WHERE messageid
						IN(SELECT TOP {$per_page} messageid
							FROM (SELECT TOP {$internal} messageid FROM {$tableprefix}messages ORDER BY messageid)
						A ORDER BY messageid DESC)
					ORDER BY messageid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[messageid]"] = $detail;
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
	function get_megaBBS_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."emoticons
			ORDER BY emoticonid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[emoticonid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}emoticons");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	EmoticonID,
							source,
							emoticonimage
					FROM {$tableprefix}emoticons WHERE EmoticonID
						IN(SELECT TOP {$per_page} EmoticonID
							FROM (SELECT TOP {$internal} EmoticonID FROM {$tableprefix}emoticons ORDER BY EmoticonID)
						A ORDER BY EmoticonID DESC)
					ORDER BY EmoticonID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[EmoticonID]"] = $detail;
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
	function get_megaBBS_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."threads
			ORDER BY threadid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[threadid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}threads");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	threadid,
							forumid,
							TotalPosts,
							datecreated,
							lastactivity,
							threadsubject,
							anonymous,
							closed,
							timesviewed,
							sticky,
							haspoll,
							pollid,
							hasattachment,
							lastposteranonymous,
							lastpostermemberid,
							lastposterisregistered,
							lastposterguestname,
							memberid,
							isregistered,
							guestname
					FROM {$tableprefix}threads WHERE threadid
						IN(SELECT TOP {$per_page} threadid
							FROM (SELECT TOP {$internal} threadid FROM {$tableprefix}threads ORDER BY threadid)
							A ORDER BY threadid DESC)
					ORDER BY threadid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[threadid]"] = $detail;
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
	function get_megaBBS_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."members
			ORDER BY memberid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[memberid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}members");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	memberid,
							username,
							password,
							realname,
							website,
							emailaddress,
							icqnumber,
							aim,
							yahoo,
							msn,
							totalposts,
							showemail,
							active,
							CAST([interests] as TEXT) as interests,
							usesignature,
							viewsignature,
							CAST([signature] as TEXT) as signature,
							dateregistered,
							lastlogon,
							location,
							notificationpreference,
							invisible,
							AvatarURL,
							PhotoURL,
							DefaultThreadView,
							DefaultForumView,
							UseRichEdit,
							DisablePostCount,
							SchemeID,
							timeoffset,
							logoffurl,
							CAST([customrank] as TEXT) as customrank,
							usecustomrank,
							sendprivatenotifications,
							includebody
					FROM {$tableprefix}members WHERE memberid
						IN(SELECT TOP {$per_page} memberid
							FROM (SELECT TOP {$internal} memberid FROM {$tableprefix}members ORDER BY memberid)
						A ORDER BY memberid DESC)
					ORDER BY memberid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[memberid]"] = $detail;
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
	function get_megaBBS_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."groups
			ORDER BY groupid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[groupid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}groups");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	groupid,
							groupname
					FROM {$tableprefix}groups WHERE groupid
						IN(SELECT TOP {$per_page} groupid
							FROM (SELECT TOP {$internal} groupid FROM {$tableprefix}groups ORDER BY groupid)
						A ORDER BY groupid DESC)
					ORDER BY groupid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[groupid]"] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_megabbs_userrgoups(&$Db_object, &$databasetype, &$tableprefix, $user_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($user_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."groupmembers
			WHERE memberid =" . $user_id . "
			ORDER BY groupid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail['groupid'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	groupid, memberid FROM {$tableprefix}groupmembers WHERE memberid =" . $user_id;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail['groupid'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_megaBBS_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . $tableprefix . "categories";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[categoryid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	categoryid,
							name,
							URL,
							locked,
							sortorder,
							schemedefault,
							forcescheme
							FROM {$tableprefix}categories";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[categoryid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_megabbs_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}attachments ORDER BY attachmentid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[attachmentid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}attachments");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT	attachmentid,
							filename,
							[file],
							filesize,
							downloadcount,
							messageid,
							infilesystem
					FROM {$tableprefix}attachments WHERE attachmentid
						IN(SELECT TOP {$per_page} attachmentid
							FROM (SELECT TOP {$internal} attachmentid FROM {$tableprefix}attachments ORDER BY attachmentid)
						A ORDER BY attachmentid DESC)
					ORDER BY attachmentid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[attachmentid]"] = $detail;
			}
		}

		return $return_array;
	}
} // Class end
# Autogenerated on : December 22, 2004, 5:35 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
