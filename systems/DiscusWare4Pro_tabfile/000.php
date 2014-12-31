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
* DiscusWare4Pro_tabfile API module
*
* @package			ImpEx.DiscusWare4Pro_tabfile
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class DiscusWare4Pro_tabfile_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '4.x';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'DiscusWare 4.x Pro tab file data';
	var $_homepage 	= 'http://support.discusware.com/manuals/admdoc40/bkupmgr03.html';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('none');

	var $replacer = array(
				"+" 	=> " ",
				"%2e"	=> "",
				"%3f"	=> "",
				"%2f"	=> "",
				"%2d"	=> "",
				"6%2e1"	=> "",
				"6%2e5"	=> "",
				"%26%2339%3" => "",
				"25%2c"	=> "",
				"17%2c"	=> "",
				"22%2c"	=> "",
				"21%2c"	=> "",
				"11%2c"	=> "",
				"27%2c"	=> "",
				"%26"	=> "",
				"%2340"	=> "",
				"%3b1141442"	=> "",
				"%2341"	=> "",
				"%3b"	=> "",
				"%21"	=> "",
				"%5b"	=> "",
				"%3a"	=> "",
				"%2334"	=> "",
				"%2362"	=> "",
				"%2343"	=> "",
				"%2342"	=> "",
				"%2c"	=> "",
				"%40"	=> "",
				"%5d"	=> ""
			);


	/**
	* Disable Dupe Checking
	*
	* @var    boolean/array
	*/

	var $_dupe_checking = false; 

	function DiscusWare4Pro_tabfile_000()
	{
	}


	/**
	* Parses and custom HTML for DiscusWare4Pro_tabfile
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function DiscusWare4Pro_tabfile_html($text)
	{
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('<LI>', '[*]', $text);


		return $text;
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
	function get_DiscusWare4Pro_tabfile_forum_details($tab_file, $adminpath)
	{
		if (!is_file($tab_file))
		{
			return false;
		}

		$return_array = array();
		$file_pointer = fopen($tab_file, "r");


		if ($file_pointer)
		{
			while (!feof($file_pointer))
			{
				$line = fgets($file_pointer, 4096);
				$linelenght = strlen($line);

				$bits = explode("\t", $line);

				if(preg_match("#^[0-9]+#", $bits[1]) AND !strstr($bits[1], 'a'))
				{
					if (is_file("{$adminpath}/msg_index/" . $bits[1] . "-tree.txt"))
					{

						$first_line = fopen("{$adminpath}/msg_index/" . $bits[1] . "-tree.txt", 'r');
						$forum_line = explode("\t", fgets($first_line, 4096));
						fclose($first_line);

						$return_array[$bits[1]]['forumname'] = str_replace(array_keys($this->replacer), $this->replacer, $forum_line[4]);
					}
					else
					{
						#die("Couldn't get tree file");
					}
				}
				unset($bits);
			}
	   }
	   fclose($file_pointer);

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
	function get_DiscusWare4Pro_tabfile_thread_details($tab_file, $num_threads, $adminpath, $threads_file)
	{
		$file_path = "{$adminpath}/msg_index/{$threads_file}-tree.txt";

		$return_array 	= array();
		$threads 		= array();
		$forums 		= array();

		if (!is_file($file_path))
		{
			return false;
		}

		$whole_file = file($file_path);

		foreach ($whole_file as $line)
		{
			$bits = explode("\t", $line);

			if (isset($threads[$bits[3]]))
			{
				$forums[$bits[3]] = $threads[$bits[3]];
				unset($threads[$bits[3]]);
			}

			$threads[$bits[2]] = $this->assosiate_details($line);
		}

		$return_array['forums'] 	= $forums;
		$return_array['threads'] 	= $threads;

		return $return_array;
	}

	function assosiate_details($line)
	{
		$bits = explode("\t", $line);

		$details = array (
			'level' 				=> $bits[0],
			'categoryid' 			=> $bits[1],
			'id' 					=> $bits[2],
			'parentid' 				=> $bits[3],
			'title' 				=> str_replace(array_keys($this->replacer), $this->replacer, $bits[4]),
			'lastpost_timestamp' 	=> $bits[9],
			'username' 				=> str_replace(array_keys($this->replacer), $this->replacer, $bits[13])
		);

		return $details;
	}

	function get_DiscusWare4Pro_tabfile_post_details($tab_file, $num_threads, $pointer_position)
	{
		if (!is_file($tab_file))
		{
			return false;
		}

		$return_array = array();
		$file_pointer = fopen($tab_file, "r");

		// Are we seeking to a new thread ?
		if ($pointer_position)
		{
			if (fseek($file_pointer, $pointer_position) != 0)
			{
				return false;
			}
		}

		if ($file_pointer)
		{
			while (!feof($file_pointer))
			{
				$line = fgets($file_pointer);
				$linelenght = strlen($line);

				if($linelenght == 2)
				{
					$thread++;
				}
				else
				{
					$post++;
					$bits = explode("\t", $line);

					if(count($bits) > 7 OR trim($bits[0]) == '')
					{
						// Something is wrong, tabs in the post text etc.
						continue;
					}

					$return_array[$bits[0]]['postid']		= $bits[0];
					$return_array[$bits[0]]['forum']		= $bits[1];
					$return_array[$bits[0]]['thread']		= $bits[2];
					$return_array[$bits[0]]['timestamp'] 	= $bits[3];
					$return_array[$bits[0]]['username'] 	= $bits[4];
					$return_array[$bits[0]]['posttext'] 	= $bits[6];
				}

				if ($num_threads == $thread)
				{
					$return_array['position'] = ftell($file_pointer);
					fclose($file_pointer);
					return $return_array;
				}
			}

			// Run off the end of the file ?
			$return_array['position'] = 'finished';
			fclose($file_pointer);
			return $return_array;
		}
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
	function get_DiscusWare4Pro_tabfile_user_details(&$path, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$counter = 0;

		$admin_profiles = file($path . '/data/md-accts.txt');
		$user_profiles = file($path . '/data/us-accts.txt');

		$full_profiles = array_merge($admin_profiles, $user_profiles);

		$admin_file_array = file($path . '/passwd.txt');
		$user_file_array = file($path . '/users.txt');

		$full_array = array_merge($admin_file_array, $user_file_array);

		#foreach($full_array as $line)
		foreach($user_file_array as $line)
		{
			if($counter >= $start_at AND $counter < ($per_page + $start_at))
			{
				$profile_bits = explode(':',$full_profiles[$counter]);

				$profile_line = explode(';=',$profile_bits[5]);

				$details = explode(':',$line); # Can be : or ,

				$profile_bits = explode(';0=', $profile_line[0]);

				$url = substr(str_replace('%2', '.', str_replace('%3a%2f%2f','://',$profile_bits[0])), 2) ;

				if ($profile_bits[2] OR $profile_bits[3] OR $profile_bits[4])
				{
					if (trim($profile_bits[2]))
					{
						$location = $profile_bits[2] . ', ';
					}
					if (trim($profile_bits[3]))
					{
						$location .= $profile_bits[3] . ', ';
					}
					if(trim($profile_bits[4]))
					{
						$location .= $profile_bits[4] . ', ';
					}

					$location = substr($location, 0, -2);

					$location = str_replace('+', ' ', $location);
					$location = str_replace('%26%2334%3b', '', $location);
					$location = str_replace('%2c', '', $location);
					$location = str_replace('%2d', '', $location);
				}

				$return_array[$counter] = array (
						'username'		=>	$details[0],
						'email'			=>	$details[2],
						'displayname'	=>	$details[3],
						'joindate'		=>	substr($details[6],2),
						'usergroup'		=>	$details[7],
						'location'		=>	$location,
						'occupation'	=>	str_replace(';1=', ' ', str_replace('+',' ',$profile_bits[1])),
						'url'			=>	$url,
						'signature'		=>	str_replace('+',' ',$profile_line[5]),

					);
					unset($details, $location, $url);
			}
			$counter++;
		}

		return $return_array;
	}

	function get_DiscusWare4Pro_next_forum(&$Db_object, &$databasetype, &$tableprefix, $threads_file)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			if ($threads_file == 0)
			{
				$sql = "SELECT importforumid FROM " . $tableprefix . "forum WHERE importforumid !=0 AND importcategoryid !=0 LIMIT 0, 1";
				$forumid = $Db_object->query_first($sql);

				return $forumid['importforumid'];
			}
			else
			{
				$sql = "SELECT importforumid FROM " . $tableprefix . "forum WHERE importforumid !=0 AND importcategoryid !=0";

				$forum_list = $Db_object->query($sql);

				while ($forum = $Db_object->fetch_array($forum_list))
				{
					if ($forum['importforumid'] == $threads_file)
					{
						$forum = $Db_object->fetch_array($forum_list);

						if ($forum['importforumid'])
						{
							return $forum['importforumid'];
						}
						else
						{
							return 0;
						}
					}
				}
			}

			return false;
		}
	}
} // Class end
# Autogenerated on : October 23, 2005, 4:32 pm
# By ImpEx-generator 2.1.
/*======================================================================*/
?>
