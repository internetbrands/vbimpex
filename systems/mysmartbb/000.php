<?php
if (!defined('IDIR')) { die; }
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
* mysmartbb
*
* @package 		ImpEx.mysmartbb
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class mysmartbb_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.50';
	var $_tested_versions = array('1.50');

	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'mysmartbb';
	var $_homepage 	= 'http://www.mysmartbb.com/';
	var $_tier = '3';
	
	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'ads', 'announcement', 'attach', 'avater', 'contactus_extensions', 'contactus_messages','contactus_settings', 'emailmsgs', 'ex', 'group',
		'info', 'lpMod', 'member', 'online', 'pages', 'pm', 'pmfolder', 'pmlists', 'poll', 'reply', 'requests', 'section', 'sectionadmin', 'sectiongroup',
		'smiles', 'style', 'subject', 'supermemberlogs', 'today', 'toolbox', 'usertitle', 'vote'
	);

	function mysmartbb_000()
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
	function mysmartbb_html($text)
	{
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
	function get_mysmartbb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT id, username FROM {$tableprefix}member ORDER BY id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row['username'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_mysmart_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}section WHERE main_section=1 ORDER BY id");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}


	function get_mysmart_forum_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}section WHERE main_section !=1 ORDER BY id");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_thread_date(&$Db_object, &$databasetype, &$tableprefix, $thread_id)
	{
		$return_id = 0;

		if ($databasetype == 'mysql')
		{
			$data = $Db_object->query_first("SELECT dateline FROM {$tableprefix}thread WHERE importthreadid = {$thread_id}");

			return $data['dateline'];
		}
		else
		{
			return false;
		}
	}

	function get_thread_userid(&$Db_object, &$databasetype, &$tableprefix, $thread_id)
	{
		$return_id = 0;

		if ($databasetype == 'mysql')
		{
			$data = $Db_object->query_first("SELECT postuserid FROM {$tableprefix}thread WHERE importthreadid = {$thread_id}");

			return $data['postuserid'];
		}
		else
		{
			return false;
		}
	}

}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
