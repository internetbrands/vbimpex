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
* phpBB2 Import Smilies
*
* @package 		ImpEx.phpBB
* @version		$Revision: 1372 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2006-04-03 03:24:38 -0700 (Mon, 03 Apr 2006) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/

class phpBB3_007 extends phpBB3_000
{
	var $_dependent 	= '001';

	function phpBB3_007(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_smilie'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_smilies'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['smilies_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['smmile_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_smile']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['smilies_per_page']));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['smilie_overwrite'] ,"over_write_smilies",1));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

		$over_write_smilies		= $sessionobject->get_session_var('over_write_smilies');

		$smilie_array 			= $this->get_phpbb3_smilie_details($Db_source, $source_database_type, $source_table_prefix);

		$class_num				= substr(get_class($this) , -3);

		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// If the image category dosn't exsist for the imported smilies, create it
		$imported_smilie_group = new ImpExData($Db_target, $sessionobject, 'imagecategory');

		$imported_smilie_group->set_value('nonmandatory', 'title',			'Imported Smilies');
		$imported_smilie_group->set_value('nonmandatory', 'imagetype',		'3');
		$imported_smilie_group->set_value('nonmandatory', 'displayorder',	'1');

		$smilie_group_id = $imported_smilie_group->import_smilie_image_group($Db_target, $target_database_type, $target_table_prefix);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($smilie_array) . " {$displayobject->phrases['smilies']}</h4>");

		$smilie_object = new ImpExData($Db_target, $sessionobject, 'smilie');

		foreach ($smilie_array as $smilie_id => $smilie)
		{
			$try = (phpversion() < '5' ? $smilie_object : clone($smilie_object));
			$import_smilie = false;

			// Set the correct key names to pass.
			$pass_array = array('title'	=> $smilie['emoticon'], 'smilietext' => $smilie['code'], 'smiliepath' => $smilie['smiley_url']);

			// Check the lenght of it
			if(strlen($pass_array['smilietext']) > 20)
			{
				$truncation = substr($pass_array['smilietext'],0, 19) . ':';

				$displayobject->display_now("<br /><font color=\"red\"><b>{$displayobject->phrases['too_long']}</b></font> '  " . $pass_array['smilietext']  . "'"  .
											"<br /><font color=\"red\"><b>{$displayobject->phrases['truncating']}</b></font> '" . $truncation . "'");

				$pass_array['smilietext'] = $truncation;
			}

			$pass_array['smilietext'] = addslashes($pass_array['smilietext']);

			// Is it a duplicate ?

			$it_is_a_duplicate = $this->does_smilie_exists($Db_target, $target_database_type, $target_table_prefix, addslashes($pass_array['smilietext']));

			if ($it_is_a_duplicate)				// Its there
			{
				if ($over_write_smilies)		// And want to over write
				{
					$import_smilie = true;
				}
			}
			else								// Its not there so it dosn't matter
			{
				$import_smilie = true;
			}

			$try->set_value('mandatory', 	'smilietext', 		$pass_array['smilietext']);
			$try->set_value('nonmandatory', 'title',			$pass_array['title']);
			$try->set_value('nonmandatory', 'smiliepath', 		$pass_array['smiliepath']);
			$try->set_value('nonmandatory', 'imagecategoryid', 	$smilie_group_id);
			$try->set_value('nonmandatory', 'displayorder', 	'1');
			$try->set_value('mandatory', 	'importsmilieid',	$smilie_id);

			if($try->is_valid())
			{
				if($import_smilie)
				{
					if($try->import_smilie($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: ' . $displayobject->phrases['smilie'] . ' -> ' .  $try->get_value('mandatory','smilietext'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($smilie_id, $displayobject->phrases['smilie_not_imported'], $displayobject->phrases['smilie_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['smilie_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now('<br /><font color="orange"><b>' . $displayobject->phrases['duplicate'] . '</span> ' . $pass_array['smilietext'] . ' -> ' . $pass_array['title']);
				}
			}
			else
			{
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
			}
			unset($try, $pass_array);
		}

		$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

		$displayobject->update_html($displayobject->module_finished($this->_modulestring,
			$sessionobject->return_stats($class_num, '_time_taken'),
			$sessionobject->return_stats($class_num, '_objects_done'),
			$sessionobject->return_stats($class_num, '_objects_failed')
		));

		$sessionobject->set_session_var($class_num,'FINISHED');
		$sessionobject->set_session_var('module','000');
		$sessionobject->set_session_var('autosubmit','0');
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 1372 $
|| ####################################################################
\*======================================================================*/
?>
