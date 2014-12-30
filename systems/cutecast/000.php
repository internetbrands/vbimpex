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
* cutecast API module
*
* @package			ImpEx.cutecast
* @version			$Revision: $
* @author			Scott MacVicar <scott.macvicar@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class cutecast_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.x';


	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'CuteCast';
	var $_homepage 	= 'http://www.artscore.net/';
	var $_tier = '2';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();


	function cutecast_000()
	{
	}

	/**
	* Simple path checker
	*
	* @param	object	displayobject	The displayobject
	* @param	object	sessionobject	The current session object
	* @param	string	mixed			The full path
	*
	* @return	boolean
	*/
	function check_path($displayobject,$sessionobject,$path)
	{
		if (is_dir($path))
		{
			$displayobject->display_now("\n<br /><b>path</b> - $path <font color=\"green\"><i>OK</i></font>");
			$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
			return true;
		}
		else
		{
			$sessionobject->add_session_var($class_num . '_objects_failed',intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1 );
			$displayobject->display_now("\n<br /><b>$path</b> - <font color=\"red\"><i>NOT OK</i></font>");
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
	* Returns the user_id => username array
	*
	* @param	string	mixed			Path to the memebers directory
	*
	* @return	array
	*/
	function get_members_list(&$path, $start_at, $per_page)
	{
		$membersarray = array();
		$counter = 0;

		if (!$handle = opendir($path))
		{
			return false;
		}

		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..' OR substr($file, strrpos($file, '.user')) != '.user')
			{
				continue;
			}

			$counter++;
			if(($counter >= $start_at AND $counter <= ($per_page + $start_at)) OR $start_at == false)
			{
				$membersarray[sprintf("%u", crc32(substr($file, 0, strpos($file, '.'))))] = $file;
			}

			if($start_at !== false AND $counter > ($per_page + $start_at))
			{
				echo "<b>" . $passing_through;
				return $membersarray;
			}
		}

		return $membersarray;
	}
	/**
	* Returns the importuserid of a username
	*
	* @param	string	username			The username
	*
	* @return	int
	*/
	function get_import_userid($username)
	{
		$username = trim(strtolower($username));
		$username = preg_replace('#(\W+)#', '', $username);
		return sprintf("%u", crc32($username));
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
	function get_cutecast_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


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
	/**
	* Returns string with bbcode converted to vB3
	*
	* @param	string	mixed			The string with cutecast bbcode
	*
	* @return	string
	*/
	function cutecast_bbcode_to_vb_bbcode($code)
	{
		$code = preg_replace("#\[br\]#i", "\n", $code);
		$code = preg_replace("#\[p\]#i", "\n\n", $code);
		$code = preg_replace("#\[TIME\](\d+)\[/TIME\]#ie", "date('m-d-y \a\\t h:i A', \\1)", $code);
		$code = preg_replace("#\[size=small\]#i", "[size=1]", $code);
		return $code;
	}

	function get_cutecast_threads_ids($Db_object, $databasetype, $tableprefix, $importforumid)
	{

		if ($databasetype == 'mysql')
		{

			$sql = "SELECT threadid, importthreadid, importforumid FROM " .	$tableprefix . "thread WHERE importforumid = $importforumid";

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

} // Class end
/*======================================================================*/
?>
