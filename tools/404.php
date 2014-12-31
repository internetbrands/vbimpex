<?
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
* 404 for external and internal link redirect.
*
* @package			ImpEx.tools
*
*/

/*

Ensure you have this table for logging

CREATE TABLE `404_actions` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`time` INT UNSIGNED NOT NULL ,
`incomming` VARCHAR( 250 ) NOT NULL ,
`outgoing` VARCHAR( 250 ) NOT NULL ,
`action` TINYINT UNSIGNED NOT NULL DEFAULT '0',
PRIMARY KEY ( `id` )
) TYPE = MYISAM ;

If you have a high volume site and lot of redirects going on, a HEAP / ENGINE=MEMORY table could be a lot better.

*/

// System

#Currently supported : 'phpBB2' 'ubb.threads' 'vb3' 'ipb2'

$old_system 	= 'phpBB2';

// Domain
// Example :: http://www.example.com/phpBB/

$old_folder 	= 'phpBB/'; 								// With trailing slash
$old_ext_type	= '.php'; 									// Including preceding dot
$standard_404 	= 'http://www.example.com/not_found.html'; 	// The usual 404 that this script replaces

// Example :: www.example.com/vBulletin/

$new_domain 	= 'example';
$new_folder		= 'vBulletin/';	// With trailing slash
$ext_type		= '.php'; 		// File extension type that vBulletin is using, i.e. index.php including the preceding dot

// Database
// This is the vBulletin database, needed for import id look up and logging
$server 		= 'localhost';
$user 			= 'user';
$password 		= 'password';
$database 		= 'forum';
$tableprefix 	= '';

// System
$refresh_speed 	= 0;		// Speed of the redirect, 0 advised.
$do_logs 		= true;		// Always best to log to see that its working and if there are any "Unknown link type, talk to Jerry" links
$do_404			= false; 	// true = a 404 (Not Found) redirect. false is a 301 search engine friendly redirect (Moved Permanently)
$debug			= false;	// This is will stop the script from actually redirecting, it will just display what the SQL and the action

#############################################################################################################################
# Don't touch below
#############################################################################################################################

$old_id 		= 0;
$action_code	= 0;
$action 		= null;
$sql 			= null;
$page			= null;
$postcount		= null;

// Get the file names and types

switch ($old_system)
{
	case 'phpBB2' :
		$old_forum_script 	= "viewforum{$old_ext_type}?f=";
		$old_thread_script 	= "viewtopic{$old_ext_type}?t=";
		$old_post_script 	= "viewtopic{$old_ext_type}?p=";
		$old_user_script 	= "profile{$old_ext_type}?mode=viewprofile&u="; // Append userid
		break;
	case 'ubb.threads' :
		$old_forum_script 	= "postlist{$old_ext_type}?"; 		// postlist.php?Cat=&Board=beadtechniques -- have to try to do it on title
		$old_thread_script 	= "showflat{$old_ext_type}?"; 		// Cat=&Board=beadtechniques&Number=74690&page=0&view=collapsed&sb=5&o=&fpart=1Greg Go for Number=XX
		$old_post_script 	= "showthreaded{$old_ext_type}?";	// ubbthreads/showthreaded.php?Cat=&Board=othertopics&Number=79355&page=0&view=collapsed&sb=5&o=&fpart=1 -- going to thread link, not post, meh
		$old_user_script 	= "showprofile{$old_ext_type}"; 	// ubbthreads/showprofile.php?Cat=&User=SaraSally&Board=isgbannounce&what=ubbthreads&page=0&view=collapsed&sb=5&o -- username
		break;
	case 'vb3' :
		$old_forum_script 	= "forumdisplay{$old_ext_type}?f=";
		$old_thread_script 	= "showthread{$old_ext_type}?p=";
		$old_post_script 	= "showpost{$old_ext_type}?p=";
		$old_user_script 	= "member{$old_ext_type}?u=";
		break;
	case 'ipb2' :	// Single file controller, these are here for refrence, the preg_match finds the actual type during the matching
		$old_forum_script 	= "showforum=";						// index.php?s=29ef4154d9b74e8978e60ca39ecb506a&showforum=X
		$old_thread_script 	= "index.php?showtopic=";
		$old_post_script 	= "index.php?";						// index.php?s=&showtopic=209664&view=findpost&p=XXXXXXXX
		$old_user_script	= "index.php?showuser=";			// index.php?showuser=XXXXX
		break;
	default :
		// No valid system entered
		die('No valid system entered');
}

