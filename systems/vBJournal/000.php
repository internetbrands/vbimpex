<?php
if (!defined('IDIR')) { die; }
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
* vBJournal
*
* @package 		ImpEx.vBJournal
* * @date 		$Date: $
*
*/

class vBJournal_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_product = 'blog';
	var $_version = '1.0.2';
	var $_tested_versions = array('1.0.2');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'vBJournal';
	var $_homepage 	= 'http://www.vbulletin.org/forum/showthread.php?t=96462';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('journal_comments', 'journal_entries', 'journal_moods', 'journal_settings', 'journals', 'user');


	function vBJournal_000()
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
	function vBJournal_html($text)
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
	function get_vBJournal_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT userid,username FROM {$tableprefix}user WHERE canhavejournal=1 ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[userid]"] = $row['username'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_vBJournal_users(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT journals.entrycount, journals.journalname, journals.journaldesc, user.* FROM {$tableprefix}journals AS journals
											LEFT JOIN {$tableprefix}user AS user ON (journals.journalist_id = user.userid)
											WHERE journals.journalist_id > {$start_at}
											ORDER BY journals.journalist_id
											LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[userid]"] = $row;

				$return_array["lastid"] = $row['userid'];
			}
		}

		$return_array["count"] = count($return_array['data']);

		return $return_array;
	}

	function get_vBJournal_blogs(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}journal_entries WHERE entry_id > {$start_at} ORDER BY entry_id LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$user_id = $Db_object->query_first("SELECT journalist_id FROM {$tableprefix}journals WHERE journal_id=" . $row['journal_id']);

				$return_array['data']["$row[entry_id]"] = $row;
				$return_array['data']["$row[entry_id]"]['userid'] = $user_id['journalist_id'];

				$return_array["lastid"] = $row['entry_id'];
			}
		}

		$return_array["count"] = count($return_array['data']);

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
