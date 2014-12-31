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
* vBforum2blog
*
* @package 		ImpEx.vBforum2blog
* * @date 		$Date: $
*
*/

class vBforum2blog_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_product = 'blog';
	var $_version = '3.6.8';
	var $_tested_versions = array('3.6.8');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'vBulletin Forum 2 Blog';
	var $_homepage 	= 'http://www.vbulletin.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('user','forum','thread','post');

	function vBforum2blog_000()
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
	function vBforum2blog_html($text)
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
	function get_vBforum2blog_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT userid, username FROM {$tableprefix}user ORDER BY userid LIMIT {$start_at}, {$per_page}");

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

	function get_first_post_text(&$Db_object, &$databasetype, &$tableprefix, &$thread_id)
	{
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT dateline, pagetext FROM {$tableprefix}post WHERE threadid={$thread_id} ORDER BY dateline ASC LIMIT 1");

			return $dataset['pagetext'];
		}
		else
		{
			return false;
		}
	}

	function check_for_post(&$Db_object, &$databasetype, &$tableprefix, &$post_id)
	{
		if ($databasetype == 'mysql')
		{
			$there = $Db_object->query_first("SELECT importblogid FROM {$tableprefix}blog_blog WHERE importblogid={$post_id}");

			if(is_numeric($there[0]))
			{
				return false;
			}
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
