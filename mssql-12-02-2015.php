<?php
/**
 * File: mssql.php
 * Description:
 * This file should be placed inside: 'xatafaxe/xf/db/drivers'
 * -------------
 *
 */

  class SQLLog {
    private static $logFile = null;
    private static $NR;
    
    private function init()    {
      if (self::$logFile == null) {
	self::$NR = 1;
        self::$logFile = fopen("sql.log",'a');
        fwrite(self::$logFile, "<=========================>\n" );
        fwrite(self::$logFile, "init::fopen('sql.log', 'a')\n" );
        fflush(self::$logFile);
      }
    }

    public static function noLogging() {
      self::$logFile = -1;
    }

    public static function log($text) {
      $nr = self::$NR++;
      if (self::$logFile == null)
        self::init();
      else if (self::$logFile == -1)
	return;

      fwrite(self::$logFile, "$nr: $text\n" );
      // fwrite(self::$logFile, "debug: " . $app->_conf['_database']['debug']);
      fflush(self::$logFile);
    }

    public static function _echo($str) {
    }
  }

function xf_db_debug() {
  SQLLog::noLogging();
}

// Function definition

class DBConn {
    static  $dbConn = "hej", $dbTable = "H";

    public static function conn() {
	return(self::$dbConn);
    }

    public static function setConn($conn) {
//	    SQLLog::log("  setConn('$conn')");
	self::$dbConn = $conn;
    }

    public static function table() {
        return(self::$dbTable);
    }

    public static function setTable($tb) {
	self::$dbTable = $tb;
    }
} 


function xf_db_connect($host, $user, $pass) {
  // ini_set('memory_limit','128M');
  $str = "sqlsrv_connect('$host', '$user', '$pass')";
  SQLLog::Log($str);

  $serverName = "(localdb)\mssqldb"; 
  $serverName = $host;
  // $connectionInfo = array( "Database"=>"test");
  // $connectionInfo = array("DataTypeCompatibility" => "80");
  $conInfo = array('ReturnDatesAsStrings' => true);

  if ($user == "")
    $conInfo = array('ReturnDatesAsStrings' => true);
  else
    $conInfo = array('ReturnDatesAsStrings' => true, 'UID' => $user, 'PWD' => $pass);

  $conn = sqlsrv_connect($serverName, $conInfo);

  DBConn::setConn($conn);
  $dbConn = DBConn::conn();
  SQLLog::Log("dbConn: '$dbConn', '$conn'");
  
  // $app =& Dataface_Application::getInstance();
  // fwrite(self::$logFile, "debug: " . $app->_conf['_database']['debug']);
  SQLLog::noLogging();

  SQLLog::log("  --> sqlsrv_connect('$serverName')");
  if ($conn) {
    SQLLog::Log("  ==> sqlConnect(OK)");
    sqlsrv_configure("WarningsReturnAsErrors", 0);
    return($conn);
  } else {
    SQLLog::Log("  ==> sqlConnect(FAILED)");
    if( ($errors = sqlsrv_errors() ) != null) {
       foreach( $errors as $error ) {
          echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
          echo "code: ".$error[ 'code']."<br />";
          echo "message: ".$error[ 'message']."<br />";
        }
    }
    /*
    echo "<br/>Client info:<br/>";
    if( $client_info = sqlsrv_client_info($conn)) {
      foreach( $client_info as $key => $value) {
         echo $key.": ".$value."\n";
       }
    }
    else {
      echo "Client info error.\n";
    }
    */
  }
}

function xf_db_get_server_info($db) {
  $str = "sql_get_server_info($db)";
  SQLLog::Log($str);

  $res = "5.6.20";
  SQLLog::Log("  ==> '$res'");
  return($res);
}

