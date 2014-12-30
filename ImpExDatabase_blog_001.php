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
* @date 		$Date: 2007-06-22 19:03:23 -0700 (Fri, 22 Jun 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }
require_once (IDIR . '/ImpExDatabase_blog.php');

class ImpExDatabase extends ImpExDatabaseBlog { }

/*======================================================================*/
?>
