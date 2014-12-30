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
* vb4_000
*
* @package 		ImpEx.vb4
* @date 		$Date: 2009-03-06 13:54:53 -0800 (Fri, 06 Mar 2009) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class vb4_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '4.0.x - 4.1.x';
	var $_tier = '2';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'vBulletin';
	var $_homepage 	= 'http://www.vbulletin.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
						'access', 'adminhelp', 'administrator', 'adminlog', 'adminutil', 'announcement', 'attachment',
						'attachmenttype', 'attachmentviews', 'avatar', 'bbcode', 'calendar', 'calendarcustomfield',
						'calendarmoderator', 'calendarpermission', 'cron', 'cronlog', 'customavatar', 'customprofilepic',
						'datastore', 'deletionlog', 'editlog', 'event', 'faq', 'filedata', 'forum', 'forumpermission', 'holiday',
						'icon', 'imagecategory', 'imagecategorypermission', 'language', 'mailqueue', 'moderation', 'moderator',
						'moderatorlog', 'passwordhistory', 'phrase', 'phrasetype', 'pm', 'pmreceipt', 'pmtext', 'poll',
						'pollvote', 'post', 'postparsed', 'posthash', 'profilefield', 'ranks',
						'reminder', 'reputation', 'reputationlevel', 'session', 'setting', 'settinggroup',
						'smilie', 'stats', 'strikes', 'style', 'subscribeevent', 'subscribeforum', 'subscribethread',
						'subscription', 'subscriptionlog', 'template', 'thread', 'threadrate', 'threadviews', 'upgradelog',
						'user', 'useractivation', 'userban', 'userfield', 'usergroup', 'usergroupleader', 'usergrouprequest',
						'usernote', 'userpromotion', 'usertextfield', 'usertitle', 'cpsession'
					);


	function vb4_000()
	{
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

	function get_vb4_pms(&$Db_object, &$databasetype, &$tableprefix, &$pm_text_id)
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
}
/*======================================================================*/
?>
