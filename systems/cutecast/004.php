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
* cutecast_004 Import Forum module
*
* @package			ImpEx.cutecast
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class cutecast_004 extends cutecast_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Forum';


	function cutecast_004()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_forums'))
				{
					$displayobject->display_now('<h4>Imported forums have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_forums','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Forum');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('forums','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Forums to import per cycle (must be greater than 1)','forumperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('forumstartat','0');
			$sessionobject->add_session_var('forumdone','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
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

		// Per page vars
		$forum_start_at			= $sessionobject->get_session_var('forumstartat');
		$forum_per_page			= $sessionobject->get_session_var('forumperpage');
		$class_num				= substr(get_class($this) , -3);


		if ($sessionobject->get_session_var('forums') == 'working')
		{
			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}


			$categoryfile = file($sessionobject->get_session_var('txtpath') . "/forums.txt");

			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');
			$displayobject->display_now("<hr /><p><b>Categories.</b></p><hr />");

			$display_order = 0;

			while (list($line,$contents)=each($categoryfile))
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));
				$matches = array();
				preg_match("#(\d+)\t(.*)\t(\w+)#i", $contents, $matches);

				$try->set_value('mandatory', 	'title', 			$matches[3]);
				$try->set_value('mandatory', 	'displayorder',		++$display_order);
				$try->set_value('mandatory', 	'parentid',			'-1');
				$try->set_value('mandatory', 	'importforumid',	'0');
				$try->set_value('mandatory', 	'options',			$this->_default_cat_permissions);
				$try->set_value('mandatory', 	'importcategoryid',	$matches[1]);


				if($try->is_valid())
				{
					if($try->import_category($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><font color=\"#00FF00\"><b>" . $try->how_complete() . "%</b></font> :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);

				}
				unset($try);
			}

			$displayobject->display_now("<hr /><p><b>Forums.</b></p><hr />");

			$vbcategoryid = $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
			$forumsfile = file($sessionobject->get_session_var('txtpath') . "/forums.txt");
			$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

			while (list($line,$contents)=each($forumsfile))
			{
				$matches = array();
				preg_match("#(\d+)\t(.*)\t(\w+)#i", $contents, $matches);
				// $matches[1] is importcategoryid
				$importcategoryid = $matches[1];
				// $matches[2] is a list of forumids

				$forumids = array();
				$forumids = explode(',', trim($matches[2]));

				$displayorder = 0;
				foreach ($forumids AS $importforumid)
				{
					// lets check we have a file first!
					if (!file_exists($sessionobject->get_session_var('datapath') . "/forum$importforumid/forum.db"))
					{
						continue;
					}
					$forumfile = file($sessionobject->get_session_var('datapath') . "/forum$importforumid/forum.db");
					$try 		= $forum_object;
					$options 	= 0;
					$private 	= false;

					foreach ($forumfile AS $file_value)
					{
						$forumdata = array();
						preg_match("#(\w+)=(.*)#", $file_value, $forumdata);
						if (!empty($forumdata[2]))
						{
							switch ($forumdata[1])
							{
								case 'title':
									$try->set_value('mandatory', 'title',	$forumdata[2]);
								break;
								case 'description':
									$try->set_value('nonmandatory', 'description',	$forumdata[2]);
								break;
								case 'rule':
									if ($forumdata[2] == 'open')
									{
										$options += 1;
									}
								break;
							}
						}
					}

					$try->set_value('mandatory', 'importcategoryid',	$importcategoryid);
					$try->set_value('mandatory', 'displayorder',	++$displayorder);
					$try->set_value('mandatory', 'importforumid',	$importforumid);
					$try->set_value('mandatory', 'parentid',	$vbcategoryid[$importcategoryid]);

					// Options			=> 1,
					//'allowposting'      => 2,
					//'cancontainthreads' => 4,
					//'moderatenewpost'   => 8,
					//'moderatenewthread' => 16,
					//'moderateattach'    => 32,
					$options += 64; //'allowbbcode'       => 64,
					$options += 128; //'allowimages'       => 128,
					$options += 512; //'allowsmilies'      => 512,
					$options += 1024; //'allowicons'      => 1024,
					$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);
	
	
					if ($try->is_valid())
					{
						$forum_result = $try->import_forum($Db_target, $target_database_type, $target_table_prefix);
						$forum_id = $Db_target->insert_id();
	
						if ($forum_result)
						{
							if ($private)
							{
								if ($this->set_forum_private($Db_target, $target_database_type, $target_table_prefix, $forum_id))
								{
									$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
									$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
								}
								else
								{
									$displayobject->display_now("<br /><span class=\"ifail\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title') . " imported, PRIVATE status NOT set, please update.");
									$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
								}
							}
							else
							{
								$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
								$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							}
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
													 'Check database permissions and user table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
							$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
						}
					}
					else
					{
						$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
					}
					unset($try);
				}
			}


			$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);


			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																		$sessionobject->return_stats($class_num,'_time_taken'),
																		$sessionobject->return_stats($class_num,'_objects_done'),
																		$sessionobject->return_stats($class_num,'_objects_failed')
																		));

			$displayobject->update_basic('displaymodules','FALSE');

			$sessionobject->set_session_var('forums','done');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');

			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$displayobject->update_html("<p align=\"center\"><b>Skipping forums import.</b></p>");
			$sessionobject->set_session_var('forums','done');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
	}// End resume
}//End Class
# Autogenerated on : June 10, 2004, 12:46 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>

