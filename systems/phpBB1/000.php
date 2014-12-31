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
* phpBB1 API module
*
* @package			ImpEx.phpBB1
*
*/
class phpBB1_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.4.x';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'phpBB1';
	var $_homepage 	= 'http://www.phpbb.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'Moderator_Action', 'access', 'backup', 'banlist', 'categories', 'config', 'disallow', 'forum_access',
		'forum_mods', 'forums', 'free_email', 'headermetafooter', 'ignore_list', 'installdefs', 'lforums',
		'markread', 'poll_data', 'poll_topics', 'posts', 'posts_text', 'priv_msgs', 'ranks', 'sessionkeys',
		'sessions', 'smiles', 'spellcheck_words', 'subscribe', 'themes', 'topics', 'users', 'visitors', 'whosonline', 'words'
	);


	function phpBB1_000()
	{
	}


	/**
	* Parses and custom HTML for phpBB1
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	/**
	* HTML parser
	*
	* @param	string	mixed			The string to parse
	* @param	boolean					Truncate smilies
	*
	* @return	array
	*/
	function phpbb_html($text)
	{
		// Quotes
		// With name

		for($i=0;$i<10;$i++)
		{
			$text = preg_replace('#\[quote:([a-z0-9]+)="(.*)"\](.*)\[/quote:\\1\]#siU', '[quote=$2]$3[/quote]', $text);
		}
			// Without
		for($i=0;$i<10;$i++)
		{
			$text = preg_replace('#\[quote:([a-z0-9]+)\](.*)\[/quote:\\1\]#siU', '[quote]$2[/quote]', $text);
		}

		$text = preg_replace('#\[code:([0-9]+):([a-z0-9]+)\](.*)\[/code:\\1:\\2\]#siU', '[code]$3[/code]', $text);

		// Bold , Underline, Italic
		$text = preg_replace('#\[b:([a-z0-9]+)\](.*)\[/b:\\1\]#siU', '[b]$2[/b]', $text);
		$text = preg_replace('#\[u:([a-z0-9]+)\](.*)\[/u:\\1\]#siU', '[u]$2[/u]', $text);
		$text = preg_replace('#\[i:([a-z0-9]+)\](.*)\[/i:\\1\]#siU', '[i]$2[/i]', $text);

		// Images
		$text = preg_replace('#\[img:([a-z0-9]+)\](.*)\[/img:\\1\]#siU', '[img]$2[/img]', $text);

		// Lists
		$text = preg_replace('#\[list(=1|=a)?:([a-z0-9]+)\](.*)\[/list:(u:|o:)?\\2\]#siU', '[list$1]$3[/list]', $text);

		// Lists items
		$text = preg_replace('#\[\*:([a-z0-9]+)\]#siU', '[*]', $text);

		// Color
		$text = preg_replace('#\[color=([^:]*):([a-z0-9]+)\](.*)\[/color:\\2\]#siU', '[color=$1]$3[/color]', $text);

		// Font
		$text = preg_replace('#\[font=([^:]*):([a-z0-9]+)\](.*)\[/font:\\2\]#siU', '[font=$1]$3[/font]', $text);

		// Text size
		$text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);

		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&quot;', '"', $text);

		$text = str_replace('</SPAN><TABLE BORDER=0 ALIGN=CENTER WIDTH=85%><TR><TD CLASS=$row_color><SPAN CLASS=$row_color>','[quote]',$text);
		$text = str_replace('<HR></SPAN></TD></TR><TR><TD CLASS=$row_color><SPAN CLASS=$row_color>','',$text);

		$text = str_replace('</SPAN></TD></TR><TR><TD><SPAN CLASS=$row_color><HR></SPAN></TD></TR></TABLE><SPAN CLASS=$row_color>','[/quote]',$text);


		return $text;
	}

	/**
	* Regex call back
	*
	* @param	string	mixed			The origional size
	* @param	string	mixed			The content text
	*
	* @return	array
	*/
	function pixel_size_mapping($size, $text)
	{
		$text = str_replace('\"', '"', $text);

		if ($size <= 8)
		{
		   $outsize = 1;
		}
		else if ($size <= 10)
		{
		   $outsize = 2;
		}
		else if ($size <= 12)
		{
		   $outsize = 3;
		}
		else if ($size <= 14)
		{
		   $outsize = 4;
		}
		else if ($size <= 16)
		{
		   $outsize = 5;
		}
		else if ($size <= 18)
		{
		   $outsize = 6;
		}
		else
		{
		   $outsize = 7;
		}

		return '[size=' . $outsize . ']' . $text .'[/size]';
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
	function get_phpBB1_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT user_id,username
			FROM " . $tableprefix . "users
			ORDER BY user_id
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[user_id]"] = $user['username'];
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
	function get_phpBB1_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums
			ORDER BY forum_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[forum_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_phpBB1_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."categories
			";

			$details_list = $Db_object->query($sql);

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
	function get_phpBB1_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forum_mods
			ORDER BY forum_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
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
	function get_phpBB1_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."priv_msgs
			ORDER BY msg_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[msg_id]"] = $detail;
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
	function get_phpBB1_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page) OR !$this->check_table($DB_object, $database_type, $table_prefix, 'poll_topics'))
		{
			return $return_array;
		}


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."poll_topics
			ORDER BY poll_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


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
	function get_phpBB1_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."posts
			ORDER BY post_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[post_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_phpbb_post_text(&$DB_object, &$database_type, &$table_prefix, &$post_id)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{

			$sql = "
			SELECT post_text, post_title FROM " .
			$table_prefix."posts_text
			WHERE
			post_id='$post_id'
			";

			$posts_text = $DB_object->query($sql);

			while ($text = $DB_object->fetch_array($posts_text))
			{

				$return_array = array(
					'post_text' => $text['post_text'],
					'post_subject' => $text['post_title']
					);
				unset($text);
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
	function get_phpBB1_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smiles
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
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
	function get_phpBB1_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."topics
			ORDER BY topic_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[topic_id]"] = $detail;
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
	function get_phpBB1_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."users
			ORDER BY user_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[user_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_phpbb1_vote_voters(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."poll_data
			WHERE poll_id = " . $poll_id;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[vote_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_phpbb1_poll_thread_id(&$Db_object, &$databasetype, &$tableprefix, $poll_id)
	{
		// Check that there is not a empty value
		if(empty($poll_id)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT topic_id FROM " . $tableprefix . "topics WHERE poll_id = " . $poll_id;

			$details = $Db_object->query_first($sql);

			return $details['topic_id'];
		}
		else
		{
			return false;
		}
		return $return_array;
	}

} // Class end
# Autogenerated on : December 13, 2004, 9:50 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
