<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* Ubb_classic
*
*
* @package 		ImpEx.ubb_classic
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_000 extends ImpExModule
{
	/**
	* Versions supported
	*
	* @var    string
	*/
	var $_version 		= '6.3 - 6.7';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string for phpUnit header
	*
	* @var    array
	*/
	var $_modulestring 	= 'Infopop UBB classic';
	var $_homepage 	= 'http://www.ubbcentral.com/ubbclassic/';

	/**
	* Constructor
	*
	* @return	none
	*/
	function ubb_classic_000()
	{
	}


	/**
	* Simple file checker
	*
	* @param	object	displayobject	The displayobject
	* @param	object	sessionobject	The current session object
	* @param	string	mixed			The full path and filename
	*
	* @return	boolean
	*/
	function check_file($displayobject,$sessionobject,$file)
	{
		if (is_file($file))
		{
			$displayobject->display_now("\n<br /><b>file</b> - $file <font color=\"green\"><i>OK</i></font>");
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done',intval($sessionobject->get_session_var(substr(get_class($this) , -3) . '_objects_done')) + 1 );
			return true;
		}
		else
		{
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed',intval($sessionobject->get_session_var(substr(get_class($this) , -3) . '_objects_failed')) + 1 );
			$displayobject->display_now("\n<br /><b>$file</b> - <font color=\"red\"><i>NOT OK</i></font>");
			$sessionobject->add_error('fatal',
									 $this->_modulestring,
									 "$path is incorrect",
									 'Check the file structe of the ubb board');
			return false;
		}
	}


	/**
	* Simple file checker
	*
	* @param	string	mixed			Path to the memebers directory
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_members_list(&$path, &$start_at, &$per_page)
	{
		$membersarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, 0, 1) != '0')
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
	* Gets the ubb user permission mapping to vb permissions
	*
	* @param	string	mixed			Path to the memebers directory
	* @param	int		mixed			Banned group id
	*
	* @return	mixed
	*/
	function get_ubb_usergroup($adminstring)
	{
		$adminstring = str_replace("&","",trim($adminstring));

		if ($adminstring=="")
		{
			return '69';
		}

		if (stristr($adminstring,"COPPA"))
		{
			return '72';
		}

		if (stristr($adminstring,"Admin"))
		{
		   return '70';
		}
		else
		{
			return '71';
		}
	}


	/**
	* Parses ubb_classic html
	*
	* @param	string	mixed			The html to parse
	*
	* @return	string
	*/
	function local_html_2bb($htmlcode)
	{
		$htmlcode = str_replace('<blockquote><font size="1" face="Verdana, Helvetica, sans-serif">quote:<hr />','[QUOTE]',$htmlcode);
		$htmlcode = str_replace('<blockquote>quote:<hr />','[QUOTE]',$htmlcode);

		$htmlcode = preg_replace('#<font size=\"([0-9]+)\" face=\"([^\"]*)\">#siU','',$htmlcode);
		$htmlcode = preg_replace('#<font face=\"([^\"]*)\" size=\"([0-9]+)\">#siU','',$htmlcode);

		//$htmlcode = str_replace('<font size="2" face="Verdana, Helvetica, sans-serif">','',$htmlcode);
		//$htmlcode = str_replace('<font size="2" face="Verdana, Arial">','',$htmlcode);

		$htmlcode = str_replace('<ul type="square">','[list]',$htmlcode);

		$htmlcode = str_replace('<hr /></blockquote>','[/QUOTE]',$htmlcode);
		$htmlcode = str_replace('</span>','',$htmlcode);

		// Final catch clean up and Ubb specific
		$htmlcode = str_replace(';-)))',';)',$htmlcode);
		$htmlcode = str_replace(';-))',';)',$htmlcode);
		$htmlcode = str_replace(';-)',';)',$htmlcode);
		$htmlcode = str_replace(':-)',':)',$htmlcode);
		$htmlcode = str_replace(',-p',':p',$htmlcode);

		return $htmlcode;
	}

	function clean_name($text)
	{
		$text = str_replace('>', '&gt;', $text);
		$text = str_replace('<', '&lt;', $text);
		$text = str_replace('"', '&quot;', $text);
		$text = str_replace('&', '&amp;', $text);
		
		return $text;
	}

	/**
	* Returns custom ubb_classic smilies
	*
	* @param	string	mixed			The directory path to the smmilies
	*
	* @return	array
	*/
	function get_custom_smilie($dir)
	{
		$return_array 	= '';
		$path 		= substr($dir,strrpos($dir,'/')+1) . '/';

		if ($handle = opendir($dir))
		{
		   while (false !== ($original_file = readdir($handle)))
		   {
			   if($original_file != '.' AND $original_file != '..')
			   {
				   // Chop the .* off
				   $file = substr($original_file, 0, strpos($original_file,'.'));

				   if(strlen($file) > 9)
				   {
					   // Strip it from the end back in case it has a common preapended name
					   $file = ':' . substr($file,-8) . ':';
				   }

				   $return_array[$path . $original_file] = $file;
			   }
		   }

		   closedir($handle);
		   return $return_array;
		}
	}


	/**
	* Returns a vbuser list from the ubb_classic users
	*
	* @param	string	mixed			The directory path
	* @param	array	mixed			The vBuser array
	*
	* @return	array
	*/
	function makelist(&$filepath, &$vbuserid)
	{
		$file = file($filepath);

		while (list($line,$contents)=each($file))
		{
			if (preg_match("#!\"?([0-9]+)!\"? \=\>#",$contents,$regs)  )
			{
				$list .= $vbuserid[intval($regs[1])]." ";
			}
		}
		return trim($list);
	}


	/**
	* Returns an array of the ubb_classic pm users
	*
	* @param	string	mixed			The directory path
	*
	* @return	array
	*/
	function get_pm_users(&$ubbmemberspath)
	{
		$handle=opendir("$ubbmemberspath/pm_users");
		while ($file = readdir($handle))
		{
			if ($file != "." && $file != "..")
			{
				$pmusers[++$i] = $file;
			}
		}

		return $pmusers;

	}


	/**
	* Regex callback
	*
	* @param	string	mixed			Variable name
	* @param	string	mixed			Variable value
	*
	* @return	string
	*/
	function convert_ubb6_pm_to_pm($varname, $data)
	{
		return "'$varname' => '" . str_replace("'", "\\'", $data) . "',\n";
	}


	/**
	* Returns a correctly formatted unix time from a ubb time string
	*
	* @param	string	mixed			Date string
	* @param	string	mixed			Time string
	*
	* @return	string
	*/
	function ubbdate2unix($datestring,$timestring)
	{
		$datebits = explode("-",$datestring);
		 $timebits = preg_match("/([0-9]*):([0-9]*) ([A-Z]*)/",$timestring,$regs);
		 if ($regs[1]==12 and $regs[3]=="AM")
		 {
			$regs[1] = 0; 		 }
		 elseif ($regs[3]=="PM" and $regs[1]!=12)
		 {
			 $regs[1] += 12;
		 }
		 return mktime($regs[1],$regs[2],0,$datebits[0],$datebits[1],$datebits[2]);
	}


	/**
	* HTML clean up for ubb_classic PM's
	*
	* @param	string	mixed			The text to be parsed
	*
	* @return	string
	*/
	function clean_pm_text($text)
	{
		$text = preg_replace('#(<img(.*)>)#iUe', '', $text);

		return $text;
	}



	/**
	* Returns a list of the ubb_polls
	*
	* @param	string	mixed			Path to the polls directory
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ubb_classic_polls_details(&$path, &$poll_start_at, &$poll_per_page)
	{
		$pollsarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
		   return false;
		}

		while (false !== ($file = readdir($handle)))
		{
		   if ($file == '.' OR $file == '..')
		   {
			   continue;
		   }

		   $counter++;
		   if($counter >= $poll_start_at AND $counter <= ($poll_per_page + $poll_start_at))
		   {
				$pollsarray[substr($file, 0, strpos($file, '.'))] = $file;
		   }

		   if($counter > ($poll_per_page + $poll_start_at))
		   {
			   return $pollsarray;
		   }
		}

		return $pollsarray;
	}


	/**
	* Mad ultra ninja code from the high council of Regexia
	*
	* @param	string	mixed			Path to the polls directory
	* @param	string	mixed			Poll file name (inc. .cgi)
	*
	* @return	magic
	*/
	function get_ubb_poll_results_details(&$polls_path, &$poll_file_name)
	{
		// set of regexes to run
		static $regex_once = array(
			'#^sub\s+poll_load_([a-z0-9_]+)\s*{\s*\$poll->\{\\1\} = \{(.*)\};\s*\};\s*1;\s*$#siU' => "\$return = array(\\2);",
			'#((\r|\n|=>)\s+)q([^a-zA-Z0-9])(.*)\\3(?=( =>)|,)#sUe' => "\$this->escape_text_single_quotes('\\4', '\\1\'', '\'')",
			'#=> undef,#i' => '=> \'undef\','
		);
		static $regex_multi = array(
			'#=>\s*\{(.*(\n|\r)\s*)\}(?=,??)#siU' => "=> array(\\1)",
			'#=>\s*\[(.*(\n|\r)\s*)\](?=,??)#siU' => "=> array(\\1)",
			'#((array\(|,)\s*)\{(.*(\n|\r)\s*)\}(?=,??)#siU' => "\\1array(\\3)"
		);

		$data = implode('', file($polls_path . "/" . $poll_file_name));


		/*$data = str_replace("=> [", " = array(", $data);
		$data = str_replace("=> {", " = array(", $data);

		$data = str_replace("],", ")", $data);

		$data = str_replace("}", "),", $data);
		$data = str_replace("{", "(", $data);
		$data = str_replace(",,", ",", $data);


		unset($data[0]);
		unset($data[count($data)]);
		unset($data[count($data)-1]);


		$data[1] = '$new_array = array (';

		eval($data);

		print_r($data);die();

		$data = preg_replace("/q?([^a-z0-9]?)([a-z0-9_]+)\\1\s+=>\s+q?([^a-z0-9])([^\n]*)\\3(,\s+?)/siUe","\$this->convert_to_array('\\2', '\\4')", $data);
		$data = preg_replace("/%([a-z0-9_]+) = \(/siU", '$\1 = array(', $data);
		$data = preg_replace("#q!([0-9]+)! => undef#iU", "'\\1' => NULL", $data);*/

		// the bit that actually parses the Perl into PHP
		$data = preg_replace(array_keys($regex_once), $regex_once, $data);
		foreach ($regex_multi AS $search => $replace)
		{
			do
			{
				$data_old = $data;
				$data = preg_replace($search, $replace, $data);
			}
			while (preg_match($search, $data) AND $data != $data_old);
		}

		// make the PHP variables
		eval($data);


		return $return;
	}


	/**
	* Regex callback
	*
	* @param	string	mixed			Text
	* @param	string	mixed			Prepend
	* @param	string	mixed			Append
	*
	* @return	string
	*/
	function escape_text_single_quotes($text, $prepend, $append)
	{
		$text = str_replace('\\"', '"', $text);
		$prepend = str_replace('\\"', '"', $prepend);
		$append = str_replace('\\"', '"', $append);

		return $prepend . str_replace("'", "\\'", $text) . $append;
	}


	/**
	* Regex callback
	*
	* @param	string	mixed			Variable name
	* @param	string	mixed			Variable value
	*
	* @return	string
	*/
	function convert_to_array($varname, $data)
	{
		return "'$varname' => '" . str_replace("'", "\\'", $data) . "',\n";
	}

	function get_forum_thread_id($Db_object, $databasetype, $tableprefix, $import_thread_id, $import_forum_id)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT threadid FROM " .
			$tableprefix."thread
			WHERE
			importthreadid='". $import_thread_id . "'
			AND
			importforumid='". $import_forum_id . "'
			";

			$id = $Db_object->query_first($sql);

			return $id['threadid'];


		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ubb_threads_ids($Db_object, $databasetype, $tableprefix)
	{

		if ($databasetype == 'mysql')
		{

			$sql = "SELECT threadid, importthreadid, importforumid FROM " .	$tableprefix . "thread";

			$ids = $Db_object->query($sql);

			while ($id = $Db_object->fetch_array($ids))
			{
				$return_array[$id['importforumid']][$id['importthreadid']] = $id['threadid'];
			}

		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ubb_moderators($string)
	{
		if (preg_match('#[0-9]#', $string))
		{
			$mod_forums = explode(',', trim(substr($string, strpos($string, '&')+1)));
			return $mod_forums;
		}

		return false;
	}

	function get_moderators_list(&$path, &$start_at, &$per_page)
	{
		$mods_array = array();

		if (!$handle = opendir($path))
		{
			echo "<H1>'" . $path . "'</H1>";
			return false;
		}


		if (!$file = file($path . '/vars_mods.cgi'))
		{
			return $mods_array;
		}

		$inner = array_slice($file, $start_at, $per_page);

		foreach($inner as $line)
		{
			$mods_array[substr($line, 6, (strpos($line, '"')-18))] =  explode('||^||', substr($line, strpos($line, '"')+1, -3));
		}

		return $mods_array;
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
