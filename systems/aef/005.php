<?php 
if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
*
* @package			ImpEx.aef
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name $
* @date				$Date: $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class aef_005 extends aef_000
{
	var $_dependent = '004';

	function aef_005(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_forum'];
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$class_num = substr(get_class($this) , -3);

		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{;
					$displayobject->display_now("<h4>{$displayobject->phrases['forums_cleared']}</h4>");
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error($Db_target, 'fatal', $class_num, 0, $displayobject->phrases['forum_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title',$displayobject->phrases['import_forum']);
			$displayobject->update_html($displayobject->do_form_header('index', $class_num));
			$displayobject->update_html($displayobject->make_hidden_code($class_num, 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['units_per_page'], 'perpage', 1000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var("{$class_num}_objects_done", '0');
			$sessionobject->add_session_var("{$class_num}_objects_failed", '0');
			$sessionobject->add_session_var('startat','0');
			$sessionobject->add_session_var('categoriesfinished','FALSE');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var($class_num, 'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$t_db_type		= $sessionobject->get_session_var('targetdatabasetype');
		$t_tb_prefix	= $sessionobject->get_session_var('targettableprefix');
		$s_db_type		= $sessionobject->get_session_var('sourcedatabasetype');
		$s_tb_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		// Per page vars
		$start_at		= $sessionobject->get_session_var('startat');
		$per_page		= $sessionobject->get_session_var('perpage');
		$class_num		= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var("{$class_num}_start"))
		{
			$sessionobject->timing($class_num , 'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		if($sessionobject->get_session_var('categoriesfinished') == 'FALSE')
		{
			// Sort out the categories
			$data_array = $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}categories", 'cid', 0, $start_at, $per_page);

			$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['categories'], $start_at);
		
			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($data_array['data'] as $cat_id => $cat)
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));

				$try->set_value('mandatory', 'title', 				$cat['name']);
				$try->set_value('mandatory', 'displayorder',		$cat['order']);
				$try->set_value('mandatory', 'parentid',			'-1');
				$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
				$try->set_value('mandatory', 'importforumid',		'0');
				$try->set_value('mandatory', 'importcategoryid',	$cat_id);

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $t_db_type, $t_tb_prefix))
					{
						$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $try->get_value('mandatory', 'title'));
						$sessionobject->add_session_var($class_num  . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $cat_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}
				}
				else
				{
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $cat_id, $displayobject->phrases['invalid_object'], $try->_failedon);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				}
				unset($try);
			}
			$sessionobject->add_session_var('categoriesfinished','TRUE');
		}
		else		
		{
			// Sort out the forums
			$data_array 	= $this->get_source_data($Db_source, $s_db_type, "{$s_tb_prefix}forums", 'fid', 0, $start_at, $per_page);
			$cat_ids_array 	= $this->get_category_ids($Db_target, $t_db_type, $t_tb_prefix);

			$displayobject->print_per_page_pass($data_array['count'], $displayobject->phrases['forums'], $start_at);

			$ImpExData_object = new ImpExData($Db_target, $sessionobject, 'forum');

			foreach ($data_array['data'] as $import_id => $data)
			{
				$try = (phpversion() < '5' ? $ImpExData_object : clone($ImpExData_object));
	
				// Mandatory
				$try->set_value('mandatory', 'parentid',				$cat_ids_array["$data[cat_id]"]);
				$try->set_value('mandatory', 'importforumid',			$import_id);
				$try->set_value('mandatory', 'importcategoryid',		$data['cat_id']);
				$try->set_value('mandatory', 'displayorder',			$data['forum_order']);
				$try->set_value('mandatory', 'options',					$this->_default_forum_permissions);
				$try->set_value('mandatory', 'title',					$data['fname']);
				
				// Non mandatory
				$try->set_value('nonmandatory', 'description',			$data['description']);
				$try->set_value('nonmandatory', 'replycount',			$data['nposts']);
				 
				// Check if object is valid
				if($try->is_valid())
				{
					if($try->import_forum($Db_target, $t_db_type, $t_tb_prefix))
					{
						if(shortoutput)
						{
							$displayobject->display_now('.');
						}
						else
						{
							$displayobject->display_now('<br /><span class="isucc">' . $import_id . ' :: <b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['forum'] . ' -> ' . $data['fname']);
						}
						$sessionobject->add_session_var("{$class_num}_objects_done",intval($sessionobject->get_session_var("{$class_num}_objects_done")) + 1 );
					}
					else
					{
						$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
						$sessionobject->add_error($Db_target, 'warning', $class_num, $import_id, $displayobject->phrases['forum_not_imported'], $displayobject->phrases['forum_not_imported_rem']);
						$displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['forum_not_imported']}");
					}// $try->import_forum
				}
				else
				{
					$sessionobject->add_session_var("{$class_num}_objects_failed",intval($sessionobject->get_session_var("{$class_num}_objects_failed")) + 1 );
					$sessionobject->add_error($Db_target, 'invalid', $class_num, $import_id, $displayobject->phrases['invalid_object'] . ' ' . $try->_failedon, $displayobject->phrases['invalid_object_rem']);
					$displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
				}// is_valid
				unset($try);
			}// End foreach
	
			// Check for page end
			if ($data_array['count'] == 0 OR $data_array['count'] < $per_page)
			{
				$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var("{$class_num}_start");
	
				$this->build_forum_child_lists($Db_target, $t_db_type, $t_tb_prefix);
	
				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
					$sessionobject->return_stats($class_num, '_time_taken'),
					$sessionobject->return_stats($class_num, '_objects_done'),
					$sessionobject->return_stats($class_num, '_objects_failed')
				));
	
				$sessionobject->set_session_var($class_num , 'FINISHED');
				$sessionobject->set_session_var('module', '000');
				$sessionobject->set_session_var('autosubmit', '0');
			}
	
			$sessionobject->set_session_var('startat', $data_array['lastid']);
		}
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : January 11, 2008, 2:38 pm
# By ImpEx-generator 2.0
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: $
|| ####################################################################
\*======================================================================*/
?>
