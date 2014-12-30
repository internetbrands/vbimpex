<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* vbcms 005 Import articles
* 
* @package         ImpEx.vbcms
* @version        $Revision: 2255 $
* @checkedout    $Name:  $
* @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
* @copyright     http://www.vbulletin.com/license.html
*
*/ 

class vbcms_006 extends vbcms_000
{
    var $_dependent     = '005';

    function vbcms_006(&$displayobject)
    {
        $this->_modulestring = $displayobject->phrases['import_cms_article'];
    }

    function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        if ($this->check_order($sessionobject,$this->_dependent))
        {
            if ($this->_restart)
            {
                if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_cms_articles'))
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
            $displayobject->update_basic('title', $displayobject->phrases['import_article']);
            $displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
            $displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
            $displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cms_article']));

            // Ask some questions
            $displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 100));
            #$displayobject->update_html($displayobject->make_input_code('Enter the node type you want to import as an article','node_type','page'));

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
        $article_start_at		= $sessionobject->get_session_var('startat');
        $article_per_page		= $sessionobject->get_session_var('perpage');
        $class_num				= substr(get_class($this) , -3);

        // Clone and cache
        $article_object			= new ImpExData($Db_target, $sessionobject, 'cms_article', 'cms');
		$node_object			= new ImpExData($Db_target, $sessionobject, 'cms_node', 'cms');
		$nodeinfo_object		= new ImpExData($Db_target, $sessionobject, 'cms_nodeinfo', 'cms');
		$nodeconfig_object		= new ImpExData($Db_target, $sessionobject, 'cms_nodeconfig', 'cms');
		$nodecategory_object	= new ImpExData($Db_target, $sessionobject, 'cms_nodecategory', 'cms');
        $idcache				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

        if(!$sessionobject->get_session_var($class_num . '_start'))
        {
            $sessionobject->timing($class_num,'start', $sessionobject->get_session_var('autosubmit'));
        }

