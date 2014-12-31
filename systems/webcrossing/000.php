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
* webcrossing API module
*
* @package			ImpEx.webcrossing
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class webcrossing_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '5.0';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'Webcrossing';
	var $_homepage 	= 'http://www.webcrossing.com/Home/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ();


	function webcrossing_000()
	{
	}


	/**
	* Parses and custom HTML for webcrossing
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function webcrossing_html($text)
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
	function get_webcrossing_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT user_id,username
			FROM " . $tableprefix . "users
			ORDER BY user_id
			LIMIT " . $start . "," . $per_page;


			$user_list = $Db_object->query($sql);


			while ($user = $Db_object->fetch_array($user_list))
			{
					$return_array["$user[user_id]"] = $user['username'];
			}
			return $return_array;
		}
		else
		{
			return false;
		}
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
	function get_webcrossing_user_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();


		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }


		if ($databasetype == 'mysql')
		{
			$sql = "
			SELECT * FROM " .
			$tableprefix."user
			ORDER BY user_id
			LIMIT " .
			$start_at .
			"," .
			$per_page
			;


			$details_list = $Db_object->query($sql);


			while ($detail = $Db_object->fetch_array($details_list))
			{
					$return_array["$detail[user_id]"] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}


} // Class end

define('WC_STATE_START', 1);
define('WC_STATE_CDATA', 2);
define('WC_STATE_TAG_START', 3);
define('WC_STATE_TAG_INTERNAL', 4);
define('WC_STATE_TAG_ATTR_NAME', 5);
define('WC_STATE_TAG_CLOSED', 6);
define('WC_STATE_END', 7);

/**
* Parser flag that means more data needs to be read
*/
define('WC_STATE_FLAG_READ', 0x8000);

