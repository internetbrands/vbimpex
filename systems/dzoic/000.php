<?php 
if (!defined('IDIR')) { die; }
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
* dzoic 
*
* @package 		ImpEx.dzoic 
*
*/

class dzoic_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '3.5';
	var $_tested_versions = array();
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'dzoic';
	var $_homepage 	= 'http://www.dzoic.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'admin_mail','admin_session','album_photos','albums','associations','banners','billing','blogs','blogs_comments','blogs_posts',
		'blogs_waiting_list', 'bookmarks','bulletins','calendar_events','calendar_notes','campaigns','classifieds','classifieds_waiting_list',
		'club_message_board','club_photos','clubs','clubs_waiting_list','code','delete_streams','ecards','ecards_collections','ecards_mailbox',
		'ecards_office','ecards_recipients','events','events_attendees','events_waiting_list','forums','geo_cities','geo_codes','geo_countries',
		'geo_install','geo_states','hschat_members','hschat_messages','hsim_contacts','hsim_ignores','hsim_messages','hsim_status','ignores',
		'internal_mail','invitations','javascripts','mass_mail','media_votes','members','members_clubs','members_settings','members_topics',
		'mod_session','moderators','music','music_comments','network','news','newsletter','orders','payment_gateway','photo_comments','photo_settings',
		'photo_votes','photos','polls','polls_votes','posts','profile_access','profile_views','profiles','searches','sessiondata',
		'social_bookmarking_engines','steps','streams','tags_cache','testimonials','tips','topics','vchat_sessions','vchat_usage','verification',
		'video_comments','video_votes','videos'	
	);

	function dzoic_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed	The string to parse
	* @param	boolean			Truncate smilies
	*
	* @return	array
	*/
	function dzoic_html($text)
	{
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
	function get_dzoic_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT mem_id, username FROM {$tableprefix}members ORDER BY mem_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[mem_id]"] = $row['username'];
			}
		}
		
		return $return_array; 
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
