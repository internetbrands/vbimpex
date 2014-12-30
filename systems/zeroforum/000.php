<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* zeroforum API module
*
* @package			ImpEx.zeroforum
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class zeroforum_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.1.0';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'zeroforum';
	var $_homepage 	= 'http://www.zeroforum.com/';


	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array ('none');


	function zeroforum_000()
	{
	}


	/**
	* Parses and custom HTML for zeroforum
	*
	* @param	string	mixed			The text to be parse
	*
	* @return	array
	*/
	function zeroforum_html($text)
	{
		$text = preg_replace('#\<TABLE(.*)><TR><TD>Quote, originally posted by (.*)<(.*)quote">(.*)</TD></TR></TABLE>#siU', '[quote=$2]$4[/quote]', $text);

		
		return $text;
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
	function get_zeroforum_forum_details($forum_file)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($forum_file)) { return $return_array; }

		$xmlobj = new XMLparser(false, $forum_file);

		return $xmlobj->parse();
	}

	function get_current_forum_file($forumpath , $current_forum = null)
	{
		$forumfiles = array();
		$counter = 0;

		if (!$handle = opendir($forumpath))
		{
			return false;
		}

		// Read them all into an array.
		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' OR $file == '..')
			{
				continue;
			}
			
			// If its empty return the first one
			if (!$current_forum)
			{
				return $file;
			}
			
			$forumfiles[] = $file;	
		}
		
		if ($current_forum == end($forumfiles))
		{
			return false;
		}
		
		reset($forumfiles);
 
		foreach ($forumfiles as $position => $filename)
		{
			if ($current_forum == $filename)
			{
				return $forumfiles[++$position];
			}
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
	function get_zeroforum_user_details($user_file)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($user_file)) { return $return_array; }

		$xmlobj = new XMLparser(false, $user_file);

		return $xmlobj->parse();
	}
} // Class end



class XMLparser
{
	/**
	* Internal PHP XML parser
	*
	* @var	resource
	*/
	var $xml_parser;
	
	var $xml_file;

	/**
	* Error number (0 for no error)
	*
	* @var	integer
	*/
	var $error_no = 0;

	/**
	* The actual XML data being processed
	*
	* @var	integer
	*/
	var $xmldata = '';

	/**
	* The final, outputtable data
	*
	* @var	array
	*/
	var $parseddata = array();

	/**
	* Intermediate stack value used while parsing.
	*
	* @var	array
	*/
	var $stack = array();

	/**
	* Current CData being parsed
	*
	* @var	string
	*/
	var $cdata = '';

	/**
	* Number of tags open currently
	*
	* @var	integer
	*/
	var $tag_count = 0;

	/**
	* Constructor
	*
	* @param	mixed	XML data or boolean false
	* @param	string	Path to XML file to be parsed
	*/
	function XMLparser($xml, $path = '')
	{
		$this->xml_file = $path;
		
		if ($xml !== false)
		{
			$this->xmldata = $xml;
		}
		else
		{
			if (empty($path))
			{
				$this->error_no = 1;
			}
			else if (!($this->xmldata = @file_get_contents($path)))
			{
				$this->error_no = 2;
			}
		}
	}

