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
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2007-06-22 19:03:23 -0700 (Fri, 22 Jun 2007) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

class ImpExDatabaseBlog extends ImpExDatabaseCore
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

	var $_target_system = 'blog';

	var $_import_blog_ids = array(
		'0' 	=> array('blog'					=>  'importblogid'),
		'1'		=> array('blog_attachment'		=>  'importblogattachmentid'),
		'2' 	=> array('blog_category'		=>  'importblogcategoryid'),
		'3' 	=> array('blog_moderator'		=>  'importblogmoderatorid'),
		'4' 	=> array('blog_rate'			=>  'importblograteid'),
		'5' 	=> array('blog_subscribeentry'	=>  'importblogsubscribeentryid'),
		'6' 	=> array('blog_subscribeuser'	=>  'importblogsubscribeuserid'),
		'7' 	=> array('blog_text'			=>  'importblogtextid'),
		'8' 	=> array('blog_trackback'		=>  'importblogtrackbackid'),
		'9'		=> array('blog_user'			=>  'importbloguserid'),
		'10'	=> array('usergroup'			=>  'importusergroupid'),
		'11' 	=> array('user'					=>  'importuserid'),
		'12'	=> array('usernote'				=>  'importusernoteid'),
		'13' 	=> array('customavatar'			=>  'importcustomavatarid'),
		'14' 	=> array('customprofilepic'		=>  'importcustomprofilepicid')
	);

	/**
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExDatabase()
	{
	}

	/**
	* Clears the currently imported users and blog users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_blog_users(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$users = $Db_object->query("SELECT bloguserid FROM " . $tableprefix  . "blog_user WHERE importbloguserid <> 0");

				if ($Db_object->num_rows($users))
				{
					$removeid = array();
					while ($user = $Db_object->fetch_array($users))
					{
						$removeid[] = $user['bloguserid'];
					}
					$Db_object->free_result($users);

					if ($removeid)
					{
						$Db_object->query("DELETE FROM " . $tableprefix  . "blog_user WHERE bloguserid IN('" . implode("', '", $removeid) . "')");
						$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_user AUTO_INCREMENT=0");
						$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_user auto_increment=0");
					}
				}

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
	* Clears the currently imported blog and blog text
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_blogs(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("
					SELECT firstblogtextid
					FROM {$tableprefix}blog
					WHERE importblogid  <> 0
				");

				if ($Db_object->num_rows($blogs))
				{
					$removeid = array('0');
					while ($blog = $Db_object->fetch_array($blogs))
					{
						$removeid[] = $blog['firstblogtextid'];
					}
					$Db_object->free_result($blogs);

					$ids = implode(',', $removeid);

					// blog_text
					$Db_object->query("DELETE FROM " . $tableprefix  . "blog_text WHERE blogtextid IN(" . $ids . ")");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text AUTO_INCREMENT=0");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text auto_increment=0");
					// blog category user
					$Db_object->query("DELETE FROM " . $tableprefix  . "blog_categoryuser WHERE blogid IN(" . $ids . ")");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_categoryuser AUTO_INCREMENT=0");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_categoryuser auto_increment=0");
				}

				$blogs = $Db_object->query("SELECT blogid FROM " . $tableprefix  . "blog WHERE importblogid  <> 0");

				if ($Db_object->num_rows($blogs))
				{
					$removeid = array('0');
					while ($blog = $Db_object->fetch_array($blogs))
					{
						$removeid[] = $blog['blogid'];
					}
					$Db_object->free_result($blogs);

					$ids = implode(',', $removeid);

					// blog
					$Db_object->query("DELETE FROM " . $tableprefix  . "blog WHERE blogid IN(" . $ids . ")");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog AUTO_INCREMENT=0");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog auto_increment=0");
				}


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

	function clear_imported_blog_category(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$varnames = array();
				$blogs = $Db_object->query("
					SELECT blogcategoryid
					FROM {$tableprefix}blog_category
					WHERE importblogcategoryid  <> 0
				");
				while ($blog = $Db_object->fetch_array($blogs))
				{
					$varnames[] = 'category' . $blog['blogcategoryid'] . '_title';
					$varnames[] = 'category' . $blog['blogcategoryid'] . '_desc';
				}

				if ($varnames)
				{
					$Db_object->query("
						DELETE FROM {$tableprefix}phrase
						WHERE
							fieldname = 'vbblogcat'
								AND
							product = 'vbblog'
								AND
							varname IN('" . implode("', '", $varnames) . "')
					");
				}
				// rebuild language now ..
				
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_category WHERE importblogcategoryid  <> 0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_category AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_category auto_increment=0");
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

	function clear_imported_blog_category_users(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("DELETE FROM {$tableprefix}blog_categoryuser WHERE importblogcategoryid  <> 0");
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

	function clear_imported_blog_moderators(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_moderator WHERE importblogmoderatorid  <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_moderator AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_moderator auto_increment=0");

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

	function clear_imported_blog_rates(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_rate WHERE importblograteid  <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_rate AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_rate auto_increment=0");

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


	function clear_imported_blog_comments(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogtexts = $Db_object->query("
					SELECT bt.blogtextid
					FROM {$tableprefix}blog_text AS bt
					LEFT JOIN {$tableprefix}blog AS b ON (bt.blogid = b.blogid)
					WHERE
						importblogtextid  <> 0
							AND
						b.firstblogtextid <> bt.blogtextid
				");

				if ($Db_object->num_rows($blogtexts))
				{
					$removeid = array('0');
					while ($blog = $Db_object->fetch_array($blogtexts))
					{
						$removeid[] = $blog['blogtextid'];
					}
					$Db_object->free_result($blogs);

					$ids = implode(',', $removeid);

					// blog_text
					$Db_object->query("DELETE FROM " . $tableprefix  . "blog_text WHERE blogtextid IN(" . $ids . ")");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text AUTO_INCREMENT=0");
					$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text auto_increment=0");

				}

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

	function clear_imported_blog_trackbacks(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_trackback WHERE importblogtrackbackid  <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_trackback AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_trackback auto_increment=0");

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

	function clear_imported_blog_text(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$blogs = $Db_object->query("DELETE FROM {$tableprefix}blog_text WHERE importblogtextid  <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "blog_text auto_increment=0");

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

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_user
					(
						bloguserid, importbloguserid, title, description, options,
						viewoption, comments, lastblog, lastblogid,
						lastblogtitle, lastcomment, lastcommenter, lastblogtextid,
						entries, allowsmilie, subscribeown, subscribeothers,
						moderation, deleted, draft, options_everyone,
						options_buddy, options_ignore, ratingnum, ratingtotal,
						rating, pending, uncatentries
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'bloguserid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importbloguserid')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . /* Default :  Type : mediumtext */ "',
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
						'" . intval($this->get_value('nonmandatory', 'allowsmilie')) . /* Default :  Type : smallint(5) unsigned */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'subscribeown'), array('none','usercp','email'), 'none') . /* Default : none Type : enum('none','usercp','email') */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'subscribeothers'), array('none','usercp','email'), 'none') . /* Default : none Type : enum('none','usercp','email') */ "',
						'" . intval($this->get_value('nonmandatory', 'moderation')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'deleted')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'draft')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_everyone')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_buddy')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'options_ignore')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingnum')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'ratingtotal')) . /* Default :  Type : int(10) unsigned */ "',
						'" . floatval($this->get_value('nonmandatory', 'rating')) . /* Default :  Type : float unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'pending')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'uncatentries')) . /* Default : 0 Type : int(10) unsigned */ "'
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
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogattachmentid FROM {$tableprefix}blog_attachment WHERE importblogattachmentidid=" . intval(trim($this->get_value('mandatory', 'importblogattachmentidid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_attachment
					(
						blogid, userid, filename, filedata,
						filesize, filehash, posthash, visible,
						counter, dateline, thumbnail, thumbnail_dateline,
						thumbnail_filesize, extension, importblogattachmentid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'filename')) . /* Default :  Type : varchar(100) */ "',
						'" . addslashes($this->get_value('mandatory', 'filedata')) . /* Default :  Type : mediumblob */ "',
						'" . intval($this->get_value('nonmandatory', 'filesize')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'filehash')) . /* Default :  Type : varchar(32) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'posthash')) . /* Default :  Type : varchar(32) */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'visible'), array('moderation','visible'), 'visible') . /* Default : visible Type : enum('moderation','visible') */ "',
						'" . intval($this->get_value('nonmandatory', 'counter')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'thumbnail')) . /* Default :  Type : mediumblob */ "',
						'" . intval($this->get_value('nonmandatory', 'thumbnail_dateline')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'thumbnail_filesize')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'extension')) . /* Default :  Type : varchar(20) */ "',
						'" . intval($this->get_value('mandatory', 'importblogattachmentid')) . /* Default : 0 Type : int(10) unsigned */ "'
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
						pending, importblogid
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
						'" . $this->enum_check($this->get_value('mandatory', 'state'), array('moderation','draft','visible','deleted'), 'visible') . /* Default : visible Type : enum('moderation','draft','visible','deleted') */ "',
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
						'" . intval($this->get_value('mandatory', 'importblogid')) . /* Default : 0 Type : int(10) unsigned */ "'
					)
				");

				if ($Db_object->affected_rows())
				{
					$insert_id = $Db_object->insert_id();

					$Db_object->query("UPDATE {$tableprefix}blog_text SET blogid={$insert_id} WHERE blogtextid=" . intval($this->get_value('mandatory', 'firstblogtextid')));

					//Insert the category id
					$userid = intval($this->get_value('mandatory', 'userid'));
					$blogcategoryid = intval($this->get_value('nonmandatory', 'blogcategoryid'));

					$Db_object->query("INSERT INTO {$tableprefix}blog_categoryuser (blogid, userid, blogcategoryid) VALUES ({$insert_id}, {$userid}, {$blogcategoryid})");

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
						importblogtextid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'dateline')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'pagetext')) . /* Default :  Type : mediumtext */ "',
						'" . addslashes($this->get_value('mandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'state'), array('moderation','visible','deleted'), 'visible') . /* Default : visible Type : enum('moderation','visible','deleted') */ "',
						'" . intval($this->get_value('nonmandatory', 'allowsmilie')) . /* Default : 0 Type : smallint(5) unsigned */ "',
						'" . addslashes($this->get_value('nonmandatory', 'username')) . /* Default :  Type : varchar(100) */ "',
						'" . intval(sprintf('%u', ip2long($this->get_value('nonmandatory', 'ipaddress')))) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'reportthreadid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'bloguserid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importblogtextid')) . /* Default :  Type : bigint(20) */ "'
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
	* Imports the current objects values as a blog_category and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_category(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogcategoryid FROM {$tableprefix}blog_category WHERE importblogcategoryid=" . intval(trim($this->get_value('mandatory', 'importblogcategoryid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_category
					(
						userid, title, description, childlist,
						parentlist, parentid, displayorder, entrycount,
						importblogcategoryid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'childlist')) . /* Default :  Type : mediumtext */ "',
						'" . addslashes($this->get_value('nonmandatory', 'parentlist')) . /* Default :  Type : mediumtext */ "',
						'" . intval($this->get_value('nonmandatory', 'parentid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'displayorder')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'entrycount')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importblogcategoryid')) . /* Default :  Type : int(10) unsigned */ "'
					)
				");

				if ($Db_object->affected_rows())
				{
					$blogcategoryid = $Db_object->insert_id();
					$varnametitle = 'category' . $blogcategoryid . '_title';
					$varnamedesc = 'category' . $blogcategoryid . '_desc';

					if (intval($this->get_value('mandatory', 'userid')) == 0)
					{
						$Db_object->query("
							REPLACE INTO {$tableprefix}phrase
							(
								languageid,
								varname,
								fieldname,
								text,
								product,
								username,
								dateline,
								version
							)
							VALUES
							(
								0,
								'$varnametitle',
								'vbblogcat',
								'" . addslashes($this->get_value('mandatory', 'title')) . "',
								'vbblog',
								'impex',
								" . time() . ",
								''
							),
							(
								0,
								'$varnamedesc',
								'vbblogcat',
								'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
								'vbblog',
								'impex',
								" . time() . ",
								''
							)
						");
					}

					return $blogcategoryid;
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
	* Imports the current objects values as a blog_categoryuser
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_category_user(&$Db_object, &$databasetype, &$tableprefix)
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
					REPLACE INTO {$tableprefix}blog_categoryuser
					(
						blogcategoryid, blogid, userid, importblogcategoryid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogcategoryid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importblogcategoryid')) . /* Default :  Type : int(10) unsigned */ "'
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

/**
	* Imports the current objects values as a blog_rate and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_rate(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blograteid FROM {$tableprefix}blog_rate WHERE importblograteid=" . intval(trim($this->get_value('mandatory', 'importblograteid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_rate
					(
						blogid, userid, vote, ipaddress, importblograteid

					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'vote')) . /* Default : 0 Type : smallint(6) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'ipaddress')) . /* Default :  Type : char(15) */ "',
						'" . intval($this->get_value('mandatory', 'importblograteid')) . /* Default : 0 Type : smallint(6) */ "'
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
	* Imports the current objects values as a blog_moderator and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_moderator(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogmoderatorid FROM {$tableprefix}blog_moderator WHERE importblogmoderatorid=" . intval(trim($this->get_value('mandatory', 'importblogmoderatorid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_moderator
					(
						userid, permissions, type, importblogmoderatorid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'permissions')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'type'), array('normal','super'), 'normal') . /* Default : normal Type : enum('super','normal') */ "',
						'" . intval($this->get_value('mandatory', 'importblogmoderatorid')) . /* Default : 0 Type : int(10) unsigned */ "'
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
	* Imports the current objects values as a blog_trackback and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/

	function import_blog_trackback(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Check the dupe
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
				{
					$there = $Db_object->query_first("SELECT blogtrackbackid FROM {$tableprefix}blog_trackback WHERE importblogtrackbackid=" . intval(trim($this->get_value('mandatory', 'importblogtrackbackid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}blog_trackback
					(
						blogid, title, snippet, url,
						state, userid, dateline, importblogtrackbackid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'blogid')) . /* Default : 0 Type : int(10) unsigned */ "',
						'" . addslashes($this->get_value('mandatory', 'title')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('nonmandatory', 'snippet')) . /* Default :  Type : varchar(255) */ "',
						'" . addslashes($this->get_value('mandatory', 'url')) . /* Default :  Type : varchar(255) */ "',
						'" . $this->enum_check($this->get_value('mandatory', 'state'), array('moderation','visible'), 'visible') . /* Default : visible Type : enum('moderation','visible') */ "',
						'" . intval($this->get_value('mandatory', 'userid')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('nonmandatory', 'dateline')) . /* Default :  Type : int(10) unsigned */ "',
						'" . intval($this->get_value('mandatory', 'importblogtrackbackid')) . /* Default : 0 Type : int(10) unsigned */ "'
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
	* Returns an array of the category ids key'ed to the import category id's
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		0|1				Wether or not to intval the import forum id
	*
	* @return	array	mixed			Data array[impforumid] = forumid
	*/
	function get_blog_category_ids($Db_object, $databasetype, $tableprefix, $pad = 0)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$categories = $Db_object->query("SELECT blogcategoryid, importblogcategoryid FROM " . $tableprefix . "blog_category WHERE importblogcategoryid > 0");
				$categoryid = array();
				while ($category = $Db_object->fetch_array($categories))
				{
					if ($pad)
					{
						$impcategoryid = intval($category['importblogcategoryid']);
						$categoryid["$impcategoryid"] = $category['blogcategoryid'];
					}
					else
					{
						$categoryid["$category[importblogcategoryid]"] = $category['blogcategoryid'];
					}
				}
				$Db_object->free_result($categories);

				return $categoryid;
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
}

/*======================================================================*/
?>