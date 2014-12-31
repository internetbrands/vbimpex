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
* yahoo_access API module
*
* @package			ImpEx.yahoo_access
*
*/
class yahoo_access_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0.0';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Yahoo Groups access dB download';
	var $_homepage 	= 'http://groups.yahoo.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('Groups', 'System');


	function yahoo_access_000()
	{
	}


	/**
	* Parses and custom HTML for yahoo_access
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function yahoo_access_html($text)
	{
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&quot;', '"', $text);
		$text = str_replace('&amp;', '&', $text);



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
	function get_yahoo_access_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page, &$forum)
	{
		$return_array = array();

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "{$tableprefix}Ygr_{$forum}_Mmb"))
		{
			return $return_array;
		}

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT user_id,username
			FROM {$tableprefix}Ygr_{$forum}_Mmb
			ORDER BY Member_ID
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[Member_ID]"] = $user['Name'];
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
	function get_yahoo_access_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Groups
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
	function get_yahoo_access_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $forum)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page) OR $forum == 'finished') { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Ygr_{$forum}
			ORDER BY YahooMessageID
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[YahooMessageID]"] = $detail;
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
	function get_yahoo_access_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $forum)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page) OR $forum == 'finished') { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Ygr_{$forum}
			WHERE Subject = SubjectSrt
			LIMIT " . $start_at . "," .	$per_page;


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
	function get_yahoo_access_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $forum)
	{
		$return_array = array();


		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Ygr_{$forum}_Mmb"))
		{
			return $return_array;
		}

		// Check that there is not a empty value
		if(empty($per_page) OR $forum == 'finished') { return $return_array; }

		if ($forum == 'none') { return false; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM {$tableprefix}Ygr_{$forum}_Mmb
			ORDER BY Member_ID
			LIMIT " . $start_at . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[Member_ID]"] = $user;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_yahoo_access_nextforum(&$Db_object, &$databasetype, &$tableprefix, $current_forum)
	{
		// Check that there is not a empty value
		if(empty($current_forum)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			if ($current_forum == 'none')
			{
				$sql = "SELECT GroupName FROM {$tableprefix}Groups LIMIT 0,1";

				$first_forum = $Db_object->query_first($sql);

				return $first_forum['GroupName'];
			}


			$sql = "SELECT GroupName FROM {$tableprefix}Groups ORDER BY Group_ID";

			$details = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details))
			{

				if ($detail['GroupName'] == $current_forum)
				{
					$detail = $Db_object->fetch_array($details);

					if ($detail['GroupName'])
					{
						return $detail['GroupName'];
					}
					else
					{
						return 'finished';
					}
				}
			}
		}
		else
		{
			return false;
		}

		return false;
	}


	function get_yahoo_access_get_threadid(&$Db_object, &$databasetype, &$tableprefix, $subject)
	{
		// Check that there is not a empty value
		if(empty($subject)) { return false; }

		$subject = stripslashes($subject);

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT threadid FROM {$tableprefix}thread WHERE title LIKE '{$subject}' LIMIT 0,1";

			$threadid = $Db_object->query_first($sql);

			return $threadid['threadid'];
		}
		else
		{
			return false;
		}

		return false;
	}
} // Class end
# Autogenerated on : November 11, 2005, 2:04 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
