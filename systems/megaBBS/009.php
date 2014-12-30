<?php if (!defined('IDIR')) { die; }
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
* megaBBS_009 Import Poll module
*
* @package			ImpEx.megaBBS
* @version			$Revision: 2321 $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class megaBBS_009 extends megaBBS_000
{
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Poll';

	function megaBBS_009()
	{
		// Constructor
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_polls'))
				{
					$displayobject->display_now('<h4>Imported polls have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_polls','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Poll');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_poll','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Polls to import per cycle (must be greater than 1)','pollperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pollstartat','0');
			$sessionobject->add_session_var('polldone','0');
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


		// Per page vars
		$poll_start_at			= $sessionobject->get_session_var('pollstartat');
		$poll_per_page			= $sessionobject->get_session_var('pollperpage');
		$class_num				= substr(get_class($this) , -3);

		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of poll details
		$poll_array 	= $this->get_megaBBS_poll_details($Db_source, $source_database_type, $source_table_prefix, $poll_start_at, $poll_per_page);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($poll_array) . ' polls</h4><p><b>From</b> : ' . $poll_start_at . ' ::  <b>To</b> : ' . ($poll_start_at + count($poll_array)) . '</p>');


		$poll_object = new ImpExData($Db_target, $sessionobject, 'poll');

		foreach ($poll_array as $poll_id => $poll_details)
		{
			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));
			
			$poll_thread_details = $this->get_poll_thread_details($Db_source, $source_database_type, $source_table_prefix, $poll_id);
			$poll_voter_details = $this->get_poll_voter_details($Db_source, $source_database_type, $source_table_prefix, $poll_id);
			
			$options 				= '';
			$votes 					= '';
			$numberoptions 			= 0;
			$voters 				= 0;
			
			if(!is_array($poll_details['options']))
			{
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
				$displayobject->display_now("<br />Invalid options, corrupt source data.");
				continue;
			}
		
			foreach ($poll_details['options'] as $vote_option_id => $option)
			{
				
				$options 	.= $option['description'] 	. '|||';
				$votes 		.= $option['votes'] 		. '|||';
				$voters		+= intval($option['votes']);
				$numberoptions++;
			}

			$options = substr($options, 0, -3);
			$votes = substr($votes, 0, -3);
			
			if(is_array($poll_voter_details))
			{
				foreach ($poll_voter_details as $id => $memberid)
				{
					$tru_id = $idcache->get_id('user', $memberid);
					$poll_voters_array[$tru_id] = 0;
				}
			}
			
			// Mandatory
			$try->set_value('mandatory', 'question',			$poll_thread_details['threadsubject']);
			$try->set_value('mandatory', 'dateline',			strtotime($poll_details['datecreated']));
			$try->set_value('mandatory', 'options',				$options);
			$try->set_value('mandatory', 'votes',				$votes);
			$try->set_value('mandatory', 'importpollid',		$poll_id);


			// Non Mandatory
			$try->set_value('nonmandatory', 'active',			$this->iif($poll_details['closed'],0,1));
			$try->set_value('nonmandatory', 'numberoptions',	$numberoptions);
			$try->set_value('nonmandatory', 'timeout',			'0');  // TODO: Is it ? $poll['vote_length']
			$try->set_value('nonmandatory', 'multiple',			'0');
			$try->set_value('nonmandatory', 'voters',			$voters);
			$try->set_value('nonmandatory', 'public',			'1');

			// Check if poll object is valid
			if($try->is_valid())
			{
				$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);
				$vb_poll_id = $Db_target->insert_id();
				$imported = false;

				if($result)
				{
					if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $poll_thread_details['threadid']))
					{
						if($try->import_poll_voters($Db_target, $target_database_type, $target_table_prefix, $poll_voters_array, $vb_poll_id))
						{
							$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Poll  -> " . $try->get_value('mandatory','question'));
							$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							$imported = true;
						}
						else
						{
							$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_poll_to_thread worked but did not attached voters",
												 'Check database permissions and thread table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got poll " . $poll['vote_text'] . " and <b>DID NOT</b> attach voters");
						}
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_poll_to_thread failed Poll imported but not attached to thread",
												 'Check database permissions and thread table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got poll " . $poll['vote_text'] . " and <b>DID NOT</b> attach to the correct thread");
					}
				}
				else
				{
					$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_poll failed",
												 'Check database permissions and thread table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Poll " . $poll['vote_text'] . " failed");
				}

				if(!$imported)
				{
					$sessionobject->add_error('warning',$this->_modulestring,
								get_class($this) . "::import_poll failed for " . $poll['topic_id'] . " Have to check 3 tables",
								'Check database permissions and user table');
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
					$displayobject->display_now("<br />Problem with poll on thread " . $poll['topic_id']);
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid post object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach

		// Check for page end
		if (count($poll_array) == 0 OR count($poll_array) < $poll_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');




			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_poll','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('pollstartat',$poll_start_at+$poll_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : December 22, 2004, 5:35 pm
# By ImpEx-generator 1.4.
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
