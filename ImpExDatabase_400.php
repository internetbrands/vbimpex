<?php
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
* The database proxy object.
*
* This handles interaction with the different types of database.
*
* @package 		ImpEx
* @date 		$Date: 2007-08-23 17:22:48 -0700 (Thu, 23 Aug 2007) $
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
/*======================================================================*/
?>
