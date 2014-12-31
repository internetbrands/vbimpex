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
* The Display object
*
* Currently HTML, just ;)
*
*
*
* @package 		ImpEx
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

class ImpExDisplay extends ImpExFunction
{
	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '0.0.1';

	var $_build_version = '1.104';

	var $_target_versions = array (
		'forum' => array(
			'400' 		=> 'vBulletin 4.0.* - 4.1.*',
			'360' 		=> 'vBulletin 3.7.* &amp; 3.8.*',
			'350' 		=> 'vBulletin 3.5.*',
			'309' 		=> 'vBulletin 3.0.*'
			),
		'blog' => array(
			'blog10' 	=> 'vBulletin Blog 1.0.*',
			'blog40' 	=> 'vBulletin Blog 4.0.* - 4.1.*'
			),
		'cms' => array(
			'cms10' 	=> 'vBulletin Suite CMS 4.0.* - 4.1.*'
			),
		);

	/**
	* Store for display HTML
	*
	* Hold all the HTML during the life of the object till display is called
	*
	* @var    string
	*/
	var $_screenstring ='';

	/**
	* Internal flags for the display functions
	*
	* Various state flags for the display
	*
	*
	* @var    string
	*/
	var $_screenbasic =  array(
		'title' 			=>		'Import / Export',
		'pageHTML'			=>		'',
		'showwarning'		=>		'TRUE',
		'warning'			=>		'',
		'system'			=>		'NONE',
		'choosesystem'		=>		'FALSE',
		'displaylinks'		=>		'TRUE',
		'autosubmit'		=>		'0',
		'donehead'			=>		'FALSE',
		'displaymodules'	=>		'TRUE'
	);

	function ImpExDisplay()
	{
	}

	/**
	* Retrives the values needed to define a ImpExData object
	*
	* @param	string	mixed	An accessor that appends a string (HTML) onto the pageHTML
	*
	* @return	boolean
	*/
	function update_html($html)
	{
		$this->_screenbasic['pageHTML'] .= $html;
		return TRUE;
	}

	/**
	* Retrives the values needed to define a ImpExData object
	*
	* @param	string	mixed	The name of the basic value or flag to update
	* @param	string	mixed	The value to update it with
	*
	* @return	boolean
	*/
	function update_basic($name, $status)
	{
		if (empty($status) OR $this->_screenbasic["$name"] == NULL)
		{
			return FALSE;
		}
		else
		{
			$this->_screenbasic["$name"] = $status;
			return TRUE;
		}
	}

	/**
	* HTML Page code - table text input
	*
	* @param	string	mixed	Input title
	* @param	string	mixed	HTML element name
	* @param	string	mixed	The default value
	* @param	string	mixed	Calls htmlspecialchars on the value
	* @param	string	mixed	The size of the input
	*
	* @return	string 	mixed	The formed HTML
	*/
	function make_input_code($title, $name, $value = '', $htmlise = 1, $size = 35)
	{
		if ($htmlise)
		{
			$value = htmlspecialchars($value);
		}

		return "
			<tr class=\"" . $this->get_row_bg() . "\" valign=\"top\">
				<td>$title</td>
				<td><input type=\"text\" size=\"$size\" name=\"$name\" value=\"$value\" /></td>
			</tr>";
	}

    function make_select_input_code($title, $name, $select_array)
    {
        return "
            <tr class=\"" . $this->get_row_bg() . "\" valign=\"top\">
                <td>$title</td>
                <td>" . $this->make_select($select_array, $name) . "</td>
            </tr>";
    }

	/**
	* HTML Page code - table header
	*
	* @param	string	mixed	Table title
	* @param	string	mixed	HTML anchor name
	* @param	string	mixed	Calls htmlspecialchars on the value
	* @param	string	mixed	The collum span width
	*
	* @return	string 	mixed	The formed HTML
	*/
	function make_table_header($title, $htmlise = 1, $colspan = 2)
	{
		return "
			<tr class=\"thead\">
				<td colspan=\"$colspan\">" . $this->iif($htmlise, htmlspecialchars($title), $title) . "</td>
			</tr>";
	}

	/**
	* Just doing the alt for table rows
	*
	*/
	function get_row_bg()
	{
		if (($bgcounter++ % 2) == 0)
		{
			return 'alt1';
		}
		else
		{
			return 'alt2';
		}
	}