function xf_db_select_db($db) {
  $str = "sql_select_db($db)";
  SQLLog::Log($str);

  $sql = "USE $db";

  $dbConn = DBConn::conn();
  $stmt = sqlsrv_query($dbConn, $sql);
  $rc = 0;
  if ($stmt == true) {
    $rc = 1;
    SQLLog::Log("  ==> OK $rc");
    DBConn::setTable($db);

    // Create view's: 'dataface__pkeys'
    $setup[0] = 'IF OBJECT_ID ( \'dataface__pkeys\', \'V\' ) IS NULL ' .
                'exec(\'CREATE VIEW dataface__pkeys AS ' .
                'select tc.TABLE_NAME, tc.CONSTRAINT_NAME, kcu.COLUMN_NAME ' .
                'from INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ' .
                'JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ' .
                'ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME ' .
                'where tc.CONSTRAINT_TYPE = \'\'PRIMARY KEY\'\' and tc.TABLE_SCHEMA = \'\'dbo\'\' \')';
    // 'dataface__autoincr'
    $setup[1] = 'IF OBJECT_ID ( \'dataface__autoincr\', \'V\' ) IS NULL ' .
	         'exec(\'CREATE VIEW dataface__autoincr AS '.
	         'SELECT TABLE_NAME, COLUMN_NAME, ' .
                 '    (SELECT \'\'auto_increment\'\' AS Expr1) AS Extra ' .
                 '   FROM     INFORMATION_SCHEMA.COLUMNS ' .
                 '  WHERE  (COLUMNPROPERTY(OBJECT_ID(TABLE_NAME), COLUMN_NAME, \'\'IsIdentity\'\') = 1)\')';

    // Create stored procedures: 'showTables'
    $setup[2] = 'IF OBJECT_ID ( \'showTables\', \'P\' ) IS NULL ' .
          ' exec (\'CREATE PROCEDURE showTables AS Select Table_name as "Table name" From Information_schema.Tables ' .
	  ' Where Table_type = \'\'BASE TABLE\'\' and Objectproperty (Object_id(Table_name), ' .
	  ' \'\'IsMsShipped\'\') = 0\')';

    // Create stored procedures. 'showColumnsFrom'
    $setup[3] = 'IF OBJECT_ID ( \'showColumnsFrom\', \'P\' ) IS  NULL ' .
	   'exec (\'CREATE PROCEDURE showColumnsFrom @param  nvarchar(50) AS '.
	   ' SELECT t.column_name AS "Field", (SELECT case DATA_TYPE ' .
           '   when \'\'int\'\' then \'\'int(\'\' + CONVERT(varchar, t.NUMERIC_PRECISION) + \'\')\'\' ' .
           '   when \'\'nchar\'\' then \'\'varchar(\'\' + CONVERT(varchar, t.CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
           '   when \'\'nvarchar\'\' then \'\'varchar(\'\' + CONVERT(varchar, t.CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
	   '   when \'\'bit\'\' then \'\'bit(1)\'\' ' .
	   '   when \'\'time\'\' then \'\'time\'\' ' .
	   '   when \'\'date\'\' then \'\'varchar(50)\'\' ' .
	   '   when \'\'datetime2\'\' then \'\'varchar(50)\'\' ' .
	   '   else \'\'Unknown\'\'  end ' .
	   ' ) AS "Type",  ' .
	   't.IS_NULLABLE AS "Null", ' .
	   '  (SELECT IIF(t.column_name = d.column_name, \'\'PRI\'\', \'\'\'\')) AS "Key", ' .
	   ' t.column_default AS "Default",' .
           '  ISNULL((SELECT Extra from dataface__autoincr WHERE table_name = t.TABLE_NAME AND' .
           '  column_name = t.COLUMN_NAME), \'\'\'\') AS "Extra" from test.INFORMATION_SCHEMA.COLUMNS AS t ' .
           '  inner JOIN dataface__pkeys AS d ON t.TABLE_NAME = d.TABLE_NAME WHERE t.TABLE_NAME = @param\')';
    // Create stored procedures: 'convert_tz'
    $setup[4] = 'IF OBJECT_ID ( \'convert_tz\', \'FN\' ) IS  NULL ' .
	   'exec (\'CREATE FUNCTION [dbo].[convert_tz]' .
           '          (@param1  datetime, @param2  nvarchar(50), @param3  nvarchar(50))' .
           '          RETURNS datetime' .
           '          AS' .
           '          BEGIN' .
	   '            RETURN @param1' .
           '          END\')';

    for ($i = 0; $i < count($setup); $i++) {
      SQLLog::Log("$i  ==> " . $setup[$i]);
      $stmt = sqlsrv_query($dbConn, $setup[$i]);
      if ($stmt != true) {
        echo "Failed";
	die("change_db failed");
      }
    }

  } else {
     SQLLog::Log("  ==> failed $rc");
  }
  sqlsrv_free_stmt($stmt);
  return($rc);
}

