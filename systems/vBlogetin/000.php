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
* vBlogetin
*
* @package 		ImpEx.vBlogetin
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class vBlogetin_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_product = 'blog';
	var $_version = '1.0 Beta 3';
	var $_tested_versions = array('1.0 Beta 3');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'vBlogetin';
	var $_homepage 	= 'http://www.vblogetin.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'blog_attach', 'blog_blocks', 'blog_blocks_user', 'blog_blogviewers', 'blog_blogviews', 'blog_category', 'blog_comment', 'blog_customfields',
		'blog_entry', 'blog_entry_rating', 'blog_entryviews', 'blog_favorites', 'blog_feed', 'blog_feedlog', 'blog_layouts', 'blog_lookup', 'blog_rating',
		'blog_search', 'blog_search_customfields', 'blog_search_relevance', 'blog_settings', 'blog_stats', 'blog_subscribeblog', 'blog_subscribeentry',
		'user'
	);

	function vBlogetin_000()
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
	function vBlogetin_html($text)
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
	function get_vBlogetin_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT userid,username FROM {$tableprefix}user WHERE blog_hasblog=1 ORDER BY userid LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[userid]"] = $row['username'];
			}
		}

		return $return_array;
	}

	function get_vBlogetin_users($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}user WHERE blog_hasblog=1 AND userid > {$start_at} ORDER BY userid LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$blog_user_info = $Db_object->query_first("SELECT * FROM {$tableprefix}blog_stats WHERE userid=". $row['userid']);
				$blog_cat_info = $Db_object->query_first("SELECT title, description FROM {$tableprefix}blog_category WHERE userid=". $row['userid']);

				$return_array['data']["$row[userid]"] = $row;
				$return_array['data']["$row[userid]"]['blogdata'] = $blog_user_info;
				$return_array['data']["$row[userid]"]['tandd'] = $blog_cat_info;

				$return_array["lasid"] = $row['userid'];
			}
		}

		$return_array["count"] = count($return_array['data']);

		return $return_array;
	}

	function get_vBlogetin_cats($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}blog_blocks ORDER BY varname LIMIT {$start_at}, {$per_page}");

			$i=1;

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][$i++] = $row;
			}
			$return_array["lastid"] = $i;
		}

		$return_array["count"] = count($return_array['data']);

		return $return_array;
	}

	function get_blog_description($Db_object, $databasetype, $tableprefix, $id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$return_array = $Db_object->query_first("SELECT title, description FROM {$tableprefix}blog_settings WHERE userid={$id}");
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
