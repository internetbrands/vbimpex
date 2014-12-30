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
* webwiz API module
*
* @package			ImpEx.webwiz
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class webwiz_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '9.08';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Web Wiz Forums';
	var $_homepage 	= 'http://www.webwizguide.info/web_wiz_forums/default.asp';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
			'ActiveUser', 'Author', 'BanList', 'BookMark', 'BuddyList', 'Category', 'Configuration',
			'DateTimeFormat', 'EmailNotify', 'Forum', 'Group', 'GuestName', 'PMMessage', 'Permissions',
			'Poll', 'PollChoice', 'Smut', 'Thread', 'Topic'
	);


	function webwiz_000()
	{
	}


	/**
	* Parses and custom HTML for webwiz
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function webwiz_html($text)
	{
		$text = str_replace('&nbsp;',' ',$text);

		$text = str_replace('<U>','[u]',$text);
		$text = str_replace('</U>','[/u]',$text);
		$text = str_replace('<u>','[u]',$text);
		$text = str_replace('</u>','[/u]',$text);


		$text = preg_replace('#<P(.*)>#siU', '', $text);
		$text = preg_replace('#<p(.*)>#siU', '', $text);
		$text = str_replace('</P>','',$text);
		$text = str_replace('</p>','',$text);

		$text = preg_replace('#<FONT(.*)>#siU', '', $text);
		$text = str_replace('</FONT>','',$text);

		$text = preg_replace('#<SPAN(.*)>#siU', '', $text);
		$text = str_replace('</SPAN>','',$text);

		$text = preg_replace('#<edited><editID>(.*)</editID><editDate>(.*)</editDate></edited>#siU', '[b]Edited by: \\1 [/b]', $text);

		$text = preg_replace('#<v:(.*)>#siU', '', $text);

		$text = str_replace('Quote:', '', $text);

		$text = preg_replace('#<\?xml(.*)>#siU', '', $text);
		$text = str_replace('<o:p>', '', $text);
		$text = str_replace('</o:p>', '', $text);

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
	function get_webwiz_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT Author_ID, Username
			FROM " . $tableprefix . "Author
			ORDER BY Author_ID
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Author_ID]"] = $user['Username'];
			}
			return $return_array;
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Author");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Author_ID,
							Username
					FROM {$tableprefix}Author WHERE Author_ID
						IN(SELECT TOP {$per_page} Author_ID
							FROM (SELECT TOP {$internal} Author_ID FROM {$tableprefix}Author ORDER BY Author_ID)
						A ORDER BY Author_ID DESC)
					ORDER BY Author_ID";

			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Author_ID]"] = $user['Username'];
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
	function get_webwiz_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Forum
			ORDER BY Forum_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Forum_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Forum");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Forum_ID,
							Forum_name,
							Forum_Order,
							Cat_ID,
							Forum_description,
							No_of_topics,
							No_of_posts,
							Password
					FROM {$tableprefix}Forum WHERE Forum_ID
						IN(SELECT TOP {$per_page} Forum_ID
							FROM (SELECT TOP {$internal} Forum_ID FROM {$tableprefix}Forum ORDER BY Forum_ID)
						A ORDER BY Forum_ID DESC)
					ORDER BY Forum_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Forum_ID]"]['Forum_ID'] 			= $user['Forum_ID'];
					$return_array["$user[Forum_ID]"]['Forum_name'] 			= $user['Forum_name'];
					$return_array["$user[Forum_ID]"]['Forum_Order'] 		= $user['Forum_Order'];
					$return_array["$user[Forum_ID]"]['Cat_ID'] 				= $user['Cat_ID'];
					$return_array["$user[Forum_ID]"]['Forum_description'] 	= $user['Forum_description'];
					$return_array["$user[Forum_ID]"]['No_of_topics'] 		= $user['No_of_topics'];
					$return_array["$user[Forum_ID]"]['No_of_posts'] 		= $user['No_of_posts'];
					$return_array["$user[Forum_ID]"]['Password'] 			= $user['Password'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the cat_id => cat array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	function get_webwiz_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Category
			ORDER BY Cat_ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Cat_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$sql = "
				SELECT	Cat_ID,
						Cat_Name,
						Cat_Order
				FROM {$tableprefix}Category
				ORDER BY Cat_ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[Cat_ID]"]['Cat_ID']		= $detail['Cat_ID'];
				$return_array["$detail[Cat_ID]"]['Cat_name']	= $detail['Cat_Name'];
				$return_array["$detail[Cat_ID]"]['Cat_order']	= $detail['Cat_Order'];
			}
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
	function get_webwiz_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PMMessage
			ORDER BY PM_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[PM_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PMMessage");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	PM_ID,
							Author_ID,
							PM_Tittle,
							CAST([PM_Message] as TEXT) as PM_Message,
							PM_Message_Date,
							Read_Post,
							Email_notify,
							From_ID
					FROM {$tableprefix}PMMessage WHERE PM_ID
						IN(SELECT TOP {$per_page} PM_ID
							FROM (SELECT TOP {$internal} PM_ID FROM {$tableprefix}PMMessage ORDER BY PM_ID)
						A ORDER BY PM_ID DESC)
					ORDER BY PM_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[PM_ID]"]['PM_ID'] 				= $user['PM_ID'];
					$return_array["$user[PM_ID]"]['Author_ID'] 			= $user['Author_ID'];
					$return_array["$user[PM_ID]"]['From_ID'] 			= $user['From_ID'];
					$return_array["$user[PM_ID]"]['PM_Tittle'] 			= $user['PM_Tittle'];
					$return_array["$user[PM_ID]"]['PM_Message'] 		= $user['PM_Message'];
					$return_array["$user[PM_ID]"]['PM_Message_Date'] 	= $user['PM_Message_Date'];
					$return_array["$user[PM_ID]"]['Read_Post'] 			= $user['Read_Post'];
					$return_array["$user[PM_ID]"]['Email_notify'] 		= $user['Email_notify'];
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
	function get_webwiz_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Poll
			ORDER BY Poll_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[Poll_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Poll");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Poll_ID,
							Poll_question,
							Multiple_votes,
							Reply
					FROM {$tableprefix}Poll WHERE Poll_ID
						IN(SELECT TOP {$per_page} Poll_ID
							FROM (SELECT TOP {$internal} Poll_ID FROM {$tableprefix}Poll ORDER BY Poll_ID)
						A ORDER BY Poll_ID DESC)
					ORDER BY Poll_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Poll_ID]"]['Poll_ID'] 			= $user['Poll_ID'];
					$return_array["$user[Poll_ID]"]['Poll_question'] 	= $user['Poll_question'];
					$return_array["$user[Poll_ID]"]['Multiple_votes'] 	= $user['Multiple_votes'];
					$return_array["$user[Poll_ID]"]['Reply'] 			= $user['Reply'];
					#$return_array["$user[Poll_ID]"]['Author_ID'] 		= $user['Author_ID'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_webwiz_poll_questions(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."PollChoice
			WHERE Poll_ID={$poll_id}
			ORDER BY Choice_ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Choice_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PollChoice");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Choice_ID,
							Choice,
							Votes
					FROM {$tableprefix}PollChoice WHERE Poll_ID={$poll_id}
					ORDER BY Choice_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Choice_ID]"]['Choice_ID']	= $user['Choice_ID'];
					$return_array["$user[Choice_ID]"]['Choice']		= $user['Choice'];
					$return_array["$user[Choice_ID]"]['Votes'] 		= $user['Votes'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_poll_thread_id(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }


		if ($databasetype == 'mysql' OR $databasetype == 'mssql' OR 'odbc')
		{
			$sql = "
			SELECT Topic_ID FROM " .
			$tableprefix."Topic
			WHERE Poll_ID={$poll_id}
			";


			$details_list = $Db_object->query_first($sql);

			return $details_list['Topic_ID'];
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
	function get_webwiz_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Thread
			ORDER BY Thread_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Thread_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Thread");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Thread_ID,
							Topic_ID,
							Author_ID,
							Message_date,
							CAST([Message] as TEXT) as Message,
							Show_signature,
							IP_addr
					FROM {$tableprefix}Thread WHERE Thread_ID
						IN(SELECT TOP {$per_page} Thread_ID
							FROM (SELECT TOP {$internal} Thread_ID FROM {$tableprefix}Thread ORDER BY Thread_ID)
						A ORDER BY Thread_ID DESC)
					ORDER BY Thread_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Thread_ID]"]['Thread_ID'] 			= $user['Thread_ID'];
					$return_array["$user[Thread_ID]"]['Topic_ID'] 			= $user['Topic_ID'];
					$return_array["$user[Thread_ID]"]['Author_ID'] 			= $user['Author_ID'];
					$return_array["$user[Thread_ID]"]['Message_date'] 		= $user['Message_date'];
					$return_array["$user[Thread_ID]"]['Message'] 			= $user['Message'];
					$return_array["$user[Thread_ID]"]['Show_signature'] 	= $user['Show_signature'];
					$return_array["$user[Thread_ID]"]['IP_addr'] 			= $user['IP_addr'];
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
	function get_webwiz_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Topic
			ORDER BY Topic_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Topic_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Topic");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Topic_ID,
							Subject,
							Forum_ID,
							Poll_ID,
							Locked,
							No_of_views
					FROM {$tableprefix}Topic WHERE Topic_ID
						IN(SELECT TOP {$per_page} Topic_ID
							FROM (SELECT TOP {$internal} Topic_ID FROM {$tableprefix}Topic ORDER BY Topic_ID)
						A ORDER BY Topic_ID DESC)
					ORDER BY Topic_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Topic_ID]"]['Topic_ID'] 			= $user['Topic_ID'];
					$return_array["$user[Topic_ID]"]['Subject'] 			= $user['Subject'];
					$return_array["$user[Topic_ID]"]['Forum_ID'] 			= $user['Forum_ID'];
					$return_array["$user[Topic_ID]"]['Poll_ID'] 			= $user['Poll_ID'];
					$return_array["$user[Topic_ID]"]['Locked'] 				= $user['Locked'];
#					$return_array["$user[Topic_ID]"]['Start_date'] 			= $user['Start_date'];
					$return_array["$user[Topic_ID]"]['No_of_views'] 			= $user['No_of_views'];
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
	function get_webwiz_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " . $tableprefix . "Author
			ORDER BY Author_ID
			LIMIT " . $start_at . "," . $per_page;

			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Author_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Author");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	Author_ID,
							Group_ID,
							Username,
							Author_email,
							Password,
							Homepage,
							ICQ,
							AIM,
							Yahoo,
							MSN,
							Join_date,
							Last_visit,
							Time_offset_hours,
							PM_notify,
							DOB,
							Location,
							Interests,
							Occupation,
							CAST([Signature] as TEXT) as Signature
					FROM {$tableprefix}Author WHERE Author_ID
						IN(SELECT TOP {$per_page} Author_ID
							FROM (SELECT TOP {$internal} Author_ID FROM {$tableprefix}Author ORDER BY Author_ID)
						A ORDER BY Author_ID DESC)
					ORDER BY Author_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{

					$return_array["$user[Author_ID]"]['Group_ID'] 			= $user['Group_ID'];
					$return_array["$user[Author_ID]"]['Username'] 			= $user['Username'];
					$return_array["$user[Author_ID]"]['Author_email'] 		= $user['Author_email'];
					$return_array["$user[Author_ID]"]['Password'] 			= $user['Password'];
					$return_array["$user[Author_ID]"]['Homepage'] 			= $user['Homepage'];
					$return_array["$user[Author_ID]"]['ICQ'] 				= $user['ICQ'];
					$return_array["$user[Author_ID]"]['AIM'] 				= $user['AIM'];
					$return_array["$user[Author_ID]"]['Yahoo'] 				= $user['Yahoo'];
					$return_array["$user[Author_ID]"]['MSN'] 				= $user['MSN'];
					$return_array["$user[Author_ID]"]['Join_date'] 			= $user['Join_date'];
					$return_array["$user[Author_ID]"]['Last_visit'] 		= $user['Last_visit'];
					$return_array["$user[Author_ID]"]['Time_offset_hours'] 	= $user['Time_offset_hours'];
					$return_array["$user[Author_ID]"]['PM_notify'] 			= $user['PM_notify'];
					$return_array["$user[Author_ID]"]['DOB'] 				= $user['DOB'];
					$return_array["$user[Author_ID]"]['Location'] 			= $user['Location'];
					$return_array["$user[Author_ID]"]['Interests'] 			= $user['Interests'];
					$return_array["$user[Author_ID]"]['Occupation'] 		= $user['Occupation'];
					$return_array["$user[Author_ID]"]['Signature'] 			= $user['Signature'];
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
	function get_webwiz_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Group
			ORDER BY Group_ID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[Group_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR 'odbc')
		{
			$internal 	= $start + $per_page;

			$sql = "SELECT 	Group_ID,
							Name
					FROM {$tableprefix}Group WHERE Group_ID
						IN(SELECT TOP {$per_page} Group_ID
							FROM (SELECT TOP {$internal} Group_ID FROM {$tableprefix}Group ORDER BY Group_ID)
						A ORDER BY Group_ID DESC)
					ORDER BY Group_ID";

			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[Group_ID]"]['Name'] = $user['Name'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function fix_time($oldtime, $timestamp = true)
	{
		return ($oldtime ? strtotime($oldtime) : false);

		/*
		$newtime['year'] = substr($oldtime, 0,4);
		$newtime['month'] = substr($oldtime, 4,2);
		$newtime['day'] = substr($oldtime, 6,2);
		$newtime['hour'] = substr($oldtime, 8,2);
		$newtime['min'] = substr($oldtime, 10,2);
		$newtime['sec'] = substr($oldtime, 12,2);

		if($timestamp)
		{
			return  mktime ($newtime['hour'], $newtime['min'], $newtime['sec'], $newtime['month'], $newtime['day'], $newtime['year']);
		}
		else
		{
			return $newtime;
		}
		*/
	}

} // Class end
# Autogenerated on : November 8, 2004, 2:52 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>

