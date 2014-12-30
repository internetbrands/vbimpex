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
* dragonfly
*
* @package 		ImpEx.dragonfly
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class dragonfly_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '9.2.1';
	var $_tested_versions = array();
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'dragonfly';
	var $_homepage 	= 'http://www.dragonflycms.org';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'bbattachments', 'bbattachments_config', 'bbattachments_desc', 'bbauth_access', 'bbbanlist', 'bbcategories', 'bbconfig', 'bbdisallow',
	 	'bbextension_groups', 'bbextensions', 'bbforbidden_extensions', 'bbforum_prune', 'bbforums', 'bbgroups', 'bbposts', 'bbposts_text',
	 	'bbprivmsgs', 'bbprivmsgs_text', 'bbquota_limits', 'bbranks', 'bbsearch_wordlist', 'bbsearch_wordmatch', 'bbsmilies', 'bbthemes',
	 	'bbthemes_name', 'bbtopic_icons', 'bbtopics', 'bbtopics_watch', 'bbuser_group', 'bbvote_desc', 'bbvote_results', 'bbvote_voters', 'users'
	);

	function dragonfly_000()
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
	function dragonfly_html($text)
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
	function get_dragonfly_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT user_id, username FROM {$tableprefix}users ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[user_id]"] = $row['username'];
			}
		}

		return $return_array;
	}
	
	function get_dragonfly_cat_details($Db_object, $databasetype, $tableprefix)
	{
		
		
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}bbcategories");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[cat_id]"] = $row;
			}
		}

		return $return_array;
	}	
	
	function get_dragonfly_posts($Db_object, $databasetype, $tableprefix, $start, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page) OR !is_numeric($start)) { return $return_array; }
	
	
		if ($databasetype == 'mysql')
		{
			$sql = "SELECT post.*,
			post_text.*
			FROM {$tableprefix}bbposts as post
			LEFT JOIN {$tableprefix}bbposts_text AS post_text ON (post.post_id = post_text.post_id)
			WHERE post.post_id > {$start}
			ORDER by post.post_id
			LIMIT {$per_page}";

			$posts = $Db_object->query($sql);

			while ($post = $Db_object->fetch_array($posts))
			{
				$return_array['data']["$post[post_id]"] = $post;
				$return_array['lastid'] = $post['post_id'];
			}
		}
		
		$return_array['count'] = count($return_array);
		return $return_array;
	}	
	
	function get_dragonfly_text($Db_object, $databasetype, $tableprefix, $pm_id)
	{
		$return_text = '';

		// Check that there isn't a empty value
		if(empty($pm_id)){ return $return_text; }
	
		if ($databasetype == 'mysql')
		{
			$posts_text = $Db_object->query_first("SELECT privmsgs_text FROM {$tableprefix}bbprivmsgs_text WHERE privmsgs_text_id={$pm_id}");

			$return_text = $posts_text['privmsgs_text'];
		}
		
		return $return_text;
	}	
	
			
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
