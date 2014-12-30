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
* vbcms 004 Import categories
* 
* @package         ImpEx.vbcms
* @version        $Revision: $
* @checkedout    $Name:  $
* @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
* @copyright     http://www.vbulletin.com/license.html
*
*/ 

class vbcms_005 extends vbcms_000
{
    var $_dependent     = '004';

    function vbcms_005(&$displayobject)
    {
        $this->_modulestring = $displayobject->phrases['import_cms_category'];
    }

    function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        if ($this->check_order($sessionobject,$this->_dependent))
        {
            if ($this->_restart)
            {
                if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_cms_categories'))
                {
                    $displayobject->display_now("<h4>{$displayobject->phrases['cms_categories_cleared']}</h4>");
                    $this->_restart = true;
                }
                else
                {
                    $sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['cms_category_restart_failed'], $displayobject->phrases['check_db_permissions']);
                }
            }

            // Start up the table
            $displayobject->update_basic('title', $displayobject->phrases['import_category']);
            $displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
            $displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
            $displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_category']));

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
        $category_start_at		= $sessionobject->get_session_var('startat');
        $category_per_page		= $sessionobject->get_session_var('perpage');
        $class_num				= substr(get_class($this) , -3);

        // Clone and cache
        $category_object			= new ImpExData($Db_target, $sessionobject, 'cms_category', 'cms');
        $idcache				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

        if(!$sessionobject->get_session_var($class_num . '_start'))
        {
            $sessionobject->timing($class_num,'start', $sessionobject->get_session_var('autosubmit'));
        }

		// Get an array data
		$category_array = $this->get_source_data($Db_source, $source_database_type, "{$source_table_prefix}cms_category", 'categoryid', 0, $category_start_at, $category_per_page);

        $displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $category_array['count'] . " categories</h4><p><b>{$displayobject->phrases['from']}</b> : " . $category_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $category_array['lastid'] . "</p>");

        $category_object = new ImpExData($Db_target, $sessionobject, 'cms_category', 'cms');

        foreach ($category_array['data'] as $category_id => $data)
        {
            $category = (phpversion() < '5' ? $category_object : clone($category_object));

			$parentnode = $idcache->get_id('cmsnode', $data['parentnode']);
			if (!$parentnode)
			{
				$parentnode = 1;
			}
            $category->set_value('mandatory',	'importcmscategoryid',  $category_id);
            $category->set_value('mandatory',	 'parentnode',			$parentnode);
            $category->set_value('nonmandatory', 'category',			$data['category']);
			$category->set_value('nonmandatory', 'description',			$data['description']);
            $category->set_value('nonmandatory', 'enabled',				$$data['enabled']);
			$category->set_value('nonmandatory', 'contentcount',		$data['contentcount']);
			$category->set_value('nonmandatory', 'parentcat',           $data['parentcat']);

			if($category->is_valid())
            {
                if($category->import_cms_category($Db_target, $target_database_type, $target_table_prefix))
                {		
					if(shortoutput)
					{
						$displayobject->display_now('.');
					}
					else
					{
						$displayobject->display_now('<br />' . $data['category_id'] . ' <span class="isucc"><b>' . $category->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_category'] . ' -> ' . $data['category']);
					}

					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
                }
                else
                {
                    $sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
                    $sessionobject->add_error($Db_target, 'warning', $class_num, $category_id, $displayobject->phrases['cms_category_not_imported'], $displayobject->phrases['cms_category_not_imported_rem']);
                    $displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_category_not_imported']}");
                }
            }
            else
            {
                $sessionobject->add_error($Db_target, 'invalid', $class_num, $category_id, $displayobject->phrases['invalid_object'], $category->_failedon);
                $displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $category->_failedon);
                $sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
            }
            unset($data);
        }
		
        if (empty($category_array['count']) OR $category_array['count'] < $category_per_page)
        {
            $sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
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
            $sessionobject->set_session_var('startat', $category_array['lastid']);
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