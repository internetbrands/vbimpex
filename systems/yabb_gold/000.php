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
* yabb_gold API module
*
* @package			ImpEx.yabb_gold
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class yabb_gold_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.3.1';
	var $_tier = '2';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'YaBB Gold';
	var $_homepage 	= 'http://www.yabbforum.com/';


	function yabb_gold_000()
	{
	}

	function yabb_2_html($text)
	{
		$text = preg_replace('#\[quote author=(.*) link(.*)\](.*)\[/quote\]#siU', '[quote=$1]$3[/quote]', $text);
		
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
	function get_yabb_gold_members_list(&$path, &$start_at, &$per_page)
	{
		$membersarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'dat')
			{
				continue;
			}

			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				// Going to have to rely on the consistancy of the file listing for a user number
				$temp_file_array = file($path . '/' . $file);
				$membersarray[$counter] = $temp_file_array[1];
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
	function get_yabb_gold_post_details(&$path, &$start_at, &$per_page)
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
#echo "<h1>'" . $counter . "'</h1>";
#echo "<h1>Start at '" . $start_at . "'</h1>";
#echo "<h1>per_page '" . $per_page . "'</h1>";
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
					$postsarray[$count][$counter]['dateline'] 		= $this->dodate($bits[3]);
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
					#10 blank
				}

			}
		}

		return $postsarray;
	}

	/**
	* Returns the line_id => usergroup
	*
	* @param	string	mixed			The path to the Variables dir
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_yabb_gold_usergroup_details(&$path)
	{
		// Check that there is not a empty value
		if(empty($path)) { return $usergrouparray; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		return file($path . '/membergroups.txt');
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
	function get_yabb_gold_user_details(&$path, &$start_at, &$per_page)
	{
		$membersarray = array();
		$counter = 0;

		// Check that there is not a empty value
		if(empty($per_page)) { return $membersarray; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'dat')
			{
				continue;
			}

			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				// Going to have to rely on the consistancy of the file listing for a user number
				$temp_file_array = file($path . '/' . $file);

				// Get the bits
				$membersarray[$counter]['username'] 	= substr($file, 0, -4);

				$membersarray[$counter]['password'] 	= $temp_file_array[0];
				$membersarray[$counter]['displayname'] 	= $temp_file_array[1];
				$membersarray[$counter]['emailaddress'] = $temp_file_array[2];
				$membersarray[$counter]['homepagetitle']= $temp_file_array[4];
				$membersarray[$counter]['homepage'] 	= $temp_file_array[4];
				$membersarray[$counter]['signature']	= $temp_file_array[5];
				$membersarray[$counter]['posts'] 		= $temp_file_array[6];
				$membersarray[$counter]['rank'] 		= trim($temp_file_array[7]);
				$membersarray[$counter]['icq'] 			= $temp_file_array[8];
				$membersarray[$counter]['occupation'] 	= $temp_file_array[9];
				$membersarray[$counter]['hobby'] 		= $temp_file_array[10];
				$membersarray[$counter]['gender']		= $temp_file_array[11];
				$membersarray[$counter]['usertext'] 	= $temp_file_array[12];
				$membersarray[$counter]['avatar'] 		= $temp_file_array[13];
				$membersarray[$counter]['regdate'] 		= $temp_file_array[14]; 
				$membersarray[$counter]['location'] 	= $temp_file_array[15];
				$membersarray[$counter]['birthday'] 	= $temp_file_array[16];
				$membersarray[$counter]['timeformat']	= $temp_file_array[17];
				$membersarray[$counter]['timeoffset'] 	= $temp_file_array[18];
				$membersarray[$counter]['hideemail'] 	= $temp_file_array[19];

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


	function get_yabb_gold_cat_details($path)
	{
		$catsarray = array();

		if (!$handle = opendir($path))
		{
			return false;
		}


		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'cat')
			{
				continue;
			}

			$counter++;

			// Going to have to rely on the consistancy of the file listing for a user number
			$temp_file_array = file($path . '/' . $file);

			$catsarray[$counter]['title'] 			= ucfirst(substr($file, 0, -4));
			$catsarray[$counter]['description'] 	= $temp_file_array[0];

			unset($temp_file_array);
		}

		return $catsarray;
	}

	function get_yabb_gold_forum_details(&$path, &$start_at, &$per_page)
	{
		$forumsarray = array();
		$counter = 0;

		// Check that there is not a empty value
		if(empty($per_page)) { return $forumsarray; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'ctb')
			{
				continue;
			}


			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				// Going to have to rely on the consistancy of the file listing for a user number
				$file = substr($file, 0, -4);

				$temp_cat_array = @file($path . '/' . $file . '.ctb');
				$temp_dat_array = @file($path . '/' . $file . '.dat');
				$temp_ttl_array = @file($path . '/' . $file . '.ttl');

				preg_match('#<font.*>(.*)</font>#iUe', $temp_dat_array[1], $matches);

				$forumsarray[$counter]['category'] 		= trim($temp_cat_array[0]);
				$forumsarray[$counter]['title'] 		= $file;
				$forumsarray[$counter]['description'] 	= $temp_dat_array[0] . ' :: ' . $matches[0];
				$forumsarray[$counter]['admin']		 	= trim($temp_dat_array[2]);


				unset($temp_cat_array);
				unset($temp_ttl_array);
				unset($temp_dat_array);
				unset($file);
			}

			if($counter > ($per_page + $start_at))
			{
				echo "<b>" . $passing_through;
				return $forumsarray;
			}

		}

		return $forumsarray;
	}

	function get_yabb_gold_thread_details(&$path, &$start_at)
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

	function get_yabb_gold_category_ids($path)
	{
		$catsarray = array();

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, -3) != 'cat')
			{
				continue;
			}

			$counter++;

			// Going to have to rely on the consistancy of the file listing for a user number
			$temp_file_array = file($path . '/' . $file);

			$title = substr($file, 0, -4);

			$catsarray[$title]= $counter;


			unset($temp_file_array);

		}

		return $catsarray;
	}

	
	function get_yabb_gold_pm_details(&$path, &$start_at, &$per_page)
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

	
	function get_yabb_gold_attachment_details(&$path, &$start_at, &$per_page)
	{
		// Check that there is not a empty value
		if(empty($path)) { return $usergrouparray; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		return array_slice(file($path . '/Variables/attachments.txt'), $start_at, $per_page);
	} 
	
	function get_yabb_gold_attachment_extra_details(&$Db_object, &$databasetype, &$tableprefix, &$importthreadid, &$post_off_set)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			// Get the thread id
			$thread_id = $Db_object->query_first("SELECT threadid FROM " . $tableprefix . "thread WHERE importthreadid = " . $importthreadid);
			
			if(!$thread_id)
			{
				return false;
			}
			
			// Get the post id ofset from that thread.
			$sql = "
			SELECT postid FROM " . $tableprefix . "post 
			WHERE threadid = " . $thread_id['threadid'] . " 
			ORDER BY dateline ASC 
			LIMIT " . $post_off_set . " , 1";
			
			$post_id = $Db_object->query_first($sql);
			
			return $post_id['postid'];
		}
		else
		{
			return false;
		}
	}
	
	
} // Class end
# Autogenerated on : August 12, 2004, 1:41 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
