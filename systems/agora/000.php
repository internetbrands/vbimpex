<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* agora API module
*
* @package			ImpEx.agora
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class agora_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '4.1.7';


	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'w-Agora';
	var $_homepage 	= 'http://www.w-agora.net';
	var $_tier = '3';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('_log', '_users', '_userthread');


	function agora_000()
	{
	}


	/**
	* Parses and custom HTML for agora
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function agora_html($text)
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
	function get_agora_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT unixdate, userid
			FROM " . $tableprefix . "_users
			ORDER BY unixdate
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[unixdate]"] = $user['userid'];
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
	function get_agora_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " . $tableprefix . "
			WHERE category != 1
			ORDER BY cle
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($detail['cle'] == 0)
				{
					$return_array[1] = $detail;
				}
				else
				{
					$return_array["$detail[cle]"] = $detail;
				}
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_agora_category_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . $tableprefix . " WHERE category = 1";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[unixdate]"] = $detail;
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
	function get_agora_post_details(&$Db_object, &$databasetype, &$tableprefix, &$tablename, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page) OR empty($tablename)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM ". $tablename . "
			ORDER BY cle 
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[cle]"] = $detail;
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
	function get_agora_thread_details(&$Db_object, &$databasetype, &$tableprefix, &$tablename, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page) OR empty($tablename)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			
			$sql = "
			SELECT * FROM ". $tablename . " 
			WHERE parent=0
			ORDER BY cle 
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[cle]"] = $detail;
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
	function get_agora_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "_users
			ORDER BY unixdate
			LIMIT " . $start_at . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[unixdate]"] = $user;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_agora_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		
		if(!$this->check_table($Db_object, $databasetype, $tableprefix, '_attachments'))
		{
			return $return_array;
		}
		
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "_attachments
			ORDER BY att_id
			LIMIT " . $start_at . "," . $per_page;

			$attach_list = $Db_object->query($sql);

			while ($attach = $Db_object->fetch_array($attach_list))
			{
				$return_array["$attach[att_id]"] = $attach;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_next_agora_forum(&$Db_object, &$databasetype, &$tableprefix, &$forum_table_name)
	{
		// Check that there is not a empty value
		if(empty($forum_table_name)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			if ($forum_table_name == 'start')
			{
				$sql = "SELECT bn_db FROM " . $tableprefix . " WHERE bn_db NOT LIKE '" . $tableprefix . "' ORDER BY cle LIMIT 0,1";

				$table_name = $Db_object->query_first($sql);

				return $table_name['bn_db'];
			}

			$sql = "SELECT bn_db FROM " . $tableprefix . " WHERE bn_db NOT LIKE '" . $tableprefix . "' AND bn_db != '' ORDER BY cle";

			$table_names = $Db_object->query($sql);

			$table_list = array();

			while ($table_name = $Db_object->fetch_array($table_names))
			{
				 $table_list[] = $table_name['bn_db'];
			}
			
			return $this->next_please($table_list, $forum_table_name);
		}
		else
		{
			return false;
		}
	}

	function next_please($array, $current)
	{
		$bit = array_search($current, $array);
		if ($array[$bit+1] == NULL)
		{
			return 'end';
		}
		else
		{
			return $array[$bit+1];
		}
	}

	function get_agora_import_forumid(&$Db_object, &$databasetype, &$tableprefix, &$forum_table_name)
	{
		// Check that there is not a empty value
		if(empty($forum_table_name)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "SELECT cle FROM " . $tableprefix . " WHERE bn_db LIKE '" . $forum_table_name . "'";

			$forumd_id = $Db_object->query_first($sql);

			return $forumd_id['cle'];

		}
		else
		{
			return false;
		}
	}


} // Class end
# Autogenerated on : February 24, 2005, 1:57 am
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