	/**
	* HTML Page code - form header
	*
	* @param	string	mixed	The target of the form
	* @param	string	mixed	The action value
	* @param	int		0|1		whether to use = ENCTYPE=multipart/form-data
	* @param	int		0|1		whether to add the beginings of a table after the <form tag
	*
	* @return	string 	mixed	The formed HTML
	*/
	function do_form_header($phpscript, $action, $uploadform = 0, $addtable = 1, $name = 'name')
	{
		$return_string ='';
		$return_string = "\n<form action=\"$phpscript.php\" " . $this->iif($uploadform, "ENCTYPE=\"multipart/form-data\" ", '') . " name=\"$name\" method=\"post\">";

		if ($addtable == 1)
		{
			$return_string .= "\n<table cellpadding=\"1\" cellspacing=\"0\" border=\"0\" align=\"center\" width=\"90%\" class=\"tblborder\">";
		}
		return $return_string;
	}

	/**
	* HTML Page code - form footer
	*
	* @param	string	mixed	The submit name
	* @param	string	mixed	The reset name
	* @param	int		mixed	The collum span width
	* @param	string	mixed	Text for the back button ( onclick="history.back(1)" )
	*
	* @return	string 	mixed	The formed HTML
	*/
	function do_form_footer($submitname = 'Submit', $resetname = 'Reset', $colspan = 2, $goback = '')
	{
		$tableadded = 1;
		$return_string = '';

		$return_string = $this->iif($tableadded == 1, "\n\t<tr id='submitrow'>\n\t<td colspan='$colspan' align='center'>", "<p><center>");
		$return_string .= "\n\t<p id='submitrow'>\n\t<input type=\"submit\" value=\"   $submitname   \" accesskey=\"s\" />";

		if ($resetname != '')
		{
			$return_string .= "\n\t<input type=\"reset\" value=\"   $resetname   \" />\n";
		}
		if ($goback != '')
		{
			$return_string .= "\n\t<input type=\"button\" value=\"   $goback   \" onclick=\"history.back(1)\" />\n";
		}
		$return_string .= $this->iif($tableadded == 1, "</p></td>\n</tr>\n</table>\n</td>\n</tr>\n</table>\n", "</p></center>\n");
		$return_string .= "\n</form>";

		return $return_string;
	}

	/**
	* HTML Page code - form footer
	*
	* @param	string	mixed	The submit name
	* @param	string	mixed	The reset name
	* @param	int		mixed	The collum span width
	* @param	string	mixed	Text for the back button ( onclick="history.back(1)" )
	*
	* @return	string 	mixed	The formed HTML
	*/
	function make_description($text, $htmlise = 0)
	{
		$return_string = "<tr class='" . $this->get_row_bg() . "' valign='top'><td colspan='2'>" . $this->iif($htmlise == 0, $text, htmlspecialchars($text)) . "</td></tr>\n";
		return $return_string;
	}

	/**
	* HTML Page code - form footer
	*
	* @param	string	mixed	The hidden value name
	* @param	string	mixed	The value
	* @param	int		1|0		htmlspecialchars($value)
	*
	* @return	string 	mixed	The formed HTML
	*/
	function make_hidden_code($name, $value = '', $htmlise = 1)
	{
		if ($htmlise)
		{
			$value = htmlspecialchars($value);
		}
		$return_string = "\n<input type=\"hidden\" name=\"$name\" value=\"$value\" />";

		return $return_string;
	}

	/**
	* HTML Page code - yes no
	*
	* @param	string	mixed	The title of the radio group
	* @param	string	mixed	The name of the value
	* @param	int		1|0		The inital setting of the yes / no
	*
	* @return	string 	mixed	The formed HTML
	*/
	function make_yesno_code($title, $name, $value = 1)
	{
		// Makes code for input buttons yes\no similar to make_input_code
		$string =
			"<tr class='" . $this->get_row_bg() . "' valign='top'>" .
			"<td><p>$title</p></td>\n<td><p>Yes<input type='radio' name='$name' value='1' " .
			$this->iif($value == 1 OR ($name == 'pmpopup' AND $value == 2), 'checked="checked"', '') . " /> No <input type='radio' name='$name' value='0' " .
			$this->iif($value == 0, 'checked="checked"', '') . ' />' .
			$this->iif($value == 2 AND $name == 'customtitle', " User Set (no html)<input type='radio' name='$name' value='2' checked=\"checked\" />", '') .
			"</p></td>\n</tr>";

		return $string;
	}


