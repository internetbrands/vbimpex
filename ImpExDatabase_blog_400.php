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
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }
require_once (IDIR . '/ImpExDatabase_blog.php');

class ImpExDatabase extends ImpExDatabaseBlog
{
	/**
	* Class version
	*
	* This will allow the checking for inter-operability of class version in different
	* versions of ImpEx
	*
	* @var    string
	*/

	var $_import_blog_ids = array(
		array('blog'				=>  'importblogid'),
		array('blog_category'		=>  'importblogcategoryid'),
		array('blog_categoryuser'	=>  'importblogcategoryid'),
		array('blog_moderator'		=>  'importblogmoderatorid'),
		array('blog_custom_block'	=>  'importcustomblockid'),
		array('blog_groupmembership'=>	'importbloggroupmembershipid'),
		array('blog_rate'			=>  'importblograteid'),
		array('blog_subscribeentry'	=>  'importblogsubscribeentryid'),
		array('blog_subscribeuser'	=>  'importblogsubscribeuserid'),
		array('blog_text'			=>  'importblogtextid'),
		array('blog_trackback'		=>  'importblogtrackbackid'),
		array('blog_user'			=>  'importbloguserid'),
		array('usergroup'			=>  'importusergroupid'),
		array('user'				=>  'importuserid'),
		array('usernote'			=>  'importusernoteid'),
		array('customavatar'		=>  'importcustomavatarid'),
		array('customprofilepic'	=>  'importcustomprofilepicid'),
	);

	/**
	* Imports the current objects values as a blog_user and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_user(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{

			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT bloguserid FROM {$tableprefix}blog_user WHERE importbloguserid=" . intval(trim($this->get_value('mandatory', 'importbloguserid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				if (!intval($this->get_value('mandatory', 'bloguserid')))
				{
					return false;
				}

				$Db_object->query("
					REPLACE INTO {$tableprefix}blog_user
					(
						bloguserid, title, description, allowsmilie, options,
						viewoption, comments, lastblog, lastblogid,
						lastblogtitle, lastcomment, lastcommenter, lastblogtextid,
						entries, deleted, moderation, draft, pending, ratingnum,
						ratingtotal, rating, subscribeown, subscribeothers,
						uncatentries, options_member, options_guest, options_buddy,
						options_ignore, isblogmoderator, comments_moderation,
						comments_deleted, categorycache, tagcloud, sidebar,
						custompages, customblocks, memberids, memberblogids, importbloguserid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'bloguserid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . /* Default :  Type : mediumtext */ "',
						'" . intval($this->get_value('nonmandatory', 'allowsmilie')) . /* Default :  Type : smallint(5) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'viewoption'), array('all','only','except'), 'all') . /* Default : all Type : enum('all','only','except') */ "',
						'" . intval($this->get_value('nonmandatory', 'comments')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'lastblog')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'lastblogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'lastblogtitle')) . /* Default :  Type : varchar(255) */ "',
						'" . intval($this->get_value('nonmandatory', 'lastcomment')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'lastcommenter')) . /* Default :  Type : varchar(100) */ "',
						'" . intval($this->get_value('nonmandatory', 'lastblogtextid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'entries')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'deleted')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'moderation')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'draft')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'pending')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingnum')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingtotal')) . /* Default :  Type : int(10) unsigned */ "',
						'" . floatval($this->get_value('nonmandatory', 'rating')) . /* Default :  Type : float unsigned */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'subscribeown'), array('none','usercp','email'), 'none') . /* Default : none Type : enum('none','usercp','email') */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'subscribeothers'), array('none','usercp','email'), 'none') . /* Default : none Type : enum('none','usercp','email') */ "',
						'" . intval($this->get_value('nonmandatory', 'uncatentries')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_member')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_guest')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_buddy')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_ignore')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'isblogmoderator')) . /* Default : 0 Type : smallint(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'comments_moderation')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'comments_deleted')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'categorycache')) . /* Default : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'tagcloud')) . /* Default : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'sidebar')) . /* Default : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'custompages')) . /* Default : mediumtext */ "',
						'" . intval($this->get_value('nonmandatory', 'customblocks')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'memberids')) . /* Default : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'memberblogids')) . /* Default : mediumtext */ "',
						'" . intval($this->get_value('mandatory', 'importbloguserid')) . "'
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres Database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}


