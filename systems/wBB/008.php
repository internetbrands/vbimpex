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
* wBB Import Smilies
*
* @package 		ImpEx.wBB
*
*/
class wBB_008 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Smilies';

	function wBB_008()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_smilies'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Smilies have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_smilies",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import smilies');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('smilies','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Smilies'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import smilies from your wBB board. Please remember to move the phpBB smilie images into the vB smilies directory ( images/smilies/).</p>"));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$displayobject->update_html($displayobject->make_yesno_code("Would you like the wBB smilies to over write the vB ones if there is a duplication ?","over_write_smilies",1));
			$displayobject->update_html($displayobject->do_form_footer("Import smilies"));

			$sessionobject->add_session_var('postsstartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($sessionobject->get_session_var('smilies') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$over_write_smilies		= $sessionobject->get_session_var('over_write_smilies');

			$smilie_array 			= $this->get_wBB_smilie_details($Db_source, $source_database_type, $source_table_prefix);

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

			$displayobject->display_now("<h4>Importing " . count($smilie_array) . " smilies.</h4>");

			$smilie_object = new ImpExData($Db_target, $sessionobject, 'smilie');

			foreach ($smilie_array as $smilie_id => $smilie)
			{
				$try = (phpversion() < '5' ? $smilie_object : clone($smilie_object));
				$import_smilie = false;

				// Set the correct key names to pass.
				$pass_array = array(
					'title'			=> $smilie['smilietitle'],
					'smilietext' 	=> $smilie['smiliecode'],
					'smiliepath' 	=> $smilie['smiliepath']
				);

				$pass_array['smiliepath'] =  substr(strrchr($pass_array['smiliepath'], "/"), 1);

				// Check the lenght of it
				if(strlen($pass_array['smilietext']) > 20)
				{
					$truncation = substr($pass_array['smilietext'],0,19) . ':';

					$displayobject->display_now("<br /><font color=\"red\"><b>Too long</font></b> '  " . $pass_array['smilietext']  . "'"  .
												"<br /><font color=\"red\"><b>Truncating to</font></b> '" . $truncation . "'");

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
							$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Smilie  -> " . $try->get_value('mandatory','smilietext'));
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							$imported = true;
						}
						else
						{
							$sessionobject->add_error('warning', $this->_modulestring,
													 get_class($this) . "::import_smilie failed for " . $pass_array['smilietext'] . ".",
													 'Check database permissions and smilie table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$displayobject->display_now("<br />Got smilie " . $pass_array['smilietext'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
						}
					}
					else
					{
						$displayobject->display_now("<br /><font color=\"orange\"><b>Duplicate</span> '  " . $pass_array['smilietext'] . "' -> '" . $pass_array['title'] . "'");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid smilie object, skipping." . $try->_failedon);
				}
				unset($try);
			}


		$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
		$sessionobject->remove_session_var($class_num . '_start');

		$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																	$sessionobject->return_stats($class_num, '_time_taken'),
																	$sessionobject->return_stats($class_num, '_objects_done'),
																	$sessionobject->return_stats($class_num, '_objects_failed')
																	));

		$sessionobject->set_session_var($class_num,'FINISHED');
		$sessionobject->set_session_var('smilies','done');
		$sessionobject->set_session_var('module','000');
		$sessionobject->set_session_var('autosubmit','0');
		$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
	}
}
/*======================================================================*/
?>
