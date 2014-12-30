<?php if (!defined('IDIR')) { die; } if (!defined('IDIR')) { die; }
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
* drupal API module
*
* @package			ImpEx.drupal
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class drupal_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '4.7.0';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Drupal - Forum';
	var $_homepage 	= 'http://drupal.org/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'access','accesslog','aggregator_category','aggregator_category_feed','aggregator_category_item','aggregator_feed',
		'aggregator_item','authmap','blocks','boxes','cache','comments', 'files','filter_formats','filters',
		'flood','forum','history','menu','node','node_access','node_comment_statistics','node_counter','permission','poll','poll_choices',
		'profile_fields','profile_values', 'role','search_index','search_total','sequences','sessions','system','term_data',
		'term_hierarchy','term_node','term_relation','term_synonym','url_alias','users','users_roles','variable','vocabulary',
		'vocabulary_node_types','watchdog'
	);



	function drupal_000()
	{
	}


	/**
	* Parses and custom HTML for drupal
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function drupal_html($text)
	{
		$text = str_replace('<p>', '', $text);
		$text = str_replace('</p>', '', $text);
		$text = str_replace('<div class="source">', '', $text);
		$text = str_replace('</div>', '', $text);

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
	function get_drupal_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT uid, name
			FROM " . $tableprefix . "users
			ORDER BY uid
			LIMIT " . $start . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user['name'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	/**
	* Returns the forum_id => forum array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_drupal_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT tid, parent FROM {$tableprefix}term_hierarchy WHERE parent > 0 ORDER BY tid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$data = $Db_object->query_first("SELECT * FROM {$tableprefix}term_data WHERE tid=" . $detail['tid']);

				$return_array["$data[tid]"] = $data;
				$return_array["$data[tid]"]['parent'] = $detail['parent'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_drupal_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT tid FROM {$tableprefix}term_hierarchy WHERE parent=0 ORDER BY tid";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$data = $Db_object->query_first("SELECT * FROM {$tableprefix}term_data WHERE tid=" . $detail['tid']);
				$return_array["$detail[tid]"] = $data;

			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the post_id => post array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_drupal_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " .$tableprefix."comments ORDER BY cid LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[cid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the thread_id => thread array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_drupal_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM {$tableprefix}node
			WHERE type='forum'
			ORDER BY nid
			LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[nid]"] = $detail;

				// Get the first post (legacy, though while we are here)
				$body = $Db_object->query_first("SELECT body, timestamp FROM {$tableprefix}node_revisions WHERE nid=" . $detail['nid'] . " ORDER BY timestamp ASC LIMIT 1");
				$return_array["$detail[nid]"]['body'] = $body['body'];

				// Get the importforumid
				$forum_id = $Db_object->query_first("SELECT tid FROM {$tableprefix}term_node WHERE nid=" . $detail['nid']);
				$return_array["$detail[nid]"]['importforumid'] = $forum_id['tid'];

			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the user_id => user array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_drupal_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT *
			FROM " . $tableprefix . "users
			ORDER BY uid
			LIMIT " . $start_at . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : April 25, 2006, 6:24 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
