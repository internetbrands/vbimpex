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
* vbcms 004 Import sections
* 
* @package         ImpEx.vbcms
* @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
*
*/ 

class vbcms_004 extends vbcms_000
{
    var $_dependent     = '003';

    function vbcms_004(&$displayobject)
    {
        $this->_modulestring = $displayobject->phrases['import_cms_section'];
    }

    function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        if ($this->check_order($sessionobject,$this->_dependent))
        {
            if ($this->_restart)
            {
                if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_cms_sections'))
                {
                    $displayobject->display_now("<h4>{$displayobject->phrases['cms_sections_cleared']}</h4>");
                    $this->_restart = true;
                }
                else
                {
                    $sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['cms_section_restart_failed'], $displayobject->phrases['check_db_permissions']);
                }
            }

            // Start up the table
            $displayobject->update_basic('title', $displayobject->phrases['import_section']);
            $displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
            $displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
            $displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_section']));

            // Ask some questions
            $displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 100));

            // End the table
            $displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

            // Reset/Setup counters for this
            $sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
            $sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
            $sessionobject->add_session_var('startat','0');
        }
        else
        {
            // Dependant has not been run
            $displayobject->update_html($displayobject->do_form_header('index',''));
            $displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
            $displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
            $sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
            $sessionobject->set_session_var('module','000');
        }
    }

    function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        // Set up working variables
        $displayobject->update_basic('displaymodules','FALSE');
        $target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
        $target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
        $source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
        $source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

        // Per page vars
        $section_start_at		= $sessionobject->get_session_var('startat');
        $section_per_page		= $sessionobject->get_session_var('perpage');
        $class_num				= substr(get_class($this) , -3);

        // Clone and cache
		$node_object			= new ImpExData($Db_target, $sessionobject, 'cms_node', 'cms');
		$nodeinfo_object		= new ImpExData($Db_target, $sessionobject, 'cms_nodeinfo', 'cms');
		$nodeconfig_object		= new ImpExData($Db_target, $sessionobject, 'cms_nodeconfig', 'cms');
		$nodecategory_object	= new ImpExData($Db_target, $sessionobject, 'cms_nodecategory', 'cms');
		$navigation_object		= new ImpexData($Db_target, $sessionobject, 'cms_navigation', 'cms');
        $idcache				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

        if(!$sessionobject->get_session_var($class_num . '_start'))
        {
            $sessionobject->timing($class_num,'start', $sessionobject->get_session_var('autosubmit'));
        }

		// Get an array data
		$section_array = $this->get_cms_section($Db_source, $source_database_type, $source_table_prefix, $section_start_at, $section_per_page);

        $displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $section_array['count'] . " sections</h4><p><b>{$displayobject->phrases['from']}</b> : " . $section_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $section_array['lastid'] . "</p>");

		if ($style = $Db_target->query_first("
			SELECT styleid
			FROM {$target_table_prefix}cms_node
			WHERE parentnode = 0 or ISNULL(parentnode)
		"))
		{
			$styleid = $style['styleid'];
		}
		else 
		{
			$styleid = $this->get_options_setting($Db_target, $target_database_type, $target_table_prefix, 'styleid');
		}

        foreach ($section_array['data'] as $nodeid => $data)
        {
			$node =  (phpversion() < '5' ? $node_object : clone($node_object));
			$nodeinfo =  (phpversion() < '5' ? $nodeinfo_object : clone($nodeinfo_object));
			$nodeconfig =  (phpversion() < '5' ? $nodeconfig_object : clone($nodeconfig_object));
			$nodecategory =  (phpversion() < '5' ? $nodecategory_object : clone($nodecategory_object));
			$navigation =  (phpversion() < '5' ? $navigation_object : clone($navigation_object));

			// Will fix parentnode as the end of the script
			$parentnode = $idcache->get_id('cmsnode', $data['parentnode']);
			if (!$parentnode)
			{
				$parentnode = 1;
			}

			$node->set_value('mandatory', 'importcmsnodeid', $nodeid);
			$node->set_value('nonmandatory', 'parentnode', $parentnode);
			$node->set_value('nonmandatory', 'url', $data['url']);
			$node->set_value('mandatory', 'contenttypeid', $this->get_contenttypeid($Db_target, $target_database_type, $target_table_prefix, 'vbcms', 'Section'));
			$node->set_value('nonmandatory', 'styleid', $data['styleid'] ? intval($styleid) : 0);
			$node->set_value('nonmandatory', 'layoutid', $idcache->get_id('layout', $data['layoutid']));
			$node->set_value('nonmandatory', 'userid', $idcache->get_id('user', $data['userid']));
			$node->set_value('nonmandatory', 'publishdate', $data['publishdate']);
			$node->set_value('nonmandatory', 'setpublish', $data['setpublish']);
			$node->set_value('nonmandatory', 'issection', $data['issection']);
			$node->set_value('nonmandatory', 'onhomepage', $data['onhomepage']);
			$node->set_value('nonmandatory', 'permissionsfrom', $data['permissionsfrom']);
			$node->set_value('nonmandatory', 'lastupdated', $data['lastupdated']);
			$node->set_value('nonmandatory', 'publicpreview', $data['publicprview']);
			$node->set_value('nonmandatory', 'auto_displayorder', $data['auto_displayorder']);
			$node->set_value('nonmandatory', 'comments_enabled', $data['comments_enabled']);
			$node->set_value('nonmandatory', 'new', $data['new']);
			$node->set_value('nonmandatory', 'showtitle', $data['showtitle']);
			$node->set_value('nonmandatory', 'showuser', $data['showuser']);
			$node->set_value('nonmandatory', 'showpreviewonly', $data['showpreviewonly']);
			$node->set_value('nonmandatory', 'showupdated', $data['showupdated']);
			$node->set_value('nonmandatory', 'showviewcount', $data['showviewcount']);
			$node->set_value('nonmandatory', 'showcreation', $data['showcreation']);
			$node->set_value('nonmandatory', 'settingsforboth', $data['settingsforboth']);
			$node->set_value('nonmandatory', 'includechildren', $data['includechildren']);
			$node->set_value('nonmandatory', 'hideshowall', $data['hideshowall']);
			$node->set_value('nonmandatory', 'editshowchildren', $data['editshowchildren']);
			$node->set_value('nonmandatory', 'showall', $data['showall']);
			$node->set_value('nonmandatory', 'showpublishdate', $data['showpublishdate']);
			$node->set_value('nonmandatory', 'showrating', $data['showrating']);
			$node->set_value('nonmandatory', 'hidden', $data['hidden']);
			$node->set_value('nonmandatory', 'shownav', $data['shownav']);
			$node->set_value('nonmandatory', 'nosearch', $data['nosearch']);
			$node->set_value('nonmandatory', 'contentid', 0);

            if($node->is_valid())
            {
				$nodeinfo->set_value('nonmandatory', 'description', $data['nodeinfo']['description']);
				$nodeinfo->set_value('nonmandatory', 'title', $data['nodeinfo']['title']);
				$nodeinfo->set_value('nonmandatory', 'html_title', $data['nodeinfo']['html_title']);
				$nodeinfo->set_value('nonmandatory', 'viewcount', $data['nodeinfo']['viewcount']);
				$nodeinfo->set_value('nonmandatory', 'creationdate', $data['nodeinfo']['creationdate']);
				$nodeinfo->set_value('nonmandatory', 'workflowdate', $data['nodeinfo']['workflowdate']);
				$nodeinfo->set_value('nonmandatory', 'workflowstatus', $data['nodeinfo']['workflowstatus']);
				$nodeinfo->set_value('nonmandatory', 'workflowcheckedout', $data['nodeinfo']['workflowcheckedout']);
				$nodeinfo->set_value('nonmandatory', 'workflowpending', $data['nodeinfo']['workflowpending']);
				$nodeinfo->set_value('nonmandatory', 'workflowlevelid', $data['nodeinfo']['workflowlevelid']);
				$nodeinfo->set_value('nonmandatory', 'associatedthreadid',  $idcache->get_id('thread', $data['nodeinfo']['associatedthreadid']));
				$nodeinfo->set_value('nonmandatory', 'keywords', $data['nodeinfo']['keywords']);
				$nodeinfo->set_value('nonmandatory', 'ratingnum', $data['nodeinfo']['ratingnum']);
				$nodeinfo->set_value('nonmandatory', 'ratingtotal', $data['nodeinfo']['ratingtotal']);
				$nodeinfo->set_value('nonmandatory', 'rating', $data['nodeinfo']['rating']);
				
				if ($node->import_cms_section($Db_target, $target_database_type, $target_table_prefix, $data, $nodeinfo, $nodeconfig, $nodecategory, $navigation))
				{
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br />' . $nodeid . ' <span class="isucc"><b>' . $node->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_section'] . ' -> ' . $data['nodeinfo']['title']);
					}

					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error($Db_target, 'warning', $class_num, $nodeid, $displayobject->phrases['cms_node_not_imported'], $displayobject->phrases['cms_node_not_imported_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_node_not_imported']}");
				}
            }
            else
            {
                $sessionobject->add_error($Db_target, 'invalid', $class_num, $nodeid, $displayobject->phrases['invalid_object'], $node->_failedon);
                $displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $node->_failedon);
                $sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
            }
            unset($data);
        }
		
        if (empty($section_array['count']) OR $section_array['count'] < $section_per_page)
        {
            $sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
            $sessionobject->remove_session_var($class_num . '_start');
			$this->update_cms_section_navigation($Db_target, $target_database_type, $target_table_prefix);

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
            $sessionobject->set_session_var('startat', $section_array['lastid']);
            $displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
        }
    }// End resume
}//End Class

/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile: 009.php,v $ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>