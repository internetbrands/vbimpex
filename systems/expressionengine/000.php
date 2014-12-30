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
* expressionengine
*
* @package 		ImpEx.expressionengine
* @version		
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class expressionengine_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.6.2';
	var $_tested_versions = array('1.6.2');
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'ExpressionEngine';
	var $_homepage 	= 'http://expressionengine.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
			'forum_administrators', 'forum_attachments', 'forum_boards', 'forum_moderators', 'forum_polls', 'forum_pollvotes',
			'forum_posts', 'forum_ranks', 'forum_read_topics', 'forum_search', 'forum_subscriptions', 'forum_topics', 'forums', 
			'members', 'member_groups'
	);

	function expressionengine_000()
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
	function expressionengine_html($text)
	{
		$text = stripcslashes($text);
		
		for($i=0;$i<5;$i++)
		{		
			$text = preg_replace('#\[quote author="(.*)" date="([0-9]+)"\](.*)\[/quote]#siU', '[quote=$1]$3[/quote]', $text);
		}
		
		for($i=0;$i<5;$i++)
		{		
			$text = preg_replace('#\[quote author="(.*)"\](.*)\[/quote]#siU', '[quote=$1]$2[/quote]', $text);
		}		
		
		$text = html_entity_decode($text);
		
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
	function get_expressionengine_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT member_id, username FROM {$tableprefix}members ORDER BY member_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[member_id]"] = $row['username'];
			}
		}
		
		return $return_array;
	}
	
	function get_ee_categories_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE forum_parent=0 ORDER BY forum_id");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[forum_id]"] = $row;
			}
		}
		
		return $return_array;
	}
	
	function get_ee_forum_details($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE forum_parent != 0 ORDER BY forum_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[forum_id]"] = $row;
				$return_array['lastid'] = $row['forum_id'];
			}
		}
		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}	
	
	function get_ee_voters($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}forum_pollvotes WHERE poll_id={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[member_id]"] = $row['choice_id'];
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