	/**
	* HTML Page code - displays table with the states of the current modules and buttons depending on
	* the state of the object (not run, running, run). If a object is running no like code is generated
	*
	* @param	object	sessionobject	The current session object
	*
	* @return	string 	mixed	The formed HTML
	*/
	function display_modules(&$sessionobject)
	{
		$string = '<table class="tborder" cellpadding="6" cellspacing="0" border="0" align="center" width="90%">';
		$_done_objects 		= 0;
		$_failed_objects 	= 0;
		$_time_taken 		= 0;

		if ($this->_screenbasic['displaylinks'] == 'TRUE')
		{
			$string .= "
				<tr>
					<td class=\"tcat\" colspan=\"6\" align=\"center\"><strong>" . $this->phrases['title'] . " :: " . $sessionobject->_session_vars['system'] . "</strong></td>
				</tr>
				<tr align=\"center\">
					<td class=\"thead\" colspan=\"2\" align=\"left\">" . $this->phrases['module'] . "</td>
					<td class=\"thead\">" . $this->phrases['action'] . "</td>
					<td class=\"thead\">" . $this->phrases['successful'] . "</td>
					<td class=\"thead\">" . $this->phrases['failed'] . "</td>
					<td class=\"thead\" align=\"right\">" . $this->phrases['timetaken'] . "</td>
				</tr>";
		}

		// -1 at the moment to take care of the 000.php module
		$num_modules = $sessionobject->get_number_of_modules();

		for ($i = 1; $i <= $num_modules - 3; $i++)
		{

// TODO: The clean up modules, loaded in index
#			// Look for the final two
#			if ($i == $num_modules -2)
#			{
#				$position = '901';
#			}
#			elseif ($i == $num_modules -1)
#			{
#				$position = '910';
#			}
#			else
#			{
				$position = str_pad($i, 3, '0', STR_PAD_LEFT);
#			}

			$taken = 0;
			if ($this->_screenbasic['displaylinks'] == 'TRUE')
			{
				if (intval($sessionobject->return_stats($position, '_time_taken')) > 60)
				{
					$taken = intval($sessionobject->return_stats($position, '_time_taken') / 60) . ' min(s)';
				}
				else
				{
					$taken = intval($sessionobject->return_stats($position, '_time_taken')) . ' sec(s)';
				}
				$string .= "
					<tr align=\"center\">
						<td class=\"alt2\" align=\"left\">$position</td>
						<td class=\"alt1\" align=\"left\">" . $sessionobject->get_module_string($position) . "</td>
						<td class=\"alt2\">
							<form action=\"index.php\" method=\"post\" style=\"display:inline\">
								<input type=\"hidden\" name=\"module\" value=\"$position\" />
								<input type=\"submit\" value=\"" . $this->iif(($sessionobject->get_session_var($position) == 'FINISHED'), $this->phrases['redo'], $this->phrases['start_module']) . "\" />
							</form>
						</td>
						<td class=\"alt1\">" . $sessionobject->return_stats($position, '_objects_done') . "</td>
						<td class=\"alt2\">" . $sessionobject->return_stats($position, '_objects_failed') . "</td>
						<td class=\"alt1\" align=\"right\">$taken</td>
					</tr>";

				$_time_taken 		+= intval($sessionobject->return_stats($position, '_time_taken'));
				$_done_objects 		+= intval($sessionobject->return_stats($position, '_objects_done'));
				$_failed_objects 	+= intval($sessionobject->return_stats($position, '_objects_failed'));
			}
			else
			{
				$string .= "
					<tr>
						<td><b>$position</b> " . $sessionobject->get_module_string($position) . "</td>
						<td>" . $this->_modules["$position"] . "</td>
					</tr>";
			}
		}

		if ($this->_screenbasic['displaylinks'] == 'TRUE')
		{
				if($_time_taken > 60)
				{
					$_time_taken = ($_time_taken / 60);
					$_append = $this->phrases['minute_title'];
				}
				else
				{
					$_append = $this->phrases['seconds_title'];
				}
			$string .= "
				<tr>
					<td class=\"tfoot\" colspan=\"3\" align=\"right\"><strong>" . $this->phrases['totals'] . "</strong></td>
					<td class=\"tfoot\" align=\"center\"><strong>$_done_objects</strong></td>
					<td class=\"tfoot\" align=\"center\"><strong>$_failed_objects</strong></td>
					<td class=\"tfoot\" align=\"right\"><strong>" . round($_time_taken, 2) . $_append . "</strong></td>
				</tr>";
		}

		$string .= '</table>';

		return $string;
	}

