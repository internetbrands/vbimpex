<?
/*
* @package 		ImpEx.tools
* @version		$Revision: 2286 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2010-02-27 15:36:08 -0500 (Sat, 27 Feb 2010) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

// Id swapper.
// Swaps all the userids in the database for the imported userid
// THIS IS A GARRUNTEED WAY TO TRASH YOU DB !!!
// COMPLETLY INVALIDATES SUPPORT
// USE AT YOUR OWN RISK.
//
// I / we / the company takes no responsiability for you running this script
// Jerry - jerry.hutchings@vbulletin.com


define('DIR', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once (DIR . '/db_mysql.php');


$Db_target = new DB_Sql_vb_impex();


$Db_target->server 		= 'localhost';
$Db_target->user    	= 'vb3';
$Db_target->password 	= 'vb3';
$Db_target->database 	= 'vb3';
$Db_target->connect();


$refrence_ids = array();

$ids = $Db_target->query("SELECT userid,importuserid FROM user where importuserid !='null'");

while ($id = $Db_target->fetch_array($ids))
{
	$refrence_ids[$id['userid']] = $id['importuserid'];
}


echo "<h4>Altering tables.........</h4>";

// ALTER TO UNSIGNED && DROP PRIMARY KEY

	$Db_target->query("ALTER TABLE `user` CHANGE `userid` `userid` INT( 10 ) NOT NULL");
	$Db_target->query("ALTER TABLE `user` DROP PRIMARY KEY");
	echo "<p>User table.....</p>";

	$Db_target->query("ALTER TABLE `customavatar` CHANGE `userid` `userid` INT( 10 ) NOT NULL");
	$Db_target->query("ALTER TABLE `customavatar` DROP PRIMARY KEY");
	echo "<p>User table.....</p>";



echo "<h4>Tables well the truly done now ........</h4>";

$i=0;
foreach($refrence_ids as $vb_user_id => $import_user_id)
{
	// user vb_id -> importid
	$Db_target->query("UPDATE user SET userid='" . $import_user_id . "' WHERE userid='" . $vb_user_id . "'");

	// importid ->  user vb_id
	$Db_target->query("UPDATE user SET importuserid='" . $vb_user_id . "' WHERE userid='" . $import_user_id . "'");

	// customavatar
	$Db_target->query("UPDATE customavatar SET userid='" . $import_user_id . "' WHERE userid='" . $vb_user_id . "'");

	echo "<br />Done - " . $i++;
	flush();
}


/*
#####################
ALTER TO UNSIGNED && DROP PRIMARY KEY
#####################
user
customavatar
access
administrator
customprofilepic
subscribeevent
userban
userfield
usertextfield

#####################
ALTER TO UNSIGNED && DROP INDEX
#####################
calendarmoderator
event
moderator
passwordhistory
pm
pmreceipt
pollvote
post
posthash
search
subscribeforum - SUBINDEX
subscribethread - indexname
threadrate - threadid
useractivation
usernote

#####################
ALTER TO UNSIGNED
#####################
adminlog
announcement
attachment
deletionlog
editlog
moderatorlog
reminder
reputation
session
subscriptionlog
usergroupleader
usergrouprequest
*/

?>
