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
* sitenetbbs API module
*
* @package			ImpEx.sitenetbbs
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class sitenetbbs_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.0.3';
	var $_tier = '3';
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'SiteNet BBS';
	var $_homepage 	= 'http://www.focalmedia.net/sitenetbbs.html';

	function sitenetbbs_000()
	{
	}


	/**
	* Parses and custom HTML for sitenetbbs
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function sitenetbbs_html($text)
	{
		$text = str_replace('[StartQuote]', '[QUOTE]', $text);
		$text = str_replace('[EndQuote]', '[/QUOTE]', $text);
		$text = str_replace('<HR>', '', $text);

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
	function get_members_list(&$path, &$start_at, &$per_page)
	{
		$path = $path . '/users';
		$membersarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
			echo "<H1>'" . $path . "'</H1>";
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR strlen($file) == 4)
			{
				continue;
			}

			$counter++;
			if($counter >= $start_at AND $counter <= ($per_page + $start_at))
			{
				$membersarray[substr($file, 0, strpos($file, '.'))] = $file;
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
	function get_sitenetbbs_forum_details(&$path)
	{
		$cats_array = $this->get_sitenetbbs_cats($path);

		$return_array = array();

		foreach ($cats_array AS $id => $name)
		{
			$new_path = "$path/$id";
			$return_array[$id] = $this->get_sitenetbbs_cats($new_path, false);
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
	function get_sitenetbbs_post_details(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page, $path)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($path)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT notes FROM " .
			$tableprefix."thread
			ORDER BY threadid
			LIMIT " . $start_at . "," .	$per_page;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{

				if (is_file($path . '/' . $detail['notes'] . '.dat'))
				{
					$thread_file = file($path . '/' . $detail['notes'] . '.dat');

					$path_bits = explode('/', $detail['notes']);

					foreach($thread_file AS $line)
					{
						$counter++;
						$line_bits = explode('	', $line);

						$return_array[$counter]['importthreadid']  	= $path_bits[2];
						$return_array[$counter]['ipaddress']  		= $line_bits[8];
						$return_array[$counter]['pagetext']  		= $line_bits[7];
						$return_array[$counter]['dateline']  		= $line_bits[6];
						$return_array[$counter]['title']  			= $line_bits[0];
						$return_array[$counter]['username']			= $line_bits[1];
					}
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
	function get_sitenetbbs_thread_details($path, &$start_at, &$per_page)
	{
		$returnarray = array();

		if (!is_file($path . "/topics.dat"))
		{
			return false;
		}

		$all_threads = file($path . "/topics.dat");

		$selection = array_slice($all_threads, $start_at, $per_page);

		foreach($selection AS $line)
		{
			$bits = explode('	', $line);
			$returnarray[$bits[0]] = $bits;
		}

		return $returnarray;
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
	function get_sitenetbbs_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."user
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

	function get_sitenetbbs_cats(&$path, $cat = true)
	{
		$type = ($cat ? 'boardtable.dat' : 'forumtable.dat');

		if (!is_file($path . "/$type"))
		{
			return false;
		}

		$cats_file = file($path . "/$type");

		foreach ($cats_file AS $line)
		{
			$bits = explode('	', $line);
			$return_array[$bits[0]] = $bits[1];
		}

		return $return_array;
	}

	function get_next_forum(&$Db_object, &$databasetype, &$tableprefix, $current_cat, $current_forum)
	{
		$stack = array();
		$sql = "SELECT importcategoryid, forumid FROM {$tableprefix}forum WHERE importforumid = 0 ORDER BY forumid";

		$cats_list = $Db_object->query($sql);

		while ($cat = $Db_object->fetch_array($cats_list))
		{
			$sql = "SELECT importforumid FROM {$tableprefix}forum WHERE parentid=" . $cat['forumid'];

			$forums_list = $Db_object->query($sql);

			while ($forum = $Db_object->fetch_array($forums_list))
			{
				if ($cat['importcategoryid'] !=0 AND $forum['importforumid'] !=0)
				{
					array_push($stack, $cat['importcategoryid'] . "::" . $forum['importforumid']);
				}
			}
		}

		if ($current_cat==0 AND $current_forum ==0)
		{
			$bits = explode('::', $stack[0]);

			$return_array = array (
				'path'			=> $bits[0] . '/' . $bits[1],
				'current_cat'	=> $bits[0],
				'current_forum'	=> $bits[1]
			);

			return $return_array;
		}

		foreach($stack AS $line)
		{
			$poss++;
			if ($line == "$current_cat::$current_forum")
			{
				if(strlen($line = $stack[$poss++]) > 1)
				{
					$bits = explode('::', $line);

					$return_array = array (
						'path'			=> $bits[0] . '/' . $bits[1],
						'current_cat'	=> $bits[0],
						'current_forum'	=> $bits[1]
					);

					return $return_array;
				}
			}
		}
		return false;
	}

} // Class end
# Autogenerated on : June 13, 2005, 1:57 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
