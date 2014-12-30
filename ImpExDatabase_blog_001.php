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
* @version		$Revision: 1771 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2007-06-22 19:03:23 -0700 (Fri, 22 Jun 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }
require_once (IDIR . '/ImpExDatabase_blog.php');

class ImpExDatabase extends ImpExDatabaseBlog { }

/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1771 $
|| ####################################################################
\*======================================================================*/
?>
