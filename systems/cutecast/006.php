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
* cutecast_006 Import Post module
*
* @package			ImpEx.cutecast
*
*/
class cutecast_006 extends cutecast_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '005';
	var $_modulestring 	= 'Import Post';


	function cutecast_006()
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
			$displayobject->update_html($displayobject->make_hidden_code('posts','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Posts to import per cycle (must be greater than 1)','threadsperpage',10));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var('finishedcurrectforum','true');
			$sessionobject->add_session_var('currectforum','0');
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
		if ($sessionobject->get_session_var('posts') == 'working')
		{
			// Set up working variables.
			$displayobject->update_basic('displaymodules','FALSE');
			$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
			$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');

			$class_num				= substr(get_class($this) , -3);

			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}

			// Setup some working vars
			$threads_start_at 	= $sessionobject->get_session_var('threadstartat');
			$threads_per_page 	= $sessionobject->get_session_var('threadsperpage');
			$threads_to_do		= $threads_start_at	+ $threads_per_page;


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

				$forumcount = $Db_target->query_first("SELECT importforumid FROM " . $sessionobject->get_session_var('targettableprefix') ."forum WHERE importforumid = $currentforum");

				if ($forumcount)
				{
					$sessionobject->set_session_var('currectforum',$currentforum);
					$sessionobject->set_session_var('finishedcurrectforum','false');
					$sessionobject->set_session_var('threadstartat','0');
					$sessionobject->set_session_var('threadsfilepath',"");
				}
				else
				{
					$higestforum = $Db_target->query_first("SELECT importforumid FROM " . $sessionobject->get_session_var('targettableprefix') ."forum ORDER BY importforumid DESC LIMIT 1;");

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
				$forum = $sessionobject->get_session_var('currectforum');				$sessionobject->add_error('fatal',
										 $this->_modulestring,
										 get_class($this) . "::resume failed trying to modify table forum to add importforumid",
										 'Check database permissions and forum table');
				$displayobject->display_now("<h3>$forumtitle</h3>");

				if ($sessionobject->get_session_var('threadstartat')=='')
				{
					$sessionobject->add_session_var('threadstartat','0');
				}

				if ($sessionobject->get_session_var('threadsfilepath')=="")
				{
					// get path to threads file if not already found
					$displayobject->display_now("<p>Locating <i>forum.db</i> file ....\n");

					if (file_exists($sessionobject->get_session_var('datapath') . "/forum$forum/forum.db"))
					{
						$sessionobject->add_session_var('threadsfilepath', $sessionobject->get_session_var('datapath') . "/forum$forum");
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::resume threads file path found in file but couldn't find the actual path.",
												 'Has the directory been deleted and the file not updated ?');
						$displayobject->display_now("<b>not found</b>. No threads to import.</p>\n");
					}

					if ($sessionobject->get_session_var('threadsfilepath')=="")
					{
						$sessionobject->add_error('fatal',
									 $this->_modulestring,
									 get_class($this) . " Cannot find threads file path",
									 'That the threads file is in the correct path');

						$displayobject->display_now("<b>not found</b>. No threads to import.</p>\n");
					}
					else
					{
						$displayobject->display_now("... <b>Found</b><br /><br />");
					}
				}

				$foruminfo = file($sessionobject->get_session_var('threadsfilepath') . "/forum.db");
				$threadinfo = array();
				$stickyinfo = array();
				foreach ($foruminfo AS $dataobj)
				{
					$matches = array();
					if (preg_match("#^threads=(.*)#i", $dataobj, $matches))
					{
						if (trim($matches[1]) != '')
						{
							$threadinfo = explode(',', $matches[1]);
						}
					}
					else if (preg_match("#^sticky=(.*)#i", $dataobj, $matches))
					{
						if (trim($matches[1]) != '')
						{
							$stickyinfo = explode(',', $matches[1]);
						}
					}
				}

				$threadlist = $threadinfo;
				foreach ($stickyinfo AS $value)
				{
					$threadlist[] = $value;
				}
				$numlines = sizeof($threadlist);

				$vbuserid 		= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix, false);
				$vbforumid 		= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
				$vb_thread_ids 	= $this->get_cutecast_threads_ids($Db_target, $target_database_type, $target_table_prefix, $forum);

				$counter=0;
				$displayobject->display_now("\n<h4>Importing forum$forum/forum.db</h4>");

				$post_object = new ImpExData($Db_target, $sessionobject, 'post');

				for ($i = intval($sessionobject->get_session_var('threadstartat')); $i < (intval($sessionobject->get_session_var('threadstartat'))+intval($sessionobject->get_session_var('threadsperpage'))); $i++)
				{
					$finished = false;
					$counter++;
					if (is_file($sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db"))
					{
						$threadfile = file($sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db");
						while (list($line,$contents)=each($threadfile))
						{
							if ($line==0)
							{
								// Its a thread
								// Though the posts need the thread id
								// First post id
							}
							else
							{
								$try = (phpversion() < '5' ? $post_object : clone($post_object));
								$vBthreadid = '0';
								$userid = '0';

								$postbits = explode("\t", trim($contents));

								if (array_key_exists($this->get_import_userid($postbits[1]), $vbuserid))
								{
									$userid = $vbuserid[$this->get_import_userid($postbits[1])];
								}
								else
								{
									$errortext = $postbits[1] . " user not found";
									// QUESTION: What is the best way of dealing with this
									// TODO: Complete add_error correctly
									//$sessionobject->add_error('008',$errortext,'moo','moo');
									// DEBUG: have to put some more checking in here to make sure that the user file dosn't actually exsist
									echo'<br />' . "$postbits[1] ". $this->get_import_userid($postbits[1]) .' <span class="ifail">User id being set to 0 because origional user not imported</span>';
									$userid = '0';
								}

								// Is it in the array, if not bash the database, though its redundant as
								// that where the array came from.
								// TODO: Need a fail safe here.
								$try->set_value('mandatory', 'threadid',			$vb_thread_ids[$forum][intval($threadlist[$i])]);

								$try->set_value('mandatory', 'userid', 				$userid);
								$try->set_value('mandatory', 'importthreadid', 		intval($threadlist[$i]));
								$try->set_value('nonmandatory', 'username', 		$postbits[1]);

								$try->set_value('nonmandatory', 'dateline', 		$postbits[3]);
								$try->set_value('nonmandatory', 'pagetext', 		htmlspecialchars_decode($this->cutecast_bbcode_to_vb_bbcode($postbits[7])));
								$try->set_value('nonmandatory', 'ipaddress', 		$postbits[2]);
								$try->set_value('nonmandatory', 'visible', 			'1');
								$try->set_value('nonmandatory', 'allowsmilie', 		($postbits[5] == 'Y' ? '1' : '0'));
								$try->set_value('nonmandatory', 'showsignature', 	($postbits[4] == 'Y' ? '1' : '0'));


								if($try->is_valid())
								{
									if($try->import_post($Db_target, $target_database_type, $target_table_prefix))
									{



										$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: Post from -> " . $try->get_value('nonmandatory','username'));
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
									$displayobject->display_now("<br />Invalid post object, skipping.'" . $try->_failedon . "'");
								}
								unset($try);
							}
						}
					}
					else
					{
						$sessionobject->add_error('warning',
												  $this->_modulestring,
												  get_class($this) . ":: failed, no posts/thread file",
												  'Check consistance of threads file against posts in ubb.');
						$displayobject->display_now("<br /><b>No file :</b> " . $sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db");
					}
				}// counter
			}//finishedcurrectforum=='false'

			if (intval($sessionobject->get_session_var('threadstartat')+$counter) > $numlines)
			{
				$displayobject->display_now("<h4>Done this forum</h4>");
				$sessionobject->set_session_var('finishedcurrectforum','true');
			}

			if ($finished)
			{
				$displayobject->display_now('Updateing parent ids to allow for a threaded view....');

				if ($this->update_post_parent_ids($Db_target, $sessionobject->get_session_var('targetdatabasetype'), $sessionobject->get_session_var('targettableprefix')))
				{
					$displayobject->display_now('Done !');
				}
				else
				{
					$displayobject->display_now('Error updating parent ids');
				}


				$sessionobject->end_timing(substr(get_class($this), -3));
				$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
				$sessionobject->remove_session_var($class_num . '_start');

				$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																			$sessionobject->return_stats($class_num,'_time_taken'),
																			$sessionobject->return_stats($class_num,'_objects_done'),
																			$sessionobject->return_stats($class_num,'_objects_failed')
																			));

				$displayobject->display_now('User import done.');
				$displayobject->update_basic('displaymodules','FALSE');


				$sessionobject->set_session_var('posts','done');
				$sessionobject->set_session_var('threadstartat','0');
				$sessionobject->set_session_var('autosubmit','0');
				$sessionobject->set_session_var('006','FINISHED');
				$sessionobject->set_session_var('module','000');
				$displayobject->update_html($displayobject->print_redirect('index.php','2'));
			}
			else
			{
				$sessionobject->add_session_var('threadstartat',($sessionobject->get_session_var('threadstartat') + $counter));
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
	}
}//End Class
# Autogenerated on : June 10, 2004, 12:46 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
