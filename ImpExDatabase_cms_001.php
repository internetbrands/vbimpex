<?php
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
* The database proxy object.
*
* This handles interaction with the different types of database.
*
* @package 		ImpEx
*
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

class ImpExDatabase extends ImpExDatabaseCore
{
	/**
	* Class version
	*
	* This will allow the checking for inter-operability of class version in different
	* versions of ImpEx
	*
	* @var    string
	*/

	var $_target_system = 'cms';


	/**
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExDatabase()
	{
	}

	var $_import_cms_ids = array(
		array('cms_article'			=> 'importcmscontentid'),
		array('cms_category'		=> 'importcmscategoryid'),
		array('cms_grid'			=> 'importcmsgridid'),
		array('cms_layout'			=> 'importcmslayoutid'),
		array('cms_layoutwidget'	=> 'importid'),
		array('cms_widget'			=> 'importcmswidgetid'),
		array('cms_widgetconfig'	=> 'importid'),
		array('cms_navigation'		=> 'importid'),
		array('cms_node'			=> 'importcmsnodeid'),
		array('cms_nodecategory'	=> 'importid'),
		array('cms_nodeconfig'		=> 'importid'),
		array('cms_nodeinfo'		=> 'importid'),
		array('cms_rate'			=> 'importcmsrateid'),
		array('cms_sectionorder'	=> 'importid'),
		array('usergroup'			=> 'importusergroupid'),
		array('user'				=> 'importuserid'),
		array('usernote'			=> 'importusernoteid'),
		array('customavatar'		=> 'importcustomavatarid'),
		array('customprofilepic'	=> 'importcustomprofilepicid'),
	);

	/**
	* Clears the currently imported CMS article
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_articles(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbcms', 'Article');
				
				$node = $Db_object->query("
					SELECT node.nodeid
					FROM {$tableprefix}cms_article AS article
					INNER JOIN {$tableprefix}cms_node AS node ON (article.contentid = node.contentid AND node.contenttypeid = $contenttypeid)
					WHERE article.importcmscontentid <> 0
				");
				while ($nodeinfo = $Db_object->fetch_array($node))
				{
					$this->delete_cms_node($Db_object, $databasetype, $tableprefix, $nodeinfo['nodeid']);
				}

				$Db_object->query("
					DELETE FROM {$tableprefix}cms_article
					WHERE importcmscontentid <> 0

				");

				# Delete the ones with the import id
				# Sort out the node tables ?

				# Delete the content, the move all the node ids in the tables -1 after the node being removed
				
				 return true;
			}
		}
	
		return false;
	}

	/**
	* Clears the currently imported CMS section
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_sections(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbcms', 'Section');

				$node = $Db_object->query("
					SELECT node.nodeid
					FROM {$tableprefix}cms_node AS node
					WHERE
						importcmsnodeid <> 0
							AND
						node.contenttypeid = $contenttypeid
				");
				while ($nodeinfo = $Db_object->fetch_array($node))
				{
					$this->delete_cms_node($Db_object, $databasetype, $tableprefix, $nodeinfo['nodeid']);
					$Db_object->query("DELETE FROM {$tableprefix}cms_navigation WHERE nodeid = {$nodeinfo['nodeid']}");
				}

				 return true;
			}
		}

		return false;
	}

	/**
	* Clears the currently imported CMS section order
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_section_orders(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{

				$Db_object->query("DELETE FROM {$tableprefix}cms_sectionorder WHERE importid  <> 0");
				return true;
			}
		}

		return false;
	}

	/**
	* Clears the currently imported CMS widget
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_widgets(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
		
				$Db_object->query("DELETE FROM {$tableprefix}cms_widget WHERE importcmswidgetid  <> 0");
				$Db_object->query("DELETE FROM {$tableprefix}cms_layoutwidget WHERE importid <> 0");
				$Db_object->query("DELETE FROM {$tableprefix}cms_widgetconfig WHERE importid <> 0");

				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_widget AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_widget auto_increment=0");
				return true;
			}
		}
	
		return false;
	}

	/**
	* Clears the currently imported CMS article
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_categories(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$category = $Db_object->query("
					SELECT categoryid
					FROM {$tableprefix}cms_category
					WHERE importcmscategoryid <> 0
				");
				while ($catinfo = $Db_object->fetch_array($category))
				{
					$this->delete_cms_category($Db_object, $databasetype, $tableprefix, $catinfo['categoryid']);
				}
				
				return true;
			}
		}
	
		return false;
	}

	/**
	* Deletes a category but not the category's children
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	array	catinfo
	*
	* @return	boolean
	*/
	function delete_cms_category($Db_object, $databasetype, $tableprefix, $catid)
	{
		if ($catinfo = $Db_object->query_first("
			SELECT categoryid, catright, catleft, (catright - catleft + 1) AS catwidth
			FROM {$tableprefix}cms_category
			WHERE categoryid = {$catid}
		"))
		{
			$Db_object->query("DELETE FROM {$tableprefix}cms_category WHERE catleft BETWEEN {$catinfo['catleft']} AND {$catinfo['catright']}");
			$Db_object->query("UPDATE {$tableprefix}cms_category SET catright = catright - {$catinfo['catwidth']} WHERE catright > {$catinfo['catright']}");
			$Db_object->query("UPDATE {$tableprefix}cms_category SET catleft = catleft - {$catinfo['catwidth']} WHERE catleft > {$catinfo['catright']}");

			$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_category AUTO_INCREMENT=0");
			$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_category auto_increment=0");
		}
	}

	/**
	* Deletes a node but not the node's children
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	array	nodeinfo
	*
	* @return	boolean
	*/
	function delete_cms_node($Db_object, $databasetype, $tableprefix, $nodeid)
	{
		if ($nodeinfo = $Db_object->query_first("
			SELECT nodeid, noderight, nodeleft, (noderight - nodeleft + 1) AS nodewidth
			FROM {$tableprefix}cms_node
			WHERE nodeid = {$nodeid}
		"))
		{
			$Db_object->query("DELETE FROM {$tableprefix}cms_node WHERE nodeleft BETWEEN {$nodeinfo['nodeleft']} AND {$nodeinfo['noderight']}");
			$Db_object->query("UPDATE {$tableprefix}cms_node SET noderight = noderight - {$nodeinfo['nodewidth']} WHERE noderight > {$nodeinfo['noderight']}");
			$Db_object->query("UPDATE {$tableprefix}cms_node SET nodeleft = nodeleft - {$nodeinfo['nodewidth']} WHERE nodeleft > {$nodeinfo['noderight']}");

			$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_node AUTO_INCREMENT=0");
			$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_node auto_increment=0");
		}

		$Db_object->query("DELETE FROM {$tableprefix}cms_nodecategory WHERE nodeid = {$nodeid}");
		$Db_object->query("DELETE FROM {$tableprefix}cms_nodeconfig WHERE nodeid = {$nodeid}");
		$Db_object->query("DELETE FROM {$tableprefix}cms_nodeinfo WHERE nodeid = {$nodeid}");
	}

	/**
	* Clears the currently imported grid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_grids(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$gridarray = array();
				$grids = $Db_object->query("
					SELECT gridid
					FROM {$tableprefix}cms_grid
					WHERE importcmsgridid  <> 0
				");
				while ($grid = $Db_object->fetch_array($grids))
				{
					$gridarray[] = 'vbcms_grid_' . $grid['gridid'];
				}

				if ($gridarray)
				{
					$Db_object->query("
						DELETE FROM {$tableprefix}template
						WHERE
							title IN ('" . implode("', '", $gridarray) . "')
								AND
							templatetype = 'template'
								AND
							styleid = 0
					");
				}

				$Db_object->query("DELETE FROM {$tableprefix}cms_grid WHERE importcmsgridid  <> 0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_grid AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_grid auto_increment=0");
				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Clears the currently imported layout
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_cms_layouts(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("DELETE FROM {$tableprefix}cms_layout WHERE importcmslayoutid  <> 0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_layout AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_layout auto_increment=0");
				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Clears the currently imported widget 
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	function clear_imported_widgets(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("DELETE FROM {$tableprefix}cms_widget WHERE importcmswidgetid  <> 0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_widget AUTO_INCREMENT=0");
				$Db_object->query("ALTER TABLE " . $tableprefix  . "cms_widget auto_increment=0");
				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}
	
	function import_cms_category(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['poll'] === false))
				{
					$there = $Db_object->query_first("SELECT importcmscategoryid FROM {$tableprefix}cms_category WHERE importcmscategoryid = " . intval(trim($this->get_value('mandatory', 'importcmscategoryid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$parentnode = intval($this->get_value('nonmandatory', 'parentnode'));

				$record = $Db_object->query_first("
					SELECT max(catright) AS catright
					FROM {$tableprefix}cms_category
				");
				$catleft = intval($record['catright']) + 1;
				$catright = $catleft + 1;

				// Make a space for the new node
				$Db_object->query_first("UPDATE {$tableprefix}cms_category SET catright = catright + 2 WHERE catright >= {$catleft}");
				$Db_object->query_first("UPDATE {$tableprefix}cms_category SET catleft = catleft + 2 WHERE catleft >= {$catleft}");
		
				$Db_object->query("
					INSERT INTO {$tableprefix}cms_category
					(
						parentnode, category, description, catleft, catright, parentcat, enabled, contentcount, importcmscategoryid
					)
					VALUES
					(
						'" . intval($this->get_value('mandatory', 'parentnode')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'category')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						" . intval($catleft) . ",
						" . intval($catright) . ",
						0,
						'" . intval($this->get_value('nonmandatory', 'enabled')) . "',
						'" . intval($this->get_value('nonmandatory', 'contentcount')) . "',
						" . intval($this->get_value('mandatory', 'importcmscategoryid')) . "
					) 
				");

				return $Db_object->insert_id();
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_article(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['poll'] === false))
				{
					$there = $Db_object->query_first("SELECT importcmscontentid FROM {$tableprefix}cms_article WHERE importcmscontentid = " . intval(trim($this->get_value('mandatory', 'importcmscontentid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}
				$new_article = array();
			
				$Db_object->query("
					INSERT INTO {$tableprefix}cms_article
					(
						pagetext, threadid, blogid, posttitle, postauthor, poststarter, blogpostid, postid, post_posted,
						post_started, previewtext, previewimage, imagewidth, imageheight, previewvideo, htmlstate,
						importcmscontentid
					)
					VALUES
					(
						'" . addslashes($this->get_value('mandatory', 'pagetext')) . "',
						'" . intval($this->get_value('nonmandatory', 'threadid')) . "',
						'" . intval($this->get_value('nonmandatory', 'blogid')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'posttitle')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'postauthor')) . "',
						'" . intval($this->get_value('nonmandatory', 'poststarter')) . "',
						'" . intval($this->get_value('nonmandatory', 'blogpostid')) . "',
						'" . intval($this->get_value('nonmandatory', 'postid')) . "',
						'" . intval($this->get_value('nonmandatory', 'post_posted')) . "',
						'" . intval($this->get_value('nonmandatory', 'post_started')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'previewtext')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'previewimage')) . "',
						'" . intval($this->get_value('nonmandatory', 'imagewidth')) . "',
						'" . intval($this->get_value('nonmandatory', 'imageheight')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'previewvideo')) . "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'htmlstate'), array('off','on','on_nl2br'), 'on_nl2br') . "',
						" . intval($this->get_value('mandatory', 'importcmscontentid')) . "
					) 
				");

				return $Db_object->insert_id();
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_section(&$Db_object, &$databasetype, &$tableprefix, &$data, &$nodeinfo, &$nodeconfig, &$nodecategory, &$navigation)
	{
		if ($nodeid = $this->import_cms_node($Db_object, $databasetype, $tableprefix, $data, $nodeinfo, $nodeconfig, $nodecategory))
		{
			if ($data['navigation'] AND $data['navigation']['nodelist'])
			{
				$navigation->set_value('mandatory', 'importid', 1);
				$navigation->set_value('mandatory', 'nodeid', $nodeid);
				$navigation->set_value('nonmandatory', 'nodelist', $data['navigation']['nodelist']);
				if (!$navigation->is_valid() OR !$navigation->import_cms_navigation($Db_object, $databasetype, $tableprefix))
				{
					return false;
				}
			}
			
			return $nodeid;			
		}
		else
		{
			return false;
		}
	}

	function import_cms_node(&$Db_object, &$databasetype, &$tableprefix, $data = array(), $nodeinfo = null, $nodeconfig = null, $nodecategory = null)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				// CMS data inserted, now sort out the node(s)
				######################
				## Create the Node in the hierarchical data

				# nodeid 		auto_increment
				# nodeleft		[generate]
				# noderight		[generate]
				# parentnode	<where are we going to nest the data under> - Create this then add nodes here and update the noderight from here on down

				// TODO: We have to create a node some where to put all this stuff under ....... likely not here, but at
				// the beginging and then save it to the session
				// $parentnode is set to 1 for now..
				$parentnode = intval($this->get_value('nonmandatory', 'parentnode'));

				// Get the new leftnode position
				$parent = $Db_object->query_first("SELECT noderight FROM {$tableprefix}cms_node WHERE nodeid = {$parentnode}");
				$left = $parent['noderight'] - 1;

				// Make a space for the new node
				$Db_object->query_first("UPDATE {$tableprefix}cms_node SET noderight = noderight + 2 WHERE noderight > {$left}");
				$Db_object->query_first("UPDATE {$tableprefix}cms_node SET nodeleft = nodeleft + 2 WHERE nodeleft > {$left}");

				// Fill the gap with our new leaf node
				$nodeleft = $left + 1;
				$noderight = $left + 2;
				//$new_article['parentid'] = $parent_section_nodeid;

				$Db_object->query("
					INSERT INTO {$tableprefix}cms_node
					(
						nodeleft, noderight, parentnode, contenttypeid, contentid, url, styleid, layoutid, userid,
						publishdate, setpublish, issection, onhomepage, permissionsfrom, lastupdated, publicpreview,
						auto_displayorder, comments_enabled, new, showtitle, showuser, showpreviewonly, showupdated, showviewcount,
						settingsforboth, includechildren, editshowchildren, showall, showpublishdate,
						showrating, hidden, shownav, nosearch, importcmsnodeid
					)
					VALUES
					(
						" . intval($nodeleft) . ",
						" . intval($noderight) . ",
						{$parentnode},
						" . intval($this->get_value('mandatory', 'contenttypeid')) . ",
						" . intval($this->get_value('nonmandatory', 'contentid')) . ",
						'" . addslashes($this->get_value('nonmandatory', 'url')) . "',
						" . intval($this->get_value('nonmandatory', 'styleid')) . ",
						" . intval($this->get_value('nonmandatory', 'layoutid')) . ",
						" . intval($this->get_value('nonmandatory', 'userid')) . ",
						" . intval($this->get_value('nonmandatory', 'publishdate')) . ",
						" . intval($this->get_value('nonmandatory', 'setpublish')) . ",
						" . intval($this->get_value('nonmandatory', 'issection')) . ",
						" . intval($this->get_value('nonmandatory', 'onhomepage')) . ",
						" . intval($this->get_value('nonmandatory', 'permissionsfrom')) . ",
						" . intval($this->get_value('nonmandatory', 'lastupdated')) . ",
						" . intval($this->get_value('nonmandatory', 'publicpreview')) . ",
						" . intval($this->get_value('nonmandatory', 'auto_displayorder')) . ",
						" . intval($this->get_value('nonmandatory', 'comments_enabled')) . ",
						" . intval($this->get_value('nonmandatory', 'new')) . ",
						" . intval($this->get_value('nonmandatory', 'showtitle')) . ",
						" . intval($this->get_value('nonmandatory', 'showuser')) . ",
						" . intval($this->get_value('nonmandatory', 'showpreviewonly')) . ",
						" . intval($this->get_value('nonmandatory', 'showupdated')) . ",
						" . intval($this->get_value('nonmandatory', 'showviewcount')) . ",
						" . intval($this->get_value('nonmandatory', 'settingsforboth')) . ",
						" . intval($this->get_value('nonmandatory', 'includechildren')) . ",
						" . intval($this->get_value('nonmandatory', 'editshowchildren')) . ",
						" . intval($this->get_value('nonmandatory', 'showall')) . ",
						" . intval($this->get_value('nonmandatory', 'showpublishdate')) . ",
						" . intval($this->get_value('nonmandatory', 'showrating')) . ",
						" . intval($this->get_value('nonmandatory', 'hidden')) . ",
						" . intval($this->get_value('nonmandatory', 'shownav')) . ",
						" . intval($this->get_value('nonmandatory', 'nosearch')) . ",
						" . intval($this->get_value('mandatory', 'importcmsnodeid')) . "
					)
				");

				$nodeid = $Db_object->insert_id();
				if (!$nodeid)
				{
					return false;
				}
				else
				{
					$idcache = new ImpExCache($Db_object, $databasetype, $tableprefix);

					$nodeinfo->set_value('mandatory', 'importid', 1);
					$nodeinfo->set_value('mandatory', 'nodeid', $nodeid);
					
					if (!$nodeinfo->is_valid() OR !$nodeinfo->import_cms_nodeinfo($Db_object, $databasetype, $tableprefix))
					{
						return false;
					}
					if ($data['nodeconfig'])
					{
						foreach ($data['nodeconfig'] AS $_nodeconfig)
						{
							$nodeconfig->set_value('mandatory', 'nodeid', $nodeid);
							$nodeconfig->set_value('mandatory', 'importid', 1);
							$nodeconfig->set_value('nonmandatory', 'name', $_nodeconfig['name']);
							$nodeconfig->set_value('nonmandatory', 'value', $_nodeconfig['value']);
							$nodeconfig->set_value('nonmandatory', 'serialized', $_nodeconfig['serialized']);
							if (!$nodeconfig->is_valid() OR !$nodeconfig->import_cms_nodeconfig($Db_object, $databasetype, $tableprefix))
							{
								return false;
							}
						}
					}

					if ($data['nodecategory'])
					{
						foreach ($data['nodecategory'] AS $_nodecategory)
						{
							$nodecategory->set_value('mandatory', 'importid', 1);
							$nodecategory->set_value('mandatory', 'nodeid', $nodeid);
							$nodecategory->set_value('mandatory', 'categoryid', $idcache->get_id('cmscategory', $_nodecategory['categoryid']));
							if (!$nodecategory->is_valid() OR !$nodecategory->import_cms_nodecategory($Db_object, $databasetype, $tableprefix))
							{
								return false;
							}
						}
					}
					return $nodeid;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_widget(&$Db_object, &$databasetype, &$tableprefix, &$data, &$widgetconfig, &$layoutwidget)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$widgettype = $Db_object->query_first("
					SELECT w.widgettypeid
					FROM {$tableprefix}cms_widgettype as w
					INNER JOIN {$tableprefix}package AS p ON (w.packageid = p.packageid)
					WHERE
						w.class = '" . addslashes($data['widgettype']['class']) . "'
							AND
						p.productid = 'vbcms'
				");

				if (!$widgettype)
				{
					return false;
				}

				$Db_object->query("
					INSERT INTO {$tableprefix}cms_widget
					(
						widgettypeid, varname, title, description, importcmswidgetid
					)
					VALUES
					(
						" . intval($widgettype['widgettypeid']) . ",
						'" . addslashes($this->get_value('nonmandatory', 'varname')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						" . intval($this->get_value('mandatory', 'importcmswidgetid')) . "
					)
				");

				$widgetid = $Db_object->insert_id();
				if (!$widgetid)
				{
					return false;
				}
				else
				{
					$idcache = new ImpExCache($Db_object, $databasetype, $tableprefix);

					foreach ($data['widgetconfig'] AS $_widgetconfig)
					{
						$widgetconfig->set_value('mandatory', 'widgetid', $widgetid);
						$widgetconfig->set_value('mandatory', 'importid', 1);
						$widgetconfig->set_value('mandatory', 'name', $_widgetconfig['name']);
						$widgetconfig->set_value('nonmandatory', 'nodeid', $idcache->get_id('cmsnode', $_widgetconfig['nodeid']));
						$widgetconfig->set_value('nonmandatory', 'value', $_widgetconfig['value']);
						$widgetconfig->set_value('nonmandatory', 'serialized', $_widgetconfig['serialized']);
						if (!$widgetconfig->is_valid() OR !$widgetconfig->import_cms_widgetconfig($Db_object, $databasetype, $tableprefix))
						{
							return false;
						}
					}
					foreach ($data['layoutwidget'] AS $_layoutwidget)
					{
						$layoutwidget->set_value('mandatory', 'widgetid', $widgetid);
						$layoutwidget->set_value('mandatory', 'importid', 1);
						$layoutwidget->set_value('mandatory', 'layoutid', $idcache->get_id('layout', $_layoutwidget['layoutid']));
						$layoutwidget->set_value('nonmandatory', 'layoutcolumn', $_layoutwidget['layoutcolumn']);
						$layoutwidget->set_value('nonmandatory', 'layoutindex', $_layoutwidget['layoutindex']);
						if (!$layoutwidget->is_valid() OR !$layoutwidget->import_cms_layoutwidget($Db_object, $databasetype, $tableprefix))
						{
							return false;
						}
					}
					return $widgetid;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_nodeinfo(&$Db_object, &$databasetype, &$tableprefix)
	{
	switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					REPLACE INTO {$tableprefix}cms_nodeinfo
					(
						nodeid, description, title, html_title, viewcount, creationdate, workflowdate, workflowstatus,
						workflowcheckedout, workflowpending, associatedthreadid, keywords, ratingnum, ratingtotal, rating,
						importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'nodeid')) . ",
						'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'html_title')) . "',
						" . intval($this->get_value('nonmandatory', 'viewcount')) . ",
						" . intval($this->get_value('nonmandatory', 'creationdate')) . ",
						" . intval($this->get_value('nonmandatory', 'workflowdate')) . ",
						'" . addslashes($this->get_value('nonmandatory', 'workflowstatus')) . "',
						" . intval($this->get_value('nonmandatory', 'workflowpending')) . ",
						" . intval($this->get_value('nonmandatory', 'workflowlevelid')) . ",
						" . intval($this->get_value('nonmandatory', 'associatedthreadid')) . ",
						'" . addslashes($this->get_value('nonmandatory', 'keywords')) . "',
						" . intval($this->get_value('nonmandatory', 'ratingnum')) . ",
						" . intval($this->get_value('nonmandatory', 'ratingtotal')) . ",
						" . floatval($this->get_value('nonmandatory', 'rating')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_section_order(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					REPLACE INTO {$tableprefix}cms_sectionorder
					(
						sectionid, nodeid, displayorder, importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'sectionid')) . ",
						" . intval($this->get_value('mandatory', 'nodeid')) . ",
						" . intval($this->get_value('nonmandatory', 'displayorder')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_nodeconfig(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					INSERT INTO {$tableprefix}cms_nodeconfig
					(
						nodeid, name, value, serialized, importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'nodeid')) . ",
						'" . addslashes($this->get_value('nonmandatory', 'name')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'value')) . "',
						" . intval($this->get_value('nonmandatory', 'serialized')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_widgetconfig(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					INSERT INTO {$tableprefix}cms_widgetconfig
					(
						widgetid, nodeid, name, value, serialized, importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'widgetid')) . ",
						" . intval($this->get_value('nonmandatory', 'nodeid')) . ",
						'" . addslashes($this->get_value('mandatory', 'name')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'value')) . "',
						" . intval($this->get_value('nonmandatory', 'serialized')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_nodecategory(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					REPLACE INTO {$tableprefix}cms_nodecategory
					(
						nodeid, categoryid, importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'nodeid')) . ",
						" . intval($this->get_value('mandatory', 'categoryid')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_layoutwidget(&$Db_object, &$databasetype, &$tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					INSERT INTO {$tableprefix}cms_layoutwidget
					(
						widgetid, layoutid, layoutcolumn, layoutindex, importid
					)
					VALUES
					(
						" . intval($this->get_value('mandatory', 'widgetid')) . ",
						" . intval($this->get_value('mandatory', 'layoutid')) . ",
						" . intval($this->get_value('nonmandatory', 'layoutcolumn')) . ",
						" . intval($this->get_value('nonmandatory', 'layoutindex')) . ",
						" . intval($this->get_value('mandatory', 'importid')) . "
					)
				");

				if ($Db_object->affected_rows())
				{
					return true; // There is no auto_inc so no return
				}
				else
				{
					return false;
				}
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function get_cms_article(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array('data' => array());

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("
				SELECT *
				FROM {$tableprefix}cms_article
				ORDER BY contentid
				LIMIT {$start_at}, {$per_page}
			");

			$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbcms', 'Article');
			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed
				// Yes -- this could be done with a join in the above query but this way leaves the two datasets separate
				$nodeinfo = $nodeconfig = $nodecategory = array();
				$node = $Db_object->query_first("
					SELECT *
					FROM {$tableprefix}cms_node
					WHERE
						contentid = {$detail['contentid']}
							AND
						contenttypeid = {$contenttypeid}
				");

				if ($node)
				{
					$nodeinfo = $Db_object->query_first("
						SELECT *
						FROM {$tableprefix}cms_nodeinfo
						WHERE nodeid = {$node['nodeid']}
					");

					$nodeconfigs = $Db_object->query("
						SELECT *
						FROM {$tableprefix}cms_nodeconfig
						WHERE nodeid = {$node['nodeid']}
					");
					while ($_nodeconfig = $Db_object->fetch_array($nodeconfigs))
					{
						$nodeconfig[] = $_nodeconfig;
					}

					$nodecategories = $Db_object->query("
						SELECT *
						FROM {$tableprefix}cms_nodecategory
						WHERE nodeid = {$node['nodeid']}
					");
					while ($_nodecategory = $Db_object->fetch_array($nodecategories))
					{
						$nodecategory[] = $_nodecategory;
					}
				}

				#$detail = array_merge($detail, $extra_array);

				$return_array['data']["$detail[contentid]"]					= $detail;
				$return_array['data']["$detail[contentid]"]['node']			= $node;
				$return_array['data']["$detail[contentid]"]['nodeinfo']		= $nodeinfo;
				$return_array['data']["$detail[contentid]"]['nodeconfig']	= $nodeconfig;
				$return_array['data']["$detail[contentid]"]['nodecategory']	= $nodecategory;

				$return_array['lastid'] = $detail['contentid'];
			}

			$return_array['count'] = count($return_array['data']);

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_cms_section(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array('data' => array());

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbcms', 'Section');
		
			// Select * them, then let ImpExData fail out on any needed
			// Yes -- this could be done with a join in the above query but this way leaves the two datasets separate

			$dataset = $Db_object->query("
				SELECT *
				FROM {$tableprefix}cms_node
				WHERE contenttypeid = {$contenttypeid}
				ORDER BY nodeid
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed
				// Yes -- this could be done with a join in the above query but this way leaves the two datasets separate

				$navigation = $nodeinfo = $nodeconfig = $nodecategory = array();
				$nodeinfo = $Db_object->query_first("
					SELECT *
					FROM {$tableprefix}cms_nodeinfo
					WHERE nodeid = {$detail['nodeid']}
				");

				$nodeconfigs = $Db_object->query("
					SELECT *
					FROM {$tableprefix}cms_nodeconfig
					WHERE nodeid = {$detail['nodeid']}
				");
				while ($_nodeconfig = $Db_object->fetch_array($nodeconfigs))
				{
					$nodeconfig[] = $_nodeconfig;
				}

				$nodecategories = $Db_object->query("
					SELECT *
					FROM {$tableprefix}cms_nodecategory
					WHERE nodeid = {$detail['nodeid']}
				");
				while ($_nodecategory = $Db_object->fetch_array($nodecategories))
				{
					$nodecategory[] = $_nodecategory;
				}

				$navigation = $Db_object->query_first("
					SELECT *
					FROM {$tableprefix}cms_navigation
					WHERE nodeid = {$detail['nodeid']}
				");

				$return_array['data']["$detail[nodeid]]"]					= $detail;
				$return_array['data']["$detail[nodeid]]"]['nodeinfo']		= $nodeinfo;
				$return_array['data']["$detail[nodeid]]"]['nodeconfig']		= $nodeconfig;
				$return_array['data']["$detail[nodeid]]"]['nodecategory']	= $nodecategory;
				$return_array['data']["$detail[nodeid]]"]['navigation']		= $navigation;

				$return_array['lastid'] = $detail['nodeid'];
			}

			$return_array['count'] = count($return_array['data']);

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function get_cms_widget(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
	{
		$return_array = array('data' => array());

		// Check that there is not a empty value
		if(empty($per_page)) { return $return_array; }

		if ($databasetype == 'mysql')
		{
			$dataset = $Db_object->query("
				SELECT *
				FROM {$tableprefix}cms_widget
				ORDER BY widgetid
				LIMIT {$start_at}, {$per_page}
			");

			while ($detail = $Db_object->fetch_array($dataset))
			{
				// Select * them, then let ImpExData fail out on any needed
				// Yes -- this could be done with a join in the above query but this way leaves the two datasets separate
				$widgettype = $widgetconfig = $layoutwidget = array();

				$widgettype = $Db_object->query_first("
					SELECT *
					FROM {$tableprefix}cms_widgettype
					WHERE widgettypeid = {$detail['widgettypeid']}
				");

				$widgetconfigs = $Db_object->query("
					SELECT *
					FROM {$tableprefix}cms_widgetconfig
					WHERE widgetid = {$detail['widgetid']}
				");
				while ($_widgetconfig = $Db_object->fetch_array($widgetconfigs))
				{
					$widgetconfig[] = $_widgetconfig;
				}

				$layoutwidgets = $Db_object->query("
					SELECT *
					FROM {$tableprefix}cms_layoutwidget
					WHERE widgetid = {$detail['widgetid']}
				");
				while ($_layoutwidget = $Db_object->fetch_array($layoutwidgets))
				{
					$layoutwidget[] = $_layoutwidget;
				}
				
				$return_array['data']["$detail[widgetid]"]					= $detail;
				$return_array['data']["$detail[widgetid]"]['widgettype']	= $widgettype;
				$return_array['data']["$detail[widgetid]"]['widgetconfig']	= $widgetconfig;
				$return_array['data']["$detail[widgetid]"]['layoutwidget']	= $layoutwidget;

				$return_array['lastid'] = $detail['widgetid'];
			}

			$return_array['count'] = count($return_array['data']);

			return $return_array;
		}
		else
		{
			return false;
		}
	}

	function import_cms_grid($Db_object, $databasetype, $tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['poll'] === false))
				{
					$there = $Db_object->query_first("SELECT importcmsgridid FROM {$tableprefix}cms_grid WHERE importcmsgridid = " . intval(trim($this->get_value('mandatory', 'importcmsgridid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$title = $origtitle = $this->get_value('mandatory', 'title');
				// Does a grid of this same name already exist?
				while ($Db_object->query_first("
					SELECT title
					FROM {$tableprefix}cms_grid
					WHERE title = '" . addslashes($title) . "'
				"))
				{
					$title = 'imported' . rand(0,999) . '_' . $origtitle;
				}

				$Db_object->query("
					INSERT INTO " . $tableprefix . "cms_grid
					(
						importcmsgridid, title, gridhtml, auxheader, auxfooter, addcolumn, addcolumnsnap, addcolumnsize, gridcolumns, flattened
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importcmsgridid') . "',
						'" . addslashes($title) . "',
						'" . addslashes($this->get_value('mandatory', 'gridhtml')) . "',
						'" . intval($this->get_value('nonmandatory', 'auxheader')) . "',
						'" . intval($this->get_value('nonmandatory', 'auxfooter')) . "',
						'" . intval($this->get_value('nonmandatory', 'addcolumn')) . "',
						'" . intval($this->get_value('nonmandatory', 'addcolumnsnap')) . "',
						'" . intval($this->get_value('nonmandatory', 'addcolumnsize')) . "',
						'" . intval($this->get_value('nonmandatory', 'gridcolumns')) . "',
						'" . intval($this->get_value('nonmandatory', 'flattened')) . "'
					)
				");

				$gridid = $Db_object->insert_id();

				$Db_object->query("
					REPLACE INTO " . TABLE_PREFIX . "template
						(styleid, title, template, template_un, dateline, username, product, version)
					VALUES
						(
							0,
							'vbcms_grid_{$gridid}',
							'" . addslashes($this->get_value('nonmandatory', 'template')) . "',
							'" . addslashes($this->get_value('mandatory', 'gridhtml')) . "',
							" . TIMENOW . ",
							'',
							'vbcms',
							'" . addslashes($this->get_options_setting($Db_object, $databasetype, $tableprefix, 'templateversion')) . "'
						)
				");

				return $gridid;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_navigation($Db_object, $databasetype, $tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$Db_object->query("
					REPLACE INTO " . $tableprefix . "cms_navigation
					(
						importid, nodeid, nodelist
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importid') . "',
						'" . $this->get_value('mandatory', 'nodeid') . "',
						'" . addslashes($this->get_value('nonmandatory', 'nodelist')) . "'
					)
				");

				return true;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	function import_cms_layout($Db_object, $databasetype, $tableprefix)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['poll'] === false))
				{
					$there = $Db_object->query_first("SELECT importcmslayoutid FROM {$tableprefix}cms_layout WHERE importcmslayoutid = " . intval(trim($this->get_value('mandatory', 'importcmslayoutid'))));

					if(is_numeric($there[0]))
					{
						return false;
					}
				}

				$Db_object->query("
					INSERT INTO " . $tableprefix . "cms_layout
					(
						importcmslayoutid, title, gridid, template, status, contentcolumn, contentindex
					)
					VALUES
					(
						'" . $this->get_value('mandatory', 'importcmslayoutid') . "',
						'" . addslashes($this->get_value('mandatory', 'title')) . "',
						'" . intval($this->get_value('mandatory', 'gridid')) . "',
						'" . addslashes($this->get_value('nonmandatory', 'template')) . "',
						'" . $this->enum_check($this->get_value('nonmandatory', 'status'), array('draft','active','retired'), 'active') . "',
						'" . intval($this->get_value('nonmandatory', 'contentcolumn')) . "',
						'" . intval($this->get_value('nonmandatory', 'contentindex')) . "'
					)
				");

				return $Db_object->insert_id();
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Returns an array of the category ids key'ed to the import category id's
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		0|1				Wether or not to intval the import forum id
	*
	* @return	array	mixed			Data array[impforumid] = forumid
	*/
	function get_cms_category_ids($Db_object, $databasetype, $tableprefix, $pad = 0)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$categories = $Db_object->query("SELECT categoryid, importcmscategoryid FROM " . $tableprefix . "cms_category WHERE importcmscategoryid > 0");
				$categoryid = array();
				while ($category = $Db_object->fetch_array($categories))
				{
					if ($pad)
					{
						$impcategoryid = intval($category['importcmscategoryid']);
						$categoryid["$impcategoryid"] = $category['categoryid'];
					}
					else
					{
						$categoryid["$category[importcmscategoryid]"] = $category['categoryid'];
					}
				}
				$Db_object->free_result($categories);

				return $categoryid;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Returns an array of section nodeids
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		0|1				Wether or not to intval the import forum id
	*
	* @return	array	mixed			Data array[impforumid] = forumid
	*/
	function get_cms_section_parentnodes($Db_object, $databasetype, $tableprefix, $pad = 0)
	{
		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			{
				$sections = $Db_object->query("SELECT nodeid, importcmsnodeid FROM " . $tableprefix . "cms_node WHERE importcmsnodeid > 0 AND issection = 1");
				$nodeid = array();
				while ($node = $Db_object->fetch_array($sections))
				{
					if ($pad)
					{
						$impnodeid = intval($node['importcmsnodeid']);
						$nodeid["$impnodeid"] = $node['nodeid'];
					}
					else
					{
						$nodeid["$node[importcmsnodeid]"] = $node['nodeid'];
					}
				}
				$Db_object->free_result($sections);

				return $nodeid;
			}

			// Postgres database
			case 'postgresql':
			{
				return false;
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	 * Moves a node to be the child of another node.
	 *
	 * @param int $nodeid						- Id of the node to move
	 * @param int $parentid						- The parent / set root to move to
	 * @param int $order						- The order to place the node (0 first)
	 */
	function cms_move_node($Db_object, $databasetype, $tableprefix, $nodeid, $parentid, $order = false)
	{
			// Get the tree info for the src and new parent nodes
		$result = $Db_object->query("
			SELECT (nodeid = " . intval($parentid) . ") AS isparent, nodeleft, noderight, parentnode, nodeid
			FROM {$tableprefix}cms_node
			WHERE nodeid IN (" . intval($parentid) . ", " . intval($nodeid) . ")"
		);

		$parent = $source = false;
		while ($node = $Db_object->fetch_array($result))
		{
			if ($node['isparent'])
			{
				$parent = $node;
			}
			else
			{
				$source = $node;
			}
		}

		if (!$parent OR !$source)
		{
			return false;
		}

		if ($source['nodeid'] == $parentid)
		{
			return true;
		}

		if (($parent['nodeleft'] >= $source['nodeleft']) AND ($parent['nodeleft'] <= $source['nodeleft']))
		{
			return false;
		}

		// Get the width of the subtree we're moving
		 $src_width = ($source['noderight'] - $source['nodeleft']) + 1;

		 // Lock the node tree
		 //$Db_object->lock_tables(array('cms_node' => 'WRITE'));

		 // Create space for the moving node to the right of the new parent's tree
		 $Db_object->query("
		 	UPDATE {$tableprefix}cms_node
		 	SET nodeleft = IF (nodeid != " . intval($parent['nodeid']) . ", nodeleft + $src_width, nodeleft),
		 		noderight = noderight + $src_width
		 	WHERE noderight >= " . intval($parent['noderight'])
		 );

		// If the source was to the right of the new parent then it was shifted to make the gap
		if ($source['nodeleft'] > $parent['noderight'])
		{
			$source['nodeleft'] += $src_width;
			$source['noderight'] += $src_width;
		}

		 // Check the distance that the node will move.  This works in both directions.
		 $distance = ($parent['noderight'] - $source['nodeleft']);

		 // Update the moved sub tree with it's new left and right values
		 $Db_object->query("
		 	UPDATE {$tableprefix}cms_node
		 	SET nodeleft = nodeleft + " . intval($distance) . ",
		 		noderight = noderight + " . intval($distance) . ",
		 		parentnode = IF(nodeid = " . intval($source['nodeid']) . ", " . intval($parent['nodeid']) . ", parentnode)
		 	WHERE nodeleft BETWEEN " . intval($source['nodeleft']) . " AND " . intval($source['noderight'])
		);

		// Close the gap where the sub tree was moved from
		$Db_object->query("
			UPDATE {$tableprefix}cms_node
			SET nodeleft = IF(nodeleft >= " . intval($source['nodeleft']) . ", nodeleft - " . intval($src_width) . ", nodeleft),
				noderight = noderight - " . intval($src_width) . "
			WHERE noderight > " . $source['noderight']
		);

		// Unlock the node tree
		//$Db_object->unlock_tables();

		return true;
	}

} // ImpExDatabase class end 

/*======================================================================*/
?>
