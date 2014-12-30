<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # Copyright 20002014 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

error_reporting(E_ALL & ~E_NOTICE);
// db class for mysql
// this class is used in all scripts
// do NOT fiddle unless you know what you are doing

define('DBARRAY_NUM', MYSQL_NUM);
define('DBARRAY_ASSOC', MYSQL_ASSOC);
define('DBARRAY_BOTH', MYSQL_BOTH);

if (!defined('IDIR')) { die; }

if (!defined('DB_EXPLAIN'))
{
	define('DB_EXPLAIN', false);
}

if (!defined('DB_QUERIES'))
{
	define('DB_QUERIES', false);
}

class DB_Sql_vb_impex
{
	var $database = '';
	var $type = '';	// mysql/mssql/sqlsrv (mssql PHP 5.3)
	var $odbcconnect = '';

	var $link_id = 0;

	var $errdesc = '';
	var $errno = 0;
	var $reporterror = 1;

	var $appname = 'vBulletin';
	var $appshortname = 'vBulletin (cp)';

	var $require_db_reselect = false; // deal with bentness in php < 4.2.0

	function connect($server, $user, $password, $usepconnect, $charset = "")
	{
		if ($this->type == 'sqlsrv')
		{
			$connectinfo = array(
				'UID'                  => $user,
				'PWD'                  => $password,
				'Database'             => $this->database,
				'ReturnDatesAsStrings' => true,
			);
			if (!($this->link_id = sqlsrv_connect($server, $connectinfo)))
			{
				echo 'Connection failed';
				return false;
			}
		}

		if ($this->type == 'mssql')
		{
			$this->link_id = mssql_connect($server, $user, $password);
			if (!$this->link_id)
			{
				$this->halt('Link-ID == false, connect failed');
				return false;
			}
			$this->select_db($this->database);
			return true;
		}


		if ($this->type == "odbc")
		{
			$this->odbcconnect = odbc_connect("Driver={SQL Server};Server=$server;Database={$this->database}",$user,$password);
			$this->link_id = $this->odbcconnect;
		}


		// connect to db server

		global $querytime;
		// do query

		if (DB_QUERIES)
		{
			echo "Connecting to database\n";

			global $pagestarttime;
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$beforetime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];

			echo "Time before: $beforetime\n";
			if (function_exists('memory_get_usage'))
			{
				echo "Memory Before: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}
		}

		if (0 == $this->link_id)
		{
			if ($usepconnect == true)
			{
				if(!function_exists('mysql_pconnect'))
				{
					die ('function mysql_pconnect() called though not supported in the PHP build');
				}

				if (phpversion() >= '4.2.0')
				{
					$this->link_id = @mysql_pconnect($server, $user, $password, true);
				}
				else
				{
					$this->link_id = @mysql_pconnect($server, $user, $password);
				}
			}
			else
			{
				if(!function_exists('mysql_connect'))
				{
					die ('function mysql_connect() called though not supported in the PHP build');
				}

				if (phpversion() >= '4.2.0')
				{
					$this->link_id = @mysql_connect($server, $user, $password, true);
				}
				else
				{
					$this->link_id = @mysql_connect($server, $user, $password);
				}
			}
			if (!$this->link_id)
			{
				$this->halt('Link-ID == false, connect failed');
				return false;
			}

			$this->select_db($this->database);

			if (DB_QUERIES)
			{
				$pageendtime = microtime();
				$starttime = explode(' ', $pagestarttime);
				$endtime = explode(' ', $pageendtime);

				$aftertime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];
				$querytime += $aftertime - $beforetime;

				echo "Time after: $aftertime\n";
				echo "Time taken: " . ($aftertime - $beforetime) . "\n";
				if (function_exists('memory_get_usage'))
				{
					echo "Memory After: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
				}

				echo "\n<hr />\n\n";
			}

			if (!empty($charset))
			{
                $this->query("SET NAMES {$charset}");
			}

			return true;
		}
	}

	function affected_rows()
	{
		$this->rows = mysql_affected_rows($this->link_id);
		return $this->rows;
	}

	function geterrdesc()
	{
		$this->error = mysql_error($this->link_id);
		return $this->error;
	}

	function geterrno()
	{
		if($this->type == 'mysql')
		{
			$this->errno = mysql_errno($this->link_id);
			return $this->errno;
		}
	}

	function select_db($database = '')
	{
		// select database
		if (!empty($database))
		{
			$this->database = $database;
		}

		if ($this->type == 'mssql')
		{
			if (!($this->msdb = mssql_select_db($this->database, $this->link_id)))
			{
				echo 'Cannot use database ' . $this->database . '<br />';
				return false;
			}
			return true;
		}


		if($this->type == 'mysql' OR $this->type == 'mysqli')
		{
			$connectcheck = @mysql_select_db($this->database, $this->link_id);
			if ($connectcheck)
			{
				return true;
			}
			else
			{
				$this->halt('cannot use database ' . $this->database);
				return false;
			}
		}
	}

	function query_unbuffered($query_string)
	{
		return $this->query($query_string, 'mysql_unbuffered_query');
	}

	function shutdown_query($query_string, $arraykey = 0)
	{
		global $shutdownqueries;

		if (NOSHUTDOWNFUNC)
		{
			return $this->query($query_string);
		}
		elseif ($arraykey)
		{
			$shutdownqueries["$arraykey"] = $query_string;
		}
		else
		{
			$shutdownqueries[] = $query_string;
		}
	}

	function query($query_string, $query_type = 'mysql_query')
	{
		global $query_count, $querytime;

		if ($this->type == 'sqlsrv')
		{
			if ($q = @sqlsrv_query($this->link_id, $query_string))
			{
				return $q;
			}
			else if ($q === false)
			{
				$this->halt('Invalid SQL: ' . $query_string);
				exit;
			}
		}

		if($this->type == 'mssql')
		{
			if ($q = @mssql_query($query_string))
			{
				return $q;
			}
			else
			{
				$this->halt('Invalid SQL: ' . $query_string);
				exit;
			}
		}

		if($this->type == 'odbc')
		{
			return @odbc_exec($this->odbcconnect, $query_string);
		}

		if (DB_QUERIES)
		{
			echo 'Query' . ($query_type == 'mysql_unbuffered_query' ? ' (UNBUFFERED)' : '') . ":\n<i>" . htmlspecialchars($query_string) . "</i>\n";

			global $pagestarttime;
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$beforetime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];

			echo "Time before: $beforetime\n";
			if (function_exists('memory_get_usage'))
			{
				echo "Memory Before: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}
		}

		// do query
		if ($this->require_db_reselect)
		{
			$this->select_db($this->database);
		}

		// Do the actual query ::
		 if (use_utf8_encode)
		 {
			 $query_id = $query_type(utf8_encode($query_string), $this->link_id);
		 }
		 else
		 {
			 $query_id = $query_type($query_string, $this->link_id);
		 }


		if (!$query_id)
		{
			$this->halt('Invalid SQL: ' . $query_string);
		}

		$query_count++;

		if (DB_QUERIES)
		{
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$aftertime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];
			$querytime += $aftertime - $beforetime;

			echo "Time after: $aftertime\n";
			echo "Time taken: " . ($aftertime - $beforetime) . "\n";
			if (function_exists('memory_get_usage'))
			{
				echo "Memory After: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}

			if (DB_EXPLAIN AND preg_match('#(^|\s)SELECT\s+#si', $query_string))
			{
				$explain_id = mysql_query("EXPLAIN " . $query_string, $this->link_id);
				echo "</pre>\n";
				echo '
				<table width="100%" border="1" cellpadding="2" cellspacing="1">
				<tr>
					<td><b>table</b></td>
					<td><b>type</b></td>
					<td><b>possible_keys</b></td>
					<td><b>key</b></td>
					<td><b>key_len</b></td>
					<td><b>ref</b></td>
					<td><b>rows</b></td>
					<td><b>Extra</b></td>
				</tr>
				';
				while($array = mysql_fetch_assoc($explain_id))
				{
					echo "
					<tr>
						<td>$array[table]&nbsp;</td>
						<td>$array[type]&nbsp;</td>
						<td>$array[possible_keys]&nbsp;</td>
						<td>$array[key]&nbsp;</td>
						<td>$array[key_len]&nbsp;</td>
						<td>$array[ref]&nbsp;</td>
						<td>$array[rows]&nbsp;</td>
						<td>$array[Extra]&nbsp;</td>
					</tr>
					";
				}
				echo "</table>\n<br /><hr />\n";
				echo "\n<pre>";
			}
			else
			{
				echo "\n<hr />\n\n";
			}
		}

		return $query_id;
	}

	function fetch_array($query_id, $type = DBARRAY_BOTH)
	{
		if ($this->type == 'sqlsrv')
		{
			if (do_mysql_fetch_assoc)
			{
				return @sqlsrv_fetch_array($query_id, SQLSRV_FETCH_ASSOC);
			}
			else
			{
				return @sqlsrv_fetch_array($query_id);
			}
		}

		if($this->type == 'mssql')
		{
			// retrieve row
			if(do_mysql_fetch_assoc)
			{
				return @mssql_fetch_assoc($query_id);
			}
			else
			{
				return @mssql_fetch_array($query_id);
			}
		}

		if($this->type == 'odbc')
		{
			return odbc_fetch_array($query_id);
		}

		if($this->type == 'mysql')
		{
			// retrieve row
			if(do_mysql_fetch_assoc)
			{
				return @mysql_fetch_assoc($query_id);
			}
			else
			{
				return @mysql_fetch_array($query_id);
			}
		}
	}

	function free_result($query_id)
	{
		// retrieve row
		return @mysql_free_result($query_id);
	}

	function query_first($query_string, $type = DBARRAY_BOTH)
	{
		// does a query and returns first row
		$query_id = $this->query($query_string);
		$returnarray = $this->fetch_array($query_id, $type);
		$this->free_result($query_id);
		$this->lastquery = $query_string;

		if($this->type == 'odbc')
		{
			$moo = array (0 => array_pop($returnarray));
	 		return $moo;
		}

		return $returnarray;
	}

	function data_seek($pos, $query_id)
	{
		// goes to row $pos
		return @mysql_data_seek($query_id, $pos);
	}

	function num_rows($query_id)
	{
		// returns number of rows in query
		return mysql_num_rows($query_id);
	}

	function num_fields($query_id)
	{
		// returns number of fields in query
		return mysql_num_fields($query_id);
	}

	function field_name($query_id, $columnnum)
	{
		// returns the name of a field in a query
		return mysql_field_name($query_id, $columnnum);
	}

	function insert_id()
	{
		// returns last auto_increment field number assigned
		return mysql_insert_id($this->link_id);
	}

	function close()
	{
		// closes connection to the database

		return mysql_close($this->link_id);
	}

	function print_query($htmlize = true)
	{
		// prints out the last query executed in <pre> tags
		$querystring = $htmlize ? htmlspecialchars($this->lastquery) : $this->lastquery;
		echo "<pre>$querystring</pre>";
	}

	function escape_string($string)
	{
		// escapes characters in string depending on Characterset
		return mysql_escape_string($string);
	}

	function halt($msg)
	{
		if ($this->link_id)
		{
			if ($this->type == 'mysql')
			{
				$this->errdesc = mysql_error($this->link_id);
				$this->errno = mysql_errno($this->link_id);
			}
			if ($this->type == 'sqlsrv')
			{
				if(($errors = @sqlsrv_errors(SQLSRV_ERR_ERRORS)) != null)
				{
					foreach($errors as $error)
					{
						$this->errdesc .= 'SQLSTATE: '. $error['SQLSTATE']. ' | ';
            $this->errdesc .= 'CODE: ' . $error['code'] . ' | ';
            $this->errdesc .= 'MESSAGE: ' . $error['message'] . "\r\n";
           }
				}
			}
			if ($this->type == 'mssql')
			{
				$this->errdesc = @mssql_get_last_message();
			}
		}
		// prints warning message when there is an error
		global $technicalemail, $bbuserinfo, $vboptions, $_SERVER;

		if ($this->reporterror == 1)
		{
			$delimiter = "\r\n";

			$message  = 'ImpEx Database error' . "$delimiter$delimiter";
			$message .= $this->type . ' error: ' . $msg . "$delimiter$delimiter";
			$message .= $this->type . ' error: ' . $this->errdesc . "$delimiter$delimiter";
			if ($this->errno)
			{
				$message .= $this->type . ' error number: ' . $this->errno . "$delimiter$delimiter";
			}
			$message .= 'Date: ' . date('l dS of F Y h:i:s A') . $delimiter;
			$message .= 'Database: ' . $this->database . $delimiter;
			if ($this->type == 'mysql')
			{
				$message .= 'MySQL error: ' .mysql_error() . $delimiter;
			}

			echo "<html><head><title>ImpEx Database Error</title>";
			echo "<style type=\"text/css\"><!--.error { font: 11px tahoma, verdana, arial, sans-serif; }--></style></head>\r\n";
			echo "<body></table></td></tr></table></form>\r\n";
			echo "<blockquote><p class=\"error\">&nbsp;</p><p class=\"error\"><b>There seems to have been a problem with the database.</b><br />\r\n";
			echo "<form><textarea class=\"error\" rows=\"15\" cols=\"100\" wrap=\"off\">" . htmlspecialchars($message) . "</textarea></form></blockquote>";
			echo "\r\n\r\n</body></html>";
			exit;
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
