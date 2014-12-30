<?php 
if (!defined('IDIR')) { die; }
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
* bbpress
*
* @package 		ImpEx.bbpress
* @version		
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class bbpress_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '0.9.0.1';
	var $_tested_versions = array('0.9.0.1');

	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'bbpress';
	var $_homepage 	= 'http://www.bbpress.org';
	var $_tier = '2';
	
	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('forums', 'posts', 'tagged', 'tags', 'topicmeta', 'topics', 'usermeta', 'users');

	function bbpress_000()
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
	function bbpress_html($text)
	{
		$text = str_replace('</p>', '', $text);
		$text = html_entity_decode($text);
		
		$text = str_replace('<code>', '[code]', $text);
		$text = str_replace('</code>', '[/code]', $text);
		
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
	function get_bbpress_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT ID, user_login FROM {$tableprefix}users ORDER BY ID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[ID]"] = $row['user_login'];
			}
		}
		
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
