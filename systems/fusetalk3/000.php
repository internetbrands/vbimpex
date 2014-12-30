<?php if (!defined('IDIR')) { die; }
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
* fusetalk API module
*
* @package			ImpEx.fusetalk
* @version			$Revision: 2213 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2009-03-06 13:01:20 -0800 (Fri, 06 Mar 2009) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
require_once (IDIR . '/systems/fusetalk/000.php');

class fusetalk3_000 extends fusetalk_000
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.0';
	var $_modulestring 	= 'FuseTalk v3';

	var $_valid_tables = array (
		'arcmessages', 'arcthreads', 'attachedfiles', 'authentication', 'authoricons', 'banning','basicchat_messages','basicchat_rooms',
		'basicchat_userroomlink','buddylist','categories','categories_mod','censorwords', 'communitydefaults','communitysettings','communitysettingsother',
		'countries','defaultsets','dictionary','dictionarywords','emailposting','emailpostingsubject','emoticons','favorites','foldericonthemes',
		'forumgroupings','forums','forumsettings','forumsettingsother','forumusers','groupings','groups','groupusers','guests','licenses','mail',
		'mailinglist','messages','moderatorlogs','modules','polls','pollsanswers','pollstracking','privatebanning','privatecategories','privatemessages',
		'privatethreads','ratingicons','recentitems','reportingactions','reportingdata','reportingtrans','reportinguserdata','securitydescriptors',
		'servers','stateprovince','subscription','surveyanswers','surveyquestions','surveyresponses','themes','threads','threadstatistics',
		'timezones','today','userrating','users','usersettings','usertitles'
	);

	function fusetalk3_000()
	{
	}

	/**
	 * Parses and custom HTML for fusetalk
	 *
	 * @param	string	mixed			The text to be parse
	 *
	 * @return	array
	 */
	function fusetalk3_html($text)
	{
		return fusetalk_html($text);
	}

	function get_fusetalk_authoricon_details(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."authoricons
			WHERE iiconid > $start_at
			ORDER BY iiconid
			LIMIT " .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iiconid]"] = $detail;
			}
		}
		else if ($databasetype == 'mssql')
		{
			$start_at = empty($start_at)? 0 : intval($start_at);
			$sql = "
			SELECT TOP $per_page * FROM " .
			$tableprefix."authoricons
			WHERE iiconid > $start_at
			ORDER BY iiconid
			";
			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[iiconid]"] = $detail;
			}
			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_fusetalk_authoricon_usage(&$Db_object, $databasetype, $tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "	SELECT  u.iuserid, MAX(icon.iiconid) AS iiconid FROM
				" .$tableprefix . "forumusers AS u INNER JOIN
				" .$tableprefix . "authoricons AS icon ON icon.vchiconfilename = u.vchauthoricon
				WHERE icon.iiconid IS NOT NULL AND iuserid > $start_at
				GROUP BY u.iuserid
				ORDER BY u.iuserid LIMIT $per_page";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[$detail['iuserid']] = $detail['iiconid'];
			}
		}
		else if ($databasetype == 'mssql')
		{
			$start_at = empty($start_at)? 0 : intval($start_at);
			$sql = "SELECT TOP $per_page u.iuserid, MAX(icon.iiconid) AS iiconid  FROM
				" .$tableprefix . "forumusers AS u INNER JOIN
				" .$tableprefix . "authoricons AS icon ON icon.vchiconfilename = u.vchauthoricon
				WHERE icon.iiconid IS NOT NULL AND iuserid > $start_at
				GROUP BY u.iuserid
				ORDER BY u.iuserid";
			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[$detail['iuserid']] = $detail['iiconid'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function update_user_avatar(&$Db_object, $databasetype, $tableprefix, $userid, $iiconid)
	{
		switch($databasetype){
			case 'mysql':
				$sql = "UPDATE " .$tableprefix . "user AS u INNER JOIN " .$tableprefix . "avatar AS a
				ON a.importavatarid = $iiconid AND u.importuserid = $userid
					  set u.avatarid = a.avatarid"
					;
				$result = $Db_object->query($sql);
				return $result;
				break;
			default:
				return false;
		} // switch
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2213 $
|| ####################################################################
\*======================================================================*/
?>
