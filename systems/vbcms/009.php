<?php

if (!defined('IDIR')) {
	die;
}
/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin [#]version[#] - Licence Number [#]license[#]
  || # ---------------------------------------------------------------- # ||
  || # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

/**
 * vbcms 008 Import Section Order
 * 
 * @package         ImpEx.vbcms
 * @version        $Revision: 2255 $
 * @checkedout    $Name:  $
 * @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
 * @copyright     http://www.vbulletin.com/license.html
 *
 */
class vbcms_009 extends vbcms_000 {

	var $_dependent = '006';

	function vbcms_009(&$displayobject) {
		$this->_modulestring = $displayobject->phrases['import_cms_section_order'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_cms_section_orders'))
				{
					$displayobject->display_now("<h4>{$displayobject->phrases['cms_section_orders_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['cms_section_order_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_section_order']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_section_order']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 100));
			#$displayobject->update_html($displayobject->make_input_code('Enter the node type you want to import as an article','node_type','page'));
			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('startat', '0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables
		$displayobject->update_basic('displaymodules', 'FALSE');
		$target_database_type = $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix = $sessionobject->get_session_var('targettableprefix');
		$source_database_type = $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix = $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$section_start_at = $sessionobject->get_session_var('startat');
		$section_per_page = $sessionobject->get_session_var('perpage');
		$class_num = substr(get_class($this), -3);

		// Clone and cache
		$section_object = new ImpExData($Db_target, $sessionobject, 'cms_sectionorder', 'cms');

		$idcache = new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		if (!$sessionobject->get_session_var($class_num . '_start')) {
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$section_array = $this->get_cms_section_order($Db_source, $source_database_type, $source_table_prefix, $section_start_at, $section_per_page);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . count($section_array) . " {$displayobject->phrases['section_orders']}</h4>");

		foreach ($section_array as $id => $data)
		{
			$section = (phpversion() < '5' ? $section_object : clone($section_object));

			// Mandatory
			$section->set_value('mandatory', 'importid', 1);
			$section->set_value('mandatory', 'sectionid', $idcache->get_id('cmsnode', $data['sectionid']));
			$section->set_value('mandatory', 'nodeid', $idcache->get_id('cmsnode', $data['nodeid']));
			$section->set_value('nonmandatory', 'displayorder', $data['displayorder']);

			if ($section->is_valid())
			{
				if ($section->import_cms_section_order($Db_target, $target_database_type, $target_table_prefix))
				{
					if (shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br /> <span class="isucc"><b>' . $section->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_sectin_order'] . ' -> ' . $idcache->get_id('cmsnode', $data['sectionid']) . '-' . $idcache->get_id('cmsnode', $data['nodeid']));
					}

					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
					$sessionobject->add_error($Db_target, 'warning', $class_num, $id, $displayobject->phrases['cms_section_order_not_imported'], $displayobject->phrases['cms_section_order_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_section_order_not_imported']}");
				}
			}
			else
			{
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $id, $displayobject->phrases['invalid_object'], $section_order->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $section_order->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
			}
			unset($data);
		}

		if (count($section_array) == 0 OR count($section_array < $section_per_page))
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
							$sessionobject->return_stats($class_num, '_time_taken'),
							$sessionobject->return_stats($class_num, '_objects_done'),
							$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
			$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$sessionobject->set_session_var('startat', $section_start_at + $section_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
		}
	}

// End resume
}

//End Class

/* ======================================================================*\
  || ####################################################################
  || # Downloaded: [#]zipbuilddate[#]
  || # CVS: $RCSfile: 009.php,v $ - $Revision: 2255 $
  || ####################################################################
  \*====================================================================== */
?>