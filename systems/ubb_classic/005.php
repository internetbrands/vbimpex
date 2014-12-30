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
* Ubb Import Forums
*
* The ubb users are stored in files with each lines corresponding to a
* diffrent values.
*
* @package 		ImpEx.ubb_classic
* @version		$Revision: 2321 $
* @author		Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout	$Name$
* @date 		$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright 	http://www.vbulletin.com/license.html
*
*/
class ubb_classic_005 extends ubb_classic_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import Forums';

	function ubb_classic_005()
	{
	}

	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);
		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_forums'))
				{
					$this->_restart = false;
					$displayobject->display_now("<h4>Imported Forums and Categories have been cleared</h4>");
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . "::restart failed , clear_imported_forums",
											 'Check database permissions and user table');
				}
			}

			$displayobject->update_basic('title','Import forums');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_table_header('Step 6: Import Forums'));
			$displayobject->update_html($displayobject->make_hidden_code('forums','working'));
			$displayobject->update_html($displayobject->make_description("<p>Any style associations that you had set for the UBB.Classic board will be preserved for the imported forums.</p>"));
			$displayobject->update_html($displayobject->make_yesno_code("Would you like the styles assigned to the forums to override users' style preferences?","styleoverride",1));
			$displayobject->update_html($displayobject->make_yesno_code("If the importer detects categories with no title, would you like to import those categories anyway?","doblankcats",0));
			$displayobject->update_html($displayobject->do_form_footer("Import Forums"));
		}
		else
		{
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


		if ($sessionobject->get_session_var('forums') == 'working')
		{
			if(!$sessionobject->get_session_var($class_num . '_start'))
			{
				$sessionobject->timing($class_num,'start' ,$sessionobject->get_session_var('autosubmit'));
			}


			if(is_file($sessionobject->get_session_var('ubbpath') . '/categories.file'))
			{
				$categoryfile = file($sessionobject->get_session_var('ubbpath') . "/categories.file");
			}

			if(is_file($sessionobject->get_session_var('ubbpath') . '/vars_cats.file'))
			{
				$categoryfile = file($sessionobject->get_session_var('ubbpath') . "/vars_cats.file");
			}

			if(is_file($sessionobject->get_session_var('ubbcgipath') . '/vars_cats.cgi'))
			{
				$categoryfile = file($sessionobject->get_session_var('ubbcgipath') . "/vars_cats.cgi");
			}

			if(is_file($sessionobject->get_session_var('ubbcgipath') . '/vars_cats.file'))
			{
				$categoryfile = file($sessionobject->get_session_var('ubbcgipath') . "/vars_cats.file");
			}

			if(is_file($sessionobject->get_session_var('ubbcgipath') . '/vars_cats.cgi'))
			{
				$categoryfile = file($sessionobject->get_session_var('ubbcgipath') . "/vars_cats.cgi");
			}


			$category_object = new ImpExData($Db_target, $sessionobject, 'forum');
			$displayobject->display_now("<hr /><p><b>Categories.</b></p><hr />");

			while (list($line,$contents)=each($categoryfile))
			{
				$try = (phpversion() < '5' ? $category_object : clone($category_object));
				unset($description);
				$catbits = explode("|^|", trim($contents));
				// IDEA: Shouldn't we search for this before we split ?
				// There might be a better way of finding a delimiter

				if ($sessionobject->get_session_var('doblankcats')==0)
				{
					if ($catbits[1]!="")
					{
						$try->set_value('mandatory', 	'title', 			$catbits[1]);
						$try->set_value('mandatory', 	'displayorder',		$catbits[0]+100);
						$try->set_value('mandatory', 	'parentid',			'-1');
						$try->set_value('mandatory', 	'importforumid',	'0');
						$try->set_value('mandatory', 	'importcategoryid',	$catbits[2]);
						$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
						$description = $catbits[1] . " Description";
						$try->set_value('nonmandatory', 'description',		$description);

					}
					else
					{
						// NOTE: This is ignoreing the categorys with out titles
						// TODO: This isn't used out side of the class so it need to be checked
						// when the import_category is called to flag it as ignored but return true

						$this->_ignore=TRUE;
					}
				}
				else
				{
					if ($catbits[1]=="")
					{
						$try->set_value('mandatory', 'title', '(inactive)');
					}
					else
					{
						$try->set_value('mandatory', 'title', $catbits[1]);
					}

					$description = $catbits[1] . " Description";
					$try->set_value('nonmandatory', 	'description',		$description);
					$try->set_value('mandatory', 		'displayorder',		'0');
					$try->set_value('mandatory', 		'parentid',			'-1');
					$try->set_value('mandatory', 		'importforumid',	'0');
					$try->set_value('mandatory', 'options',				$this->_default_cat_permissions);
					$try->set_value('mandatory', 		'importcategoryid',	$catbits[2]);
				}

				if($try->is_valid())
				{
					if($try->import_category($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->display_now("<br /><font color=\"#00FF00\"><b>" . $try->how_complete() . "%</b></font> :: " . $try->get_value('mandatory','title'));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						$imported = true;
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid category object, skipping." . $try->_failedon);

				}
				unset($try);
			}

			$displayobject->display_now("<hr /><p><b>Forums.</b></p><hr />");


			// Get all the display options we can

			$options_file_data = implode('', file($sessionobject->get_session_var('ubbcgipath') . "/vars_display.cgi"));



			$options_file_data = preg_replace("/q?([^a-z0-9]?)([a-z0-9_]+)\\1\s+=>\s+q?([^a-z0-9])([^\n]*)\\3(,\s+?)/siUe", "\$this->convert_ubb_array('\\2', '\\4')", $options_file_data);
			$options_file_data = preg_replace("/q\!([^a-z0-9]\!)([a-z0-9_]+)\\1\s+=>\s+q\!([^a-z0-9])([^\n]*)\\3(,\s+\!)/siUe", "\$this->convert_ubb_array('\\2', '\\4')", $options_file_data);
			$options_file_data = preg_replace("/%([a-z0-9_]+) = \(/siU", '$\1 = array(', $options_file_data);

			//Strange cases
			$options_file_data = str_replace("q!close! => undef", "'close' => 'undef'", $options_file_data);
			$options_file_data = str_replace("q~close~ => q~~","'close' => ''", $options_file_data);
			$options_file_data = str_replace("q!1! => undef", "'1' => undef", $options_file_data);
			$options_file_data = str_replace("', );", "');", $options_file_data);
			$options_file_data = str_replace("1;", "", $options_file_data);
			$options_file_data = str_replace("\\\'", "\'", $options_file_data);

			$options_file_data = str_replace("q!DirectoryFields! =>", "'DirectoryFields' => array", $options_file_data);
			$options_file_data = str_replace("q!", "'", $options_file_data);
			$options_file_data = str_replace("!", "'", $options_file_data);
			$options_file_data = str_replace("]", ")", $options_file_data);
			$options_file_data = str_replace("[", "(", $options_file_data);
			$options_file_data = str_replace("'", "\"", $options_file_data);
			$options_file_data = str_replace('".', '\".', $options_file_data);


			$options_file_data = trim($options_file_data);

			@eval($options_file_data);

			// Right we have now got :

			/*
			%vars_display = (
					q!EnableModeratorReport! => q!yes!,
					q!DirectoryCIList! => q!momentpostersrecent!,
					q!ShowPrivacyLink! => q!ON!,
					q!AllowSignatureImage! => q!no!,
					q!NewestMemberWelcome! => q!no!,
					q!ForumDisplayMax! => q!40!,
					q!PaginationType! => q!new!,
					q!AvatarPopupHeight! => q!560!,
					q!YourCopyrightNotice! => q!!,
					q!AllowSignature! => q!NO!,
					q!ShowHomepageLink! => q!ON!,
					q!showcopytype! => q!text!,
					q!PrintTopic! => q!yes!,
					q!PrivacyURL! => q!http://www.miata.net/privacy_policy.html!,
					q!AvatarPopupRows! => q!6!,
					q!EmailBlock! => q!OFF!,
					q!DirectorySortEnable! => q!YES!,
					q!InlineFrame! => q!FALSE!,
					q!AvatarHeight! => q!48!,
					q!forum_intro! => q!!,
					q!ContactURL! => q!http://www.miata.net/feedback.html!,
					q!AvatarFileExts! => q!\.(gif|jpg|png)!,
					q!HotCount! => q!30!,
					q!CIForceMultiSelect! => q!0!,
					q!MembersOnlyAccess! => q!NO!,
					q!PopupHeight! => q!250!,
					q!user_ratings! => q!off!,
					q!AvatarPopupCols! => q!5!,
					q!LastPostColOpt! => q!ON!,
					q!BBEmail! => q!forum@miataforum.com!,
					q!DaysPruneDefault! => q!10!,
					q!ForumDescriptions! => q!yes!,
					q!masterCharset! => q!ISO-8859-1!,
					q!ForumTotalOption! => q!Posts!,
					q!EnableInstantCodeBlock! => q!yes!,
					q!DirectoryFields! =>
					  [
						q!OCCUPATION!,
						q!INTERESTS!,
						q!LOCATION!,
					  ],
					q!homepage_icon_link! => q!yes!,
					q!AvatarForceSize! => q!no!,
					q!HomePageURL! => q!http://www.miata.net/!,
					q!MyHomePage! => q!Miata.net!,
					q!CategoryView! => q!yes!,
					q!EnableDirectory! => q!regonly!,
					q!SmartPageJump! => q!ON!,
					q!RequireLoginPosts! => q!NO!,
					q!PopupWidth! => q!500!,
					q!HotIcons! => q!ON!,
					q!HTMLDisplayMax! => q!40!,
					q!DirectorySearchPref! => q!SEARCHONLY!,
					q!author_location! => q!yes!,
					q!author_reg_date! => q!yes!,
					q!ShowMods! => q!no!,
					q!AvatarRemoteFileExts! => q!\.(gif|jpg|png)!,
					q!ShowContactUsLink! => q!ON!,
					q!author_post_total! => q!yes!,
					q!AvatarIntroText! => q!!,
					q!BlueArrow! => q!ON!,
					q!ShowLastPostCol! => q!YES!,
					q!ReverseThreads! => q!FALSE!,
					q!AvatarWidth! => q!48!,
					q!DisplayMemberTotal! => q!true!,
					q!DirectorySearchResLim! => q!60!,
					q!ContactLinkType! => q!URL!,
					q!CategoriesOnly! => q!false!,
					q!DirectoryDisplayLimit! => q!20!,
					q!PreviewPost! => q!yes!,
					q!AllowIcons! => q!FALSE!,
					q!AvatarPopupWidth! => q!530!,
					q!UseAvatars! => q!yes!,
			*/



			$vbcategoryid = $this->get_category_ids($Db_target, $target_database_type, $target_table_prefix);

			/*
			Styles not imported, so not needed atm
			$vbstyleid = $this->get_style_ids($Db_target, $target_database_type, $target_table_prefix);

			$stylesfile = file($sessionobject->get_session_var('ubbcgipath') . "/vars_template_match.cgi");

			while (list($line,$contents)=each($stylesfile))
			{
				if (eregi("forum_([0-9]*) => \"([0-9]*)\"", $contents, $regs))
				{
					$ubbforumid = $regs[1];
					$ubbstyleid = $regs[2];
					$forumstyle[$ubbforumid] = $vbstyleid[$ubbstyleid];
				}
			}
			*/

			$forumsfile = file($sessionobject->get_session_var('ubbcgipath') . "/vars_forums.cgi");
			$forum_object = new ImpExData($Db_target, $sessionobject, 'forum');

			while (list($line,$contents)=each($forumsfile))
			{
				$try 		= $forum_object;
				$forumbits 	= explode("|^|", trim($contents));
				$options 	= 0;
				$private 	= false;

				if ($this->iif($forumbits[6]=="private",1,0) 			OR
					$this->iif($forumbits[6]=="privaterestrict",1,0) 	OR
					$this->iif($forumbits[6]=="privaterestrict&privatenone",1,0))
				{
					$private = true;
				}


				// Options
				if ($this->option2bin(trim($forumbits[3])))							{ $options += 1; } //'active'			=> 1,
				//'allowposting'      => 2,
				//'cancontainthreads' => 4,
				//'moderatenewpost'   => 8,
				//'moderatenewthread' => 16,
				//'moderateattach'    => 32,
				if ($this->option2bin(trim($forumbits[5])))							{ $options += 64;	} //'allowbbcode'       => 64,
				if ($this->option2bin(trim($forumbits[10])))						{ $options += 128;	} //'allowimages'       => 128,
				if ($this->option2bin(trim($forumbits[4])))	 						{ $options += 256;	} //'allowhtml'         => 256,
				//'allowsmilies'      => 512,
				if ($this->option2bin($vars_diplay['AllowIcons']))					{ $options += 1024;	} //'allowicons'        => 1024,

				if ($forum['password'])
				{
					$try->set_value('nonmandatory', 'password', trim($forumbits[12]));
					if ($this->option2bin(trim($forumbits[5]))) 					{ $options += 8192;	} //'canhavepassword'   => 8192,
				}

				//'allowratings'      => 2048,
				//'countposts'        => 4096,
				//'indexposts'        => 16384,
				//'styleoverride'     => 32768,
				//'showonforumjump'   => 65536,
				//'warnall'           => 131072

				// From vB2 importer
				//	$forum[styleoverride] = intval($styleoverride);


				$try->set_value('mandatory', 		'title', 			trim($forumbits[1]));
				$try->set_value('mandatory', 		'displayorder', 	intval(trim($forumbits[14]))+100);
				$try->set_value('mandatory', 		'parentid', 		$this->iif($forumbits[0],$vbcategoryid[$forumbits[0]],-1));
				$try->set_value('mandatory', 		'importcategoryid', '0');
				$try->set_value('mandatory', 		'importforumid', 	trim($forumbits[8]));
				$try->set_value('mandatory', 'options',				$this->_default_forum_permissions);

				$try->set_value('nonmandatory', 	'daysprune', 		$vars_diplay['DaysPruneDefault']);
				$try->set_value('nonmandatory', 	'importforumid', 	trim($forumbits[8]));
				$try->set_value('nonmandatory', 	'styleid', 			$forumstyle[$forum[trim($forumbits[8])]]);
				$try->set_value('nonmandatory',		'description', 		trim($forumbits[2]));
				$try->set_value('nonmandatory',		'options', 			$options);


				if ($try->is_valid())
				{
					$forum_result = $try->import_forum($Db_target, $target_database_type, $target_table_prefix);
					$forum_id = $Db_target->insert_id();

					if ($forum_result)
					{
						if ($private)
						{
							if ($this->set_forum_private($Db_target, $target_database_type, $target_table_prefix, $forum_id))
							{
								$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
								$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							}
							else
							{
								$displayobject->display_now("<br /><span class=\"ifail\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title') . " imported, PRIVATE status NOT set, please update.");
								$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
							}
						}
						else
						{
							$displayobject->display_now("<br /><span class=\"isucc\"><b>" . $try->how_complete() . "%</b></span> :: " . $try->get_value('mandatory','title'));
							$sessionobject->add_session_var($class_num  . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
						}
					}
					else
					{
						$sessionobject->add_error('warning',
												 $this->_modulestring,
												 get_class($this) . "::import_category failed for " . $cat['cat_title'] . " get_phpbb2_categories_details was ok.",
												 'Check database permissions and user table');
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num . '_objects_failed') + 1 );
						$displayobject->display_now("<br />Got category " . $cat['cat_title'] . " and <b>DID NOT</b> imported to the " . $target_database_type . " database");
					}
				}
				else
				{
					$displayobject->display_now("<br />Invalid forum object, skipping." . $try->_failedon);
				}
				unset($try);
			}


			$this->build_forum_child_lists($Db_target, $target_database_type, $target_table_prefix);


			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
																		$sessionobject->return_stats($class_num,'_time_taken'),
																		$sessionobject->return_stats($class_num,'_objects_done'),
																		$sessionobject->return_stats($class_num,'_objects_failed')
																		));

			$displayobject->update_basic('displaymodules','FALSE');

			$sessionobject->set_session_var('forums','done');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');

			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
		else
		{
			$displayobject->update_html("<p align=\"center\"><b>Skipping forums import.</b></p>");
			$sessionobject->set_session_var('forums','done');
			$sessionobject->set_session_var($class_num,'FINISHED');
			$sessionobject->set_session_var('module','000');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}
	}
	
	function convert_ubb_array($varname, $data)
	{
		return "'$varname' => '" . str_replace("'", "\\'", $data) . "',\n";
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile$ - $Revision: 2321 $
|| ####################################################################
\*======================================================================*/
?>