	/**
	* Class version - Finds a module version by includeing the file and creating one then accessing the
	* local version number (need to be updated to use an accessor)
	*
	* @see choose_system
	*
	* @param	strinf	systemname	the subdirectory that comes after systems/
	* @param	int		XXX			A three digit number corrisponding to the module number that you are quering
	*
	* @return	string|boolean 	mixed	The formed HTML
	*/
	function module_ver($file, $num)
	{
		$modulepath = 'impex/systems/' . $file . '/' . $num . '.php';

		$details = array('title' => '', 'version' => '', 'homepage' => '');
		$tit = $ver = $hom = FALSE;

		if (file_exists($modulepath))
		{
			$base_file = file($modulepath);
		}
		else
		{
			return false;
		}

		$details['product'] = 'forum';

		foreach($base_file as $line)
		{
			$line = trim($line);

			if(strpos($line, '$_product'))
			{
				$details[product] = substr($line, strpos($line,"'")+1, -2);
				#$prod = true;
			}

			if(strpos($line, '$_modulestring'))
			{
				$details[title] = substr($line, strpos($line,"'")+1, -2);
				$tit = true;
			}

			if(strpos($line, '$_version'))
			{
				$details[version] = substr($line, strpos($line,"'")+1, -2);
				$ver = true;
			}

			if(strpos($line, '$_homepage'))
			{
				$details[homepage] = substr($line, strpos($line,"'")+1, -2);
				$hom = true;
			}

			if($tit AND $ver AND $hom)
			{
				continue;
			}
		}

		unset($base_file);

		return $details;
	}

	/**
	* Choose system - Lists the aviable systems to be imported from depending on what is in systems/
	* local version number (need to be updated to use an accessor)
	*
	*
	* @param	object	sessionobject	The current session object
	*
	* @return	string 	mixed			The formed HTML
	*/
	function choose_system(&$sessionobject)
	{
		$return  = $this->do_form_header('index', 'post');
		$return .= $this->make_table_header('');
		$return .= $this->make_hidden_code('module', '000');
		$form .= $this->phrases['select_system'] . '   <select name="system">';
		$each = '<hr><h4 align="center">' . $this->phrases['installed_systems'] . '</h4><table width="100%">';


		$systems_list = array();
		$system_details = array();

		if ($handle = opendir(IDIR . '/systems'))
		{
			while (false !== ($file = readdir($handle)))
			{
				#if ($file=='vBlogetin') { continue; }
				if ($file[0] != '.' AND $file != '.svn' AND $file != 'index.html' AND substr($file, -4) != '.zip' )
				{
					if ($details = $this->module_ver($file, '000'))
					{
						$system_details["$file"] = $details;

						$title = $details['title'];
						$product = $details['product'];

						if (doubleval($details['version']))
						{
							$title .= " ($details[version])";
						}
					}
					else
					{
						$title = $file;
					}

					$systems_list[$product]["$file"] = $title;
				}
			}
			closedir($handle);
		}


		foreach($systems_list as $id => $product)
		{
			natcasesort($systems_list[$id]);
		}

		$system_count = min(3, count($systems_list));
		$each .= '<tr>';

		do
		{
			$each .= '<th>System</th><th>Version</th>';
			$system_count--;
		}
		while($system_count > 0);

		$each .= '</tr><tr>';

		$i = 1;
			$rows = 0;

		foreach ($systems_list AS $product => $product_array)
		{
			$form .= '<optgroup label="'.$product.'">';

			foreach ($product_array AS $file => $title)
			{
				$form .= '<option value="' . $file . '">' . $i . ' . ' . $title . '</option>';

				if ($system_details["$file"])
				{
					$details =& $system_details["$file"];
					$rows++;

					$each .=  '
						<td>' . $i . ' . <a target="_blank" href="' . $details['homepage'] .'">' . $details['title'] . '</a></td>
						<td align="center">  <b>' . $details['version'] . '</b></td>
					';

					if($rows == 3)
					{
						$each .= "\n</tr><tr>";
						$rows = 0;
					}
				}

				$i++;
			}
			$form .= '</optgroup>';
		}

		$form .= '</select>';
		$each .= '</table>';

		$to = $this->phrases['select_target_system'] . '<select name="targetsystem">';

		foreach ($this->_target_versions AS $product => $prod_list)
		{
			$to .= '<optgroup label="'.$product.'">';

			foreach($prod_list AS $ver => $text)
			{
				$to .= "<option value=\"{$ver}\">{$text}</option>";
			}

			$to .= '</optgroup>';
		}

		$to .= "</select>";

		$return .= $this->make_description($form, 0, 1, '', 'center');
		$return .= $this->make_description($to, 0, 1, '', 'center');
		$return .= $this->make_description($each, 0, 1, '', 'center');

		$return .= $this->do_form_footer($this->phrases['start_import']);

		return $return;

	}


