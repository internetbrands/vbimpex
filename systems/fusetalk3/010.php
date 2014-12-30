<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* fusetalk3_010 Import Author Icons module
*
* @package			ImpEx.fusetalk
* @version			$Revision: 1363 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2006-04-03 01:46:42 -0700 (Mon, 03 Apr 2006) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class fusetalk3_010 extends fusetalk3_000
{
	var $_version 		= '0.0.1';
	var $_modulestring 	= 'Import Avatars';
	var $_dependent 	= '001';

	//Officially it's 1048574, then I substract some for the non-image parts.
	var $max_imagesize = 1048000;

	function get_avatar_categories(&$Db_target, $tableprefix)
	{
		$categories = $Db_target->query("
			SELECT imagecategoryid,title
			FROM " . $tableprefix . "imagecategory
			WHERE imagetype = 1 ORDER BY displayorder
		");
		$cats = array();
		while ($category = $Db_target->fetch_array($categories))
		{
			$cats[$category['imagecategoryid']] = $category['title'];
		}
		return $cats;
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_attachments'))
				{
					$displayobject->display_now('<h4>Imported icons have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_attachments','Check database permissions');
				}
			}

			$tableprefix = $sessionobject->get_session_var('targettableprefix');
			$categories = $this->get_avatar_categories($Db_target, $tableprefix);

			if (empty($categories))
			{
				$displayobject->update_html('<strong>You need to have some categories before I can do an import</strong><br />');
				// End the table
				$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));
				return;
			}

			// Start up the table
			$displayobject->update_basic('title','Import Icons');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_attachment','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Author Icons to import per cycle (must be greater than 1)','attachmentperpage',50));
			$displayobject->update_html($displayobject->make_input_code('Full path to FuseTalk Author Icons folder', 'fusetalk_avatarsfolder',$sessionobject->get_session_var('fusetalk_avatarsfolder'),1,60));

			$avatarsfolder = ($sessionobject->get_session_var('avatarsfolder'))? $sessionobject->get_session_var('avatarsfolder') : 'images/avatars';

			$displayobject->update_html($displayobject->make_input_code('Path to vBulletin avatar folder<br/>This file path should be readable AND writeable by your web server (usually chmod 0777)',
				'avatarsfolder',	$avatarsfolder,1,60));

			$displayobject->update_html($displayobject->make_select_input_code('Category for avatars', 'categoryid', $categories));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('attachmentstartat','0');
			$sessionobject->add_session_var('attachmentdone','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}


	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Per page vars
		$attachment_start_at			= $sessionobject->get_session_var('attachmentstartat');
		$attachment_lastid			= $sessionobject->get_session_var('last_attachid');
		$attachment_per_page			= $sessionobject->get_session_var('attachmentperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		//validate the user-entered settings.
		$dir = $sessionobject->get_session_var('fusetalk_avatarsfolder');

		$upload_ok = true;
		if ($dir == '' OR !file_exists($dir) OR (!is_dir($dir)))
		{
			$displayobject->display_now("$dir must be a folder, it must be accessible to the process, and it must be readable. Please enter a valid path.");
			$upload_ok = false;
		}

		$avatarsfolder = $sessionobject->get_session_var('avatarsfolder');
		//we expect something like 'images/avatars'. We will normally be in <forum root>/admincp.
		// So we would want something like '../../images/avatars


		if (!is_dir($avatarsfolder) AND is_dir('../' . $avatarsfolder))
		{
			$avatar_loc = '../';
		}
		else if (!is_dir($avatarsfolder) AND is_dir('../../' . $avatarsfolder))
		{
			$avatar_loc = '../../';
		}
		else
		{

		}

		if ($avatarsfolder == '' OR !is_dir($avatar_loc . $avatarsfolder))
		{
			$displayobject->display_now("The avatars folder must be a folder, it must be accessible to the process, and it must be writeable. Please enter a valid local path.");
			$upload_ok = false;
		}

		$categoryid = $sessionobject->get_session_var('categoryid');
		if (!categoryid)
		{
			$displayobject->display_now("Please go back and select a category ");
			$upload_ok = false;
		}

		if ($upload_ok)
		{
			// Get an array of icon details
			$icon_array = $this->get_fusetalk_authoricon_details($Db_source, $source_database_type, $source_table_prefix, $attachment_lastid, $attachment_per_page);

			//We're going to need to map fusetalk userids to vb user ids
			$userids =  $this->get_done_user_ids($Db_target, $target_database_type, $target_table_prefix);

			//Let's make sure both folders end in '/';
			if (substr($dir,-1,1) != '/')
			{
				$dir .= '/';
			}

			if (substr($avatarsfolder,-1,1) != '/')
			{
				$avatarsfolder .= '/';
			}

			$icon_object = new ImpExData($Db_target, $sessionobject, 'avatar');

			foreach ($icon_array as $icon_id => $icon_details)
			{
				if (!is_file($dir . '\\' . $icon_details['vchiconfilename']))
				{
					$displayobject->display_now("<br />\nSource file not found  :: " . $dir . '\\' . $icon_details['vchiconfilename']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					continue;
				}
				$filesize = filesize($dir . '/' . $icon_details['vchiconfilename']);

				if ($filesize > $this->max_imagesize)
				{
					$displayobject->display_now("<br />\nAttachment too large for insertion :: " . $icon_details['vchiconfilename']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					continue;
				}

				$try = (phpversion() < '5' ? $icon_object : clone($icon_object));
				$sourcefile = $dir . $icon_details['vchiconfilename'];
				$targetfile = $avatarsfolder . $icon_details['vchiconfilename'];
				$try->set_value('nonmandatory', 'avatarpath', $targetfile);
				$try->set_value('nonmandatory', 'imagecategoryid', $categoryid);
				$try->set_value('nonmandatory', 'title', $icon_details['vchiconname']);
				$try->set_value('mandatory', 'importavatarid', $icon_details['iiconid']);
				$targetfile = $avatar_loc . $targetfile;

				$result = $try->copy_avatar($Db_target, $target_database_type, $target_table_prefix, $sourcefile, $targetfile);

				if ($result)
				{
					$displayobject->display_now("<br />\nImported icon  :: " . $dir . '\\' . $icon_details['vchiconfilename']);
					$sessionobject->set_session_var($class_num . '_objects_done',$sessionobject->get_session_var($class_num. '_objects_done') + 1 );
				}
				else
				{
					$displayobject->display_now("<br />\nUnable to import file :: " . $dir . '\\' . $icon_details['vchiconfilename']);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}

			}// End foreach

			// Check for page end
			if (count($icon_array) == 0 OR count($icon_array) < $attachment_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));
				$sessionobject->set_session_var($class_num ,'FINISHED');
				$sessionobject->set_session_var('import_attachment','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			$sessionobject->add_session_var('last_attachid', $icon_details['iiconid']);
			$sessionobject->set_session_var('attachmentstartat',$attachment_start_at+$attachment_per_page);
		}
		else
		{
			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('autosubmit','0');
			
		}

		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1363 $
|| ####################################################################
\*======================================================================*/
?>
