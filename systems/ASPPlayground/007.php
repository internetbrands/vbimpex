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
* ASPPlayground_007 Import Post module
*
* @package			ImpEx.ASPPlayground
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/

class ASPPlayground_007 extends ASPPlayground_000
{
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Post';

	function ASPPlayground_007()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts'))
				{
					$displayobject->display_now('<h4>Imported posts have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_posts','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Post');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_post','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Posts to import per cycle (must be greater than 1)','postperpage', 2000));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat','0');
			$sessionobject->add_session_var('postdone','0');
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
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of post details
		$post_array 	= $this->get_ASPPlayground_post_details($Db_source, $source_database_type, $source_table_prefix, $post_start_at, $post_per_page);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($post_array) . ' posts</h4><p><b>From</b> : ' . $post_start_at . ' ::  <b>To</b> : ' . ($post_start_at + count($post_array)) . '</p>');

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		foreach ($post_array as $post_id => $post_details)
		{
			$try = (phpversion() < '5' ? $post_object : clone($post_object));
			// Mandatory
			$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $post_details['threadid']));
			$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $post_details['userid']));
			$try->set_value('mandatory', 'importthreadid',		$post_details['threadid']);

			// Non Mandatory
			if ($post_details['parent'] ==  $post_details['threadid'])
			{
				$try->set_value('nonmandatory', 'parentid',		'0');
			}
			else
			{
				$try->set_value('nonmandatory', 'parentid',		$idcache->get_id('post', $post_details['parent']));
			}

			$try->set_value('nonmandatory', 'username',			$idcache->get_id('username', $post_details['userid']));
			$try->set_value('nonmandatory', 'title',			$post_details['title']);
			$try->set_value('nonmandatory', 'dateline',			strtotime($post_details['dateline']));
			$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($this->ASPPlayground_html($post_details['pagetext'])));
			$try->set_value('nonmandatory', 'allowsmilie',		'1');
			$try->set_value('nonmandatory', 'showsignature',	'1');
			$try->set_value('nonmandatory', 'ipaddress',		$post_details['ipaddress']);
			#$try->set_value('nonmandatory', 'iconid',			$post_details['iconid']);
			$try->set_value('nonmandatory', 'visible',			'1');
			#$try->set_value('nonmandatory', 'attach',			$post_details['attach']);
			$try->set_value('nonmandatory', 'importpostid',		$post_id);

			// Check if post object is valid
			if($try->is_valid())
			{
				if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: post -> ' . $post_details['title']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar post and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($post_array) == 0 OR count($post_array) < $post_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');


			$displayobject->display_now('Updateing parent ids to allow for a threaded view....');


			if ($this->update_post_parent_ids($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->display_now('Done !');
			}
			else
			{
				$displayobject->display_now('Error updating parent ids');
			}


			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_post','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('poststartat',$post_start_at+$post_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : December 1, 2004, 3:44 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
