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
* cutecast_005 Import Thread module
*
* @package			ImpEx.cutecast
* @version			$Revision: $
* @author			Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout		$Name$
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class cutecast_005 extends cutecast_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '004';
	var $_modulestring 	= 'Import Thread';


	function cutecast_005()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_threads'))
				{
					$displayobject->display_now('<h4>Imported threads have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_threads','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import Thread');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('threads','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Threads to import per cycle (must be greater than 1)','threadsperpage',50));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var('finishedcurrectforum','true');
			$sessionobject->add_session_var('currectforum','0');
			$sessionobject->add_session_var('threadstartat','0');
			$sessionobject->add_session_var('threaddone','0');
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

				$sql = "
				SELECT importforumid FROM " . $target_table_prefix ."forum
				WHERE importforumid=$currentforum";
				$forumcount = $Db_target->query_first($sql);

				if ($forumcount)
				{
					$sessionobject->set_session_var('currectforum',$currentforum);
					$sessionobject->set_session_var('finishedcurrectforum','false');
					$sessionobject->set_session_var('threadstartat','0');
					$sessionobject->set_session_var('threadsfilepath',"");

					$displayobject->display_now("<br />Moving onto forum " . $currentforum);
				}
				else
				{
					$sql = "
					SELECT importforumid FROM " . $target_table_prefix ."forum
					ORDER BY importforumid DESC limit 1";

					$higestforum = $Db_target->query_first($sql);

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
					$sessionobject->add_session_var('threadstartat','0');
				}

				if ($sessionobject->get_session_var('threadsfilepath')=="")
				{
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
													 get_class($this) . "::resume Cannot find the threads file path.",
													 'Does the forum have any threads at all ? Is the directory correct ?');
						$displayobject->display_now("there is no threads file path");
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

				$vbuserid 	= $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);
				$vbforumid 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);
				$vbcatid 	= $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

				$counter=0;

				for ($i = intval($sessionobject->get_session_var('threadstartat')); $i < (intval($sessionobject->get_session_var('threadstartat'))+intval($sessionobject->get_session_var('threadsperpage'))); $i++)
				{
					$finished = false;
					$counter++;
					if (is_file($sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db"))
					{
						$threadfile = file($sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db");

						$thread_object = new ImpExData($Db_target, $sessionobject, 'thread');

						$threadbits = explode("\t", trim($threadfile[0]));
						$timebits = explode("\t", trim($threadfile[1]));
						$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

						if ($vbforumid[$forum])
						{
							$try->set_value('mandatory', 'forumid' , $vbforumid[$forum]);
						}
						else
						{
							die("moo");
						}


						$try->set_value('mandatory', 'title', 			trim($threadbits[2]));
						$try->set_value('mandatory', 'importthreadid', 	$threadlist[$i]);
						$try->set_value('mandatory', 'importforumid', 	$forum);

						$try->set_value('nonmandatory', 'visible', 		'1');
						$try->set_value('nonmandatory', 'open', 		($threadbits[0] == 'open' ? '1' : '0'));
						$try->set_value('nonmandatory', 'postuserid', $vbuserid[$this->get_import_userid($threadbits[1])]);
						$try->set_value('nonmandatory', 'postusername', $threadbits[1]);
						$try->set_value('nonmandatory', 'views', $threadbits[3]);
						$try->set_value('nonmandatory', 'dateline', $timebits[3]);
						if (array_search($threadlist[$i], $stickyinfo))
						{
							$try->set_value('nonmandatory', 'sticky', 1);
						}

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
										 get_class($this) . "::resume Found a thread in the list but could not find the corisponding file : " . $sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db",
										 'Is the directory correct ?');
				$displayobject->display_now("<br /><b>No file :</b> " . $sessionobject->get_session_var('threadsfilepath') . "/{$threadlist[$i]}.db");
			}

			if (intval($sessionobject->get_session_var('threadstartat')+$counter) > $numlines)
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
				$sessionobject->set_session_var('005','FINISHED');
				$sessionobject->set_session_var('module','000');

				$displayobject->update_html($displayobject->print_redirect('index.php','1'));
			}
			else
			{
				$sessionobject->add_session_var('threadstartat',($sessionobject->get_session_var('threadstartat') + $counter));
				$displayobject->update_html($displayobject->print_redirect('index.php'));
			}
		}
	}// End resume
}//End Class
# Autogenerated on : June 10, 2004, 12:46 am
# By ImpEx-generator 1.0.
/*======================================================================*/
?>
