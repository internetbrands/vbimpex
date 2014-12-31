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
* wordpress_000
*
* @package      ImpEx.wordpress
*
*/

class vbcms_000 extends ImpExModule
{
    /**
    * Supported version
    *
    * @var    string
    */
    var $_version = '4.0.x - 4.1.x';
    var $_tier = '1';
    var $_product = 'cms';
    
    /**
    * Module string
    *
    * Class string for phpUnit header
    *
    * @var    array
    */
    var $_modulestring     = 'vBulletin CMS';
    var $_homepage     = 'http://www.vbulletin.com';

    /**
    * Valid Database Tables
    *
    * @var    array
    */
    var $_valid_tables = array (
        'cms_article', 'cms_category', 'cms_grid', 'cms_layout', 'cms_layoutwidget', 'cms_navigation', 'cms_node', 'cms_nodecategory',
		'cms_nodeconfig', 'cms_nodeinfo', 'cms_permissions', 'cms_rate', 'cms_sectionorder', 'cms_widget', 'cms_widgetconfig', 'cms_widgettype'
    );


    function vbcms_000()
    {
    }

    /**
    * HTML parser
    *
    * @param    string    mixed            The string to parse
    * @param    boolean                    Truncate smilies
    *
    * @return    array
    */
    function vbcms_html($text)
    {
        return $text;
    }

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	void
	*/
	function update_cms_category_parentid(&$Db_object, &$databasetype, &$tableprefix)
	{
		$cats = $this->get_cms_category_ids($Db_object, $databasetype, $tableprefix);

		if ($cats)
		{
			$categories = $Db_object->query("
				SELECT
					categoryid, importcmscategoryid, parentcat
				FROM {$tableprefix}cms_category
				WHERE
					parentcat <> 0
			");
			while ($cat = $Db_object->fetch_array($categories))
			{
				$Db_object->query("
					UPDATE {$tableprefix}cms_category
					SET parentcat = " . ($cats[$cat['parentcat']] ? $cats[$cat['parentcat']] : 0) . "
					WHERE
						categoryid = $cat[categoryid]
				");
			}
		}
	}

	function get_cms_section_order(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page)
	{
		$return_array = array();

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$details_list = $Db_object->query("SELECT * FROM {$tableprefix}cms_sectionorder ORDER BY sectionid, nodeid LIMIT {$start_at}, {$per_page}");

			while ($detail = $Db_object->fetch_array($details_list))
			{
				$return_array[] = $detail;
			}
		}
		else
		{
			return false;
		}
		return $return_array;
	}
	
	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	void
	*/
	function update_cms_section_navigation(&$Db_object, &$databasetype, &$tableprefix)
	{
		$allsections = $this->get_cms_section_parentnodes($Db_object, $databasetype, $tableprefix);

		if ($allsections)
		{
			$navigation = $Db_object->query("
				SELECT nodeid, nodelist
				FROM {$tableprefix}cms_navigation
				WHERE importid <> 0
			");
			while ($section = $Db_object->fetch_array($navigation))
			{
				$newnodes = array();
				$nodes = explode(',', $section['nodelist']);
				foreach ($nodes AS $__nodeid)
				{
					if ($_nodeid = $allsections[$__nodeid])
					{
						$newnodes[] = $_nodeid;
					}
				}

				if ($newnodes)
				{
					$Db_object->query("
						REPLACE INTO {$tableprefix}cms_navigation
						(nodeid, nodelist, importid)
						VALUES
						(
							{$section['nodeid']},
							'" . implode(',', $newnodes) . "',
							1
						)
					");
				}

			}

			if ($allsections[1] AND $rootnode = $Db_object->query_first("
				SELECT nodelist
				FROM {$tableprefix}cms_navigation
				WHERE nodeid = 1
			"))
			{
				$newlist = array();
				$list = explode(',', $rootnode['nodelist']);
				foreach ($list AS $_nodeid)
				{
					$newlist[$_nodeid] = true;
				}
				$newlist[$allsections[1]] = true;

				// Update root node
				$Db_object->query("
					UPDATE {$tableprefix}cms_navigation
					SET nodelist = '" . addslashes(implode(',', array_keys($newlist))) . "'
					WHERE nodeid = 1
				");
			}
		}
	}
}// Class
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile: 000.php,v $ - $Revision: $
|| ####################################################################
\*======================================================================*/