/**
* Web Crossing export parser
*
* @package			ImpEx.webcrossing
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class WebCrossing_Parser
{
	/**
	* File handle where the HTML to be parsed is coming from.
	*
	* @var	resource
	*/
	var $source = null;

	/**
	* Message handler that receives messages from the parser.
	* Should be a function name (string) or an object method in the form
	* array(&$obj, 'method')
	*
	* @var	callback
	*/
	var $handler = null;

	/**
	* Text that has been read from the file, but not yet parsed.
	*
	* @var	string
	*/
	var $buffer = null;

	/**
	* If parsing is completed/stopped, this will be the location of the last
	* character that was parsed.
	*
	* @var	integer
	*/
	var $end_position = 0;

	/**
	* Option to control whether cdata that, when trimmed, is empty ('') should
	* be sent to the message handler.
	*
	* @var	boolean
	*/
	var $ignore_empty_cdata = false;

	/**
	* Option to forcibly end parsing at the current location. This is good
	* if you are parsing a very large file.
	*
	* @var	boolean
	*/
	var $stop_parsing = false;

	/**
	* Constructor. Allows you to optionally setup the source file.
	*
	* @param	string	Filename to read from
	*/
	function WebCrossing_Parser($source = '')
	{
		if ($this->source)
		{
			$this->set_source($source);
		}
	}

	/**
	* Opens the source file and prepares for parsing.
	*
	* @param	string	Filename to read from
	*
	* @return	boolean	True on success; false on failure
	*/
	function set_source($filename)
	{
		$fh = @fopen($filename, 'r');

		if (!$fh)
		{
			return false;
		}
		else
		{
			$this->source = $fh;
			return true;
		}
	}

	/**
	* Sets the parsing start position in the source file.
	* Note: this assumes to see to a position just after or just before a tag,
	* not inside one!
	*
	* @param	integer	Byte location to start parsing at
	*
	* @return	boolean	True on success; false on failure
	*/
	function set_start($byte_offset)
	{
		if ($this->source AND $byte_offset > 0)
		{
			fseek($this->source, $byte_offset);
			$this->end_position = $byte_offset;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Gets the current position in the file. Note that this is the position of
	* the file handle. This does not account for data read but not parsed!
	*
	* @return	integer	Byte position in file
	*/
	function get_position()
	{
		return ftell($this->source);
	}

	/**
	* Sets the message handler function. Messages are sent with 3 arguments
	* a reference to the parser, the type of message, the data of the message
	*
	* @param	callback	Function/object method to send messages to
	*
	* @return	boolean		True on success; false on failure
	*/
	function set_handler($callback)
	{
		if (is_callable($callback))
		{
			$this->handler = $callback;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Parse the file from the start position. This will parse until told to stop
	* by the message handler function!
	*
	* @param	boolean	Whether to automatically destroy the remaining parser data upon completion
	*
	* @return	boolean	False if the file cannot be read; true otherwise
	*/
	function parse($auto_destroy = true)
	{
		if (!$this->source)
		{
			return false;
		}

		$state = WC_STATE_START;
		$buffer = '';
		$this->buffer =& $buffer;

		$state_info = array();

		$counter = 0;

		do
		{
			if ($state & WC_STATE_FLAG_READ OR $buffer === '')
			{
				$state &= ~WC_STATE_FLAG_READ;
				$buffer .= fread($this->source, 8 * 1024);

				if (feof($this->source))
				{
					if ($buffer === '')
					{
						$state = WC_STATE_END;
					}
					else
					{
						if (isset($state_cache) AND $state == $state_cache AND $buffer == $buffer_cache)
						{
							// this could be changed to a break at some point
							trigger_error("Webcrossing Parser: Apparent infinite loop. Parsing failed. Please contact support with this error.", E_USER_ERROR);
						}

						$state_cache = $state;
						$buffer_cache = $buffer;
					}
				}
				else if (!$state)
				{
					$state = WC_STATE_START;
				}
			}

			if ($state == WC_STATE_START)
			{
				$state_info = array();

				if ($this->stop_parsing)
				{
					break;
				}

				$tag_open = strpos($buffer, '<', 0);
				if ($tag_open !== false)
				{
					if ($tag_open === 0)
					{
						$state = WC_STATE_TAG_START;
					}
					else
					{
						$state = WC_STATE_CDATA;
						$state_info['tag_open'] = $tag_open;
					}
				}
				else
				{
					$state = WC_STATE_CDATA;
				}
			}
			else if ($state == WC_STATE_CDATA)
			{
				if (!empty($state_info['tag_open']))
				{
					$cdata = substr($buffer, 0, $state_info['tag_open']);
					$buffer = substr($buffer, $state_info['tag_open']);
				}
				else
				{
					$cdata = $buffer;
					$buffer = '';
				}

				if (!$this->ignore_empty_cdata OR trim($cdata) !== '')
				{
					$this->send_message('cdata', $cdata);
				}

				$state = WC_STATE_START;
			}
			else if ($state == WC_STATE_TAG_START)
			{
				if (preg_match('#^(<([^\s>]+))(\s*(/?)\s*>|\s+)#s', $buffer, $match))
				{

					$buffer = substr($buffer, strlen($match[1]));

					if ($match[2]{0} == '/')
					{
						$state_info['type'] = 'close';
						$tag_name = substr($match[2], 1);
					}
					else
					{
						$state_info['type'] = 'open';
						$tag_name = $match[2];
					}

					$state_info['tag_name'] = $tag_name;

					$trailing = trim($match[3]);
					if (!empty($trailing))
					{
						if (!empty($match[4]))
						{
							$state_info['type'] = 'open_close';
						}
						$state = WC_STATE_TAG_CLOSED;
					}
					else
					{
						$state = WC_STATE_TAG_INTERNAL;
					}
				}
				else
				{
					$state |= WC_STATE_FLAG_READ;
				}
			}
			else if ($state == WC_STATE_TAG_INTERNAL)
			{
				/*if ($this->count == 3601)
				{
					echo "<p>internal: " . htmlspecialchars($buffer) . "</p>";
				}*/
				if (preg_match('#^(\s*((?>[^\s=/>]+)))(\s*=|\s*(/?)\s*>|\s+[a-z0-9_-])#si', $buffer, $match))
				{
					$buffer = substr($buffer, strlen($match[1]));

					$state_info['last_attribute'] = $match[2];

					if (trim($match[3]) == '=')
					{
						// nowrap="nowrap" style
						$state = WC_STATE_TAG_ATTR_NAME;
					}
					else if (substr($match[3], -1) == '>')
					{
						// nowrap> style
						if (!empty($match[4]))
						{
							$state_info['type'] = 'open_close';
						}
						$state = WC_STATE_TAG_CLOSED;
					}
					else
					{
						// nowrap [...] style
						$state_info['attributes']["$match[2]"] = $match[2];
						unset($state_info['last_attribute']);

						$state = WC_STATE_TAG_INTERNAL;
					}
				}
				else if (preg_match('#^(\s*)(/?)\s*>#', $buffer, $match))
				{
					$buffer = substr($buffer, strlen($match[1]));

					if (!empty($match[2]))
					{
						$state_info['type'] = 'open_close';
					}

					$state = WC_STATE_TAG_CLOSED;
				}
				else
				{
					$state |= WC_STATE_FLAG_READ;
				}
			}
			else if ($state == WC_STATE_TAG_ATTR_NAME)
			{
				/*if ($this->count == 3601)
				{
					echo "<p>attr name: " . htmlspecialchars($buffer) . "</p>";
				}*/
				if (preg_match('#^(\s*=)(.)#s', $buffer, $match))
				{
					if ($match[2] == '"' OR $match[2] == "'")
					{
						$valid_inner = preg_match('#^\s*=' . $match[2] . '(.*?)' . $match[2] . '(.)#s', $buffer, $inner_match);
					}
					else
					{
						$valid_inner = preg_match('#^\s*=([^\s>]*)([\s>])#s', $buffer, $inner_match);
					}

					if ($valid_inner)
					{
						$buffer = substr($buffer, strlen($inner_match[0]) - 1); // we need to keep the last char

						$last = $state_info['last_attribute'];
						$state_info['attributes']["$last"] = $inner_match[1];
						unset($state_info['last_attribute']);

						if ($inner_match[2] == '>')
						{
							$state = WC_STATE_TAG_CLOSED;
						}
						else
						{
							$state = WC_STATE_TAG_INTERNAL;
						}
					}
					else
					{
						$state |= WC_STATE_FLAG_READ;
					}
				}
				else
				{
					$state |= WC_STATE_FLAG_READ;
				}
			}
			else if ($state == WC_STATE_TAG_CLOSED)
			{
				if (preg_match('#^(\s*)(/?)\s*>#', $buffer, $match))
				{
					$buffer = substr($buffer, strlen($match[0]));
					$this->send_message('tag', $state_info);

					$state = WC_STATE_START;
				}
				else
				{
					$state |= WC_STATE_FLAG_READ;
				}
			}
			else if ($state == WC_STATE_END)
			{
				$this->stop_parsing = true;
				$this->send_message('eof', null);

				break;
			}
			else
			{
				trigger_error("Webcrossing Parser: invalid state. Please contact support. (state = $state)", E_USER_ERROR);
			}
		}
		while (true);

		$this->stop_parsing = false;
		$this->end_position = $this->get_position() - strlen($buffer);

		unset($buffer);
		$this->buffer = null;

		if ($auto_destroy)
		{
			$this->destroy();
		}

		return true;
	}

	var $count = 0;
	/**
	* Sends a message to the message handler callback
	*
	* @param	string	Type of message
	* @param	mixed	Message data
	*/
	function send_message($type, $data)
	{
		if ($type == 'tag')
		{
			//$this->count++;
		}
		if ($this->handler)
		{
			call_user_func_array($this->handler, array(&$this, $type, $data));
		}
	}

	/**
	* Closes the file that was being parsed.
	*/
	function destroy()
	{
		if ($this->source)
		{
			fclose($this->source);
			$this->source = null;
		}
	}

	/**
	* Stops parsing once the parser returns to its start state.
	*/
	function stop_parsing()
	{
		$this->stop_parsing = true;
	}

	/**
	* Sets whether empty cdata is ignored
	*
	* @param	boolean	Ignored if true
	*/
	function ignore_empty_cdata($value)
	{
		$this->ignore_empty_cdata = $value;
	}
}


class ParserHandler extends webcrossing_000
{
	var $count = 0;
	var $perpage = 10000;
	var $eof = false;

	var $Db_object;
	var $session;

	var $target_db_type;
	var	$target_db_prefix;

	var $stack = array();


	function webxing_html_clean($text_in)
	{
		$find = array('&lt;','&gt;','&quot;','&amp;');
		$replace = array('<','>','"','&');

		$text_out = str_replace($find, $replace, $text_in);
		return preg_replace('/&#(\d+);/e', "chr(intval('\\1'))", $text_out);
	}


	// ######################### STACK MANAGEMENT ##############

	function fetch_stack_size()
	{
		return count($this->stack);
	}

	function push_stack($data)
	{
		array_unshift($this->stack, $data);
	}

	function &get_first_tag($tag_name, $pop = false)
	{
		$return = false;
		foreach ($this->stack AS $key => $data)
		{
			if ($data['tag_name'] == $tag_name)
			{
				if ($pop == true)
				{
					$return = $this->stack["$key"];
					unset($this->stack["$key"]);
				}
				else
				{
					$return =& $this->stack["$key"];
				}
				break;
			}
		}

		return $return;
	}

	function find_first_tag($tag_name)
	{
		$return = false;
		foreach ($this->stack AS $key => $data)
		{
			if ($data['tag_name'] == $tag_name)
			{
				return $key;
			}
		}

		return false;
	}

	function pop_first_tag($tag_name)
	{
		return $this->get_first_tag($tag_name, true);
	}

	// ######################### PARSER MESSAGE HANDLER ##############

	/**
	* Receives messages from the parser. The possible messages are:
	* 	'tag' - called when a tag is found. Array contains tag info (name, type [open, close], and attributes)
	*	'cdata' - called when data is found between tags; string contains text found; may be called more than once in a row
	*	'eof' - called when the parser stops parsing but ONLY when it reaches the end of the file
	*
	* @param	WebCrossing_Parser	Reference to the parser that called this function
	* @param	string				Message type
	* @param	mixed				Message data
	*/
	function parser_callback(&$parser, $type, $data)
	{
		if ($type == 'tag')
		{
			$this->tag_handler($parser, $data);
		}
		else if ($type == 'cdata')
		{
			$this->cdata_handler($parser, $data);
		}
		else if ($type == 'eof')
		{
			$this->eof = true;
		}
	}

	// ######################### GENERIC HANDLERS ##############

	function tag_handler(&$parser, $data)
	{
		++$this->count;

		//echo "$this->count $data[type] $data[tag_name]<br />\n";
		//flush();

		if (is_array($data['attributes']))
		{
			foreach ($data['attributes'] AS $attribute => $attrval)
			{
				$data['attributes']["$attribute"] = $this->webxing_html_clean($attrval);
			}
		}

		// we care about these tags when they're opened
		switch ($data['tag_name'])
		{
			case 'user':
				$this->parse_user($data);
				break;

			case 'folder':
				$this->parse_folder($data);
				break;

			case 'discussion':
				$this->parse_discussion($data);
				break;

			case 'message':
				$this->parse_message($data);
				break;

			default:
				// we don't care about this tag
				//echo "useless tag: $data[tag_name] ($data[type])<br />\n"; flush();
		}

		if ($this->count % $this->perpage == 0)
		{
			$parser->stop_parsing();
		}
	}

	function cdata_handler(&$parser, $data)
	{
		//echo "<i>some cdata</i><br />\n"; flush();
	}

	// ########################## SPECIFIC HANDLERS ###############

	function parse_user($data)
	{
		$attributes =& $data['attributes'];

		if (empty($attributes['unique']))
		{
			//echo "user tag without data we need!<br />\n"; flush();
			return;
		}

		$deleted = (isset($attributes['deleted']) ? '; deleted!' : '');

		$user_object = new ImpExData($this->Db_object, $this->session, 'user');

		$user_object->set_value('mandatory', 'usergroupid',			$this->session->get_session_var('usergroupid'));

		// Swap hutch, jerry for jerry hutch
		if (substr(', ', $attributes['name']))
		{
			$name_bits = explode(' ',$attributes['name']);
			$attributes['name'] = $name_bits[1] . ' ' . substr($name_bits[0], 0, -1);
		}

		$user_object->set_value('mandatory', 'username',			trim($attributes['name']));

		$user_object->set_value('mandatory', 'email',				$attributes['email']);
		$user_object->set_value('mandatory', 'importuserid',		hexdec($attributes['ID']));

		// passwords are stored as binary md5 hashes. Need to take each nibble from each byte
		// and convert it to hex
		if (strlen($attributes['password']) == 16)
		{
			$password = '';
			for ($i = 0; $i < 16; $i++)
			{
				$chr = ord($attributes['password']{$i});
				$password .= dechex(($chr >> 4) & 0xF);
				$password .= dechex($chr & 0xF);
			}
		}
		else
		{
			// hey, this wouldn't even going to come out to a valid md5 hash...
			$password = $attributes['password'];
		}

		$user_object->_password_md5_already = true;
		$user_object->set_value('nonmandatory', 'password',			$password);
		$user_object->set_value('nonmandatory', 'passworddate',		time());
		$user_object->set_value('nonmandatory', 'homepage',			$attributes['homePage']);
		$user_object->set_value('nonmandatory', 'joindate',			@strtotime($attributes['registeredTd']));
		$user_object->set_value('nonmandatory', 'lastvisit',		@strtotime($attributes['lastLogin']));
		$user_object->set_value('nonmandatory', 'lastactivity',		@strtotime($attributes['lastLogin']));
		$user_object->set_value('nonmandatory', 'options',			$this->_default_user_permissions);

		if($user_object->import_user($this->Db_object, $this->target_db_type, $this->target_db_prefix))
		{
			echo("<br /><span class=\"isucc\"><b>" . $user_object->how_complete() . "%</b></span> users -> :: " . $user_object->get_value('mandatory','username'));
			flush();
		}
		else
		{
			echo "<h1>'" . trim($attributes['name']) . " not imported" . "'</h1>";
		}

		#echo "found user '$attributes[name]' ($attributes[unique]$deleted)<br />\n"; flush();
	}

	function parse_generic_pair($data)
	{
		if ($data['type'] == 'open')
		{
			$this->push_stack($data);
			echo "put data $data[tag_name] on stack<br />\n"; flush();
		}
		else
		{
			if ($popdata = $this->pop_first_tag($data['tag_name']))
			{
				//echo "popped $data[tag_name] off<br />\n"; flush();
			}
		}
	}

	function parse_folder($data)
	{
		/*
		// this method is not particularly extensible. Using these flags, 2 bits
		// were found to always be on, 0x180000. Any forum with either of these
		// bits will now be imported
		$valid_folder_flags = array(
			'00181000',
			'00190800',
			'00191000',
			'00193000',
			'001B1800',
			'08181000',
			'08190800',
			'081B1800',
		);
		if ($data['type'] == 'open' AND in_array($data['attributes']['flags'], $valid_folder_flags))
		*/

		if ($data['type'] == 'open')
		{
			if ($forum =& $this->get_first_tag('folder') AND !empty($forum['__ignorechildren']))
			{
				// we're ignoring a parent, so ignore this one as well
				$data['__ignorechildren'] = true;
				$this->push_stack($data);
				return;
			}

			if (!(hexdec($data['attributes']['flags']) & 0x180000) OR
				in_array(trim($data['attributes']['name']), array(
					'In',
					'system',
					'common',
					'Click Here to Begin',
					'Quick Help',
					'plugins',
					'Templates',
					'webxTemplates',
					'Images'
			)))
			{
				// wrong flags or is a special forum we don't want, so
				// don't import it and ignore any children it may have
				$data['__ignorechildren'] = true;
				$this->push_stack($data);
				return;
			}
			else
			{
				$this->push_stack($data);
			}

			$stack_size = $this->fetch_stack_size();

			if ($stack_size == 0)
			{
				//eeke !
				die('no push');
			}
			else if ($stack_size == 1)
			{
				// One deep its a cat
				$category_object = new ImpExData($this->Db_object, $this->session, 'forum');

				$category_object->set_value('mandatory', 'title', 				($data['attributes']['title'] ? $data['attributes']['title'] : $data['attributes']['name']));
				$category_object->set_value('mandatory', 'displayorder',		'1');
				$category_object->set_value('mandatory', 'parentid',			'-1');
				$category_object->set_value('mandatory', 'importforumid',		'0');
				$category_object->set_value('mandatory', 'importcategoryid',	hexdec($data['attributes']['unique']));
				$category_object->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				$category_object->set_value('nonmandatory', 'description', 		$data['attributes']['heading']);

				if($cat_id = $category_object->import_category($this->Db_object, $this->target_db_type, $this->target_db_prefix))
				{
					echo("<br /><span class=\"isucc\">Category -- <b>" . $category_object->how_complete() . "%</b></span> :: " . $category_object->get_value('mandatory','title'));
					$this->session->add_session_var('currentcat', $cat_id);
					$this->session->add_session_var('currentforum', $cat_id);
					flush();
				}
				else
				{
					echo "<br />'" . trim($attributes['attributes']['name']) . " not imported";
				}
				unset($category_object);
			}
			else if ($stack_size == 2)
			{
				// Two deep its a forum
				$forum_object = new ImpExData($this->Db_object, $this->session, 'forum');

				$forum_object->set_value('mandatory', 'parentid',			$this->session->get_session_var('currentcat'));
				$forum_object->set_value('mandatory', 'title', 				$data['attributes']['name']);
				$forum_object->set_value('mandatory', 'displayorder',		$data['attributes']['sortSeq']);
				$forum_object->set_value('mandatory', 'importforumid',		hexdec($data['attributes']['unique']));
				$forum_object->set_value('mandatory', 'importcategoryid',	'0');
				$forum_object->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$forum_object->set_value('nonmandatory', 'description',		$forum['heading']);
				$forum_object->set_value('nonmandatory', 'visible',			'1');

				if($forum_id = $forum_object->import_forum($this->Db_object, $this->target_db_type, $this->target_db_prefix))
				{
					echo("<br /><span class=\"isucc\">Forum -- <b>" . $forum_object->how_complete() . "%</b></span> :: forum " . $forum_object->get_value('mandatory','title'));
					$this->session->add_session_var('currentforum', $forum_id);
					flush();
				}
				else
				{
					echo "<br />'" . trim($data['attributes']['name']) . " not imported";
				}
				unset($forum_object);
			}
			else
			{
				// Two deep its a sub forum, with a folder tag
				$forum_object = new ImpExData($this->Db_object, $this->session, 'forum');

				$forum_object->set_value('mandatory', 'parentid',			$this->session->get_session_var('currentforum'));
				$forum_object->set_value('mandatory', 'title', 				$data['attributes']['name']);
				$forum_object->set_value('mandatory', 'displayorder',		$data['attributes']['sortSeq']);
				$forum_object->set_value('mandatory', 'importforumid',		hexdec($data['attributes']['unique']));
				$forum_object->set_value('mandatory', 'importcategoryid',	'0');
				$forum_object->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$forum_object->set_value('nonmandatory', 'description',		$forum['heading']);
				$forum_object->set_value('nonmandatory', 'visible',			'1');

				if($forum_id = $forum_object->import_forum($this->Db_object, $this->target_db_type, $this->target_db_prefix))
				{
					echo("<br /><span class=\"isucc\">Sub Forum -- <b>" . $forum_object->how_complete() . "%</b></span> :: forum " . $forum_object->get_value('mandatory','title'));
					$this->session->add_session_var('currentforum', $forum_id);
					flush();
				}
				else
				{
					echo "<br />'" . trim($data['attributes']['name']) . " not imported";
				}
				unset($forum_object);
			}

		}
		else if ($data['type'] == 'close')
		{
			if ($popdata = $this->pop_first_tag($data['tag_name']))
			{
				//echo "popped $data[tag_name] off<br />\n"; flush();
			}
		}
	}

	function parse_discussion($data)
	{
		if (!($forum =& $this->get_first_tag('folder')))
		{
			// not in a forum
			return;
		}
		else if (!empty($forum['__ignorechildren']))
		{
			// in an ignored forum
			return;
		}

		if ($data['type'] == 'open')
		{
			/*
			echo str_repeat('--', sizeof($this->stack)) . " <i>discussion</i><br />\n";
			flush();
			*/
			$this->push_stack($data);

			$user_names	= $this->get_username($this->Db_object, $this->target_db_type, $this->target_db_prefix);
			$users_ids	= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

			### Import the thread first ####

			// Thread


			$thread_object = new ImpExData($this->Db_object, $this->session, 'thread');

			$thread_object->set_value('mandatory', 'title', 			htmlspecialchars($data['attributes']['title']));
			$thread_object->set_value('mandatory', 'forumid', 			$this->session->get_session_var('currentforum'));
			$thread_object->set_value('mandatory', 'importthreadid', 	hexdec($data['attributes']['unique']));
			$thread_object->set_value('mandatory', 'importforumid', 	$this->session->get_session_var('currentforum'));

			$userid = hexdec($data['attributes']['author']);

			$thread_object->set_value('nonmandatory', 'postusername',	$user_names[$userid]);
			$thread_object->set_value('nonmandatory', 'postuserid', 	$users_ids[$userid]);
			$thread_object->set_value('nonmandatory', 'dateline', 		strtotime($data['attributes']['created']));
			$thread_object->set_value('nonmandatory', 'visible', 		'1');
			$thread_object->set_value('nonmandatory', 'open', 			'1');
			$thread_object->set_value('nonmandatory', 'sticky',			'0');

			if($thread_id = $thread_object->import_thread($this->Db_object, $this->target_db_type, $this->target_db_prefix))
			{
				echo("<br /><span class=\"isucc\">Thread -- <b>" . $thread_object->how_complete() . "%</b></span> :: thread " . $thread_object->get_value('mandatory','title'));
				$this->session->add_session_var('currentthread', $thread_id);
				flush();
			}
			else
			{
				echo "<br />'" . trim($data['attributes']['title']) . " not imported";
			}

			### Put the data in the first post ###

			// Post

			$post_object = new ImpExData($this->Db_object, $this->session, 'post');

			$post_object->set_value('mandatory', 'threadid', 			$thread_id);
			$post_object->set_value('mandatory', 'userid', 				$users_ids[$userid]);
			$post_object->set_value('mandatory', 'importthreadid', 		hexdec($data['attributes']['unique']));

			$post_object->set_value('nonmandatory', 'visible', 			'1');
			$post_object->set_value('nonmandatory', 'dateline',			strtotime(str_replace('.', ' ', $data['attributes']['created'])));
			$post_object->set_value('nonmandatory', 'allowsmilie', 		'1');
			$post_object->set_value('nonmandatory', 'showsignature', 	'1');
			$post_object->set_value('nonmandatory', 'username', 		$user_names[$userid]);
			$post_object->set_value('nonmandatory', 'ipaddress',		$data['attributes']['sourceIp']);
			$post_object->set_value('nonmandatory', 'title', 			htmlspecialchars($data['attributes']['title']));
			$post_object->set_value('nonmandatory', 'pagetext', 		$this->html_2_bb($data['attributes']['heading']));
			$post_object->set_value('nonmandatory', 'importpostid',		hexdec($data['attributes']['unique']));

			if($post_object->import_post($this->Db_object, $this->target_db_type, $this->target_db_prefix))
			{
				echo("<br /><span class=\"isucc\">Post -- <b>" . $post_object->how_complete() . "%</b></span> :: Post from - " . $post_object->get_value('nonmandatory','username'));
				flush();
			}
			else
			{
				echo "<br />'" . trim($data['attributes']['title']) . " not imported";
			}
			unset($post_object, $thread_object);
		}
		else if ($data['type'] == 'close')
		{
			if ($popdata = $this->pop_first_tag($data['tag_name']))
			{
				//echo "popped $data[tag_name] off<br />\n"; flush();
			}
		}
	}

	function parse_message($data)
	{
		if ($discussion =& $this->get_first_tag('discussion'))
		{
			/*
			echo str_repeat('--', sizeof($this->stack)) . " message<br />\n";
			flush();
			*/
			$user_names	= $this->get_username($this->Db_object, $this->target_db_type, $this->target_db_prefix);
			$users_ids	= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

			$userid = hexdec($data['attributes']['author']);

			$post_object = new ImpExData($this->Db_object, $this->session, 'post');

			$post_object->set_value('mandatory', 'threadid', 			$this->session->get_session_var('currentthread'));
			$post_object->set_value('mandatory', 'userid', 				$users_ids[$userid]);
			$post_object->set_value('mandatory', 'importthreadid', 		'1');

			$post_object->set_value('nonmandatory', 'visible', 			'1');
			$post_object->set_value('nonmandatory', 'dateline',			strtotime(str_replace('.', ' ', $data['attributes']['date'])));
			$post_object->set_value('nonmandatory', 'allowsmilie', 		'1');
			$post_object->set_value('nonmandatory', 'showsignature', 	'1');
			$post_object->set_value('nonmandatory', 'username', 		$user_names[$userid]);
			$post_object->set_value('nonmandatory', 'ipaddress',		$data['attributes']['sourceIp']);
			$post_object->set_value('nonmandatory', 'title', 			htmlspecialchars($data['attributes']['title']));
			$post_object->set_value('nonmandatory', 'pagetext', 		$this->html_2_bb($data['attributes']['body']));
			$post_object->set_value('nonmandatory', 'importpostid',		'1');

			if($post_object->import_post($this->Db_object, $this->target_db_type, $this->target_db_prefix))
			{
				echo("<br /><span class=\"isucc\">Post -- <b>" . $post_object->how_complete() . "%</b></span> :: Post from - " . $post_object->get_value('nonmandatory','username'));
				flush();
			}
			else
			{
				echo "<br />Post not imported";
			}
			unset($post_object);
		}
	}
}
# Autogenerated on : April 15, 2005, 12:49 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
