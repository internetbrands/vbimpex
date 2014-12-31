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
* Open Topic, XML importer
*
* @package 		index.OT
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class OT_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '4.0';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* Class string for phpUnit header
	*
	* @var    array
	*/
	var $_modulestring 	= 'Infopop Open Topic';

	var $_homepage = 'http://www.infopop.com/docs/opentopic/';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();


	function OT_000()
	{
	}

	/**
	* HTML parser
	*
	* @param	string	mixed			The string to parse
	* @param	boolean					Truncate smilies
	*
	* @return	array
	*/
	function OT_html($text, $truncate_smilies = false)
	{
		// Quotes
			// With name
		$text = preg_replace('#\[quote:([a-z0-9]+)="(.*)"\](.*)\[/quote:\\1\]#siU', '[quote=$2]$3[/quote]', $text);
			// Without
		$text = preg_replace('#\[quote:([a-z0-9]+)\](.*)\[/quote:\\1\]#siU', '[quote]$2[/quote]', $text);

		// Bold , Underline, Italic
		$text = preg_replace('#\[b:([a-z0-9]+)\](.*)\[/b:\\1\]#siU', '[b]$2[/b]', $text);
		$text = preg_replace('#\[u:([a-z0-9]+)\](.*)\[/u:\\1\]#siU', '[u]$2[/u]', $text);
		$text = preg_replace('#\[i:([a-z0-9]+)\](.*)\[/i:\\1\]#siU', '[i]$2[/i]', $text);

		// Images
		$text = preg_replace('#\[img:([a-z0-9]+)\](.*)\[/img:\\1\]#siU', '[img]$2[/img]', $text);

		// Color
		$text = preg_replace('#\[color=([^:]*):([a-z0-9]+)\](.*)\[/color:\\2\]#siU', '[color=$1]$3[/color]', $text);

		// Text size
		$text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);

		// Smiles
		// Get just truncated phpBB smilies for this one to do the replacments

		if($truncate_smilies)
		{
			$text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
		}

		return $text;
	}

	/**
	* Regex call back
	*
	* @param	string	mixed			The origional size
	* @param	string	mixed			The content text
	*
	* @return	array
	*/
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
	* Returns the user fields array
	*
	* @return	array
	*/
	function init_userfields()
	{
		$userfields = array("USER_OID",						// OTUserid, used to connect back to posts
						  "USERNAME",						// Username
						  "DISPLAY_NAME",					// Display name
						  "REGISTRATION_DATE",				// Date Registered
						  "DISPLAY_EMAIL",					// blank if Email is hidden
						  "DOB",							// Date of Birth
						  "SIGNATURE",						// Signature
						  "HOME_PAGE_URL",					// Homepage
						  "LOCATION",						// Location
						  "ICQ_NUMBER",						// ICQ Number
						  "OCCUPATION",						// Occupation
						  "INTERESTS",						// Interests
						  "BIO",							// Biography
						  "IS_REGISTRATION_VALIDATED",		// Verified User
						  "IS_AGE_RESTRICTED_USER",			// Coppa User
						  "USER_POST_COUNT",				// Post Count
						  "LAST_LOGIN_DATETIME",			// Last Login Time
						  "PARENT_EMAIL", 					// Coppa User's Parent Email Address
						  "IS_EMAIL_VERIFIED",				// Verified user
						  "HAS_OPTED_OUT_OF_EMAIL",			// Receive email from Admin?
						  "EMAIL",							// Email Address
						  "IP_AT_REGISTRATION",
						  "IS_ADMINISTRATOR",
						  "PASSWORD",
						  "PARENT_PASSWORD");

		return $userfields;
	}

	function init_postfields()
	{

		$postfields = array("MESSAGE_BODY",             	// Message Text
						  "DATETIME_POSTED",
						  "SUBJECT",                  		// This is the post subject. Contains the Thread Title if this is the first post in a thread.
						  "MESSAGE_PAGE_VIEW_COUNT",
						  "POSTER_IP",
						  "USERNAME",
						  "AUTHOR_OID",               		// OT user ID of message poster
						  "IS_TOPIC_CLOSED",         		// First message in a thread = "Y"
						  "IS_TOPIC");

		return $postfields;
	}

	function init_forumfields()
	{
		$fields = array ("FORUM",
						"FORUM_OID",
						"SITE_OID",
						"STYLE_OID",
						"FEATURED_FORUM_POLL_OID",
						"IS_MESSAGE_FEEDBACK_ENABLED",
						"IS_SHOWING_MESSAGE_VIEW_COUNTS",
						"IS_FORUM_ENABLED",
						"IS_FORUM_READ_ONLY",
						"IS_UBB_CODE_ALLOWED",
						"IS_UBB_CODE_IMAGES_ALLOWED",
						"IS_TOPICS_ALLOWED",
						"IS_TOPICS_MODERATED",
						"IS_REPLIES_MODERATED",
						"IS_ATTACHMENTS_MODERATED",
						"IS_MODERATION_LIVE",
						"IS_SIGNATURE_ENABLED",
						"IS_ICON_POSTING_ENABLED",
						"IS_POLLING_ENABLED",
						"CONVERT_SMILIE_TO_GRAPHIC",
						"USE_CHRONOLOGICAL_TOPIC_ORDER",
						"ORDER_TOPICS_BY_TOPIC_POST",
						"USE_INLINE_FRAME_ON_REPLY",
						"IS_TOPIC_LEAD_ENABLED",
						"IS_MESSAGE_ATTACHMENT_ALLOWED",
						"IS_AUTO_LINKING_POSTS",
						"IS_IMAGE_SHOWN_WITH_POSTS",
						"IS_ANY_ATTACHMENT_ALLOWED",
						"IS_IMAGE_ATTACHMENT_ALLOWED",
						"IS_ZIP_ATTACHMENT_ALLOWED",
						"IS_TEXT_ATTACHMENT_ALLOWED",
						"ATTACHMENT_BYTE_LIMIT",
						"IS_USER_ABLE_TO_EDIT",
						"IS_USER_ABLE_TO_DELETE",
						"IS_USER_ABLE_TO_CLOSE_TOPICS",
						"MINUTE_LIMIT_ON_CHANGES",
						"ENABLE_T_NOTIF_FOR_AUTHORS",
						"MAX_TOPICS_PER_PAGE",
						"MAX_MESSAGES_PER_PAGE",
						"FORUM_CREATED_BY_USER_OID",
						"SUPER_FORUM_OID",
						"THREADING_ORDER",
						"DATE_FORUM_CREATED",
						"FORUM_NAME",
						"FORUM_DESCRIPTION",
						"FORUM_TYPE",
						"FORUM_INTRO",
						"FORUM_CUSTOM_1",
						"FORUM_CUSTOM_2",
						"FORUM_CUSTOM_3",
						"FORUM_CUSTOM_4",
						"IMG_FORUM_GRAPHIC_URL",
						"IMG_FORUM_GRAPHIC_W",
						"IMG_FORUM_GRAPHIC_H",
						"FORUM_KEY_WORDS",
						"FORUM_CATEGORIES",
						"MODERATOR_1_OID",
						"MODERATOR_2_OID",
						"MODERATOR_3_OID",
						"MODERATOR_4_OID",
						"MODERATOR_1_NAME",
						"MODERATOR_2_NAME",
						"MODERATOR_3_NAME",
						"MODERATOR_4_NAME",
						"MODERATOR_1_NON_OT_USER_ID",
						"MODERATOR_2_NON_OT_USER_ID",
						"MODERATOR_3_NON_OT_USER_ID",
						"MODERATOR_4_NON_OT_USER_ID",
						"FORUM_TOPIC_COUNT",
						"FORUM_POST_COUNT",
						"LAST_FORUM_POST_DATETIME",
						"CATEGORY_NAME");
		return $fields;
	}


	function parse($data, $fields)
	{
		for ($i=0;$i<count($fields);$i++)
		{
			$field = $fields[$i];
			unset($matches);
			preg_match("/<$field>(.*)<\/$field>/s", $data, $matches);
			$temp[$field] = $matches[1];
		}
	return $temp;
	}

	function OThtml2bb($htmlcode)
	{
		// bold and italics: easy peasy
		$htmlcode=str_replace("<b>","[b]",$htmlcode);
		$htmlcode=str_replace("</b>","[/b]",$htmlcode);
		$htmlcode=str_replace("<i>","[i]",$htmlcode);
		$htmlcode=str_replace("</i>","[/i]",$htmlcode);
		$htmlcode=str_replace("<B>","[b]",$htmlcode);
		$htmlcode=str_replace("</B>","[/b]",$htmlcode);
		$htmlcode=str_replace("<I>","[i]",$htmlcode);
		$htmlcode=str_replace("</I>","[/i]",$htmlcode);
		$htmlcode=str_replace("<italic>","[i]",$htmlcode);
		$htmlcode=str_replace("</italic>","[/i]",$htmlcode);

		$htmlcode=eregi_replace("<a href=\"mailto:([^\"]*)\">([^<]*)</a>","[email]\\2[/email]",$htmlcode);
		$htmlcode=eregi_replace("<a href=\"([^\"]*)\" target=_blank>([^<]*)</a>","[url=\"\\1\"]\\2[/url]",$htmlcode);
		$htmlcode=eregi_replace("<a href=\"([^\"]*)\">([^<]*)</a>","[url=\"\\1\"]\\2[/url]",$htmlcode);

		$htmlcode=eregi_replace("<img src=\"([^\"]*)\">","[img]\\1[/img]",$htmlcode);

		// do code tags
		$htmlcode=eregi_replace("<BLOCKQUOTE><font size=\"1\" face=\"([^\"]*)\">code:</font><HR><pre>","[code]",$htmlcode);
		$htmlcode=str_replace("</pre><HR></BLOCKQUOTE>","[/code]",$htmlcode);

		// do quotes
		$htmlcode=eregi_replace("<BLOCKQUOTE><font size=\"1\" face=\"([^\"]*)\">quote:</font><HR>","[quote]",$htmlcode);
		$htmlcode=str_replace("<HR></BLOCKQUOTE>","[/quote]",$htmlcode);

		// do lists
		$htmlcode=eregi_replace("<ul type=square>","[list]",$htmlcode);
		$htmlcode=eregi_replace("</ul>","[/list]",$htmlcode);
		$htmlcode=eregi_replace("<ol type=1>","[list=1]",$htmlcode);
		$htmlcode=eregi_replace("<ol type=A>","[list=a]",$htmlcode);
		$htmlcode=eregi_replace("</ol>","[/list=a]",$htmlcode);
		$htmlcode=eregi_replace("<li>","[*]",$htmlcode);

		$htmlcode=str_replace("<p>","\n\n",$htmlcode);
		$htmlcode=str_replace("<P>","\n\n",$htmlcode);
		$htmlcode=str_replace("<br>","\n",$htmlcode);
		$htmlcode=str_replace("<BR>","\n",$htmlcode);

		return $htmlcode;
	}

	/**
	* Returns the user details array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	function get_OT_user_details(&$file, &$user_start_at, &$user_per_page)
	{
		$userfields = $this->init_userfields();

		$user_return_array = array();

		if(!($fp = @fopen($file, 'r')))
		{
			return false;
		}


		// This moves us up to grab the first valid record...
		while (!feof($fp)&&!$stop)
		{ // get to the first record
			$temp = fgets($fp, 16384);
			if (trim($temp) == "<USER>")
			{
				$stop = 1;
			}
		}

		unset($temp);
		unset($stop);

		while (!feof($fp)&&!$stop)
		{
			$temp = fgets($fp, 16384);
			$userinfo .= $temp;

			if (trim($temp) == "<USER>" || trim($temp) == "</USERS>")
			{
				$count++;

				if ($count>=$user_start_at && $count<=$user_start_at+$user_per_page)
				{
					$user_data = $this->parse($userinfo, $userfields);
					$doneimport=1;
					unset($user);
					$user[importuserid] = preg_replace('/^0*/','',$user_data[USER_OID]); // Take out leading zeros, intval() can't handle large numbers so we have to do it this way


					if ($user_data[IS_REGISTRATION_VALIDATED] == "N" || $user_data[IS_EMAIL_VERIFIED] == "N")
					{
						$user[usergroupid] = 3;
					}
					elseif ($user_data[IS_ADMINSTRATOR] == 'Y')
					{
						$user[usergroupid] = 6;
					}
					else
					{
						$user[usergroupid] = 2;
					}

					#$user[username] 	= htmlspecialchars($user_data[USERNAME]);
					if (htmlspecialchars($user_data[DISPLAY_NAME]) != '[no handle chosen]')
					{
						$user[username] 	= htmlspecialchars($user_data[DISPLAY_NAME]);
					}
					else
					{
						$user[username] 	= htmlspecialchars($user_data[EMAIL]);
					}
					
					$user[email] 		= $user_data[EMAIL];
					$user[posts] 		= $user_data[USER_POST_COUNT];
					$user[joindate] 	= strtotime(substr($user_data[REGISTRATION_DATE],0,19));

					if ($user_data[EMAIL] == $user_data[DISPLAY_EMAIL])
					{
						$user[showemail] = 1;
					}

					if ($user_data[DOB])
					{
						$user[birthday] = $user_data[DOB];
					}

					$user[parentemail] 	= $user_data[PARENT_EMAIL];
					$user[coppauser] 	= ($user_data[IS_AGE_RESTRICTED_USER] == "Y") ? 1 : 0;
					$user[adminemail] 	= ($user_data[HAS_OPTED_OUT_OF_EMAIL] == "Y") ? 0 : 1;
					$user[homepage] 	= $user_data[HOME_PAGE_URL];

					if ($user_data[ICQ_NUMBER])
					{
						$user[icq] = intval($user_data[ICQ_NUMBER]);
					}

					$user[daysprune] 	= -1;
					$user[cookieuser] 	= 1;
					$user[canpost] 		= 1;
					$user_data[LAST_LOGIN_DATETIME] = substr($user_data[LAST_LOGIN_DATETIME],0,19);
					$user[lastvisit] 	= strtotime($user_data[LAST_LOGIN_DATETIME]);
					$user[lastactivity] = $user[lastvisit];
					$user[lastpost] 	= $user[lastvisit];
					$user[signature] 	= $this->OThtml2bb(htmlspecialchars($user_data[SIGNATURE]));
					$user[emailnotification] = 0;
					$user[ipaddress] 	= $user_data[IP_AT_REGISTRATION];
					$user[password] 	= $user_data[PASSWORD];

					$user[location] 	= htmlspecialchars($user_data[LOCATION]);
					$user[occupation] 	= htmlspecialchars($user_data[OCCUPATION]);
					$user[interests] 	= htmlspecialchars($user_data[INTERESTS]);
					$user[biography] 	= htmlspecialchars($user_data[BIO]);

					$user[customtitle] = 0;
					$user_return_array["$user[importuserid]"] = $user;
				}
				else
				{
					if ($count == ($perpage+$startat))
					{
						$stop = 1;
					}
				}

			unset($userinfo);
			}
		@fclose($xmlpath);
		}

		return $user_return_array;
	}


	/**
	* Returns the posts details array
	*
	* @param	resource	file_handle		An open file handle with read permission to the file that you want the posts from
	* @param	int			mixed			The byte location to start from
	* @param	int			mixed			Number of posts to get
	*
	* @return	array
	*/
	function get_OT_posts_details($file_handle, $start_at, $stop_at)
	{
		$stack 				= array();
		$return_array 		= array();
		$element_counter 	= 0;
		$ignore 			= true;

		// Get going to where we what to be !
		if(!fseek ($file_handle, $start_at) == 0 OR !is_resource($file_handle))
		{
			// Couldn't move to that position
			$return_array['status'] = 'pointer_error';
			return $return_array;
		}

		do
		{
			// Get a line, trim it, take out the keepalive if present, remember where we have got to
			$buffer = fgets($file_handle);
			$buffer = trim($buffer);
			$buffer = str_replace("<!-- output keepalive end of output keepalive -->", "", $buffer);
			$pointer_last_poss =  ftell($file_handle);

			if($buffer != '')
			{
				// Its an element
				if(preg_match('#<([^"]*)>(.*)</\\1>#', $buffer , $matches))
				{
					// What ever section we are in is the array that the details need to go in
					if(!$ignore and substr(end($stack), 1 , -1) == 'MESSAGE')
					{
						// Aways going to be MESSAGE at the moment, though *should* be adapted in the
						// future to be generic, time dosn't allow at the moment.
						$position = end($stack);
						${$position}["$matches[1]"] = $matches[2];
					}
				}
				else
				{
					// If we are here and there is a / in the name its a closeing brace
					if (strpos ($buffer, '/'))
					{
						// As long as we havn't build a empty array, add it on the end.
						if (!empty(${end($stack)}))
						{
							$return_array[] = ${end($stack)};
						}

						// Clear it, take the element pointer off the stack
						unset(${end($stack)});
						array_pop($stack);
						$element_counter++;
						$ignore = true;
					}
					else
					{
						// Its an opening brace
						$stack[] = $buffer;

						// Do we want to get the elements for the new thing we have found ?
						if(substr(trim($buffer), 1 , -1) == 'MESSAGE')
						{
							$ignore = false;
						}
						else
						{
							$ignore = true;
						}

					}

					// If there are the number of elements on return all the data and the
					// position we are at in the file
					if(count($return_array) >= $stop_at)
					{
						$return_array['status'] = 'count';
						$return_array['pointer_position'] = $pointer_last_poss;
						return $return_array;
					}
				}
			}

		}
		while (!feof($file_handle));
		$return_array['status'] = 'eof';
		return $return_array;
	}


	/**
	* Returns the categories details array
	*
	* @param	resource	file_handle		An open file handle with read permission to the file that you want the posts from
	* @param	int			mixed			The byte location to start from
	* @param	int			mixed			Number of categorys to get, must be an passed variable
	*
	* @return	array
	*/
	function get_OT_details(&$file_handle, &$start_at, &$stop_at, &$return_array, &$type)
	{
		$start ='';
		$end = '';

		switch ($type)
		{
			case 'message':
				$start = '<MESSAGE>';
				$end = '</MESSAGE>';
				break;

			case 'category':
				$start = '<FORUM>';
				$end = '<TOPICS>';
				break;

			default :
				return false;
		}


		if($stop_at == 0)
		{
			return $return_array;
		}

		$forum 				= false;
		#$pointer_last_poss	= 0;

		if(!fseek ($file_handle, $start_at) == 0)
		{
			// Couldn't move to that position
			$return_array['status'] = 'pointer_error';
			die("Pointer error");
			return $return_array;
		}

		do
		{
			// Get a line, trim it, take out the keepalive if present, remember where we have got to
			$buffer = fgets($file_handle);
			if(feof($file_handle))
			{
				$stop_at = 0;
				return $return_array;
			}

			$buffer = trim($buffer);
			$buffer = str_replace("<!-- output keepalive end of output keepalive -->", "", $buffer);
			$pointer_last_poss =  ftell($file_handle);


			if($buffer == $start)
			{
				$forum = true;
			}

			if($forum)
			{
				// Its an element
				if(preg_match('#<([^"]*)>(.*)</\\1>#', $buffer , $matches))
				{
					$return_array[$stop_at]["$matches[1]"] = $matches[2];

				}
			}
		}
		while ($buffer != $end);

		$stop_at = $stop_at - 1;

		if($type == 'message')
		{
			$return_array['pointer_position'] = $pointer_last_poss;
		}

		$this->get_OT_details($file_handle, $return_array['pointer_position'], $stop_at, $return_array, $type);

		if($type == 'category')
		{
			$return_array['pointer_position'] = $pointer_last_poss;
		}


		return $return_array;
	}


	function category_exsists(&$Db_object, &$databasetype, &$tableprefix, $category_name)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$id = $Db_object->query_first("SELECT forumid FROM " . $tableprefix . "forum WHERE title = '$category_name' AND importcategoryid <> 0");

				if($id)
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


	function get_OT_category_ids(&$Db_object, &$databasetype, &$tableprefix)
	{

		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$forums = $Db_object->query("SELECT forumid, title  FROM " . $tableprefix . "forum WHERE importcategoryid <> 0");

				while ($forum = $Db_object->fetch_array($forums))
				{
					$categoryid["$forum[title]"] = $forum['forumid'];
				}
				$Db_object->free_result($forums);

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