	/**
	* Outputs the content of the headder before index.php is called, ensures that
	* the <html <head <body tags are out putted correctly and not disrupted by an echo() etc
	*
	* Output is augmented by object state and internal flags
	*
	* @param	string	mixed	The output to be displayed, usally the $_screenstring.
	*
	* @return	string 	mixed			The formed HTML
	*/
	function make_select($select_array, $select_name)
	{
		$return_string = '<select name="' . $select_name . '">';

		foreach ($select_array as $select_value => $select_display)
		{
			$return_string .= '<option value="' . $select_value . '">' . $select_display . '</option>';
		}

		$return_string .= '</select>';

		return $return_string;
	}


	/**
	* Outputs the content of the headder before index.php is called, ensures that
	* the <html <head <body tags are out putted correctly and not disrupted by an echo() etc
	*
	* Output is augmented by object state and internal flags
	*
	* @param	string	mixed	The output to be displayed, usally the $_screenstring.
	*
	* @return	string 	mixed			The formed HTML
	*/
	function display_now($screentext)
	{
		$string = $this->page_header();

		// TODO: Where do we want the modules status ? Probally here and update the interface to be all nice and groovy :)
		// $string .= $this->display_modules();

		$this->_screenbasic['displaymodules'] = 'FALSE';

		$string .= $screentext;
		echo "\n" . $string;
		flush();
	}

	function display_error($screentext)
	{
		$this->display_now($screentext);
	}


	/**
	* HTML Page code - returns the html page header code depending on the internal flag autosubmit
	*
	* @return	string 	mixed	The formed HTML
	*/
	function page_header()
	{
		if ($this->_screenbasic['donehead'] != 'FALSE')
		{
			return '';
		}

		if (!$this->_screenbasic['title'] OR $this->_screenbasic['title'] == 'NONE')
		{
			$outtitle = $this->phrases['title'];
		}
		else
		{
			$outtitle = $this->_screenbasic['title'];
		}

		$css = '<style type="text/css">.isucc { color: green; } .ifail { color: red; }</style><link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />';

		if ($this->_screenbasic['autosubmit'] == '0')
		{
			$string = "<html>\n\t<head>\n\t<title>{$this->_screenbasic['title']}</title>\n\t{$css}\n\t</head>\n\t<body>";
		}
		else
		{
			$string = '<html><head><title>' . $outtitle . '</title>' . $css . '</head><body onload="document.name.submit();">';
		}

		$string .= "\n\t<b>" . $this->phrases['remove'] . "</b>\n";
		$string .= "\n\t<b><br>" . $this->phrases['build_version'] . $this->_build_version . "</b>\n";

		$this->_screenbasic['donehead'] = 'TRUE';

		return $string;
	}

	/**
	* HTML Page code - returns the html page footer code
	*
	* @return	string 	mixed	The formed HTML
	*/
	function page_footer()
	{
		$string = '<br><table align="center"><tr><td><a href="http://www.vbulletin.com/docs/html/impex_cleanup" target="_blank">' . $this->phrases['finished_import'] . '</a></td></tr></table>';
		$string .= '</body></html>';
		return $string;
	}

	/**
	* Main display - returns the current HTML stored in the object
	*
	* @see		display_modules
	* @see		choose_system
	*
	* @param	object	sessionobject	The current session object
	*
	* @return	string 	mixed	The formed HTML
	*/
	function display(&$sessionobject)
	{
		if ($this->_screenbasic['showwarning'] == 'TRUE')
		{
			$string .= $this->_screenbasic['warning'];
		}

		if ($this->_screenbasic['displaymodules'] != 'FALSE')
		{
			$string .= $this->display_modules($sessionobject);
		}

		if ($this->_screenbasic['choosesystem'] == 'TRUE')
		{
			// TODO: here
			#$string .= $this->choose_target_system($sessionobject);
			$string .= $this->choose_system($sessionobject);
		}

		$string .= $this->_screenbasic['pageHTML'];

		return $string;
	}

