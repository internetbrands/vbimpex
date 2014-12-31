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
* yaf
*
* @package 		ImpEx.yaf
* *
*/

class yaf_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.9.0';
	var $_tested_versions = array('1.9.0');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'Yet Another Forum';
	var $_homepage 	= 'http://www.yetanotherforum.net/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'AccessMask', 'Active', 'Attachment', 'BannedIP', 'Board', 'Category', 'CheckEmail', 'Choice', 'Forum', 'ForumAccess', 'Group',
		'Mail', 'Message', 'NntpForum', 'NntpServer', 'NntpTopic', 'PMessage', 'Poll', 'Rank', 'Registry', 'Replace_Words', 'Smiley',
		'Topic', 'User', 'UserForum', 'UserGroup', 'UserPMessage', 'WatchForum', 'WatchTopic'
	);

	function yaf_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed	The string to parse
	* @param	boolean			Truncate smilies
	*
	* @return	array
	*/
	function yaf_html($text)
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
	function get_yaf_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT UserID, Name FROM {$tableprefix}User ORDER BY UserID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[UserID]"] = $row['Name'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			echo "ere";
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}User");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							Name
					FROM {$tableprefix}User WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}User ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{

				$return_array["$row[UserID]"] = $row['Name'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_yaf_usergroups(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Group ORDER BY GroupID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['GroupID'];
				$return_array['count']++;
				$return_array['data']["$row[GroupID]"]['Name'] = $row['Name'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Group");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	GroupID,
							Name
					FROM {$tableprefix}Group WHERE GroupID
						IN(SELECT TOP {$per_page} GroupID
							FROM (SELECT TOP {$internal} GroupID FROM {$tableprefix}Group ORDER BY GroupID)
						A ORDER BY GroupID DESC)
					ORDER BY GroupID";

			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['GroupID']; // Not that we're using this in MSSQL
				$return_array['count']++;
				$return_array['data']["$row[GroupID]"]['Name'] = $row['Name'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_yaf_users(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}User ORDER BY UserID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['UserID'];
				$return_array['count']++;
				$return_array['data']["$row[UserID]"] = $row;

				$usergroup = $Db_object->query_first("SELECT GroupID FROM {$tableprefix}UserGroup WHERE UserID=" . $row['UserID']);

				$return_array['data']["$row[UserID]"]['GroupID'] 	= $usergroup['GroupID'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}User");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							Name,
							Email,
							Joined,
							LastVisit,
							IP,
							NumPosts,
							Location,
							HomePage,
							Avatar,
							CAST([Signature] as TEXT) as Signature,
							MSN,
							YIM,
							AIM,
							ICQ,
							RealName,
							Occupation,
							Interests
					FROM {$tableprefix}User WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}User ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['UserID']; // Not that we're using this in MSSQL
				$return_array['count']++;

				$return_array['data']["$row[UserID]"]['Name'] 		= $row['Name'];
				$return_array['data']["$row[UserID]"]['Email'] 		= $row['Email'];
				$return_array['data']["$row[UserID]"]['Joined'] 	= $row['Joined'];
				$return_array['data']["$row[UserID]"]['LastVisit'] 	= $row['LastVisit'];
				$return_array['data']["$row[UserID]"]['IP'] 		= $row['IP'];
				$return_array['data']["$row[UserID]"]['NumPosts'] 	= $row['NumPosts'];
				$return_array['data']["$row[UserID]"]['Location'] 	= $row['Location'];
				$return_array['data']["$row[UserID]"]['HomePage'] 	= $row['HomePage'];
				$return_array['data']["$row[UserID]"]['Avatar'] 	= $row['Avatar'];
				$return_array['data']["$row[UserID]"]['Signature'] 	= $row['Signature'];
				$return_array['data']["$row[UserID]"]['MSN'] 		= $row['MSN'];
				$return_array['data']["$row[UserID]"]['YIM'] 		= $row['YIM'];
				$return_array['data']["$row[UserID]"]['AIM'] 		= $row['AIM'];
				$return_array['data']["$row[UserID]"]['ICQ'] 		= $row['ICQ'];
				$return_array['data']["$row[UserID]"]['RealName']	= $row['RealName'];
				$return_array['data']["$row[UserID]"]['Occupation'] = $row['Occupation'];
				$return_array['data']["$row[UserID]"]['Interests'] 	= $row['Interests'];

				$usergroup = $Db_object->query_first("SELECT GroupID FROM {$tableprefix}UserGroup WHERE UserID=" . $row['UserID']);

				$return_array['data']["$row[UserID]"]['GroupID'] 	= $usergroup['GroupID'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_yaf_cats(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Category ORDER BY CategoryID");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[CategoryID]"] = $row;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	CategoryID,
						BoardID,
						Name,
						SortOrder
					FROM {$tableprefix}Category
					";

			$categories = $Db_object->query($sql);

			while ($cat = $Db_object->fetch_array($categories))
			{
				$return_array["$cat[CategoryID]"] = $cat;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_yaf_forums(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Forum ORDER BY ForumID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[ForumID]"] = $row;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Forum");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ForumID,
							CategoryID,
							ParentID,
							Name,
							Description,
							SortOrder
					FROM {$tableprefix}Forum WHERE ForumID
						IN(SELECT TOP {$per_page} ForumID
							FROM (SELECT TOP {$internal} ForumID FROM {$tableprefix}Forum ORDER BY ForumID)
						A ORDER BY ForumID DESC)
					ORDER BY ForumID";

			$forum_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forum_list))
			{
				$return_array["$forum[ForumID]"] = $forum;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_yaf_threads(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Topic ORDER BY TopicID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['TopicID'];
				$return_array['count']++;
				$return_array['data']["$row[TopicID]"] = $row;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Topic");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	TopicID,
							ForumID,
							UserID,
							UserName,
							Posted,
							Topic,
							Views
					FROM {$tableprefix}Topic WHERE TopicID
						IN(SELECT TOP {$per_page} TopicID
							FROM (SELECT TOP {$internal} TopicID FROM {$tableprefix}Topic ORDER BY TopicID)
						A ORDER BY TopicID DESC)
					ORDER BY TopicID";

			$data = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($data))
			{
				$return_array['data']["$row[TopicID]"] = $row;
				$return_array['count']++;
				$return_array['lastid'] = $row['TopicID'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}


	function get_yaf_posts(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Message ORDER BY MessageID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['MessageID'];
				$return_array['count']++;
				$return_array['data']["$row[MessageID]"] = $row;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Message");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	MessageID,
							TopicID,
							ReplyTo,
							[Position],
							UserID,
							UserName,
							Posted,
							CAST([Message] as TEXT) as Message,
							IP
					FROM {$tableprefix}Message WHERE MessageID
						IN(SELECT TOP {$per_page} MessageID
							FROM (SELECT TOP {$internal} MessageID FROM {$tableprefix}Message ORDER BY MessageID)
						A ORDER BY MessageID DESC)
					ORDER BY MessageID";

			$data = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($data))
			{
				$return_array['data']["$row[MessageID]"] = $row;
				$return_array['count']++;
				$return_array['lastid'] = $row['MessageID'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_yaf_pms(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}PMessage ORDER BY PMessageID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['PMessageID'];
				$return_array['count']++;
				$return_array['data']["$row[PMessageID]"] = $row;

				$to = $Db_object->query_first("SELECT UserID FROM {$tableprefix}UserPMessage WHERE UserPMessageID=" . $row['PMessageID']);

				$return_array['data']["$row[PMessageID]"]['to'] 	= $to['UserID'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PMessage");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	PMessageID,
							FromUserID,
							Created,
							Subject,
							CAST([Body] as TEXT) as Body
					FROM {$tableprefix}PMessage WHERE PMessageID
						IN(SELECT TOP {$per_page} PMessageID
							FROM (SELECT TOP {$internal} PMessageID FROM {$tableprefix}PMessage ORDER BY PMessageID)
						A ORDER BY PMessageID DESC)
					ORDER BY PMessageID";

			$data = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($data))
			{
				$return_array['data']["$row[PMessageID]"]['PMessageID']	= $row['PMessageID'];
				$return_array['data']["$row[PMessageID]"]['FromUserID']	= $row['FromUserID'];
				$return_array['data']["$row[PMessageID]"]['Created'] 	= $row['Created'];
				$return_array['data']["$row[PMessageID]"]['Subject'] 	= $row['Subject'];
				$return_array['data']["$row[PMessageID]"]['Body'] 		= $row['Body'];

				$return_array['data']["$row[PMessageID]"] = $row;
				$return_array['count']++;
				$return_array['lastid'] = $row['PMessageID'];

				$to = $Db_object->query_first("SELECT UserID FROM {$tableprefix}UserPMessage WHERE UserPMessageID=" . $row['PMessageID']);

				$return_array['data']["$row[PMessageID]"]['to'] 	= $to['UserID'];
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_yaf_attachments(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Attachment ORDER BY AttachmentID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['lastid'] = $row['AttachmentID'];
				$return_array['count']++;
				$return_array['data']["$row[AttachmentID]"] = $row;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Attachment");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	AttachmentID,
							MessageID,
							FileName,
							Bytes,
							Downloads
					FROM {$tableprefix}Attachment WHERE AttachmentID
						IN(SELECT TOP {$per_page} AttachmentID
							FROM (SELECT TOP {$internal} AttachmentID FROM {$tableprefix}Attachment ORDER BY AttachmentID)
						A ORDER BY AttachmentID DESC)
					ORDER BY AttachmentID";

			$data = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($data))
			{
				$return_array['data']["$row[AttachmentID]"] = $row;
				$return_array['count']++;
				$return_array['lastid'] = $row['AttachmentID'];

			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>

