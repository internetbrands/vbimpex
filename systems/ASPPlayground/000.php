<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* ASPPlayground API module
*
* @package			ImpEx.ASPPlayground
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class ASPPlayground_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.5.5';


	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'ASPPlayground';
	var $_homepage 	= 'http://www.aspplayground.net/';
	var $_tier = '2';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'Announcement', 'Colorscheme','Config', 'CSS', 'DBHeaderFooter', 'Events', 'Forums', 'GroupMember', 'Members', 'Messages',
		'Moderator', 'PMFolder', 'PMreceive', 'PMsg', 'Poll', 'PollLog', 'PrivateUser',	'Ranking', 'RateTrack', 'Revision', 'Session',
		'Srvmsg', 'Subscription', 'upfile', 'UserGroup'
	);


	function ASPPlayground_000()
	{
	}


	/**
	* Parses and custom HTML for ASPPlayground
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function ASPPlayground_html($text)
	{
		// Smilies
		$text = str_replace('[:)]', ':)', $text);
		$text = str_replace('[:(]', ':(', $text);
		$text = str_replace('[;)]', ';)', $text);
		$text = str_replace('[:D]', ':D', $text);
		$text = str_replace('[8|]', ':eek:', $text);

		$text = str_replace('[image]', '[img]', $text);
		$text = str_replace('[/image]', '[/img]', $text);

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
	function get_ASPPlayground_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = mssql_fetch_array(mssql_query("SELECT count(*) FROM {$tableprefix}Members"));


			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Mem,
							Login
					FROM {$tableprefix}Members WHERE Mem
						IN(SELECT TOP {$per_page} Mem
							FROM (SELECT TOP {$internal} Mem FROM {$tableprefix}Members ORDER BY Mem)
						A ORDER BY Mem DESC)
					ORDER BY Mem";

			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				if($user['Mem'] != -1)
				{
					$return_array["$user[Mem]"] = $user['Login'];
				}
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
	function get_ASPPlayground_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Forums");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ForumID,
							ForumTitle,
							CAST([ForumDesc] as TEXT) as ForumDesc,
							Total,
							Topics,
							Sort,
							CatID,
							IsPrivate
					FROM {$tableprefix}Forums WHERE ForumID
						IN(SELECT TOP {$per_page} ForumID
							FROM (SELECT TOP {$internal} ForumID FROM {$tableprefix}Forums ORDER BY ForumID)
						A ORDER BY ForumID DESC)
					ORDER BY ForumID
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ForumID]"]['displayorder'] 	= $detail['Sort'];
				$return_array["$detail[ForumID]"]['title'] 			= $detail['ForumTitle'];
				$return_array["$detail[ForumID]"]['description'] 	= $detail['ForumDesc'];
				$return_array["$detail[ForumID]"]['catid'] 			= $detail['CatID'];
				$return_array["$detail[ForumID]"]['IsPrivate']	 	= $detail['IsPrivate'];
				$return_array["$detail[ForumID]"]['threads'] 		= $detail['Topics'];
				$return_array["$detail[ForumID]"]['posts']	 		= $detail['Total'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ASPPlayground_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mssql')
		{
			$sql = "
				SELECT
					CatID,
					CatName,
					sort
				FROM {$tableprefix}Category
				ORDER BY CatID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[CatID]"]['displayorder']	= $detail['sort'];
				$return_array["$detail[CatID]"]['title'] 		= $detail['CatName'];
				$return_array["$detail[CatID]"]['description'] 	= $detail['CatName'];
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
	function get_ASPPlayground_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."moderator
			ORDER BY moderator_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[moderator_id]"] = $detail;
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
	function get_ASPPlayground_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PMsg");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT PMID,
							SenderID,
							subject,
							CAST([body] as TEXT) as body,
							sentTo,
							datesent,
							withsig
					FROM {$tableprefix}PMsg WHERE PMID
						IN(SELECT TOP {$per_page} PMID
							FROM (SELECT TOP {$internal} PMID FROM {$tableprefix}PMsg ORDER BY PMID)
						A ORDER BY PMID DESC)
					ORDER BY PMID
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[PMID]"]['fromid']	= $detail['SenderID'];
				$return_array["$detail[PMID]"]['subject']	= $detail['subject'];
				$return_array["$detail[PMID]"]['body']		= $detail['body'];
				$return_array["$detail[PMID]"]['sentto']	= $detail['sentTo'];
				$return_array["$detail[PMID]"]['dateline']	= $detail['datesent'];
				$return_array["$detail[PMID]"]['signature']	= $detail['withsig'];
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
	function get_ASPPlayground_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Messages WHERE isPoll = 1");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	messageID
					FROM {$tableprefix}Messages WHERE isPoll = 1 AND messageID
						IN(SELECT TOP {$per_page} messageID
							FROM (SELECT TOP {$internal} messageID FROM {$tableprefix}Messages WHERE (isPoll = 1) ORDER BY messageID)
						A ORDER BY messageID DESC)
					ORDER BY messageID
			";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$inner_sql = "SELECT * FROM {$tableprefix}Poll WHERE PollID =" . $detail['messageID'];

				$polls_list = $Db_object->query($inner_sql);

				while ($poll = $Db_object->fetch_array($polls_list))
				{
					$return_array["$detail[messageID]"]["$poll[ChoiceID]"]['option']	= $poll['Choice'];
					$return_array["$detail[messageID]"]["$poll[ChoiceID]"]['votes']		= $poll['Counts'];
					$return_array["$detail[messageID]"]["$poll[ChoiceID]"]['multi']		= $poll['allowMultiple'];
				}
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}




	function get_ASPPlayground_vote_voters(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$sql = "SELECT * FROM {$tableprefix}PollLog WHERE PollLogID =" . $poll_id;

			$details_list = $Db_object->query($sql);

			$count=1;
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$count++;
				$return_array[$count]['choiceid']	= $detail['ChoiceID'];
				$return_array[$count]['userid']		= $detail['Mem'];
				$return_array[$count]['ipadress']	= $detail['IP'];
				$return_array[$count]['dateline']	= $detail['voteDate'];
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
	function get_ASPPlayground_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Messages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	messageID,
							threadID,
							Subject,
							parent,
							ForumID,
							ip,
							Mem,
							hits,
							Locked,
							isTop,
							dateCreated,
							CAST([Body] as TEXT) as posttext,
							totalReply
					FROM {$tableprefix}Messages WHERE messageID
						IN(SELECT TOP {$per_page} messageID
							FROM (SELECT TOP {$internal} messageID FROM {$tableprefix}Messages ORDER BY messageID)
						A ORDER BY messageID DESC)
					ORDER BY messageID
			";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[messageID]"]['threadid'] 		= $detail['threadID'];
				$return_array["$detail[messageID]"]['sticky'] 			= $detail['isTop'];
				$return_array["$detail[messageID]"]['parent'] 			= $detail['parent'];
				$return_array["$detail[messageID]"]['title'] 			= $detail['Subject'];
				$return_array["$detail[messageID]"]['pagetext']			= $detail['posttext'];
				$return_array["$detail[messageID]"]['forumid'] 			= $detail['ForumID'];
				$return_array["$detail[messageID]"]['ipaddress'] 		= $detail['ip'];
				$return_array["$detail[messageID]"]['userid']		 	= $detail['Mem'];
				$return_array["$detail[messageID]"]['views']	 		= $detail['hits'];
				$return_array["$detail[messageID]"]['locked']			= $detail['Locked'];
				$return_array["$detail[messageID]"]['dateline']			= $detail['dateCreated'];
				$return_array["$detail[messageID]"]['replycount']		= $detail['totalReply'];
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
	function get_ASPPlayground_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Messages WHERE parent = 0");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	messageID,
							Subject,
							ForumID,
							ip,
							Mem,
							hits,
							Locked,
							isTop,
							dateCreated,
							totalReply
					FROM {$tableprefix}Messages WHERE parent = 0 AND messageID
						IN(SELECT TOP {$per_page} messageID
							FROM (SELECT TOP {$internal} messageID FROM {$tableprefix}Messages WHERE parent = 0 ORDER BY messageID)
						A ORDER BY messageID DESC)
					ORDER BY messageID
			";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[messageID]"]['sticky'] 			= $detail['isTop'];
				$return_array["$detail[messageID]"]['title'] 			= $detail['Subject'];
				$return_array["$detail[messageID]"]['forumid'] 			= $detail['ForumID'];
				$return_array["$detail[messageID]"]['ipaddress'] 		= $detail['ip'];
				$return_array["$detail[messageID]"]['userid']		 	= $detail['Mem'];
				$return_array["$detail[messageID]"]['views']	 		= $detail['hits'];
				$return_array["$detail[messageID]"]['locked']			= $detail['Locked'];
				$return_array["$detail[messageID]"]['dateline']			= $detail['dateCreated'];
				$return_array["$detail[messageID]"]['replycount']		= $detail['totalReply'];
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
	function get_ASPPlayground_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Members");




			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Mem,
							Login,
							Email,
							Fname,
							Lname,
							homepage,
							signature,
							Userpass,
							totalPosts,
							ip,
							dateSignUp,
							emailview,
							location,
							ICQ,
							Yahoo,
							AOL,
							interests,
							occupation,
							lastLogin,
							avatar,
							customTitle,
							MSNMsger
					FROM {$tableprefix}Members WHERE Mem
						IN(SELECT TOP {$per_page} Mem
							FROM (SELECT TOP {$internal} Mem FROM {$tableprefix}Members ORDER BY Mem)
						A ORDER BY Mem DESC)

					ORDER BY Mem
			";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[Mem]"]['username'] 		= $user['Login'];
				$return_array["$user[Mem]"]['email'] 			= $user['Email'];
				$return_array["$user[Mem]"]['forename'] 		= $user['Fname'];
				$return_array["$user[Mem]"]['surname'] 			= $user['Lname'];
				$return_array["$user[Mem]"]['password'] 		= $user['Userpass'];
				$return_array["$user[Mem]"]['ipaddress'] 		= $user['ip'];
				$return_array["$user[Mem]"]['posts'] 			= $user['totalPosts'];
				$return_array["$user[Mem]"]['homepage'] 		= $user['homepage'];
				$return_array["$user[Mem]"]['signature'] 		= $user['signature'];
				$return_array["$user[Mem]"]['joindate'] 		= $user['dateSignUp'];
				$return_array["$user[Mem]"]['lastvisit'] 		= $user['lastLogin'];
				$return_array["$user[Mem]"]['icq'] 				= $user['ICQ'];
				$return_array["$user[Mem]"]['yahoo'] 			= $user['Yahoo'];
				$return_array["$user[Mem]"]['aim'] 				= $user['AOL'];
				$return_array["$user[Mem]"]['intrests'] 		= $user['intrests'];
				$return_array["$user[Mem]"]['occupation'] 		= $user['occupation'];
				$return_array["$user[Mem]"]['location'] 		= $user['location'];
				$return_array["$user[Mem]"]['avatar'] 			= $user['avatar'];
				$return_array["$user[Mem]"]['MSNMsger'] 		= $user['msn'];
				$return_array["$user[Mem]"]['customtitle'] 		= $user['customTitle'];

				$usergroup = $Db_object->query_first("SELECT GID FROM {$tableprefix}GroupMember WHERE Mem=" .$user['Mem']);

				$return_array["$user[Mem]"]['usergroup'] 		= $usergroup['GID'];

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
	function get_ASPPlayground_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}UserGroup");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}



			$sql = "SELECT 	GID,
							Gname
					FROM {$tableprefix}UserGroup WHERE GID
						IN(SELECT TOP {$per_page} GID
							FROM (SELECT TOP {$internal} GID FROM {$tableprefix}UserGroup ORDER BY GID)
						A ORDER BY GID DESC)
					ORDER BY GID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[GID]"]['title'] = $user['Gname'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
	}


} // Class end
# Autogenerated on : December 1, 2004, 3:44 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
