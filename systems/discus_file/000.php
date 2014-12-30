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
* discus_file API module
*
* @package			ImpEx.discus_file
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class discus_file_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '4.00.6';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'DiscusWare (file based)';
	var $_homepage 	= 'http://www.discusware.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('counters', 'locks', 'log', 'passwd', 'search', 'users');


	/**
	* Disable Dupe Checking
	*
	* @var    boolean/array
	*/

	var $_dupe_checking = false; 

	function discus_000()
	{
	}


	function get_discus_file_members_list(&$path, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there isn't a empty value
		if(empty($per_page)) { return $return_array; }

		$counter = 0;

		$admin_file_array = file($path . '/passwd.txt');
		$user_file_array = file($path . '/users.txt');

		$full_array = array_merge($admin_file_array, $user_file_array);

		foreach($full_array as $line)
		{
			if($counter >= $start_at AND $counter < ($per_page + $start_at))
			{
				$details = explode(':',$line);

				$return_array[$counter] = $details[0];
			}
			$counter++;
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
	function get_discus_file_user_details(&$path, $start_at, $per_page)
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

				$details = explode(':',$line);

				$return_array[$counter] = array (
						'username'		=>	$details[0],
						'email'			=>	$details[2],
						'displayname'	=>	$details[3],
						'joindate'		=>	substr($details[6],2),
						'usergroup'		=>	$details[7],
						'location'		=>	$profile_line[2] . ' , ' . $profile_line[3] . ' , ' . $profile_line[4],
						'occupation'	=>	str_replace('+',' ',$profile_line[1]),
						'url'			=>	str_replace('%2e','.',$profile_line[0]),
						'signature'		=>	str_replace('+',' ',$profile_line[5])
					);
					unset($details);
			}
			$counter++;
		}
		return $return_array;
	}

	function get_first_cat_number(&$DB_object, &$database_type, &$table_prefix)
	{
		if ($database_type == 'mysql')
		{
			$id = $DB_object->query_first("
			SELECT importcategoryid
			FROM {$table_prefix}forum
			WHERE importcategoryid <> 0
			ORDER BY
			forumid LIMIT 0,1");

			return $id['importcategoryid'];
		}
		return false;
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
	function get_discus_file_usergroup_details(&$path)
	{
		$return_array = array();

		if (is_file($path . '/groups.txt'))
		{
			$user_file_array = file($path . '/groups.txt');

			foreach($user_file_array as $line)
			{
				$bits = explode(':',$line);
				$return_array[] = $bits[0];
			}
		}
		return $return_array;
	}

	function get_discus_file_forum_details(&$path, &$num)
	{
		$return_array = array();

		if(!is_file($path . '/' . $num . '/' . $num  . '.html'))
		{
			return $return_array;
		}

		$cats_file = implode('', file($path . '/' . $num . '/' . $num  . '.html'));

		preg_match_all(' #<!--?\s*Top:\s*(\d+)\s*--?>.*<a[^>]+>(.*)</a>.*<!--?\s*Descr\s*--?>(.*)<!--?\s*/Descr\s*--?>.*<!--?\s*/Top\s*--?>#siU', $cats_file, $matches, PREG_SET_ORDER);

		$return_array = array();

		foreach ($matches AS $id => $details_array)
		{
			// 1 - digit, 5 -forum title, 7 - description if available

			$return_array[] = array(
										'displayorder' 	=> $id+1,
										'forumid'			=> $details_array[1],
										'title'			=> $details_array[2],
										'description'	=> $details_array[3]
									);

		}
		return $return_array;
	}


	function get_discus_file_categories_details(&$path)
	{
		$return_array = array();

		$cats_file = implode('', file($path . '/' . 'board-topics.html'));

		preg_match_all('#<!--?\s*Top:\s*(\d+)\s*--?>.*((<b><a[^>]+>)|(<a[^>]+><b>))(.*)(?(3)</a></b>|</b></a>)(<!--?\s*Descr\s*--?>(.*)<!--?\s*/Descr\s*--?>|.)*<!--?\s*/Top\s*--?>#siU', $cats_file, $matches, PREG_SET_ORDER);

		$return_array = array();

		foreach ($matches AS $id => $details_array)
		{
			// 1 - digit, 5 -forum title, 7 - description if available

			$return_array[] = array(
										'displayorder' 	=> $id+1,
										'catid'			=> $details_array[1],
										'title'			=> $details_array[5],
										'description'	=> $details_array[7]
									);

		}
		return $return_array;
	}

	function get_parent_id(&$file)
	{
		$return_array['parent'] = '';
		$return_array['subject'] = '';
		$parent = $false;
		$subject = $false;
		$time = false;

		foreach($file as $line)
		{
			if(substr($line, 0, 11) == '<!--Parent:')
			{
				preg_match('#<!--Parent: (\d+)-->#siU', $line, $matches);
				$return_array['parent'] = $matches[1];
				$parent = true;
			}

			if(substr($line, 0, 12) == '<!--Level 1:')
			{
				preg_match('#<!--Level 1: (\d+)/(.+)-->#siU', $line, $matches);
				$return_array['subject'] = $matches[2];
				$return_array['threadid'] = $matches[1];
				$subject = true;
			}

			#<!-Post: 3280-!><!-Time: 948125574-!>
			if(substr($line, 0, 9) == '<!-Post: ')
			{
				preg_match('#<!-Post: (\d+)-!><!-Time: (\d+)-!>#siU', $line, $matches);
				$return_array['time'] = $matches[2];
				$time = true;
			}

			if($parent AND $subject AND $time)
			{
				break;
			}
		}
		return $return_array;
	}

	function get_discus_file_threads_details(&$path, &$forum, &$thread_start_at, &$threads_per_page)
	{
		// Check that there isn't a empty value
		if(empty($threads_per_page)) { return $return_array; }

		$return_array 	= array();
		$threads_array 	= array();
		$vaild_counter  = 0;

		$cats_file = file($path . '/' . 'board-topics.html');

		$files = $this->scandir($path . '/' . $forum);

		if(!$files)
		{
			// Empty dir
			return $return_array;
		}

		foreach($files as $num => $filename)
		{
			if(substr($filename, -4) != '.gif'			// Not a gif
				AND substr($filename, -4) != '.jpg' 	// Not a jpg
				AND $filename{0} != '.'					// Not a dir listing
				AND	$filename != ($forum . '.html')		// Not the forum index page
				)
			{
				// File it
				$thread = file($path . '/' . $forum  . '/' . $filename);
				// Get the parent id
				$parent_id = $this->get_parent_id($thread);
				if($parent_id['parent'] == $forum)
				{
					if($vaild_counter >= $thread_start_at AND $vaild_counter < ($threads_per_page + $thread_start_at))
					{
						$threadid = substr($filename, 0, -4);

						$threads_array[$threadid] = array(
									'title'		=>		$parent_id['subject'],
									'dateline'	=>		$parent_id['time']
									);
					}
					$vaild_counter++;
				}
			}
		}
		return $threads_array;
	}

	function get_next_cat_id(&$DB_object, &$database_type, &$table_prefix, $current_forum)
	{
		if ($database_type == 'mysql')
		{
			$id = $DB_object->query("
				SELECT importcategoryid
				FROM {$table_prefix}forum
				WHERE importcategoryid <> 0
				ORDER BY forumid
			");

			while ($forumid = $DB_object->fetch_array($id))
			{
				if($forumid['importcategoryid'] == $current_forum)
				{
					// the next one
					$next_id = $DB_object->fetch_array($id);
					return $next_id['importcategoryid'];
				}
			}

			return $id['importcategoryid'];
		}
		return false;
	}


	function get_discus_file_post_details(&$path, &$forum, &$thread_start_at, &$threads_per_page)
	{

		// Check that there isn't a empty value
		if(empty($threads_per_page)) { return $return_array; }

		// $return_array[importthreadid][postid][postdetails-array]
		$return_array 	= array();
		$threads_array 	= array();
		$counter  = 0;

		$cats_file = file($path . '/' . 'board-topics.html');


		$files = $this->scandir($path . '/' . $forum, ($thread_start_at+$threads_per_page));

		if(!$files)
		{
			// Empty dir
			return 0;
		}

		foreach($files as $num => $filename)
		{
			if(substr($filename, -4) != '.gif'			// Not a gif
				AND substr($filename, -4) != '.jpg' 	// Not a jpg
				AND substr($filename, -4) != '.unk' 	// Not a unk
				AND $filename{0} != '.'					// Not a dir listing
				AND	$filename != ('index.html')		// Not the forum index page
				)
			{
				// It is in the valid select range
				if($counter >= $thread_start_at AND $counter < ($threads_per_page + $thread_start_at))
				{
					$thread = file($path . '/' . $forum  . '/' . $filename);

					$parent_id = $this->get_parent_id($thread);
					$pagetext = implode($thread);
/*
					#preg_match_all('#<!-Post: (\d+)-!><!-Time: (\d+)-!>.*<!-Email-!><a href="mailto:([^"]+)">.*<!-Name-!>(.*)<!-/Name-!>.*<!-Text-!>(.*)<!-/Text-!>#siU', $pagetext, $matches, PREG_SET_ORDER);
					#preg_match_all('#<!-Post: (\d+)-!><!-Time: (\d+)-!>.*<!-Email-!>(.*)<!-/Email-!>.*<!-Name-!>(.*)<!-/Name-!>.*<!-Text-!>(.*)<!-/Text-!>#siU', $pagetext, $matches, PREG_SET_ORDER);
					#preg_match_all('#<!--?Post: (\d+)--?><!--?Time: (\d+)--?>.*<!--?email--?>(.*)<!--?/email--?>.*<!--?field:uname--?>(.*)<!--?/field--?>.*<!--?field:ip_address--?>(.*)<!--?/field--?>.*<!--?Text--?>(.*)<!--?/Text--?>#siU', $pagetext, $matches, PREG_SET_ORDER);
*/

					preg_match_all('#<!--Post: (\d+)--><!--Time: (\d+)-->.*<!--email-->(.*)<!--/email-->.*<!--name-->(.*)<!--/name-->.*<!--text-->(.*)<!--/text-->.*<!--/Post: (\\1)-->#siU', $pagetext, $matches, PREG_SET_ORDER);

					$segmentid = substr($filename, 0, -5);

					$counter = 0;
					foreach($matches as $matched_post)
					{
						preg_match_all('#<a href="mailto:([^"]+)">#siU',$matched_post[3], $moo);

						if(count($moo[0]))
						{
							$matches[$counter][3] = $moo[1][0];
						}
						else
						{
							$matches[$counter][3] = 'none';
						}

						// Trim the username to get it all
						$matches[$counter][4] = trim($matches[$counter][4]);
						$counter++;
					}

/*
						1 = postid
						2 = timestamp
						3 = email or 'none'
						4 = username (possibly)
						5 = page text
*/

					// Forum -> Thread -> Segment
					$return_array[$forum]["$parent_id[threadid]"][$segmentid] = $matches;
					unset($post_array);
				}

				$counter++;
			}
		}
		return $return_array;
	}
} // Class end
# Autogenerated on : May 17, 2004, 1:49 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
