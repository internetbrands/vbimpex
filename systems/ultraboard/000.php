<?php 
if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* ultraboard
*
* @package 		ImpEx.ultraboard
* @version		
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class ultraboard_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '2000';
	var $_tested_versions = array();
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'Ultraboard';
	var $_homepage 	= 'http://www.ub2k.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('Accounts', 'Announcements', 'Categories', 'Groups', 'PM', 'Profiles', 'Sessions');

	function ultraboard_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed	The string to parse
	* @param	boolean			Truncate smilies
	*
	* @return	array
	*/
	function ultraboard_html($text)
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
	function get_ultraboard_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT USERNAME FROM {$tableprefix}Accounts ORDER BY USERNAME LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[user_id]"] = $row['username'];
			}
		}
		
		return $return_array;
	}
		
	function get_ultraboard_users($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$return_array['lastid'] = ($start_at + $per_page);
		
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT Accounts.*, Profiles.*
			FROM {$tableprefix}Accounts as Accounts
			LEFT JOIN {$tableprefix}Profiles AS Profiles ON (Accounts.USERNAME = Profiles.USERNAME)
			ORDER by Accounts.USERNAME
			LIMIT {$start_at}, {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$start_at++;
				$return_array['data'][$start_at] = $row;
			}
		}
		
		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_next_ultra_forum($Db_object, $databasetype, $tableprefix, $current)
	{
		$board_id = NULL;

		if ($databasetype == 'mysql')
		{
			$current = ($current < 0 ? $current = 0 : $current);
			
			$sql = "SELECT ID FROM {$tableprefix}Boards ORDER BY ID LIMIT {$current},1";
			
			$board_id = $Db_object->query_first($sql);

			// If we are out of LIMIT range it's false
			if (is_numeric($board_id['ID']))
			{
				$board_id = $board_id['ID'];
			}
			
		}
		
		return $board_id;
	}
	
	function get_ultraboard_threads($Db_object, $databasetype, $table, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$return_array['lastid'] = ($start_at + $per_page);
		
		if ($databasetype == 'mysql')
		{
			
			$sql = "SELECT * FROM {$table} WHERE ROOT=0 LIMIT {$start_at}, {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][$row['ID']] = $row;
				$return_array['lastid']= $row['ID'];
			}
		}
		
		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}

	function get_ultraboard_posts($Db_object, $databasetype, $table, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		$return_array['lastid'] = ($start_at + $per_page);
		
		if ($databasetype == 'mysql')
		{
			
			$sql = "SELECT * FROM {$table} LIMIT {$start_at}, {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][$row['ID']] = $row;
				$return_array['lastid']= $row['ID'];
			}
		}
		
		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}
	
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
