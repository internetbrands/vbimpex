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

	var $_customernumber = '[#]customernumber[#]';

	/**
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExDatabase()
	{
	}


	function import_attachment(&$Db_object, &$databasetype, &$tableprefix, $import_post_id = TRUE)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if($import_post_id)
				{
					if($this->get_value('nonmandatory', 'postid'))
					{
						// Get the real post id
						$post_id = $Db_object->query_first("
							SELECT postid, userid
							FROM " . $tableprefix . "post
							WHERE
							importpostid = " . $this->get_value('nonmandatory', 'postid'));

						if(empty($post_id['postid']))
						{
							// Its not there to be attached through.
							return false;
						}
					}
					else
					{
						// No post id !!!
						return false;
					}
				}
				else
				{
					$sql ="
					SELECT userid, postid
					FROM " . $tableprefix . "post
					WHERE postid = " . $this->get_value('nonmandatory', 'postid');

					$post_id = $Db_object->query_first($sql);
				}

				// Update the post attach
				$Db_object->query("UPDATE " . $tableprefix . "post SET attach = attach+1 WHERE postid = " . $post_id['postid']);

				// Ok, so now where is it going ......
				$attachpath =  $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachpath');
				$attachfile = $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachfile');

				$Db_object->query("
					INSERT INTO " . $tableprefix . "attachment
					(
						importattachmentid, filename, filedata,
						dateline, visible, counter, filesize,
						postid, filehash, userid
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importattachmentid') . "',
						'" . addslashes($this->get_value('mandatory', 'filename')) . "',
						'',
						'" . $this->get_value('nonmandatory', 'dateline')  . "',
						'" . $this->get_value('nonmandatory', 'visible')  . "',
						'" . $this->get_value('nonmandatory', 'counter')  . "',
						'',
						'" . $post_id['postid']  . "',
						'" . $this->get_value('nonmandatory', 'filehash')  . "',
						'" . $post_id['userid'] . "'
					)
				");

				$attachment_id = $Db_object->insert_id();

				switch (intval($attachfile))
				{
					case '0':	// Straight into the dB
					{
						$Db_object->query("
							UPDATE " . $tableprefix . "attachment
							SET
							filedata = '" . addslashes($this->get_value('mandatory', 'filedata')) . "',
							filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
							WHERE attachmentid = {$attachment_id}
						");

						return $attachment_id;
					}

					case '1':	// file system OLD naming schema
					{
						$full_path = $this->fetch_attachment_path($post_id['userid'], $attachpath, false, $attachment_id);

						if($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
						{
							if ($fp = fopen($full_path, 'wb'))
							{
								fwrite($fp, $this->get_value('mandatory', 'filedata'));
								fclose($fp);
								$filesize = filesize($full_path);

								if($filesize)
								{
									$Db_object->query("
										UPDATE " . $tableprefix . "attachment
										SET
										filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
										WHERE attachmentid = {$attachment_id}
									");

									return $attachment_id;
								}
							}
						}
						return false;
					}

					case '2':	// file system NEW naming schema
					{
						$full_path = $this->fetch_attachment_path($post_id['userid'], $attachpath, true, $attachment_id);

						if($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
						{
							if ($fp = fopen($full_path, 'wb'))
							{
								fwrite($fp, $this->get_value('mandatory', 'filedata'));
								fclose($fp);
								$filesize = filesize($full_path);

								if($filesize)
								{
									$Db_object->query("
										UPDATE " . $tableprefix . "attachment
										SET
										filesize = " . $this->get_value('nonmandatory', 'filesize')  . "
										WHERE attachmentid = {$attachment_id}
									");

									return $attachment_id;
								}
							}
						}
						return false;
					}
					default :
					{
						// Shouldn't ever get here
						return false;
					}
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
	* Imports the current object as a vB3 avatar
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_vb3_avatar(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					INSERT INTO " . $tableprefix . "avatar
					(
						importavatarid, title, minimumposts, avatarpath, imagecategoryid, displayorder
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importavatarid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
						'" . $this->get_value('nonmandatory', 'minimumposts') . "',
						'" . $this->get_value('nonmandatory', 'avatarpath') . "',
						'" . $this->get_value('nonmandatory', 'imagecategoryid') . "',
						'" . $this->get_value('nonmandatory', 'displayorder') . "'
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
	* Imports the current object as a vB3 avatar
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_vb3_customavatar(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{


				$sql ="
					REPLACE INTO " . $tableprefix . "customavatar
					(
						importcustomavatarid, userid, avatardata, dateline, filename, visible, filesize
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importcustomavatarid') . "',
						'" . $this->get_value('nonmandatory', 'userid') . "',
						'" . $this->get_value('nonmandatory', 'avatardata') . "',
						'" . $this->get_value('nonmandatory', 'dateline') . "',
						'" . addslashes($this->get_value('nonmandatory', 'filename')) . "',
						'" . $this->get_value('nonmandatory', 'visible') . "',
						'" . $this->get_value('nonmandatory', 'filesize') . "'
					)
				";

				if ($Db_object->query($sql))
				{
					return true;
				}
				else
				{
					return false;
				}
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
	* Imports the an arrary as a ban list in various formats $key => $value, $int => $data
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_ban_list(&$Db_object, &$databasetype, &$tableprefix, $list, $type)
	{
		if (empty($list))
		{
			return true;
		}

		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$sql = '';
				$internal_list = '';

				switch ($type)
				{
					case 'emaillist':
					{
						foreach($list as $key => $data)
						{
							$internal_list .= $data . " ";
						}
						// For datastore opposed to setting table if it ever gets used
						// $sql = "UPDATE " . $tableprefix . "settings SET value=CONCAT(value,' " . $list . "') WHERE varname='banemail'";

						$sql = "UPDATE " . $tableprefix . "datastore SET data = CONCAT(data, '$internal_list') WHERE title = 'banemail'";
					}
					break;

					case 'iplist':
					{
						foreach($list as $key => $ip)
						{
							$internal_list .= $ip . " ";
						}
						// For datastore opposed to setting table
						$sql = "UPDATE " . $tableprefix . "setting SET value = CONCAT(value, ' $internal_list') WHERE varname = 'banip'";
					}
					break;

					case 'namebansfull':
					{
						$user_id_list = array();
						foreach ($list as $key => $vb_user_name)
						{
							$banned_userid = $Db_object->query_first("SELECT userid FROM " . $tableprefix . "user WHERE username = '$vb_user_name'");

							$user_id_list[] = $banned_userid['userid'];
						}

						return $this->import_ban_list($Db_object, $databasetype, $tableprefix, $user_id_list, 'userid');
					}
					break;

					case 'userid':
					{
						$banned_group_id = $Db_object->query_first("SELECT usergroupid FROM " . $tableprefix . "usergroup WHERE title= 'Banned Users'");

						if($banned_group_id['usergroupid'] != null)
						{
							foreach($list as $key => $banned_user_id)
							{
								$Db_object->query("UPDATE " . $tableprefix . "user SET membergroupids = CONCAT(membergroupids, ' $banned_group_id[usergroupid]') WHERE userid = '$banned_user_id'");
							}
						}
						return true;
					}
					break;

					default:
					{
						return false;
					}
				}

				$Db_object->query($sql);
				return ($Db_object->affected_rows() > 0);
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
	* Imports the current objects values as a User
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_user(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// Auto email associate
				if ($this->_auto_email_associate)
				{
					// Do a search for the email address to find the user to match this imported one to :
					$email_match = $Db_object->query_first("SELECT userid FROM " . $tableprefix . "user WHERE email='". $this->get_value('mandatory', 'email') . "'");


					if ($email_match)
					{

						if($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
						{
							// We matched the email address and associated propperly
							$result['automerge'] = true;
							return $result;
						}
						else
						{
							// Hmmm found the email but didn't associate !!
						}
					}
					else
					{
						// There is no email to match with, so return nothing and let the user import normally.
					}
				}

				$newpassword = '';
				$salt =	$this->fetch_user_salt();

				if ($this->_password_md5_already)
				{
					 $newpassword = md5($this->get_value('nonmandatory', 'password') . $salt);
				}
				else
				{
					$newpassword = md5(md5($this->get_value('nonmandatory', 'password')) . $salt);
				}

				// Link the admins
				if(strtolower($this->get_value('mandatory', 'username')) == 'admin')
				{
					$this->set_value('mandatory', 'username', 'imported_admin');
				}

				// If there is a dupe username pre_pend "imported_"
				$double_name = $Db_object->query("SELECT username FROM " . $tableprefix . "user WHERE username='". addslashes($this->get_value('mandatory', 'username')) . "'");

				if($Db_object->num_rows($double_name))
				{
					$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
				}


				$sql = "
					INSERT INTO	" . $tableprefix . "user
					(
						username, email, usergroupid,
						importuserid, password, salt,
						passworddate, options, homepage,
						posts, joindate, icq,
						daysprune, aim, membergroupids,
						displaygroupid, styleid, parentemail,
						yahoo, showvbcode, usertitle,
						customtitle, lastvisit, lastactivity,
						lastpost, reputation, reputationlevelid,
						timezoneoffset, pmpopup, avatarid,
						avatarrevision, birthday, birthday_search, maxposts,
						startofweek, ipaddress, referrerid,
						languageid, msn, emailstamp,
						threadedmode, pmtotal, pmunread,
						autosubscribe
					)
					VALUES
					(
						'" . addslashes($this->get_value('mandatory', 'username')) . "',
						'" . addslashes($this->get_value('mandatory', 'email')) . "',
						'" . $this->get_value('mandatory', 'usergroupid') . "',
						'" . $this->get_value('mandatory', 'importuserid') . "',
						'" . $newpassword . "',
						'" . addslashes($salt) . "',
						NOW(),
						'" . $this->get_value('nonmandatory', 'options') . "',
						'" . addslashes($this->get_value('nonmandatory', 'homepage')) . "',
						'" . $this->get_value('nonmandatory', 'posts') . "',
						'" . $this->get_value('nonmandatory', 'joindate') . "',
						'" . addslashes($this->get_value('nonmandatory', 'icq')) . "',
						'" . $this->get_value('nonmandatory', 'daysprune') . "',
						'" . addslashes($this->get_value('nonmandatory', 'aim')) . "',
						'" . $this->get_value('nonmandatory', 'membergroupids') . "',
						'" . $this->get_value('nonmandatory', 'displaygroupid') . "',
						'" . $this->get_value('nonmandatory', 'styleid') . "',
						'" . $this->get_value('nonmandatory', 'parentemail') . "',
						'" . addslashes($this->get_value('nonmandatory', 'yahoo')) . "',
						'" . $this->get_value('nonmandatory', 'showvbcode') . "',
						'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
						" . intval($this->get_value('nonmandatory', 'customtitle')) . ",
						'" . $this->get_value('nonmandatory', 'lastvisit') . "',
						'" . $this->get_value('nonmandatory', 'lastactivity') . "',
						'" . $this->get_value('nonmandatory', 'lastpost') . "',
						'" . $this->get_value('nonmandatory', 'reputation') . "',
						'" . $this->get_value('nonmandatory', 'reputationlevelid') . "',
						'" . $this->get_value('nonmandatory', 'timezoneoffset') . "',
						'" . $this->get_value('nonmandatory', 'pmpopup') . "',
						'" . $this->get_value('nonmandatory', 'avatarid') . "',
						'" . $this->get_value('nonmandatory', 'avatarrevision') . "',
						'" . $this->get_value('nonmandatory', 'birthday') . "',
						'" . $this->get_value('nonmandatory', 'birthday_search') . "',
						'" . $this->get_value('nonmandatory', 'maxposts') . "',
						'" . $this->get_value('nonmandatory', 'startofweek') . "',
						'" . $this->get_value('nonmandatory', 'ipaddress') . "',
						'" . $this->get_value('nonmandatory', 'referrerid') . "',
						'" . $this->get_value('nonmandatory', 'languageid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'msn')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'emailstamp')) . "',
						'" . $this->get_value('nonmandatory', 'threadedmode') . "',
						'" . $this->get_value('nonmandatory', 'pmtotal') . "',
						'" . $this->get_value('nonmandatory', 'pmunread') . "',
						'" . $this->get_value('nonmandatory', 'autosubscribe') . "'
					)
				";

				$userdone = $Db_object->query($sql);
				$userid = $Db_object->insert_id();

				if ($userdone)
				{		
					$exists = $Db_object->query_first("SELECT userid FROM " . $tableprefix . "usertextfield WHERE userid = $userid");

					if (!$exists)
					{
						if (!$Db_object->query("INSERT INTO " . $tableprefix . "usertextfield (userid) VALUES ($userid)"))
						{
							$this->_failedon = "usertextfield fill";
							return false;
						}

						if (!$Db_object->query("INSERT INTO " . $tableprefix . "userfield (userid) VALUES ($userid)"))
						{
							$this->_failedon = "userfield fill";
							return false;
						}
					}

					if ($this->_has_custom_types)
					{
						foreach ($this->get_custom_values() as $key => $value)
						{
							if (!$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid))
							{
								$this->_failedon = "import_user_field_value - $key - $value - $userid";
								return false;
							}
						}
					}

					if ($this->get_value('nonmandatory', 'avatar') != NULL)
					{
						$this->import_avatar($Db_object, $databasetype, $tableprefix,$userid,$this->get_value('nonmandatory', 'avatar'));
					}

					if ($this->_has_default_values)
					{
						foreach ($this->get_default_values() as  $key => $value)
						{
							if ($key != 'signature')
							{
								if (!$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid))
								{
									$this->_failedon = "import_user_field_value - $key - $value - $userid";
									return false;
								}
							}
						}
					}

					if (array_key_exists('signature',$this->_default_values))
					{
						if (!$Db_object->query("UPDATE " . $tableprefix . "usertextfield SET signature='" . $this->_default_values['signature'] . "' WHERE userid='" . $userid ."'"))
						{
							$this->_failedon = "usertextfield SET signature";
							return false;
						}
					}
				}
				else
				{
					return false;
				}

				return $userid;
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

	// Overridden to maintain salt and password details
	function import_vb3_user(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// TODO: Still need to check and see if all the current usersnames being imported are unique
				if(strtolower($this->get_value('mandatory', 'username')) == 'admin')
				{
					$this->set_value('mandatory', 'username', 'admin_old');
				}

				// Auto email associate
				if ($this->_auto_email_associate)
				{
					// Do a search for the email address to find the user to match this imported one to :
					$email_match = $Db_object->query_first("SELECT userid FROM " . $tableprefix . "user WHERE email='". $this->get_value('mandatory', 'email') . "'");


					if ($email_match)
					{
						if($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
						{
							// We matched the email address and associated propperly
							$result['automerge'] = true;
							return $result;
						}
						else
						{
							// Hmmm found the email but didn't associate !!
						}
					}
					else
					{
						// There is no email to match with, so return nothing and let the user import normally.
					}
				}

				// If there is a dupe username pre_pend "imported_"
				$double_name = $Db_object->query("SELECT username FROM " . $tableprefix . "user WHERE username='". addslashes($this->get_value('mandatory', 'username')) . "'");

				if($Db_object->num_rows($double_name))
				{
					$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
				}

				$sql = "
					INSERT INTO	" . $tableprefix . "user
					(
						username, email, usergroupid,
						importuserid, password, salt,
						passworddate, options, homepage,
						posts, joindate, icq,
						daysprune, aim, membergroupids,
						displaygroupid, styleid, parentemail,
						yahoo, showvbcode, usertitle,
						customtitle, lastvisit, lastactivity,
						lastpost, reputation, reputationlevelid,
						timezoneoffset, pmpopup, avatarid,
						avatarrevision, birthday, birthday_search, maxposts,
						startofweek, ipaddress, referrerid,
						languageid, msn, emailstamp,
						threadedmode, pmtotal, pmunread,
						autosubscribe
					)
					VALUES
					(
						'" . addslashes($this->get_value('mandatory', 'username')) . "',
						'" . addslashes($this->get_value('mandatory', 'email')) . "',
						'" . $this->get_value('mandatory', 'usergroupid') . "',
						'" . $this->get_value('mandatory', 'importuserid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'password')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'salt')) . "',
						'" . $this->get_value('nonmandatory', 'passworddate') . "',
						'" . $this->get_value('nonmandatory', 'options') . "',
						'" . addslashes($this->get_value('nonmandatory', 'homepage')) . "',
						'" . $this->get_value('nonmandatory', 'posts') . "',
						'" . $this->get_value('nonmandatory', 'joindate') . "',
						'" . addslashes($this->get_value('nonmandatory', 'icq')) . "',
						'" . $this->get_value('nonmandatory', 'daysprune') . "',
						'" . addslashes($this->get_value('nonmandatory', 'aim')) . "',
						'" . $this->get_value('nonmandatory', 'membergroupids') . "',
						'" . $this->get_value('nonmandatory', 'displaygroupid') . "',
						'" . $this->get_value('nonmandatory', 'styleid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'parentemail')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'yahoo')) . "',
						'" . $this->get_value('nonmandatory', 'showvbcode') . "',
						'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'customtitle')) . "',
						'" . $this->get_value('nonmandatory', 'lastvisit') . "',
						'" . $this->get_value('nonmandatory', 'lastactivity') . "',
						'" . $this->get_value('nonmandatory', 'lastpost') . "',
						'" . $this->get_value('nonmandatory', 'reputation') . "',
						'" . $this->get_value('nonmandatory', 'reputationlevelid') . "',
						'" . $this->get_value('nonmandatory', 'timezoneoffset') . "',
						'" . $this->get_value('nonmandatory', 'pmpopup') . "',
						'" . $this->get_value('nonmandatory', 'avatarid') . "',
						'" . $this->get_value('nonmandatory', 'avatarrevision') . "',
						'" . $this->get_value('nonmandatory', 'birthday') . "',
						'" . $this->get_value('nonmandatory', 'birthday_search') . "',
						'" . $this->get_value('nonmandatory', 'maxposts') . "',
						'" . $this->get_value('nonmandatory', 'startofweek') . "',
						'" . $this->get_value('nonmandatory', 'ipaddress') . "',
						'" . $this->get_value('nonmandatory', 'referrerid') . "',
						'" . $this->get_value('nonmandatory', 'languageid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'msn')) . "',
						'" . $this->get_value('nonmandatory', 'emailstamp') . "',
						'" . $this->get_value('nonmandatory', 'threadedmode') . "',
						'" . $this->get_value('nonmandatory', 'pmtotal') . "',
						'" . $this->get_value('nonmandatory', 'pmunread') . "',
						'" . $this->get_value('nonmandatory', 'autosubscribe') . "'
					)
				";

				$userdone = $Db_object->query($sql);
				$userid = $Db_object->insert_id();

				if ($userdone)
				{
					$exists = $Db_object->query_first("SELECT userid FROM " . $tableprefix . "usertextfield WHERE userid = $userid");

					if (!$exists)
					{
						if (!$Db_object->query("INSERT INTO " . $tableprefix . "usertextfield (userid) VALUES ($userid)"))
						{
							$this->_failedon = "usertextfield fill";
							return false;
						}

						if (!$Db_object->query("INSERT INTO " . $tableprefix . "userfield (userid) VALUES ($userid)"))
						{
							$this->_failedon = "userfield fill";
							return false;
						}
					}

					if ($this->_has_default_values)
					{
						foreach ($this->get_default_values() as  $key => $value)
						{
							if ($key != 'signature')
							{
								if (!$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid))
								{
									$this->_failedon = "import_user_field_value - $key - $value - $userid";
									return false;
								}
							}
						}
					}

					if (array_key_exists('signature',$this->_default_values))
					{
						if (!$Db_object->query("UPDATE " . $tableprefix . "usertextfield SET signature='" . addslashes($this->_default_values['signature']) . "' WHERE userid='" . $userid ."'"))
						{
							$this->_failedon = "usertextfield SET signature";
							return false;
						}
					}
				}
				else
				{
					return false;
				}

				return $userid;
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
	* Imports the users avatar from a local file or URL.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	int		The userid
	* @param	string	int		The location of the avatar file
	*
	* @return	insert_id
	*/
	function import_avatar(&$Db_object, &$databasetype, &$tableprefix, $userid, $file)
	{
		if ($filenum = @fopen($file, 'r'))
		{
			$contents = $this->vb_file_get_contents($file);

			$size = getimagesize($file);

			if($size)
			{
				$width 	= $size[0];
				$height = $size[1];
			}
			else
			{
				$width 	= '0';
				$height = '0';
			}

			if(!$file_sz = @filesize($file))
			{
				$file_sz = 0;
			}

			$urlbits = parse_url($file);
			$pathbits = pathinfo($urlbits['path']);

			$Db_object->query("
				INSERT INTO " . $tableprefix . "customavatar
					(userid, avatardata, dateline, filename, filesize)
				VALUES
				(
					$userid,
					'" . addslashes($contents) . "',
					NOW(),
					'" . addslashes($pathbits['basename'])."',
					". $file_sz . "
				)
			");

			#, width, height
			# {$width},
			# {$height}

			if ($Db_object->affected_rows())
			{
				return $Db_object->insert_id();
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}


	/**
	* Imports a usergroup
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	insert_id
	*/
	function import_user_group(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			case 'mysql':
			{
				$cols = $Db_object->query("describe {$tableprefix}usergroup");
				$there = false;

				while ($col = $Db_object->fetch_array($cols))
				{
					if($col['Field'] == 'pmforwardmax')
					{
						$there = true;
					}
				}

				if(!$there)
				{
					$Db_object->query("ALTER TABLE `{$tableprefix}usergroup` ADD `pmforwardmax` SMALLINT( 5 ) UNSIGNED DEFAULT '5' NOT NULL");
				}

				$Db_object->query("
					INSERT INTO " . $tableprefix . "usergroup
					(
						importusergroupid, title, description,
						usertitle, passwordexpires, passwordhistory,
						pmquota, pmsendmax, pmforwardmax,
						opentag, closetag, canoverride,
						ispublicgroup, forumpermissions, pmpermissions,
						calendarpermissions, wolpermissions, adminpermissions,
						genericpermissions, genericoptions, attachlimit,
						avatarmaxwidth, avatarmaxheight, avatarmaxsize,
						profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importusergroupid') . "',
						'" . addslashes($this->get_value('nonmandatory','title')) . "',
						'" . addslashes($this->get_value('nonmandatory','description')) . "',
						'" . addslashes($this->get_value('nonmandatory','usertitle')) . "',
						'" . $this->get_value('nonmandatory','passwordexpires') . "',
						'" . $this->get_value('nonmandatory','passwordhistory') . "',
						'" . $this->get_value('nonmandatory','pmquota') . "',
						'" . $this->get_value('nonmandatory','pmsendmax') . "',
						'" . $this->get_value('nonmandatory','pmforwardmax') . "',
						'" . addslashes($this->get_value('nonmandatory','opentag')) . "',
						'" . addslashes($this->get_value('nonmandatory','closetag')) . "',
						'" . $this->get_value('nonmandatory','canoverride') . "',
						'" . $this->get_value('nonmandatory','ispublicgroup') . "',
						'" . $this->get_value('nonmandatory','forumpermissions') . "',
						'" . $this->get_value('nonmandatory','pmpermissions') . "',
						'" . $this->get_value('nonmandatory','calendarpermissions') . "',
						'" . $this->get_value('nonmandatory','wolpermissions') . "',
						'" . $this->get_value('nonmandatory','adminpermissions') . "',
						'" . $this->get_value('nonmandatory','genericpermissions') . "',
						'" . $this->get_value('nonmandatory','genericoptions') . "',
						'" . $this->get_value('nonmandatory','attachlimit') . "',
						'" . $this->get_value('nonmandatory','avatarmaxwidth') . "',
						'" . $this->get_value('nonmandatory','avatarmaxheight') . "',
						'" . $this->get_value('nonmandatory','avatarmaxsize') . "',
						'" . $this->get_value('nonmandatory','profilepicmaxwidth') . "',
						'" . $this->get_value('nonmandatory','profilepicmaxheight') . "',
						'" . $this->get_value('nonmandatory','profilepicmaxsize') . "'
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
	* Imports the current objects values as a Forum
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_category(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if ($this->get_value('mandatory', 'options') == '!##NULL##!')
				{
					$this->set_value('mandatory', 'options', $this->_default_cat_permissions);
				}

				$result = $Db_object->query("
					INSERT INTO " . $tableprefix . "forum
					(
						styleid, title, description,
						options, daysprune, displayorder,
						parentid, importforumid, importcategoryid
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'styleid') . "',
						'" . addslashes($this->get_value('mandatory', 'title')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						" . $this->get_value('mandatory', 'options') . ",
						'30',
						'" . $this->get_value('mandatory', 'displayorder') . "',
						'-1',
						'" . $this->get_value('mandatory', 'importforumid') . "',
						'" . $this->get_value('mandatory', 'importcategoryid') . "'
					)
				");
				$categoryid = $Db_object->insert_id($result);

				if ($result)
				{
					$Db_object->query("UPDATE {$tableprefix}forum SET parentlist = '$categoryid,-1' WHERE forumid = '$categoryid'");
					if ($Db_object->affected_rows())
					{
						return $categoryid;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
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
	* Imports the current objects values as a Forum
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_forum(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{

				$result = $Db_object->query("
					INSERT INTO " . $tableprefix . "forum
					(
						styleid, title, options,
						displayorder, parentid, importforumid,
						importcategoryid, description, replycount,
						lastpost, lastposter, lastthread,
						lastthreadid, lasticonid, threadcount,
						daysprune, newpostemail, newthreademail,
						parentlist, password, link, childlist
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'styleid') . "',
						'" . addslashes($this->get_value('mandatory', 'title')) . "',
						" . $this->get_value('mandatory', 'options') . ",
						'" . $this->get_value('mandatory', 'displayorder') . "',
						'" . $this->get_value('mandatory', 'parentid') . "',
						'" . $this->get_value('mandatory', 'importforumid') . "',
						'" . $this->get_value('mandatory', 'importcategoryid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						'" . $this->get_value('nonmandatory', 'replycount') . "',
						'" . $this->get_value('nonmandatory', 'lastpost') . "',
						'" . addslashes($this->get_value('nonmandatory', 'lastposter')) . "',
						'" . $this->get_value('nonmandatory', 'lastthread') . "',
						'" . $this->get_value('nonmandatory', 'lastthreadid') . "',
						'" . $this->get_value('nonmandatory', 'lasticonid') . "',
						'" . $this->get_value('nonmandatory', 'threadcount') . "',
						'" . $this->get_value('nonmandatory', 'daysprune') . "',
						'" . $this->get_value('nonmandatory', 'newpostemail') . "',
						'" . $this->get_value('nonmandatory', 'newthreademail') . "',
						'" . $this->get_value('nonmandatory', 'parentlist') . "',
						'" . $this->get_value('nonmandatory', 'password') . "',
						'" . $this->get_value('nonmandatory', 'link') . "',
						'" . $this->get_value('nonmandatory', 'childlist') . "'
					)
				");
				$forumid = $Db_object->insert_id($result);

				if ($result)
				{
					$Db_object->query("UPDATE {$tableprefix}forum SET parentlist='$forumid,-1' WHERE forumid='$forumid'");
					if ($Db_object->affected_rows())
					{
						return $forumid;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
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

	function import_vb2_forum(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$result = $Db_object->query("
					INSERT INTO " . $tableprefix . "forum
					(
						styleid, title, options,
						displayorder, parentid, importforumid,
						description, replycount,
						lastpost, lastposter, lastthread,
						lastthreadid, lasticonid, threadcount,
						daysprune, newpostemail, newthreademail,
						parentlist, password, link, childlist
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'styleid') . "',
						'" . addslashes($this->get_value('mandatory', 'title')) . "',
						'" . $this->get_value('nonmandatory', 'options') . "',
						'" . $this->get_value('mandatory', 'displayorder') . "',
						'" . $this->get_value('mandatory', 'parentid') . "',
						'" . $this->get_value('mandatory', 'importforumid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						'" . $this->get_value('nonmandatory', 'replycount') . "',
						'" . $this->get_value('nonmandatory', 'lastpost') . "',
						'" . addslashes($this->get_value('nonmandatory', 'lastposter')) . "',
						'" . $this->get_value('nonmandatory', 'lastthread') . "',
						'" . $this->get_value('nonmandatory', 'lastthreadid') . "',
						'" . $this->get_value('nonmandatory', 'lasticonid') . "',
						'" . $this->get_value('nonmandatory', 'threadcount') . "',
						'" . $this->get_value('nonmandatory', 'daysprune') . "',
						'" . $this->get_value('nonmandatory', 'newpostemail') . "',
						'" . $this->get_value('nonmandatory', 'newthreademail') . "',
						'" . $this->get_value('nonmandatory', 'parentlist') . "',
						'" . $this->get_value('nonmandatory', 'password') . "',
						'" . $this->get_value('nonmandatory', 'link') . "',
						'" . $this->get_value('nonmandatory', 'childlist') . "'
					)
				");
				$forumid = $Db_object->insert_id($result);

				return $forumid;
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
	* Imports the current objects values as a Custom profile pic
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_custom_profile_pic(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$sql ="
					INSERT INTO
					" . $tableprefix . "customprofilepic
					(
					importcustomprofilepicid, userid, profilepicdata, dateline, filename, visible, filesize
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importcustomprofilepicid') . "',
						'" . $this->get_value('nonmandatory', 'userid') . "',
						'" . $this->get_value('nonmandatory', 'profilepicdata') . "',
						'" . $this->get_value('nonmandatory', 'dateline') . "',
						'" . $this->get_value('nonmandatory', 'filename') . "',
						'" . $this->get_value('nonmandatory', 'visible') . "',
						'" . $this->get_value('nonmandatory', 'filesize') . "'
					)
				";

				if ($Db_object->query($sql))
				{
					return true;
				}
				else
				{
					return false;
				}
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
	* Imports a customer userfield value
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The key i.e. 'surname'
	* @param	string	mixed			The value i.e. 'Hutchings'
	*
	* @return	boolean
	*/
	function import_user_field_value(&$Db_object, &$databasetype, &$tableprefix, $title, $value, $userid)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$fieldid = $Db_object->query_first("SELECT profilefieldid FROM ". $tableprefix . "profilefield WHERE title = '$title'");

				// TODO: This will break with a 0 on field id, need to handel it a lot better and have a return
				if($fieldid['profilefieldid'])
				{
					$Db_object->query("UPDATE ". $tableprefix . "userfield SET field" . $fieldid['profilefieldid'] ." = '" . addslashes($value) . "' WHERE userid = '$userid'");
				}

				return true;
				// TODO: Fix this, it dosn't work. affected_rows() Isn't picking up an UPDATE
				if ($Db_object->affected_rows())
				{
					return true;
				}
				else
				{
					return false;
				}

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
	* Imports a rank, has to be used incombination with import usergroup to make sense get its usergroupid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	false/int		The tablerow inc id
	*/
	function import_usergroup(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$cols = $Db_object->query("describe {$tableprefix}usergroup");
				$there = false;

				while ($col = $Db_object->fetch_array($cols))
				{
					if($col['Field'] == 'pmforwardmax')
					{
						$there = true;
					}
				}

				if(!$there)
				{
					$Db_object->query("ALTER TABLE `{$tableprefix}usergroup` ADD `pmforwardmax` SMALLINT( 5 ) UNSIGNED DEFAULT '5' NOT NULL");
				}

				$Db_object->query("
					INSERT INTO " . $tableprefix ."usergroup
					(
						importusergroupid, title, description,
						usertitle, passwordexpires, passwordhistory,
						pmquota, pmsendmax, pmforwardmax,
						opentag, closetag, canoverride,
						ispublicgroup, forumpermissions, pmpermissions,
						calendarpermissions, wolpermissions, adminpermissions,
						genericpermissions, genericoptions, attachlimit,
						avatarmaxwidth, avatarmaxheight, avatarmaxsize,
						profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importusergroupid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
						'" . $this->get_value('nonmandatory', 'passwordexpires') . "',
						'" . $this->get_value('nonmandatory', 'passwordhistory') . "',
						'" . $this->get_value('nonmandatory', 'pmquota') . "',
						'" . $this->get_value('nonmandatory', 'pmsendmax') . "',
						'" . $this->get_value('nonmandatory', 'pmforwardmax') . "',
						'" . $this->get_value('nonmandatory', 'opentag') . "',
						'" . $this->get_value('nonmandatory', 'closetag') . "',
						'" . $this->get_value('nonmandatory', 'canoverride') . "',
						'" . $this->get_value('nonmandatory', 'ispublicgroup') . "',
						'" . $this->get_value('nonmandatory', 'forumpermissions') . "',
						'" . $this->get_value('nonmandatory', 'pmpermissions') . "',
						'" . $this->get_value('nonmandatory', 'calendarpermissions') . "',
						'" . $this->get_value('nonmandatory', 'wolpermissions') . "',
						'" . $this->get_value('nonmandatory', 'adminpermissions') . "',
						'" . $this->get_value('nonmandatory', 'genericpermissions') . "',
						'" . $this->get_value('nonmandatory', 'genericoptions') . "',
						'" . $this->get_value('nonmandatory', 'attachlimit') . "',
						'" . $this->get_value('nonmandatory', 'avatarmaxwidth') . "',
						'" . $this->get_value('nonmandatory', 'avatarmaxheight') . "',
						'" . $this->get_value('nonmandatory', 'avatarmaxsize') . "',
						'" . $this->get_value('nonmandatory', 'profilepicmaxwidth') . "',
						'" . $this->get_value('nonmandatory', 'profilepicmaxheight') . "',
						'" . $this->get_value('nonmandatory', 'profilepicmaxsize') . "'
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

	function zuul($dana) { return "There is no 3.0.9 only 4.0.0"; }



	/**
	* Imports a poll
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The board type that we are importing from
	*
	* @return	boolean
	*/
	function import_poll(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					INSERT INTO " . $tableprefix . "poll
					(
						importpollid, question, dateline,
						options, votes, active,
						numberoptions, timeout, multiple,
						voters, public
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importpollid') . "',
						'" . addslashes($this->get_value('mandatory', 'question')) . "',
						'" . $this->get_value('mandatory', 'dateline') . "',
						'" . addslashes($this->get_value('mandatory', 'options')) . "',
						'" . $this->get_value('mandatory', 'votes') . "',
						'" . $this->get_value('nonmandatory', 'active') . "',
						'" . $this->get_value('nonmandatory', 'numberoptions') . "',
						'" . $this->get_value('nonmandatory', 'timeout') . "',
						'" . $this->get_value('nonmandatory', 'multiple')  . "',
						'" . $this->get_value('nonmandatory', 'voters') . "',
						'" . $this->get_value('nonmandatory', 'public') . "'
					)
				");

				return $Db_object->insert_id();
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
	* Imports the current objects values as a Smilie
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function import_smilie(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$update = $Db_object->query_first("SELECT smilieid FROM " . $tableprefix . "smilie WHERE smilietext = '". addslashes($this->get_value('mandatory', 'smilietext')) . "'");

				if (!$update)
				{
					$sql = "
						INSERT INTO	" . $tableprefix . "smilie
						(
							title, smilietext, smiliepath,
							imagecategoryid, displayorder, importsmilieid
						)
						VALUES
						(
							'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
							'" . addslashes($this->get_value('mandatory', 'smilietext')) . "',
							'" . $this->get_value('nonmandatory', 'smiliepath') . "',
							'" . $this->get_value('nonmandatory', 'imagecategoryid') . "',
							'" . $this->get_value('nonmandatory', 'displayorder') . "',
							'" . $this->get_value('mandatory', 'importsmilieid') . "'
						)
					";
				}
				else
				{
					$sql = "
						UPDATE " . $tableprefix . "smilie SET
						title = '" . addslashes($this->get_value('nonmandatory', 'title')) . "',
						smiliepath = '" . $this->get_value('nonmandatory', 'smiliepath') . "'
						WHERE smilietext = '" . addslashes($this->get_value('mandatory', 'smilietext')) . "'
					";
				}

				$Db_object->query($sql);
				return ($Db_object->affected_rows() > 0);
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
	* Import a poll from one vB3 board to another
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int	mixed				The vb_poll_id
	* @param	int	mixed				The import_poll_id
	*
	* @return	boolean
	*/
	function import_poll_to_vb3_thread(&$Db_object, &$databasetype, &$tableprefix, $vb_poll_id, $import_poll_id)
	{
		switch ($databasetype)
		{
			case 'mysql':
			{
				$sql = "UPDATE " . $tableprefix . "thread
					SET pollid = '$vb_poll_id'
					WHERE pollid = '$import_poll_id'
					";

				$Db_object->query($sql);

				if ($Db_object->affected_rows())
				{
					return true;
				}
				else
				{
					return false;
				}
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
	* Updates parent ids of imported forums where parent id = 0
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	array	mixed			importforumid => forumid
	*
	* @return	array
	*/
	function clean_nested_forums(&$Db_object, &$databasetype, &$tableprefix, $importid)
	{
		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT forumid, importcategoryid FROM " .
			$tableprefix."forum
			WHERE
			parentid = 0
			AND
			importforumid <> 0";


			$do_list = $Db_object->query($sql);

			while ($do = $Db_object->fetch_array($do_list))
			{
				$catid = $do['importcategoryid'];
				$fid = $do['forumid'];
				if ($importid[$catid] AND $fid)
				{
					$sql = "UPDATE " . $tableprefix."forum SET parentid=" . $importid[$catid] . " WHERE forumid =" . $fid;
				}

				$Db_object->query($sql);
			}
		}
		else
		{
			return false;
		}
	}
}
/*======================================================================*/
?>