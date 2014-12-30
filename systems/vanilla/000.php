<?php 
if (!defined('IDIR')) { die; }
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
* vanilla
*
* @package 		ImpEx.vanilla
* @version		
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class vanilla_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.1.4';
	var $_tested_versions = array('1.1.4');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'vanilla';
	var $_homepage 	= 'http://www.getvanilla.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
	'Attachment','Category','CategoryBlock','CategoryRoleBlock','Comment','Discussion','DiscussionUserWhisperFrom',
	'DiscussionUserWhisperTo','IpHistory','Poll','PollBlock','PollData','PollRoleBlock','Role','Style','User','UserBookmark',
	'UserDiscussionWatch','UserRoleHistory','UserSearch'
	);

	function vanilla_000()
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
	function vanilla_html($text)
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
	function get_vanilla_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT UserID, Name FROM {$tableprefix}User ORDER BY UserID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[UserID]"] = $row['Name'];
			}
		
		}
		 
		return $return_array;
	}
	
	function get_vanilla_cats($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		// Check that there is not a empty value

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Category");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[CategoryID]"] = $row;
			}
		}
		 
		return $return_array;
	}
	
	function get_vanilla_threads($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Discussion WHERE WhisperUserID=0 ORDER BY DiscussionID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[DiscussionID]"] = $row;
				$return_array['lastid'] = $row['DiscussionID'];
			}
		}

		$return_array['count'] = count($return_array['data']);
		
		return $return_array;
	}

	function get_vanilla_posts($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Comment WHERE WhisperUserID=0 AND CommentID > {$start_at} ORDER BY CommentID LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[CommentID]"] = $row;
				$return_array['lastid'] = $row['CommentID'];		
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
