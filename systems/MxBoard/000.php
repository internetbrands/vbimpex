<?php
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
* MxBoard API module
*
* @package			ImpEx.MxBoard
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class MxBoard_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.1.4';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'MxBoard';
	var $_homepage 	= 'http://www.webvelosix.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'categories', 'db_analyzer', 'forum', 'ignore_list', 'ip_blacklist', 'log_failed', 'log_success', 'member_list', 'messages',
		'permissions', 'plugins', 'poll_answers', 'poll_entity', 'poll_voters', 'read_thread', 'restricted_access', 'system', 'thread_title',
		'topic_ignore', 'topics', 'users'
	);


	function MxBoard_000()
	{
	}


	/**
	* Parses and custom HTML for MxBoard
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function MxBoard_html($text)
	{
		$text = html_entity_decode($text);

		$text = str_replace('<center>', '', $text);
		$text = str_replace('</p>', '', $text);
		$text = str_replace('<br>', '', $text);

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
	function get_MxBoard_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT uid, email, handle FROM {$tableprefix}users ORDER BY uid LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				if(strlen(trim($user['email'])) != 0)
				{
					$return_array["$user[uid]"] = $user['handle'];
				}
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
	function get_MxBoard_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}forum ORDER BY fid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[fid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_MxBoard_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}categories ORDER BY cat_id");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[cat_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the poll_id => poll array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_MxBoard_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}poll_entity ORDER BY poll_id LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[poll_id]"] = $detail;
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
	function get_MxBoard_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}messages ORDER BY mid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[mid]"] = $detail;
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
	function get_MxBoard_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}thread_title ORDER BY tdid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[tdid]"] = $detail;
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
	function get_MxBoard_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}users ORDER BY uid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if(strlen(trim($detail['email'])) != 0)
				{
					$return_array["$detail[uid]"] = $detail;
				}
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function do_date($old_date)
	{
		if(!$old_date)
		{
			return 0;
		}

		if (strlen($old_date) == 14)
		{
			//YYYYMMDDHHMMSS
			return mktime (substr($old_date, 8, 2), substr($old_date, 10, 2), substr($old_date, 12, 2), substr($old_date, 4, 2), substr($old_date, 6, 2), substr($old_date, 0, 4));
		}
		else
		{
			return strtotime($old_date);
		}
	}

	function get_MxBoard_poll_option_vote_details(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT * FROM {$tableprefix}poll_answers WHERE poll_id={$poll_id}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array['options'] .= $poll['answer'] . '|||';
				$return_array['votes'] .= $poll['votes'] . '|||';
				$return_array['numberoptions']++;
				$return_array['voters'] += intval($poll['votes']);
			}
		}
		else
		{
			return false;
		}

		$return_array['options'] = substr($return_array['options'], 0, -3);
		$return_array['votes'] = substr($return_array['votes'], 0, -3);

		return $return_array;
	}

	function get_MxBoard_vote_voters(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$polls = $Db_object->query("SELECT user_id, answer_id FROM {$tableprefix}poll_voters WHERE poll_id ={$poll_id}");

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[user_id]"] = $poll['answer_id'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : September 13, 2006, 3:53 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
