<?php 
if (!defined('IDIR')) { die; }
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
* webbbs
*
* @package 		ImpEx.webbbs
*
*/

class webbbs_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '5.30';
	var $_tested_versions = array('5.12');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'webbbs';
	var $_homepage 	= 'http://awsd.com/scripts/webbbs/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();

	function webbbs_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed	The string to parse
	* @param	boolean			Truncate smilies
	*
	* @return	array
	*/
	function webbbs_html($text)
	{
		$text = str_ireplace('LINKURL>', '', $text);
		return $text;
	}

	function get_cats($dir)
	{
		$forum_list = array();
		
		if (is_dir($dir)) 
		{
		    if ($dh = opendir($dir)) 
		   	{
		        while (($file = readdir($dh)) !== false) 
		        {
		        	if (substr($file, 0, 5) == "main" AND is_dir($dir . '/' . $file))
					{
		            	$forum_list[$file] = $dir . '/' . $file;
					}
		        }
		        closedir($dh);
		    }
		}
		
		asort($forum_list);		
		return $forum_list;
	}	
	
	function get_forums($dir)
	{
		$forum_list = array();
		
		if (is_dir($dir)) 
		{
		    if ($dh = opendir($dir)) 
		   	{
		        while (($file = readdir($dh)) !== false) 
		        {
		        	if (substr($file, 0, 3) == "bbs" AND is_dir($dir . '/' . $file))
					{
		            	$forum_list[$file] = $dir . '/' . $file;
					}
		        }
		        closedir($dh);
		    }
		}
		
		asort($forum_list);
		return $forum_list;
	}	
	
	function get_forum_posts($dir)
	{
		$forum_list = array();
		
		if (is_dir($dir)) 
		{
		    if ($dh = opendir($dir)) 
		   	{
		        while (($file = readdir($dh)) !== false) 
		        {
		        	if (is_file($dir . '/' . $file))
					{
		            	$forum_list[$file] = $this->get_post($dir . '/' . $file);
					}
		        }
		        closedir($dh);
		    }
		}
		
		ksort($forum_list);
		return $forum_list;
	}	
	
	function get_post($path)
	{
		if (!is_file($path))
		{
			return false;
		}
		
		$input_file = file($path);
	
		$work_array = array();
		$page = '';
		
		// Get the first details of the post
		foreach ($input_file as $id => $line) 
		{
			if ($id == 11)
			{
				break;
			}
			
			// Swap the line title for array key
			$before = substr($line, 0, strpos($line, '>'));
			$work_array[$before] = substr($line, strpos($line, '>')+1);
		}
		
		// Get the post text 
		for ($i=10; $i++; )
		{
			if ($input_file[$i])
			{
				$page .= $input_file[$i];
			}
			else
			{
				break;
			}
		}
		
		$work_array['PAGETEXT'] = trim($page);
		return $work_array;
	}	
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