function _error() {
  if( ($errors = sqlsrv_errors() ) != null) {
     foreach( $errors as $error ) {
       SQLLog::Log("   SQLSTATE: ".$error[ 'SQLSTATE']);
       SQLLog::Log("   code: ".$error['code']);
       SQLLog::Log("   message: ".$error[ 'message']);
      }
     return($error['code']);
  }
  return(0);
}

function xf_db_error($db = null) {
  $str = "sql_error($db)";
  SQLLog::Log($str);

  if ($db === null) {
    SQLLog::_echo("xf_db_error($db === null)");
    SQLLog::Log("xf_db_error($db === null)");
  }

  $arr = sqlsrv_errors();
  if (count($arr) == 0) {
    SQLLog::Log("  ==> OK return(true)");
    return(true);
  }
  else {
    SQLLog::Log("  ==> #arr: " . count($arr));
    for ($i=0;  $i < count($arr);  $i++) {
      $ar = $arr[$i];
      SQLLog::Log("   STATE: $ar[0], code: $ar[1], msg: $ar[2]");
      if ($ar[0] == "01000") {
	SQLLog::Log("ar[0]: $ar[0] => return(null)"); 
        return(0);
      }
    }
  }
  return(0);
}

function xf_db_errno($db) {
  $str = "sql_errno($db)";
  SQLLog::Log($str);
  return xf_db_error($db);
}

function xf_db_free_result($res) {
  SQLLog::Log("xf_db_free_result($res)");
  sqlsrv_free_stmt($res);
}

