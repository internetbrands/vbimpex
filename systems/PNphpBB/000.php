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
* PNphpBB API module
*
* @package			ImpEx.PNphpBB
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class PNphpBB_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'PNphpBB2 (Post Nuke)';
	var $_homepage 	= 'http://www.pnphpbb.com';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
			'phpbb_attach_quota', 'phpbb_attachments', 'phpbb_attachments_config', 'phpbb_attachments_desc', 'phpbb_auth_access',
			'phpbb_banlist', 'phpbb_categories', 'phpbb_config', 'phpbb_confirm', 'phpbb_disallow', 'phpbb_extension_groups',
			'phpbb_extensions', 'phpbb_forbidden_extensions', 'phpbb_forum_prune', 'phpbb_forums', 'phpbb_groups', 'phpbb_posts',
			'phpbb_posts_text', 'phpbb_privmsgs', 'phpbb_privmsgs_text', 'phpbb_quota_limits', 'phpbb_ranks', 'phpbb_search_results',
			'phpbb_search_wordlist', 'phpbb_search_wordmatch', 'phpbb_sessions', 'phpbb_smilies', 'phpbb_themes', 'phpbb_themes_name',
			'phpbb_topics', 'phpbb_topics_watch', 'phpbb_user_group', 'phpbb_users', 'phpbb_vote_desc', 'phpbb_vote_results',
			'phpbb_vote_voters', 'phpbb_words'
	);


	function PNphpBB_000()
	{
	}


	/**
	* HTML parser
	*
	* @param	string	mixed			The string to parse
	* @param	boolean					Truncate smilies
	*
	* @return	array
	*/
	function PNphpBB_html($text, $truncate_smilies = false)
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

		// Smiles
		// Get just truncated phpBB smilies for this one to do the replacments

		if($truncate_smilies)
		{
			$text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
		}

		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&quot;', '"', $text);

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
	function get_PNphpBB_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT user_id,username
			FROM " . $tableprefix . "phpbb_users
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
	function get_PNphpBB_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_attachments
			ORDER BY attach_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$file_info = $Db_object->query_first("SELECT * FROM " . $tableprefix . "phpbb_attachments_desc WHERE attach_id=" . $detail['attach_id']);

				$return_array["$detail[attach_id]"]['postid'] 		= $detail['post_id'];
				$return_array["$detail[attach_id]"]['userid'] 		= $detail['user_id_1'];
				$return_array["$detail[attach_id]"]['filename'] 	= $file_info['physical_filename'];
				$return_array["$detail[attach_id]"]['downloads']	= $file_info['download_count'];
				$return_array["$detail[attach_id]"]['filesize']		= $file_info['filesize'];
				$return_array["$detail[attach_id]"]['filetime']		= $file_info['filetime'];
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
	function get_PNphpBB_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_groups
			WHERE group_single_user=0
			ORDER BY group_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[group_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_PNphpBB_usergroupids(&$Db_object, &$databasetype, &$tableprefix, $user_id)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($user_id)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_user_group
			WHERE user_id = " . $user_id . "
			ORDER BY group_id";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($detail['group_id'] != $user_id AND $detail['group_id'])
				{
					$return_array[] = $detail['group_id'];
				}
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
	function get_PNphpBB_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_forums
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


	function get_pnphpbb_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_categories
			ORDER BY cat_id
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
	function get_PNphpBB_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_privmsgs
			WHERE privmsgs_type IN (0,1,3,4,5) 
			ORDER BY privmsgs_id
			LIMIT " .
			$start_at .",".
			$per_page
			;

			$pms = $Db_object->query($sql);

			while ($pm = $Db_object->fetch_array($pms))
			{
				$return_array["$pm[privmsgs_id]"] = $pm;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

 
	function get_PNphpBB_pm_text(&$DB_object, &$database_type, &$table_prefix, &$pm_id)
	{
		if ($database_type == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$table_prefix."phpbb_privmsgs_text
			WHERE privmsgs_text_id ='" . $pm_id ."'"
			;

			$pms = $DB_object->query_first($sql);

			return $pms;
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
	function get_PNphpBB_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."poll
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
	function get_PNphpBB_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_posts
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

	function get_PNphpBB_post_text(&$DB_object, &$database_type, &$table_prefix, &$post_id)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{

			$sql = "
			SELECT post_text,post_subject FROM " .
			$table_prefix."phpbb_posts_text
			WHERE
			post_id='$post_id'
			";

			$posts_text = $DB_object->query($sql);

			while ($text = $DB_object->fetch_array($posts_text))
			{

				$return_array = array(
					'post_text' => $text['post_text'],
					'post_subject' => $text['post_subject']
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

	function get_PNphpBB_truncated_smilies(&$DB_object, &$database_type, &$table_prefix)
	{
		$return_array = array();
		if ($database_type == 'mysql')
		{
			$sql = "
			SELECT code FROM " .
			$table_prefix."phpbb_smilies
			";

			$smilies = $DB_object->query($sql);

			while ($smilie = $DB_object->fetch_array($smilies))
			{
				if(strlen($smilie['code']) > 20)
				{
					 $return_array[$smilie['code']] =  substr($smilie['code'],0,19) . ':';
				}
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
	function get_PNphpBB_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_smilies
			ORDER BY smilies_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[smilies_id]"] = $detail;
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
	function get_PNphpBB_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_topics
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
	function get_PNphpBB_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."phpbb_users
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


	function reverse_ip($ip)
	{
		$hexy_thing = explode('.', chunk_split($ip, 2, '.'));
		return hexdec($hexy_thing[0]). '.' . hexdec($hexy_thing[1]) . '.' . hexdec($hexy_thing[2]) . '.' . hexdec($hexy_thing[3]);
	}

} // Class end
# Autogenerated on : September 16, 2004, 12:14 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
