<?php if (!defined('IDIR')) { die; }
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
* xsorbit API module
*
* @package			ImpEx.xsorbit
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class xsorbit_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = 'x5';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Xsorbit X5';
	var $_homepage 	= 'http://www.xsorbit.com';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'attachments', 'banned', 'board_permissions', 'boards', 'calendar', 'calendar_holidays', 'categories', 'collapsed_categories', 'im_recipients',
		'instant_messages', 'log_actions', 'log_activity', 'log_banned', 'log_boards', 'log_errors', 'log_floodcontrol', 'log_karma', 'log_mark_read',
		'log_notify', 'log_online', 'log_polls', 'log_topics', 'membergroups', 'members', 'messages', 'moderators', 'permissions', 'poll_choices',
		'polls', 'settings', 'smileys', 'themes', 'topics'	
	);


	function xsorbit_000()
	{
	}


	/**
	* Parses and custom HTML for xsorbit
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function xsorbit_html($text)
	{
		// Could link post
		#[quote author=babygurl2000 link=topic=1526.msg8336#msg8336 date=1132401243]
		$how_many_quote = substr_count($text, '[quote');
		for($i=0; $i < $how_many_quote; $i++)
		{
			$text = preg_replace('#\[quote author=(.*) link(.*)\]#siU', '[quote=$1]', $text);
		}
		
		$how_many_img = substr_count($text, '[img');
		for($i=0; $i < $how_many_img; $i++)
		{
			$text = preg_replace('#\[img(.*)\]#siU', '[img]', $text);
		}		
		
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&quot;', '"', $text);
		$text = str_replace('&amp;', '&', $text);		
		
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
	function get_xsorbit_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT ID_MEMBER, memberName
			FROM " . $tableprefix . "members 
			ORDER BY ID_MEMBER
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[ID_MEMBER]"] = $user['memberName'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	/**
	* Returns the attachment_id => attachment array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xsorbit_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachments
			WHERE ID_MSG > 0 
			ORDER BY ID_ATTACH
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_ATTACH]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
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
	function get_xsorbit_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."boards
			ORDER BY ID_BOARD
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_BOARD]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_xsorbit_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . $tableprefix."categories ORDER BY ID_CAT";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_CAT]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}	
	

	/**
	* Returns the pm_id => pm array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xsorbit_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."instant_messages
			ORDER BY ID_PM
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_PM]"] = $detail;
				
				// Recipts
				$recipts = $Db_object->query("SELECT * from " . $tableprefix."im_recipients WHERE ID_PM = " . $detail['ID_PM'] );
				
				while ($row = $Db_object->fetch_array($recipts))
				{
					$recipt[] = $row;
				}
				
				$return_array["$detail[ID_PM]"]['recipts'] = $recipt; 
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
	function get_xsorbit_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT polls.ID_POLL, polls.question, polls.votingLocked, polls.maxVotes, polls.expireTime, polls.hideResults, polls.ID_MEMBER, polls.posterName,
			topics.ID_TOPIC, topics.ID_POLL
			FROM " .$tableprefix."polls AS polls
			LEFT JOIN " .$tableprefix."topics AS topics ON(polls.ID_POLL = topics.ID_POLL)
			ORDER BY polls.ID_POLL
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$choices = array();
				$votes = array();
				$poll_voters = array();
				$return_array["$detail[ID_POLL]"] = $detail;
				
				// Options
				$options = $Db_object->query("SELECT * from " . $tableprefix."poll_choices WHERE ID_POLL = " . $detail['ID_POLL']);
				
				while ($row = $Db_object->fetch_array($options))
				{
					$choices[]= $row['label'];
					$votes[]= $row['votes'];
				}
				
				// Voters
				$voters_choice = $Db_object->query("SELECT * FROM " . $tableprefix . "log_polls WHERE ID_POLL =" . $detail['ID_POLL']);

				while ($choice = $Db_object->fetch_array($voters_choice))
				{
					$poll_voters["$choice[ID_MEMBER]"] = $choice['ID_CHOICE']+1; // They use a 0 start count array, vB starts at 1
				}				
				
				$return_array["$detail[ID_POLL]"]['numberoptions'] = count($choices);
				$return_array["$detail[ID_POLL]"]['voters'] = array_sum($votes);
				$return_array["$detail[ID_POLL]"]['options'] = implode('|||', $choices);
				$return_array["$detail[ID_POLL]"]['votes'] = implode('|||', $votes);
				$return_array["$detail[ID_POLL]"]['poll_voters'] = $poll_voters;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	
	function get_phpbb2_vote_voters(&$Db_object, &$databasetype, &$tableprefix, &$poll_id)
	{
		$return_array = array();
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."log_polls
			WHERE ID_POLL ='" .	$poll_id ."'"

			;

			$polls = $Db_object->query($sql);

			while ($poll = $Db_object->fetch_array($polls))
			{
				$return_array["$poll[vote_user_id]"] = 0;
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
	function get_xsorbit_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."messages
			ORDER BY ID_MSG
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MSG]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the smilie_id => smilie array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xsorbit_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smileys
			ORDER BY ID_SMILEY
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_SMILEY]"] = $detail;
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
	function get_xsorbit_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT topics.ID_TOPIC, topics.isSticky, topics.ID_BOARD, topics.ID_FIRST_MSG, topics.ID_MEMBER_STARTED, topics.numReplies, topics.numViews,
			messages.subject, messages.posterName, messages.ID_MEMBER, messages.posterTime 
			FROM " . $tableprefix . "topics AS topics
			LEFT JOIN " . $tableprefix . "messages AS messages ON(topics.ID_FIRST_MSG = messages.ID_MSG)
			ORDER BY ID_TOPIC
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_TOPIC]"] = $detail;
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
	function get_xsorbit_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."members
			ORDER BY ID_MEMBER 
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_MEMBER]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the usergroup_id => usergroup array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xsorbit_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."membergroups
			ORDER BY ID_GROUP 
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[ID_GROUP]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


} // Class end
# Autogenerated on : February 13, 2006, 3:37 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
