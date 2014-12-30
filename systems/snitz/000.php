<?php if (!defined('IDIR')) { die; }
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
* snitz API module
*
* @package			ImpEx.snitz
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class snitz_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.4.04';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Snitz Mysql & MSSQL';
	var $_homepage 	= 'http://forum.snitz.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'ACTIVE_USERS', 'ALBUM', 'ALBUM_CAT', 'ALBUM_CONFIG', 'ALBUM_USERS', 'ALLOWED_MEMBERS',
		'AVATAR', 'AVATAR2', 'A_REPLY', 'A_TOPICS', 'BADWORDS', 'BOOKMARKS', 'CATEGORY', 'CONFIG_NEW',
		'FILELISTER', 'FILELISTER_CAT', 'FILELISTER_CONFIG', 'FILELISTER_USERS', 'FILES', 'FORUM',
		'GB_OPTIONS', 'GROUPS', 'GROUP_NAMES', 'GUESTBOOK', 'IPLIST', 'IPLOG', 'MAILLIST', 'MEMBERS',
		'MEMBERS_PENDING', 'MODERATOR', 'NAMEFILTER', 'NOTES', 'PM', 'POLLS', 'POLL_VOTES', 'RATINGS',
		'REPLY', 'REV', 'REVIEWS', 'REV_OPTIONS', 'SMILES', 'SMILES2', 'SMILES_CUSTOM', 'SUBSCRIPTIONS',
		'TOPICS', 'TOTALS', 'ANNOUNCE'
	);


	function snitz_000()
	{
	}


	function snitz_html($text)
	{
		// <font> tags
		$text = preg_replace('#<font(.*)>#siU', '', $text);
		$text = preg_replace('#</font(.*)>#siU', '', $text);

		// quotes
		$text = str_replace('<blockquote id="quote">', '[quote]', $text);
		$text = str_replace('</blockquote id="quote">', '[/quote]', $text);

		$text = str_replace('<BLOCKQUOTE id=quote>', '[quote]', $text);
		$text = str_replace('</BLOCKQUOTE id=quote>', '[/quote]', $text);

		$text = preg_replace('#<blockquote id="quote"><font(.*)Original message by(.*)</i><br />(.*)<hr(.*)</font id="quote">#siU', '[quote=$2]$3[/quote]', $text);

		// html
		$text = preg_replace('#<hr(.*)>#siU', '', $text);
		$text = str_replace('<ul>', '[list]', $text);

		$text = str_replace('<u>', '[u]', $text);

		//Smilies
		$text = str_replace('[:D]', ':D', $text);
		$text = str_replace('[;)]', ';)', $text);
		$text = str_replace('[:(!]', ':(', $text);
		$text = str_replace('[:(]', ':(', $text);
		$text = str_replace('[:)]', ':)', $text);
		$text = str_replace(';-)', ';)', $text);

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
	function get_snitz_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		$req_fields = array(
			'MEMBER_ID'	=> 'mandatory',
			'M_NAME'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "members" : "MEMBERS" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT MEMBER_ID, M_NAME FROM " . $tableprefix . (lowercase_table_names ? "members" : "MEMBERS" ) . " ORDER BY MEMBER_ID LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$tempArray = array($user['MEMBER_ID'] => $user['M_NAME']);
				$return_array = $return_array + $tempArray;
			}
			return $return_array;
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}MEMBERS");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	MEMBER_ID,
							M_NAME
					FROM {$tableprefix}MEMBERS WHERE MEMBER_ID
						IN(SELECT TOP {$per_page} MEMBER_ID
							FROM (SELECT TOP {$internal} MEMBER_ID FROM {$tableprefix}MEMBERS ORDER BY MEMBER_ID)
						A ORDER BY MEMBER_ID DESC)
					ORDER BY MEMBER_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{

					$return_array["$user[MEMBER_ID]"] = $user['M_NAME'];
			}

			return $return_array;
		}
		else
		{
			return false;
		}
	}


	/**
	* Returns the cat_id => category array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_snitz_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		$req_fields = array(
			'CAT_NAME'	=> 'mandatory',
			'CAT_ORDER'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "category" : "CATEGORY" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "category" : "CATEGORY" ));

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[CAT_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$sql = "SELECT 	CAT_ID,
							CAT_NAME,
							CAT_ORDER
					FROM {$tableprefix}CATEGORY";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[CAT_ID]"] = $detail;
			}

			return $return_array;
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
	function get_snitz_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'F_SUBJECT'	=> 'mandatory',
			'F_ORDER'	=> 'mandatory',
			'CAT_ID'	=> 'mandatory',
			'FORUM_ID'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "forum" : "FORUM" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "forum" : "FORUM" ) . " ORDER BY FORUM_ID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[FORUM_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}FORUM");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						F_SUBJECT,
						F_ORDER,
						CAT_ID,
						FORUM_ID,
						F_COUNT,
						F_TOPICS,
						F_LAST_POST,
						F_LAST_POST_REPLY_ID,
						CAST([F_DESCRIPTION] as TEXT) as F_DESCRIPTION
					FROM {$tableprefix}FORUM WHERE FORUM_ID
						IN(SELECT TOP {$per_page} FORUM_ID
							FROM (SELECT TOP {$internal} FORUM_ID FROM {$tableprefix}FORUM ORDER BY FORUM_ID)
						A ORDER BY FORUM_ID DESC)
					ORDER BY FORUM_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[FORUM_ID]"] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the moderator_id => moderator array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_snitz_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'MOD_ID'	=> 'mandatory',
			'FORUM_ID'	=> 'mandatory',
			'MEMBER_ID'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "moderator" : "MODERATOR" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "moderator" : "MODERATOR" ) . " ORDER BY MOD_ID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[MOD_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}MODERATOR");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						MOD_ID,
						FORUM_ID,
						MEMBER_ID,
						MOD_TYPE
					FROM {$tableprefix}MODERATOR WHERE MOD_ID
						IN(SELECT TOP {$per_page} MOD_ID
							FROM (SELECT TOP {$internal} MOD_ID FROM {$tableprefix}MODERATOR ORDER BY MOD_ID)
						A ORDER BY MOD_ID DESC)
					ORDER BY MOD_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[MOD_ID]"] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
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
	function get_snitz_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'M_SUBJECT'	=> 'mandatory',
			'M_FROM'	=> 'mandatory',
			'M_SENT'	=> 'mandatory',
			'M_TO'		=> 'mandatory',
			'M_MESSAGE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "pm" : "PM" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "pm" : "PM" )))
			{
				$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "pm" : "PM" ) . " ORDER BY M_ID LIMIT {$start_at}, {$per_page}");

				while ($detail = $Db_object->fetch_array($details_list))
				{
					$return_array["$detail[M_ID]"] = $detail;
				}
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PM");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						M_ID,
						M_SUBJECT,
						M_FROM,
						M_TO,
						M_SENT,
						CAST([M_MESSAGE] as TEXT) as M_MESSAGE,
						M_PMCOUNT,
						M_READ,
						M_MAIL,
						M_OUTBOX
					FROM {$tableprefix}PM WHERE M_ID
						IN(SELECT TOP {$per_page} M_ID
							FROM (SELECT TOP {$internal} M_ID FROM {$tableprefix}PM ORDER BY M_ID)
						A ORDER BY M_ID DESC)
					ORDER BY M_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[M_ID]"] = $detail;
			}

			return $return_array;
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
	function get_snitz_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'POLL_ID'	=> 'mandatory',
			'CAT_ID'	=> 'mandatory',
			'FORUM_ID'	=> 'mandatory',
			'TOPIC_ID'	=> 'mandatory'
		);

		if ($databasetype == 'mysql')
		{
			if($this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "polls" : "POLLS" )))
			{
				$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "polls" : "POLLS" ) . " ORDER BY POLL_ID LIMIT {$start_at}, {$per_page}");

				while ($detail = $Db_object->fetch_array($details_list))
				{
					$return_array["$detail[POLL_ID]"] = $detail;
				}
			}
		}
		else if ($databasetype == 'mssql')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}POLLS");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						*
					FROM {$tableprefix}POLLS WHERE POLL_ID
						IN(SELECT TOP {$per_page} POLL_ID
							FROM (SELECT TOP {$internal} POLL_ID FROM {$tableprefix}POLLS ORDER BY POLL_ID)
						A ORDER BY POLL_ID DESC)
					ORDER BY POLL_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[POLL_ID]"] = $detail;
			}

			return $return_array;
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
	function get_snitz_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'REPLY_ID'	=> 'mandatory',
			'TOPIC_ID'	=> 'mandatory',
			'R_AUTHOR'	=> 'mandatory',
			'R_DATE'	=> 'mandatory',
			'R_MESSAGE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "reply" : "REPLY" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "reply" : "REPLY" ) . " WHERE REPLY_ID > {$start_at} ORDER BY REPLY_ID LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}REPLY");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						REPLY_ID,
						TOPIC_ID,
						R_AUTHOR,
						R_DATE,
						R_SIG,
						R_IP,
						R_STATUS,
						CAST([R_MESSAGE] as TEXT) as R_MESSAGE
					FROM {$tableprefix}REPLY WHERE REPLY_ID
						IN(SELECT TOP {$per_page} REPLY_ID
							FROM (SELECT TOP {$internal} REPLY_ID FROM {$tableprefix}REPLY ORDER BY REPLY_ID)
						A ORDER BY REPLY_ID DESC)
					ORDER BY REPLY_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_snitz_archive_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		$req_fields = array(
			'REPLY_ID'	=> 'mandatory',
			'TOPIC_ID'	=> 'mandatory',
			'R_AUTHOR'	=> 'mandatory',
			'R_DATE'	=> 'mandatory',
			'R_MESSAGE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "a_reply" : "A_REPLY" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "a_reply" : "A_REPLY" ) . " WHERE REPLY_ID > {$start_at} ORDER BY REPLY_ID LIMIT {$per_page}");
				
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}A_REPLY");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						REPLY_ID,
						TOPIC_ID,
						R_AUTHOR,
						R_DATE,
						R_SIG,
						R_IP,
						R_STATUS,
						CAST([R_MESSAGE] as TEXT) as R_MESSAGE
					FROM {$tableprefix}A_REPLY WHERE REPLY_ID
						IN(SELECT TOP {$per_page} REPLY_ID
							FROM (SELECT TOP {$internal} REPLY_ID FROM {$tableprefix}A_REPLY ORDER BY REPLY_ID)
						A ORDER BY REPLY_ID DESC)
					ORDER BY REPLY_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
			}

			return $return_array;
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
	function get_snitz_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'S_CODE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "smiles" : "SMILES" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "smiles" : "SMILES" )))
			{
				return $return_array;
			}

			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "smiles" : "SMILES" ) . " ORDER BY S_ID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[S_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}SMILES");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						S_ID,
						S_CODE,
						S_URL,
						S_DESC,
						S_VISIBLE,
						S_ENABLED,
						S_COLSPAN,
						S_DEFAULT,
						S_EXCLUDE
					FROM {$tableprefix}SMILES WHERE S_ID
						IN(SELECT TOP {$per_page} S_ID
							FROM (SELECT TOP {$internal} S_ID FROM {$tableprefix}SMILES ORDER BY S_ID)
						A ORDER BY S_ID DESC)
					ORDER BY S_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[S_ID]"] = $detail;
			}

			return $return_array;
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
	function get_snitz_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'T_SUBJECT'	=> 'mandatory',
			'FORUM_ID'	=> 'mandatory',
			'TOPIC_ID'	=> 'mandatory',
			'T_DATE'	=> 'mandatory',
			'T_MESSAGE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "topics" : "TOPICS" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "topics" : "TOPICS" ) . " ORDER BY TOPIC_ID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TOPIC_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}TOPICS");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						T_SUBJECT,
						FORUM_ID,
						TOPIC_ID,
						T_STATUS,
						T_REPLIES,
						T_AUTHOR,
						T_LAST_POST_AUTHOR,
						T_DATE,
						T_VIEW_COUNT,
						T_STICKY,
						T_IP,
						CAST([T_MESSAGE] as TEXT) as T_MESSAGE
					FROM {$tableprefix}TOPICS WHERE TOPIC_ID
						IN(SELECT TOP {$per_page} TOPIC_ID
							FROM (SELECT TOP {$internal} TOPIC_ID FROM {$tableprefix}TOPICS ORDER BY TOPIC_ID)
						A ORDER BY TOPIC_ID DESC)
					ORDER BY TOPIC_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TOPIC_ID]"] = $detail;
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_snitz_archive_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$req_fields = array(
			'T_SUBJECT'	=> 'mandatory',
			'FORUM_ID'	=> 'mandatory',
			'TOPIC_ID'	=> 'mandatory',
			'T_DATE'	=> 'mandatory',
			'T_MESSAGE'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "a_topics" : "A_TOPICS" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "a_topics" : "A_TOPICS" ) . " ORDER BY TOPIC_ID LIMIT {$start_at}, {$per_page}");
	
			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TOPIC_ID]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}A_TOPICS");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						T_SUBJECT,
						FORUM_ID,
						TOPIC_ID,
						T_STATUS,
						T_REPLIES,
						T_AUTHOR,
						T_LAST_POST_AUTHOR,
						T_DATE,
						T_VIEW_COUNT,
						T_STICKY,
						T_IP,
						CAST([T_MESSAGE] as TEXT) as T_MESSAGE
					FROM {$tableprefix}A_TOPICS WHERE TOPIC_ID
						IN(SELECT TOP {$per_page} TOPIC_ID
							FROM (SELECT TOP {$internal} TOPIC_ID FROM {$tableprefix}A_TOPICS ORDER BY TOPIC_ID)
						A ORDER BY TOPIC_ID DESC)
					ORDER BY TOPIC_ID";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[TOPIC_ID]"] = $detail;
			}

			return $return_array;
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
	function get_snitz_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		$do_avatar = false;

		$req_fields = array(
			'M_NAME'	=> 'mandatory',
			'M_EMAIL'	=> 'mandatory',
			'MEMBER_ID'	=> 'mandatory',
			'M_PASSWORD'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "members" : "MEMBERS" ), $req_fields))
		{
			return $return_array;
		}

		if($this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "avatar" : "AVATAR" )))
		{
			$do_avatar = true;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM " . $tableprefix . (lowercase_table_names ? "members" : "MEMBERS" ) . " ORDER BY MEMBER_ID LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[MEMBER_ID]"] = $detail;

				if($do_avatar)
				{
					$avatar = $Db_object->query_first("SELECT A_URL FROM " . $tableprefix . (lowercase_table_names ? "avatar" : "AVATAR" ) . " WHERE A_MEMBER_ID=" . $detail['MEMBER_ID']);
					$return_array["$detail[MEMBER_ID]"]['avatar'] = $avatar['A_URL'];
				}
			}
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}MEMBERS");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT
						M_NAME,
						M_EMAIL,
						MEMBER_ID,
						M_HOMEPAGE,
						M_ICQ,
						M_AIM,
						M_YAHOO,
						M_MSN,
						M_TITLE,
						M_PASSWORD,
						M_DATE,
						M_LASTPOSTDATE,
						M_POSTS,
						M_DOB,
						M_IP,
						M_CITY,
						M_STATE,
						M_COUNTRY,
						M_OCCUPATION,
						CAST([M_HOBBIES] as TEXT) as M_HOBBIES,
						CAST([M_SIG] as TEXT) as M_SIG
					FROM {$tableprefix}MEMBERS WHERE MEMBER_ID
						IN(SELECT TOP {$per_page} MEMBER_ID
							FROM (SELECT TOP {$internal} MEMBER_ID FROM {$tableprefix}MEMBERS ORDER BY MEMBER_ID)
						A ORDER BY MEMBER_ID DESC)
					ORDER BY MEMBER_ID";

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[MEMBER_ID]"] = $user;
				if ($do_avatar)
				{
					$avatar = $Db_object->query_first("SELECT A_URL FROM " . $tableprefix . (lowercase_table_names ? "avatar" : "AVATAR" ) . " WHERE A_MEMBER_ID=" . $user['MEMBER_ID']);				$return_array["$user[MEMBER_ID]"]['avatar'] = $avatar['A_URL'];
					$return_array["$user[MEMBER_ID]"]['avatar'] = $avatar['A_URL'];
				}
			}

			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_snitz_question($Db_object, $databasetype, $tableprefix, $thread_id)
	{

		$sql='';

		$req_fields = array(
			'T_SUBJECT'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, (lowercase_table_names ? "topics" : "TOPICS" ), $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT T_SUBJECT FROM " . $tableprefix . (lowercase_table_names ? "topics" : "TOPICS" ) . " WHERE TOPIC_ID={$thread_id}";
		}
		else if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$sql = "SELECT T_SUBJECT FROM " . $tableprefix."TOPICS WHERE TOPIC_ID={$thread_id}";
		}
		else
		{
			return false;
		}

		$detail = $Db_object->query_first($sql);
		return $detail['T_SUBJECT'];
	}

} // Class end
# Autogenerated on : May 20, 2004, 12:45 am
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
