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
* phpBB1_008 Import Poll module
*
* @package			ImpEx.phpBB1
*
*/
class phpBB1_008 extends phpBB1_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Poll';


	function phpBB1_008()
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


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of poll details
		$poll_array 	= $this->get_phpBB1_poll_details($Db_source, $source_database_type, $source_table_prefix, $poll_start_at, $poll_per_page);


		// Get some refrence arrays (use and delete as nessesary).
		// User info
		#$this->get_vb_userid($Db_target, $target_database_type, $target_table_prefix, $importuserid);
		#$this->get_one_username($Db_target, $target_database_type, $target_table_prefix, $theuserid, $id='importuserid');
		#$users_array = $this->get_user_array($Db_target, $databasetype, $tableprefix, $startat = null, $perpage = null);
		#$done_user_ids = $this->get_done_user_ids($Db_target, $target_database_type, $target_table_prefix);
		$user_ids_array = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
		#$user_name_array = $this->get_username($Db_target, $target_database_type, $target_table_prefix);
		// Groups info
		#$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);
		#$user_group_ids_by_name_array = $this->get_imported_group_ids_by_name($Db_target, $target_database_type, $target_table_prefix);
		#$bannded_groupid = $this->get_banned_group($Db_target, $target_database_type, $target_table_prefix);
		// Thread info
		#$this->get_thread_id($Db_target, $target_database_type, $target_table_prefix, &$importthreadid, &$forumid); // & left to show refrence
		#$thread_ids_array = $this->get_threads_ids($Db_target, $target_database_type, $target_table_prefix);
		// Post info
		#$this->get_posts_ids($Db_target, $target_database_type, $target_table_prefix);
		#$this->get_vb_post_id($Db_target, $target_database_type, $target_table_prefix, $import_post_id);
		// Category info
		#$cat_ids_array = $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);
		#$style_ids_array = $this->get_style_ids($Db_target, $target_database_type, $target_table_prefix, $pad=0);
		// Forum info
		#$forum_ids_array = $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix, $pad=0);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($poll_array) . ' polls</h4><p><b>From</b> : ' . $poll_start_at . ' ::  <b>To</b> : ' . ($poll_start_at + count($poll_array)) . '</p>');


		$poll_object = new ImpExData($Db_target, $sessionobject, 'poll');


		foreach ($poll_array as $poll_id => $poll)
		{
			$votes_array 		= $this->get_phpbb1_vote_voters($Db_source, $source_database_type, $source_table_prefix, $poll_id);
			$phpbb_thread_id	= $this->get_phpbb1_poll_thread_id($Db_source, $source_database_type, $source_table_prefix, $poll_id);
			$try = (phpversion() < '5' ? $poll_object : clone($poll_object));

			$bits = explode(',',$poll['choices']);
			
			foreach($bits as $id => $bit)
			{
				$bits[$id] = trim($bit);
			}
			
			$options	= implode('|||',$bits);
			$votes		= array();
			$numberoptions	= (substr_count($options, '|||')+1);
			$voters		= 0;

			for($i=1;$i <= $options; $i++)
			{
				$votes[$i] = 0;
			}

			foreach($votes_array AS $id => $vote)
			{
				$votes["$vote[choice]"]++;
				$voters++;
				$userid = $user_ids_array["$vote[user_id]"];
				$poll_voters_array[$userid] = $vote['choice'];
			}

			$votes = implode('|||',$votes);

			$try->set_value('mandatory', 'importpollid',		$poll_id);
			$try->set_value('mandatory', 'question',		$poll['title']);
			$try->set_value('mandatory', 'dateline',		strtotime($poll['date']));
			$try->set_value('mandatory', 'options',			$options);
			$try->set_value('mandatory', 'votes',			$votes);

			$try->set_value('nonmandatory', 'active',		'1');
			$try->set_value('nonmandatory', 'numberoptions',	$numberoptions);
			$try->set_value('nonmandatory', 'timeout',		'0');  // TODO: Is it ? $poll['vote_length']
			$try->set_value('nonmandatory', 'multiple',		'0');
			$try->set_value('nonmandatory', 'voters',		$voters);
			$try->set_value('nonmandatory', 'public',		'1');


			if($try->is_valid())
			{
				$result = $try->import_poll($Db_target, $target_database_type, $target_table_prefix);
				$vb_poll_id = $Db_target->insert_id();
				$imported = false;

				if($result)
				{
					if($try->import_poll_to_thread($Db_target, $target_database_type, $target_table_prefix, $vb_poll_id, $phpbb_thread_id))
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
# Autogenerated on : December 13, 2004, 9:50 pm
# By ImpEx-generator 1.4.
/*======================================================================*/
?>
