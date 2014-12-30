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
* ubb_threads API module
*
* @package			ImpEx.ubb_threads
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ubb_threads_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '6.5';
	var $_tier = '1';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Infopop UBB.threads';
	var $_homepage 	= 'http://www.ubbcentral.com/ubbthreads/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'AddressBook', 'Banned', 'Boards', 'Category', 'DisplayNames', 'Events',
		'Favorites', 'Graemlins', 'Groups', 'IIPcache', 'Languages', 'Last',
		'Mailer', 'Messages', 'ModNotify', 'Moderators', 'Online', 'PollMain',
		'PollOptions', 'PollQuestions', 'PollVotes', 'Posts', 'Ratings', 'Subscribe', 'Users'
	);


	function ubb_threads_000()
	{
	}

	function ubb_threads_html_2_bb($text)
	{
		$text = str_replace('</font><blockquote><font class=\"small\">Quote:</font><hr />', '[QUOTE]', $text);

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
	function get_ubb_threads_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'U_Number'		=> 'mandatory',
			'U_Username'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Users", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT U_Number,U_Username FROM {$tableprefix}Users ORDER BY U_Number LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
					$tempArray = array($user['U_Number'] => $user['U_Username']);
					$return_array = $return_array + $tempArray;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}


	/**
	* Returns the cat_id => cat array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ubb_threads_cat_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		$req_fields = array(
			'Cat_Title'		=> 'mandatory',
			'Cat_Number'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Category", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Category ORDER BY Cat_Number");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[Cat_Number]"] = $detail;
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
	function get_ubb_threads_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'Bo_Title'		=> 'mandatory',
			'Bo_Sorter'		=> 'mandatory',
			'Bo_Cat'		=> 'mandatory',
			'Bo_Number'		=> 'mandatory',
			'Bo_Keyword'	=> 'mandatory' // For get_forum_by_keyword()
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Boards", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Boards ORDER BY Bo_Number LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[Bo_Number]"] = $detail;
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
	function get_ubb_threads_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'Mod_Uid'		=> 'mandatory',
			'Mod_Board'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Moderators", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Moderators ORDER BY Mod_Uid LIMIT {$start_at}, {$per_page}");

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
	function get_ubb_threads_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if (!is_numeric($start_at)) { $start_at=0; }

		$req_fields = array(
			'M_Uid'			=> 'mandatory',
			'M_Sender'		=> 'mandatory',
			'M_Subject'		=> 'mandatory',
			'M_Message'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Messages", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Messages ORDER BY M_Number LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[M_Number]"] = $detail;
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
	function get_ubb_threads_poll_question(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'P_Question'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "PollQuestions", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}PollQuestions ORDER BY P_QuestionNum LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[P_QuestionNum]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ubb_threads_poll_thread_id(&$Db_object, &$databasetype, &$tableprefix, $Poll_id)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$details = $Db_object->query_first("SELECT B_Main FROM {$tableprefix}Posts WHERE B_Poll = {$Poll_id}");

			return $details[0];
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
	function get_ubb_threads_poll_options(&$Db_object, &$databasetype, &$tableprefix, $Poll_id, $question_num)
	{
		$return_array = array();

		$req_fields = array(
			'P_PollId'		=> 'mandatory',
			'P_OptionNum'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "PollOptions", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}PollOptions WHERE P_QuestionNum={$question_num}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[P_OptionNum]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_ubb_threads_poll_votes(&$Db_object, &$databasetype, &$tableprefix, $option, $questionnum)
	{
		$return_array = array();

		$req_fields = array(
			'P_OptionNum'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "PollVotes", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			// This isn't an indexed field by default ......
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PollVotes WHERE P_OptionNum={$option} and P_QuestionNum={$questionnum}");

			return $count[0];
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
	function get_ubb_threads_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'B_Main'		=> 'mandatory',
			'B_PosterId' 	=> 'mandatory',
			'B_Main'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Posts", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Posts WHERE B_Number > {$start_at} ORDER BY B_Number LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($return_array['data']["$detail[B_Number]"])
				{
					// Dupe id
					$return_array['data'][] = $detail;
					// Though this might have a knock on effect for the B_Number that it takes that does exsist, this can't be used for import id
				}
				else
				{
					$return_array['data']["$detail[B_Number]"] = $detail;
				}
				$return_array['lastid'] = $detail['B_Number'];
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
	function get_ubb_threads_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Posts WHERE B_Topic=1 AND B_Number > {$start_at} ORDER BY B_Number LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($return_array['data']["$detail[B_Number]"])
				{
					// Though this might have a knock on effect for the B_Number that it takes that does exsist, this can't be used for import id
					$return_array['data'][] = $detail;
				}
				else
				{
					$return_array['data']["$detail[B_Number]"] = $detail;
				}
				$return_array['lastid'] = $detail['B_Number'];
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_forum_by_keyword(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();


		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT Bo_Keyword, Bo_Number FROM {$tableprefix}Boards");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[Bo_Keyword]"] = $detail['Bo_Number'];
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
	function get_ubb_threads_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'U_Number'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "Users", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}Users WHERE U_Number > {$start_at} ORDER BY U_Number LIMIT {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($return_array['data']["$detail[U_Number]"])
				{
					// Though this might have a knock on effect for the U_Number that it takes that does exsist, this can't be used for import id
					$return_array['data'][] = $detail;
				}
				else
				{
					$return_array['data']["$detail[U_Number]"] = $detail;
				}
				$return_array['lastid'] = $detail['U_Number'];
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
	function get_ubb_threads_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."Groups
			ORDER BY G_Id
			";


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				if($detail['G_Id'])
				{
					$return_array["$detail[G_Id]"] = $detail;
				}
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}



	function get_ubb_threads_post_parentid(&$Db_object, &$databasetype, &$tableprefix, &$post_id)
	{
		$return_array = array();


		if($post_id == 0)
		{
			return 0;
		}


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT postid
			FROM " .
			$tableprefix . "post
			WHERE importpostid = '{$post_id}'
			";

			$post = $Db_object->query_first($sql);

			return $post[0];
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ubb_threads_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT B_Number, B_File, B_Posted, B_FileCounter FROM " .
			$tableprefix."Posts
			WHERE B_File != ''
			ORDER BY B_Number
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[B_Number]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ubb_threads_attachment($path, $file_name)
	{
		$file_address = $path . "/" . $file_name;

		if($file_name == '' OR !is_file($file_address))
		{
			return false;
		}


		$the_file = array();
		$file = fopen($file_address,'rb');

		if($file AND filesize($file_address) > 0)
		{
			$the_file['data']		= fread($file, filesize($file_address));
			$the_file['filesize']	= filesize($file_address);
			$the_file['filehash']	= md5($the_file['data']);
		}

		return $the_file;
	}

} // Class end
# Autogenerated on : May 17, 2004, 10:34 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>

