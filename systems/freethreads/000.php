<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* freethreads API module
*
* @package			ImpEx.freethreads
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class freethreads_000 extends ImpExModule
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
	var $_modulestring 	= 'freethreads';
	var $_homepage 	= 'http://freethreads.sourceforge.net/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('Status', 'Users', 'Forums');


	function freethreads_000()
	{
	}


	/**
	* Parses and custom HTML for freethreads
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function freethreads_html($text)
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
	function get_freethreads_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT RECORDNUM, USERNAME 
			FROM " . $tableprefix . "Users
			ORDER BY RECORDNUM 
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[RECORDNUM]"] = $user['USERNAME'];
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
	function get_freethreads_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Forums
			ORDER BY RECORDNUM
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[RECORDNUM]"] = $detail;
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
	function get_freethreads_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $forum_table_name)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page) OR empty($forum_table_name)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix . $forum_table_name . "
			ORDER BY RECORDNUM
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[RECORDNUM]"] = $detail;
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
	function get_freethreads_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $forum_table_name)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page) OR empty($forum_table_name)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix . $forum_table_name . "
			WHERE PARENT = 0
			ORDER BY RECORDNUM
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[RECORDNUM]"] = $detail;
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
	function get_freethreads_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Users
			ORDER BY RECORDNUM 
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[RECORDNUM]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
	
	function freethreads_get_first_forum(&$Db_object, &$databasetype, &$tableprefix)
	{
		$tablename = 'end';
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT TABLENAME FROM " . $tableprefix . "Forums ORDER BY RECORDNUM LIMIT 0,1";

			$details = $Db_object->query_first($sql);
			
			$tablename = $details['TABLENAME'];
		}
		else
		{
			return false;
		}
		return $tablename;
	}

	function freethreads_get_next_forum(&$Db_object, &$databasetype, &$tableprefix, $forumget)
	{
		$tablename = 'end';
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT TABLENAME FROM " . $tableprefix . "Forums ORDER BY RECORDNUM";

			$details = $Db_object->query($sql);
			
			while ($detail = $Db_object->fetch_array($details))
			{
				if($detail['TABLENAME'] == $forumget)
				{
					$detail = $Db_object->fetch_array($details);

					if($detail['TABLENAME'] != NULL)
					{
						return $detail['TABLENAME'];
					}
				}
			}
		}
		else
		{
			return false;
		}
		return $tablename;
	}	
	
	
	function freethreads_get_full_forum_name(&$Db_object, &$databasetype, &$tableprefix, $tablename)
	{
		
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT NAME FROM " . $tableprefix . "Forums 
			WHERE TABLENAME='" . $tablename ."'";

			$details = $Db_object->query_first($sql);
			
			return $details['NAME'];
		}
		else
		{
			return false;
		}
		return false;
	}	
	
	
} // Class end
# Autogenerated on : February 8, 2005, 11:52 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
