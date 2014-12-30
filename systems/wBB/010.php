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
* wBB Import Polls
*
* @package 		ImpEx.wBB
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class wBB_010 extends wBB_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '007';
	var $_modulestring 	= 'Import Polls';

	function phpBB2_010()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_polls'))
				{
					$this->_restart = FALSE;
					$displayobject->display_now("<h4>Imported Polls have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_polls",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import posts');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('polls','working'));
			$displayobject->update_html($displayobject->make_table_header('Import Polls'));
			$displayobject->update_html($displayobject->make_description('<p>The importer will now start to import polls from your wBB board. Depending on the size of your board, this may take some time.</p>'));
			$displayobject->update_html($displayobject->make_input_code('Number of posts to import per cycle','pollsperpage','10'));
			$displayobject->update_html($displayobject->do_form_footer('Import polls'));


			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');


			$sessionobject->add_session_var('pollsstartat','0');
		}
		else
		{
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($sessionobject->get_session_var('polls') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');

			$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
			$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');

			$polls_start_at 		= $sessionobject->get_session_var('pollsstartat');
			$polls_per_page 		= $sessionobject->get_session_var('pollsperpage');

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if(intval($polls_per_page) == 0)
			{
				$polls_per_page = 150;
			}

			$polls_array 			= $this->get_wBB_polls_details($Db_source, $source_database_type, $source_table_prefix, $polls_start_at, $polls_per_page);
			$thread_ids				= $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);

			$to = $polls_start_at + $polls_per_page;
			$last_pass = $sessionobject->get_session_var('last_pass');
			$displayobject->display_now("<h4>Importing " . count($polls_array) . " polls</h4><p><b>From</b> : " . $polls_start_at . " ::  <b>To</b> : " . ($polls_start_at + count($polls_array)) ."</p><p><b>Last pass took</b> : " . $last_pass . " seconds</p>");

			$start = time();
			$poll_object = new ImpExData($Db_target, $sessionobject, 'poll');


			foreach ($polls_array as $poll_id => $poll)
			{
				$questions				= $this->get_wBB_polls_questions($Db_source, $source_database_type, $source_table_prefix, $poll_id);
				$poll_voters_array		= $this->get_wBB_poll_results_details($Db_source, $source_database_type, $source_table_prefix, $poll_id);

				$try = (phpversion() < '5' ? $poll_object : clone($poll_object));

				$options 				= '';
				$votes 					= '';
				$numberoptions 			= 0;
				$voters 				= 0;

				// Get the questions
				foreach ($questions as $id => $option)
				{
					$options 	.= $option['polloption'] 	. '|||';
					$votes 		.= $option['votes'] 		. '|||';
					$voters		+= intval($option['votes']);
					$numberoptions++;
				}

				$options = substr($options, 0, -3);
				$votes = substr($votes, 0, -3);


				$try->set_value('mandatory', 'importpollid',		$poll_id);
				$try->set_value('mandatory', 'question',			$poll['question']);
				$try->set_value('mandatory', 'dateline',			$poll['starttime']);
				$try->set_value('mandatory', 'options',				$options);
				$try->set_value('mandatory', 'votes',				$votes);

				$try->set_value('nonmandatory', 'active',			'1');
				$try->set_value('nonmandatory', 'numberoptions',	$numberoptions);
				$try->set_value('nonmandatory', 'timeout',			$poll['timeout']);  // TODO: Is it ? $poll['vote_length']
				$try->set_value('nonmandatory', 'multiple',			$poll['choicecount']);
				$try->set_value('nonmandatory', 'voters',			$voters);
				$try->set_value('nonmandatory', 'public',			'1');


				if($try->is_valid())
				{
					$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);
					$vb_poll_id = $Db_target->insert_id();
					$imported = false;

					if($result)
					{
						if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $poll['threadid']))
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
			}


			$the_end = time() - $start;
			$sessionobject->add_session_var('last_pass', $the_end);

			if (count($polls_array) == 0 OR count($polls_array) < $polls_per_page)
			{
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
											$sessionobject->return_stats($class_num, '_time_taken'),
											$sessionobject->return_stats($class_num, '_objects_done'),
											$sessionobject->return_stats($class_num, '_objects_failed')
																			));

				$sessionobject->set_session_var($class_num, 'FINISHED');
				$sessionobject->set_session_var('polls','done');
				$sessionobject->set_session_var('module','000');
				$sessionobject->set_session_var('autosubmit','0');
				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
			$sessionobject->set_session_var('pollsstartat',$polls_start_at+$polls_per_page);
			$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
		else
		{
			$displayobject->display_now('Going to the main page...');
			$displayobject->update_html($displayobject->print_redirect('index.php'));
		}
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>
