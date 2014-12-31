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
* InvisionCommunityBlog
*
* @package 		ImpEx.InvisionCommunityBlog
* * @date 		$Date: $
*
*/

class InvisionCommunityBlog_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.2.4';
	var $_tested_versions = array('1.2.4');
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_product = 'blog';
	var $_modulestring = 'Invision Community Blog';
	var $_homepage 	= 'http://www.invisionpower.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'blog_attachments', 'blog_authmembers', 'blog_blogs', 'blog_categories', 'blog_cblock_cache', 'blog_cblocks', 'blog_comments', 'blog_custom_cblocks',
		'blog_default_cblocks', 'blog_entries', 'blog_lastinfo', 'blog_moderators', 'blog_pingservices', 'blog_polls', 'blog_ratings', 'blog_read',
		'blog_rsscache', 'blog_trackback', 'blog_trackback_spamlogs', 'blog_tracker', 'blog_tracker_queue', 'blog_updatepings', 'blog_upgrade_history',
		'blog_views', 'blog_voters', 'member_extra', 'members', 'members_converge', 'members_partial'
	);

	function InvisionCommunityBlog_000()
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
	function InvisionCommunityBlog_html($text)
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
	function get_InvisionCommunityBlog_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT id, name FROM {$tableprefix}members ORDER BY id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row['name'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_InvisionCommunityBlog_users(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}members WHERE has_blog=1 ORDER BY id LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed
				$extra_array = $Db_object->query_first("SELECT * FROM {$tableprefix}member_extra WHERE id={$detail['id']}");
				$blog_array = $Db_object->query_first("SELECT * FROM {$tableprefix}blog_blogs WHERE member_id={$detail['id']}");


				#$detail = array_merge($detail, $extra_array);

				$return_array['data']["$detail[id]"] 					= $detail;
				$return_array['data']["$detail[id]"]['notes'] 			= $extra_array['notes'];
				$return_array['data']["$detail[id]"]['links'] 			= $extra_array['links'];
				$return_array['data']["$detail[id]"]['bio'] 			= $extra_array['bio'];
				$return_array['data']["$detail[id]"]['ta_size'] 		= $extra_array['ta_size'];
				$return_array['data']["$detail[id]"]['photo_type'] 		= $extra_array['photo_type'];
				$return_array['data']["$detail[id]"]['photo_location'] 	= $extra_array['photo_location'];
				$return_array['data']["$detail[id]"]['photo_dimensions'] = $extra_array['photo_dimensions'];
				$return_array['data']["$detail[id]"]['aim_name'] 		= $extra_array['aim_name'];
				$return_array['data']["$detail[id]"]['icq_number'] 		= $extra_array['icq_number'];
				$return_array['data']["$detail[id]"]['website']			= $extra_array['website'];
				$return_array['data']["$detail[id]"]['yahoo'] 			= $extra_array['yahoo'];
				$return_array['data']["$detail[id]"]['interests'] 		= $extra_array['interests'];
				$return_array['data']["$detail[id]"]['msnname'] 		= $extra_array['msnname'];
				$return_array['data']["$detail[id]"]['vdirs'] 			= $extra_array['vdirs'];
				$return_array['data']["$detail[id]"]['location'] 		= $extra_array['location'];
				$return_array['data']["$detail[id]"]['signature'] 		= $extra_array['signature'];
				$return_array['data']["$detail[id]"]['avatar_location'] = $extra_array['avatar_location'];
				$return_array['data']["$detail[id]"]['avatar_size'] 	= $extra_array['avatar_size'];
				$return_array['data']["$detail[id]"]['avatar_type'] 	= $extra_array['avatar_type'];
				$return_array['data']["$detail[id]"]['p_customblock'] 	= $extra_array['p_customblock'];
				$return_array['data']["$detail[id]"]['p_customheight'] 	= $extra_array['p_customheight'];

				$return_array['data']["$detail[id]"]['blog_name'] 	= $blog_array['blog_name'];
				$return_array['data']["$detail[id]"]['blog_desc'] 	= $blog_array['blog_desc'];

				$return_array['lasid'] = $detail['id'];
			}

			$return_array['count'] = count($return_array['data']);

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
