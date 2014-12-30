<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is ©2000-2014 vBulletin Solutions Inc. # ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/**
* webcrossing_002 Import Forum
*
* @package			ImpEx.webcrossing
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class webcrossing_002 extends webcrossing_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '001';
	var $_modulestring 	= 'Import Forum';

	function webcrossing_003()
	{
		// Constructor
	}
	
	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				// Need to clean out the whole board. 
				$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users');
				$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_forums');
				$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads');
				$this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts');
			}

			// Start up the table
			$displayobject->update_basic('title','Import Board');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_user','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Tags to import per cycle (must be greater than 1)','tagspp',5000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Do it !!','Reset'));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('tagstartat','0');
			$sessionobject->add_session_var('tagsdone','0');
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
		// Timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
		}
		
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');
		$class_num				= substr(get_class($this) , -3);

		$start = explode(' ', microtime());

		// Create handelers and parsers
		if ($sessionobject->get_session_var('handler'))
		{
			$parser_handler = unserialize($sessionobject->get_session_var('handler'));
		}
		else
		{
			$parser_handler =& new ParserHandler();
		}
		
		$parser =& new Webcrossing_Parser();
		// ########
		
		// Grab the file 
		if (!$parser->set_source($sessionobject->get_session_var('filepath')))
		{
			echo "couldn't open " . $sessionobject->get_session_var('filepath') . " for parsing";
			exit;
		}
		// ########
		
		// Set the parser start and per page
		if ($sessionobject->get_session_var('tagstartat'))
		{
			$parser->set_start($sessionobject->get_session_var('tagstartat'));
		}
		
		if($sessionobject->get_session_var('tagspp'))
		{
			$parser_handler->perpage = $sessionobject->get_session_var('tagspp');
		}
		// ########
		
		// Set up parser and handeler
		$parser_handler->Db_object 			=&  $Db_target;
		$parser_handler->session			=&  $sessionobject;
		$parser_handler->target_db_type		=  $target_database_type;
		$parser_handler->target_db_prefix	= $target_table_prefix;
		
		$parser->ignore_empty_cdata(true);
		$parser->set_handler(array(&$parser_handler, 'parser_callback'));
		// #######
		
		// Just do it ....
		$parser->parse(false);
		
		
		$end = explode(' ', microtime());
		
		echo "<p>Time: " . (($end[0] - $start[0]) + ($end[1] - $start[1])) . "</p>";

		if ($parser_handler->eof)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

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
			$sessionobject->set_session_var('import_user','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$sessionobject->add_session_var('tagstartat',$parser->end_position);
			$sessionobject->add_session_var('handler', serialize($parser_handler));
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}// End resume
}//End Class
# Autogenerated on : April 15, 2005, 12:49 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
