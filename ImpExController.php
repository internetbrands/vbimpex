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
* The controller mediates between the display and the session and deals with POST variables
*
*
* @package 		ImpEx
*
*/
if (!defined('IDIR')) { die; }

class ImpExController
{
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
	* Constructor
	*
	* Empty
	*
	*/
	function ImpExController()
	{
	}


	/**
	* Places the $_POST values in the session array
	*
	* @param	object	sessionobject	The current session object
	* @param	array	mixed			The $_POST array
	*
	* @return	none
	*/
	function get_post_values(&$sessionobject, $postarray)
	{
		// TODO: Need some checking and error handling in here.
		// NOTE: hard coded the avaiable passable values that can be handeled by interfacing with the modules
		// NOTE : could handel it in a 000.php check
		foreach ($postarray as $key => $value)
		{
			$sessionobject->add_session_var($key, $value);
		}
	}


	/**
	* Modifyes the display depending on the state of the session object.
	*
	* @param	object	sessionobject	The current session object
	* @param	object	displayobject	The display object to be updated
	*
	* @return	none
	*/
	function updateDisplay(&$sessionobject, &$displayobject)
	{
		if ($sessionobject->_session_vars['system'] == 'NONE')
		{
			$displayobject->update_basic('status', 'Please choose a system to import from');
			$displayobject->update_basic('displaymodules', 'FALSE');
			$displayobject->update_basic('choosesystem', 'TRUE');
		}
		else
		{
			$displayobject->update_basic('system', $sessionobject->_session_vars['system']);
		}

		for ($i = 0; $i <= $sessionobject->get_number_of_modules(); $i++)
		{
			$position = str_pad($i, 3, '0', STR_PAD_LEFT);
			if ($sessionobject->_session_vars[$position] == 'WORKING')
			{
				$displayobject->update_basic('displaylinks', 'FALSE');
			}
		}
	}


	/**
	* Returns the current session or false if there isn't a current one
	*
	* @param	object	databaseobject	The database object connected to the dB where the session is stored
	* @param	string	mixed			Table prefix
	*
	* @return	object|boolean
	*/
	function return_session(&$Db_object, &$targettableprefix)
	{
		$getsession 	= null;
		$session_db		= null;
		$session_data	= false;

		if (forcesqlmode)
		{
			$Db_object->query("set sql_mode = ''");
		}

		$session_db = $Db_object->query("SELECT data FROM {$targettableprefix}datastore WHERE title = 'ImpExSession'");

		// TODO: switch on database type.
		if (mysql_num_rows($session_db))
		{
			$session_data = mysql_result($session_db, 0, 'data');
		}

		if ($session_data)
		{
			return unserialize($session_data);
		}
		else
		{
			return false;
		}
	}


	/**
	* Stores the current session
	*
	* @param	object	databaseobject	The database object connected to the dB where the session is stored
	* @param	string	mixed			Table prefix
	* @param	object	sessionobject	The session to store
	*
	* @return	none
	*/
	function store_session(&$Db_object, &$targettableprefix, &$ImpExSession)
	{
		// TODO: Need a return values and assurace that it was committed

		if (forcesqlmode)
		{
			$Db_object->query("set sql_mode = ''");
		}

		$Db_object->query("
			REPLACE INTO " . $targettableprefix . "datastore (title, data)
			VALUES ('ImpExSession', '" . addslashes(serialize($ImpExSession)) . "')
		");
	}
}
/*======================================================================*/
?>