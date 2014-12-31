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
* yabb2 API module
*
* @package			ImpEx.yabb2
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class yabb2_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.1';
	var $_dupe_checking = false;
	var $_tier = '1';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'YaBB 2';
	var $_homepage 	= 'http://www.yabbforum.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();


	function yabb2_000()
	{
	}


	/**
	* Parses and custom HTML for yabb2
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function yabb2_html($text)
	{
		$text = preg_replace('#\[quote author=(.*) link(.*)\](.*)\[/quote\]#siU', '[quote=$1]$3[/quote]', $text);

		return $text;
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
	function get_yabb2_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachment
			ORDER BY attachment_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[attachment_id]"] = $detail;
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
	function get_yabb2_forum_details(&$path)
	{
		$filename	= 'forum.control';

		$new_forums = array();
		$forums = file ($path .'/'. $filename);

		foreach($forums AS $forumid => $forum)
		{
			# Category | forum | blank | top thread title | moderator, moderator,
			$forum_line = explode('|', $forum);

			$cat 			= $forum_line[0];
			$forum 			= $forum_line[1];
			$first_thread	= $forum_line[3];
			$moderators		= $forum_line[4];

			$new_forums[$cat][] = array($forum => $moderators);
		}
		return $new_forums;
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
	function get_yabb2_pm_details(&$path, &$start_at, &$per_page)
	{
		$pmarray = array();
		$counter = 0;

		// Check that there is not a empty value
		if(empty($per_page)) { return $pmarray; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'msg')
			{
				continue;
			}

			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				// Going to have to rely on the consistancy of the file listing for a user number
				$temp_file_array = file($path . '/' . $file);

				// add the array of PM's to the return array
				$pmarray[$file] = $temp_file_array;

				unset($temp_file_array);
			}

			if($counter > ($per_page + $start_at))
			{
				echo "<b>" . $passing_through;
				return $pmarray;
			}

		}

		return $pmarray;
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
	function get_yabb2_post_details(&$path, &$start_at, &$per_page)
	{
		$postsarray = array();
		$counter = 0;


		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'txt')
			{
				continue;
			}

			$counter++;

			if($counter >= $start_at AND $counter < ($per_page + $start_at))
			{
				$file = substr($file, 0, -4);

				$temp_cat_array = @file($path . '/' . $file . '.txt');


				if(count($temp_cat_array) == 0)
				{
					continue;
				}

				foreach($temp_cat_array AS $count => $line)
				{
					$bits = explode('|',$line);

					$postsarray[$count][$counter]['title']		 	= $bits[0];
					$postsarray[$count][$counter]['threadid']		= $file;
					$postsarray[$count][$counter]['displayname']	= $bits[1];
					$postsarray[$count][$counter]['emailaddress'] 	= $bits[2];
					$postsarray[$count][$counter]['dateline'] 		= $bits[3];
					if($bits[4] == 'admin')
					{
						$postsarray[$count][$counter]['username']		= 'imported_admin';
					}
					else
					{
						$postsarray[$count][$counter]['username']		= $bits[4];
					}
					#5 lamp ? icon name
					#6 0 a status field ?
					$postsarray[$count][$counter]['ipaddress'] 		= $bits[7];
					$postsarray[$count][$counter]['pagetext'] 		= $bits[8];
					#9 blank
					#10 some time stamp ?
					#11 username again ?

					if(trim($bits[12]))
					{
						// There is an attachment
						$postsarray[$count][$counter]['attachment']	= $bits[12];
					}
					else
					{
						$postsarray[$count][$counter]['attachment']	= false;
					}
				}

			}
		}

		return $postsarray;
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
	function get_yabb2_thread_details(&$path, &$start_at)
	{
		$threadsarray = array();
		$counter = 0;


		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'txt')
			{
				continue;
			}


			$counter++;
			if($counter >= $start_at)
			{
				$file = substr($file, 0, -4);

				$temp_cat_array = @file($path . '/' . $file . '.txt');


				if(count($temp_cat_array) == 0)
				{
					continue;
				}

				$inner_counter = 1;
				foreach($temp_cat_array AS $line)
				{
					$bits = explode('|',$line);

					$threadsarray[$inner_counter]['threadid']	= $bits[0];
					$threadsarray[$inner_counter]['forum']		= $file;
					$threadsarray[$inner_counter]['title'] 		= $bits[1];
					$threadsarray[$inner_counter]['author'] 	= $bits[2];
					$threadsarray[$inner_counter]['email']	 	= $bits[3];
					$threadsarray[$inner_counter]['timestamp'] 	= $this->dodate($bits[4]);
					$threadsarray[$inner_counter]['number1']	= $bits[5];
					$threadsarray[$inner_counter]['name']	 	= $bits[6];
					$threadsarray[$inner_counter]['number2']	= $bits[7];
					$threadsarray[$inner_counter]['number3']	= $bits[8];

					$inner_counter++;
				}

				return $threadsarray;
			}
		}

		return $threadsarray;
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
	function get_yabb2_user_details(&$path, &$start_at, &$per_page)
	{
		$membersarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -4) != 'vars')
			{
				continue;
			}

			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				// Going to have to rely on the consistancy of the file listing for a user number
				$member_array = file($path . '/' . $file);

				// Trim the begining
				unset($member_array[0], $member_array[1]);

				// Make it one line to do replaces on
				$member_line = implode('', $member_array);

				// Swap out the newline and commas to array() syntax
				$member_line = str_replace(",", '=>', $member_line);
				$member_line = str_replace("\n", ',', $member_line);

				// Create the actual array
				eval ('$new_user = array(' . $member_line .');');

				$membersarray[$counter] = $new_user;

				// Add the username from the filename
				$membersarray[$counter]['username_file'] = substr($file, 0, strpos($file, '.'));


				unset($temp_file_array);
			}

			if($counter > ($per_page + $start_at))
			{
				echo "<b>" . $passing_through;
				return $membersarray;
			}

		}

		return $membersarray;
	}

	function dodate($string)
	{
		$date = explode('/',substr($string, 0, 8));

		if(strpos($string, ':'))
		{
			$time = explode(':',substr($string, -8));
		}

		$newtime = mktime($time[0], $time[1], $time[2], $date[0], $date[1], $date[2]);

		return $newtime;
	}

} // Class end
# Autogenerated on : March 20, 2006, 7:38 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
