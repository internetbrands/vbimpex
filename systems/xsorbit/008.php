<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* xsorbit_008 Import Smilie module
*
* @package			ImpEx.xsorbit
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class xsorbit_008 extends xsorbit_000
{
	var $_dependent 	= '001';


	function xsorbit_008(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_smilie'];
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_smilies'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['smilies_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['smilie_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}


			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_smilie']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['smilies_per_page'],'smilieperpage',500));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['smilie_overwrite'] ,"over_write_smilies",1));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('smiliestartat','0');
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Per page vars
		$smilie_start_at		= $sessionobject->get_session_var('smiliestartat');
		$smilie_per_page		= $sessionobject->get_session_var('smilieperpage');
		$class_num				= substr(get_class($this) , -3);
		$over_write_smilies		= $sessionobject->get_session_var('over_write_smilies');


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// If the image category dosn't exsist for the imported smilies, create it
		$imported_smilie_group = new ImpExData($Db_target, $sessionobject, 'imagecategory');

		$imported_smilie_group->set_value('nonmandatory', 'title',			'Imported Smilies');
		$imported_smilie_group->set_value('nonmandatory', 'imagetype',		'3');
		$imported_smilie_group->set_value('nonmandatory', 'displayorder',	'1');


		$smilie_group_id = $imported_smilie_group->import_smilie_image_group($Db_target, $target_database_type, $target_table_prefix);		

		// Get an array of smilie details
		$smilie_array 	= $this->get_xsorbit_smilie_details($Db_source, $source_database_type, $source_table_prefix, $smilie_start_at, $smilie_per_page);

		// Display count and pass time
		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($smilie_array) . " {$displayobject->phrases['smilies']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $smilie_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($smilie_start_at + count($smilie_array)) . "</p>");


		$smilie_object = new ImpExData($Db_target, $sessionobject, 'smilie');


		foreach ($smilie_array as $smilie_id => $smilie_details)
		{
			$try = (phpversion() < '5' ? $smilie_object : clone($smilie_object));
			// Mandatory
			$try->set_value('mandatory', 'smilietext',			$smilie_details['code']);
			$try->set_value('mandatory', 'importsmilieid',		$smilie_id);


			// Non Mandatory
			$try->set_value('nonmandatory', 'title',			$smilie_details['description']);
			$try->set_value('nonmandatory', 'smiliepath',		$smilie_details['filename']);
			$try->set_value('nonmandatory', 'imagecategoryid',	$smilie_group_id);
			$try->set_value('nonmandatory', 'displayorder',		$smilie_details['smileyOrder']);

			$it_is_a_duplicate = $this->does_smilie_exists($Db_target, $target_database_type, $target_table_prefix, addslashes($pass_array['code']));

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
			
			// Check if smilie object is valid
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
			unset($try);
		}// End foreach


		// Check for page end
		if (count($smilie_array) == 0 OR count($smilie_array) < $smilie_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_smilie','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}


		$sessionobject->set_session_var('smiliestartat',$smilie_start_at+$smilie_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : February 13, 2006, 3:37 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
