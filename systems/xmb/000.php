<?php if (!defined('IDIR')) { die; }
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
* xmb API module
*
* @package			ImpEx.xmb
*
*/
class xmb_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '1.9';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'XMB forum';
	var $_homepage 	= 'http://www.xmbforum.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		 'attachments', 'banned', 'buddys', 'favorites', 'forums', 'logs',
		 'members', 'posts', 'ranks', 'restricted', 'settings', 'smilies',
		 'templates', 'themes', 'threads', 'u2u', 'whosonline', 'words'
	);


	function xmb_000()
	{
	}


	function xmb_html($text)
	{
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&quot;', '"', $text);
		$text = str_replace('&amp;', '&', $text);

		return $text;
	}

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT uid, username
			FROM " . $tableprefix . "members
			ORDER BY uid
			LIMIT " . $start . "," . $per_page;

			$user_list = $Db_object->query($sql);

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[uid]"] = $user['username'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	/**
	* Returns the forum_id => forum array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums
			WHERE type='forum' OR type='sub'
			ORDER BY fid, type
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($detail['displayorder'] <= 0)
				{
					$detail['displayorder'] = 1;
				}
				$return_array["$detail[fid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	function get_xmb_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."forums
			WHERE type='group'
			ORDER BY fid
			";

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				if ($detail['displayorder'] <= 0)
				{
					$detail['displayorder'] = 1;
				}
				$return_array["$detail[fid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}



	/**
	* Returns the pmtext_id => pmtext array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_pmtext_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}u2u WHERE folder='Inbox' ORDER BY u2uid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[u2uid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the post_id => post array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."posts
			ORDER BY pid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[pid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the ranks_id => ranks array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_ranks_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."ranks
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the smilie_id => smilie array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."smilies
			WHERE type='smiley'
			ORDER BY id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the thread_id => thread array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."threads
			ORDER BY tid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[tid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


	/**
	* Returns the user_id => user array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_xmb_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."members
			ORDER BY uid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[uid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}



	function get_xmb_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		if(empty($per_page)) { return $return_array; }

		$req_fields = array(
			'filename' 		=> 'mandatory',
			'attachment'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "attachments", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."attachments
			ORDER BY aid
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;

			$details_list = $Db_object->query($sql);

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[aid]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


} // Class end
# Autogenerated on : June 23, 2004, 8:54 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
