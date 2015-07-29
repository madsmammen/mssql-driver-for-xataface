<?php
if (! class_exists('SQLLog')) {
  class SQLLog
  {
    private static $logFile;
    private static $logAll = false;
    private static $logQuery = true;
    private static $NR;
    
    private function init()    {
      if( self::$logFile == null ) {
	self::$NR = 1;
        self::$logFile = fopen("sql.log",'a');
        fwrite(self::$logFile, "<=========================>\n" );
        fwrite(self::$logFile, "init::fopen('sql.log', 'a')\n" );
        fflush(self::$logFile);
        // echo "<br/>Fopen()";
      }
    }

    public static function log($text) {
      $nr = self::$NR++;
      // self::$logFile = fopen("sql.log",'a');
      self::init();
      // echo "<br/>log($text)";
      if (self::$logAll) {
        fwrite( self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
      // fclose(self::$logFile);
      // self::$logFile = null;
    }

    public static function select($text) {
      $nr = self::$NR++;
      // self::$logFile = fopen("sql.log",'a');
      self::init();
      // echo "<br/>log($text)";
      if (self::$logQuery) {
        fwrite( self::$logFile, "$nr: $text\n" );
        fflush(self::$logFile);
      }
      // fclose(self::$logFile);
      // self::$logFile = null;
    }

    public static function _echo($str) {
    }
  }
}


function xf_db_connect($host,$user,$pass) {
  SQLLog::log("xf_db_connect('$host','$user','$pass')");
  return mysql_connect($host, $user, $pass);
}

function xf_db_connect_errno() {
  SQLLog::log("xf_db_connect_errno()");
  return mysql_connect_errno();
}

function xf_db_connect_error() {
  SQLLog::log("xf_db_connect_error()");
  return mysql_connect_error();
}

function xf_db_query($sql, $conn=null) { 
  SQLLog::select("xf_db_query($sql, $conn=null)");
  if ( $conn === null ){
    return mysql_query($sql);
  } else {
    return mysql_query($sql, $conn); 
  }
}

function xf_db_error($link=null) {
  SQLLog::log("xf_db_error($link=null)");
  if ( $link === null ){
    return mysql_error();
  } else {
    return mysql_error($link);
  }
}

function xf_db_errno($link=null){  
  SQLLog::log("xf_db_errno($link=null)");
  if ( $link === null ){
    return mysql_errno();
  }
  return mysql_errno($link); 
}

function xf_db_escape_string($unescaped_string) {
  SQLLog::log("xf_db_escape_string($unescaped_string)");
  return mysql_escape_string($unescaped_string);
}

function xf_db_real_escape_string($link, $unescaped_string) {
  SQLLog::log("xf_db_real_escape_string($link, $unescaped_string)");
  return mysql_real_escape_string($link, $unescaped_string);
}

function xf_db_fetch_array($result) {
  SQLLog::log("xf_db_fetch_array($result)");
  $arr = mysql_fetch_array($result); 
  if ($arr == null)
    SQLLog::log("  ==> no more rows");
  else
    SQLLog::log("  ==> count(): " . count($arr));
  return($arr);
}

function xf_db_fetch_assoc($result) {
  SQLLog::log("xf_db_fetch_assoc($result)");
  $arr = mysql_fetch_assoc($result); 
  if ($arr == null)
    SQLLog::log("  ==> no more rows");
  else
    SQLLog::log("  ==> count(): " . count($arr));
  return($arr);
}

function xf_db_fetch_object($result) {
  SQLLog::log("xf_db_fetch_object($result)");
  return mysql_fetch_object($result);
}

function xf_db_fetch_row($result) {
  SQLLog::log("xf_db_fetch_row($result)");
  return mysql_fetch_row($result); 
}

function xf_db_select_db($dbname, $link=null) { 
  SQLLog::select("xf_db_select_db($dbname, $link=null)");
  if ( $link === null ){
    return mysql_select_db($dbname);
  }
  return mysql_select_db($dbname, $link); 
}

function xf_db_free_result($result) {
  SQLLog::log("xf_db_free_result($result)");
  return mysql_free_result($result);
}

function xf_db_affected_rows($link=null) { 
  SQLLog::log("xf_db_affected_rows($link=null)");
  if ( $link === null ){
    return mysql_affected_rows($link);
  }
  return mysql_affected_rows($link);
}

function xf_db_fetch_lengths($result) {
  SQLLog::log("xf_db_fetch_lengths($result)");
  return mysql_fetch_lengths($result);
}

function xf_db_num_rows($result) {
  SQLLog::log("xf_db_num_rows($result)");
  return mysql_num_rows($result);
}
  
function xf_db_insert_id($link=null) { 
  SQLLog::log("xf_db_insert_id($link=null)");
  if ( $link === null ){
    $id = mysql_insert_id();
  SQLLog::log("  => id: $id");
    return($id);
  }
  $id = mysql_insert_id($link);
  SQLLog::log("  => id: $id");
  return($id);
}

function xf_db_data_seek($result, $offset) {
  SQLLog::log("xf_db_data_seek($result, $offset)");
  return mysql_data_seek($result, $offset);
}

function xf_db_character_set_name($link=null) { 
  SQLLog::log("xf_db_character_set_name($link=null)");
  if ( $link === null ){
    return mysql_character_set_name();
  }
  return mysql_character_set_name($link);
}

function xf_db_close($link=null) { 
  SQLLog::log("xf_db_close($link=null)");
  if ( $link === null ){
    return mysql_close();
  }
  return mysql_close($link);
}

function xf_db_get_server_info($link=null) { 
  SQLLog::log("xf_db_get_server_info($link=null)");
  if ( $link === null ){
  return mysql_get_server_info();
  }
  return mysql_get_server_info($link);
}
