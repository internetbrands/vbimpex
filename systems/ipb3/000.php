<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* ipb3 API module
*
* @package			ImpEx.ipb3
* @version			$Revision: 2010 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2008-06-13 16:56:15 -0700 (Fri, 13 Jun 2008) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class ipb3_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '3.0.x - 3.1.4';
	var $_tier = '1';
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Invision Board 3';
	var $_homepage 	= 'http://www.invisionboard.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'admin_login_logs', 'admin_logs', 'admin_permission_rows', 'announcements', 'api_log', 'api_users', 'attachments',
		'attachments_type', 'badwords', 'banfilters', 'bbcode_mediatag', 'bouncy_license', 'brc_downloads', 'brc_schedule', 'bulk_mail',
		'cache_store', 'cal_calendars', 'cal_events', 'captcha', 'conf_settings', 'conf_settings_titles', 'content_cache_posts',
		'content_cache_sigs', 'converge_local', 'conv_apps', 'conv_link', 'core_applications', 'core_hooks', 'core_hooks_files',
		'core_item_markers', 'core_item_markers_storage', 'core_sys_conf_settings', 'core_sys_cp_sessions', 'core_sys_lang', 'core_sys_lang_words',
		'core_sys_login', 'core_sys_module', 'core_sys_settings_titles', 'core_uagents', 'core_uagent_groups', 'custom_bbcode', 'dnames_change',
		'downloads_categories', 'downloads_ccontent', 'downloads_cfields', 'downloads_comments', 'downloads_downloads',
		'downloads_favorites', 'downloads_filebackup', 'downloads_files', 'downloads_filestorage', 'downloads_fileviews', 'downloads_ip2ext',
		'downloads_mime', 'downloads_mimemask', 'downloads_mods', 'downloads_sessions', 'downloads_upgrade_history', 'email_logs',
		'emoticons', 'error_logs', 'faq', 'forums', 'forum_perms', 'forum_tracker', 'gallery_albums', 'gallery_bandwidth',
		'gallery_categories', 'gallery_comments', 'gallery_ecardlog', 'gallery_favorites', 'gallery_form_fields', 'gallery_images',
		'gallery_media_types', 'gallery_ratings', 'gallery_subscriptions', 'gallery_upgrade_history', 'groups', 'ignored_users',
		'index_offsets', 'installed_mods', 'links', 'links_cats', 'links_comments', 'links_ratings', 'login_methods',
		'mail_error_logs', 'mail_queue', 'members', 'members_partial', 'message_posts', 'message_topics', 'message_topic_user_map',
		'moderators', 'moderator_logs', 'mod_queued_items', 'openid_temp', 'permission_index', 'pfields_content', 'pfields_data',
		'pfields_groups', 'polls', 'posts', 'profile_comments', 'profile_friends', 'profile_friends_flood', 'profile_portal', 'profile_portal_views',
		'profile_ratings', 'question_and_answer', 'rc_classes', 'rc_comments', 'rc_modpref', 'rc_reports', 'rc_reports_index', 'rc_status',
		'rc_status_sev', 'reg_antispam', 'reputation_cache', 'reputation_index', 'reputation_levels', 'rss_export', 'rss_import', 'rss_imported',
		'search_results', 'sessions', 'shoutbox_mods', 'shoutbox_shouts', 'skin_cache', 'skin_collections', 'skin_css',
		'skin_replacements', 'skin_templates', 'skin_templates_cache', 'skin_url_mapping', 'spider_logs', 'subscriptions', 'subscription_currency',
		'subscription_extra', 'subscription_logs', 'subscription_methods', 'subscription_trans', 'tags_index', 'task_logs', 'task_manager',
		'template_sandr', 'titles', 'topics', 'topic_mmod', 'topic_ratings', 'topic_views', 'tracker', 'upgrade_history', 'upgrade_sessions',
		'validating', 'voters', 'warn_logs'
	);

	function ipb3_000()
	{
	}

	function ipb3_html($text)
	{
		return html_entity_decode($text, ENT_QUOTES);
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
	function get_ipb3_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'member_id'	=> 'mandatory',
			'name'		=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "members", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$user_list = $Db_object->query("SELECT member_id, name FROM {$tableprefix}members ORDER BY member_id LIMIT {$start}, {$per_page}");

			while ($user = $Db_object->fetch_array($user_list))
			{
				$return_array["$user[member_id]"] = $user['name'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
	}
	/**
	* Returns the attachment_id => attachment array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ipb3_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'attach_id' 	=> 'mandatory',
			'attach_file'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "attachments", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}attachments ORDER BY attach_id LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[attach_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
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
	function get_ipb3_forum_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'id' 		=> 'mandatory',
			'name'		=> 'mandatory',
			'position'	=> 'mandatory',
			'parent_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "forums", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE parent_id > 0 ORDER BY id LIMIT {$start_at}, {$per_page}");

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


	function get_ipb3_categories_details(&$Db_object, &$databasetype, &$tableprefix)
	{
		$return_array = array();

		// Check Mandatory Fields.
		$req_fields = array(
			'name' 		=> 'mandatory',
			'position'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "forums", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}forums WHERE parent_id = -1");

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
	* Returns the moderator_id => moderator array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ipb3_moderator_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check Mandatory Fields.
		$req_fields = array(
			'mid' 		=> 'mandatory',
			'forum_id'	=> 'mandatory',
			'member_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "moderators", $req_fields))
		{
			return $return_array;
		}

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("
				SELECT *
				FROM {$tableprefix}moderators
				WHERE member_id <> -1
				ORDER BY mid
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$forumids = explode(",", $detail['forum_id']);
				foreach ($forumids AS $forumid)
				{
					if ($_forumid = intval($forumid))
					{
						$id = $detail['mid'] . '-' . $_forumid;
						$detail['forum_id'] = $_forumid;
						$return_array["$id"] = $detail;
					}
				}
			}
		}
		else
		{
			return false;
		}

		return array(
			'count' => $Db_object->num_rows($details_list),
			'mods'  => $return_array,
		);
	}

	/**
	* Returns the pm_id => pm array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ipb3_pm_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'mt_id' 			=> 'mandatory',
			'mt_title' 			=> 'mandatory',
			'mt_starter_id' 	=> 'mandatory',
			'mt_to_member_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "message_topics", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			// This gets the pm, the pm_text is stored else where just like another database.

			// IPB 3.0.something changed the pm schema
			$Db_object->reporterror = false;
			$check = $Db_object->query_first("SELECT mt_is_deleted FROM {$tableprefix}message_topics LIMIT 1");
			$Db_object->reporterror = true;

			if ($check)
			{
				$details_list = $Db_object->query("
					SELECT mt.*
					FROM {$tableprefix}message_topics AS mt
					WHERE
						mt.mt_is_deleted = 0
							AND
						mt.mt_is_draft = 0
					ORDER BY mt.mt_id
					LIMIT {$start_at}, {$per_page}
				");
				$count = $Db_object->num_rows($details_list);
				// Now we need to build new pms out of the conversation
				while ($detail = $Db_object->fetch_array($details_list))
				{
					$pms = $Db_object->query("
						SELECT mp.*, map.*
						FROM {$tableprefix}message_posts AS mp
						INNER JOIN {$tableprefix}message_topic_user_map AS map ON (mp.msg_topic_id = map.map_topic_id)
						WHERE
							mp.msg_topic_id = $detail[mt_id]
								AND
							mp.msg_author_id != map.map_user_id
								AND
							mp.msg_author_id != 0
					");
					while ($pm = $Db_object->fetch_array($pms))
					{
						$id = $pm['msg_topic_id'] . '-' . $pm['msg_id'] . '-' . $pm['map_user_id'];
						$detail['mt_date'] = $pm['msg_date'];
						$detail['msg_id'] = $pm['msg_id'];
						$detail['mt_starter_id'] = $pm['msg_author_id'];
						$detail['mt_to_member_id'] = $pm['map_user_id'];
						$detail['mt_vid_folder'] = 'in';
						//echo "Msg: {$pm['msg_id']}, From: {$pm['msg_author_id']}, To: ${pm['map_user_id']} {$detail['mt_vid_folder']}<br />";
						$return_array["$id"] = $detail;
					}
				}
			}
			else
			{
				$details_list = $Db_object->query("SELECT * FROM {$tableprefix}message_topics ORDER BY mt_id LIMIT {$start_at}, {$per_page}");
				$count = $Db_object->num_rows($details_list);
				while ($detail = $Db_object->fetch_array($details_list))
				{
					$return_array["$detail[mt_id]"] = $detail;
				}
			}
		}
		else
		{
			return false;
		}

		return array(
			'count' => $count,
			'pms'   => $return_array,
		);
	}

	function get_ipb3_pm_text(&$Db_object, &$databasetype, &$tableprefix, $mt_msg_id)
	{
		$return_array = array();

		// Check Mandatory Fields.
		$req_fields = array(
			'msg_id' 	=> 'mandatory',
			'msg_post'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "message_posts", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$detail = $Db_object->query_first("SELECT * FROM {$tableprefix}message_posts WHERE msg_id={$mt_msg_id}");

			return $detail['msg_post'];
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the poll_id => poll array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ipb3_poll_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'pid' 			=> 'mandatory',
			'tid'			=> 'mandatory',
			'choices'		=> 'mandatory',
			'poll_question'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "polls", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			// voter data is in voters.
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}polls ORDER BY pid LIMIT {$start_at}, {$per_page}");

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
	function get_ipb3_post_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();
die('use the > and limit');
		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'pid' 	=> 'mandatory',
			'author_id'	=> 'mandatory',
			'topic_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "posts", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}posts ORDER BY pid LIMIT {$start_at}, {$per_page}");

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
	function get_ipb3_smilie_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'id' 	=> 'mandatory',
			'typed'	=> 'mandatory',
			'image'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "emoticons", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}emoticons ORDER BY id LIMIT {$start_at}, {$per_page}");

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
	function get_ipb3_thread_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'tid' 		=> 'mandatory',
			'forum_id'	=> 'mandatory',
			'title'		=> 'mandatory'

		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "topics", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}topics ORDER BY tid LIMIT {$start_at}, {$per_page}");

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
	function get_ipb3_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'member_id'			=> 'mandatory',
			'member_group_id'	=> 'mandatory',
			'email'				=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "members", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{

			$pfarray = array();
			// Normal table
			$pfields = $Db_object->query("
				SELECT
					pf_id, pf_title
				FROM {$tableprefix}pfields_data
			");
			while ($pfield = $Db_object->fetch_array($pfields))
			{
				switch ($pfield['pf_title'])
				{
					case 'AIM':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS aim_name';
						break;
					case 'MSN':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS msnname';
						break;
					case 'Website URL':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS website';
						break;
					case 'ICQ':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS icq_number';
						break;
					case 'Location':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS location';
						break;
					case 'Interests':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS interests';
						break;
					case 'Yahoo':
						$pfarray[] = 'pf.field_' . $pfield['pf_id'] . ' AS yahoo';
						break;
				}
			}

			$details_list = $Db_object->query("
				SELECT
					m.*,
					pp.notes, pp.signature, pp.avatar_location, pp.avatar_size, pp.avatar_type
					" . (!empty($pfarray) ? ',' . implode(',', $pfarray) : '') . "
				FROM {$tableprefix}members AS m
				LEFT JOIN {$tableprefix}profile_portal AS pp ON (m.member_id = pp.pp_member_id)
				" . (!empty($pfarray) ? "LEFT JOIN {$tableprefix}pfields_content AS pf ON (m.member_id = pf.member_id)" : '') . "
				ORDER BY m.member_id
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				// These items are from ipb2 schema
				#$return_array["$detail[id]"]['photo_type'] 		= $extra_array['photo_type'];
				#$return_array["$detail[id]"]['photo_location'] 	= $extra_array['photo_location'];
				#$return_array["$detail[id]"]['photo_dimensions'] = $extra_array['photo_dimensions'];
				#$return_array["$detail[id]"]['p_customblock'] 	= $extra_array['p_customblock'];
				#$return_array["$detail[id]"]['p_customheight'] 	= $extra_array['p_customheight'];

				$return_array["$detail[member_id]"] 					= $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	/**
	* Returns the usergroup_id => usergroup array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_ipb3_usergroup_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'g_id' 	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "groups", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}groups ORDER BY g_id LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[g_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function get_ipb3_vote_voters(&$Db_object, &$databasetype, &$tableprefix, $thread_id)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		// Check Mandatory Fields.
		$req_fields = array(
			'vid' 	=> 'mandatory',
			'tid'	=> 'mandatory',
			'member_id'	=> 'mandatory'
		);

		if(!$this->check_table($Db_object, $databasetype, $tableprefix, "voters", $req_fields))
		{
			return $return_array;
		}

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}voters WHERE tid={$thread_id}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array["$detail[vid]"] = $detail;
			}

		}
		else
		{
			return false;
		}
		return $return_array;
	}

	function unconvert_size($size="", $text="")
	{
		switch($size)
		{
		   case '21':
			  $size=4;
			  break;
		   case '14':
			  $size=3;
			  break;
		   case '8':
			  $size=1;
			  break;
		   default:
			  $size=2;
			  break;
		}
		return '[SIZE='.$size.']'.$text.'[/SIZE]';
	}

	/**
	* HTML parser
	*
	* @param	string	mixed			The string to be parsed
	*
	* @return	string
	*/
	function ipb_html($post)
	{

		//return $post;

		$post = preg_replace('#<u>([^"]*)</u>#siU', '[u]\\1[/u]', $post);
		$post = preg_replace('#<b>([^"]*)</b>#siU', '[b]\\1[/b]', $post);
		$post = preg_replace('#<i>([^"]*)</i>#siU', '[i]\\1[/i]', $post);
		$post = preg_replace('#<span style=\'font-family:([^"]*)\'>([^"]*)</span>#siU', '[font=\\1]\\2[/font]', $post);
		$post = preg_replace('#<span style=\'color:([^"]*)\'>([^"]*)</span>#siU', '[color=\\1]\\2[/color]', $post);
		$post = preg_replace('#<a href=\'(http://|https://|ftp://|news://)([^"]*)\' target=\'_blank\'>([^"]*)</a>#siU', '[url=\\1\\2]\\3[/url]', $post);

		$post = preg_replace('#<img src=\'([^"]*)\' border=\'0\' alt=\'user posted image\'(\s/)?>#siU', '[img]\\1[/img]', $post);
		$post = str_replace("<img src='","[img]",$post);
		$post = preg_replace('#<a href=\'mailto:([^"]*)\'>([^"]*)</a>#siU', '[email=\\1]\\2[/email]', $post);

		$post = preg_replace('#<ul>#siU', '[list]', $post);
		$post = preg_replace('#<ol type=\'[1|i]\'>#siU', '[list=1]', $post);
		$post = preg_replace('#<ol type=\'a\'>#siU', '[list=a]', $post);
		$post = preg_replace('#<li>([^"]*)</li>#siU', "[*]\\1\n", $post);
		$post = preg_replace('#</ul>#siU', '[/list]', $post);
		$post = preg_replace('#</ol>#siU', '[/list]', $post);

		$post = preg_replace('#<!--emo&([^"]*)-->([^"]*)<!--endemo-->#siU', '\\1', $post);
		$post = preg_replace('#<!--c1-->([^"]*)<!--ec1-->#siU', '[code]', $post);
		$post = preg_replace('#<!--c2-->([^"]*)<!--ec2-->#siU', '[/code]', $post);
		$post = preg_replace('#<!--QuoteBegin-->([^"]*)<!--QuoteEBegin-->#siU', '[quote][b]', $post);
		$post = preg_replace('#<!--QuoteBegin-{1,2}([^"]*)\+-->([^"]*)<!--QuoteEBegin-->#si', '[quote][i]Originally posted by \\1[/i]<br />[b]', $post);
		$post = preg_replace('#<!--QuoteBegin-{1,2}([^"]*)\+([^"]*)-->([^"]*)<!--QuoteEBegin-->#si', '[quote][i]Originally posted by \\1[/i]@\\2<br />[b]', $post);
		$post = preg_replace('#<!--QuoteEnd-->([^"]*)<!--QuoteEEnd-->#siU', '[/b][/quote]', $post);
		$post = preg_replace('#<span style=\'font-size:(.+?)pt;line-height:100%\'>(.+?)</span>#e', '\$this->unconvert_size("\\1", "\\2")', $post);
		$post = preg_replace('#<!--EDIT\|([^"]*)\|([^"]*)-->#siU', 'Last edited by \\1 at \\2', $post);

		$post = str_replace("<br />","\n",$post);
		$post = str_replace("<br>","\n",$post);
		$post = str_replace("&amp;","&",$post);
		$post = str_replace("&lt;","<",$post);
		$post = str_replace("&gt;",">",$post);
		$post = str_replace("&quot;","\"",$post);
		$post = str_replace("&#039;","'",$post);
		$post = str_replace("&#033;","!",$post);
		$post = str_replace("&#124;","|",$post);

		$post = preg_replace('#<a href=\'([^"]*)\' target=\'_blank\'><img src=\'([^"]*)\' alt=\'([^"]*)\' width=\'([^"]*)\' height=\'([^"]*)\' class=\'([^"]*)\' /></a>#siU', '[img]\\2[/img]', $post);

		$post = preg_replace('#<!--aimg-->#siU', '', $post);
		$post = preg_replace('#<!--/aimg-->#siU', '', $post);
		$post = preg_replace('#--Resize_Images_Alt_Text--#siU', '', $post);
		$post = preg_replace('#<!--Resize_Images_Hint_Text-->#siU', '', $post);

		$post = preg_replace('#<!--QuoteBegin-{1,2}([^"]*)\+-->([^"]*)<!--QuoteEBegin-->#siU', '[quote]Originally posted by \\1<br />[b]', $post);
		$post = preg_replace('#<!--QuoteBegin-{1,2}([^"]*)\+([^"]*)-->([^"]*)<!--QuoteEBegin-->#siU', '[quote]Originally posted by \\1@\\2<br />[b]', $post);

		$post = preg_replace('#\[quote(.*)\]#siU', '[quote]', $post);

	   return trim(stripslashes($post));
	}

} // Class end
# Autogenerated on : August 20, 2004, 2:31 pm
# By ImpEx-generator 1.0.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2010 $
|| ####################################################################
\*======================================================================*/
?>