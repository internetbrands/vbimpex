<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* The database proxy object.
*
* This handles interaction with the different types of database.
*
* @package 		ImpEx
* @version		$Revision: 1830 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2007-08-23 17:22:48 -0700 (Thu, 23 Aug 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

if (!class_exists('ImpExDatabaseCore')) { die('Direct class access violation'); }

class ImpExDatabase extends ImpExDatabaseCore
{
	/**
	* Class version
	*
	* This will allow the checking for inter-operability of class version in different
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '0.0.1';

	var $_target_system = 'forum';

	/**
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExDatabase()
	{
	}
		
	function import_attachment($Db_object, $databasetype, $tableprefix, $import_post_id = TRUE)
	{
		return $this->import_vb4_attachment($Db_object, $databasetype, $tableprefix, $import_post_id = true);
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1830 $
|| ####################################################################
\*======================================================================*/
?>