function multiexplode ($delimiters,$string) {
   
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

function xf_db_replace($str) {
  $log = "xf_db_replace($str)";
  SQLLog::Log("  tr < $str");
  
  // $str = mb_strtolower($str);			// Convert to lower case
  $word = explode(' ', trim($str));		// Get the 1'st word
  $tb = DBConn::table();
  if (($word[0] == "set") || ($word[0] == "SET")) {
    SQLLog::Log(" ======  $str ====== removed");
    $newStr = "";
  } else if (($word[0] == "show") || ($word[0] == "SHOW")) {
    SQLLog::Log("   -- ($word[0]): $str");
    $pattern = array();
    $pattern[0] = '/`/';
    $pattern[1] = '/^show[ ]*tables$/';
    $pattern[2] = '/^show[ ]*tables like(.*)$/';
    $pattern[3] = '/^SHOW[ ]*COLUMNS[ ]*FROM[ ]*\"([a-zA-Z_]*)\"$/';
    $pattern[4] = '/^SHOW[ ]*TABLES[ ]*LIKE[ ]*\'([a-zA-Z_%]*)\'/';
    $pattern[5] = '/^show[ ]*tables where(.*)$/';
    $pattern[6] = '/^show[ ]*columns[ ]*from[ ]*\"([a-zA-Z_]*)\"$/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = 'Select Table_name as "Table name" ' . 
                  'From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' and Objectproperty ' .
                  '(Object_id(Table_name), \'IsMsShipped\') = 0';

    $replace[2] = 'Select Table_name as "Table name" ' . 
                  'From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' and Objectproperty ' .
                  '(Object_id(Table_name), \'IsMsShipped\') = 0 AND Table_name LIKE $1' ;
    $replace[3] = 'showColumnsFrom \'$1\'';
    $replace[4] = 'SELECT table_name from information_schema.tables where table_name like \'$1\'';
    $replace[5] = 'Select Table_name as "Table name" ' . 
                  'From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' and Objectproperty ' .
                  '(Object_id(Table_name), \'IsMsShipped\') = 0 AND $1' ;
    $replace[6] = 'showColumnsFrom \'$1\'';

    $newStr = preg_replace($pattern, $replace, $str);
  } else if (($word[0] == "replace") || ($word[0] == "REPLACE")) {
    $word = multiexplode(array(" ", "(", ")", ",", "`"), trim($str));		// Get the 1'st word

    if ($word[2] == "dataface__mtimes")  {
     $newStr = "IF (NOT EXISTS(SELECT * FROM $word[2] WHERE \"$word[5]\" = $word[13]))" .
	     " BEGIN insert into $word[2] (\"$word[5]\", \"$word[8]\") ".
	     "  values ($word[13], $word[14]) END ELSE BEGIN ".
	     "update $word[2] set \"$word[8]\" = $word[14] where \"$word[5]\" = $word[13] END";

      for ($i=0; $i < count($word); $i++) {
        SQLLog::Log("word[$i]: >$word[$i]<");
      }
      if (count($word) != 16) {
        echo "<br/>newStr: $newStr<br/>";
        die("xf_db_replace(): expexted: 16 words got: " . count($word));
      }
    }
    else if ($word[2] == "dataface__record_mtimes")  {
      $newStr = "UPDATE $word[2] set \"$word[4]\" = $word[12], \"$word[8]\" = $word[14] WHERE $word[6] = $word[13]";
      echo "<br/>newStr: $newStr<br/>";
      if (count($word) != 16) {
        echo "<br/>newStr: $newStr<br/>";
        die("xf_db_replace(): expexted: 16 words got: " . count($word));
      }
    }
    else {
      $newStr = "XX";
      die("xf_db_replace(): expexted: 2 words got: " . count($word));
      for ($i=0; $i < count($word); $i++) {
        SQLLog::Log("word[$i]: >$word[$i]<");
      }
    }
  } else if (($word[0] == "create") || ($word[0] == "CREATE")) {
    $pattern = array();
    $pattern[0] = '/`/';
    $pattern[1] = '/ENGINE=InnoDB DEFAULT CHARSET=utf8/';
    $pattern[2] = '/int\([0-9]*\)/';				// int(#) => int
    $pattern[3] = '/unsigned/';
    $pattern[4] = '/auto_increment/';
    $pattern[5] = '/(,[\n \t]*index[a-zA-Z0-9 \t"_(]*[)])/';
    $pattern[6] = '/create table if not exists ["]?([a-zA-Z0-9_]*)["]? (\([a-zA-z0-9\(\)"\n,_ \t]*)/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = '';
    $replace[2] = 'int';
    $replace[3] = '';
    $replace[4] = 'IDENTITY(1,1)';
    $replace[5] = '';
    $replace[6] = 'IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = \'$1\') ' .
	          'CREATE TABLE "$1" $2';
    $newStr = preg_replace($pattern, $replace, $str);
  } else {			// NOT 'show' or 'create'
    SQLLog::_echo ("<br/>\$str: $str<br/>");
    $pattern = array();
    $pattern[0] = '/`/';
    $pattern[1] = '/int\([0-9]*\)/';           			// int(#) -> int
    $pattern[2] = '/length\(/';           			// length(..) -> LEN(..)
    $pattern[3] = '/select CREATE_TIME.*TABLE_SCHEMA=([a-zA-Z0-9_\']*).*TABLE_NAME=([a-zA-Z0-9_\']*) limit 1/';
    $pattern[4] = '/(SELECT)(.*)LIMIT[ ]+(\d+),(\d+)/';		// limit #,# -> select top .
    $pattern[5] = '/(select)(.*)limit[ ]+(\d+)/';
    $pattern[6] = '/(SELECT)(.*)LIMIT[ ]+(\d+)/';
    $pattern[7] = '/(UPDATE)(.*)LIMIT[ ]+(\d+)/';		// UPDATE ... LIMIT
    $pattern[8] = '/convert_tz/';
    $pattern[9] = '/ifnull/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = 'int';
    $replace[2] = 'LEN(';
    $replace[3] = 'select TOP create_date AS \'Create_time\',  \'NULL\' AS \'Update_time\' '.
	          'from sys.databases where name = $1';
    $replace[4] = '$1 TOP $4 $2';
    $replace[5] = '$1 TOP $3 $2';
    $replace[6] = '$1 TOP $3 $2';
    $replace[7] = '$1 $2';
    $replace[8] = 'dbo.convert_tz';
    $replace[9] = 'ISNULL';
    $newStr = preg_replace($pattern, $replace, $str);
  }
  SQLLog::Log("  tr > $newStr");
  return($newStr);
}

function xf_db_query($query, $db) {
  $str = "sql_query($query, $db)";
  $dbConn = DBConn::conn();
  SQLLog::Log($str);
  $nq = xf_db_replace($query);
  SQLLog::_echo ("<br/>xf_db_query($nq)<br/>)");
  SQLLog::Log("   <>  $nq");
  $ret = sqlsrv_query($dbConn, $nq, array(), array("Scrollable"=>"buffered"));
  if (! $ret) {
    SQLLog::Log("  ==> failed");
    $id = _error($dbConn);
    if ($id == 208 or $id == 2714) {
      // echo "MSSQL failed ! code: $id";
    }
    else
      die("MSSQL failed ! code: $id");
  }
  else {
    SQLLog::Log("  ==> OK '$ret'");
    $hr = sqlsrv_has_rows($ret);
    if ($hr == true)
      SQLLog::Log("  ==> has rows");
    else
      SQLLog::Log("  ==> has NOT rows");

    $rc = sqlsrv_num_rows($ret);
    SQLLog::Log("  rc==>  '$rc'");

    $nf = sqlsrv_num_fields($ret);
    SQLLog::Log("  nf==>  '$nf'");
  }

  return($ret);
}

function xf_db_fetch_row($res) {
  $row = null;
  $str = "sql_fetch_row_($res)";
  SQLLog::Log($str);
  SQLLog::_echo ("<br/>xf_db_fetch_row()<br/>");
  $hr = sqlsrv_has_rows($res);
  if ($hr != true) {
    SQLLog::Log("  ==>xf_db_fetch_row(): has NOT rows");
    return(null);
  }
  else {
    SQLLog::Log("  ==>xf_db_fetch_row(): has rows");

    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_NUMERIC); // SQLSRV_FETCH_BOTH);
    if (! $row) {
      SQLLog::Log("  ==>xf_db_fetch_row(): failed");
      $dbConn = DBConn::conn();
      _error($dbConn);
    }
    else {
      SQLLog::Log("  ==>xf_db_fetch_row(): OK (# " .  count($row) . ")");
      for ($i=0;  $i < count($row);  $i++) {
        SQLLog::Log("       $i:  " . $row[0]);
      }
    }
  }
  return($row);
}

