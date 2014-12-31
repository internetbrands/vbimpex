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
* deluxeportal API module
*
* @package			ImpEx.deluxeportal
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class deluxeportal_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.0';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Deluxe Portal';
	var $_homepage 	= 'http://www.deluxeportal.net/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'adminlog', 'announcement', 'article', 'attachment', 'buddylist', 'changergroups', 'config',
		'customfield', 'defaultgroups', 'download', 'downloadcategory', 'downloadpermissions', 'dpcode',
		'emailgroups', 'emailverify', 'faq', 'faqcategory', 'faqitem', 'forum', 'forumpermission', 'grouprule',
		'groups', 'icon', 'ignorelist', 'imagestore', 'iplock', 'link', 'linkcategory', 'linkpermissions',
		'markread', 'moderator', 'moderatorlog', 'parsedtemplate', 'passwordreminder', 'poll', 'post',
		'privatemessage', 'search', 'searchresult', 'section', 'sectionpermissions', 'session', 'smilie',
		'style', 'stylecss', 'styleset', 'subscribedemail', 'subscribedforum', 'subscribedthread', 'task',
		'tasklog', 'template', 'templateset', 'thread', 'title', 'topic', 'topicpermissions', 'user',
		'usercustomfield', 'whovoted'
	);


	function deluxeportal_000()
	{
	}


	function deluxeportal_html($text)
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
	function get_deluxeportal_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT userid,name
			FROM " . $tableprefix . "user
			ORDER BY userid
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[userid]"] = $user['name'];
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
	function get_deluxeportal_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachment
			ORDER BY postid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array[] = $detail;
			}
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
	function get_deluxeportal_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum
			WHERE parentid > 0
			ORDER BY forumid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[forumid]"] = $detail;
			}
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
	function get_deluxeportal_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum
			WHERE parentid = 0
			ORDER BY forumid
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[forumid]"] = $detail;
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
	function get_deluxeportal_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."moderator
			ORDER BY moderatorid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[moderatorid]"] = $detail;
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
	function get_deluxeportal_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."privatemessage
			ORDER BY privatemessageid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[privatemessageid]"] = $detail;
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
	function get_deluxeportal_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$thread_sql="SELECT threadid, name,  poll_multiple FROM " .
			$tableprefix."thread
			WHERE poll != ''
			LIMIT " .
			$start_at .
			"," .
			$per_page;

			$threads_list = $Db_object->query($thread_sql);

			while ($thread = $Db_object->fetch_array($threads_list))
			{
				$sql = "
				SELECT * FROM " .
				$tableprefix."poll
				WHERE threadid =" . $thread['threadid'] . "
				ORDER BY ordered
				";

				$details_list = $Db_object->query($sql);

				while ($detail = $Db_object->fetch_array($details_list))
				{
						$return_array["$thread[threadid]"]["$detail[ordered]"] = $detail;
				}
				$return_array["$thread[threadid]"]['question'] = $thread['name'];
				$return_array["$thread[threadid]"]['multiple'] = $thread['poll_multiple'];
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
	function get_deluxeportal_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."post
			ORDER BY postid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[postid]"] = $detail;
			}
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
	function get_deluxeportal_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smilie
			ORDER BY smilieid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[smilieid]"] = $detail;
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
	function get_deluxeportal_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."thread
			ORDER BY threadid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[threadid]"] = $detail;
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
	function get_deluxeportal_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."user
			ORDER BY userid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[userid]"] = $detail;
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
	function get_deluxeportal_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."groups
			ORDER BY groupid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[groupid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_deluxeportal_attachment_post_details(&$Db_object, &$databasetype, &$tableprefix, &$delux_postid)
	{


		// Check that there is not a empty value
		if(empty($delux_postid)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT postid, userid, dateline FROM " .
			$tableprefix."post
			WHERE importpostid= {$delux_postid}";

echo "<h1>delux '" . $delux_postid . "'</h1>";
			$details_list = $Db_object->query_first($sql);
		}
		else
		{
			return false;
		}
		return $details_list;
	}

} // Class end
# Autogenerated on : September 11, 2004, 2:50 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
