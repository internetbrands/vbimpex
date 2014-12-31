<?php
/*
* @package 		ImpEx.tools
* @date 		$Date: 2006-04-01 18:09:40 -0500 (Sat, 01 Apr 2006) $
*
*/

// vbfields updater.
// Updates the feilds in vbfields table to ensure that ImpEx has the latest
// Dosn't input fields that don't exsist
//
//
// I / we / the company takes no responsiability for you running this script
// Jerry - jerry.hutchings@vbulletin.com

define('DIR', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once (DIR . '/db_mysql.php');

$targettableprefix 			= '';
$targetdatabasetype 		= 'mysql';
$targetserver 				= 'localhost';
$targetuser    				= 'vb3';
$targetpassword 			= 'vb3';
$targetdatabase 			= 'vb3';

$Db_target = new DB_Sql_vb_impex();

$Db_target->server 		= $targetserver;
$Db_target->user    	= $targetuser;
$Db_target->password 	= $targetpassword;
$Db_target->database 	= $targetdatabase;
$Db_target->connect();


$data_base_result = $Db_target->query("show tables");

 while ($table = $Db_target->fetch_array($data_base_result))
 {
	  if($table[0] != 'vbfields')
	  {
		   echo "<h4>'" . $table[0] . "'</h4>";
		   $table_result = $Db_target->query("DESCRIBE " . $table[0]);

		   while ($row = $Db_target->fetch_array($table_result))
		   {
				// Check that it is in vBfields to start with
				$not_found = $Db_target->query_first("SELECT fieldid FROM vbfields WHERE fieldname='" . $row['Field'] . "' AND tablename='" . $table[0] . "'");

				if($not_found[0] == null)
				{
					echo "<h2><font color='red'>I found field " . $row['Field'] . " in table '" . $table[0] . "' and not in vbfields !</font></h2>";
				}

				echo "<br /><i>updating</i> -|- <b>" . $row['Field'] . "</b> :: " .$row['Type'];
				$unsigned = 'NO';
				if(strstr($row['Type'],'unsigned'))
				{
					$unsigned = 'YES';
				}

				if(strstr($row['Type'],'char') OR strstr($row['Type'],'text') OR strstr($row['Type'],'enum'))
				{
					$unsigned = 'N/A';
				}

				$sql ="UPDATE vbfields
				SET
				isunsigned='" . $unsigned . "' ,
				defaultvalue='" . $row['Default'] . "' ,
				createsql='" . addslashes($row['Type']) . "',
				localupdate='Y'
				WHERE fieldname='" . $row['Field'] . "' AND tablename='" . $table[0] . "'
				";

				$Db_target->query($sql);
		   }
	  }
 }
?>

