<?php
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
* Core module that needs to be exeteded by the diffrent import systems.
*
* Is the base module class that all the import systems must extend to be
* controlled by ImpEx core, it maintains the dependacy controll amongst the
* modules and defines the functions that the core will call and that which
* be overridden.
*
* @package 		ImpEx
* @date 		$Date: 2011-10-21 18:59:56 -0400 (Fri, 21 Oct 2011) $
*
*/

if (!class_exists('ImpExDatabase')) { die('Direct class access violation'); }

class ImpExModule extends ImpExDatabase
{
	// TODO: Update ALL systems & remove as now defaut in dB and are different to below
	#var $_default_user_permissions 		= 2135;
	var $_default_user_permissions 		= 11537495;
										  
	#var $_default_user_options 		= 11537495;
	
	var $_default_mod_permissions 		= 1279;

	var $_default_blog_mod_permissions	= 0;

	var $_default_forum_permissions 	= 89799;

	var $_default_cat_permissions 		= 89795;

	var $_smilies = array(
		'smiles.gif'		=>	':)',
		'wink.gif'			=>	';)',
		'rolleyes.gif' 		=>	':rolleyes:',
		'icon_rolleyes.gif' =>	':rolleyes:',
		'wink.gif'			=>	';)',
		'biggrin.gif'		=>	':D',
		'razz.gif'			=>	':razz:',
		'mad.gif'			=>	':mad:',
		'confused.gif' 		=>	':confused:',
		'cool.gif'			=>	':cool:',
		'eek.gif'			=>	':eek:',
		'frown.gif'			=>	':(',
		'icon_wink.gif'		=>	';)',
		'icon_biggrin.gif'	=>	':D',
		'icon_razz.gif'		=>	':razz:',
		'icon_mad.gif'		=>	':mad:',
		'icon_confused.gif' =>	':confused:',
		'icon_smile.gif'	=>	':)',
		'icon_cool.gif'		=>	':cool:',
		'icon_eek.gif'		=>	':eek:',
		'icon_frown.gif'	=>	':('
	);


	var $_import_ids = array (
		'0' 	=> array('moderator'		=>  'importmoderatorid'),
		'1'		=> array('usergroup'		=>  'importusergroupid'),
		'2' 	=> array('ranks'			=>  'importrankid'),
		'3' 	=> array('poll'				=>  'importpollid'),
		'4' 	=> array('forum'			=>  'importforumid'),
		'5' 	=> array('forum'			=>  'importcategoryid'),
		'6' 	=> array('user'				=>  'importuserid'),
		'7' 	=> array('style'			=>  'importstyleid'),
		'8' 	=> array('thread'			=>  'importthreadid'),
		'9'		=> array('post'				=>  'importthreadid'),
		'10'	=> array('thread'			=>  'importforumid'),
		'11' 	=> array('smilie'			=>  'importsmilieid'),
		'12' 	=> array('pmtext'			=>  'importpmid'),
		'13' 	=> array('avatar'			=>  'importavatarid'),
		'14' 	=> array('customavatar'		=>  'importcustomavatarid'),
		'15' 	=> array('customprofilepic'	=>  'importcustomprofilepicid'),
		'16' 	=> array('post'				=>  'importpostid'),
		'17' 	=> array('attachment'		=>  'importattachmentid'),
		'18'	=> array('pm'				=>  'importpmid'),
		'19'	=> array('usernote'			=>  'importusernoteid'),
		'20'	=> array('phrase'			=>  'importphraseid'),
		'21'	=> array('subscription'		=>	'importsubscriptionid'),
		'22'	=> array('subscriptionlog'	=>	'importsubscriptionlogid')
	);

	var $_import_ids_400 = array(
		array('filedata'	=>	'importfiledataid')
	);
	
	var $_avatar_size = array(
		'-1' 			=> 'choose',
		'5000' 			=> '  5 Kb',
		'10000' 		=> ' 10 Kb',
		'15000' 		=> ' 15 Kb',
		'20000' 		=> ' 20 Kb',
		'25000' 		=> ' 25 Kb',
		'30000' 		=> ' 30 Kb',
		'35000' 		=> ' 35 Kb',
		'40000' 		=> ' 40 Kb',
		'50000' 		=> ' 50 Kb',
		'60000' 		=> ' 60 Kb',
		'70000' 		=> ' 70 Kb',
		'80000' 		=> ' 80 Kb',
		'90000' 		=> ' 90 Kb',
		'100000' 		=> '100 Kb',
		'125000' 		=> '125 Kb',
		'150000' 		=> '150 Kb',
		'175000' 		=> '175 Kb',
		'200000' 		=> '200 Kb'
	);

	/**
	* Class version
	*
	* This will allow the checking for interoprability of class version in diffrent
	* versions of ImpEx
	*
	* @var    string
	*/
	var $_version = '0.0.1';

	/**
	* State variable
	*
	* Allows the object to know if it has been restarted or not.
	* versions of ImpEx
	*
	* @var    boolean
	*/
	var $_restart = FALSE;

