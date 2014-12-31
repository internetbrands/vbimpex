<?php
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
* vb_36 API module
*
* @package			ImpEx.vb_36
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class vb_36_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.7.x';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'vBulletin';
	var $_homepage 	= 'http://www.vbulletin.com';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'access', 'adminhelp', 'administrator', 'adminlog', 'adminmessage', 'adminutil', 'announcement', 'announcementread', 'attachment', 'attachmentpermission',
		'attachmenttype', 'attachmentviews', 'avatar', 'bbcode', 'calendar', 'calendarcustomfield', 'calendarmoderator', 'calendarpermission', 'cpsession', 'cron',
		'cronlog', 'customavatar', 'customprofilepic', 'datastore', 'deletionlog', 'editlog', 'event', 'externalcache', 'faq', 'forum', 'forumpermission', 'forumread',
		'holiday', 'icon', 'imagecategory', 'imagecategorypermission', 'infraction', 'infractiongroup', 'infractionlevel', 'language', 'mailqueue',
		'moderation', 'moderator', 'moderatorlog', 'passwordhistory', 'paymentapi', 'paymentinfo', 'paymenttransaction', 'phrase', 'phrasetype', 'plugin', 'pm',
		'pmreceipt', 'pmtext', 'podcast', 'poll', 'pollvote', 'post', 'posthash', 'postindex', 'postparsed', 'product', 'productcode', 'productdependency',
		'profilefield', 'ranks', 'regimage', 'reminder', 'reputation', 'reputationlevel', 'rssfeed', 'rsslog', 'search', 'session', 'setting', 'settinggroup', 'sigparsed',
		'sigpic', 'smilie', 'stats', 'strikes', 'style', 'subscribeevent', 'subscribeforum', 'subscribethread', 'subscription', 'subscriptionlog', 'subscriptionpermission',
		'tachyforumpost', 'tachythreadpost', 'template', 'templatehistory', 'thread', 'threadrate', 'threadread', 'threadredirect', 'threadviews', 'upgradelog', 'user',
		'useractivation', 'userban', 'userfield', 'usergroup', 'usergroupleader', 'usergrouprequest', 'usernote', 'userpromotion', 'usertextfield', 'usertitle', 'word'
	);





	function vb_36_000()
	{
	}


	/**
	* Parses and custom HTML for vb_36
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function vb_36_html($text)
	{
		return $text;
	}


	function get_vb_36_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT userid, username FROM {$tableprefix}user ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[userid]"] = $user['username'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_post_parent_id(&$Db_object, &$databasetype, &$tableprefix, $import_post_id)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT postid FROM " . $tableprefix . "post WHERE importpostid =" . $import_post_id;

			$post_id = $Db_object->query_first($sql);

			return $post_id[0];
		}
		else
		{
			return false;
		}
	}

	function get_thread_id_from_poll_id(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT importthreadid FROM " . $tableprefix . "thread WHERE pollid =" . $poll_id;

			$thread_id = $Db_object->query_first($sql);

			return $thread_id[0];
		}
		else
		{
			return false;
		}
	}

	function update_poll_ids(&$Db_object, &$databasetype, &$tableprefix)
	{
		if ($databasetype == 'mysql')
		{
			$result = $Db_object->query("SELECT pollid, threadid, importthreadid FROM " . $tableprefix . "thread WHERE open=10 AND pollid <> 0 AND importthreadid <> 0");

			while ($thread = $Db_object->fetch_array($result))
			{
				$new_thread_id = $Db_object->query_first("SELECT threadid FROM " . $tableprefix . "thread where importthreadid = ".$thread['pollid']);

				if($new_thread_id['threadid'])
				{
					// Got it
					$Db_object->query("UPDATE " . $tableprefix . "thread SET pollid =" . $new_thread_id['threadid'] . " WHERE threadid=".$thread['threadid']);
				}
				else
				{
					// Why does it miss some ????
				}
			}
		}
		else
		{
			return false;
		}
	}

	function get_vb3_pms(&$Db_object, &$databasetype, &$tableprefix, &$pm_text_id)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$result = $Db_object->query("SELECT * FROM " . $tableprefix . "pm WHERE pmtextid=". $pm_text_id);

			while ($pm = $Db_object->fetch_array($result))
			{
				$return_array["$pm[pmid]"] = $pm;
			}
		}
		else
		{
			return false;
		}

		return $return_array;
	}

	function get_vb_36_phrase_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$phrases = $Db_object->query("SELECT * FROM {$tableprefix}phrase WHERE dateline > 0 ORDER BY phraseid LIMIT {$start} ,{$per_page}");

			while ($phrase = $Db_object->fetch_array($phrases))
			{
				$return_array["$phrase[phraseid]"] = $phrase;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_vb3_poll_voters($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$polevotes = $Db_object->query("SELECT userid, voteoption FROM {$tableprefix}pollvote WHERE pollid={$poll_id} ORDER BY pollvoteid");

			while ($polevote = $Db_object->fetch_array($polevotes))
			{
				$return_array["$polevote[userid]"] = $polevote['voteoption'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
