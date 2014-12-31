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
* eve API module
*
* @package			ImpEx.eve
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class eve_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.2.22 - 4.0.3';
	var $_tier = '1';


	/**
	* Module string
	*
	* @var    array
	*/
	#var $_modulestring 	= 'Infopop Groupee 1.2.6 (a.k.a Eve) (Forum UBB.x 4.0.3)';
	var $_modulestring 	= 'Groupee 1.2.22';
	var $_homepage 	= 'http://www.infopop.com/eve_platform/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'CACHE', 'CURRENT_DB_VERSION', 'DATABASE_INFO', 'IP_A_ALBUM', 'IP_A_ALBUM_IMAGE', 'IP_BANNED_USERS', 'IP_CAT_CATEGORY',
	 	'IP_CAT_RESOURCE_CATEGORY', 'IP_CHAT_EMOTE', 'IP_CHAT_MESSAGE', 'IP_CHAT_MODERATED_MESSAGE', 'IP_CHAT_PARTICIPANT',
	 	'IP_CHAT_ROOM', 'IP_CHAT_TOPICS', 'IP_CHAT_TOPIC_MESSAGES', 'IP_CHAT_TOPIC_UNMOD_QUEUE', 'IP_CLONE_SOURCE_AND_DEST',
		'IP_CONTENT_SUBSCRIPTIONS', 'IP_CUSTOM_CODES', 'IP_CUSTOM_PROFILE_FIELDS', 'IP_CUST_ALBUM_CATEGORY', 'IP_CUST_LOCATION',
		'IP_C_CONTENT', 'IP_C_CONTENT_ISLAND', 'IP_C_CONTENT_LOOKUP', 'IP_C_CONTENT_REPORT', 'IP_C_CONTENT_TYPE', 'IP_C_EXTENDED_DATA',
		'IP_C_GUEST_USER', 'IP_C_MODERATED_CONTENT', 'IP_C_RATED_CONTENT', 'IP_C_SEARCH_EVENT', 'IP_C_UPLOAD', 'IP_C_USER_FAVORITE',
		'IP_C_USER_INTEREST_LOG', 'IP_DISPLAY_OPTIONS', 'IP_DISPLAY_RESOURCES', 'IP_DISPLAY_RES_ASSOCIATIONS', 'IP_EVENT_LOG',
		'IP_F_FORUM', 'IP_F_FORUM_ACCEPTED_POST_TYPE', 'IP_F_FORUM_ATTACHMENT_RULE', 'IP_F_FORUM_MOD_CONTENT_TYPE', 'IP_F_FORUM_STATS',
		'IP_F_FORUM_TOPIC', 'IP_F_STATS_CONSOLIDATION', 'IP_GROUPS', 'IP_GROUP_USERS', 'IP_IGNORED_USERS', 'IP_IM_ACTIVE_MENUS',
		'IP_IM_PARTICIPANTS', 'IP_KARMA_LEVELS', 'IP_PERMISSIONS', 'IP_PREMIUM_GROUP_SETTINGS', 'IP_PRIVATE_WEB_DIRECTORIES',
		'IP_PROFILES', 'IP_PT_PRIVATE_TOPIC', 'IP_PT_PRIVATE_TOPIC_PARTICIPANT', 'IP_P_POLL', 'IP_P_POLL_ANSWER', 'IP_P_POLL_QUESTION',
	 	'IP_P_POLL_RESPONSE', 'IP_RESOURCES', 'IP_RIGHTS', 'IP_RW_LOCKS2', 'IP_SESSION_ACTIVITY', 'IP_SETTINGS', 'IP_SS_IMAGE_DIMENSIONS',
	 	'IP_STATISTICS', 'IP_STREET_ADDRESSES', 'IP_STYLE_SETS', 'IP_STYLE_SETTINGS', 'IP_TEMPLATE_FILES', 'IP_TEMPLATE_SETS',
		'IP_TEMPLATE_SET_USAGE', 'IP_TEMPLATE_SUPPORT_INFO', 'IP_T_ARCHIVED_MESSAGE', 'IP_T_ARCHIVED_TOPIC', 'IP_T_MESSAGE',
	 	'IP_T_MESSAGE_TO_DELETE', 'IP_T_TOPIC', 'IP_T_TOPIC_STATS', 'IP_UNCONFIRMED_USERS', 'IP_USERS', 'IP_USER_CONTACT_INFO',
	 	'IP_USER_RESOURCE_INFO', 'IP_USER_STATS', 'IP_WORDLETS', 'IP_WORDLET_SETS'
	);

	/**
	* Disable Dupe Checking
	*
	* @var    boolean/array
	*/

	var $_dupe_checking = false;


	function eve_000()
	{
	}


	/**
	* Parses and custom HTML for eve
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function eve_html($text)
	{
		$text = preg_replace('#<BLOCKQUOTE(.*)-content">(.*)</div></BLOCKQUOTE>#siU', '[quote]$2[/quote]', $text);

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
	function get_eve_members_list($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_USERS = 'IP_USERS';
			
			if (lowercase_table_names)
				$IP_USERS = strtolower($IP_USERS);
				
			$result = $Db->query("
				SELECT 
					USER_OID,
					LOGIN
				FROM {$prefix}{$IP_USERS} 
					ORDER BY USER_OID
				LIMIT {$offset}, {$limit}
			");

			while ($row = $Db->fetch_array($result)) {
					$return_array["$row[USER_OID]"] = $row['LOGIN'];
			}
		}
		
		return $return_array;
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
	function get_eve_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM " . (lowercase_table_names ? "ip_c_upload" : "IP_C_UPLOAD" ) . "
					ORDER BY UPLOAD_OID
					LIMIT " .
					$start_at .
					"," .
					$per_page
					;

			$attachments = $Db_object->query($sql);

			while ($attachment = $Db_object->fetch_array($attachments))
			{
				$return_array["$attachment[UPLOAD_OID]"] = $attachment;
				$extra = $Db_object->query_first("SELECT RELATED_CONTENT_OID FROM " . (lowercase_table_names ? "ip_c_content" : "IP_C_CONTENT" ) . " WHERE CONTENT_OID=" . $attachment['UPLOAD_OID']);
				$return_array["$attachment[UPLOAD_OID]"]['importpostid'] = $extra['RELATED_CONTENT_OID'];
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

	function get_eve_cat_details($Db, $dbtype, $prefix)
	{
		$return_array = array();

		if ($dbtype == 'mysql')
		{
			$IP_CAT_CATEGORY = 'IP_CAT_CATEGORY';
			
			if (lowercase_table_names)
				$IP_CAT_CATEGORY = strtolower($IP_CAT_CATEGORY);
			
 $result = $Db->query("
SELECT
CATEGORY_OID,
CATEGORY_NAME,
CATEGORY_DESCRIPTION,
IS_CATEGORY_HIDDEN,
THREADING_ORDER,
PARENT_CATEGORY_OID
FROM {$prefix}{$IP_CAT_CATEGORY}
ORDER BY PARENT_CATEGORY_OID ASC
"); 				
/*
			$result = $Db->query("
				SELECT 
					 CATEGORY_OID,
					 CATEGORY_NAME,
					 CATEGORY_DESCRIPTION,
					 IS_CATEGORY_HIDDEN,
					 THREADING_ORDER,
					 PARENT_CATEGORY_OID 
				FROM {$prefix}{$IP_CAT_CATEGORY}
				WHERE PARENT_CATEGORY_OID IS NULL
			");
*/
			while ($row = $Db->fetch_array($result)) {
				$return_array["$row[CATEGORY_OID]"] = $row;
			}
		}
		 
		return $return_array;
	}


	function get_eve_forum_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_F_FORUM 				= 'IP_F_FORUM';					# F
			$IP_CAT_RESOURCE_CATEGORY 	= 'IP_CAT_RESOURCE_CATEGORY';	# C
			$IP_CAT_CATEGORY			= 'IP_CAT_CATEGORY';			# CC
			
			if (lowercase_table_names) {
				$IP_F_FORUM 				= strtolower($IP_F_FORUM);
				$IP_CAT_RESOURCE_CATEGORY 	= strtolower($IP_CAT_RESOURCE_CATEGORY);
				$IP_CAT_CATEGORY			= strtolower($IP_CAT_CATEGORY);
			}
			/*
			$forums = $Db->query("
				SELECT * FROM {$IP_F_FORUM}
				WHERE PARENT_CATEGORY_OID IS NOT NULL
				ORDER BY THREADING_ORDER
				LIMIT {$offset}, {$limit}
			");				
			/*
			$forums = $Db->query("
				SELECT
					F.FORUM_OID, F.FORUM_INTRO, F.DATE_FORUM_CREATED,
					C.CATEGORY_OID, CC.CATEGORY_NAME, 
					CC.PARENT_CATEGORY_OID, CC.THREADING_ORDER
				FROM 
					{$prefix}{$IP_F_FORUM} AS F
					LEFT JOIN {$prefix}{$IP_CAT_RESOURCE_CATEGORY} AS C ON (F.FORUM_OID = C.RESOURCE_OID)
					INNER JOIN {$prefix}{$IP_CAT_CATEGORY} AS CC ON (F.FORUM_OID = CC.CATEGORY_OID AND CC.PARENT_CATEGORY_OID IS NOT NULL)
				WHERE CC.PARENT_CATEGORY_OID IS NOT NULL  
				ORDER BY F.FORUM_OID
				LIMIT {$offset}, {$limit}
			");				
*/
				
		$forums = $Db->query("
				SELECT
					F.FORUM_OID, F.FORUM_INTRO, F.DATE_FORUM_CREATED,
					C.CATEGORY_OID, CC.CATEGORY_NAME, 
					CC.PARENT_CATEGORY_OID, CC.THREADING_ORDER
				FROM 
					{$prefix}{$IP_F_FORUM} AS F
					LEFT JOIN {$prefix}{$IP_CAT_RESOURCE_CATEGORY} AS C ON (F.FORUM_OID = C.RESOURCE_OID)
					LEFT JOIN {$prefix}{$IP_CAT_CATEGORY} AS CC ON (F.FORUM_OID = CC.CATEGORY_OID)
				ORDER BY F.FORUM_OID
				LIMIT {$offset}, {$limit}");
				
			while ($forum = $Db->fetch_array($forums))
			{
				$return_array[$forum[FORUM_OID]] = $forum;
			}
		}
		 
		return $return_array;
	}


	/**
	* Returns the pmtext_id => pmtext array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_eve_pmtext_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_T_MESSAGE			= 'IP_T_MESSAGE';			# M
			$IP_C_CONTENT 			= 'IP_C_CONTENT';			# C 
			$IP_PT_PRIVATE_TOPIC	= 'IP_PT_PRIVATE_TOPIC';	# P
			$IP_T_TOPIC				= 'IP_T_TOPIC';				# T

			if (lowercase_table_names) {
				$IP_T_MESSAGE			= strtolower($IP_T_MESSAGE);
				$IP_C_CONTENT			= strtolower($IP_C_CONTENT);
				$IP_PT_PRIVATE_TOPIC	= strtolower($IP_PT_PRIVATE_TOPIC);
				$IP_T_TOPIC				= strtolower($IP_T_TOPIC);
			}			
			
			$result = $Db->query("
				SELECT
					M.BODY, M.TOPIC_OID,
					C.CONTENT_OID, C.AUTHOR_OID, C.DATETIME_CREATED, C.POSTER_IP,
					P.PRIVATE_TOPIC_OID,
					T.SUBJECT
				FROM {$prefix}{$IP_T_MESSAGE} AS M
					LEFT JOIN {$prefix}{$IP_C_CONTENT} AS C ON (M.MESSAGE_OID = C.CONTENT_OID)
					INNER JOIN {$prefix}{$IP_PT_PRIVATE_TOPIC} AS P ON (M.TOPIC_OID = P.PRIVATE_TOPIC_OID)
					LEFT JOIN {$prefix}{$IP_T_TOPIC} AS T ON (P.PRIVATE_TOPIC_OID = T.TOPIC_OID)
					ORDER BY C.CONTENT_OID
				LIMIT {$offset}, {$limit}			
			");				 

			while ($row = $Db->fetch_array($result)) {
				$return_array["$row[CONTENT_OID]"]	= $row;
			}	
		}
		
		return $return_array;
	}

	
	function get_eve_pm_recipitent_details($Db, $dbtype, $prefix, $p_topic_oid)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($p_topic_oid) OR empty($p_topic_oid))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_PT_PRIVATE_TOPIC_PARTICIPANT = 'IP_PT_PRIVATE_TOPIC_PARTICIPANT';	
			
			if (lowercase_table_names) {
				$IP_PT_PRIVATE_TOPIC_PARTICIPANT = strtolower($IP_PT_PRIVATE_TOPIC_PARTICIPANT);
			}			
			
			$result = $Db->query("
				SELECT
					USER_OID
				FROM {$prefix}{$IP_PT_PRIVATE_TOPIC_PARTICIPANT}
				WHERE
					PRIVATE_TOPIC_OID={$p_topic_oid}
			");				 

			while ($row = $Db->fetch_array($result)) {
				$return_array[]	= $row;
			}	
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
	function get_eve_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{

		$return_array = array();

		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT * FROM {$tableprefix}IP_P_POLL_QUESTION ORDER BY POLL_QUESTION_OID LIMIT {$start_at}, {$per_page}";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$choices = array();
				$votes = array();
				$poll_voters = array();

				// Options
				$options = $Db_object->query("SELECT * FROM " . $tableprefix . "IP_P_POLL_ANSWER WHERE POLL_QUESTION_OID=" . $detail['POLL_QUESTION_OID'] . " ORDER BY ANSWER_POSITION");

				while ($row = $Db_object->fetch_array($options))
				{
					$choices[]	= $row['ANSWER_TEXT'];
					$votes[]	= $row['MEMBER_VOTE_COUNT']; // Not counting guest votes GUEST_VOTE_COUNT
					$answers[] 	= $row['POLL_ANSWER_OID'];
				}

				// Voters
				$voters_choice = $Db_object->query("SELECT USER_OID FROM " . $tableprefix . "IP_P_POLL_RESPONSE WHERE POLL_ANSWER_OID IN (" .  implode(',', $answers) . ")");

				while ($choice = $Db_object->fetch_array($voters_choice))
				{
					$poll_voters["$choice[USER_OID]"] = '0'; // Not worth the effort to record their actual choice and a reverse lookup
				}

				// Thread ID
				$threadid = $Db_object->query_first("SELECT TOPIC_OID AS threadid FROM " . $tableprefix . "IP_T_TOPIC WHERE PRIMARY_TOPIC_CONTENT_OID=" . $detail['POLL_OID']);

				if ($threadid)
				{
					$return_array["$detail[POLL_QUESTION_OID]"]['threadid'] = $threadid['threadid'];
				}
				else
				{
					$threadid['threadid'] = NULL; // Let's hope we aren't here any time soon
				}

				$return_array["$detail[POLL_QUESTION_OID]"]['numberoptions'] = count($choices);
				$return_array["$detail[POLL_QUESTION_OID]"]['voters'] = array_sum($votes);
				$return_array["$detail[POLL_QUESTION_OID]"]['options'] = implode('|||', $choices);
				$return_array["$detail[POLL_QUESTION_OID]"]['votes'] = implode('|||', $votes);
				$return_array["$detail[POLL_QUESTION_OID]"]['poll_voters'] = $poll_voters;
				$return_array["$detail[POLL_QUESTION_OID]"]['question'] = substr($this->html_2_bb($detail['QUESTION']), 0, 99);
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
	function get_eve_post_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_T_MESSAGE 		= 'IP_T_MESSAGE'; 		# T
			$IP_C_CONTENT		= 'IP_C_CONTENT';		# C
			
			if (lowercase_table_names) {
				$IP_T_MESSAGE 		= strtolower($IP_T_MESSAGE);
				$IP_C_CONTENT 		= strtolower($IP_C_CONTENT);
			}
			
			$result = $Db->query("
				SELECT
				  T.MESSAGE_OID, T.TOPIC_OID, T.BODY, T.HAS_SIGNATURE, T.IS_MESSAGE_VISIBLE,
				  C.AUTHOR_OID, C.DATETIME_CREATED, C.POSTER_IP
				FROM 
					{$prefix}{$IP_T_MESSAGE} AS T
					LEFT JOIN {$prefix}{$IP_C_CONTENT} AS C ON (T.MESSAGE_OID = C.CONTENT_OID)
				WHERE T.MESSAGE_OID > {$offset}
				ORDER BY MESSAGE_OID
				LIMIT {$limit}			
			");	

			while ($row = $Db->fetch_array($result)) {
				$return_array['data']["$row[MESSAGE_OID]"] = $row;
				$return_array["lastid"] = $row['MESSAGE_OID'];
			}
		}
		
		$return_array["count"] = count($return_array["data"]);
		
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
	function get_eve_archive_post_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_T_ARCHIVED_MESSAGE 	= 'IP_T_ARCHIVED_MESSAGE';
			
			if (lowercase_table_names) 
				$IP_T_ARCHIVED_MESSAGE = strtolower($IP_T_ARCHIVED_MESSAGE);
							
			$result = $Db->query("
				SELECT 
					MESSAGE_OID, TOPIC_OID, AUTHOR_OID, DATETIME_CREATED, BODY, POSTER_IP, IS_VISIBLE
				FROM 
					{$prefix}{$IP_T_ARCHIVED_MESSAGE}
				WHERE MESSAGE_OID > {$offset}
				ORDER BY MESSAGE_OID
				LIMIT {$limit}			
			");	

			while ($row = $Db->fetch_array($result)) {
				$return_array['data']["$row[MESSAGE_OID]"] = $row;
				$return_array["lastid"] = $row['MESSAGE_OID'];
			}
		}
		
		$return_array["count"] = count($return_array["data"]);
		
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
	function get_eve_thread_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_F_FORUM_TOPIC 	= 'IP_F_FORUM_TOPIC'; 	# FT
			$IP_T_TOPIC			= 'IP_T_TOPIC';			# TT
			$IP_T_TOPIC_STATS	= 'IP_T_TOPIC_STATS';	# TS
			
			if (lowercase_table_names) {
				$IP_F_FORUM_TOPIC = strtolower($IP_F_FORUM_TOPIC);
				$IP_T_TOPIC = strtolower($IP_T_TOPIC);
				$IP_T_TOPIC_STATS = strtolower($IP_T_TOPIC_STATS);				
			}			
			
			$result = $Db->query("
				SELECT 
					 FT.FORUM_TOPIC_OID, FT.TOPIC_OID, FT.FORUM_OID, FT.LAST_TOPIC_POST_DATETIME, FT.TOPIC_POSTED_DATETIME, FT.IS_VISIBLE,
					 TT.SUBJECT, TT.IS_TOPIC_CLOSED, TT.IS_TOPIC_ARCHIVED
					 
				FROM 
					{$prefix}{$IP_F_FORUM_TOPIC} AS FT
					LEFT JOIN {$prefix}{$IP_T_TOPIC} AS TT ON (FT.TOPIC_OID = TT.TOPIC_OID)
					
				ORDER BY FT.TOPIC_OID
				LIMIT {$offset}, {$limit}			
			");				 
				# TS.TOPIC_POST_COUNT, TS.LAST_TOPIC_POST_DATETIME, TS.MESSAGE_PAGE_VIEW_COUNT
				
				# LEFT JOIN {$prefix}{$IP_T_TOPIC_STATS} AS TS ON (FT.TOPIC_OID = TS.TOPIC_OID)
			while ($row = $Db->fetch_array($result)) {
				$return_array[] = $row;
			}
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
	function get_eve_user_details($Db, $dbtype, $prefix, $offset, $limit)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(!is_numeric($offset) OR empty($limit))
			return $return_array;

		if ($dbtype == 'mysql')
		{
			$IP_USERS 					= 'IP_USERS';					# U
			$IP_PROFILES 				= 'IP_PROFILES';				# P
			$IP_USER_STATS 				= 'IP_USER_STATS';				# S
			$IP_BANNED_USERS			= 'IP_BANNED_USERS';			# B
			
			if (lowercase_table_names) {
				$IP_USERS = strtolower($IP_USERS); $IP_PROFILES = strtolower($IP_PROFILES);
				$IP_USER_STATS = strtolower($IP_USER_STATS); $IP_BANNED_USERS = strtolower($IP_BANNED_USERS);	
			}
#P.USER_IP,			
			$result = $Db->query("
				SELECT
					U.USER_OID, U.USER_STATUS, U.DISPLAY_NAME, 
					P.REGISTRATION_DATE, P.FIRST_NAME, P.LAST_NAME, P.USER_TITLE, P.DOB, P.AVATAR_URL, P.PICTURE_URL, P.HOME_PAGE_URL, P.LOCATION, P.SIGNATURE, P.OCCUPATION, P.INTERESTS, P.BIO, P.EMAIL, P.HAS_OPTED_OUT_OF_EMAIL,
					S.USER_POST_COUNT, S.CUMULATIVE_USER_POST_COUNT, S.KARMA_POINTS, S.LAST_LOGIN_DATETIME, S.USER_IP,
					B.BAN_REASON, B.BANNER_OID
				FROM 
					{$prefix}{$IP_USERS} AS U
					LEFT JOIN {$prefix}{$IP_PROFILES} AS P ON (U.USER_OID = P.USER_OID)
					LEFT JOIN {$prefix}{$IP_USER_STATS} AS S ON (U.USER_OID = S.USER_OID)
					LEFT JOIN {$prefix}{$IP_BANNED_USERS} AS B ON (U.USER_OID = B.USER_OID)
				WHERE U.USER_OID > {$offset}	
				ORDER BY U.USER_OID
				LIMIT {$limit}
			");
			
			while ($row1 = $Db->fetch_array($result)) {
				$return_array['data']["$row1[USER_OID]"] = $row1;
				$return_array["lastid"] = $row1['USER_OID'];
			}
		}
		
		$return_array["count"] = count($return_array["data"]);
		
		return $return_array;
	}


} // Class end
# Autogenerated on : March 8, 2005, 12:17 am
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