	/**
	* Return the display string for the module complete
	*
	* @param	string		mixed		The name of the module
	* @param	int			mixed		The seconds taken to complete
	*
	* @return	mixed	string|NULL
	*/
	function module_finished($modulestring, $seconds, $successful, $failed)
	{
		if($seconds <= 1)
		{
			return "<p align=\"center\">{$this->phrases['module']} <b>{$modulestring}</b>. <i>{$this->phrases['successful']}</i>, : 1 {$this->phrases['second']}.</p>
					<p align=\"center\"> {$this->phrases['successful']}: <b>$successful</b>. {$this->phrases['failed']}: <b>$failed</b>.</p>";
		}
		else
		{
			return "<p align=\"center\">{$this->phrases['module']} : <b>$modulestring</b>. <i>{$this->phrases['successful']}</i>, : $seconds {$this->phrases['second']}.</p>
					<p align=\"center\"> {$this->phrases['successful']}: <b>$successful</b>. {$this->phrases['failed']}: <b>$failed</b>.</p>";
		}
	}

	function print_redirect($gotopage, $timeout = 0.5)
	{
		if (step_through)
		{
			return $this->print_redirect_001($gotopage, $timeout);
		}
		else
		{
			// performs a delayed javascript page redirection
			// get rid of &amp; if there are any...
			$gotopage = str_replace('&amp;', '&', $gotopage);

			echo '<p align="center" class="smallfont"><a href="' . $gotopage . '" onclick="clearTimeout(timerID);"></a></p>';
			echo "\n<script type=\"text/javascript\">\n";
			if ($timeout == 0)
			{
				echo "window.location=\"$gotopage\";";
			}
			else
			{
				echo "myvar = \"\"; timeout = " . ($timeout*10) . ";
				function exec_refresh()
				{
					window.status=\"Redirecting\"+myvar; myvar = myvar + \" .\";
					timerID = setTimeout(\"exec_refresh();\", 100);
					if (timeout > 0)
					{ timeout -= 1; }
					else { clearTimeout(timerID); window.status=\"\"; window.location=\"$gotopage\"; }
				}
				exec_refresh();";
			}
			echo "\n</script>\n";
		}
	}

	function print_redirect_001($gotopage, $timeout = 0.5)
	{
		$rt = '';
		$rt .= '<div align="center">';
		$rt .= '<FORM METHOD="LINK" ACTION="'.$gotopage.'">';
		$rt .= '<INPUT TYPE="submit" VALUE="' . $this->phrases['continue'] . '">';
		$rt .= '</FORM>';
		$rt .= '</div>';
		echo $rt;
	}


	function print_per_page_pass($count, $datatypename, $startat)
	{
		$to = $count+$startat;

		$rt =  "<h4>{$this->phrases['importing']} {$count} {$datatypename}</h4>";
		$rt .= "<p><b>{$this->phrases['from']}</b> : {$startat} ::  <b>{$this->phrases['to']}</b> : {$to} </p>";

		echo $rt;
	}



}

class CLI_ImpExDisplay extends ImpExDisplay
{
	function update_html($html) { return true; }

	function update_basic($name, $status) { return true; }
	function make_input_code($title, $name, $value = '', $htmlise = 1, $size = 35) { return true; }
	function make_table_header($title, $htmlise = 1, $colspan = 2) { return true; }
	function get_row_bg() { return true; }
	function do_form_header($phpscript, $action, $uploadform = 0, $addtable = 1, $name = 'name') { return true; }
	function do_form_footer($submitname = 'Submit', $resetname = 'Reset', $colspan = 2, $goback = '') { return true; }
	function make_description($text, $htmlise = 0) { return true; }
	function make_hidden_code($name, $value = '', $htmlise = 1) { return true; }
	function make_yesno_code($title, $name, $value = 1) { return true; }
	function display_modules(&$sessionobject)  { return true; }
	function module_ver($file, $num) { return true; }
	function choose_system(&$sessionobject) { return true; }
	function make_select($select_array, $select_name) { return true; }

	function display_now($screentext)
	{
		echo ".";
		return true;
	}

	function display_error($screentext) { return true; }
	function page_header() { return true; }
	function page_footer() { return true; }
	function display(&$sessionobject) { return true; }
	function module_finished($modulestring, $seconds, $successful, $failed) { return true; }
	function print_redirect($gotopage, $timeout = 0.5) { return true; }
}


/*======================================================================*/
?>
