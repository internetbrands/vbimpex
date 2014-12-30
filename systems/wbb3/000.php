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
* wbb3
*
* @package 		ImpEx.wbb3
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class wbb3_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '3.0.3';
	var $_tested_versions = array('3.0.1', '3.0.3');
	var $_tier = '1';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'wbb3';
	var $_homepage 	= 'http://www.example.com/';
 
	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'acp_menu_item', 'acp_session', 'acp_template', 'acp_template_patch', 'attachment', 'avatar', 'bbcode', 'bbcode_attribute',
		'captcha', 'cronjobs', 'cronjobs_log', 'event_listener', 'feed_entry', 'feed_source', 'group', 'group_application',
		'group_leader', 'group_option', 'group_option_category', 'group_option_value', 'header_menu_item', 'help_item', 'language',
		'language_category', 'language_item', 'language_to_packages', 'option', 'option_category', 'package', 'package_dependency',
		'package_installation_file_log', 'package_installation_plugin', 'package_installation_queue', 'package_installation_sql_log', 'package_requirement',
		'package_update', 'package_update_fromversion', 'package_update_requirement', 'package_update_server',
		'package_update_version', 'page_location', 'pm', 'pm_folder', 'pm_hash', 'pm_to_user', 'poll', 'poll_option',
		'poll_option_vote', 'poll_vote', 'search', 'searchable_message_type', 'session', 'smiley', 'spider', 'style', 'style_variable',
		'style_variable_to_attribute', 'template', 'template_pack', 'template_patch', 'user', 'user_blacklist', 'user_option',
		'user_option_category', 'user_option_value', 'user_rank', 'user_to_groups', 'user_to_languages', 'user_whitelist', 'usercp_menu_item'
	);

	function wbb3_000()
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
	function wbb3_html($text)
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
	function get_wbb3_members_list($Db, $db_type, $t_prefix, $start_at, $per_page)
	{
		$return_array = array();
		
		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		
		if ($db_type == 'mysql')
		{
			$dataset = $Db->query("SELECT userID, username FROM {$t_prefix}user ORDER BY userID LIMIT {$start_at}, {$per_page}");

			while ($row = $Db->fetch_array($dataset))
			{
				$return_array["$row[userID]"] = $row['username'];
			}
		}
	
		return $return_array; 
	}
	
	function get_wbb3_group($Db, $db_type, $t_prefix, $user_id)
	{
		$return_array = array();
		
		// Check that there is not a empty value
		if(empty($user_id)) { return $return_array; }
		
		if ($db_type == 'mysql')
		{
			$dataset = $Db->query("SELECT groupID FROM {$t_prefix}user_to_groups WHERE userID={$user_id} ORDER by groupID");
			
			while ($row = $Db->fetch_array($dataset))
			{
				$return_array[] = $row['groupID'];
			}			
		}
	
		return $return_array; 
	}	
	
	function get_wbb3_categories_details($Db, $db_type, $t_prefix)
	{
		$return_array = array();
		
		if ($db_type == 'mysql')
		{
			$dataset = $Db->query("SELECT * FROM {$t_prefix}board WHERE  boardType=1");
			
			while ($row = $Db->fetch_array($dataset))
			{
				$return_array[] = $row;
			}			
		}
	
		return $return_array; 
	}
	
	
	
	function get_wbb3_forum_details($Db, $db_type, $t_prefix, $start_at, $per_page)
	{
		$return_array = array();
		
		if ($db_type == 'mysql')
		{
			$dataset = $Db->query("SELECT * FROM {$t_prefix}board WHERE boardType=0 AND boardID > {$start_at} ORDER BY boardID LIMIT {$per_page}");
			
			while ($row = $Db->fetch_array($dataset))
			{
				$return_array[] = $row;
			}			
		}
	
		return $return_array; 
	}
	
	function get_wbb3_pm_recipt($Db, $db_type, $t_prefix, $pm_id)
	{
		$return_array = array();
		
		if ($db_type == 'mysql')
		{
			$return_array = $Db->query_first("SELECT * FROM {$t_prefix}pm_to_user WHERE pmID={$pm_id}");
		}
	
		return $return_array; 
	}
	
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
