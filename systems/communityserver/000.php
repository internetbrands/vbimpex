<?php
define(CS21, true);

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
* communityserver API module
*
* @package			ImpEx.communityserver
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class communityserver_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.1';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Community Server';
	var $_homepage 	= 'http://communityserver.org/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	#var $_valid_tables = array (
#		'AnonymousUsers' , 'Emails' , 'ForumGroups' , 'Forums' , 'ForumsRead' , 'Messages' , 'ModerationAction' , 'ModerationAudit' , 'Moderators' , 'Post_Archive' ,
#		'Posts' , 'PostsRead' , 'PrivateForums' , 'ThreadTrackings' , 'UserGTSRequest' , 'UserRoles' , 'Users' , 'UsersInRoles' , 'Vote'
#	);


	var $_valid_tables = array (
	 'Moderators', 'ThemeConfigurationData', 'SectionSubscriptions', 'SearchBarrel', 'Threads', 'ProductPermissions', 'ThemeConfigurationData_TEMP', 'nntp_Posts', 'ServiceSchedule',
	 'PostEditNotes', 'PostMetadata', 'TrackedSections', 'Licenses', 'Censorship', 'DisallowedNames', 'UserProfile', 'Groups', 'nntp_Newsgroups', 'Posts_InCategories',
	 'PostsArchive', 'PrivateMessages', 'Ranks', 'Reports', 'SchemaVersion', 'SearchIgnoreWords', 'SectionPermissions', 'Sections', 'Users', 'Links', 'LinkCategories',
	 'ThreadRating', 'weblog_Weblogs', 'SiteMappings', 'Sites', 'Styles', 'Version', 'Votes', 'VoteSummary', 'Post_Categories_Parents', 'PostRating', 'FavoritePosts',
	 'UserAvatar', 'SectionsRead', 'FavoriteSections', 'FavoriteUsers', 'ForumPingback', 'BlogActivityReport', 'UrlRedirects', 'BannedAddresses', 'AnonymousUsers', 'VisitsDaily',
	 'RollerBlogFeeds', 'Visits', 'BannedNetworks', 'RollerBlogPost', 'Licenses_2.x', 'TrackedThreads', 'RollerBlogUrls', 'Folder', 'PostAttachments', 'FeedState',
	 'PostAttachments_TEMP', 'Feed', 'Post_Categories', 'Messages', 'FolderFeed', 'UserInvitation', 'SiteSettings', 'EventLog', 'Exceptions', 'es_Search_RemoveQueue', 'FeedPost',
	 'EventDescriptions', 'Services', 'SectionTokens', 'UserReadPost', 'statistics_Site', 'statistics_User','InkData', 'ThreadsRead', 'Content', 'posts_deleted_archive', 'RoleQuotas', 'Posts',
	 'ApplicationConfigurationSettings', 'ApplicationType', 'Urls', 'Referrals', 'CodeScheduleType', 'Smilies', 'CodeServiceType', 'PageViews', 'ModerationAction', 'ModerationAudit'
	);


	function communityserver_000()
	{
	}


	/**
	* Parses and custom HTML for communityserver
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function communityserver_html($text)
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
	function get_communityserver_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	UserId,
							UserName
					FROM {$tableprefix}Users WHERE UserId
						IN(SELECT TOP {$per_page} UserId
							FROM (SELECT TOP {$internal} UserId FROM {$tableprefix}Users ORDER BY UserId)
						A ORDER BY UserId DESC)
					ORDER BY UserId";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[UserId]"] = $user['UserName'];
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
	function get_communityserver_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Sections");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
 			
			if (CS21)
			{
				$sql = "SELECT 	SectionID,
								ParentID,
								Name,
								Description,
								DateCreated,
								SortOrder,
								TotalPosts,
								TotalThreads
						FROM {$tableprefix}Sections WHERE ApplicationType=0 AND SectionID
							IN(SELECT TOP {$per_page} SectionID
								FROM (SELECT TOP {$internal} SectionID FROM {$tableprefix}Sections WHERE ApplicationType=0 ORDER BY SectionID)
							A ORDER BY SectionID DESC)
						ORDER BY SectionID";
			}
			else 
			{
				$sql = "with Forums ([level], ForumID, [Name], [Description], ParentID, DateCreated, SortOrder, TotalPosts, TotalThreads) as
						(
						select 0, a.GroupID + 100000 as SectionID, a.Name, a.Description, 0, null, a.SortOrder, 0, 0 from cs_Groups a
						where a.ApplicationType = 0
						union all
						select 1, a.SectionID, a.Name, a.Description, a.GroupID + 100000,
						a.DateCreated, a.SortOrder, a.TotalPosts, a.TotalThreads from cs_Sections a
						where a.ParentID = 0 and a.ApplicationType = 0
						union all
						select b.level + 1, a.SectionID, a.Name, a.Description, a.ParentID, a.DateCreated, a.SortOrder,
						a.TotalPosts, a.TotalThreads from cs_Sections a
						inner join Forums b on b.ForumID = a.ParentID
						where a.ApplicationType = 0
						)
						select row_number() over (order by [level], [ParentID], [SortOrder], TotalPosts) Row, *
						into #tt
						from Forums order by [level], [ParentID], [SortOrder], TotalPosts;
						select * from #tt where Row between {$start_at} and {$internal};
						drop table #tt";
			}
			
			$forum_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forum_list))
			{
				$return_array["$forum[SectionID]"] = $forum;
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
	function get_communityserver_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Posts");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
/*
			$sql = "SELECT 	PostID,
							ThreadID,
							ParentID,
							SortOrder,
							Subject,
							PostDate,
							SectionID,
							UserID,
							PostAuthor,
							PostDate,
							CAST([Body] as TEXT) as Body
					FROM {$tableprefix}Posts WHERE PostID
						IN(SELECT TOP {$per_page} PostID
							FROM (SELECT TOP {$internal} PostID FROM {$tableprefix}Posts ORDER BY PostID)
						A ORDER BY PostID DESC)
					ORDER BY PostID";
*/
			$sql="with PostList as (
					select row_number() over (order by p.PostID) Row,
					p.PostID, p.ThreadID, p.ParentID, p.SortOrder, p.PostDate, p.Subject, p.SectionID ForumID, p.PostAuthor UserName, p.UserID UserID,
					p.IPAddress, t.ThreadDate, cast(p.[Body] as text) Body
					from cs_Posts p
					inner join cs_Threads t on t.ThreadID = p.ThreadID
					inner join cs_Sections s on s.SectionID = p.SectionID
					where s.ApplicationType = 0 )
					select * from PostList
					where Row between {$start_at} and {$internal}
					order by PostID";

			$post_list = $Db_object->query($sql);

			while ($post = $Db_object->fetch_array($post_list))
			{
				$return_array[] = $post;
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
	function get_communityserver_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) AS count FROM {$tableprefix}Threads");

			$internal = $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			/*
			$sql = "SELECT 	ThreadID,
							SectionID,
							PostAuthor,
							ThreadDate,
							IsLocked,
							IsSticky,
							TotalViews,
							TotalReplies
					FROM {$tableprefix}Threads WHERE ThreadID
						IN(SELECT TOP {$per_page} ThreadID
							FROM (SELECT TOP {$internal} ThreadID FROM {$tableprefix}Threads ORDER BY ThreadID)
						A ORDER BY ThreadID DESC)
					ORDER BY ThreadID";
			*/

			$sql = "with ThreadList as (
					select row_number() over (order by p.PostID) Row,
					p.PostID, t.ThreadID, p.ParentID, p.Subject, p.SectionID ForumID, t.TotalViews, t.PostDate ThreadDate,
					t.IsLocked, t.IsSticky IsPinned, p.PostAuthor UserName, p.UserID postuserid, t.TotalReplies
					from cs_Threads t
					inner join cs_Posts p on p.ThreadID = t.ThreadID
					inner join cs_Sections s on s.SectionID = t.SectionID
					where s.ApplicationType = 0 and p.PostLevel = 1 and p.ParentID = p.PostID )
					select * from ThreadList
					where Row between {$start_at} and {$internal}
					order by PostID";

			$thread_list = $Db_object->query($sql);

			while ($thread = $Db_object->fetch_array($thread_list))
			{
				/*
				$subject = $Db_object->query_first("SELECT Subject FROM {$tableprefix}Posts WHERE PostID=" . $thread['ThreadID']);
				$return_array["$thread[ThreadID]"] = $thread;
				$return_array["$thread[ThreadID]"]['Subject'] = $subject['Subject'];
				*/
				$return_array[] = $thread;
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
	function get_communityserver_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
							
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Users");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			if(CS21)
			{
				$sql = "SELECT MembershipID, UserID, LastActivity
						FROM {$tableprefix}Users WHERE UserID
							IN(SELECT TOP {$per_page} UserID
								FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}Users ORDER BY UserID)
							A ORDER BY UserID DESC)
						ORDER BY UserID";
			}
			else
			{
				$sql = "SELECT 	UserName, UserID, Email, LastActivity
					FROM {$tableprefix}Users WHERE UserID
						IN(SELECT TOP {$per_page} UserID
							FROM (SELECT TOP {$internal} UserID FROM {$tableprefix}Users ORDER BY UserID)
						A ORDER BY UserID DESC)
					ORDER BY UserID";
			}


			$user_list = $Db_object->query($sql);
			
			while ($user = $Db_object->fetch_array($user_list))
			{	 
				if(CS21)
				{	
					$return_array["$user[UserID]"] = $user;
					
					$username 	= $Db_object->query_first("SELECT UserName FROM dbo.aspnet_Users WHERE UserID='{$user['MembershipID']}'");
					$password 	= $Db_object->query_first("SELECT Password FROM dbo.aspnet_Membership WHERE UserID='{$user['MembershipID']}'");
					$email 		= $Db_object->query_first("SELECT Email FROM dbo.aspnet_Membership WHERE UserID='{$user['MembershipID']}'");
					$tp 		= $Db_object->query_first("SELECT TotalPosts FROM dbo.{$tableprefix}UserProfile WHERE MembershipID='{$user['MembershipID']}'");
					
					$return_array["$user[UserID]"]['UserName'] = $username[0];										
					$return_array["$user[UserID]"]['Email'] = $email[0];
					$return_array["$user[UserID]"]['Password'] = $password[0];
					$return_array["$user[UserID]"]['TotalPosts'] = $tp[0];
				}
				else 
				{
					$return_array["$user[UserID]"] = $user;
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
} // Class end
# Autogenerated on : July 19, 2006, 11:33 am
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