/**
	* Imports the current objects values as a blog_attachment and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_attachment(&$Db_object, &$databasetype, &$tableprefix)
	{
		return $this->import_vb4_attachment($Db_object, $databasetype, $tableprefix, true, 'blog');
	}

	/**
	* Imports the current objects values as a blog_text and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_text(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogtextid FROM {$tableprefix}blog_text WHERE importblogtextid=" . intval(trim($this->get_value('mandatory', 'importblogtextid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_text
					(
						blogid, userid, dateline, pagetext,
						title, state, allowsmilie, username,
						ipaddress, reportthreadid, bloguserid,
						importblogtextid, htmlstate
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'dateline')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'pagetext')) . /* Default :  Type : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('moderation','visible','deleted'), 'visible') . /* Default : visible Type : enum('moderation','visible','deleted') */ "',
						'" . intval($this->get_value('nonmandatory', 'allowsmilie')) . /* Default : 0 Type : smallint(5) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'username')) . /* Default :  Type : varchar(100) */ "',
						'" . intval(sprintf('%u', ip2long($this->get_value('nonmandatory', 'ipaddress')))) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'reportthreadid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'bloguserid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importblogtextid')) . /* Default :  Type : bigint(20) */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'htmlstate'), array('off','on','on_nl2br'), 'on_nl2br') . "'
					)
				");

				if ($Db_object->affected_rows())
				{
					return $Db_object->insert_id();
				}
				else
				{
					return false;
				}
			}

			// Postgres Database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Imports the current objects values as a blog and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogid FROM {$tableprefix}blog WHERE importblogid=" . intval(trim($this->get_value('mandatory', 'importblogid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog
					(
						firstblogtextid, userid, dateline, comments_visible,
						comments_moderation, comments_deleted, attach, state,
						views, username, title, trackback_visible,
						trackback_moderation, options, lastcomment, lastblogtextid,
						lastcommenter, ratingnum, ratingtotal, rating,
						pending, categories, taglist, postedby_userid, postedby_username, importblogid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'firstblogtextid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'dateline')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'comments_visible')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'comments_moderation')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'comments_deleted')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'attach')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('moderation','draft','visible','deleted'), 'visible') . /* Default : visible Type : enum('moderation','draft','visible','deleted') */ "',
						'" . intval($this->get_value('nonmandatory', 'views')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'username')) . /* Default :  Type : varchar(100) */ "',
						'" . addslashes($this->get_value('mandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . intval($this->get_value('nonmandatory', 'trackback_visible')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'trackback_moderation')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'options')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'lastcomment')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'lastblogtextid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'lastcommenter')) . /* Default :  Type : varchar(100) */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingnum')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingtotal')) . /* Default :  Type : int(10) unsigned */ "',
						'" . floatval($this->get_value('nonmandatory', 'rating')) . /* Default : 0 Type : float unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'pending')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'categories')) . /* Default :  Type : mediumtext) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'taglist')) . /* Default :  Type : mediumtext */ "',
						'" . intval($this->get_value('mandatory', 'postedby_userid')) . /* Default :  Type : int */ "',
						'" . addslashes($this->get_value('nonmandatory', 'postedby_username')) . /* Default :  Type : varchar(100) */ "',
						'" . intval($this->get_value('mandatory', 'importblogid')) . /* Default : 0 Type : int(10) unsigned */ "'
					)
				");

				if ($Db_object->affected_rows())
				{
					$insert_id = $Db_object->insert_id();

					$Db_object->query("UPDATE {$tableprefix}blog_text SET blogid={$insert_id} WHERE blogtextid=" . intval($this->get_value('mandatory', 'firstblogtextid')));

					return $insert_id;
				}
				else
				{
					return false;
				}
			}

			// Postgres Database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Imports the current objects values as a blog_custom_block and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_custom_block(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT customblockid FROM {$tableprefix}blog_custom_block WHERE importcustomblockid=" . intval(trim($this->get_value('mandatory', 'importcustomblockid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_custom_block
					(
						userid, title, pagetext, dateline, allowsmilie, type, location, displayorder, importcustomblockid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'pagetext')) . /* Default :  Type : varchar(255) */ "',
						'" . intval($this->get_value('nonmandatory', 'dateline')) . /* Default :  Type : varchar(255) */ "',
						'" . intval($this->get_value('nonmandatory', 'allowsmilie')) . /* Default :  Type : int(10) unsigned */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'type'), array('block', 'page'), 'block') . /* Default : visible Type : enum('moderation','visible') */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'location'), array('none', 'side', 'top'), 'none') . /* Default : visible Type : enum('moderation','visible') */ "',
						'" . intval($this->get_value('mandatory', 'displayorder')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importcustomblockid')) . /* Default : 0 Type : int(10) unsigned */ "'
					)
				");

				if ($Db_object->affected_rows())
				{
					return $Db_object->insert_id();
				}
				else
				{
					return false;
				}
			}

			// Postgres Database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function clear_imported_blog_custom_blocks(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_custom_block WHERE importcustomblockid  <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_custom_block AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_custom_block auto_increment=0");

				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function clear_imported_blog_group_memberships(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("DELETE FROM {$tableprefix}blog_groupmembership WHERE importbloggroupmembershipid <> 0");
				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Imports the current objects values as a blog_groupmembership
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_group_membership(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{

				}

				$Db_object->query("
					REPLACE INTO {$tableprefix}blog_groupmembership
					(
						bloguserid, userid, permissions, state, dateline
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'bloguserid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'permissions')) . /* Default :  Type : int(10) unsigned */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('active', 'pending', 'ignored'), 'pending') . /* Default : visible Type : enum('moderation','visible') */ "',
						'" . intval($this->get_value('nonmandatory', 'dateline')) . /* Default :  Type : int(10) unsigned */ "'
					)
				");

				if ($Db_object->affected_rows())
				{
					return true;
				}
				else
				{
					return false;
				}
			}

			// Postgres Database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}
}

/*======================================================================*/
?>