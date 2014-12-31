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
* vBB API module
*
* @package			ImpEx.vBB
*
*/
class vBB_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.0 RC 1';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'versatile Bulletin Board';
	var $_homepage 	= 'http://sourceforge.net/projects/tvbb';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
			'attachment', 'blockednames', 'categ_mod', 'category', 'countries', 'curronline', 'forum',
			'listuser', 'maxuser', 'message', 'mod', 'mod_POLL_option', 'mod_POLL_topic', 'mod_POLL_vote',
			'mread', 'netaccess', 'option', 'pm', 'quota', 'rated', 'smiley', 'style', 'subs', 'user',
			'userlevel', 'userlist'
	);


	function vBB_000()
	{
	}


	function vBB_2_bb($text)
	{
		$text = preg_replace("#<blockquote class='quote'>(.*)</blockquote>#siU", '[quote]$1[/quote]', $text);

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
	function get_vBB_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT ID, name
			FROM " . $tableprefix . "user
			ORDER BY ID
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$tempArray = array($user['ID'] => $user['name']);
					$return_array = $return_array + $tempArray;
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
	function get_vBB_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum
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
	function get_vBB_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."pm
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
	function get_vBB_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."mod_POLL_topic
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
	function get_vBB_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."message
			WHERE reply !=0
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
	function get_vBB_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smiley
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
	function get_vBB_category_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."category
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


	function get_vBB_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."message
			WHERE reply=0
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
	function get_vBB_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."user
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


	function get_vBB_poll_options(&$Db_object, &$databasetype, &$tableprefix, $topic_id)
	{
		$return_array = array();
		$number_options = 0;

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."mod_POLL_option
			WHERE topic_ID = " . $topic_id . "
			ORDER BY ID
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{

					$return_array['options'] .= $detail['option_name'] . '|||';
					$return_array["$detail[ID]"] = $detail;
					$number_options++;
			}

			$return_array['options'] = substr($return_array['options'], 0, -3);
			$return_array['numberoptions'] = $number_options;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : June 24, 2004, 6:23 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
