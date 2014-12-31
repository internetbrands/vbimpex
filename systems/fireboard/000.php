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
* fireboard
*
* @package 		ImpEx.fireboard
* @date 		$Date: $
*
*/

class fireboard_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.0.4';
	var $_tested_versions = array('1.0.4');
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'fireboard';
	var $_homepage 	= 'http://www.bestofjoomla.com/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'fb_announcement', 'fb_attachments', 'fb_categories', 'fb_favorites', 'fb_groups', 'fb_messages', 'fb_messages_text', 'fb_moderation',
		'fb_ranks', 'fb_sessions', 'fb_smileys', 'fb_subscriptions', 'fb_users', 'fb_whoisonline'
	);

	function fireboard_000()
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
	function fireboard_html($text)
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
	function get_fireboard_members_list($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT users.id, users.username 
			FROM {$tableprefix}users as users
			LEFT JOIN {$tableprefix}fb_users AS fb_users ON (users.id = fb_users.userid)
			WHERE users.id > {$start_at}
			ORDER by users.id 
			LIMIT {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row['username'];
			}
		
		}
		
		return $return_array;
	}
	
	function get_fireboard_users($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT users.*,
			 fb_users.*
			FROM {$tableprefix}users as users
			LEFT JOIN {$tableprefix}fb_users AS fb_users ON (users.id = fb_users.userid)
			WHERE users.id > {$start_at}
			ORDER by users.id 
			LIMIT {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[id]"] = $row;
				$return_array['lastid'] = $row['id'];
			}
		
		}
		$return_array['count'] = count($return_array['data']);
		return $return_array;
	}	
	
	function get_fireboard_cat_details($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}fb_categories WHERE parent=0");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row;
			}
		}

		return $return_array;
	}	
	
	function get_fireboard_forum_details($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}fb_categories WHERE parent>0 LIMIT {$start_at}, {$per_page}");			
			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[id]"] = $row;
			}
		}

		return $return_array;
	}	
	
	function get_fireboard_threads($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
		
		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}fb_messages WHERE id=thread AND id > {$start_at} LIMIT {$per_page}");			
			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[id]"] = $row;
				$return_array['lastid'] = $row['id'];
			}
		}

		$return_array['count'] = count($return_array['data']);  
		return $return_array;
	}	
	
	function get_fireboard_posts($Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "SELECT messages.*,
			 messages_text.message AS post_text
			FROM {$tableprefix}fb_messages as messages
			LEFT JOIN {$tableprefix}fb_messages_text AS messages_text ON (messages.id = messages_text.mesid)
			WHERE messages.id > {$start_at}
			ORDER by messages.id 
			LIMIT {$per_page}";
			
			$dataset = $Db_object->query($sql);

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[id]"] = $row;
				$return_array['lastid'] = $row['id'];
			}
		
		}
		$return_array['count'] = count($return_array['data']);
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
