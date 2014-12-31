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
* Ubb Import Threads
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
*
*/
class ubb_classic_006 extends ubb_classic_000
{
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Threads';

	function ubb_classic_006()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads'))
				{
					$displayobject->display_now("<h4>Imported Threads have been cleared</h4>");
					$this->_restart = false;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_threads",
											 'Check database permissions and user table');
				}
			}
			$displayobject->update_basic('title','Import threads');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('threads','working'));
			$displayobject->update_html($displayobject->make_table_header('Step 7: Import Threads'));
			$displayobject->update_html($displayobject->make_description("<p>The importer will now start to import threads from your UBB.Classic board. Depending on the size of your board, this may take some time.</p>"));
			$displayobject->update_html($displayobject->make_input_code("Number of threads to import per cycle","threadsperpage","100"));
			$displayobject->update_html($displayobject->do_form_footer("Import Threads"));

			$sessionobject->add_session_var('finishedcurrectforum','true');
			$sessionobject->add_session_var('currectforum','0');
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
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');

		$class_num				= substr(get_class($this) , -3);


		if ($sessionobject->get_session_var('threads') == 'working')
		{
			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			if ($sessionobject->get_session_var('finishedcurrectforum')=='true')
			{
				if ($sessionobject->get_session_var('skipped') == 'TRUE')
				{
					$currentforum = intval($sessionobject->get_session_var('currectforum'));
					$sessionobject->add_session_var('skipped','FALSE');
				}
				else
				{
					$currentforum = intval($sessionobject->get_session_var('currectforum'))+1;
				}

				$forumcount = $Db_target->query_first("SELECT importforumid FROM {$target_table_prefix}forum WHERE importforumid={$currentforum}");

				if ($forumcount)
				{
					$sessionobject->set_session_var('currectforum',$currentforum);
					$sessionobject->set_session_var('finishedcurrectforum','false');
					$sessionobject->set_session_var('threadstartat','1');
					$sessionobject->set_session_var('threadsfilepath',"");

					$displayobject->display_now("<br />Moving onto forum " . $currentforum);
				}
				else
				{
					$higestforum = $Db_target->query_first("SELECT importforumid FROM {$target_table_prefix}forum ORDER BY importforumid DESC limit 1");

					if ($currentforum >= $higestforum[0] )
					{
						$finished = true;
					}
					elseif ($currentforum < $higestforum)
					{
						$displayobject->display_now("Skipping forum $currentforum as it isn't there.");
						$currentforum++;
						$sessionobject->set_session_var('currectforum',$currentforum);
						$sessionobject->add_session_var('skipped','TRUE');
					}
				}
			}

			if ($sessionobject->get_session_var('finishedcurrectforum')=='false')
			{
				$forum = $sessionobject->get_session_var('currectforum');
				$displayobject->display_now("<h3>$forumtitle</h3>");

				if ($sessionobject->get_session_var('threadstartat')=='')
				{
					$sessionobject->add_session_var('threadstartat','1');
				}

				if ($sessionobject->get_session_var('threadsfilepath')=="")
				{
					$displayobject->display_now("<p>Locating <i>forum_$forum.threads</i> file ....\n");

					$handle=opendir($sessionobject->get_session_var('ubbpath') . "/Forum$forum");

					while ($file = readdir($handle))
					{
						if (strstr($file, "private-"))
						{
							$privatedir = $file;
						}
					}
					closedir($handle);

					if ($privatedir!="")
					{
						if (file_exists($sessionobject->get_session_var('ubbpath') . "/Forum$forum/$privatedir/forum_$forum.threads"))
						{
							$sessionobject->add_session_var('threadsfilepath', $sessionobject->get_session_var('ubbpath') . "/Forum$forum/$privatedir");
						}
						elseif (file_exists($sessionobject->get_session_var('ubbpath') . "/Forum$forum/forum_$forum.threads"))
						{
							$sessionobject->add_session_var('threadsfilepath', $sessionobject->get_session_var('ubbpath') . "/Forum$forum");
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::resume Private directory found in file but couldn't find the path.",
													 'Has the directory been deleted and the file not updated ?');

							$displayobject->display_now("<b>not found</b>. No threads to import.</p>\n");
						}
					}
					else
					{
						if (file_exists($sessionobject->get_session_var('ubbpath') . "/Forum$forum/forum_$forum.threads"))
						{
							$sessionobject->add_session_var('threadsfilepath', $sessionobject->get_session_var('ubbpath') . "/Forum$forum");
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::resume threads file path found in file but couldn't find the actual path.",
													 'Has the directory been deleted and the file not updated ?');
							$displayobject->display_now("<b>not found</b>. No threads to import.</p>\n");
						}
					}

					if ($sessionobject->get_session_var('threadsfilepath')=="")
					{
							$sessionobject->add_error('fatal',
													 $this->_modulestring,
													 get_class($this) . "::resume Cannot find the threads file path.",
													 'Does the forum have any threads at all ? Is the directory correct ?');
						$displayobject->display_now("there is no threads file path");
					}
					else
					{
						$displayobject->display_now("... <b>Found</b><br /><br />");
					}
				}
				if(is_file($sessionobject->get_session_var('threadsfilepath') . "/forum_$forum.threads"))
				{
					$threadslist = file($sessionobject->get_session_var('threadsfilepath') . "/forum_$forum.threads");
					if (false)#('forum_6.threads' == "forum_$forum.threads")
					{
						echo "<pre>";
						print_r($threadslist);
						echo "</pre>";
					}
				}
				else
				{
					$displayobject->display_now("forum_$forum.threads missing moving on ");
					#continue;
				}

				$numlines = sizeof($threadslist)-2;

				$vbforumid 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

				$counter=0;
				for ($i = intval($sessionobject->get_session_var('threadstartat')); $i < (intval($sessionobject->get_session_var('threadstartat'))+intval($sessionobject->get_session_var('threadsperpage'))); $i++)
				{
					$finished = false;
					$counter++;
					if (preg_match("/q!([0-9]*)!/", $threadslist[$i], $regs) or preg_match("/\"([0-9]*)\"/",$threadslist[$i],$regs) or preg_match("/q~([0-9]*)~/", $threadslist[$i], $regs) or preg_match("/'([0-9]*)'/", $threadslist[$i], $regs))
					{
						if (is_file($sessionobject->get_session_var('threadsfilepath') . "/$regs[1].cgi"))
						{
							$threadfile = file($sessionobject->get_session_var('threadsfilepath') . "/$regs[1].cgi");

							$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

							while (list($line,$contents)=each($threadfile))
							{
								if ($line==0)
								{
									$try = (phpversion() < '5' ? $thread_object : clone($thread_object));
									$threadbits = explode("||", trim($contents));

									if ($vbforumid[$forum])
									{
										$try->set_value('mandatory', 'forumid' , $vbforumid[$forum]);
									}
									else
									{
										die("moo");
									}


									$try->set_value('mandatory', 'title', 			trim($threadbits[4]));
									$try->set_value('mandatory', 'importthreadid', 	$regs[1]);
									$try->set_value('mandatory', 'importforumid', 	$forum);

									$try->set_value('nonmandatory', 'visible', 		'1');

									$try->set_value('nonmandatory', 'open', 		(substr_count($threadbits[1], 'X') ? 0 : 1));
									// TODO: What does N mean ?


									/*
									$try->set_value('nonmandatory', 'firstpostid', );
									$try->set_value('nonmandatory', 'lastpost', );
									$try->set_value('nonmandatory', 'replycount', );
									$try->set_value('nonmandatory', 'postusername', );
									$try->set_value('nonmandatory', 'postuserid', );
									$try->set_value('nonmandatory', 'dateline', );
									$try->set_value('nonmandatory', 'views', );
									$try->set_value('nonmandatory', 'pollid', );
									$try->set_value('nonmandatory', 'lastposter', );
									$try->set_value('nonmandatory', 'iconid', );
									$try->set_value('nonmandatory', 'notes', );
									$try->set_value('nonmandatory', 'visible', );
									$try->set_value('nonmandatory', 'sticky', );
									$try->set_value('nonmandatory', 'votenum', );
									$try->set_value('nonmandatory', 'votetotal', );
									$try->set_value('nonmandatory', 'attach', );
									$try->set_value('nonmandatory', 'similar', );
									*/


									if($try->is_valid())
									{
										if($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
										{
											$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
											$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
											$imported = true;
										}
										else
										{
											$sessionobject->add_error('warning',
																	 $this->_modulestring,
																	 get_class($this) . "::import_thread failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
																	 'Check database permissions and user table');
											$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
											$displayobject->display_now("<br />Got thread " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
										}
									}
									else
									{
										$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
									}

									unset($try);
								}
							}
						}
						else
						{
							$sessionobject->add_error('warning',
													 $this->_modulestring,
													 get_class($this) . "::resume Found a thread in the list but could not find the corisponding file : " . $sessionobject->get_session_var('threadsfilepath') . "/$regs[1].cgi",
													 'Is the directory correct ?');
							$displayobject->display_now("<br /><b>No file :</b> " . $sessionobject->get_session_var('threadsfilepath') . "/$regs[1].cgi");
						}
					}
				}
			}

			if (intval($sessionobject->get_session_var('threadstartat')) +$counter > $numlines)
			{
				$displayobject->display_now("<h4>Done this forum</h4>");
				$sessionobject->set_session_var('finishedcurrectforum','true');
			}

			if ($finished)
			{
				$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																			$sessionobject->return_stats($class_num,'_time_taken'),
																			$sessionobject->return_stats($class_num,'_objects_done'),
																			$sessionobject->return_stats($class_num,'_objects_failed')
																			));

				$sessionobject->set_session_var('threads','done');
				$sessionobject->set_session_var('threadstartat','0');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('finishedcurrectforum','true');
				$sessionobject->set_session_var('006','FINISHED');
				$sessionobject->set_session_var('module','000');

				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
				$sessionobject->add_session_var('threadstartat',($sessionobject->get_session_var('threadstartat') + $counter));
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
	}
}
/*======================================================================*/
?>
