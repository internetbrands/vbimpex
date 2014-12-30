<?php

/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin [#]version[#] - Licence Number [#]license[#]
  || # ---------------------------------------------------------------- # ||
  || # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

if (!defined('IDIR')) {
	die;
}

/* simply contains a function that defines how to populate target_db.vbfields */

function &retrieve_vbfields_queries($tableprefix = '') {
	$queries = array();

	$queries[] = "DROP TABLE IF EXISTS {$tableprefix}vbfields";

	$queries[] = "CREATE TABLE `{$tableprefix}vbfields` (
	  `fieldname` varchar(50) NOT NULL default '',
	  `tablename` varchar(20) NOT NULL default '',
	  `vbmandatory` enum('Y','N','A') NOT NULL default 'N',
	  `defaultvalue` varchar(200) default '!##NULL##!',
	  `dictionary` mediumtext NOT NULL,
	  `product` varchar(25) default ''
	)
	";

	# Adminstrator
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'administrator', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('adminpermissions', 'administrator', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('navprefs', 'administrator', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('cssprefs', 'administrator', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Attachment
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attachmentid', 'attachment', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filename', 'attachment', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filedata', 'attachment', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'attachment', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('counter', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filesize', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postid', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filehash', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('posthash', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('thumbnail', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('thumbnail_dateline', 'attachment', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('extension', 'attachment', 'N', '!##NULL##!','return true;', 'vbulletin')";

	# Attachment 4.0
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('caption', 'attachment', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('settings', 'attachment', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'attachment', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'attachment', 'N', 'visible', 'return true;', 'vbulletin')";

	# Avatar
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarid', 'avatar', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('minimumposts', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarpath', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('imagecategoryid', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'avatar', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('height', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('width', 'avatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Custom avatar
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filedata', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filename', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'customavatar', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filesize', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('height', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('width', 'customavatar', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Forum
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumid', 'forum', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('styleid', 'forum', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'forum', 'Y', 'Forum title', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'forum', 'N', '!Forum description', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'forum', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'forum', 'Y', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('replycount', 'forum', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastpost', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastposter', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastthread', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastthreadid', 'forum', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lasticonid', 'forum', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('threadcount', 'forum', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('daysprune', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('newpostemail', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('newthreademail', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentid', 'forum', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentlist', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('password', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('link', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('childlist', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Forum permission (depreciate)
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumpermissionid', 'forumpermission', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumid', 'forumpermission', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usergroupid', 'forumpermission', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumpermissions', 'forumpermission', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Forumread (depreciate)
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'forumread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumid', 'forumread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('readtime', 'forumread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Moderator
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('moderatorid', 'moderator', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'moderator', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumid', 'moderator', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('permissions', 'moderator', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('permissions2', 'moderator', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Pm
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmid', 'pm', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmtextid', 'pm', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'pm', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('folderid', 'pm', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('messageread', 'pm', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Pmtext
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmtextid', 'pmtext', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('fromuserid', 'pmtext', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('fromusername', 'pmtext', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'pmtext', 'Y', 'PM title', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('message', 'pmtext', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('touserarray', 'pmtext', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('iconid', 'pmtext', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'pmtext', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showsignature', 'pmtext', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'pmtext', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Poll
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pollid', 'poll', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('question', 'poll', 'Y', 'Poll question', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'poll', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'poll', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('votes', 'poll', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('active', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('numberoptions', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('timeout', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('multiple', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('voters', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('public', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastvote', 'poll', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Poll vote
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pollvoteid', 'pollvote', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pollid', 'pollvote', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'pollvote', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('votedate', 'pollvote', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('voteoption', 'pollvote', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";

	#Post
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postid', 'post', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('threadid', 'post', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentid', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('username', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'post', 'Y', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pagetext', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'post', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showsignature', 'post', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('iconid', 'post', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'post', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attach', 'post', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Profilefield
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('profilefieldid', 'profilefield', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('required', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('hidden', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('maxlength', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('size', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('editable', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('data', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('height', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('def', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('optional', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('searchable', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('memberlist', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('regex', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('form', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('html', 'profilefield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Ranks
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('rankid', 'ranks', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('minposts', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ranklevel', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('rankimg', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usergroupid', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Smilie
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('smilieid', 'smilie', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'smilie', 'N', 'Smilie title', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('smilietext', 'smilie', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('smiliepath', 'smilie', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('imagecategoryid', 'smilie', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'smilie', 'N', '1', 'return true;', 'vbulletin')";

	# Thread
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('threadid', 'thread', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'thread', 'Y', 'Thread title', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('firstpostid', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastpost', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumid', 'thread', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pollid', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('open', 'thread', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('replycount', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postusername', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postuserid', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastposter', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('views', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('iconid', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('notes', 'thread', 'N', 'Imported thread', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'thread', 'N', '1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sticky', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('votenum', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('votetotal', 'thread', 'N', '0', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attach', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('similar', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('hiddencount', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# User
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'user', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usergroupid', 'user', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('membergroupids', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displaygroupid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('username', 'user', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('password', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('passworddate', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('email', 'user', 'Y', 'noemail-impex@example.com', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('styleid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentemail', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('homepage', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('icq', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('aim', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('yahoo', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showvbcode', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usertitle', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('customtitle', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('joindate', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('daysprune', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastvisit', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastactivity', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastpost', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('posts', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('reputation', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('reputationlevelid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('timezoneoffset', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmpopup', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarrevision', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('birthday', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('maxposts', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('startofweek', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('referrerid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('languageid', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('msn', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('emailstamp', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('threadedmode', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmtotal', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmunread', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('salt', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('autosubscribe', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('profilepicrevision', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatar', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('birthday_search', 'user', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Userban
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usergroupid', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displaygroupid', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('adminid', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('bandate', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('liftdate', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('customtitle', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usertitle', 'userban', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Userfield
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('temp', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field1', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field2', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field3', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field4', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field8', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field9', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field10', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field11', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field12', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field13', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field46', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field49', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field50', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field51', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field57', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field58', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field59', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field60', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field61', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field62', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field63', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field64', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('field65', 'userfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Usergroup
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usergroupid', 'usergroup', 'A', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'usergroup', 'N', 'Usergroup title', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'usergroup', 'N', 'Usergroup description', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usertitle', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('passwordexpires', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('passwordhistory', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmquota', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmsendmax', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmforwardmax', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('opentag', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('closetag', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('canoverride', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ispublicgroup', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forumpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('calendarpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('wolpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('adminpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('genericpermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('genericoptions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmpermissions_bak', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attachlimit', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarmaxwidth', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarmaxheight', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('avatarmaxsize', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('profilepicmaxwidth', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('profilepicmaxheight', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('profilepicmaxsize', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Ranks
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('display', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('stack', 'ranks', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Usertextfield
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('subfolders', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pmfolders', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('buddylist', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ignorelist', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('signature', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('searchprefs', 'usertextfield', 'N', '!##NULL##!', 'return true;', 'vbulletin')";

	# Customprofilepic
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'customprofilepic', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filedata', 'customprofilepic', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'customprofilepic', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filename', 'customprofilepic', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'customprofilepic', 'N', '1','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filesize', 'customprofilepic', 'N', '!##NULL##!','return true;', 'vbulletin')";

	# Image category
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'imagecategory', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('imagetype', 'imagecategory', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'imagecategory', 'N', '!##NULL##!','return true;', 'vbulletin')";

	# Import id's
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importuserid', 'user', 'Y', '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importthreadid', 'thread', 'Y', '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importforumid', 'thread', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importthreadid', 'post', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importpollid', 'poll', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importsmilieid', 'smilie', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importrankid', 'ranks', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importforumid', 'forum', 'Y', '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcategoryid', 'forum', 'Y', '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importusergroupid', 'usergroup', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importpmid', 'pmtext', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importavatarid', 'avatar', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcustomavatarid', 'customavatar', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importmoderatorid', 'moderator', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importpostid', 'post', 'N',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importattachmentid', 'attachment', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importpmid', 'pm', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcustomprofilepicid', 'customprofilepic', 'Y',  '-1','return true;', 'vbulletin')";

	// vB 3.6.0 additions
	# usergroup
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('signaturepermissions', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigpicmaxwidth', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigpicmaxheight', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigpicmaxsize', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigmaximages', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigmaxsizebbcode', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigmaxchars', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigmaxrawchars', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigmaxlines', 'usergroup', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	# user
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usernote', 'user', 'N', 'Imported user','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sigpicrevision', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipoints', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('infractions', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('warnings', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('infractiongroupids', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('infractiongroupid', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('adminoptions', 'user', 'N', '!##NULL##!','return true;', 'vbulletin')";
	# forum
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showprivate', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastpostid', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('defaultsortfield', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('defaultsortorder', 'forum', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	# thread
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('deletedcount', 'thread', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	# phrase
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importphraseid', 'phrase', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('varname', 'phrase', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('fieldname', 'phrase', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('text', 'phrase', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('product', 'phrase', 'N', 'vbulletin', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('languageid', 'phrase', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('username', 'phrase', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'phrase', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('version', 'phrase', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	# subscription
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importsubscriptionid', 'subscription', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('cost', 'subscription', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('active', 'subscription', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'subscription', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('adminoptions', 'subscription', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('varname', 'subscription', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('membergroupids', 'subscription', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'subscription', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('forums', 'subscription', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nusergroupid', 'subscription', 'N', '!##NULL##!', 'return true;', 'vbulletin')";
	# subscriptionlog
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importsubscriptionlogid', 'subscriptionlog', 'Y',  '-1', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('subscriptionid', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pusergroupid', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('status', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('regdate', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('expirydate', 'subscriptionlog', 'Y', '!##NULL##!', 'return true;', 'vbulletin')";

	##
	#
	# Blog
	#
	##
	# blog
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog', 'A',  '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('firstblogtextid', 'blog', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'blog', 'Y', '6', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog', 'Y', 'title', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_visible', 'blog', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_moderation', 'blog', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_deleted', 'blog', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attach', 'blog',  'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'blog', 'N', 'visible', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('views', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('username', 'blog', 'N',  '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('trackback_visible', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('trackback_moderation', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastcomment', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastblogtextid', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastcommenter', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingnum', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingtotal', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('rating', 'blog', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pending', 'blog',  'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogcategoryid', 'blog',  'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('categories', 'blog', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('taglist', 'blog', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postedby_userid', 'blog', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postedby_username', 'blog', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogid', 'blog',  'Y', '-1',  'return true;', 'blog')";


	# blog_user

	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importbloguserid', 'blog_user', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('bloguserid', 'blog_user', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog_user', 'N', 'title', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'blog_user', 'N', 'description', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'blog_user', 'N', '1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('viewoption', 'blog_user', 'N', 'all', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments', 'blog_user', 'N', 'Imported', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastblog', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastblogid', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastblogtitle', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastcomment', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastcommenter', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastblogtextid', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('entries', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('deleted', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('moderation', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('draft', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pending', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingnum', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingtotal', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('rating', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('subscribeown', 'blog_user', 'N', 'none', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('subscribeothers', 'blog_user', 'N', 'none', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('uncatentries', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options_everyone', 'blog_user', 'N', '3', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options_buddy', 'blog_user', 'N', '3', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options_ignore', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('isblogmoderator', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_moderation', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_deleted', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('akismet_key', 'blog_user', 'N', '', 'return true;', 'blog')";

	# blog_user 4.x
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options_member', 'blog_user', 'N', '3', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('options_guest', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sidebar', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('custompages', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('customblocks', 'blog_user', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('memberids', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('memberblogids', 'blog_user', 'N', '!##NULL##!', 'return true;', 'blog')";

	# blog_comments
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('usercommentid', 'blog_usercomment', 'A', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_usercomment', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postuserid', 'blog_usercomment', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postusername', 'blog_usercomment', 'N', 'username', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_usercomment', 'N', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'blog_usercomment', 'N', '1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'blog_usercomment', 'N', 'visible', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pagetext', 'blog_usercomment', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'blog_usercomment', 'N', '0', 'return true;', 'blog')";

	# blog_text
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogtextid', 'blog_text', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_text', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_text', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_text', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pagetext', 'blog_text', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog_text', 'N', 'Blog title', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'blog_text', 'N', 'visible', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'blog_text', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('username', 'blog_text', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'blog_text', 'N', 'email', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('reportthreadid', 'blog_text', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('bloguserid', 'blog', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogtextid', 'blog_text', 'Y', '-1', 'return true;', 'blog')";

	# blog_text 4.x
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('htmlstate', 'blog_text', 'N', 'on_nl2br', 'return true;', 'blog')";

	# blog_category

	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogcategoryid', 'blog_category', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogcategoryid', 'blog_category', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog_category', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_category', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'blog_category', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('childlist', 'blog_category', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentlist', 'blog_category', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentid', 'blog_category', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'blog_category', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('entrycount', 'blog_category', 'N', '0',  'return true;', 'blog')";

	# blog_groupmembership
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importbloggroupmembershipid', 'blog_groupmembership', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('bloguserid', 'blog_groupmembership', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_groupmembership', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('permissions', 'blog_groupmembership', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'blog_groupmembership', 'N', 'pending',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_groupmembership', 'N', '0',  'return true;', 'blog')";

	# blog_customblock
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcustomblockid', 'blog_custom_block', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('customblockid', 'blog_custom_block', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog_custom_block', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_custom_block', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pagetext', 'blog_custom_block', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_custom_block', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('allowsmilie', 'blog_custom_block', 'N', '1',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'blog_custom_block', 'N', 'block',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('location', 'blog_custom_block', 'N', 'none',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'blog_custom_block', 'N', '0',  'return true;', 'blog')";

	# blog_categoryuser

	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogcategoryid', 'blog_categoryuser', 'Y', '0', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogcategoryid', 'blog_categoryuser', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_categoryuser', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_categoryuser', 'Y', '!##NULL##!', 'return true;', 'blog')";

	# blog_attachment
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogattachmentid', 'blog_attachment', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('attachmentid', 'blog_attachment', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_attachment', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_attachment', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filename', 'blog_attachment', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filedata', 'blog_attachment', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filesize', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('filehash', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('posthash', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('visible', 'blog_attachment', 'N', 'visible',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('counter', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('thumbnail', 'blog_attachment', 'N', '!##NULL##!',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('thumbnail_dateline', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('thumbnail_filesize', 'blog_attachment', 'N', '0',  'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('extension', 'blog_attachment', 'N', '!##NULL##!',  'return true;', 'blog')";

	# blog_moderator
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogmoderatorid', 'blog_moderator', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogmoderatorid', 'blog_moderator', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogmoderatorid', 'blog_moderator', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_moderator', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('permissions', 'blog_moderator', 'N', '507', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'blog_moderator', 'N', 'normal', 'return true;', 'blog')";

	# blog_rate
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblograteid', 'blog_rate', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blograteid', 'blog_rate', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_rate', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_rate', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('vote', 'blog_rate', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'blog_rate', 'N', '!##NULL##!', 'return true;', 'blog')";

	# blog_trackback
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importblogtrackbackid', 'blog_trackback', 'Y', '-1', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogtrackbackid', 'blog_trackback', 'A', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_trackback', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'blog_trackback', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('url', 'blog_trackback', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('snippet', 'blog_trackback', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('state', 'blog_trackback', 'N', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_trackback', 'Y', '!##NULL##!', 'return true;', 'blog')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_trackback', 'N', '!##NULL##!', 'return true;', 'blog')";

	# blog_subscribeentry
	/*
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogsubscribeentryid', 'blog_subscribeentry', 'A', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'blog_subscribeentry', 'Y', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_subscribeentry', 'Y', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_subscribeentry', 'N', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'blog_subscribeentry', 'N', 'email', 'return true;', 'blog')";
	 */

	# blog_subscribeuser
	/*
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogsubscribeuserid',  'blog_subscribeuser', 'A', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('bloguserid', 'blog_subscribeuser', 'Y', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'blog_subscribeuser', 'Y', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('dateline', 'blog_subscribeuser', 'N', '!##NULL##!', 'return true;', 'blog')";
	  $queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('type', 'blog_subscribeuser', 'N', 'email', 'return true;', 'blog')";
	 */

	# cms_article
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmscontentid', 'cms_article', 'Y', '-1', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('pagetext', 'cms_article', 'Y', '', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('threadid', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogid', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('posttitle', 'cms_article', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postauthor', 'cms_article', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('poststarter', 'cms_article', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('blogpostid', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('postid', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('post_posted', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('post_started', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('previewtext', 'cms_article', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('previewimage', 'cms_article', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('imagewidth', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('imageheight', 'cms_article', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('previewvideo', 'cms_article', 'N', '!##NULL#!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('htmlstate', 'cms_article', 'N', 'on_nl2br', 'return true;', 'cms')";

	#cms_category
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmscategoryid', 'cms_category', 'Y', '-1', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentnode', 'cms_category', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('category', 'cms_category', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'cms_category', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('catleft', 'cms_category', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('catright', 'cms_category', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentcat', 'cms_category', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('enabled', 'cms_category', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('contentcat', 'cms_category', 'N', '0', 'return true;', 'cms')";

	#cms_grid
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmsgridid', 'cms_grid', 'Y', '-1', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('gridhtml', 'cms_grid', 'Y', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'cms_grid', 'Y', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('auxheader', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('auxfooter', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('addcolumn', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('addcolumnsnap', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('addcolumnsize', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('gridcolumns', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('flattened', 'cms_grid', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('template', 'cms_grid', 'N', '0', 'return true;', 'cms')";

	#cms_layout
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmslayoutid', 'cms_layout', 'Y', '-1', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'cms_layout', 'Y', '!##NULL#!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('gridid', 'cms_layout', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('template', 'cms_layout', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('status', 'cms_layout', 'N', 'active', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('contentcolumn', 'cms_layout', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('contentindex', 'cms_layout', 'N', '0', 'return true;', 'cms')";

	#cms_layoutwidget
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_layoutwidget', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('layoutid', 'cms_layoutwidget', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('widgetid', 'cms_layoutwidget', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('layoutcolumn', 'cms_layoutwidget', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('layoutindex', 'cms_layoutwidget', 'N', '0', 'return true;', 'cms')";

	#cms_widget
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmswidgetid', 'cms_widget', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('widgettypeid', 'cms_widget', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('varname', 'cms_widget', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'cms_widget', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'cms_widget', 'N', '!##NULL##!', 'return true;', 'cms')";

	#cms_widgetconfig
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('widgetid', 'cms_widgetconfig', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_widgetconfig', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_widgetconfig', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('name', 'cms_widgetconfig', 'Y', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('value', 'cms_widgetconfig', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('serialized', 'cms_widgetconfig', 'N', '0', 'return true;', 'cms')";

	#cms_widgettype
	#$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmswidgettypeid', 'cms_widgettype', 'Y', '0', 'return true;', 'cms')";
	#$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('class', 'cms_widgettype', 'Y', '!##NULL##!', 'return true;', 'cms')";
	#$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('packageid', 'cms_widgettype', 'Y', '0, 'return true;', 'cms')";
	#cms_navigation
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_navigation', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_navigation', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodelist', 'cms_navigation', 'N', '!##NULL##!', 'return true;', 'cms')";

	#cms_node
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmsnodeid', 'cms_node', 'Y', 'A', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeleft', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('noderight', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('parentnode', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('contenttypeid', 'cms_node', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('contentid', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('url', 'cms_node', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('styleid', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('layoutid', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('publishdate', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('setpublish', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('issection', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('onhomepage', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('permissionsfrom', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('lastupdated', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('publicpreview', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('comments_enabled', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('new', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showtitle', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showuser', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showpreviewonly', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showupdated', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showviewcount', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('settingsforboth', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('includechildren', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('editshowchildren', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showall', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showpublishdate', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('showrating', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('hidden', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('shownav', 'cms_node', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nosearch', 'cms_node', 'N', '0', 'return true;', 'cms')";

	#cms_nodecategory
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_nodecategory', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('categoryid', 'cms_nodecategory', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_nodecategory', 'Y', '0', 'return true;', 'cms')";

	#cms_nodeconfig
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_nodeconfig', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_nodeconfig', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('name', 'cms_nodeconfig', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('value', 'cms_nodeconfig', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('serialized', 'cms_nodeconfig', 'N', '0', 'return true;', 'cms')";

	#cms_nodeinfo
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_nodeinfo', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_nodeinfo', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('description', 'cms_nodeinfo', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('title', 'cms_nodeinfo', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('viewcount', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('creationdate', 'cms_nodeinfo', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('workflowdate', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('workflowstatus', 'cms_nodeinfo', 'N', 'published', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('workflowcheckedout', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('workflowpending', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('workflowlevelid', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('associatedthreadid', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('keywords', 'cms_nodeinfo', 'N', '!##NULL##!', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingnum', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ratingtotal', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('rating', 'cms_nodeinfo', 'N', '0', 'return true;', 'cms')";

	#cms_rate
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importcmsrateid', 'cms_rate', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_rate', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('userid', 'cms_rate', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('vote', 'cms_rate', 'N', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('ipaddress', 'cms_rate', 'N', '!##NULL##!', 'return true;', 'cms')";

	#cms_sectionorder
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('importid', 'cms_sectionorder', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('sectionid', 'cms_sectionorder', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('nodeid', 'cms_sectionorder', 'Y', '0', 'return true;', 'cms')";
	$queries[] = "INSERT INTO {$tableprefix}vbfields VALUES ('displayorder', 'cms_sectionorder', 'N', '0', 'return true;', 'cms')";

	return $queries;
}

/* ======================================================================*\
  || ####################################################################
  || # Downloaded: [#]zipbuilddate[#]
  || # CVS: $RCSfile$ - $Revision: 2364 $
  || ####################################################################
  \*====================================================================== */
?>
