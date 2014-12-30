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
* vBulletin Suite Blog 4.x
*
* @package 		ImpEx.vBulletinBlog4
* @version
* @author		
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class vBBlog4_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '4.0.x - 4.1.x';
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_product = 'blog';
	var $_modulestring = 'vBulletin Blog';
	var $_homepage 	= 'http://www.vbulletin.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'blog_category', 'blog_categorypermission', 'blog_categoryuser', 'blog_custom_block', 'blog_custom_block_parsed', 'blog_deletionlog',
		'blog_editlog', 'blog_featured', 'blog_groupmembership', 'blog_hash', 'blog_moderation', 'blog_moderator',
		'blog_pinghistory', 'blog_rate', 'blog_read', 'blog_relationship', 'blog_search', 'blog_searchresult', 'blog_sitemapconf',
		'blog_subscribeentry', 'blog_subscribeuser', 'blog_summarystats', 'blog_tachyentry', 'blog_text', 'blog_textparsed', 'blog_trackback',
		'blog_trackbacklog', 'blog_user', 'blog_usercss', 'blog_usercsscache', 'blog_userread', 'blog_userstats', 'blog_views',
		'blog_visitor',
	);

	function vBBlog4_000()
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
	function vBBlog4_html($text)
	{
		return $text;
	}

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			comma separated list of userids to match
	*
	* @return	array
	*/
	function get_import_ids_from_list(&$Db_object, &$databasetype, &$tableprefix, $userids)
	{
		$userlist = array();
		if ($databasetype == 'mysql')
		{
			$data = $Db_object->query("
				SELECT userid, importuserid
				FROM {$tableprefix}user
				WHERE importuserid IN ($userids)
			");
			while ($userinfo = $Db_object->fetch_array($data))
			{
				$userlist[$userinfo['importuserid']] = $userinfo['userid'];
			}			
		}
		return $userlist;
	}

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	void
	*/
	function update_blog_category_parentid(&$Db_object, &$databasetype, &$tableprefix)
	{
		$cats = $this->get_blog_category_ids($Db_object, $databasetype, $tableprefix);

		if ($cats)
		{
			$categories = $Db_object->query("
				SELECT
					blogcategoryid, importblogcategoryid, parentid
				FROM {$tableprefix}blog_category
				WHERE
					parentid <> 0
			");
			while ($cat = $Db_object->fetch_array($categories))
			{
				$Db_object->query("
					UPDATE {$tableprefix}blog_category
					SET parentid = " . ($cats[$cat['parentid']] ? $cats[$cat['parentid']] : 0) . "
					WHERE
						blogcategoryid = $cat[blogcategoryid]
				");
			}
		}
	}

	function get_vBBlog_blog(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("
				SELECT *
				FROM {$tableprefix}blog
				ORDER BY blogid
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed
				// Yes -- this could be done with a join in the above query but this way leaves the two datasets separate
				$blog_text = $Db_object->query_first("SELECT * FROM {$tableprefix}blog_text WHERE blogtextid={$detail['firstblogtextid']}");


				#$detail = array_merge($detail, $extra_array);

				$return_array['data']["$detail[blogid]"] 					= $detail;
				$return_array['data']["$detail[blogid]"]['blog_text']		= $blog_text;

				$return_array['lastid'] = $detail['blogid'];
			}

			$return_array['count'] = count($return_array['data']);

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_vBBlog_blogtext(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array(
			'data'  => array(),
			'count' => 0,
		);

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			// Query comments, filtering out the first comment, which is the blog text.
			$dataset = $Db_object->query("
				SELECT bt.*
				FROM {$tableprefix}blog_text AS bt
				INNER JOIN {$tableprefix}blog AS b ON (bt.blogid = b.blogid AND b.firstblogtextid <> bt.blogtextid)
				ORDER BY bt.blogtextid
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed

				$return_array['data']["$detail[blogtextid]"]	= $detail;
				$return_array['lastid'] = $detail['blogtextid'];
			}

			$return_array['count'] = count($return_array['data']);
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