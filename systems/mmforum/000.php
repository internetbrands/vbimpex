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
* mmforum
*
* @package 		ImpEx.mmforum
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class mmforum_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '0.1.5';
	var $_tested_versions = array('0.1.5');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'mmforum';
	var $_homepage 	= 'http://typo3.org/extensions/repository/view/mm_forum/0.1.5/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
	'fe_groups', 'fe_groups_language_overlay', 'fe_session_data', 'fe_sessions', 'fe_users', 'tx_mmforum_attachments', 'tx_mmforum_favorites',
	'tx_mmforum_forums', 'tx_mmforum_mailkey', 'tx_mmforum_pminbox', 'tx_mmforum_polls', 'tx_mmforum_polls_answers', 'tx_mmforum_polls_votes',
	'tx_mmforum_post_alert', 'tx_mmforum_postparser', 'tx_mmforum_postqueue', 'tx_mmforum_posts', 'tx_mmforum_posts_text', 'tx_mmforum_postsread',
	'tx_mmforum_ranks', 'tx_mmforum_searchresults', 'tx_mmforum_smilies', 'tx_mmforum_syntaxhl', 'tx_mmforum_topic_prefix', 'tx_mmforum_topicmail',
	'tx_mmforum_topics', 'tx_mmforum_userconfig', 'tx_mmforum_userfields', 'tx_mmforum_userfields_contents', 'tx_mmforum_wordlist', 'tx_mmforum_wordmatch'
	);

	function mmforum_000()
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
	function mmforum_html($text)
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
	function get_mmforum_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT uid,username FROM {$tableprefix}fe_users ORDER BY uid LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[uid]"] = $row['username'];
			}
		}

		return $return_array;
	}

	function get_mmforum_cat_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}tx_mmforum_forums WHERE parentID=0 ORDER BY uid");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[uid]"] = $row;
			}
		}

		return $return_array;
	}

	function get_mmforum_forum_details($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}tx_mmforum_forums WHERE parentID!=0 ORDER BY uid LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[uid]"] = $row;
			}
		}

		return $return_array;
	}

	function get_mmforum_posts($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{

			$sql = "SELECT posts.*,
			post_text.post_text
			FROM {$tableprefix}tx_mmforum_posts as posts
			LEFT JOIN {$tableprefix}tx_mmforum_posts_text AS post_text ON (posts.uid = post_text.post_id)
			WHERE posts.uid > {$start_at}
			ORDER by posts.uid
			LIMIT {$per_page}";

			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[uid]"] = $row;
				$return_array['lasid'] = $row['uid'];
			}

			$return_array['count'] = count($return_array['data']);
		}
		return $return_array;
	}


	function get_mmforum_poll_options($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}tx_mmforum_polls_answers WHERE poll_id={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array[] = $row['answer'];
			}
		}

		return implode('|||', $return_array);
	}

	function get_mmforum_poll_votes($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}tx_mmforum_polls_votes WHERE poll_id={$poll_id}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[uid]"] = $row;
			}
		}

		return $return_array;
	}

	function get_mmforum_poll_threadid($Db_object, $databasetype, $tableprefix, $poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return 0; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT uid FROM {$tableprefix}tx_mmforum_topics WHERE poll_id={$poll_id}");

			return $dataset['uid'];
		}

		return 0;
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