function xf_db_fetch_array($res) {
  $str = "sql_fetch_array($res)";
  SQLLog::_echo ("<br/>xf_db_fetch_array()<br/>");
  SQLLog::Log($str);
  $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_BOTH);	 // SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ASSOC;
  if ($row == null) {
    SQLLog::Log("  -->xf_db_fetch_array(): no more rows");
    return(false);
  }
  else if ($row == false) {
    SQLLog::Log("  ==>xf_db_fetch_array(): failed");
    $dbConn = DBConn::conn();
    _error($dbConn);
  }
  else {
    SQLLog::Log("  ==>xf_db_fetch_array(): OK (# " . count($row) . ")");
  }
  return($row);
}

function xf_db_fetch_assoc($res) {
  $str = "sql_fetch_assoc($res)";
  SQLLog::_echo("<br/>xf_db_fetch_assoc()<br/>");
  SQLLog::Log($str);
  $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
  if ($row == null) {
    SQLLog::Log("  --> no more rows");
    return(false);
  }
  else if ($row == false) {
    SQLLog::Log("  ==> failed");
    $dbConn = DBConn::conn();
    _error($dbConn);
  }
  else {
    SQLLog::Log("  ==> OK (# " . count($row) . ")");
  }
  return($row);
}

function xf_db_num_rows($res) {
  $str = "sql_num_rows($res)";
  SQLLog::Log($str);
  $rc = sqlsrv_num_rows($res);
  SQLLog::_echo ("<br/>xf_db_num_rows:$rc<br/>");
  SQLLog::Log("  ==> OK (# " . $rc . ")");
  SQLLog::_echo ("<br/>RETURN(xf_db__num_rows) rc: ". $rc ."<br/>");
  return($rc);
}

function xf_db_insert_id($db) {
  // AUTOGENERATE
  $str = "xf_db_insert_id($db)";
  SQLLog::Log($str);
  $id = 0;
  SQLLog::Log("  ==> OK (id: " . $id . ")");

  return($id);
}
