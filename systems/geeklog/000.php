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
* geeklog API module
*
* @package			ImpEx.geeklog
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class geeklog_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.3.10';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Geeklog';
	var $_homepage 	= 'http://www.geeklog.net/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'access', 'article_images', 'blocks', 'cb_chatterlog', 'cb_userprefs', 'commentcodes', 'commentmodes', 'comments',
		'cookiecodes', 'dateformats', 'events', 'eventsubmission', 'featurecodes', 'features', 'forum_banned_ip',
		'forum_categories', 'forum_forums', 'forum_log', 'forum_moderators', 'forum_settings', 'forum_topic', 'forum_userinfo',
		'forum_userprefs', 'forum_watch', 'frontpagecodes', 'group_assignments', 'groups', 'links', 'linksubmission', 'maillist',
		'personal_events', 'plugins', 'pollanswers', 'pollquestions', 'pollvoters', 'postmodes', 'sessions', 'sortcodes',
		'speedlimit', 'staticpage', 'statuscodes', 'stories', 'storysubmission', 'syndication', 'topics', 'tzcodes',
		'usercomment', 'userindex', 'userinfo', 'userprefs', 'users', 'vars'
	);


	function geeklog_000()
	{
	}


	/**
	* Parses and custom HTML for geeklog
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function geeklog_html($text)
	{
		// Quotes
		$text = preg_replace('#\[QUOTE BY= (.*)\]#siU', '[quote=$1]', $text);

		// SIMILIES - vBulletin ones
		$text = str_replace('[img]images/smilies/wink.gif[/img]', ';)', $text);
		$text = str_replace('[img]images/smilies/biggrin.gif[/img]', ':D', $text);
		$text = str_replace('[img]images/smilies/surprised.gif[/img]', ':eek:', $text);
		$text = str_replace('[img]images/smilies/frown.gif[/img]', ':(', $text);
		$text = str_replace('[img]images/smilies/confused.gif[/img]', ':confused:', $text);

		// SIMILIES - have to create afterwards
		$text = preg_replace('#\[img\]images/smilies/(.*).gif\[/img\]#siU', ':$1:', $text);

		// Fonts
		$text = preg_replace('#<FONT color="(.*)">(.*)</FONT>#siU', '[color=$1]$2[/color]', $text);

		// Then clean up the non matching ones ......... naughty little things ......
		$text = preg_replace('#<FONT color="(.*)">#siU', '', $text);

		// random
		$text = preg_replace('#<SPAN(.*)>#siU', '', $text);
		$text = preg_replace('#<pre(.*)>#siU', '', $text);
		$text = preg_replace('#<font(.*)>#siU', '', $text);

		// Last chance
		$text = str_replace('<u>', '', $text);
		$text = preg_replace('#</(.*)>#siU', '', $text);
		$text = preg_replace('#</(.*)>#siU', '', $text);

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
	function get_geeklog_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT uid,username
			FROM " . $tableprefix . "users
			ORDER BY uid
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user['username'];
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
	function get_geeklog_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_forums
			ORDER BY forum_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[forum_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_geeklog_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();



		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_categories
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
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
	function get_geeklog_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_moderators
			ORDER BY mod_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[mod_id]"] = $detail;
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
	function get_geeklog_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_topic
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
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
	function get_geeklog_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_topic
			WHERE pid = 0
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
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
	function get_geeklog_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."users
			ORDER BY uid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$bio = $Db_object->query_first("SELECT about, lastlogin FROM ".$tableprefix."userinfo WHERE uid=" . $detail['uid']);

				$return_array["$detail[uid]"] 				= $detail;
				$return_array["$detail[uid]"]['bio'] 		= $bio['about'];
				$return_array["$detail[uid]"]['lastlogin'] 	= $bio['lastlogin'];
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
	function get_geeklog_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."groups
			ORDER BY grp_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[grp_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_geek_usersgroups(&$Db_object, &$databasetype, &$tableprefix, &$user_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($user_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."group_assignments
			WHERE `ug_uid` = {$user_id}
			ORDER BY `ug_main_grp_id` ASC
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail['ug_main_grp_id'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_geek_thread_id(&$Db_object, &$databasetype, &$tableprefix, $parent_id)
	{
		// Check that there is not a empty value
		if(empty($parent_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{

			$sql = "SELECT id, pid FROM " .	$tableprefix . "forum_topic	WHERE id = {$parent_id}";

			$id = $Db_object->query_first($sql);

			if ($id['pid'] === '0')
			{
				return $id['id'];
			}
			else
			{
				return $this->get_geek_thread_id($Db_object, $databasetype, $tableprefix, $id['pid']);
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : December 3, 2004, 2:46 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
