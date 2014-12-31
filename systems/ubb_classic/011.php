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
* ubb_classic_012 Import Poll module
*
* @package			ImpEx.ubb_classic
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ubb_classic_011 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '006';
	var $_modulestring 	= 'Import Poll';


	function ubb_classic_011()
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
		$polls_path 	= $sessionobject->get_session_var('pollspath');
		$poll_array 	= $this->get_ubb_classic_polls_details($polls_path, $poll_start_at, $poll_per_page);
		$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($poll_array) . ' polls</h4><p><b>From</b> : ' . $poll_start_at . ' ::  <b>To</b> : ' . ($poll_start_at + count($poll_array)) . '</p>');

		$poll_object = new ImpExData($Db_target, $sessionobject, 'poll');

		$poll_path = $sessionobject->get_session_var('pollspath');
		
		foreach ($poll_array as $name => $filename)
		{
			$details = $this->get_ubb_poll_results_details($poll_path, $filename);

			$vb_threadid = $idcache->get_id('threadandforum',intval($details['forum']), intval($details['topic']));

			$options 		= '';
			$votes 			= array();
			$voters 		= 0;
			$user_vote_array = array();

			#echo '<br> Start time -> ' . 	$details['start_time'];
			#echo '<br> End time -> ' . 		$details['stop_time'];

			foreach($details['questions'][0]['options'] as $num => $text)
			{
				if($text)
				{
					$options 	.=  $text . '|||';
				}
			}

			$options = substr($options, 0, -3);
		 
			foreach ($details['answers'] as $cust_id => $vote)
			{
					$user_vote_array[$user_ids_array[intval($cust_id)]] = key($vote[0]);
					$votes[key($vote[0])]++;
					$voters++;
			}

			foreach ($votes as $key => $val)
			{
				if(!$val)
				{
					$votes[$key] = 0;
				}
			}

			$votes = array_pad($votes, count($options), 0);

			$votes = implode('|||',$votes);

			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));
			// Mandatory
			$try->set_value('mandatory', 'question',			$details['questions'][0]['title']);
			$try->set_value('mandatory', 'dateline',			time()); // Oh well ..........
			$try->set_value('mandatory', 'options',				$options);
			$try->set_value('mandatory', 'votes',				$votes);
			$try->set_value('mandatory', 'importpollid',		crc32($filename));

			// Non Mandatory
			$try->set_value('nonmandatory', 'active',			'1');
			$try->set_value('nonmandatory', 'numberoptions',	$details['questions'][0]['opts']);
			$try->set_value('nonmandatory', 'timeout',			'0');

			if($details['questions'][0]['limit'] == 1)
			{
				$try->set_value('nonmandatory', 'multiple',		'0');
			}
			else
			{
				$try->set_value('nonmandatory', 'multiple',		'1');
			}

			$try->set_value('nonmandatory', 'voters',			$voters);
			$try->set_value('nonmandatory', 'public',			'1');

			// Check if poll object is valid
			$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);

			$vb_poll_id = $Db_target->insert_id();

			if($result)
			{
				if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $vb_threadid, true))
				{
					if($try->import_poll_voters($Db_target, $target_database_type, $target_table_prefix, $user_vote_array, $vb_poll_id))
					{
						$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Poll  -> " . $try->get_value('mandatory', 'question'));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::import_poll_to_thread worked but did not attached voters",
													 'Check database permissions and thread table');
							$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
							$displayobject->display_now("<br />Got poll " . $try->get_value('mandatory', 'question') . " and <b>DID NOT</b> attach voters");
					}
				}
				else
				{
					$sessionobject->add_error(
						'warning',
						$this->_modulestring,
						get_class($this) . "::import_poll_to_thread failed for " . $try->get_value('mandatory', 'question'),
						'Check database permissions'
					);
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
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
				$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
				$displayobject->display_now("<br />Poll " . $try->get_value('mandatory','question') . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
			}
		unset($try);
		}

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

/*======================================================================*/
?>

