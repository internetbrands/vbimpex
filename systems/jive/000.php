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
* jive
*
* @package 		ImpEx.jive
* @version		
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class jive_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '5.5';
	var $_tested_versions = array();
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'Jive';
	var $_homepage 	= 'http://www.jivesoftware.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('Category', 'Forum', 'Group', 'Message', 'Attachment', 'Thread', 'User', 'PMessage');

	function jive_000()
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
	function jive_html($text)
	{
		return $text;
	}

	function get_jive_usergroup_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }
 

		
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Group");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}
		
			$sql = "SELECT 	groupID,
							name,
							description
					FROM {$tableprefix}Group WHERE groupID
						IN(SELECT TOP {$per_page} groupID
							FROM (SELECT TOP {$internal} groupID FROM {$tableprefix}Group ORDER BY groupID)
						A ORDER BY groupID DESC)
					ORDER BY groupID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[groupID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Group", 'groupID', 0, $start_at, $per_page);
		}
		else
		{
			return false;
		}
		
		return $return_array;
	}
	
	function get_jive_user_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}User");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	userID,
							username,
							passwordHash,
							name,
							email,
							creationDate
					FROM {$tableprefix}User WHERE userID
						IN(SELECT TOP {$per_page} userID
							FROM (SELECT TOP {$internal} userID FROM {$tableprefix}User ORDER BY userID)
						A ORDER BY userID DESC)
					ORDER BY userID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[userID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}User", 'userID', 0, $start_at, $per_page);
		}		
		else
		{
			return false;
		}
				
		return $return_array;
	}
		
	function get_jive_cat_details(&$Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();
 
	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$sql = "SELECT categoryID, name, lft, CAST([description] as TEXT) as description FROM {$tableprefix}Category"; 

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[categoryID]"] = $details;
			}

			$return_array['count'] = count($return_array['data']);			
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Forum", 'forumID', 0, 0, $per_page);
		}		
		else
		{
			return false;
		}
		return $return_array;
	}		
 
	function get_jive_forum_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Forum");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	forumID,
							name,
							CAST([description] as TEXT) as description,
							categoryID
					FROM {$tableprefix}Forum WHERE forumID
						IN(SELECT TOP {$per_page} forumID
							FROM (SELECT TOP {$internal} forumID FROM {$tableprefix}Forum ORDER BY forumID)
						A ORDER BY forumID DESC)
					ORDER BY forumID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[forumID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);	
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Forum", 'forumID', 0, $start_at, $per_page);
		}		
		else
		{
			return false;
		}
		
		return $return_array;
	}			
	 
	function get_jive_thread_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Thread");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	threadID,
							forumID,
							rootMessageID,
							creationDate
					FROM {$tableprefix}Thread WHERE threadID
						IN(SELECT TOP {$per_page} threadID
							FROM (SELECT TOP {$internal} threadID FROM {$tableprefix}Thread ORDER BY threadID)
						A ORDER BY threadID DESC)
					ORDER BY threadID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$details['subject'] =  $Db_object->query_first("SELECT subject from {$tableprefix}Message where messageID=" . $details['rootMessageID']);
				$return_array['data']["$details[threadID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);			
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Thread", 'threadID', 0, $start_at, $per_page);
			
			foreach ($return_array['data'] AS $ID => $thread_details)
			{
				$data = $Db_object->query_first("SELECT subject from {$tableprefix}Message where messageID=" . $thread_details['rootMessageID']);
				$return_array['data'][$ID]['subject'] = $data['subject'];  
			}

		}		
		else
		{
			return false;
		}
		return $return_array;
	}		
		
	function get_jive_post_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Message");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	messageID,
							threadID,
							forumID,
							userID,
							subject,
							CAST([body] as TEXT) as body,
							creationDate,
							parentMessageID
					FROM {$tableprefix}Message WHERE messageID
						IN(SELECT TOP {$per_page} messageID
							FROM (SELECT TOP {$internal} messageID FROM {$tableprefix}Message ORDER BY messageID)
						A ORDER BY messageID DESC)
					ORDER BY messageID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[messageID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);			
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Message", 'messageID', 0, $start_at, $per_page);
		}		
		else
		{
			return false;
		}
		return $return_array;
	}
		
	function get_jive_PM_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}PMessage");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	pMessageID,
							ownerID,
							senderID,
							recipientID,
							subject,
							CAST([body] as TEXT) as body,
							pMessageDate
					FROM {$tableprefix}PMessage WHERE pMessageID
						IN(SELECT TOP {$per_page} pMessageID
							FROM (SELECT TOP {$internal} pMessageID FROM {$tableprefix}PMessage ORDER BY pMessageID)
						A ORDER BY pMessageID DESC)
					ORDER BY pMessageID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[pMessageID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);			
		}
		else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}PMessage", 'pMessageID', 0, $start_at, $per_page);
		}		
		else
		{
			return false;
		}
		return $return_array;
	}	
		
	function get_jive_attachment_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

	 	if ($databasetype == 'mssql' OR $databasetype == 'odbc')
		{
			$count = $Db_object->query_first("SELECT count(*) FROM {$tableprefix}Attachment");

			$internal 	= $start_at + $per_page;

			if($internal > intval($count[0]))
			{
				$per_page = abs($start_at - intval($count[0]));
				$internal = intval($count[0]);
			}

			$sql = "SELECT 	attachmentID,
							fileName,
							fileSize,
							creationDate
					FROM {$tableprefix}Attachment WHERE attachmentID
						IN(SELECT TOP {$per_page} THE_ID
							FROM (SELECT TOP {$internal} attachmentID FROM {$tableprefix}Attachment ORDER BY attachmentID)
						A ORDER BY attachmentID DESC)
					ORDER BY attachmentID";

			$details_list = $Db_object->query($sql);

			while ($details = $Db_object->fetch_array($details_list))
			{
				$return_array['data']["$details[attachmentID]"] = $details;
			}
			
			$return_array['lastid'] = ($start_at + $per_page);
			$return_array['count'] = count($return_array['data']);			
		}
			else if ($databasetype == 'mysql')
		{
			$return_array = $this->get_source_data($Db_object, $databasetype, "{$tableprefix}Attachment", 'attachmentID', 0, $start_at, $per_page);
		}		
		else
		{
			return false;
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
