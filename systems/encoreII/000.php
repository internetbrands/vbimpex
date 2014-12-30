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
* encoreII API module
*
* @package			ImpEx.encoreII
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class encoreII_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2';


	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'EncoreII';
	var $_homepage 	= 'http://www.example.com';
	var $_tier = '3';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'Announcement', 'Broadcast', 'ChatMessage', 'ChatRoom', 'ChatUser', 'Choice', 'Issue', 'Message',
		'MessageReport', 'MessageStatus', 'Newsletter', 'Poll', 'PrivateMessage', 'PrivateMessageReceive',
		'PrivateMessageSend', 'Subscription', 'Subtopic', 'SubtopicStatus', 'SubtopicSubscription',
		'SystemBackIssueFloodProtect', 'SystemBlanketBan', 'SystemCustomise', 'SystemEmail', 'SystemFloodProtect',
		'SystemIPBan', 'SystemRatingFloodProtect', 'SystemSession', 'SystemVoteFloodProtect', 'SystemWhosOnline',
		'Theme', 'Tip', 'Topic', 'TopicAccess', 'TopicCategory', 'UserGroup', 'UserProfile', 'UserStatus'
	);


	function encoreII_000()
	{
	}


	/**
	* Parses and custom HTML for encoreII
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function encoreII_html($text)
	{
		return $text;
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
	function get_encoreII_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Topic
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ID]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_encoreII_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."TopicCategory
			ORDER BY ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[ID]"] = $detail;
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
	function get_encoreII_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Message
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$i = $detail['ID'] +1;
				$post_details = $Db_object->query_first("SELECT * FROM ".$tableprefix."MessageStatus WHERE ID ='" . str_pad($detail['ID'], 8, "0", STR_PAD_LEFT) . "'");

				// got to get the rest of the detals
				$return_array[$i] = $detail;

				$return_array[$i]['DatePosted']		= $post_details['DatePosted'];
				$return_array[$i]['TimePosted']		= $post_details['TimePosted'];
				$return_array[$i]['SubtopicID']		= $post_details['SubtopicID'];
				$return_array[$i]['InReplyTo']		= intval($post_details['InReplyTo']);
				$return_array[$i]['Approved']		= $post_details['Approved'];
				$return_array[$i]['DateLastEdited']	= $post_details['DateLastEdited'];
				$return_array[$i]['TimeLastEdited']	= $post_details['TimeLastEdited'];
				$return_array[$i]['UserLastEdited']	= $post_details['UserLastEdited'];

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
	function get_encoreII_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Subtopic
			ORDER BY ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$i++;
				$thread_details = $Db_object->query_first("SELECT * FROM ".$tableprefix."SubtopicStatus WHERE ID ='" . str_pad($detail['ID'], 8, "0", STR_PAD_LEFT) . "'");

				$return_array[$i] = $detail;
				$return_array[$i]['DatePosted']		= $thread_details['DatePosted'];
				$return_array[$i]['TimePosted']		= $thread_details['TimePosted'];
				$return_array[$i]['Type']			= $thread_details['Type'];
				$return_array[$i]['Views']			= $thread_details['Views'];
				$return_array[$i]['NumOfPosts']		= $thread_details['NumOfPosts'];
				$return_array[$i]['TopicID']		= $thread_details['TopicID'];
				$return_array[$i]['DateLastPost']	= $thread_details['DateLastPost'];
				$return_array[$i]['TimeLastPost']	= $thread_details['TimeLastPost'];
				$return_array[$i]['UserLastPost']	= $thread_details['UserLastPost'];
				$return_array[$i]['NameLastPost']	= $thread_details['NameLastPost'];
				$return_array[$i]['PublicNotes']	= $thread_details['PublicNotes'];
				$return_array[$i]['PrivateNotes']	= $thread_details['PrivateNotes'];
				$return_array[$i]['Approved']		= $thread_details['Approved'];
				$return_array[$i]['Condemned']		= $thread_details['Condemned'];
				$return_array[$i]['Locked']			= $thread_details['Locked'];
				$return_array[$i]['Persistent']		= $thread_details['Persistent'];
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
	function get_encoreII_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		$i=1;

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."UserProfile
			WHERE Email != ''
			ORDER BY Username
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$i++;
				$profile_details = $Db_object->query_first("SELECT * FROM ".$tableprefix."UserStatus WHERE Username ='".$detail['Username']."'");

				$return_array[$i] = $detail;
				$return_array[$i]['UserGroup']		= $profile_details['UserGroup'];
				$return_array[$i]['Notes']			= $profile_details['Notes'];
				$return_array[$i]['Locked']			= $profile_details['Locked'];
				$return_array[$i]['Deleted']		= $profile_details['Deleted'];
				$return_array[$i]['DateLastPost']	= $profile_details['DateLastPost'];
				$return_array[$i]['TimeLastPost']	= $profile_details['TimeLastPost'];
				$return_array[$i]['DateLastVisit']	= $profile_details['DateLastVisit'];
				$return_array[$i]['TimeLastVisit']	= $profile_details['TimeLastVisit'];
				$return_array[$i]['NumOfPosts']		= $profile_details['NumOfPosts'];
				$return_array[$i]['SignUpDate']		= $profile_details['SignUpDate'];
				$return_array[$i]['SignUpTime']		= $profile_details['SignUpTime'];

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
	function get_encoreII_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		$i=1;

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."UserGroup
			ORDER BY GroupName
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array[$i++] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : February 1, 2005, 8:41 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
