<?#shebang#?><?php
if (function_exists('set_time_limit') AND get_cfg_var('safe_mode')==0)
{
	@set_time_limit(0);
}

ignore_user_abort(true);
error_reporting(E_ALL  & ~E_NOTICE);
/*
* @package 		ImpEx.tools
* @version		$Revision: 1826 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2007-08-21 17:03:19 -0400 (Tue, 21 Aug 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
// BACK UP YOUR DATABASE
// Cleaner
// Swaps X for Y in the database handy for smilies that have been wrongly parsed
// BACK UP YOUR DATABASE
//
########################################
#
#	Set to true / false
# $do_posts 		= ALL POSTS
# $do_sigs 			= ALL SIGNATURES
# $do_thread_titles = ALL THREAD TITLES
# $do_pm_text		= ALL PM TEXTS;
# $do_pm_text_title	= ALL PM TITLES;
########################################
// BACK UP YOUR DATABASE
// Set to true to run, and false when done, or remove this script (and all of impex when finished).
$active				= false;

// Set true or false as to the data you want to clean
$do_posts			= true;
$do_sigs			= false;
$do_thread_titles	= false;
$do_pm_text			= false;
$do_pm_text_title	= false;
########################################

# Replace 'Find me' and "Replace with me" with the strings  you want replaced i.e
# "<b>"			=> "[b]"
# "<blockquote>" 	=> "[quote]"
# add as many elements to the array as needed

// BACK UP YOUR DATABASE
$replacer = array(
			""	=> "",
			""	=> "",
			""	=> "",
			""	=> "",
			""	=> ""
);

// BACK UP YOUR DATABASE

################################################################################
# CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHI
################################################################################
if (!$active)
{
	exit('Not active');
}
define('IDIR', (($getcwd = getcwd()) ? $getcwd : '.'));
include_once('./../db_mysql.php');
// BACK UP YOUR DATABASE
require_once ('./../ImpExConfig.php');

$targetserver 		= $impexconfig['target']['server'];
$targetuser			= $impexconfig['target']['user'];
$targetpassword		= $impexconfig['target']['password'];
$targetdatabase 	= $impexconfig['target']['database'];
$targettableprefix 	= $impexconfig['target']['tableprefix'];
$targettablecharset	= $impexconfig['target']['charset'];

$Db_target = new DB_Sql_vb_impex();
$Db_target->appname      	= 'vBulletin:ImpEx Target';
$Db_target->appshortname 	= 'vBulletin:ImpEx Target';
$Db_target->type			= 'mysql';
$Db_target->database     	= $targetdatabase;

$Db_target->connect($targetserver, $targetuser, $targetpassword, 0, $targettablecharset);
$Db_target->select_db($targetdatabase);
// BACK UP YOUR DATABASE

# Posts
if($do_posts)
{
	$posts = $Db_target->query("SELECT postid, pagetext, title FROM " . $targettableprefix . "post");

	while ($post = $Db_target->fetch_array($posts))
	{
		$text = str_replace(array_keys($replacer), $replacer, $post['pagetext']);
		$title_text = str_replace(array_keys($replacer), $replacer, $post['title']);
		#$text = preg_replace('##siU', '', $text);
		#$title_text = preg_replace('##siU', '', $text);
		$Db_target->query("UPDATE " . $targettableprefix . "post SET pagetext='" . addslashes($text) . "', title='" . addslashes($title_text) . "' WHERE postid='" . $post['postid'] . "'");
		echo "<br /><b>Post done -></b><i> " . $post['postid'] . "</i>";
	}
}

# Signatures
if($do_sigs)
{
	$users = $Db_target->query("SELECT userid, signature FROM " . $targettableprefix . "usertextfield");

	while ($user = $Db_target->fetch_array($users))
	{
		$text = str_replace(array_keys($replacer), $replacer, $user['signature']);
		#$text = preg_replace('##siU', '', $text);
		$Db_target->query("UPDATE " . $targettableprefix . "usertextfield SET signature='" . addslashes($text) . "' WHERE userid='" . $user['userid'] . "'");
		echo "<br /><b>Signature done -></b><i> " . $user['userid'] . "</i>";
	}
}

# Thread titles
if($do_thread_titles)
{
	$users = $Db_target->query("SELECT threadid, title FROM " . $targettableprefix . "thread");

	while ($user = $Db_target->fetch_array($users))
	{
		$text = str_replace(array_keys($replacer), $replacer, $user['title']);
		#$text = preg_replace('##siU', '', $text);
		$Db_target->query("UPDATE " . $targettableprefix . "thread SET title='" . addslashes($text) . "' WHERE threadid='" . $user['threadid'] . "'");
		echo "<br /><b>Thread done -></b><i> " . $user['threadid'] . "</i> :: <b>{$text}</b>";
	}
}

# PM text
if($do_pm_text)
{
	$pms = $Db_target->query("SELECT pmtextid, message, title FROM " . $targettableprefix . "pmtext");

	while ($pm = $Db_target->fetch_array($pms))
	{
		$text = str_replace(array_keys($replacer), $replacer, $pm['message']);
		$title = str_replace(array_keys($replacer), $replacer, $pm['title']);
		#$text = preg_replace('##siU', '', $text);
		$Db_target->query("UPDATE " . $targettableprefix . "pmtext SET message='" . addslashes($text) . "', title='" . addslashes($title) . "' WHERE pmtextid='" . $pm['pmtextid'] . "'");
		echo "<br /><b>Pm done -></b><i> " . $pm['pmtextid'] . "</i> :: <b>{$text}</b>";
	}
}

// BACK UP YOUR DATABASE
// You shouldn't be reading down here !!
?>
