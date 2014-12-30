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
* phorum3 API module
*
* @package			ImpEx.phorum3
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class phorum3_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.4.8';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Phorum 3';
	var $_homepage 	= 'http://phorum.org/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'forums', 'forums_auth', 'Browse', 'forums_forum2group', 'forums_groups', 'forums_moderators', 'forums_user2group'
	);


	function phorum3_000()
	{
	}


	/**
	* Parses and custom HTML for phorum3
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function phorum3_html($text)
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
	function get_phorum3_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT id,username
			FROM " . $tableprefix . "forums_auth
			ORDER BY id
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[id]"] = $user['username'];
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
	function get_phorum3_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums
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
	function get_phorum3_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums_moderators
			ORDER BY user_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				// There is no consistant id per row
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
	function get_phorum3_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $sourceforumtablename)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page) OR empty($sourceforumtablename)) { return $return_array; }

		//Check it its there and has it been strtolower()'ed
		if(!$this->check_table($Db_object, $databasetype, $tableprefix, $sourceforumtablename))
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, strtolower($sourceforumtablename)))
			{
				$sourceforumtablename = strtolower($sourceforumtablename);		
			}
			else
			{
				return false;
			}
		}
		
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix . $sourceforumtablename . "
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$body = $Db_object->query_first("SELECT body FROM " . $tableprefix . $sourceforumtablename . "_bodies WHERE id = " . $detail['id']);
				$return_array["$detail[id]"] = $detail;
				$return_array["$detail[id]"]["pagetext"] = $body['body'];
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
	function get_phorum3_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $sourceforumtablename)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page) OR empty($sourceforumtablename)) { return $return_array; }

		//Check it its there and has it been strtolower()'ed
		if(!$this->check_table($Db_object, $databasetype, $tableprefix, $sourceforumtablename))
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, strtolower($sourceforumtablename)))
			{
				$sourceforumtablename = strtolower($sourceforumtablename);		
			}
			else
			{
				return false;
			}
		}

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix . $sourceforumtablename . "
			WHERE parent = 0
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
	function get_phorum3_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums_auth
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


	function get_phorum3_forum_step(&$Db_object, &$databasetype, &$tableprefix, $currentforumloop)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($currentforumloop)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "SELECT id, table_name FROM ".$tableprefix."forums ORDER BY id";

			$query_id = $Db_object->query($sql);
			
			if(!$Db_object->data_seek($currentforumloop-1,$query_id))
			{
				return false;
			}

			$details_list = $Db_object->fetch_array($query_id);
			$Db_object->free_result($query_id);
			$Db_object->lastquery = $sql;

			$count_sql = "SELECT COUNT(id) AS count FROM ".$tableprefix."forums";
			$count_list = $Db_object->query_first($count_sql);


			$return_array['id'] 		= $details_list['id'];
			$return_array['table_name'] = $details_list['table_name'];
			$return_array['count']		= $count_list['count'];
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_first_forum_name(&$Db_object, &$databasetype, &$tableprefix)
	{
		$sql = "
		SELECT id,table_name
		FROM forums
		ORDER BY id
		LIMIT 1";

		$details_list = $Db_object->query_first($sql);
		$table_name['name'] = $details_list['table_name'];
		$table_name['id'] = $details_list['id'];

		return $table_name;
	}
	
	
	function get_phorum3_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $sourceforumtablename)
	{
		$return_array = array();
		
		// Check that there is not a empty value
		if(empty($per_page) OR empty($sourceforumtablename)) { return $return_array; }
		
		//Check it its there and has it been strtolower()'ed
		if(!$this->check_table($Db_object, $databasetype, $tableprefix, $sourceforumtablename))
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, strtolower($sourceforumtablename)))
			{
				$sourceforumtablename = strtolower($sourceforumtablename);		
			}
			else
			{
				return false;
			}
		}
		
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix.$sourceforumtablename . "_attachments
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
	
	function get_parent_post_threadid(&$Db_object, &$databasetype, &$tableprefix, $sourceforumtablename, $post_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($sourceforumtablename)) { return $return_array; }

		//Check it its there and has it been strtolower()'ed
		if(!$this->check_table($Db_object, $databasetype, $tableprefix, $sourceforumtablename))
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, strtolower($sourceforumtablename)))
			{
				$sourceforumtablename = strtolower($sourceforumtablename);		
			}
			else
			{
				return false;
			}
		}

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT thread, parent FROM {$tableprefix}{$sourceforumtablename} WHERE id={$post_id}";

			$details_list = $Db_object->query_first($sql);
			
			if ($details_list['parent'] == 0)
			{
				return $details_list['thread'];
			}
			else
			{
				return $this->get_parent_post_threadid($Db_object, $databasetype, $tableprefix, $sourceforumtablename, $details_list['parent']);
			}
		}
		else
		{
			return false;
		}
		return $return_array;		
	}

} // Class end
# Autogenerated on : September 24, 2004, 2:23 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
