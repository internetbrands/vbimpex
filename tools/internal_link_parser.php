<?#shebang#?><?php
/*
* @package 		ImpEx.tools
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-08-30 14:38:02 -0400 (Tue, 30 Aug 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

########################################
# CURRENTLY WORKS FOR :
# phpBB:
#	thread links (viewtopic.php=XXX)
########################################


// BACK UP YOUR DATABASE
// Thread id swapper
// Swaps imported id's for the new vB id's
// THIS IS A GARRUNTEED WAY TO TRASH YOU DB !!! .
// COMPLETLY INVALIDATES SUPPORT
// USE AT YOUR OWN RISK.
// BACK UP YOUR DATABASE
//
// I / we / the company takes no responsiability for you running this script
// Jerry - jerry.hutchings@vbulletin.com
########################################
#
#	Set to true / false
# $do_posts = ALL POSTS
# $do_sigs 	= ALL SIGNATURES
# $do_thread_titles = ALL THREAD TITLES
########################################
// BACK UP YOUR DATABASE
$do_posts			= true;
$do_sigs			= false;
$do_thread_titles	= false;
$do_pm_text			= false;
########################################

// BACK UP YOUR DATABASE

# e.g. http://example_source.com/phpBB/viewtopic.php?topic=576&forum=1
$old_domain				= 'http://example_source.com/';
$old_forum_path 		= 'phpBB/';

#  e.g. http://example_target.com/vBulletin/showthread.php?t=343

$new_domain				= 'http://example_target.com/';
$new_forum_path 		= 'vBulletin/';

$file_extenstion		= 'php';   // php, php3, html, etc, whatever your vb board is running

// BACK UP YOUR DATABASE
##################################################################################################################
# CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHIING BELOW THIS LINE # CHANGE NOTHI
##################################################################################################################

include_once('./../db_mysql.php');
include_once('./../ImpExFunction.php');
include_once('./../ImpExDatabaseCore.php');

// BACK UP YOUR DATABASE
extract(parse_ini_file('./../ImpExConfig.php'), EXTR_SKIP);

$Db_target = new DB_Sql_vb_impex();
$Db_target->appname      	= 'vBulletin:ImpEx Target';
$Db_target->appshortname 	= 'vBulletin:ImpEx Target';
$Db_target->type			= 'mysql';
$Db_target->database     	= $targetdatabase;

$Db_target->connect($targetserver, $targetuser, $targetpassword, 0);
$Db_target->select_db($targetdatabase);

$Imp_database = new ImpExDatabaseCore();
$thread_ids_array = $Imp_database->get_threads_ids($Db_target, $Db_target->type, $target_table_prefix);

// BACK UP YOUR DATABASE

# Posts
if($do_posts)
{
	$posts = $Db_target->query("SELECT postid, pagetext FROM " . $targettableprefix . "post");

	while ($post = $Db_target->fetch_array($posts))
	{
		if(strrpos($post['pagetext'],$old_domain . $old_forum_path))
		{
			$text = $post['pagetext'];
			// Threads id replacment 
			#viewtopic.php\?topic OR viewtopic.php\?t
			
			$text =	preg_replace(
				"#(.*){$old_domain}{$old_forum_path}viewtopic.php\?topic=([0-9]+)\&forum=[0-9]+(.*)#isUe", 
				"phpBB_thread_id_swapper(\$file_extenstion, \$new_domain, \$new_forum_path, '\\1', '\\2', \$thread_ids_array)",
				$post['pagetext']
			);
			$Db_target->query("UPDATE " . $targettableprefix . "post SET pagetext='" . addslashes($text) . "' WHERE postid='" . $post['postid'] . "'");
			echo "<br /><b>Post done -></b><i> " . $post['postid'] . "</i>";
		}
	}
}

# Signatures
if($do_sigs)
{
	$users = $Db_target->query("SELECT userid, signature FROM " . $targettableprefix . "usertextfield");

	while ($user = $Db_target->fetch_array($users))
	{
		if(strrpos($user['signature'],$old_domain . $old_forum_path))
		{
			// Threads id replacment 
			$text =	preg_replace(
				"#(.*){$old_domain}{$old_forum_path}viewtopic.php\?topic=([0-9]+)\&forum=[0-9]+(.*)#isUe", 
				"phpBB_thread_id_swapper(\$file_extenstion, \$new_domain, \$new_forum_path, '\\1', '\\2', \$thread_ids_array)",
				$post['pagetext']
			);		
			$text = str_replace(array_keys($replacer), $replacer, $user['signature']);
			$Db_target->query("UPDATE " . $targettableprefix . "usertextfield SET signature='" . addslashes($text) . "' WHERE userid='" . $user['userid'] . "'");
			echo "<br /><b>Signature done -></b><i> " . $user['userid'] . "</i>";
		}
	}
}

# Thread titles
if($do_thread_titles)
{
	$threads = $Db_target->query("SELECT threadid, title FROM " . $targettableprefix . "thread");

	while ($thread = $Db_target->fetch_array($threads))
	{
		if(strrpos($thread['title'],$old_domain . $old_forum_path))
		{		
			$text = str_replace(array_keys($replacer), $replacer, $thread['title']);
			$Db_target->query("UPDATE " . $targettableprefix . "thread SET title='" . addslashes($text) . "' WHERE threadid='" . $thread['threadid'] . "'");
			echo "<br /><b>Thread done -></b><i> " . $user['threadid'] . "</i> :: <b>{$text}</b>";
		}
	}
}

# PM text
if($do_pm_text)
{
	$pms = $Db_target->query("SELECT pmtextid, message FROM " . $targettableprefix . "pmtext");

	while ($pm = $Db_target->fetch_array($pms))
	{
		if(strrpos($pm['message'],$old_domain . $old_forum_path))
		{				
			$text = str_replace(array_keys($replacer), $replacer, $pm['message']);
			$Db_target->query("UPDATE " . $targettableprefix . "pmtext SET message='" . addslashes($text) . "' WHERE pmtextid='" . $pm['pmtextid'] . "'");
			echo "<br /><b>Pm done -></b><i> " . $pm['pmtextid'] . "</i> :: <b>{$text}</b>";
		}
	}
}
// BACK UP YOUR DATABASE
// You shouldn't be reading down here !!




function phpBB_thread_id_swapper($file_extenstion, $new_domain, $new_forum_path, $pre, $thread_id, $thread_ids_array)
{
	return $pre . $new_domain . $new_forum_path . "showthread.{$file_extenstion}?t=" . $thread_ids_array[$thread_id];
}

?>
