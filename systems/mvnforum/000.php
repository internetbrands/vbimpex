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
* mvnforum
*
* @package 		ImpEx.mvnforum
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class mvnforum_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
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
	var $_modulestring = 'mvnforum';
	var $_homepage 	= 'http://www.mvnforum.com/mvnforumweb/index.jsp';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'Attachment', 'Category', 'FavoriteThread', 'Forum', 'GroupForum', 'GroupPermission', 'Groups', 'Member', 'MemberForum', 'MemberGroup',
	 	'MemberPermission', 'Message', 'MessageFolder', 'MessageStatistics', 'PmAttachMessage', 'PmAttachment', 'Post', 'Rank', 'Thread', 'Watch'
	);

	function mvnforum_000()
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
	function mvnforum_html($text)
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
	function get_mvnforum_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT MemberID, MemberName FROM {$tableprefix}Member ORDER BY MemberID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[MemberID]"] = $row['MemberName'];
			}
			return $return_array;
		} 
		else
		{
			return false;
		}
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
