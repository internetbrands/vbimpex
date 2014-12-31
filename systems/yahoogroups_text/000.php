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
* yahoogroups_text API module
*
* @package			ImpEx.yahoogroups_text
*
*/
class yahoogroups_text_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '0.0';
	var $_tier = '3';
	
	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Yahoo groups (raw text)';
	var $_homepage 	= 'http://groups.yahoo.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();


	function yahoogroups_text_000()
	{
	}


	/**
	* Parses and custom HTML for yahoogroups_text
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function yahoogroups_text_html($text)
	{
		// Going to need some section parsing
		$text = preg_replace('#<(.*)HTML(.*)>#siU', '', $text);
		
		#$text = str_replace("</HTML>", '', $text);

		$text = preg_replace('#<FONT(.*)>#siU', '', $text);
		$text = str_replace("</FONT>", '', $text);

		$text = preg_replace('#<BLOCKQUOTE(.*)>#siU', '[quote]', $text);
		$text = str_replace("</BLOCKQUOTE>", '[/quote]', $text);

		$text = preg_replace('#--part(.*)_(.*)boundary#siU', '', $text);

		$text = preg_replace('#<DIV(.*)>#siU', '', $text);
		$text = str_replace("</DIV>", '', $text);

		$text = preg_replace('#<TT(.*)>#siU', '', $text);

		$text = preg_replace('#<hr(.*)>#siU', '', $text);
		
		$text = preg_replace('#<BODY(.*)>#siU', '', $text);

		$text = preg_replace('#\s*--\d+-\d+-\d+=:\d+\s*#s', '', $text);

		$text = preg_replace('#Yahoo! Groups Links(.*)Your use of Yahoo! Groups is subject to the Yahoo! Terms of Service.#siU', '', $text);
		$text = preg_replace('#To unsubscribe from this group, send an email to:(.*)-unsubscribe@yahoogroups.com#siU', '', $text);
		$text = preg_replace('#Your use of Yahoo(.*)Terms of Service(.*)[/url].#siU', '', $text);

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
	function get_yahoogroups_text_members_list($start_at, $per_page, &$path)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		if(!is_file($path . '/users.txt'))
		{
			return false;
		}

		// Go get it
		$total = file($path . '/users.txt');

		foreach($total AS $line_no => $data)
		{
			$bits = explode(',',$data);

			if($bits[0] AND strlen(trim($bits[0])) != 0 AND $bits[0] != "''")
			{
				$return_array[$line_no+1] = substr(substr($bits[0], 1), 0,  -1);
			}
			else
			{
				$return_array[$line_no+1] = substr(substr($bits[1], 1), 0,  -1);
			}
		}

		return array_slice($return_array, $start_at, $per_page);
	}


	/**
	* Returns the post_id => post array
	*
	*/
	function get_yahoogroups_text_post_details(&$path, &$filename, &$start_at, &$per_page)
	{
		$return_array = array();

		if (!$handle = opendir($path))
		{
			return false;
		}

		if(!is_file($path . '/' . $filename))
		{
			return false;
		}

		$handle = fopen($path . '/' . $filename, "r");

		while (!feof($handle))
		{
			// Were we looping to get a post, if so seek to the position just after the line to
			// carry on and find the next start_finder()
			if($start_pos)
			{
				fseek($handle,$start_pos);
				if(!$buffer = fgets($handle, 4096))
				{
					return $return_array;
				}
				unset($start_pos);
			}
			else
			{
				if(!$buffer = fgets($handle, 4096))
				{
					return $return_array;
				}
			}

			if($result = $this->start_finder($buffer))
			{
				// Count through all of them as we go through the file.
				$total++;
				if($total < $start_at)
				{
					// We havent got to the post to
					// start at yet.
					continue;
				}

				// If we are within  :: ( > start_at) AND (< start_at + per_page)
				// count them
				$end_counter++;

				// Remember the line we are on for continuing after we have all
				// the post data.
				$start_pos = ftell($handle);


				unset($recived, $from, $ipaddress, $subject);

				do
				{
					// Keep getting data for this post untill we get to
					// the beginning of the next post
					if(!$line = fgets($handle, 4096))
					{
						return $return_array;
					}

					// Parse all the headder data as well as the message_body out.

					if($line{0} == ' ')
					{
						// Its a headder line starting with a blank, or its a spacer line.
						// content lines don't start with spaces.
						continue;
					}

					$str = strtolower(substr($line, 0, strpos($line, ':')));

					switch($str)
					{
						case 'date':
							if(!$recived)
							{
								$recived = true;
								$post_details['dateline'] = strtotime(trim(substr(substr($line, strpos($line, ':')+1), 0, -5)));
							}
							break;
						case 'from':
							if(!$from)
							{
								$from = true;
								$post_details['from'] = trim(substr(substr($line, strpos($line, ':')+2), 1, -1));
							}
							break;
						case 'x-originating-ip':
							if(!$ipaddress)
							{
								$ipaddress = true;
								$post_details['ipaddress'] = trim(substr($line, strpos($line, ':')+1));
							}
							break;
						case 'subject':
							if(!$subject)
							{
								$subject = true;
								$post_details['subject'] = trim(substr($line, strpos($line, ':')+1));
							}
							break;

						case 'received':
						case 'return-path':
						case 'x-egroups-return':
						case 'x-sender':
						case 'x-apparently-to':
						case 'to':
						case 'message-id':
						case 'user-agent':
						case 'mime-version':
						case 'content-type':
						case 'content-length':
						case 'x-mailer':
						case 'bcc':
						case 'x-egroups-announce':
						case 'x-originalarrivaltime':
						case 'x-yahoo-profile':
						case 'x-yahoo-newman-property':
						case 'in-reply-to':
						case 'x-yahoo-newman-property':
						case 'x-egroups-remote-ip':
						case 'x-yahoo-group-post':
						case 'x-originating-email':
						case 'reply-to':
						case 'references':
						case 'content-transfer-encoding':

						case 'cc':
							// Bin it
							break;
						default:
						{
							$post_details['pagetext'][] = $line;
						}
					}
				}
				while(!$this->start_finder($line));

				// use the total counter so we get the importpostid's as they are in the file.
				$return_array[$total]['email'] = $result;

				// Trim the last line as its the step over into the next email i.e.
				// the line where :: while(!start_finder($line));
				$post_details['pagetext'] = array_slice($post_details['pagetext'], 0, count($post_details['pagetext'])-1);

				$return_array[$total]['post'] = $post_details;
				unset($post_details);
			}

			// Check the total number of messages we have so far
			if($end_counter == $per_page)
			{
				return $return_array;
			}
		}

		return $return_array;
	}


	/**
	* Returns the thread_id => thread array
	*
	*/
	function get_yahoogroups_text_thread_details(&$path, &$filename, &$group_name, &$start_at, &$per_page)
	{
		$return_array = array();


		if (!$handle = opendir($path))
		{
			return false;
		}

		if(!is_file($path . '/' . $filename))
		{
			return false;
		}

		$handle = fopen($path . '/' . $filename, "r");

		while (!feof($handle))
		{
			// Were we looping to get a post, if so seek to the position just after the line to
			// carry on and find the next start_finder()
			if($start_pos)
			{
				fseek($handle,$start_pos);
				if(!$buffer = fgets($handle, 4096))
				{
					return $return_array;
				}
				unset($start_pos);
			}
			else
			{
				if(!$buffer = fgets($handle, 4096))
				{
					return $return_array;
				}
			}

			if(feof($handle))
			{
				return $return_array;
			}

			if($result = $this->start_finder($buffer))
			{
				// Count through all of them as we go through the file.
				$total++;
				if($total < $start_at)
				{
					// We havent got to the post to
					// start at yet.
					continue;
				}

				// If we are within  :: ( > start_at) AND (< start_at + per_page)
				// count them
				$end_counter++;

				// Remember the line we are on for continuing after we have all
				// the post data.
				$start_pos = ftell($handle);


				do
				{
					// Keep getting data for this post untill we get to
					// the beginning of the next post
					if(!$line = fgets($handle, 4096))
					{
						return $return_array;
					}

					// Parse all the headder data as well as the message_body out.

					if($line{0} == ' ')
					{
						// Its a headder line starting with a blank, or its a spacer line.
						// content lines don't start with spaces.
						continue;
					}



					$str = strtolower(substr($line, 0, strpos($line, ':')));

					switch($str)
					{
						case 'subject':
							if(!$subject)
							{
								$subject = true;
								$cleaned = '';

								// If we find anything its going to return a cleaned string
								// else its going to be false, so its probally an origional
								if(!$cleaned = $this->clean_subject($group_name, $line))
								{
									continue;
								}

								$post_details['subject'] = $cleaned;
							}
							break;

						case 'date':
							if(!$recived)
							{
								$recived = true;
								$post_details['dateline'] = strtotime(trim(substr(substr($line, strpos($line, ':')+1), 0, -5)));
							}
							break;

						default:
						{
							// Bin it
							break;
						}
					}
				}
				while(!$this->start_finder($line));

				// Check for duplicates
				foreach($return_array AS $count => $exsisting)
				{
					// If there is the same subject but an older dateline
					if(
						$exsisting['thread']['subject'] == $post_details['subject']
						AND
						$exsisting['thread']['dateline'] <= $post_details['dateline']
					)
					{
						// Take the newer date line
						$skipping++;
						$return_array[$count]['thread']['dateline'] = $post_details['dateline'];
						$skip = true;
					}

				}

				if($post_details['subject'] AND !$skip)
				{
					$return_array[$total]['thread'] = $post_details;
				}

				unset($post_details, $subject, $recived, $skip);
			}

			// Check the total number of messages we have so far
			if(count($return_array) == $per_page)
			{
				return $return_array;
			}
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
	function get_yahoogroups_text_user_details($start_at, $per_page, &$path)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if (!$handle = opendir($path))
		{
			return false;
		}

		if(!is_file($path . '/users.txt'))
		{
			return false;
		}

		// Go get it
		$total = file($path . '/users.txt');

		foreach($total AS $line_no => $data)
		{
			$bits = explode(',',$data);

			if($bits[0] AND strlen(trim($bits[0])) != 0 AND $bits[0] != "''")
			{
				$return_array[$line_no+1]['username'] = substr(substr($bits[0], 1), 0,  -1);
			}
			else
			{
				$return_array[$line_no+1]['username'] = substr(substr($bits[1], 1), 0,  -1);
			}

			$return_array[$line_no+1]['email'] = substr(substr($bits[1], 1), 0,  -1);
			$return_array[$line_no+1]['joindate'] = substr(substr($bits[9], 1), 0,  -1);
		}

		return array_slice($return_array, $start_at, $per_page);
	}


	function start_finder($str)
	{
		$pos1 = strpos($str, 'From');
		$pos2 = strpos($str, '@');
		global $message;

		if(substr($str, 0, 5) == 'From ')
		{
			if($pos2 !== false)
			{
				if(substr_count($str, ':') == 2)
				{
					$bits = explode(' ', $str);

					if(!strpos($bits[1], '@')) { return false; }
					if(strtotime(implode(' ', array_slice($bits, 2, count($bits)))) == -1)  { return false; }

					return array(
						'emailaddress' 	=> $bits[1],
						'timestamp'		=> strtotime(implode(' ', array_slice($bits, 2, count($bits))))
					);
				}
			}
		}

		return false;
	}


	function clean_subject(&$groupname, &$text)
	{
		// Find Re: Subject: or [$groupname] in the subject and strip it
		// else return false and hope its an origional


		$re = strpos(strtoupper($text), 'RE:');
		if($re !== false)
		{
			$text = substr($text, 0, $re) . substr($text, $re+3, strlen($text));
		}

		$su = strpos(strtoupper($text), 'SUBJECT:');
		if($su !== false)
		{
			$text = substr($text, 0, $su) . substr($text, $su+8, strlen($text));
		}

		$fw = strpos(strtoupper($text), 'FW:');
		if($fw !== false)
		{
			$text = substr($text, 0, $fw) . substr($text, $fw+3, strlen($text));
		}

		$gr = strpos($text, "[{$groupname}]");
		if($gr !== false)
		{
			$text = substr($text, 0, $gr) . substr($text, $gr+strlen($groupname)+2, strlen($text));
		}

		$fwd = strpos(strtoupper($text), 'FWD:');
		if($fwd !== false)
		{
			$text = substr($text, 0, $fwd) . substr($text, $fwd+4, strlen($text));
		}

		if(
			strpos(strtoupper($text), 'RE:')
			OR
			strpos(strtoupper($text), 'FW:')
			OR
			strpos(strtoupper($text), 'FWD:')
			OR
			strpos($text, "[{$groupname}]")
		)
		{
			$this->clean_subject($groupname, $text);
		}


		if($re !== false OR $su !== false OR $gr !== false OR $fw !== false OR $fwd !== false)
		{
			return trim($text);
		}
		else
		{
			return false;
		}
	}


	function yahoo_title_search(&$Db_object, &$databasetype, &$tableprefix, $thread_subject)
	{
		// Check that there is not a empty value
		if(empty($thread_subject)) { return false; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT threadid
			FROM " . $tableprefix . "thread
			WHERE `title` = '" . addslashes($thread_subject) . "'
			";

			$details_list = $Db_object->query_first($sql);

			return $details_list['threadid'];
		}
		else
		{
			return false;
		}
		return false;
	}

} // Class end
# Autogenerated on : November 25, 2004, 12:31 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>

