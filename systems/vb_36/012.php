<?php
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
* vb_36_012 Import Customprofilepic module
*
* @package			ImpEx.vb_36
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class vb_36_012 extends vb_36_000
{
	var $_dependent 	= '004';

	function vb_36_012(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_cust_pic'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_custom_pics'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['customprofilepics_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['customprofilepic_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_customprofilepic']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['cust_pics_per_page'],'custompicsperpage',50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('customprofilepicstartat','0');
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
		$customprofilepic_start_at	= $sessionobject->get_session_var('customprofilepicstartat');
		$customprofilepic_per_page	= $sessionobject->get_session_var('custompicsperpage');
		$class_num					= substr(get_class($this) , -3);
		$idcache 					= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$customprofilepic_object 	= new ImpExData($Db_target, $sessionobject, 'customprofilepic');


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		$customprofilepic_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $custom_pics_start_at, $custom_pics_per_page, 'customprofilepic', 'userid');
		$users_ids = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);



		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($customprofilepic_array) . " {$displayobject->phrases['cust_pics']}</h4>");

		foreach ($customprofilepic_array as $cust_pic_id => $cus_pic)
		{
			$try = (phpversion() < '5' ? $customprofilepic_object : clone($customprofilepic_object));

			$try->set_value('mandatory', 'importcustomprofilepicid',	$cust_pic_id);

			$try->set_value('nonmandatory', 'userid',					$idcache->get_id('user', $cus_pic['userid']));
			$try->set_value('nonmandatory', 'filedata',					$Db_target->escape_string($cus_pic['profilepicdata']));
			$try->set_value('nonmandatory', 'dateline',					$cus_pic['dateline']);
			$try->set_value('nonmandatory', 'filename',					$cus_pic['filename']);
			$try->set_value('nonmandatory', 'visible',					$cus_pic['visible']);

			if($try->is_valid())
			{
				if($try->import_custom_profile_pic($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . ' %</b></span> ' . $displayobject->phrases['cus_pic'] . ' -> ' . $try->get_value('nonmandatory', 'filename'));
					$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $cust_pic_id, $displayobject->phrases['custom_profile_pic_not_imported'], $displayobject->phrases['custom_profile_pic_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['custom_profile_pic_not_imported']} :-> " . $try->_failedon);
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $cust_pic_id, $displayobject->phrases['invalid_object'], $try->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />Invalid avatar object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($customprofilepic_array) == 0 OR count($customprofilepic_array) < $customprofilepic_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_customprofilepic','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
		}

		$sessionobject->set_session_var('customprofilepicstartat',$customprofilepic_start_at+$customprofilepic_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : August 9, 2006, 2:39 pm
# By ImpEx-generator 2.1.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
