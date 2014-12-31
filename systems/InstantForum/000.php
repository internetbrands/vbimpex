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
* InstantForum API module
*
* @package			ImpEx.InstantForum
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class InstantForum_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '4.1.4';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'InstantForum ';
	var $_homepage 	= 'http://www.instantasp.co.uk/products/instantforum/default.aspx';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'InstantASP_Administrators','InstantASP_Emails','InstantASP_IgnoredSearchTerms','InstantASP_Roles','InstantASP_ScheduledTasks','InstantASP_Sessions',
		'InstantASP_Settings','InstantASP_Users','InstantASP_UsersRoles','InstantASP_Wrappers','InstantForum_AttachmentTypes','InstantForum_Attachments',
		'InstantForum_AttachmentsPosts','InstantForum_BannedIPAddresses','InstantForum_BuddyIgnoreList','InstantForum_BulkMessages','InstantForum_Events',
		'InstantForum_EventsRoles','InstantForum_Folders','InstantForum_ForumSubscriptions','InstantForum_Forums','InstantForum_ForumsModerators',
		'InstantForum_ForumsRead','InstantForum_ForumsRoles','InstantForum_IFCode','InstantForum_LanguageFilters','InstantForum_Messages',
		'InstantForum_PermissionSets','InstantForum_PermissionSetsRoles','InstantForum_PollAnswers','InstantForum_Polls','InstantForum_PollVotes',
		'InstantForum_PrivateMessages','InstantForum_SearchResults','InstantForum_Settings','InstantForum_TopicRatings','InstantForum_TopicSubscriptions',
		'InstantForum_Topics','InstantForum_TopicsRead','InstantForum_UserLevels','InstantForum_Users','InstantForum_WhosOn'
	);


	function InstantForum_000()
	{
	}


	/**
	* Parses and custom HTML for InstantForum
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function InstantForum_html($text)
	{
		// Font
		$text = preg_replace('#\<font(.*)\>#siU', '', $text);

		$text = str_replace('</P>', '', $text);
		$text = str_replace('[hr]', '', $text);
		$text = str_replace('</FONT>', '', $text);


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
	function get_InstantForum_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantASP_Users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							Username
					FROM {$tableprefix}InstantASP_Users WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}InstantASP_Users ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[UserID]"] = $user['Username'];
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
	function get_InstantForum_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_Attachments");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	AttachmentID,
							UserID,
							Filename,
							AttachmentBLOB,
							ContentLength,
							Views,
							DateStamp,
							ContentType
					FROM {$tableprefix}InstantForum_Attachments WHERE AttachmentID
						IN(SELECT TOP {$per_page} AttachmentID
							FROM (SELECT TOP {$internal} AttachmentID FROM {$tableprefix}InstantForum_Attachments ORDER BY AttachmentID)
						A ORDER BY AttachmentID DESC)
					ORDER BY AttachmentID";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[AttachmentID]"] = $detail;
				$post_id = $Db_object->query_first("SELECT PostID FROM {$tableprefix}InstantForum_AttachmentsPosts WHERE AttachmentID=" . $detail['AttachmentID']);

				$return_array["$detail[AttachmentID]"]['PostID'] = $post_id['PostID'];
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
	function get_InstantForum_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_Forums");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	ForumID,
							ParentID,
							Name,
							Description,
							TotalTopics,
							TotalPosts,
							SortOrder
					FROM {$tableprefix}InstantForum_Forums WHERE ForumID
						IN(SELECT TOP {$per_page} ForumID
							FROM (SELECT TOP {$internal} ForumID FROM {$tableprefix}InstantForum_Forums WHERE IsCategory=0 ORDER BY ForumID)
						A ORDER BY ForumID DESC)
						AND IsCategory=0
					ORDER BY ForumID";

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ForumID]"] = $detail;
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
	function get_InstantForum_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_PrivateMessages");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	PrivateMessageID,
							AuthorID,
							RecipientID,
							Title,
							CAST([Message] as TEXT) as Message,
							DateStamp,
							ReadFlag
					FROM {$tableprefix}InstantForum_PrivateMessages WHERE PrivateMessageID
						IN(SELECT TOP {$per_page} PrivateMessageID
							FROM (SELECT TOP {$internal} PrivateMessageID FROM {$tableprefix}InstantForum_PrivateMessages ORDER BY PrivateMessageID)
						A ORDER BY PrivateMessageID DESC)
					ORDER BY PrivateMessageID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[PrivateMessageID]"] = $detail;
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
	function get_InstantForum_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_Topics");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	PostID,
							TopicID,
							ForumID,
							UserID,
							Title,
							Views,
							IsPinned,
							Replies,
							IPAddress
						FROM {$tableprefix}InstantForum_Topics WHERE PostID
						IN(SELECT TOP {$per_page} PostID
							FROM (SELECT TOP {$internal} PostID FROM {$tableprefix}InstantForum_Topics ORDER BY PostID)
						A ORDER BY PostID DESC)
					ORDER BY PostID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[PostID]"] = $detail;

				$sql_1 = "SELECT CAST([Message] as TEXT) as Message, DateStamp FROM {$tableprefix}InstantForum_Messages WHERE PostID = " . $detail['PostID'];

				$post_details = $Db_object->query_first($sql_1);

				$return_array["$detail[PostID]"]['Message']		= $post_details['Message'];
				$return_array["$detail[PostID]"]['DateStamp'] 	= $post_details['DateStamp'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
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
	function get_InstantForum_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_Topics WHERE PostID=TopicID");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	TopicID,
							PostID,
							ForumID,
							UserID,
							Title,
							Views,
							DateStamp,
							IsPinned,
							Replies,
							IsLocked
						FROM {$tableprefix}InstantForum_Topics WHERE
						PostID	IN(SELECT TOP {$per_page} PostID
							FROM (SELECT TOP {$internal} PostID FROM {$tableprefix}InstantForum_Topics WHERE PostID=TopicID ORDER BY PostID)
						A ORDER BY PostID DESC)
						AND PostID=TopicID
					ORDER BY PostID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[PostID]"] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_InstantForum_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mssql')
		{
			$details_list = $Db_object->query("SELECT ForumID, Name, Description, SortOrder FROM {$tableprefix}InstantForum_Forums WHERE IsCategory=1");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ForumID]"] = $detail;
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
	function get_InstantForum_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_Users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							PermissionID,
							WebAddress,
							AvatarURL,
							MSN,
							Yahoo,
							ICQ,
							AIM,
							Location,
							Occupation,
							CAST([Interests] as TEXT) as Interests,
							CAST([Biography] as TEXT) as Biography,
							DOBDay,
							DOBMonth,
							DOBYear,
							PostCount,
							CAST([PostSignature] as TEXT) as PostSignature
					FROM {$tableprefix}InstantForum_Users WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}InstantForum_Users ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[UserID]"] = $user;

				$sql_1 = "SELECT EmailAddress, Password, Username, IPAddress, TimeZoneOffset, CreatedDate, LastLoginDate FROM {$tableprefix}InstantASP_Users WHERE UserID = " . $user['UserID'];

				$core_user = $Db_object->query_first($sql_1);

				$return_array["$user[UserID]"]['EmailAddress'] 		= $core_user['EmailAddress'];
				$return_array["$user[UserID]"]['Password'] 			= $core_user['Password'];
				$return_array["$user[UserID]"]['Username'] 			= $core_user['Username'];
				$return_array["$user[UserID]"]['IPAddress'] 		= $core_user['IPAddress'];
				$return_array["$user[UserID]"]['TimeZoneOffset'] 	= $core_user['TimeZoneOffset'];
				$return_array["$user[UserID]"]['CreatedDate'] 		= $core_user['CreatedDate'];
				$return_array["$user[UserID]"]['LastLoginDate'] 	= $core_user['LastLoginDate'];
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
	function get_InstantForum_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}InstantForum_PermissionSets");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	PermissionID,
							PermissionName
					FROM {$tableprefix}InstantForum_PermissionSets WHERE PermissionID
						IN(SELECT TOP {$per_page} PermissionID
							FROM (SELECT TOP {$internal} PermissionID FROM {$tableprefix}InstantForum_PermissionSets ORDER BY PermissionID)
						A ORDER BY PermissionID DESC)
					ORDER BY PermissionID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[PermissionID]"] = $user;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


} // Class end
# Autogenerated on : February 12, 2006, 4:05 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
