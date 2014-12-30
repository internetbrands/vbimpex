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
* gossamer_threads
*
* @package 		ImpEx.gossamer_threads
* @version
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name: $
* @date 		$Date: $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class gossamer_threads_000 extends ImpExModule
{
	/**
	* Supported version
	*
	* @var    string
	*/
	var $_version = '1.2.4';
	var $_tested_versions = array('1.2.4');
	var $_tier = '2';
	
	/**
	* Module string
	*
	* Class string
	*
	* @var    array
	*/
	var $_modulestring = 'GossamerThreads';
	var $_homepage 	= 'http://www.gossamer-threads.com/scripts/gforum/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'Category', 'Category_tree', 'CustomDict', 'EditLog', 'EmailRecipient', 'EmailTemplate', 'Expanded', 'Forum', 'ForumBan',
		'ForumGroup', 'ForumModerator', 'ForumSubscriber', 'Grouping', 'Guest', 'MailingIndex', 'Message', 'MessageAttachment',
		'Online', 'Payment', 'PaymentLog', 'Post', 'PostAttachment', 'PostNew', 'PostView', 'Post_tree', 'Remember', 'SentMessage',
		'Session', 'TempAttachment', 'ThreadWatch', 'User', 'UserGroup', 'UserNew'
	);

	function gossamer_threads_000()
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
	function gossamer_threads_html($text)
	{
		// url
		$text = preg_replace('#\[url "(.*)"\]\\1\[/url\]#isU', '[URL]$1[/URL]', $text);
		$text = preg_replace('#\[url "(.*)"\](.*)\[/url\]#isU', '[URL="$1"]$2[/URL]', $text);
		$text = preg_replace('#\[url "http://(.*)"\]\\1\[/url\]#isU', '[URL]http://$1[/URL]', $text);

		// More quoting
		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 1\]In Reply To\[/size\]\[/font\](.*)\[hr\](.*)\[hr\](.*)#isU', '[quote]$4[/quote]$5', $text);
		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 1\]Quote\[/size\]\[/font\](.*)\[hr\](.*)\[(.*)\]\[size 1\](.*)\[/size\]\[(.*)\](.*)\[hr\](.*)#isU', '[quote]$6[/quote]$9', $text);
		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 1\]Code\[/size\]\[/font\](.*)\[hr\](.*)\[(.*)\]\[size 1\](.*)\[/size\]\[(.*)\](.*)\[hr\](.*)#isU', '[code]$6[/code]$9', $text);

		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 2\]In Reply To\[/size\]\[/font\](.*)\[hr\](.*)\[hr\](.*)#isU', '[quote]$4[/quote]$5', $text);
		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 2\]Quote\[/size\]\[/font\](.*)\[hr\](.*)\[(.*)\]\[size 1\](.*)\[/size\]\[(.*)\](.*)\[hr\](.*)#isU', '[quote]$6[/quote]$9', $text);
		$text = preg_replace('#\[hr\](.*)\[font(.*)\]\[size 2\]Code\[/size\]\[/font\](.*)\[hr\](.*)\[(.*)\]\[size 1\](.*)\[/size\]\[(.*)\](.*)\[hr\](.*)#isU', '[code]$6[/code]$9', $text);

		// Link removing
		$text = preg_replace('#\[img\]http://(.*)/gforum.cgi\?do=post_attachment;postatt_id=(.*);\[/img\]#isU', '', $text);

		$text = preg_replace('#\[inline(.*)\]#isU', '', $text);
		$text = preg_replace('#\[\#(.*)\]#isU', '', $text);
		$text = preg_replace('#\[\#(.*)\]#isU', '', $text);
		$text = preg_replace('#\[/\#(.*)\]#isU', '', $text);

		$text = str_replace('[image]' , '[img]', $text);
		$text = str_replace('[/image]' , '[/img]', $text);

		$text = str_replace('[reply]' , '[quote]', $text);
		$text = str_replace('[/reply]' , '[/quote]', $text);
		$text = str_replace('[signature]' , '', $text);
		#$text = htmlspecialchars($text);

		// Lists
		$text = str_replace('[ol]' , '[LIST=1]', $text);
		$text = str_replace('[ul]' , '[LIST]', $text);

		$text = str_replace('[/ul]' , '[/LIST]', $text);
		$text = str_replace('[/ol]' , '[/LIST]', $text);

		$text = str_replace('[li]' , '[*]', $text);
		$text = str_replace('[/li]' , '', $text);


		$text = str_replace('[hr]' , "\n__________________________________________________\n", $text);
		$text = str_replace('[HR]' , "\n__________________________________________________\n", $text);

		// Font
		$text = preg_replace('#\[font=([^:]*):([a-z0-9]+)\](.*)\[/font:\\2\]#siU', '[font=$1]$3[/font]', $text);

		// Text size
		$text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);
		$text = preg_replace('#\[size([0-9]+)\](.*)\[/size]#siUe', "\$this->pixel_size_mapping('\\1', '\\2')", $text);
		$text = preg_replace('#\[size ([0-9]+)\](.*)\[/size]#siUe', "\$this->pixel_size_mapping('\\1', '\\2')", $text);


		$text = str_replace('[pre]', '[QUOTE]', $text);
		$text = str_replace('[/pre]', '[/QUOTE]', $text);

		//colours
		$text = preg_replace('#\[\#(.*)\](.*)\[/\#]#siUe', "[COLOR=\#\\1]\\2[/COLOR]", $text);

		$text = str_replace('[black]', '[COLOR=Black]', $text);
		$text = str_replace('[/black]', '[/COLOR]', $text);
		$text = str_replace('[blue]', '[COLOR=Blue]', $text);
		$text = str_replace('[/blue]', '[/COLOR]', $text);
		$text = str_replace('[green]', '[COLOR=Green]', $text);
		$text = str_replace('[/green]', '[/COLOR]', $text);
		$text = str_replace('[orange]', '[COLOR=Orange]', $text);
		$text = str_replace('[/orange]', '[/COLOR]', $text);
		$text = str_replace('[purple]', '[COLOR=Purple]', $text);
		$text = str_replace('[/purple]', '[/COLOR]', $text);
		$text = str_replace('[red]', '[COLOR=Red]', $text);
		$text = str_replace('[/red]', '[/COLOR]', $text);
		$text = str_replace('[silver]', '[COLOR=Silver]', $text);
		$text = str_replace('[/silver]', '[/COLOR]', $text);
		$text = str_replace('[white]', '[COLOR=White]', $text);
		$text = str_replace('[/white]', '[/COLOR]', $text);
		$text = str_replace('[yellow]', '[COLOR=Yellow]', $text);
		$text = str_replace('[/yellow]', '[/COLOR]', $text);

		$text = preg_replace('#\[font(.*)\]#isU', '', $text);
		$text = str_replace('[/font]', '', $text);

		return $text;
	}

	function pixel_size_mapping($size, $text)
	{
		$text = str_replace('\"', '"', $text);

		if ($size <= 8)
		{
		   $outsize = 1;
		}
		else if ($size <= 10)
		{
		   $outsize = 2;
		}
		else if ($size <= 12)
		{
		   $outsize = 3;
		}
		else if ($size <= 14)
		{
		   $outsize = 4;
		}
		else if ($size <= 16)
		{
		   $outsize = 5;
		}
		else if ($size <= 18)
		{
		   $outsize = 6;
		}
		else
		{
		   $outsize = 7;
		}

		return '[size=' . $outsize . ']' . $text .'[/size]';
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
	function get_gossamer_threads_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT user_id,user_username FROM {$tableprefix}User ORDER BY user_id LIMIT {$start_at}, {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array["$row[user_id]"] = $row['user_username'];
			}

		}

		return $return_array;
	}

	function get_gossamer_user_groupid(&$Db_object, &$databasetype, &$tableprefix, &$source_user_id)
	{
		$usergroupid = 0;

		// Check that there is not a empty value
		if(empty($source_user_id)) { return $usergroupid; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query_first("SELECT group_id_fk FROM {$tableprefix}UserGroup WHERE user_id_fk={$source_user_id}");

			$usergroupid = $dataset['group_id_fk'];
		}

		return $usergroupid;
	}

	function get_gossamer_threads_threads(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}Post WHERE post_root_id=0 AND post_id > {$start_at} ORDER BY post_id LIMIT {$per_page}");

			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data']["$row[post_id]"] = $row;
				$return_array['lastid'] = $row['post_id'];
			}
		}

		$return_array['count'] = count($return_array['data']);

		return $return_array;
	}

	function get_gossamer_threads_mods(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("SELECT * FROM {$tableprefix}ForumModerator ORDER BY  forum_id_fk LIMIT {$start_at}, {$per_page}");

			$i=1;
			while ($row = $Db_object->fetch_array($dataset))
			{
				$return_array['data'][$i++] = $row;
			}
		}

		$return_array['count'] = count($return_array['data']);

		return $return_array;
	}

	function get_gossamer_thread_views(&$Db_object, &$databasetype, &$tableprefix, &$thread_id)
	{
		$views = 0;
		// Check that there is not a empty value
		if(empty($thread_id)) { return $views; }

		if ($databasetype == 'mysql')
		{
			$db_count = $Db_object->query_first("SELECT post_thread_views FROM {$tableprefix}PostView WHERE post_id_fk={$thread_id}");

			$views = $db_count['post_thread_views'];
		}

		return $views;
	}

}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $
|| ####################################################################
\*======================================================================*/
?>
