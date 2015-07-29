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
    private static $logAll = false;
    private static $logQuery = false;
    private static $logDelete = false;
    private static $logUpdate = false;
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
      self::$logAll = false;
      self::$logQuery = false;
      self::$logDelete = false;
    }

    public static function log($text) {
      $nr = self::$NR++;
      if (self::$logFile == null)
        self::init();
      else if (self::$logFile == -1)
	return;

      if (self::$logAll) {
        fwrite(self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
    }

    public static function select($text) {
      $nr = self::$NR++;
      if (self::$logFile == null)
        self::init();
      else if (self::$logFile == -1)
	return;

      if (self::$logQuery) {
        fwrite(self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
    }

      public static function update($text) {
      $nr = self::$NR++;
      if (self::$logFile == null)
        self::init();
      else if (self::$logFile == -1)
	return;

      if (self::$logUpdate) {
        fwrite(self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
    }

    public static function delete($text) {
      $nr = self::$NR++;
      if (self::$logFile == null)
        self::init();
      else if (self::$logFile == -1)
	return;

      if (self::$logDelete) {
        fwrite(self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
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
	// phpinfo();
  // ini_set('memory_limit','128M');
  $str = "sqlsrv_connect('$host', '$user', '$pass')";
  SQLLog::Log($str);

  $serverName = "(localdb)\mssqldb"; 
  $serverName = $host;
  // $connectionInfo = array( "Database"=>"test");
  // $connectionInfo = array("DataTypeCompatibility" => "80");
  $conInfo = array('ReturnDatesAsStrings' => true);

  if ($user == "") {
    $conInfo = array('ReturnDatesAsStrings' => true, 'CharacterSet'=>'UTF-8');
  }
  else {
    $conInfo = array('ReturnDatesAsStrings' => true, 'CharacterSet'=>'UTF-8', 'UID' => $user, 'PWD' => $pass);
  }

  $conn = sqlsrv_connect($serverName, $conInfo);

  DBConn::setConn($conn);
  $dbConn = DBConn::conn();
  SQLLog::Log("DbConn: '$dbConn', '$conn'");
  
  // $app =& Dataface_Application::getInstance();
  // fwrite(self::$logFile, "debug: " . $app->_conf['_database']['debug']);
  // SQLLog::noLogging();

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
  $db = '[' . $db . ']';
  $str = "sql_select_db($db)";
  SQLLog::select($str);

  $sql = "USE $db";

  $dbConn = DBConn::conn();
  $stmt = sqlsrv_query($dbConn, $sql);
  $rc = 0;
  if ($stmt == true) {
    $rc = 1;
    SQLLog::Log("  ==> OK $rc");
    DBConn::setTable($db);

    $tb = DBConn::table();
    SQLLog::Log("  ==> OK $tb");
    
    // Create view's: 'dataface__pkeys'
    /*
    $setup[0] = 'IF OBJECT_ID ( \'dataface__pkeys\', \'V\' ) IS NULL ' .
                'exec(\'CREATE VIEW dataface__pkeys AS ' .
                'select tc.TABLE_NAME, tc.CONSTRAINT_NAME, kcu.COLUMN_NAME' .
                'from INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ' .
                'JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ' .
                'ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME ' .
                'where tc.CONSTRAINT_TYPE = \'\'PRIMARY KEY\'\' and tc.TABLE_SCHEMA = \'\'dbo\'\' \')';
    */
    $setup[0] = 'IF OBJECT_ID ( \'dataface__pkeys\', \'V\' ) IS NULL ' .
                'exec(\'CREATE VIEW dataface__pkeys AS ' .
                'select tc.TABLE_NAME, kcu.COLUMN_NAME, (SELECT \'\'PRI\'\') AS "Key" ' .
                'from INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ' .
                'JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ' .
                'ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME ' .
                'where tc.CONSTRAINT_TYPE = \'\'PRIMARY KEY\'\' \')';
                // 'where tc.CONSTRAINT_TYPE = \'\'PRIMARY KEY\'\' and tc.TABLE_SCHEMA = \'\'dbo\'\' \')';
    // 'dataface__autoincr'
    $setup[1] = 'IF OBJECT_ID ( \'dataface__autoincr\', \'V\' ) IS NULL ' .
	         'exec(\'CREATE VIEW dataface__autoincr AS '.
	         'SELECT TABLE_NAME, COLUMN_NAME, ' .
                 '    (SELECT \'\'auto_increment\'\' AS Expr1) AS Extra ' .
                 '   FROM     INFORMATION_SCHEMA.COLUMNS ' .
                 '  WHERE  (COLUMNPROPERTY(OBJECT_ID(TABLE_NAME), COLUMN_NAME, \'\'IsIdentity\'\') = 1)\')';

    // Create stored procedures: 'showTables'
    /*
    $setup[2] = 'IF OBJECT_ID ( \'showTables\', \'P\' ) IS NULL ' .
          ' exec (\'CREATE PROCEDURE showTables AS Select Table_name as "Table name" From Information_schema.Tables ' .
	  ' Where Table_type = \'\'BASE TABLE\'\' and Objectproperty (Object_id(Table_name), ' .
	  ' \'\'IsMsShipped\'\') = 0\')';
    */
    $setup[2] = 'IF OBJECT_ID ( \'showTables\', \'P\' ) IS NULL ' .
          ' exec (\'CREATE PROCEDURE showTables AS (SELECT TABLE_NAME FROM Information_schema.Tables' .
          '         UNION SELECT TABLE_NAME FROM INFORMATION_SCHEMA.Views)\')';
	  // ' Where Table_type = \'\'BASE TABLE\'\'\')';

    // Create stored procedures. 'showColumnsFrom'
    $setup[3] = 'IF OBJECT_ID ( \'showColumnsFrom\', \'P\' ) IS  NULL ' .
	   'exec (\'CREATE PROCEDURE showColumnsFrom @param  nvarchar(50) AS '.
	   ' SELECT column_name AS "Field", (SELECT case DATA_TYPE ' .
           '   when \'\'int\'\' then \'\'int(\'\' + CONVERT(varchar, NUMERIC_PRECISION) + \'\')\'\' ' .
           '   when \'\'bigint\'\' then \'\'bigint(\'\' + CONVERT(varchar, NUMERIC_PRECISION) + \'\')\'\' ' .
	   '   when \'\'real\'\' then \'\'real\'\' ' .
           '   when \'\'nchar\'\' then \'\'varchar(\'\' + CONVERT(varchar, CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
           '   when \'\'nvarchar\'\' then \'\'varchar(\'\' + CONVERT(varchar, CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
           '   when \'\'char\'\' then \'\'varchar(\'\' + CONVERT(varchar, CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
           '   when \'\'varchar\'\' then \'\'varchar(\'\' + CONVERT(varchar, CHARACTER_MAXIMUM_LENGTH) + \'\')\'\' ' .
	   '   when \'\'text\'\' then \'\'text\'\' ' .
	   '   when \'\'bit\'\' then \'\'bit(1)\'\' ' .
	   '   when \'\'time\'\' then \'\'time\'\' ' .
	   '   when \'\'date\'\' then \'\'date\'\' ' .
	   '   when \'\'datetime\'\' then \'\'datetime\'\' ' .
	   '   when \'\'datetime2\'\' then \'\'varchar(50)\'\' ' .
	   '   else \'\'Unknown\'\'  end ' .
	   ' ) AS "Type",  ' .
	   'IS_NULLABLE AS "Null", ' .
	   //
	   // '  (SELECT IIF(t.column_name = d.column_name, \'\'PRI\'\', \'\'\'\')) AS "Key", ' .
	   '  ISNULL((SELECT [Key] from dataface__pkeys WHERE table_name = t.TABLE_NAME ' .
	   '     AND  column_name = t.COLUMN_NAME), \'\'\'\') AS "Key",' .
	   //
	   ' column_default AS "Default",' .
           '  ISNULL((SELECT Extra from dataface__autoincr WHERE table_name = t.TABLE_NAME AND ' .
           '  column_name = t.COLUMN_NAME), \'\'\'\') AS "Extra" from ' . $tb . '.INFORMATION_SCHEMA.COLUMNS AS t ' .
           '  WHERE TABLE_NAME = @param\')';
           // '  inner JOIN dataface__pkeys AS d ON t.TABLE_NAME = d.TABLE_NAME WHERE t.TABLE_NAME = @param\')';
    // Create stored procedures: 'convert_tz'
    $setup[4] = 'IF OBJECT_ID ( \'convert_tz\', \'FN\' ) IS  NULL ' .
	   // 'exec (\'CREATE FUNCTION [dbo].[convert_tz]' .
	   'exec (\'CREATE FUNCTION [convert_tz]' .
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
	echo <<< END
         <fieldset style="background:yellow"><legend><b>SQL Error</b></legend>
	 <table>
	   <tr>
	     <td valign="top"><b>Query:</b></td>
	     <td>$setup[$i]</td>
	   </tr>
	   <tr>
	     <td><b>Id#</b></td>
             <td>$dbConn</td>
	   </tr>
END;
      if( ($errors = sqlsrv_errors() ) != null) {
        foreach( $errors as $error ) {
          echo "<tr>" .
	       "  <td><b>SQLSTATE:</b></td>" .
	       "  <td>" . $error['SQLSTATE'] . "</td>" .
	       "</tr>" .
	       "<tr>" .
	       "  <td><b>Code:</b></td>" .
	       "  <td>" . $error['code'] . "</td>" .
	       "<tr>" .
	       "<tr>" .
	       "  <td valign=top><b>Message:</b></td>" .
	       "  <td>" . $error[ 'message']. "</td>" .
	       "</tr>" ;
        }
      }

      echo <<< END
	 </table>
         </fieldset>
END;
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
  if ($res === "Resource id #75") {   // MM !!
    SQLLog::Log("xf_db_free_result($res)");
	  die("stop");
  }
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

  /*
  if (preg_match('/__history/', $str)) {
    SQLLog::delete("  delete(drop) < $str");
    return("");
  }
   */
  
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
    $pattern[3] = '/^SHOW[ ]*COLUMNS[ ]*FROM[ ]*\"([a-zA-Z_0-9\-]*)\"$/';
    $pattern[4] = '/^SHOW[ ]*TABLES[ ]*LIKE[ ]*\'([a-zA-Z_%]*)\'/';
    $pattern[5] = '/^show[ ]*tables where(.*)$/';
    $pattern[6] = '/^show[ ]*columns[ ]*from[ ]*\"([a-zA-Z_0-9\-]*)\"$/';
    $pattern[7] = '/^show[ ]*databases$/';
    $pattern[7] = '/^SHOW TABLE STATUS LIKE \'([a-zA-Z_%]*)\'/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = 'seLect Table_name as "Table name" ' . 
                  'From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' ' . // and Objectproperty ' .
                  'UNION SELECT TABLE_NAME FROM INFORMATION_SCHEMA.Views';
                  // '(Object_id(Table_name), \'IsMsShipped\') = 0';

    $replace[2] = 'Select Table_name as "Table name" ' . 
                  'From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' and Objectproperty ' .
                  '(Object_id(Table_name), \'IsMsShipped\') = 0 AND Table_name LIKE $1' ;
    $replace[3] = 'showColumnsFrom \'$1\'';
    $replace[4] = 'SELECT table_name from information_schema.tables where table_name like \'$1\'';
    $replace[5] = 'select Table_name as "Table name" From Information_schema.Tables ' .
                  'Where Table_type = \'BASE TABLE\' AND $1 ' .
                  'UNION SELECT TABLE_NAME FROM INFORMATION_SCHEMA.Views where $1';
                  // 'and Objectproperty (Object_id(Table_name), \'IsMsShipped\') = 0 AND $1' ;
    $replace[6] = 'showColumnsFrom \'$1\'';
    $replace[7] = 'SELECT NAME FROM sys.sysdatabases ';
    $replace[8] = 'ses ';

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
      // $newStr = "UPDATE $word[2] set \"$word[4]\" = $word[12], \"$word[8]\" = $word[14] WHERE $word[6] = $word[13]";
      
      $newStr = "IF (NOT EXISTS(SELECT * FROM $word[2] WHERE \"$word[4]\" = $word[12]))" .
	        " BEGIN insert into $word[2] (\"$word[4]\", \"$word[6]\", \"$word[8]\") ".
	        "  values ($word[12], $word[13], $word[14]) END ELSE BEGIN ".
	        "  update $word[2] set \"$word[6]\" = $word[13], \"$word[8]\" = $word[14] where \"$word[4]\" = $word[12] END";
      // echo "<br/>newStr: $newStr<br/>";

      /* Alternatively
         INSERT INTO dataface__record_mtimes (recordhash, recordid, mtime) 
         SELECT '34ffc485954c946d321cc41855085d32','status?id=2','1431715531'  WHERE NOT EXISTS 
         (SELECT recordhash FROM dataface__record_mtimes
          WHERE recordhash = '34ffc485954c946d321cc41855085d32');

         UPDATE dataface__record_mtimes
         SET recordid = 'status?id=23', mtime = '1431715533'
         WHERE recordhash = '34ffc485954c946d321cc41855085d32'
     */       
      for ($i=0; $i < count($word); $i++) {
        SQLLog::select("word[$i]: >$word[$i]<");
      }
      if (count($word) != 16) {
        echo "<br>newStr: $newStr<br/>";
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
    $pattern[7] = '/KEY ([a-zA-Z0-9]*) using [a-z]+ /';
    $pattern[7] = '/KEY ([a-zA-Z0-9]*) using [a-z]+ \([a-zA-z0-9_"]+\)/';
    $pattern[8] = '/CONVERT\(([a-zA-Z0-9" ]*),[ ]*CHAR\(50\)\)/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = '';
    $replace[2] = 'int';
    $replace[3] = '';
    $replace[4] = 'IDENTITY(1,1)';
    $replace[5] = '';
    $replace[6] = 'IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = \'$1\') ' .
	          'CREATE TABLE "$1" $2';
    $replace[7] = 'CONSTRAINT $1 UNIQUE';
    $replace[7] = '';
    $replace[8] = 'CAST($1 AS CHAR)';
    $newStr = preg_replace($pattern, $replace, $str);

  } else if (($word[0] == "truncate") || ($word[0] == "TRUNCATE")) {
      SQLLog::Log("truncate");
      $newStr = "";

  } else if (($word[0] == "insert" && $word[1] == "ignore") || ($word[0] == "INSERT" && $word[1] == "IGNORE")) {     // 'insert ignore ....' 
    SQLLog::select("insert ignore");
    $arg = explode(' ', trim($str));
    SQLLog::select($arg[0] . ", " . $arg[1] . ", " . $arg[2] . ", " . $arg[3] . ", " . $arg[4]);
    $str = "INSERT INTO $arg[3] ($arg[5]) SELECT $arg[7] " .
	      " WHERE NOT EXISTS (SELECT $arg[5] FROM $arg[3] WHERE $arg[5] = $arg[7])";
    $pattern = array();
    $pattern[0] = '/`/';
    $replace = array();
    $replace[0] = '"';

    $newStr = preg_replace($pattern, $replace, $str);

  } else if (($word[0] == "update") || ($word[0] == "UPDATE")) {
    $pattern = array();
    $pattern[0] = '/`/';
    $pattern[1] = '/(UPDATE)[ ]*(.*)LIMIT[ ]*(\d+),(\d+)/';		// UPDATE ... LIMIT #,#
    $pattern[2] = '/LIMIT 1/';						// UPDATE ... LIMIT 1
    $replace = array();
    $replace[0] = '"';
    $replace[1] = '$1 $2';
    $replace[2] = '';

    $newStr = preg_replace($pattern, $replace, $str);
    SQLLog::update("Update: " . $newStr);

  } else {			// NOT 'show' or 'create'
    SQLLog::_echo ("<br/>\$str: $str<br/>");
    $pattern = array();
    $pattern[0] = '/`/';
    $pattern[1] = '/int\([0-9]*\)/';           			// int(#) -> int
    $pattern[2] = '/length\(/';           			// length(..) -> LEN(..)
    $pattern[3] = '/NOW\(\)/';           			// Time function NOW()
    $pattern[4] = '/SELECT (.*) LIMIT 0,1/';           			// Just remove all 'LIMIT 0,1' -> just one row

    $pattern[5] = '/select CREATE_TIME.*TABLE_SCHEMA=([a-zA-Z0-9_\']*).*TABLE_NAME=([a-zA-Z0-9_\']*) limit ([0-9]*)/';
    $pattern[6] = '/(SELECT)(.*)FROM(.*)ORDER BY (.*) LIMIT[ ]+(\d+),(\d+)/';		   // limit #,# -> select top .
    $pattern[6] = '/(SELECT)(.*)FROM(.*)ORDER BY [a-zA-Z_\-"]*.(.*) LIMIT[ ]+(\d+),(\d+)/';   // limit #,# -> select top .
    $pattern[7] = '/(SELECT)(.*)FROM(.*)LIMIT[ ]+(\d+),(\d+)/';		// limit #,# -> select top .
    $pattern[8] = '/(select)(.*)limit[ ]+(\d+)/';
    $pattern[9] = '/(SELECT)(.*)LIMIT[ ]+(\d+)/';
    $pattern[10] = '/(select)(.*)from(.*)LIMIT[ ]+(\d+),(\d+)/';		// 'from' is a join
    $pattern[10] = '/(select)(.*)from(.*)LIMIT[ ]+(\d+),(\d+)/';		// 'from' is a join
    $pattern[11] = '/convert_tz\("([a-zA-Z0-9_.-]+)"\)/';
    $pattern[11] = '/convert_tz\(([a-zA-Z0-9."_ -]+),[a-zA-Z\', \/_-]+\)/';
    $pattern[12] = '/ifnull/';
    $pattern[13] = '/LIMIT 0,1/';
    $pattern[14] = '/CONVERT\(([a-zA-Z0-9" ]*),[ ]*CHAR\(50\)\)/';
    $pattern[15] = '/CONCAT/';
    $replace = array();
    $replace[0] = '"';
    $replace[1] = 'int';
    $replace[2] = 'LEN(';
    $replace[2] = 'DATALENGTH (';
    $replace[3] = 'getdate()';

    $replace[4] = 'SELECT TOP 1 $1';
    $replace[5] = 'select TOP $3 create_date AS \'Create_time\',  \'NULL\' AS \'Update_time\' '.
	          'from sys.databases where name = $2';
    $replace[6] = '=1:=$1=TOP=4:=$4=2:=$2=3:=$3=5:=$5';
    $replace[6] = '$1 TOP $5 $2 FROM $3';
    $replace[6] = '01$3:select $2 FROM( SELECT *, ROW_NUMBER() over (ORDER BY <id>) as __ct from $3 ) ' .
                  ' sub where __ct > $5 and __ct <= $5+$6 ORDER BY $4';
    $replace[7] = '01$3:select $2 FROM( SELECT *, ROW_NUMBER() over (ORDER BY <id>) as __ct from $3 ) ' .
                  ' sub where __ct > $4 and __ct <= $4+$5';
    $replace[8] = '$1 TOP $3 $2';
    $replace[9] = '$1 TOP $3 $2';
    $replace[10] = 'Select $1, >$2< from:>$3<, ($4,$5)';
    $replace[10] = '>>Select $2 from $3';
    $replace[11] = 'dbo.convert_tz [$1]';
    $replace[11] = '$1';
    $replace[12] = 'ISNULL';
    $replace[13] = '';
    $replace[14] = 'cast($1 AS CHAR)';
    $replace[15] = 'concat';
    $newStr = preg_replace($pattern, $replace, $str);
  }
  SQLLog::Log("  tr > $newStr");

  if (substr($newStr, 0,2) == "01") {
    SQLLog::select("");
    SQLLog::select("===\$newStr: " . $newStr);
    if (false) {
      print_r($_SESSION['-table']);
      print_r($_GET['-table']);
      $str = ' 01 (select *, concat(\'\' , \' \', cast(SQLID AS CHAR), \' \', cast(IndkoebsDato AS CHAR), \' \', cast(BrugerType AS CHAR), \' \', cast(EksternBrugerSqlId AS CHAR), \'\') as xb_search , concat(\'Null\', \'\') as Null_group_1 from tbl_MobilTlf) as "tbl_MobilTlf" :select  DATALENGTH ("tbl_MobilTlf"."xb_search") as "__xb_search_length","tbl_MobilTlf"."xb_search",DATALENGTH ("tbl_MobilTlf"."Null_group_1") as "__Null_group_1_length","tbl_MobilTlf"."Null_group_1",DATALENGTH ("tbl_MobilTlf"."MobilType") as "__MobilType_length","tbl_MobilTlf"."MobilType",DATALENGTH ("tbl_MobilTlf"."EmeiNr") as "__EmeiNr_length","tbl_MobilTlf"."EmeiNr",DATALENGTH ("tbl_MobilTlf"."SerieNr") as "__SerieNr_length","tbl_MobilTlf"."SerieNr",DATALENGTH ("tbl_MobilTlf"."IndkoebsDato") as "__IndkoebsDato_length","tbl_MobilTlf"."IndkoebsDato",DATALENGTH ("tbl_MobilTlf"."Status") as "__Status_length","tbl_MobilTlf"."Status",DATALENGTH ("tbl_MobilTlf"."BrugerType") as "__BrugerType_length","tbl_MobilTlf"."BrugerType",DATALENGTH ("tbl_MobilTlf"."MedarbejderSqlID") as "__MedarbejderSqlID_length","tbl_MobilTlf"."MedarbejderSqlID",DATALENGTH("tbl_MobilTlf"."OrgEnhedSqlID") as "__OrgEnhedSqlID_length","tbl_MobilTlf"."OrgEnhedSqlID",DATALENGTH ("tbl_MobilTlf"."EksternBrugerSqlId") as "__EksternBrugerSqlId_length","tbl_MobilTlf"."EksternBrugerSqlId",DATALENGTH ("tbl_MobilTlf"."Note") as "__Note_length","tbl_MobilTlf"."Note",DATALENGTH ("tbl_MobilTlf"."TlfNr" as "__TlfNr_length","tbl_MobilTlf"."TlfNr",DATALENGTH ("tbl_MobilTlf"."SidstGuiOpdateret") as "__SidstGuiOpdateret_length","tbl_MobilTlf"."SidstGuiOpdateret",DATALENGTH ("tbl_MobilTlf"."SidstGuiOpdateretAf") as "__SidstGuiOpdateretAf_length","tbl_MobilTlf"."SidstGuiOpdateretAf",DATALENGTH ("tbl_MobilTlf"."SQLID") as"__SQLID_length","tbl_MobilTlf"."SQLID",DATALENGTH ("tbl_MobilTlf"."KundeNr") as"__KundeNr_length","tbl_MobilTlf"."KundeNr",DATALENGTH ("tbl_MobilTlf"."Personlig") as "__Personlig_length","tbl_MobilTlf"."Personlig",DATALENGTH ("tbl_MobilTlf"."PersonligUdenPrivat") as "__PersonligUdenPrivat_length","tbl_MobilTlf"."PersonligUdenPrivat",DATALENGTH ("tbl_MobilTlf"."FunktionsTlf") as "__FunktionsTlf_length","tbl_MobilTlf"."FunktionsTlf",DATALENGTH ("tbl_MobilTlf"."FunktionsTekst" as "__FunktionsTekst_length","tbl_MobilTlf"."FunktionsTekst"  FROM( SELECT *,ROW_NUMBER() over (ORDER BY <id>) as __ct from  (select *, concat(\'\' , \' \', cast(SQLID AS CHAR), \' \', cast(IndkoebsDato AS CHAR), \' \', cast(BrugerType AS CHAR),\' \', cast(EksternBrugerSqlId AS CHAR), \'\') as xb_search , concat(\'Null\', \'\') asNull_group_1 from tbl_MobilTlf) as "tbl_MobilTlf"  )  sub where __ct > 0 and __ct <= 0+20';

      $pattern = array();
      $pattern[0] = '/(01) \(select concat\([a-zA-Z0-9_-]*, [a-zA-Z0-9_-]*\) as [a-zA-Z0-9_ ]*\) as ([a-zA-Z_"-]*)/';
      $pattern[0] = '/(01) \(select concat\([a-zA-Z0-9_-]+, [a-zA-Z0-9_-]+\) as ([a-zA-Z0-9_]+), SqlId/';

      $pattern[0] = '/(01) \(select #, concat\([a-zA-Z0-9_-]+, [a-zA-Z0-9_-]+\) as ([a-zA-Z0-9_]+), SqlId/';
      $pattern[0] = '/(01) \(select \*, concat\(/';
      $pattern[0] = '/(01) \(select concat\([a-zA-Z0-9_-]*, [a-zA-Z0-9_-]*\) as [a-zA-Z0-9_ ]*\) as ([a-zA-Z_"-]*)/';
      $pattern[0] = '/(01) \(select \*, concat\([a-zA-Z0-9, \']+/';
      $pattern[0] = '/(01) .* as "(.+)" :select/';

      $replace = array();
      $replace[0] = 'X[$1] >$2< >$3<'; 
      // $rc = preg_replace($pattern, $replace, $newStr);
      $rc = preg_replace($pattern, $replace, $str);
      SQLLog::select("-------------");
      SQLLog::select("rc : '$rc'");
      echo "rc : '$str'<p>";
      echo "rc : '$rc'";
      // preg_match( ''/(01) \(([a-zA-Z0-9_ \.\*="]+)\) as "([a-zA-Z0-9_]+)"/', $newStr, $matches);
      preg_match('/(01) \(([a-zA-Z0-9_ \.\*="]+)\) as ([a-zA-Z0-9_\-"]+)/', $str, $matches);
      echo "<p>var_dump: " ; var_dump($matches);
      echo "<br>matches: $matches[3]";
      SQLLog::select("-------------");
      // die("STOP");
    }

    // http://localhost/xbuildsite/index.php?-table=xBuildSite__fields
    $tbl = "";
    if (isset($_GET['-table'])) {
      SQLLog::select("--->\$tbl: " . $_GET['-table']);
      $tbl = $_GET['-table'];
      // SQLLog::select("\$_GET['-table']: " . $_GET['-table'] . '$tbl');
      SQLLog::select("--->\$tbl: '$tbl'");
      // echo ">>: " . $newStr;
    }
    else if (preg_match('/(01) .* as "(.+)" :select/', $newStr, $matches)) {
      SQLLog::select("#0 FOUND" . $matches[2]);
      // print_r($matches);
      $tbl = $matches[2];
    }
    else if (preg_match('/(01) (select [a-zA-Z0-9_\-".= ]*)("[a-zA-Z0-9_\-]*") :/', $newStr, $matches)) {
      SQLLog::select("#1 FOUND" . $matches[2]);
      // print_r($matches);
      $tbl = $matches[2];
    }
    else if (preg_match('/(01) "([a-zA-Z0-9_\-]*)" :/', $newStr, $matches)) {
      SQLLog::select("#2 FOUND" . $matches[2]);
      // print_r($matches);
      $tbl = $matches[2];
    } else if (preg_match('/(01) \(([a-zA-Z0-9_ \.\*="]+)\) as ([a-zA-Z0-9_\-"]+)/', $newStr, $matches)) {
      SQLLog::select("#3.1 FOUND" . $matches[3]);
      $tbl = $matches[3];
    } else if (preg_match('/(01) ["a-zA-Z0-9_.]+ as "([a-zA-Z0-9_-]+)"/' , $newStr, $matches)) {
      SQLLog::select("#3.2 FOUND" . $matches[2]);
      $tbl = $matches[2];
    } else if (preg_match('/(01) \(select concat\([\'a-zA-Z0-9_, -]+\) as [a-zA-Z0-9_]+[\*a-zA-Z_0-9, ]+\) as (["a-zA-Z0-9_-]+)/', $newStr, $matches)) {
      SQLLog::select("#4.1 FOUND" . $matches[2]);
      $tbl = $matches[2];
    } else if (preg_match('/(01) \(select concat\([a-zA-Z0-9_-]+, [a-zA-Z0-9_-]+\) as [a-zA-Z0-9_]+[\*a-zA-Z_0-9, ]+\) as (["a-zA-Z0-9_-]+)/', $newStr, $matches)) {
    // This is perhaps the same as above
      SQLLog::select("#4.2 FOUND" . $matches[2]);
      $tbl = $matches[2];
    } else if (preg_match('/(01) \(([a-zA-Z0-9_\- .*=\'<>]*)\)[ ]*as[ ]*"([a-zA-Z0-9_\- ]*)" :/', $newStr, $matches)) {
      SQLLog::select("#5: FOUND: " . $matches[3]);
      // print_r($matches);
      $tbl = $matches[3];
    } else
        SQLLog::select("NOT FOUND");

    $select = "showColumnsFrom " . $tbl;
    SQLLog::select("\$Select: " . $select);

    $res = xf_db_query($select, null);
    if ($res) {
      SQLLog::select("OK");
      $row = xf_db_fetch_array($res, MYSQL_NUM);
      $id = $row[0];
      SQLLog::select("\$id: " . $id);
    }
    else 
      SQLLog::select("FAILED !");
       

    $pattern = array();
    $pattern[0] = '/.*:/';
    $pattern[1] = '/<id>/';
    $pattern[2] = '/"[a-zA-Z_\-]*"\./';
    // $pattern[2] = '/"' . $tbl . '"\./';		// Remove prefix "$tbl".
    $replace = array();
    $replace[0] = '';
    $replace[1] = $id;
    // $replace[2] = '';

    $str = $newStr;
    $newStr = preg_replace($pattern, $replace, $str);
    SQLLog::select("  tr > $newStr");
  }
  
  // $newStr = preg_replace('/dataface__/', 'dbo.xb__dataface__', $newStr);
  return($newStr);
}

function xf_db_query($query, $db) {
  $str = "sql_query($query, $db)";
  $dbConn = DBConn::conn();
  SQLLog::select($str);
  $nq = xf_db_replace($query);
  // SQLLog::_echo ("<br/>xf_db_query($nq)<br/>)");
  SQLLog::select("   <>  $nq");
  $ret = sqlsrv_query($dbConn, $nq, array(), array("Scrollable"=>"buffered"));
  if (! $ret) {
    SQLLog::Log("  ==> failed");
    $id = _error($dbConn);
    if ($id == 208 or $id == 2714) {
      // echo "MSSQL failed ! code: $id";
    }
    else {
      $dbConn = DBConn::conn();

      echo <<< END
         <fieldset style="background:yellow"><legend><b>SQL Error</b></legend>
	 <table>
	   <tr>
	     <td valign="top"><b>Query:</b></td>
	     <td>$query</td>
	   </tr>
	   <tr>
	     <td valign="top"><b>Translated:</b></td>
	     <td>$nq</td>
	   </tr>
	   <tr>
	     <td><b>Id#</b></td>
             <td>$dbConn</td>
	   </tr>
END;
      if( ($errors = sqlsrv_errors() ) != null) {
        foreach( $errors as $error ) {
          echo "<tr>" .
	       "  <td><b>SQLSTATE:</b></td>" .
	       "  <td>" . $error['SQLSTATE'] . "</td>" .
	       "</tr>" .
	       "<tr>" .
	       "  <td><b>Code:</b></td>" .
	       "  <td>" . $error['code'] . "</td>" .
	       "<tr>" .
	       "<tr>" .
	       "  <td valign=top><b>Message:</b></td>" .
	       "  <td>" . $error[ 'message']. "</td>" .
	       "</tr>" ;
        }
      }

      echo <<< END
	 </table>
         </fieldset>
END;
      die("MSSQL failed ! code: $id");
    }
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
  SQLLog::select("<<<<>  $nq");

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
    SQLLog::Log("  ==>xf_db_fetch_array(): OK (# " . count($row) . "): \$row[0]: " . $row[0]);
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
