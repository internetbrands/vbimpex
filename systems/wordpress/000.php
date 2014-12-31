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
* wordpress
*
* @package 		ImpEx.wordpress
* * @date 		$Date: $
*
*/

class wordpress_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_product = 'blog';
	var $_version = '2.3.1';
	var $_tested_versions = array('2.3.1');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'WordPress';
	var $_homepage 	= 'http://wordpress.org';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	#var $_valid_tables = array ('categories', 'comments', 'link2cat', 'links', 'metar_cache', 'options', 'post2cat', 'postmeta', 'posts', 'snews_members', 'usermeta', 'useronline', 'users');

	var $_valid_tables = array ('comments' ,'links' ,'options' ,'postmeta' ,'posts' ,'term_relationships' ,'term_taxonomy' ,'terms' ,'usermeta' ,'users');

	
	function wordpress_000()
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
	function wordpress_html($text)
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
	function get_wordpress_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT ID, display_name FROM {$tableprefix}users ORDER BY ID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[ID]"] = $row['display_name'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_wordpress_cats(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			
			$sql = "SELECT terms.name, terms.term_id FROM {$tableprefix}terms AS terms
					LEFT JOIN {$tableprefix}term_taxonomy AS term_taxonomy ON (terms.term_id = term_taxonomy.term_id)
					WHERE term_taxonomy.taxonomy LIKE 'category'
					LIMIT {$start_at}, {$per_page}";
					
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[term_id]"] = $row;

				$return_array["lastid"] = $row['term_id'];
			}
		}

		$return_array["count"] = count($return_array['data']);

		return $return_array;
	}
	
	
	function get_wordpress_posts(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();
		$catids = array();
			
		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			
			// Get all the cats
			$sql = "SELECT terms.term_id FROM {$tableprefix}terms AS terms
					LEFT JOIN {$tableprefix}term_taxonomy AS term_taxonomy ON (terms.term_id = term_taxonomy.term_id)
					WHERE term_taxonomy.taxonomy LIKE 'category'
					LIMIT {$start_at}, {$per_page}";
					
			$dataset = $Db_object->query($sql);

			$catids[] = 0;
			
			while ($row = $Db_object->fetch_array($dataset))
			{
				$catids[] = $row['term_id'];
			}
			
			
			// Get the posts from the cats
			$sql = "SELECT posts.* 
					FROM wp_posts AS posts
					LEFT JOIN wp_term_relationships AS term_relationships ON (term_relationships.object_id = posts.ID)
					WHERE term_relationships.term_taxonomy_id IN (" . implode(',', $catids) . ")";
			
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[ID]"] = $row;

				$return_array["lastid"] = $row['ID'];
			}
		}

		$return_array["count"] = count($return_array['data']);

		return $return_array;
	}
	

	function get_wordpress_catid(&$Db_object, &$databasetype, &$tableprefix, &$import_id)
	{
		// Check that there is not a empty value
		if(empty($import_id)) { return "0"; }

		if ($databasetype == 'mysql')
		{
			$catid = $Db_object->query_first("SELECT term_taxonomy_id FROM {$tableprefix}term_relationships WHERE object_id ={$import_id}");

			return $catid['term_taxonomy_id'];
		}

		return "0";
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
