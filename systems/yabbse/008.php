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
* yabbse_008 Import Poll module
*
* @package			ImpEx.yabbse
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class yabbse_008 extends yabbse_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Poll';


	function yabbse_008()
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
			$displayobject->update_basic('title','Import Polls');
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
		$poll_object 			= new ImpExData($Db_target, $sessionobject, 'poll');

		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}

		// Get an array of poll details
		$poll_array 	= $this->get_yabbse_poll_details($Db_source, $source_database_type, $source_table_prefix, $poll_start_at, $poll_per_page);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($poll_array) . ' polls</h4><p><b>From</b> : ' . $poll_start_at . ' ::  <b>To</b> : ' . ($poll_start_at + count($poll_array)) . '</p>');

		foreach ($poll_array as $poll_id => $poll_details)
		{
			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));

			$options 		= '';
			$votes 			= '';
			$numberoptions 	= 0;
			$voters 		= 0;

			if($poll['option_1'])
			{
				$options 	.=  $poll['option_1'] 			. '|||';
				$votes 		.=  $poll['votes_1'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_1'];
			}
			if($poll['option_2'])
			{
				$options 	.=  $poll['option_2'] 			. '|||';
				$votes 		.=  $poll['votes_2'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_2'];
			}
			if($poll['option_3'])
			{
				$options 	.=  $poll['option_3'] 			. '|||';
				$votes 		.=  $poll['votes_3'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_3'];
			}
			if($poll['option_14'])
			{
				$options 	.=  $poll['option_4'] 			. '|||';
				$votes 		.=  $poll['votes_4'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_4'];
			}
			if($poll['option_5'])
			{
				$options 	.=  $poll['option_5'] 			. '|||';
				$votes 		.=  $poll['votes_5'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_5'];
			}
			if($poll['option_6'])
			{
				$options 	.=  $poll['option_6'] 			. '|||';
				$votes 		.=  $poll['votes_6'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_6'];
			}
			if($poll['option_7'])
			{
				$options 	.=  $poll['option_7'] 			. '|||';
				$votes 		.=  $poll['votes_7'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_7'];
			}
			if($poll['option_8'])
			{
				$options 	.=  $poll['option_8'] 			. '|||';
				$votes 		.=  $poll['votes_8'] . '|||';
				$numberoptions++;
				$voters 	+= $poll['votes_8'];
			}

			$options = substr($options, 0, -3);
			$votes = substr($votes, 0, -3);


			// Mandatory
			$try->set_value('mandatory', 'question',				$this->html_2_bb($poll_details['question']));
			$try->set_value('mandatory', 'dateline',				time()); // Oh well ..........
			$try->set_value('mandatory', 'options',					$options);
			$try->set_value('mandatory', 'votes',					$votes);
			$try->set_value('mandatory', 'importpollid',			$poll_details['ID_POLL']);


			// Non Mandatory
			$try->set_value('nonmandatory', 'active',			'1');
			$try->set_value('nonmandatory', 'numberoptions',	$numberoptions);
			$try->set_value('nonmandatory', 'timeout',			'0');
			$try->set_value('nonmandatory', 'multiple',			'0');
			$try->set_value('nonmandatory', 'voters',			$voters);
			$try->set_value('nonmandatory', 'public',			'1');


			// Check if poll object is valid
			$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);

			$vb_poll_id = $Db_target->insert_id();
			$thread_id = $this->get_yabbse_poll_thread_id($Db_source, $source_database_type, $source_table_prefix, $poll_details['ID_POLL']);

			if($result)
			{
				if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $thread_id))
				{
					$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Poll  -> " . $try->get_value('mandatory', 'question'));
					$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->add_error(
						'warning',
						$this->_modulestring,
						get_class($this) . "::import_poll_to_thread failed for " . $try->get_value('mandatory', 'question'),
						'Check database permissions'
					);
					$displayobject->display_now("<br />Poll " . $try->get_value('mandatory','question') . " and <b>DID </b> imported to the " . $target_database_type . " database but isn't assigned to the thread");
				}
			}
			else
			{
				$sessionobject->add_error(
					'warning',
					$this->_modulestring,
					get_class($this) . "::import_poll failed for " . $try->get_value('mandatory', 'question'),
					'Check database permissions'
				);
				$displayobject->display_now("<br />Poll " . $try->get_value('mandatory','question') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
			}
			unset($try);
		}// End resume


		$the_end = time() - $start;
		$sessionobject->add_session_var('last_pass', $the_end);


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
		}

		$sessionobject->set_session_var('pollstartat',$poll_start_at+$poll_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
	}// End resume
}//End Class
# Autogenerated on : May 17, 2004, 3:28 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
