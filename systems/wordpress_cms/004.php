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
* wordpress6_cms Import articles
* 
* @package         ImpEx.wordpress6_cms
* @version        $Revision: $
* @author        Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout    $Name:  $
* @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
* @copyright     http://www.vbulletin.com/license.html
*
*/ 

class wordpress_cms_004 extends wordpress_cms_000
{
    var $_dependent     = '003';

    function wordpress_cms_004(&$displayobject)
    {	
		$this->_modulestring = $displayobject->phrases['import_cms_article'];
    }

    function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        if ($this->check_order($sessionobject,$this->_dependent))
        {
            if ($this->_restart)
            {
                if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_cms_articles'))
                {
                     $displayobject->display_now("<h4>{$displayobject->phrases['cms_articles_cleared']}</h4>");
                    $this->_restart = true;
                }
                else
                {
                   $sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['cms_article_restart_failed'], $displayobject->phrases['check_db_permissions']);
                }
            }

            // Start up the table
            $displayobject->update_basic('title', $displayobject->phrases['import_cms_article']);
            $displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
            $displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
            $displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_article']));

            // Ask some questions
            $displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 100));
            #$displayobject->update_html($displayobject->make_input_code('Enter the node type you want to import as an article','node_type','page'));


            // End the table
            $displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

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
        $target_database_type    = $sessionobject->get_session_var('targetdatabasetype');
        $target_table_prefix    = $sessionobject->get_session_var('targettableprefix');
        $source_database_type    = $sessionobject->get_session_var('sourcedatabasetype');
        $source_table_prefix    = $sessionobject->get_session_var('sourcetableprefix');

        // Per page vars
        $article_start_at        = $sessionobject->get_session_var('startat');
        $article_per_page        = $sessionobject->get_session_var('perpage');
        $class_num                = substr(get_class($this) , -3);

        // Clone and cache
        $article_object			= new ImpExData($Db_target, $sessionobject, 'cms_article', 'cms');
		$node_object			= new ImpExData($Db_target, $sessionobject, 'cms_node', 'cms');
		$nodeinfo_object		= new ImpExData($Db_target, $sessionobject, 'cms_nodeinfo', 'cms');
        $idcache                 = new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

        if(!$sessionobject->get_session_var($class_num . '_start'))
        {
            $sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
        }

        $article_array = $this->get_wordpress_content_details($Db_source, $source_database_type, $source_table_prefix, $article_start_at, $article_per_page);
		$contenttypeid = $this->get_contenttypeid($Db_target, $target_database_type, $target_table_prefix, 'vbcms', 'Article');
        $displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $article_array['count'] . " nodes</h4><p><b>{$displayobject->phrases['from']}</b> : " . $article_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $article_array['lastid'] . "</p>");
    
        foreach ($article_array['data'] as $article_id => $data)
        {
            $article = (phpversion() < '5' ? $article_object : clone($article_object));
			$node =  (phpversion() < '5' ? $node_object : clone($node_object));
			$nodeinfo =  (phpversion() < '5' ? $nodeinfo_object : clone($nodeinfo_object));

            // Mandatory
            $article->set_value('mandatory', 'importcmsarticleid',    $article_id);
            $article->set_value('mandatory', 'pagetext',         $data['post_content']);
            $article->set_value('nonmandatory', 'title',         $data['post_title']);
			$article->set_value('nonmandatory', 'poststarter',   $idcache->get_id('user', $data['post_author']));
            $article->set_value('nonmandatory', 'postauthor',    $idcache->get_id('username', $data['post_author']));
            $article->set_value('nonmandatory', 'previewtext',   substr($data['post_content'], 0, 2040));
            $article->set_value('nonmandatory', 'post_posted',   strtotime($data['post_date']));
			$article->set_value('nonmandatory', 'post_started',  strtotime($data['post_date']));
    
            if($article->is_valid())
            {
                if (!method_exists($article, 'import_cms_article'))
                {
                    die ('You have not selected the CMS target system, restart the import and ensure you select the correct target');
                }
    
                if($contentid = $article->import_cms_article($Db_target, $target_database_type, $target_table_prefix))
                {

					$parentnode = 1;

					$node->set_value('nonmandatory', 'contentid', $contentid);
					$node->set_value('mandatory', 'importcmsnodeid', 1);
					$node->set_value('mandatory', 'contenttypeid', $contenttypeid);
					$node->set_value('nonmandatory', 'url', $data['post_title']);
					$node->set_value('nonmandatory', 'userid', $idcache->get_id('user',$data['post_author']));
					$node->set_value('nonmandatory', 'publishdate',  strtotime($data['post_date']));
					$node->set_value('nonmandatory', 'permissionsfrom', 1);

					if ($node->is_valid())
					{
						$nodeinfo->set_value('nommandatory', 'description', $data['post_title']);
						$nodeinfo->set_value('nonmandatory', 'title', $data['post_title']);
						$nodeinfo->set_value('nonmandatory', 'html_title', $data['post_title']);
						$nodeinfo->set_value('nonmandatory', 'creationdate', strtotime($data['post_date']));
						$nodeinfo->set_value('nonmandatory', 'workflowstatus', 'published');

						if ($node->import_cms_node($Db_target, $target_database_type, $target_table_prefix, $data, $nodeinfo))
						{
							if(shortoutput)
							{
								$displayobject->display_now('.');
							}
							else
							{
								$displayobject->display_now('<br />' . $data['article_id'] . ' <span class="isucc"><b>' . $article->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_article'] . ' -> ' . $data['post_title']);
							}

							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$sessionobject->add_error($Db_target, 'warning', $class_num, $article_id, $displayobject->phrases['cms_node_not_imported'], $displayobject->phrases['cms_node_not_imported_rem']);
							$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_node_not_imported']}");
						}
					}
					else
					{
						$sessionobject->add_error($Db_target, 'invalid', $class_num, $article_id, $displayobject->phrases['invalid_object'], $node->_failedon);
						$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $node->_failedon);
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
					}
                }
                else
                {
                    $sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
                    $sessionobject->add_error($Db_target, 'warning', $class_num, $data['article_id'], $displayobject->phrases['cms_article_not_imported'], $displayobject->phrases['cms_article_not_imported_rem']);
                    $displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_article_not_imported']}");
                }
            }
            else
            {
                $sessionobject->add_error($Db_target, 'invalid', $class_num, $data['article_id'], $displayobject->phrases['invalid_object'], $article->_failedon);
                $displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $article->_failedon);
                $sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
            }
            unset($article);
        }

        if (empty($article_array['count']) OR $article_array['count'] < $article_per_page)
        {
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
        }
        else
        {
            $sessionobject->set_session_var('startat',$article_array['lastid']);
            $displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
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