	/**
	* Parses XML document into an array
	*
	* @param	string	Encoding of the inputted XML file
	* @param	bool	Empty the XML data string after parsing
	*
	* @return	mixed	array or false on error
	*/
	function &parse($encoding = 'ISO-8859-1', $emptydata = true)
	{
		if (empty($this->xmldata) OR $this->error_no > 0)
		{
			return false;
		}

		$this->xml_parser = xml_parser_create($encoding);

		xml_parser_set_option($this->xml_parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_character_data_handler($this->xml_parser, array(&$this, 'handle_cdata'));
		xml_set_element_handler($this->xml_parser, array(&$this, 'handle_element_start'), array(&$this, 'handle_element_end'));

		xml_parse($this->xml_parser, $this->xmldata);
		
		if ($err = xml_get_error_code($this->xml_parser))
		{
			die(
				"XML parser error (error code $err): " . xml_error_string($err) . 
				"<br>Error occurred at line " . xml_get_current_line_number($this->xml_parser) . 
				", column " . xml_get_current_column_number($this->xml_parser) . 
				", byte offset " .  xml_get_current_byte_index($this->xml_parser) .
				"<br> This is lightly caused by a missing &lt;[CDATA[ ...text... ]] &gt;" .
				"Check the source file, and add it" .
				"<br><b>{$this->xml_file}</b></br>" .
				"<br>Have you run impex/tools/cleaner_zeroforum_xml.php ?"
			);
		}

		if ($emptydata)
		{
			$this->xmldata = '';
			$this->stack = array();
			$this->cdata = '';
		}

		if ($err)
		{
			return false;
		}

		xml_parser_free($this->xml_parser);

		return $this->parseddata;
	}

	/**
	* XML parser callback. Handles CDATA values.
	*
	* @param	resource	Parser that called this
	* @param	string		The CDATA
	*/
	function handle_cdata(&$parser, $data)
	{
		$this->cdata .= $data;
	}

	/**
	* XML parser callback. Handles tag opens.
	*
	* @param	resource	Parser that called this
	* @param	string		The name of the tag opened
	* @param	array		The tag's attributes
	*/
	function handle_element_start(&$parser, $name, $attribs)
	{
		$this->cdata = '';

		foreach ($attribs AS $key => $val)
		{
			if (preg_match('#&[a-z]+;#i', $val))
			{
				$attribs["$key"] = htmlspecialchars_decode($val);
			}
		}

		array_unshift($this->stack, array('name' => $name, 'attribs' => $attribs, 'tag_count' => ++$this->tag_count));
	}

	/**
	* XML parser callback. Handles tag closes.
	*
	* @param	resource	Parser that called this
	* @param	string		The name of the tag closed
	*/
	function handle_element_end(&$parser, $name)
	{
		$tag = array_shift($this->stack);
		if ($tag['name'] != $name)
		{
			// there's no reason this should actually happen -- it'd mean invalid xml
			return;
		}

		$output = $tag['attribs'];

		if (trim($this->cdata) !== '' OR $tag['tag_count'] == $this->tag_count)
		{
			if (sizeof($output) == 0)
			{
				$output = $this->unescape_cdata($this->cdata);
			}
			else
			{
				$this->add_node($output, 'value', $this->unescape_cdata($this->cdata));
			}
		}

		if (isset($this->stack[0]))
		{
			$this->add_node($this->stack[0]['attribs'], $name, $output);
		}
		else
		{
			// popped off the first element
			// this should complete parsing
			$this->parseddata = $output;
		}


		$this->cdata = '';
	}

	/**
	* Returns parser error string
	*
	* @return	mixed error message
	*/
	function error_string()
	{
		return xml_error_string($this->error_code());
	}

	/**
	* Returns parser error line number
	*
	* @return	int error line number
	*/
	function error_line()
	{
		return xml_get_current_line_number($this->xml_parser);
	}

	/**
	* Returns parser error code
	*
	* @return	int error line code
	*/
	function error_code()
	{
		return xml_get_error_code($this->xml_parser);
	}

	/**
	* Adds node with appropriate logic, multiple values get added to array where unique are their own entry
	*
	* @param	array	Reference to array node has to be added to
	* @param	string	Name of node
	* @param	string	Value of node
	*
	*/
	function add_node(&$children, $name, $value)
	{
		if (!is_array($children) OR !in_array($name, array_keys($children)))
		{ // not an array or its not currently set
			$children[$name] = $value;
		}
		else if (is_array($children[$name]) AND isset($children[$name][0]))
		{ // its the same tag and is already an array
			$children[$name][] = $value;
		}
		else
		{  // its the same tag but its not been made an array yet
			$children[$name] = array($children[$name]);
			$children[$name][] = $value;
		}
	}

	/**
	* Adds node with appropriate logic, multiple values get added to array where unique are their own entry
	*
	* @param	string	XML to have any of our custom CDATAs to be made into CDATA
	*
	*/
	function unescape_cdata($xml)
	{
		static $find, $replace;

		if (!is_array($find))
		{
			$find = array('�![CDATA[', ']]�', "\r\n", "\n");
			$replace = array('<![CDATA[', ']]>', "\n", "\r\n");
		}

		return str_replace($find, $replace, $xml);
	}
}
# Autogenerated on : May 23, 2005, 2:32 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
