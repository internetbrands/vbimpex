<?
define('IDIR', (($getcwd = getcwd()) ? $getcwd : '.'));

if(!is_file(IDIR . '/ImpExConfig.php'))
{
	echo 'Cannot find ImpExConfig.php, have you configured the file and renamed it ?';
	exit;
}
else
{
	require_once (IDIR . '/ImpExConfig.php');
	require_once (IDIR . $impexconfig['system']['language']);
}


// #############################################################################
// Requires
// #############################################################################

require_once (IDIR . '/db_mysql.php');

require_once (IDIR . '/ImpExFunction.php');
require_once (IDIR . '/ImpExDatabaseCore.php');
require_once (IDIR . '/ImpExDatabase_360.php');
require_once (IDIR . '/ImpExModule.php');

require_once (IDIR . '/ImpExSession.php');
require_once (IDIR . '/ImpExController.php');
require_once (IDIR . '/ImpExDisplay.php');
require_once (IDIR . '/ImpExDisplayWrapper.php');


$Db_target = new DB_Sql_vb_impex();
$Db_source = new DB_Sql_vb_impex();

$Db_target->appname 		= 'vBulletin:ImpEx Target';
$Db_target->appshortname 	= 'vBulletin:ImpEx Target';
$Db_target->database 		= $impexconfig['target']['database'];
$Db_target->type 			= $impexconfig['target']['databasetype'];

$Db_target->connect($impexconfig['target']['server'], $impexconfig['target']['user'], $impexconfig['target']['password'], $impexconfig['target']['persistent'], $impexconfig['target']['charset']);

$Db_target->select_db($impexconfig['target']['database']);


// #############################################################################
// Session start
// #############################################################################

$ImpExDisplay = new ImpExDisplay();
$ImpExDisplay->phrases =& $impex_phrases;


$ImpEx = new ImpExController();

$ImpExSession = new ImpExSession();

		$tire_1 = '';
		$tire_2 = '';
		$tire_3 = '';
#var_dump($folders);

foreach(scandir(IDIR . "/systems/") AS $id => $folder)
{
	if(
		$folder[0] != '.'
		AND $folder != 'index.html'
		AND $folder != 'webboard'
		AND $folder != 'ubbthreads'
		AND $folder != 'rat'
		AND $folder != 'ikon'
		AND $folder != 'ubb'
		AND $folder != 'phpBB'
		AND $folder != 'gossamer_threads'
			AND $folder != 'gossamer_threads.zip'
			AND $folder != 'probid.zip'

	)
	{
		require_once(IDIR . "/systems/" . $folder . "/000.php");
		$classname = "{$folder}_000";

		$obj = new $classname($ImpExSession);
		

		
		// Main list
		if(
			$module[0] != '.'
			AND $module != 'index.html'
			AND substr($module, 0 -3) != 'bck'
		)
		{
			require_once(IDIR . "/systems/" . $folder . "/000.php");
			
			$module = "{$folder}_000";
			$module_obj = new $module($ImpExDisplay);

			switch ($module_obj->_tier)
			{
				case 1:
					$tire_1 .= "[tr][td]{$module_obj->_modulestring}[/td][td]$module_obj->_version[/td][/tr]\n";
					break;
				case 2:
					$tire_2 .= "[tr][td]{$module_obj->_modulestring}[/td][td]$module_obj->_version[/td][/tr]\n";
					break;
				case 3:
					$tire_3 .= "[tr][td]{$module_obj->_modulestring}[/td][td]$module_obj->_version[/td][/tr]\n";
					break;
				default:	
					echo "<br> Bah : " . $module_obj->_modulestring;
			}
	
		}
		
		foreach(scandir(IDIR . "/systems/{$folder}") AS $id => $module)
		{
			if(
				$module[0] != '.'
				AND $module != 'index.html'
				AND substr($module, 0 -3) != 'bck'
			)
			{
				require_once(IDIR . "/systems/" . $folder . "/$module");
				$module = substr("{$folder}_$module", 0, -4);


				$module_obj = new $module($ImpExDisplay);

				if ($module_obj->_modulestring != 'Check and update database' AND $module_obj->_modulestring != 'Associate Users')
				{
					if (substr($module_obj->_modulestring, 0, 6) == 'Import')
					{
						echo "<br>[*]" . $module_obj->_modulestring . "[/*]";
					}
					else
					{
						echo "<br>[b]" . $module_obj->_modulestring . "[/b]";

						if ($module_obj->_tier)
						{
							echo "<br>[b] Tier = " . $module_obj->_tier . "[/b]";
						}
						else
						{
							echo "<br>[b] Tier = 2[/b]";
						}
						echo "<br>Source version support in ImpEx = [b]" . $module_obj->_version . "[/b]";
						echo "<br>[list]";
					}

					if (!$module_obj->_modulestring)
					{
						#  meh
					}
				}

				unset($module_obj);
			}
		}
		echo "<br>[/list]<br><br>";
	}
}

echo "<hr>";

	echo "[b]Tier 1[/b]\n[table]{$tire_1}[/table]\n\n\n";
	echo "[b]Tier 2[/b]\n[table]{$tire_2}[/table]\n\n\n";
	echo "[b]Tier 3[/b]\n[table]{$tire_3}[/table]\n\n\n";
	
	

		
?>