		// Get an array data
		$article_array = $this->get_cms_article($Db_source, $source_database_type, $source_table_prefix, $article_start_at, $article_per_page);
		$contenttypeid = $this->get_contenttypeid($Db_target, $target_database_type, $target_table_prefix, 'vbcms', 'Article');
        $displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $article_array['count'] . " articles</h4><p><b>{$displayobject->phrases['from']}</b> : " . $article_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . $article_array['lastid'] . "</p>");

        foreach ($article_array['data'] as $article_id => $data)
        {
            $article = (phpversion() < '5' ? $article_object : clone($article_object));
			$node =  (phpversion() < '5' ? $node_object : clone($node_object));
			$nodeinfo =  (phpversion() < '5' ? $nodeinfo_object : clone($nodeinfo_object));
			$nodeconfig =  (phpversion() < '5' ? $nodeconfig_object : clone($nodeconfig_object));
			$nodecategory =  (phpversion() < '5' ? $nodecategory_object : clone($nodecategory_object));

            // Mandatory
            $article->set_value('mandatory', 'importcmscontentid',    $article_id);
            $article->set_value('mandatory', 'pagetext',         $data['pagetext']);
            $article->set_value('nonmandatory', 'threadid',      $idcache->get_id('thread', $data['threadid']));
			$article->set_value('nonmandatory', 'blogid',        $idcache->get_id('blog', $data['blogid']));
            $article->set_value('nonmandatory', 'postauthor',    $idcache->get_id('username', $data['poststarter']));
			$article->set_value('nonmandatory', 'poststarter',   $idcache->get_id('user', $data['poststarter']));
			$article->set_value('nonmandatory', 'posttitle',     $data['posttitle']);
            $article->set_value('nonmandatory', 'previewtext',   substr($data['pagetext'], 0, 2040));
            $article->set_value('nonmandatory', 'post_posted',   $data['post_posted']);
			$article->set_value('nonmandatory', 'post_started',  $data['post_started']);
			$article->set_value('nonmandatory', 'postid',        $idcache->get_id('post', $data['postid']));
			$article->set_value('nonmandatory', 'previewimage',  $data['previewimage']);
			$article->set_value('nonmandatory', 'imagewidth',    $data['imagewidth']);
			$article->set_value('nonmandatory', 'imageheight',   $data['imageheight']);
			$article->set_value('nonmandatory', 'previewvideo',  $data['previewvideo']);
			$article->set_value('nonmandatory', 'htmlstate',     $data['htmlstate']);

			$parentnode = $idcache->get_id('cmsnode', $data['node']['parentnode']);
			if (!$parentnode)
			{
				$parentnode = 1;
			}

			$node->set_value('mandatory', 'importcmsnodeid', $data['node']['nodeid']);
			$node->set_value('nonmandatory', 'parentnode', $parentnode);
			$node->set_value('nonmandatory', 'url', $data['node']['url']);
			$node->set_value('mandatory', 'contenttypeid', $contenttypeid);
			$node->set_value('nonmandatory', 'styleid', $data['node']['styleid']);
			$node->set_value('nonmandatory', 'layoutid', $idcache->get_id('layout', $data['node']['layoutid']));
			$node->set_value('nonmandatory', 'userid', $idcache->get_id('user', $data['node']['userid']));
			$node->set_value('nonmandatory', 'publishdate', $data['node']['publishdate']);
			$node->set_value('nonmandatory', 'setpublish', $data['node']['setpublish']);
			$node->set_value('nonmandatory', 'issection', $data['node']['issection']);
			$node->set_value('nonmandatory', 'onhomepage', $data['node']['onhomepage']);
			$node->set_value('nonmandatory', 'permissionsfrom', $data['node']['permissionsfrom']);
			$node->set_value('nonmandatory', 'lastupdated', $data['node']['lastupdated']);
			$node->set_value('nonmandatory', 'publicpreview', $data['node']['publicprview']);
			$node->set_value('nonmandatory', 'auto_displayorder', $data['node']['auto_displayorder']);
			$node->set_value('nonmandatory', 'comments_enabled', $data['node']['comments_enabled']);
			$node->set_value('nonmandatory', 'new', $data['node']['new']);
			$node->set_value('nonmandatory', 'showtitle', $data['node']['showtitle']);
			$node->set_value('nonmandatory', 'showuser', $data['node']['showuser']);
			$node->set_value('nonmandatory', 'showpreviewonly', $data['node']['showpreviewonly']);
			$node->set_value('nonmandatory', 'showupdated', $data['node']['showupdated']);
			$node->set_value('nonmandatory', 'showviewcount', $data['node']['showviewcount']);
			$node->set_value('nonmandatory', 'showcreation', $data['node']['showcreation']);
			$node->set_value('nonmandatory', 'settingsforboth', $data['node']['settingsforboth']);
			$node->set_value('nonmandatory', 'includechildren', $data['node']['includechildren']);
			$node->set_value('nonmandatory', 'hideshowall', $data['node']['hideshowall']);
			$node->set_value('nonmandatory', 'editshowchildren', $data['node']['editshowchildren']);
			$node->set_value('nonmandatory', 'showall', $data['node']['showall']);
			$node->set_value('nonmandatory', 'showpublishdate', $data['node']['showpublishdate']);
			$node->set_value('nonmandatory', 'showrating', $data['node']['showrating']);
			$node->set_value('nonmandatory', 'hidden', $data['node']['hidden']);
			$node->set_value('nonmandatory', 'shownav', $data['node']['shownav']);
			$node->set_value('nonmandatory', 'nosearch', $data['node']['nosearch']);

            if($article->is_valid())
            {
                if($contentid = $article->import_cms_article($Db_target, $target_database_type, $target_table_prefix))
                {
					$node->set_value('nonmandatory', 'contentid', $contentid);

					if ($node->is_valid())
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

						if ($node->import_cms_node($Db_target, $target_database_type, $target_table_prefix, $data, $nodeinfo, $nodeconfig, $nodecategory))
						{
							if(shortoutput)
							{
								$displayobject->display_now('.');
							}
							else
							{
								$displayobject->display_now('<br />' . $data['article_id'] . ' <span class="isucc"><b>' . $article->how_complete() . '%</b></span> ' . $displayobject->phrases['cms_article'] . ' -> ' . $data['nodeinfo']['title']);
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
                    $sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
                    $sessionobject->add_error($Db_target, 'warning', $class_num, $article_id, $displayobject->phrases['cms_article_not_imported'], $displayobject->phrases['cms_article_not_imported_rem']);
                    $displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['cms_article_not_imported']}");
                }
            }
            else
            {
                $sessionobject->add_error($Db_target, 'invalid', $class_num, $article_id, $displayobject->phrases['invalid_object'], $article->_failedon);
                $displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $article->_failedon);
                $sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
            }
            unset($data);
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

            $sessionobject->set_session_var($class_num, 'FINISHED');
            $sessionobject->set_session_var('module', '000');
            $sessionobject->set_session_var('autosubmit', '0');
            $displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
        }
        else
        {
            $sessionobject->set_session_var('startat', $article_array['lastid']);
            $displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
        }
    }// End resume
}//End Class

/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile: 009.php,v $ - $Revision: 2255 $
|| ####################################################################
\*======================================================================*/
?>