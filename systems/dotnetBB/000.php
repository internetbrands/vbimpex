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
* dotnet API module
*
* @package			ImpEx.dotnet
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class dotnetBB_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.42';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'dotnetBB';
	var $_homepage 	= 'http://www.dotnetbb.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'ActiveVisiting', 'AdminActions', 'AdminCategory', 'AdminMenuAccess', 'AdminMenuTitles', 'AvatarGallery',
		'CalendarDetail', 'CalendarEvent', 'EmailBan', 'EmailMessages', 'EmailNotify', 'Emoticons', 'FileAttachments',
		'FilterWords', 'ForumCategories', 'Forums', 'ForumSubscribe', 'GroupMembers', 'Groups', 'Ignored', 'ImageThumbs',
		'IPBan', 'MailConfirm', 'Messages', 'MessagesRead', 'Moderators', 'ModMailer', 'PollQs', 'PollVs', 'PrivateAccess',
		'PrivateMessage', 'Profiles', 'SearchIndexPost', 'SearchIndexTitle', 'SearchWords', 'Stats', 'Titles', 'UploadAvatars',
		'UserExperience'
	);


	function dotnetBB_000()
	{
	}


	/**
	* Parses and custom HTML for dotnet
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function dotnetBB_html($text)
	{
		$text = str_replace('<DIV class=msgQuoteWrap>', '[quote]', $text);
		$text = str_replace('</QUOTE>', '[/quote]', $text);
		
		$text = preg_replace('#<DIV(.*)>#siU', '', $text);
		$text = preg_replace('#<hr(.*)>#siU', '', $text);
		
		$text = str_replace('</DIV>', '', $text);

		$text = str_replace('</U>', '', $text);
		$text = str_replace('<U>', '', $text);
		$text = str_replace('&quot;', "'", $text);
		
		$text = preg_replace('#<FONT(.*)>#siU', '', $text);
		$text = str_replace('</FONT>', '', $text);
		
		$text = preg_replace('#<T(.*)>#siU', '', $text);
		$text = preg_replace('#</T(.*)>#siU', '', $text);
		
		$text = preg_replace('#<P(.*)>#siU', '', $text);
		$text = str_replace('</p>', '', $text);
		$text = str_replace('</P>', '', $text);	
		
		$text = preg_replace('#<SPAN(.*)>#siU', '', $text);
		$text = preg_replace('#</S(.*)>#siU', '', $text);
		
		$text = str_replace('<UL>', '[list]', $text);
		$text = str_replace('<LI>', '[*]', $text);
		$text = str_replace('</LI>', '', $text);
		
		$text = preg_replace('#<CODE>(.*)</CODE>#siU', '[code]$1[/code]', $text);
		
		$text = str_replace('</PRE>', '', $text);
		
		$text = preg_replace('#<H(.*)>#siU', '', $text);
		$text = preg_replace('#</H(.*)>#siU', '', $text);
		
		$text = str_replace('<o:p>', '', $text);
		$text = str_replace('</o>', '', $text);
		
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&gt;', '>', $text);
		
		$text = preg_replace('#<B(.*)>#siU', '[b]', $text);
		$text = preg_replace('#<\?xml(.*)>#siU', '', $text);
		
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
	function get_dotnetBB_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Profiles");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							UserName
					FROM {$tableprefix}Profiles WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}Profiles ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[UserID]"] = $user['UserName'];
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
	function get_dotnetBB_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{		
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}FileAttachments");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	AttachID,
							AttachGUID,
							UserID,
							PostDate,
							FileBinary,
							AttachFileName,
							DownloadCount
					FROM {$tableprefix}FileAttachments WHERE AttachID
						IN(SELECT TOP {$per_page} AttachID
							FROM (SELECT TOP {$internal} AttachID FROM {$tableprefix}FileAttachments ORDER BY AttachID)
						A ORDER BY AttachID DESC)
					ORDER BY AttachID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[AttachID]"]['attachid'] 		= $detail['AttachID'];
				$return_array["$detail[AttachID]"]['userid']		= $detail['UserID'];
				$return_array["$detail[AttachID]"]['postdate']		= $detail['PostDate'];
				$return_array["$detail[AttachID]"]['file']			= $detail['FileBinary'];
				$return_array["$detail[AttachID]"]['filename']		= $detail['AttachFileName'];
				$return_array["$detail[AttachID]"]['downloads']		= $detail['DownloadCount'];
				
				// get the propper postid and not the GUID rubbish
				$sql = "SELECT {$tableprefix}Messages.MessageID
						FROM {$tableprefix}Messages INNER JOIN
						{$tableprefix}FileAttachments ON {$tableprefix}Messages.AttachGUID = {$tableprefix}FileAttachments.AttachGUID AND {$tableprefix}FileAttachments.AttachID = " . $detail['AttachID'];
					  
				$postid = $Db_object->query_first($sql);
				$return_array["$detail[AttachID]"]['postid'] = $postid['MessageID'];
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
	function get_dotnetBB_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
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
							ForumName,
							ForumDesc,
							CategoryID,
							TotalPosts,
							TotalTopics,
							LastPostDate,
							ForumOrder,
							IsPrivate
					FROM {$tableprefix}Forums WHERE ForumID
						IN(SELECT TOP {$per_page} ForumID
							FROM (SELECT TOP {$internal} ForumID FROM {$tableprefix}Forums ORDER BY ForumID)
						A ORDER BY ForumID DESC)
					ORDER BY ForumID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ForumID]"]['title'] 			= $detail['ForumName'];
				$return_array["$detail[ForumID]"]['description']	= $detail['ForumDesc'];
				$return_array["$detail[ForumID]"]['catid']			= $detail['CategoryID'];
				$return_array["$detail[ForumID]"]['posts']			= $detail['TotalPosts'];
				$return_array["$detail[ForumID]"]['threads']		= $detail['TotalTopics'];
				$return_array["$detail[ForumID]"]['lastpostdate']	= $detail['LastPostDate'];
				$return_array["$detail[ForumID]"]['order']			= $detail['ForumOrder'];
				$return_array["$detail[ForumID]"]['private']		= $detail['IsPrivate'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	
	function get_dotnetBB_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{	
		$return_array = array();

		if ($databasetype == 'mssql')
		{
			$sql = "SELECT 	CategoryID,
							CategoryName,
							CategoryOrder,
							CategoryDesc
					FROM {$tableprefix}ForumCategories";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[CategoryID]"]['name']		= $detail['CategoryName'];
				$return_array["$detail[CategoryID]"]['description']	= $detail['CategoryDesc'];
				$return_array["$detail[CategoryID]"]['position']	= $detail['CategoryOrder'];
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
	function get_dotnetBB_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
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
	function get_dotnetBB_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."pmtext
			ORDER BY pmtext_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[pmtext_id]"] = $detail;
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
	function get_dotnetBB_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."poll
			ORDER BY poll_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[poll_id]"] = $detail;
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
	function get_dotnetBB_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
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

			$sql = "SELECT 	MessageID,
							ForumID,
							MessageTitle,
							CAST([MessageText] as TEXT) as body,
							PostDate,
							ParentMsgID,
							PostIPAddr,
							UserGUID
					FROM {$tableprefix}Messages WHERE MessageID
						IN(SELECT TOP {$per_page} MessageID
							FROM (SELECT TOP {$internal} MessageID FROM {$tableprefix}Messages ORDER BY MessageID)
						A ORDER BY MessageID DESC)
					ORDER BY MessageID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[MessageID]"]['forumid']		= $detail['ForumID'];
				$return_array["$detail[MessageID]"]['title']		= $detail['MessageTitle'];
				$return_array["$detail[MessageID]"]['body']			= $detail['body'];
				$return_array["$detail[MessageID]"]['threadid']		= $detail['ParentMsgID'];
				$return_array["$detail[MessageID]"]['ipaddress']	= $detail['PostIPAddr'];
				$return_array["$detail[MessageID]"]['dateline']		= $detail['PostDate'];

				// Get the userid
				$sql="SELECT {$tableprefix}Profiles.UserID
					  FROM {$tableprefix}Profiles INNER JOIN
					  {$tableprefix}Messages ON {$tableprefix}Profiles.UserGUID = {$tableprefix}Messages.UserGUID AND {$tableprefix}Messages.MessageID = " . $detail['MessageID'];
				
				$userid = $Db_object->query_first($sql);
				
				$return_array["$detail[MessageID]"]['userid']	= $userid['UserID'];
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
	function get_dotnetBB_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smilie
			ORDER BY smilie_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[smilie_id]"] = $detail;
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
	function get_dotnetBB_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Messages WHERE IsParentMsg = 1");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	MessageID,
							ForumID,
							MessageTitle,
							PostDate,
							TotalReplies,
							TotalViews,
							IsSticky,
							TopicLocked,
							UserGUID
					FROM {$tableprefix}Messages WHERE IsParentMsg = 1 AND MessageID
						IN(SELECT TOP {$per_page} MessageID
							FROM (SELECT TOP {$internal} MessageID FROM {$tableprefix}Messages WHERE IsParentMsg = 1 ORDER BY MessageID)
						A ORDER BY MessageID DESC)
					ORDER BY MessageID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[MessageID]"]['forumid']		= $detail['ForumID'];
				$return_array["$detail[MessageID]"]['title']		= $detail['MessageTitle'];
				$return_array["$detail[MessageID]"]['dateline']		= $detail['PostDate'];
				$return_array["$detail[MessageID]"]['replies']		= $detail['TotalReplies'];
				$return_array["$detail[MessageID]"]['views']		= $detail['TotalViews'];
				$return_array["$detail[MessageID]"]['sticky']		= $detail['IsSticky'];
				$return_array["$detail[MessageID]"]['TopicLocked']	= $detail['locked'];
				
				// Get the userid
				$sql="SELECT {$tableprefix}Profiles.UserID
					  FROM {$tableprefix}Profiles INNER JOIN
					  {$tableprefix}Messages ON {$tableprefix}Profiles.UserGUID = {$tableprefix}Messages.UserGUID AND {$tableprefix}Messages.MessageID = " . $detail['MessageID'];

				$userid = $Db_object->query_first($sql);
				
				$return_array["$detail[MessageID]"]['userid']	= $userid['UserID'];
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
	function get_dotnetBB_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Profiles");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserID,
							UserName,
							DisplayName,
							euPassword,
							EmailAddress,
							Homepage,
							AIMName
							ICQNumber,
							TimeOffset,
							CAST([EditableSignature] as TEXT) as signature,
							CreateDate,
							TotalPosts,
							LastPostDate,
							LastLoginDate,
							MSNM,
							YPager,
							PMPopUp,
							HomeLocation,
							Occupation,
							CAST([Interests] as TEXT) as intrests,
							UserTitle,
							DateOfBirth
					FROM {$tableprefix}Profiles WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}Profiles ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[UserID]"]['username'] 	= $detail['UserName'];
				$return_array["$detail[UserID]"]['displayname']	= $detail['DisplayName'];
				$return_array["$detail[UserID]"]['password']	= $detail['euPassword'];
				$return_array["$detail[UserID]"]['email']		= $detail['EmailAddress'];
				$return_array["$detail[UserID]"]['homepage']	= $detail['Homepage'];
				$return_array["$detail[UserID]"]['aim']			= $detail['AIMName'];
				$return_array["$detail[UserID]"]['icq']			= $detail['ICQNumber'];
				$return_array["$detail[UserID]"]['timeoffset']	= $detail['TimeOffset'];
				$return_array["$detail[UserID]"]['signature'] 	= $detail['signature'];
				$return_array["$detail[UserID]"]['createdate']	= $detail['CreateDate'];
				$return_array["$detail[UserID]"]['lastpost']	= $detail['LastPostDate'];
				$return_array["$detail[UserID]"]['lastlogin'] 	= $detail['LastLoginDate'];
				$return_array["$detail[UserID]"]['msn']			= $detail['MSNM'];
				$return_array["$detail[UserID]"]['location']	= $detail['HomeLocation'];
				$return_array["$detail[UserID]"]['occupation']	= $detail['Occupation'];
				$return_array["$detail[UserID]"]['intrests'] 	= $detail['intrests'];
				$return_array["$detail[UserID]"]['usertitle']	= $detail['UserTitle'];
				$return_array["$detail[UserID]"]['dob']			= $detail['DateOfBirth'];
				$return_array["$detail[UserID]"]['yahoo']		= $detail['YPager'];
				$return_array["$detail[UserID]"]['posts']		= $detail['TotalPosts'];
				$return_array["$detail[UserID]"]['pmpopup']		= $detail['PMPopUp'];
				
				
				// Get the usergroups.
				$usergroup_list = $Db_object->query("SELECT GroupID FROM {$tableprefix}GroupMembers WHERE UserID = " . $detail['UserID']);

				while ($usergroup = $Db_object->fetch_array($usergroup_list))
				{
					$user_usergroups[] = $usergroup['GroupID'];
				}
				
				$return_array["$detail[UserID]"]['usergroups']	= $user_usergroups;
				unset($user_usergroups);
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
	function get_dotnetBB_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Groups");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	GroupID,
							GroupName
					FROM {$tableprefix}Groups WHERE GroupID
						IN(SELECT TOP {$per_page} GroupID
							FROM (SELECT TOP {$internal} GroupID FROM {$tableprefix}Groups ORDER BY GroupID)
						A ORDER BY GroupID DESC)
					ORDER BY GroupID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[GroupID]"] = $detail['GroupName'];
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
# Autogenerated on : March 20, 2005, 6:30 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
