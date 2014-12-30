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
* aef
*
* @package 		ImpEx.aef
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class aef_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.05';
	var $_tested_versions = array('1.0.5');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'Advanced Electron Forum';
	var $_homepage 	= 'http://www.anelectron.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'registry', 'attachment_mimetypes', 'attachments', 'categories', 'forumpermissions', 'forums',
	 	'fpass', 'mark_read', 'moderators', 'news', 'notify_forum', 'notify_topic', 'permissions',
	 	'pm', 'poll_options', 'poll_voters', 'polls', 'posts', 'read_board', 'read_forums',
	 	'read_topics', 'registry', 'sessions', 'shouts', 'smileys', 'stats', 'theme_registry',
	 	'themes', 'topics', 'user_groups', 'users'
	);

	function aef_000()
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
	function aef_html($text)
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
	function get_aef_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT id, username FROM {$tableprefix}users ORDER BY id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row['username'];
			}
		}			 
		return $return_array;
	}
	
	function get_aef_mods($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}moderators ORDER BY mod_mem_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][] = $row;
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