	/**
	* Error stack
	*
	* Array used by add_error to hold error information internal to the object
	* to be delt with , this is for internal usage where as the error in ImpExSession
	* is for display.
	*
	* @var    boolean
	*/
	var $_error = array();

	/**
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExModule()
	{
	}

	/**
	* Instantiates a class of the child module being called by index.php
	*
	* @param	object	sessionobject	The current sessionobject.
	* @param	object	displayobject	The display object that needs updating for the output.
	* @param	object	databaseobject	The target database (the one that the imported data is going to be put into).
	* @param	object	databaseobject	The source database (the one that the origional data has come from, i.e. the old board)
	* @param	boolean	boolean			Indicating whether the object has already been started and is being resumed.
	*
	* @return	none
	*/
	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = FALSE)
	{
		$modulenumber = substr(get_class($this), 7);
		$currentmoduleworking = $sessionobject->get_session_var('system');

		$name = 'systems/' . $currentmoduleworking . '/' . $modulenumber .'.php';

		if (file_exists($name))
		{
			include $name;
		}
		else
		{
			$sessionobject->add_error(
				'fatal',
				'ImpExModule',
				"ImpExModule::init failed trying to find file $name",
				'Check the path and that the file is accessable by the web server'
			);
		}

		$classname = $currentmoduleworking . '_' . $modulenumber;

		$ModuleCall = new $classname($Db_target, $sessionobject);

		if ($resume)
		{
			$ModuleCall->resume($sessionobject, $displayobject, $Db_target, $Db_source);
		}
		else
		{
			$ModuleCall->init($sessionobject, $displayobject, $Db_target, $Db_source);
		}
	}

	/**
	* Calls the various restart functions for the modules to be able to clean up and start again
	*
	* @param	object	sessionobject	The current sessionobject.
	* @param	object	displayobject	The display object that needs updating for the output.
	* @param	object	databaseobject	The target database (the one that the imported data is going to be put into).
	* @param	object	databaseobject	The source database (the one that the origional data has come from, i.e. the old board)
	* @param	string	mixed			The functions name of the clean up function to call
	* @param	array	mixed			Optional arguments to send to the $function
	*
	* @return	none
	*/
	function restart(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $function, $arguments = null)
	{
		$targetdatabasetype = $sessionobject->get_session_var('targetdatabasetype');
		$targettableprefix = $sessionobject->get_session_var('targettableprefix');

		if ($this->$function($Db_target, $targetdatabasetype, $targettableprefix, $arguments))
		{
			if(!$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0'))
			{
				return false;
			}

			if(!$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0'))
			{
				return false;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Checks if a module can run, by checking the state of its dependent
	*
	* @param	object	sessionobject	The current sessionobject.
	* @param	string	$dependent		the three digit module number i.e. '004'
	* @return	boolean
	*/
	function check_order(&$sessionobject, $dependent)
	{
		if ($sessionobject->get_session_var($dependent) != 'FINISHED')
		{
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	* Sets the module number of the instantiated class to working in the sessionobject
	*
	* @param	object	sessionobject	The current sessionobject.
	*/
	function using(&$sessionobject)
	{
		$sessionobject->set_session_var(substr(get_class($this), (intval(strlen(get_class($this))) - 3)), 'WORKING');
	}

	/**
	* Calls the init of the current class and passes TRUE init boolean call
	*
	* @param	object	sessionobject	The current sessionobject.
	* @param	object	displayobject	The display object that needs updating for the output.
	* @param	object	databaseobject	The target database (the one that the imported data is going to be put into).
	* @param	object	databaseobject	The source database (the one that the origional data has come from, i.e. the old board)
	*
	* @return	none
	*/
	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		$this->init($sessionobject, $displayobject, $Db_target, $Db_source, TRUE);
	}

	/**
	* Accessor: Sets the private memeber variable _restart to true
	*
	*/
	function restarted()
	{
		$this->_restart = true;
	}

	/**
	* Accessor: adds an error string to the error array
	*
	* @param	string	mixed	An error string for the internal error stack
	*/
	function add_module_error($text)
	{
		$this->_error = $this->_error + array(count($this->_error), $text);
	}

	/**
	* Accessor: adds an error string to the error array
	*
	* @param	string	mixed	The directory to find all the modules of a system in.
	*
	* @return	array	string	An array of all the modules names of a system
	*/
	function get_class_list($dir)
	{
		$moduleclassarray = array();
		$count = 0;
		$line = array();
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($filename = readdir($dh)) !== false)
				{
					if (($filename != '.') && ($filename != '..') && ($filename != 'CVS'))
					{
						//open the file
						$filetext = file($dir .'/'. $filename);
						//find the line with class
						foreach ($filetext as $value)
						{
							if (strpos($value, 'extends'))
							{
								$line=explode(' ', $value);
								break;
							}
						}
						$tempArray = array($line[1] => $filename);
						$moduleclassarray = array_merge($moduleclassarray, $tempArray);
					}
				}
				closedir($dh);
			}
		}
		return $moduleclassarray;
	}
}
/*======================================================================*/
?>