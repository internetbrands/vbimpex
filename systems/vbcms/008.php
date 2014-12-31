<?php

if (!defined('IDIR')) {
	die;
}
/* ======================================================================*\
  || ####################################################################
  || # vBulletin Impex
  || # ---------------------------------------------------------------- # ||
  || # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc. # ||
  || # This code is made available under the Modified BSD License -- see license.txt # ||
 || # http://www.vbulletin.com
  || ####################################################################
  \*====================================================================== */

/**
 * vbcms 008 Import widgets
 * 
 * @package         ImpEx.vbcms
 *
 */
class vbcms_008 extends vbcms_000 {

	var $_dependent = '003';

	function vbcms_008(&$displayobject) {
		$this->_modulestring = $displayobject->phrases['import_cms_widget'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source) {
		if ($this->check_order($sessionobject, $this->_dependent)) {
			if ($this->_restart) {
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_cms_widgets')) {
					$displayobject->display_now("<h4>{$displayobject->phrases['cms_widgets_cleared']}</h4>");
					$this->_restart = true;
				} else {
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['cms_widget_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_widget']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_widget']));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 100));
			#$displayobject->update_html($displayobject->make_input_code('Enter the node type you want to import as an article','node_type','page'));
			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('startat', '0');
		} else {
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source) {
		// Set up working variables
		$displayobject->update_basic('displaymodules', 'FALSE');
		$target_database_type = $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix = $sessionobject->get_session_var('targettableprefix');
		$source_database_type = $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix = $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$widget_start_at = $sessionobject->get_session_var('startat');
		$widget_per_page = $sessionobject->get_session_var('perpage');
		$class_num = substr(get_class($this), -3);

		// Clone and cache
		$widget_object = new ImpExData($Db_target, $sessionobject, 'cms_widget', 'cms');
		$widgetconfig_object = new ImpExData($Db_target, $sessionobject, 'cms_widgetconfig', 'cms');
		$layoutwidget_object = new ImpExData($Db_target, $sessionobject, 'cms_layoutwidget', 'cms');

		$idcache = new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		if (!$sessionobject->get_session_var($class_num . '_start')) {
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		// Get an array data
		$widget_array = $this->get_cms_widget($Db_source, $source_database_type, $source_table_prefix, $widget_start_at, $widget_per_page);

		$displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $widget_array['count'] . " widgets</h4><p><b>{$displayobject->phrases['from']}</b> : " . $widget_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $widget_array['lastid'] . "</p>");

		$widget_object = new ImpExData($Db_target, $sessionobject, 'cms_widget', 'cms');

		foreach ($widget_array['data'] as $widget_id => $data) {
			$widget = (phpversion() < '5' ? $widget_object : clone($widget_object));
			$widgetconfig = (phpversion() < '5' ? $widgetconfig_object : clone($widgetconfig_object));
			$layoutwidget = (phpversion() < '5' ? $layoutwidget_object : clone($layoutwidget_object));

			// Mandatory
			$widget->set_value('mandatory', 'importcmswidgetid', $widget_id);
			$widget->set_value('mandatory', 'widgettypeid', $data['widgettypeid']);
			$widget->set_value('nonmandatory', 'varname', $data['varname']);
			$widget->set_value('nonmandatory', 'title', $data['title']);
			$widget->set_value('nonmandatory', 'description', $data['description']);

			if ($widget->is_valid()) {
				if ($widget->import_cms_widget($Db_target, $target_database_type, $target_table_prefix, $data, $widgetconfig, $layoutwidget)) {
					if (shortoutput) {
						$displayobject->display_now('.');
					} else {
						$displayobject->display_now('<br />' . $data['widget_id'] . ' <span class="isucc"><b>' . $widget->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_widget'] . ' -> ' . $data['title']);
					}

					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				} else {
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
					$sessionobject->add_error($Db_target, 'warning', $class_num, $widget_id, $displayobject->phrases['cms_widget_not_imported'], $displayobject->phrases['cms_widget_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_widget_not_imported']}");
				}
			} else {
				$sessionobject->add_error($Db_target, 'invalid', $class_num, $widget_id, $displayobject->phrases['invalid_object'], $widget->_failedon);
				$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $widget->_failedon);
				$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
			}
			unset($data);
		}

		if (empty($widget_array['count']) OR $widget_array['count'] < $widget_per_page) {
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
		} else {
			$sessionobject->set_session_var('startat', $widget_array['lastid']);
			$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
		}
	}

// End resume
}

//End Class

/* ======================================================================*\
  || ####################################################################
  || # Downloaded: [#]zipbuilddate[#]
  || # CVS: $RCSfile: 009.php,v $ - $Revision: $
  || ####################################################################
  \*====================================================================== */
?>