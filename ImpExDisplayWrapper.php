<?php
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/liceNse.html # ||
|| ####################################################################
\*======================================================================*/
/**
* The Display Wrapper object
*
* Calls the vB3 admincp funtions where avaiable to override when not being
* run stand alone.
*
*
* @package 		ImpEx
* @version		$Revision: $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

if (!class_exists('ImpExDisplay')) { die('Direct class access violation'); }

class ImpExDisplayWrapper extends ImpExDisplay
{
	function ImpExDisplayWrapper()
	{
	}

	function call($function_name, $params = null)
	{
		require_once('./includes/functions.php');
		require_once('./includes/adminfunctions.php');
		
		// If NULL set it to an empty array
		$params = (is_null($params) == true ? array() : $params);
		
		return call_user_func_array($function_name, $params);
	}

	function display_now($var)
	{
		echo $var;
	}

	function page_header()
	{
		if ($this->_screenbasic['donehead'] != 'FALSE')
		{
			return '';
		}

		if (!$this->_screenbasic['title'] OR $this->_screenbasic['title'] == 'NONE')
		{
			global $vbphrase;
			if (isset($vbphrase['import']))
			{
				$outtitle = "$vbphrase[import] / $vbphrase[export]";
			}
			else
			{
				$outtitle = 'Import / Export';
			}
		}
		else
		{
			$outtitle = $this->_screenbasic['title'];
		}

		$this->call('print_cp_header', array($outtitle, $this->_screenbasic['autosubmit'] != '0' ? 'document.name.submit();' : ''));
		$this->_screenbasic['donehead'] = 'TRUE';

		$string .= '<style type="text/css">.isucc { color: green; } .ifail { color: red; }</style>';

		$string .= "\n" . '<b>' . $this->phrases['build_version'] . $this->_build_version . '</b>';
		
		return $string;
	}

	function page_footer()
	{
		$this->call('print_cp_footer');
	}


	function update_html($function_called)
	{
		if (!empty($function_called) AND method_exists($this, $function_called))
		{
			return $this->$function_called;
		}
	}
	function make_input_code($title, $name, $value = '',$htmlise = 1,$size = 35)
	{
		$this->call('print_input_row', array($title, $name, $value));
	}

	function make_table_header($title, $htmlise = 1, $colspan = 2, $anchor = '', $align = 'center', $helplink = 1)
	{
		$this->call('print_table_header', array($title, $colspan, $htmlise, $anchor, $align, $helplink));
	}

	function get_row_bg()
	{
		return 1;
	}

	function do_form_header($phpscript = '', $action, $uploadform = false ,$addtable = true, $name = 'cpform', $width = '90%', $target = '', $echobr = true, $method = 'post')
	{
		$this->call('print_form_header', array($phpscript, $action, $uploadform, $addtable, $name, $width, $target, $echobr, $method));
	}

	function do_form_footer($submitname = '', $resetname = '_default_', $colspan = 2, $goback = '', $extra = '')
	{
		$this->call('print_submit_row', array($submitname , $resetname , $colspan , $goback , $extra));
	}

	function make_description($text, $htmlise = 0, $colspan = 2, $class = '', $align = '')
	{
		$this->call('print_description_row', array($text, $htmlise = 0, $colspan = 2, $class = '', $align = ''));
	}

	function make_yesno_code($title, $name, $value = 1, $onclick = '')
	{
		$this->call('print_yes_no_row', array($title, $name, $value, $onclick));
	}

	function make_hidden_code($name, $value = '', $htmlise = 1)
	{
		/*
		if ($htmlise)
		{
			$value=htmlspecialchars($value);
		}
		$return_string = "\n<input type=\"hidden\" name=\"$name\" value=\"$value\" />";

		echo $return_string;
		*/

		$this->call('construct_hidden_code', array($name, $value, $htmlise));
	}
}

/*======================================================================*/
?>
