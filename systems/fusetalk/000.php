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
* fusetalk API module
*
* @package			ImpEx.fusetalk
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class fusetalk_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.0';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'FuseTalk';
	var $_homepage 	= 'http://www.fusetalk.com';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'arcmessages', 'arcthreads', 'attachedfiles', 'authentication', 'banning','basicchat_messages','basicchat_rooms',
		'basicchat_userroomlink','buddylist','categories','categories_mod','censorwords', 'communitydefaults','communitysettings','communitysettingsother',
		'countries','defaultsets','dictionary','dictionarywords','emailposting','emailpostingsubject','emoticons','favorites','foldericonthemes',
		'forumgroupings','forums','forumsettings','forumsettingsother','forumusers','groupings','groups','groupusers','guests','licenses','mail',
		'mailinglist','messages','moderatorlogs','modules','polls','pollsanswers','pollstracking','privatebanning','privatecategories','privatemessages',
		'privatethreads','ratingicons','recentitems','reportingactions','reportingdata','reportingtrans','reportinguserdata','securitydescriptors',
		'servers','stateprovince','subscription','surveyanswers','surveyquestions','surveyresponses','themes','threads','threadstatistics',
		'timezones','today','userrating','users','usersettings','usertitles'
	);


	function fusetalk_000()
	{
	}

	/**
	* Parses and custom HTML for fusetalk
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function fusetalk_html($text)
	{
		$text = str_replace('<ul>','[list]',$text);
		$text = str_replace('</li>','',$text);
		$text = str_replace('</ul>','[/list]',$text);

		$text = str_replace('<UL>','[list]',$text);
		$text = str_replace('</LI>','',$text);
		$text = str_replace('<UL>','[/list]',$text);


		$text = str_replace('<blockquote>Quote:', '[quote]', $text);

		$text = str_replace('<blockquote>Quote:', '[quote]', $text);

		$text = str_replace('<font size="+2">', '[size=6]', $text);
		$text = str_replace('</font>', '[/size]', $text);

		$text = str_replace('<blockquote>Quote:', '[quote]', $text);
		$text = str_replace('<blockquote>Quote', '[quote]', $text);
		$text = str_replace('<blockquote>quote:', '[quote]', $text);
		$text = str_replace('<hr></blockquote>','[/quote]',$text);

		$text = preg_replace('#<P(.*)>#U', '', $text);
		$text = str_replace('</P>','',$text);

		$text = preg_replace('#<F(.*)>#U', '', $text);
		$text = str_replace('</FONT>','',$text);
		$text = str_replace('&nbsp;','',$text);
		$text = str_replace('<hr>','',$text);

		// last pass clean
		$text = preg_replace('#<font(.*)>#U', '', $text);
		$text = preg_replace('#</font(.*)>#U', '', $text);

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
	function get_fusetalk_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	iuserid,
							vchnickname
					FROM {$tableprefix}users WHERE iuserid
						IN(SELECT TOP {$per_page} iuserid
							FROM (SELECT TOP {$internal} iuserid FROM {$tableprefix}users ORDER BY iuserid)
						A ORDER BY iuserid DESC)
					ORDER BY iuserid";

			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[iuserid]"] = $user['vchnickname'];
			}

			return $return_array;
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT iuserid,vchnickname
			FROM " . $tableprefix . "users
			ORDER BY iuserid
			LIMIT " . $start_at . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[iuserid]"] = $user['vchnickname'];
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
	function get_fusetalk_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}categories");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	icategoryid,
							icatgroupnum,
							vchcategoryname,
							vchdescription,
							vchlastposter,
							iforumid
					FROM {$tableprefix}categories WHERE icategoryid
						IN(SELECT TOP {$per_page} icategoryid
							FROM (SELECT TOP {$internal} icategoryid FROM {$tableprefix}categories ORDER BY icategoryid)
						A ORDER BY icategoryid DESC)
					ORDER BY icategoryid
			";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[icategoryid]"]['displayorder'] 	= $detail['icatgroupnum'];
				$return_array["$detail[icategoryid]"]['name'] 			= $detail['vchcategoryname'];
				$return_array["$detail[icategoryid]"]['description'] 	= $detail['vchdescription'];
				$return_array["$detail[icategoryid]"]['lastposter'] 	= $detail['vchlastposter'];
				$return_array["$detail[icategoryid]"]['parentid']	 	= $detail['iforumid'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "categories
			ORDER BY icategoryid
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[icategoryid]"]['displayorder'] 	= $detail['icatgroupnum'];
				$return_array["$detail[icategoryid]"]['name'] 			= $detail['vchcategoryname'];
				$return_array["$detail[icategoryid]"]['description'] 	= $detail['vchdescription'];
				$return_array["$detail[icategoryid]"]['lastposter'] 	= $detail['vchlastposter'];
				$return_array["$detail[icategoryid]"]['parentid']	 	= $detail['iforumid'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_fusetalk_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mssql')
		{
			$sql = "
				SELECT
					iforumid,
					vchforumname
				FROM {$tableprefix}forums
				ORDER BY iforumid
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iforumid]"]['position'] 		= $detail['iforumid'];
				$return_array["$detail[iforumid]"]['name'] 			= $detail['vchforumname'];
				$return_array["$detail[iforumid]"]['description'] 	= $detail['vchforumname'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}forums ORDER BY iforumid");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iforumid]"]['position'] 		= $detail['iforumid'];
				$return_array["$detail[iforumid]"]['name'] 			= $detail['vchforumname'];
				$return_array["$detail[iforumid]"]['description'] 	= $detail['vchforumname'];
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
	function get_fusetalk_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}privatemessages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	imessageid,
							iuserid,
							CAST([txmessage] as TEXT) as message,
							dtinsertdate,
							iownerid
					FROM {$tableprefix}privatemessages WHERE imessageid
						IN(SELECT TOP {$per_page} imessageid
							FROM (SELECT TOP {$internal} imessageid FROM {$tableprefix}privatemessages ORDER BY imessageid)
						A ORDER BY imessageid DESC)
					ORDER BY imessageid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[imessageid]"]['messageid'] 	= $detail['imessageid'];
				$return_array["$detail[imessageid]"]['senderid'] 	= $detail['iuserid'];
				$return_array["$detail[imessageid]"]['message']		= $detail['message'];
				$return_array["$detail[imessageid]"]['reciverid']	= $detail['iownerid'];
				$return_array["$detail[imessageid]"]['dateline']	= $detail['dtinsertdate'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "privatemessages
			ORDER BY imessageid
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[imessageid]"]['messageid'] 	= $detail['imessageid'];
				$return_array["$detail[imessageid]"]['senderid'] 	= $detail['iuserid'];
				$return_array["$detail[imessageid]"]['message']		= $detail['txmessage'];
				$return_array["$detail[imessageid]"]['reciverid']	= $detail['iownerid'];
				$return_array["$detail[imessageid]"]['dateline']	= $detail['dtinsertdate'];
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
	function get_fusetalk_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}polls");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ipollid,
							CAST([txdescription] as TEXT) as question,
							iparentid,
							iuserid,
							vchpolltype,
							dtinsertdate
					FROM {$tableprefix}polls WHERE ipollid
						IN(SELECT TOP {$per_page} ipollid
							FROM (SELECT TOP {$internal} ipollid FROM {$tableprefix}polls ORDER BY ipollid)
						A ORDER BY ipollid DESC)
					ORDER BY ipollid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if($detail['vchpolltype'] == 'thread')
				{
					$return_array["$detail[ipollid]"]['pollid'] 	= $detail['ipollid'];
					$return_array["$detail[ipollid]"]['question'] 	= $detail['question'];
					$return_array["$detail[ipollid]"]['threadid']	= $detail['iparentid'];
					$return_array["$detail[ipollid]"]['userid']		= $detail['iuserid'];
					$return_array["$detail[ipollid]"]['dateline']	= $detail['dtinsertdate'];
				}
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "polls
			WHERE vchpolltype = 'thread'
			ORDER BY ipollid
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ipollid]"]['pollid'] 	= $detail['ipollid'];
				$return_array["$detail[ipollid]"]['question'] 	= $detail['txdescription'];
				$return_array["$detail[ipollid]"]['threadid']	= $detail['iparentid'];
				$return_array["$detail[ipollid]"]['userid']		= $detail['iuserid'];
				$return_array["$detail[ipollid]"]['dateline']	= $detail['dtinsertdate'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_fusetalk_poll_questions(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}pollsanswers");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ianswerid,
							vchanswer,
							ipollid,
							icount
					FROM {$tableprefix}pollsanswers
					WHERE ipollid = {$poll_id}
					ORDER BY ianswerid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ianswerid]"]['pollid']	= $detail['ipollid'];
				$return_array["$detail[ianswerid]"]['answer']	= $detail['vchanswer'];
				$return_array["$detail[ianswerid]"]['votes']	= $detail['icount'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
				SELECT *
				FROM " . $tableprefix . "pollsanswers
				WHERE ipollid = {$poll_id}
				ORDER BY ianswerid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ianswerid]"]['pollid']	= $detail['ipollid'];
				$return_array["$detail[ianswerid]"]['answer']	= $detail['vchanswer'];
				$return_array["$detail[ianswerid]"]['votes']	= $detail['icount'];
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
	function get_fusetalk_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}messages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	imessageid,
							CAST([txmessage] as TEXT) as posttext,
							ithreadid,
							iuserid,
							vchipaddress,
							dtmessagedate,
							iparentid,
							vchmessagetitle
					FROM {$tableprefix}messages WHERE imessageid
						IN(SELECT TOP {$per_page} imessageid
							FROM (SELECT TOP {$internal} imessageid FROM {$tableprefix}messages ORDER BY imessageid)
						A ORDER BY imessageid DESC)
					ORDER BY imessageid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$detail[imessageid]"]['postid']		= $detail['imessageid'];
				$return_array['data']["$detail[imessageid]"]['pagetext']	= $detail['posttext'];
				$return_array['data']["$detail[imessageid]"]['title']		= $detail['vchmessagetitle'];
				$return_array['data']["$detail[imessageid]"]['userid']		= $detail['iuserid'];
				$return_array['data']["$detail[imessageid]"]['threadid']	= $detail['ithreadid'];
				$return_array['data']["$detail[imessageid]"]['dateline']	= $detail['dtmessagedate'];
				$return_array['data']["$detail[imessageid]"]['parentid']	= $detail['iparentid'];
				$return_array['data']["$detail[imessageid]"]['ipaddress']	= $detail['chipaddress'];
				$return_array['lastid'] = $detail['imessageid'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}messages WHERE imessageid > {$start_at} ORDER BY imessageid LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$detail[imessageid]"]['postid']		= $detail['imessageid'];
				$return_array['data']["$detail[imessageid]"]['pagetext']	= $detail['posttext'];
				$return_array['data']["$detail[imessageid]"]['title']		= $detail['vchmessagetitle'];
				$return_array['data']["$detail[imessageid]"]['userid']		= $detail['iuserid'];
				$return_array['data']["$detail[imessageid]"]['threadid']	= $detail['ithreadid'];
				$return_array['data']["$detail[imessageid]"]['dateline']	= $detail['dtmessagedate'];
				$return_array['data']["$detail[imessageid]"]['parentid']	= $detail['iparentid'];
				$return_array['data']["$detail[imessageid]"]['ipaddress']	= $detail['chipaddress'];
				$return_array['lastid'] = $detail['imessageid'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_fusetalk_archive_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}arcmessages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	imessageid,
							CAST([txmessage] as TEXT) as posttext,
							ithreadid,
							iuserid,
							vchipaddress,
							dtmessagedate,
							iparentid,
							vchmessagetitle
					FROM {$tableprefix}arcmessages WHERE imessageid
						IN(SELECT TOP {$per_page} imessageid
							FROM (SELECT TOP {$internal} imessageid FROM {$tableprefix}arcmessages ORDER BY imessageid)
						A ORDER BY imessageid DESC)
					ORDER BY imessageid
			";

			$details_list = $Db_object->query($sql);
			$i=1;
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[$i]['postid']		= $detail['imessageid'];
				$return_array[$i]['pagetext']	= $detail['posttext'];
				$return_array[$i]['title']		= $detail['vchmessagetitle'];
				$return_array[$i]['userid']		= $detail['iuserid'];
				$return_array[$i]['threadid']	= $detail['ithreadid'];
				$return_array[$i]['dateline']	= $detail['dtmessagedate'];
				$return_array[$i]['parentid']	= $detail['iparentid'];
				$return_array[$i]['ipaddress']	= $detail['chipaddress'];
				$return_array['lastid'] = $detail['imessageid'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}arcmessages WHERE imessageid > {$start_at} ORDER BY imessageid LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$detail[imessageid]"]['postid']		= $detail['imessageid'];
				$return_array['data']["$detail[imessageid]"]['pagetext']	= $detail['posttext'];
				$return_array['data']["$detail[imessageid]"]['title']		= $detail['vchmessagetitle'];
				$return_array['data']["$detail[imessageid]"]['userid']		= $detail['iuserid'];
				$return_array['data']["$detail[imessageid]"]['threadid']	= $detail['ithreadid'];
				$return_array['data']["$detail[imessageid]"]['dateline']	= $detail['dtmessagedate'];
				$return_array['data']["$detail[imessageid]"]['parentid']	= $detail['iparentid'];
				$return_array['data']["$detail[imessageid]"]['ipaddress']	= $detail['chipaddress'];
				$return_array['lastid'] = $detail['imessageid'];
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
	function get_fusetalk_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page) OR $per_page == 0) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}threads");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT TOP {$per_page} 	ithreadid,
							vchthreadname,
							dtinsertdate,
							iuserid,
							icategoryid,
							imessagecount,
							iviewcount,
							vchtopicsummary,
							bprivate,
							vchalertthread,
							vchlastposter
					FROM {$tableprefix}threads WHERE ithreadid >=
						$start_at
					ORDER BY ithreadid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ithreadid]"]['threadid']		= $detail['ithreaid'];
				$return_array["$detail[ithreadid]"]['position']		= $detail['iuserid'];
				$return_array["$detail[ithreadid]"]['title']		= $detail['vchthreadname'];
				$return_array["$detail[ithreadid]"]['userid']		= $detail['iuserid'];
				$return_array["$detail[ithreadid]"]['forumid']		= $detail['icategoryid'];
				$return_array["$detail[ithreadid]"]['replycount']	= $detail['imessagecount'];
				$return_array["$detail[ithreadid]"]['views']		= $detail['iviewcount'];
				$return_array["$detail[ithreadid]"]['description']	= $detail['vchtopicsummary'];
				$return_array["$detail[ithreadid]"]['private']		= $detail['bprivate'];
				$return_array["$detail[ithreadid]"]['postusername']	= $detail['vchlastposter'];
				$return_array["$detail[ithreadid]"]['dateline']		= $detail['dtinsertdate'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
				SELECT *
				FROM " . $tableprefix . "threads WHERE ithreadid >=
				$start_at
				ORDER BY ithreadid
				LIMIT " . $per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ithreadid]"]['threadid']		= $detail['ithreadid'];
				$return_array["$detail[ithreadid]"]['position']		= $detail['iuserid'];
				$return_array["$detail[ithreadid]"]['title']		= $detail['vchthreadname'];
				$return_array["$detail[ithreadid]"]['userid']		= $detail['iuserid'];
				$return_array["$detail[ithreadid]"]['forumid']		= $detail['icategoryid'];
				$return_array["$detail[ithreadid]"]['replycount']	= $detail['imessagecount'];
				$return_array["$detail[ithreadid]"]['views']		= $detail['iviewcount'];
				$return_array["$detail[ithreadid]"]['description']	= $detail['vchtopicsummary'];
				$return_array["$detail[ithreadid]"]['private']		= $detail['bprivate'];
				$return_array["$detail[ithreadid]"]['postusername']	= $detail['vchlastposter'];
				$return_array["$detail[ithreadid]"]['dateline']		= $detail['dtinsertdate'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_fusetalk_archive_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}arcthreads");

			if ($count[0] == 0)
			{
				return array();
			}

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT TOP {$per_page}
					FROM {$tableprefix}arcthreads WHERE ithreadid > $start_at
					ORDER BY ithreadid
			";

			$details_list = $Db_object->query($sql);
			$i=1;
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[$i]['threadid']		= $detail['ithreaid'];
				$return_array[$i]['position']		= $detail['iuserid'];
				$return_array[$i]['title']		= $detail['vchthreadname'];
				$return_array[$i]['userid']		= $detail['iuserid'];
				$return_array[$i]['forumid']		= $detail['icategoryid'];
				$return_array[$i]['replycount']	= $detail['imessagecount'];
				$return_array[$i]['views']		= $detail['iviewcount'];
				$return_array[$i]['description']	= $detail['vchthreadname'];
				$return_array[$i]['dateline']		= $detail['dtinsertdate'];
				$i++;
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
				SELECT *
				FROM " . $tableprefix . "arcthreads
				WHERE ithreadid > $start_at
				ORDER BY ithreadid
				LIMIT " . $per_page;

			$details_list = $Db_object->query($sql);
			$i=1;
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[$i]['threadid']		= $detail['ithreadid'];
				$return_array[$i]['position']		= $detail['iuserid'];
				$return_array[$i]['title']		= $detail['vchthreadname'];
				$return_array[$i]['userid']		= $detail['iuserid'];
				$return_array[$i]['forumid']		= $detail['icategoryid'];
				$return_array[$i]['replycount']	= $detail['imessagecount'];
				$return_array[$i]['views']		= $detail['iviewcount'];
				$return_array[$i]['description']	= $detail['vchthreadname'];
				$return_array[$i]['dateline']		= $detail['dtinsertdate'];
				$i++;
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
	function get_fusetalk_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = mssql_fetch_array(mssql_query("SELECT count(*) FROM {$tableprefix}users"));

			$start_at = intval($start_at);

			$sql = "SELECT TOP $per_page iuserid,
							vchnickname,
							vchemailaddress,
							vchfirstname,
							vchlastname,
							vchpassword,
							itimezoneid,
							imessagepostcount,
							dtinsertdate,
							dtlastvisiteddate,
							bemailavailable,
							bmsgallow,
							vchicqnumber,
							vchaim,
							vchweburl,
							CAST([txsignature] as TEXT) as sig,
							dtbirthdate,
							dtinsertdate,
							dtlastvisiteddate

					FROM {$tableprefix}users WHERE iuserid > $start_at
					ORDER BY iuserid
			";

			#vchfirstname,
			#				vchlastname,
			#				vchsignature,
			#vchauthoricon

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[iuserid]"]['username'] 		= $user['vchnickname'];
				$return_array["$user[iuserid]"]['email'] 			= $user['vchemailaddress'];
				$return_array["$user[iuserid]"]['forename'] 		= $user['vchfirstname'];
				$return_array["$user[iuserid]"]['surname'] 			= $user['vchlastname'];
				$return_array["$user[iuserid]"]['password'] 		= $user['vchpassword'];
				$return_array["$user[iuserid]"]['timezoneoffset'] 	= intval($user['itimezoneid']);
				$return_array["$user[iuserid]"]['posts'] 			= $user['imessagepostcount'];
				$return_array["$user[iuserid]"]['joindate'] 		= $user['dtinsertdate'];
				$return_array["$user[iuserid]"]['lastvisit'] 		= $user['dtlastvisiteddate'];
				$return_array["$user[iuserid]"]['icq'] 				= $user['vchicqnumber'];
				$return_array["$user[iuserid]"]['aim'] 				= $user['vchaim'];
				$return_array["$user[iuserid]"]['birthday'] 		= $user['dtbirthdate'];
				$return_array["$user[iuserid]"]['homepage'] 		= $user['vchweburl'];
				$return_array["$user[iuserid]"]['signature'] 		= $user['vchsignature'];
				$return_array["$user[iuserid]"]['dtinsertdate'] 	= $user['dtinsertdate'];
				$return_array["$user[iuserid]"]['dtlastvisiteddate'] = $user['dtlastvisiteddate'];
				$return_array["$user[iuserid]"]['vchfirstname'] 	= $user['vchfirstname'];
				$return_array["$user[iuserid]"]['vchlastname'] 		= $user['vchlastname'];
				$return_array["$user[iuserid]"]['vchsignature'] 	= $user['vchsignature'];
			}
		}
		else if ($databasetype == 'mysql')
		{
			$sql = "
				SELECT *
				FROM " . $tableprefix . "users
				ORDER BY iuserid
				LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($details_list))
			{
				$return_array["$user[iuserid]"]['username'] 		= $user['vchnickname'];
				$return_array["$user[iuserid]"]['email'] 			= $user['vchemailaddress'];
				$return_array["$user[iuserid]"]['forename'] 		= $user['vchfirstname'];
				$return_array["$user[iuserid]"]['surname'] 			= $user['vchlastname'];
				$return_array["$user[iuserid]"]['password'] 		= $user['vchpassword'];
				$return_array["$user[iuserid]"]['timezoneoffset'] 	= intval($user['itimezoneid']);
				$return_array["$user[iuserid]"]['posts'] 			= $user['imessagepostcount'];
				$return_array["$user[iuserid]"]['joindate'] 		= $user['dtinsertdate'];
				$return_array["$user[iuserid]"]['lastvisit'] 		= $user['dtlastvisiteddate'];
				$return_array["$user[iuserid]"]['icq'] 				= $user['vchicqnumber'];
				$return_array["$user[iuserid]"]['aim'] 				= $user['vchaim'];
				$return_array["$user[iuserid]"]['birthday'] 		= $user['dtbirthdate'];
				$return_array["$user[iuserid]"]['homepage'] 		= $user['vchweburl'];
				$return_array["$user[iuserid]"]['signature'] 		= $user['vchsignature'];
				$return_array["$user[iuserid]"]['dtinsertdate'] 	= $user['dtinsertdate'];
				$return_array["$user[iuserid]"]['dtlastvisiteddate'] = $user['dtlastvisiteddate'];
				$return_array["$user[iuserid]"]['vchfirstname'] 		= $user['vchfirstname'];
				$return_array["$user[iuserid]"]['vchlastname'] 		= $user['vchlastname'];
				$return_array["$user[iuserid]"]['vchsignature'] 	= $user['vchsignature'];
				$return_array["$user[iuserid]"]['vchauthoricon'] 	= $user['vchauthoricon'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_fusetalk_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachedfiles
			ORDER BY iattachmentid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iattachmentid]"] = $detail;
			}
		}
		else
		{
			$start_at = empty($start_at)? 0 : intval($start_at);
			$sql = "
			SELECT TOP $per_page * FROM " .
			$tableprefix."attachedfiles
			WHERE iattachmentid > $start_at
			ORDER BY iattachmentid
			";
			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iattachmentid]"] = $detail;
			}
			return $return_array;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : November 3, 2004, 3:01 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