// It's for the old forum
if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}") === 0)
{
	switch ($old_system)
	{
		case 'phpBB2' :
			// It's a forum link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_forum_script}") === 0)
			{
				$action = 'forum';
				$old_id = intval(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?f=')+3));
				$sql = "SELECT forumid FROM {$tableprefix}forum WHERE importforumid={$old_id}";
			}

			// It's a thread link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_thread_script}") === 0)
			{
				$action = 'thread';
				if( preg_match("/&/", $_SERVER['REQUEST_URI']) )
				{
					$old_id = intval(substr(substr($_SERVER['REQUEST_URI'], 2), 0,  strpos(substr($_SERVER['REQUEST_URI'], 2), '&')));
					$sql = "SELECT threadid FROM {$tableprefix}thread WHERE importthreadid={$old_id}";
				}
				else
				{
					$old_id = intval(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?t=')+3));
					$sql = "SELECT threadid FROM {$tableprefix}thread WHERE importthreadid={$old_id}";
				}
			}

			// It's a post link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_post_script}") === 0)
			{
				$action = 'post';
				if( preg_match("/&/", $_SERVER['REQUEST_URI']) )
				{
					$old_id = intval(substr(substr($_SERVER['REQUEST_URI'], 2), 0,  strpos(substr($_SERVER['REQUEST_URI'], 2), '&')));
					$sql = "SELECT postid FROM {$tableprefix}post WHERE importpostid={$old_id}";
				}
				else
				{
					$old_id = intval(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?p=')+3));
					$sql = "SELECT postid FROM {$tableprefix}post WHERE importpostid={$old_id}";
				}
			}

			// It's a user link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_user_script}") === 0)
			{
				$action = 'user';
				// Cuts 12 out of this : profile.php?mode=viewprofile&u=12&sid=f646e2a0948e0244ba82cef12c3b93d8
				$old_id = intval(substr(substr($_SERVER['REQUEST_URI'], 19), 0,  strpos(substr($_SERVER['REQUEST_URI'], 19), '&')));
				$sql = "SELECT userid FROM {$tableprefix}user WHERE importuserid={$old_id}";
			}
		break;


		case 'ubb.threads' :

			// It's a forum link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_forum_script}") === 0)
			{
				$action = 'forum';
				$old_id = intval(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], 'Board=')+6));

				$sql = "SELECT forumid FROM {$tableprefix}forum WHERE importforumid={$old_id}";
			}

			// It's a thread link (Will get direct post links some times too)
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_thread_script}") === 0)
			{
				$action = 'thread';
				$begin	= strpos ($url, 'Number=');
				$middle	= strpos ($url, '&', $begin);

				$old_id = intval(substr($url, $begin+7, ($middle-$begin)-7)); // +7 & -7 To remove the off set of Number=

				$sql = "SELECT threadid FROM {$tableprefix}thread WHERE importthreadid={$old_id}";
			}

			// It's a post link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_post_script}") === 0)
			{
				if(preg_match('#(.*)Number=(.*)&(.*)#Uis', $_SERVER['REQUEST_URI'], $matches))
				{
					if (is_numeric($matches[2]))
					{
						$action = 'post';
						$sql = "SELECT postid FROM {$tableprefix}post WHERE importpostid = " . $matches[2];
					}
				}
			}

			// It's a user link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_user_script}") === 0)
			{
				if(preg_match('#(.*)User=(.*)&(.*)#Uis', $_SERVER['REQUEST_URI'], $matches))
				{
					if (is_numeric($matches[2]))
					{
						$action = 'post';
						$sql = "SELECT userid FROM {$tableprefix}user WHERE importpostid = " . $matches[2];
					}
				}
			}
		break;

		case 'vb3' :
			// It's a forum link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_forum_script}") === 0)
			{
				$action = 'forum';
				$old_id = intval(substr($_SERVER['REQUEST_URI'], 2, abs(strpos($_SERVER['REQUEST_URI'], '&',2)-2)));
				$sql = "SELECT forumid FROM {$tableprefix}forum WHERE importforumid={$old_id}";
			}

			// It's a thread link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_thread_script}") === 0)
			{
				$action = 'thread';
				if (strpos($_SERVER['REQUEST_URI'], '&page=')) // If its paged
				{
					preg_match('#(.*)\?t=([0-9]+)\&page=([0-9]+)#si', $_SERVER['REQUEST_URI'], $matches);
					if ($matches[2] AND $matches[3])
					{
						$matches[2] = intval($matches[2]);
						$sql = "SELECT threadid FROM {$tableprefix}thread WHERE importthreadid=" . $matches[2];
						$page = $matches[3];
					}
				}
				else
				{
					if ($id = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?t=')+3))
					{
						$id = intval($id);
						$sql = "SELECT threadid FROM {$tableprefix}thread WHERE importthreadid={$id}";
					}
				}
			}

			// It's a post link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_post_script}") === 0)
			{
				$action = 'post';
				if (strpos($_SERVER['REQUEST_URI'], '&postcount=')) // If its postcounted
				{
					preg_match('#(.*)\?p=([0-9]+)\&postcount=([0-9]+)#si', $_SERVER['REQUEST_URI'], $matches);
					if ($matches[2] AND $matches[3])
					{
						$matches[2] = intval($matches[2]);
						$sql = "SELECT postid FROM {$tableprefix}post WHERE importpostid = " . $matches[2];
						$postcount = $matches[3];
					}
				}
				else
				{
					if ($id = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?p=')+3))
					{
						$id = intval($id);
						$sql = "SELECT postid FROM {$tableprefix}post WHERE importpostid={$id}";
					}
				}
			}

			// It's a user link
			if (strpos($_SERVER['REQUEST_URI'], "/{$old_folder}{$old_user_script}") === 0)
			{
				$action = 'user';
				if ($id = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?u=')+3))
				{
					$id = intval($id);
					$sql = "SELECT userid FROM {$tableprefix}user WHERE importuserid ={$id}";
				}
			}
		break;

		case 'ipb2'	:
			if(preg_match('#index.php?(.*)&showforum=([0-9]+)#is', $_SERVER['REQUEST_URI'], $matches))
			{
				if (is_numeric($matches[2]))
				{
					$matches[2] = intval($matches[2]);
					$action = 'forum';
					$sql = "SELECT forumid FROM {$tableprefix}forum WHERE importforumid=" . $matches[2];
				}
			}

			// It's a thread link
			if(preg_match('#index.php\?showtopic=([0-9]+)#is', $_SERVER['REQUEST_URI'], $matches))
			{
				if (is_numeric($matches[1]))
				{
					$action = 'thread';
					$sql = "SELECT threadid FROM {$tableprefix}forum WHERE importthreadid=" . $matches[1];
				}
			}

			// It's a post link
			if(preg_match('#view=findpost\&p=([0-9]+)#is', $_SERVER['REQUEST_URI'], $matches))
			{
				if (is_numeric($matches[1]))
				{
					$action = 'post';
					$sql = "SELECT postid FROM {$tableprefix}forum WHERE importpostid=" . $matches[1];
				}
			}

			// It's a user link
			if(preg_match('#index.php\?showuser=([0-9]+)#is', $_SERVER['REQUEST_URI'], $matches))
			{
				if (is_numeric($matches[1]))
				{
					$action = 'user';
					$sql = "SELECT userid FROM {$tableprefix}user WHERE importuserid =" . $matches[1];
				}
			}
		break;

		default :
			// No valid system entered
			die('No valid system entered');
	}

	if (!$action)
	{
		$action = 'log';
	}

}

if ($debug)
{
	echo "<html><head><title>404 debug</title></head><body>";
	echo "<br>Action :: " .   $action;
	echo "<br>SQL :: " .  $sql;
	echo "<br>REQUEST_URI :: " . $_SERVER['REQUEST_URI'] ;
	echo "</body></html>";
	die;
}


if (!$action)
{
?>
	<html>
	<head>
		<meta http-equiv="refresh" content="<? echo $refresh_speed; ?>; URL=<? echo $standard_404; ?>">
	</head>
	<body>
	</body>
	</html>
<?
	// Got nuffink
	die;
}


// If we are here we have data to look up and redirect to a vBulletin page.

$link = @mysql_connect($server, $user, $password);
if (!$link)
{
	#die('Could not connect: ' . mysql_error());
	$new_url = $standard_404;
}
$db_selected = @mysql_select_db($database, $link);

if (!$db_selected)
{
	#die ('Can\'t use foo : ' . mysql_error());
	$new_url = $standard_404;
}

if ($sql)
{
	$result = @mysql_query($sql);
	$row = @mysql_fetch_row($result);

	if (!$row[0])
	{
		$action = 'Original data missing';
	}
	@mysql_free_result($result);
}

// Just incase
$new_url = $standard_404;

switch ($action)
{
	case 'cat':
	case 'forum':
		$new_url = "http://{$new_domain}/{$new_folder}forumdisplay{$ext_type}?f=" . $row[0];
		$action_code = 1;
		break;
	case 'thread':
		$new_url = "http://{$new_domain}/{$new_folder}showthread{$ext_type}?t=" . $row[0];
		if ($page)
		{
			$new_url .= "&page={$page}";
		}
		$action_code = 2;
		break;
	case 'post':
		$new_url = "http://{$new_domain}/{$new_folder}showpost{$ext_type}?p=" . $row[0];
		if ($postcount)
		{
			$new_url .= "&postcount={$postcount}";
		}
		$action_code = 3;
		break;
	case 'user':
		$new_url = "http://{$new_domain}/{$new_folder}member{$ext_type}?u=" . $row[0];
		$action_code = 4;
		break;
	case 'Original data missing':
		$action_code = 10;
		break;
	default :
		$action_code = 20;
		break;
}


// Do logging ?
if ($do_logs)
{
	// Codes
	# forum 	= 1
	# thread 	= 2
	# post 		= 3
	# user 		= 4

	# script error = 0

	# Original data missing 			= 10
	# Unknown link type, talk to Jerry	= 20

	$sql = "
	INSERT INTO {$tableprefix}404_actions
	(
		time, incomming, outgoing, action
	)
	VALUES
	(
		" . time() . ", '" . $_SERVER['REQUEST_URI'] . "', '" . $new_url . "', '" . $action_code . "'
	)
	";

	mysql_query($sql);
}

@mysql_close($link);

// Do the new redirect
if ($do_404)
{
?>
	<html>
	<head>
		<meta http-equiv="refresh" content="<? echo $refresh_speed; ?> URL=<? echo $new_url; ?>">
	</head>
	<body>
	</body>
	</html>
<?
}
else
{
	Header( "HTTP/1.1 301 Moved Permanently" );
	Header( "Location: {$new_url}" );
}
// Da end
?>
