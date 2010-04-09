<?php

/**
	file: db.php
	function: Abstract layer to the database.
	flmod: 04.20.04 02:13 PM
	flmod-who: Vic Cherubini <vic@openglforums.com>
*/

/**
	CHANGELOG
	12.31.99
		* initial creation of class
	04.01.04
		* added $querytime variable
		* changed $this->database != "" to !empty($this->database) in connect()
		* changed $this->pconnect == 0 to $this->pconnect == false
		* added spaces and parentheses to conditional statements
		* removed all English error messages and replaced with false returns
		* changed isset()'s to !empty()'s and vice versa
		* added function getquerytime() to return $querytime
		* added a lot of comments
		* fixed $numconnections bug
		* added variable $maxconnections
		* added function setmaxconnections() 
		* added define()'s for return values for functions that return more than one possible value
		* changed return values in connect() and dbquery()
	04.20.04
		* added insertid()
	05.04.04
		* added getqueries()
		* added class level variable $numqueries
*/

define('ERROR_TOO_MANY_CONNECTIONS', -1, false);
define('ERROR_NO_LINK_ID', -2, false);
define('ERROR_FAILED_DB_SELECT', -3, false);
define('ERROR_EMPTY_QUERY', -4, false);
define('ERROR_FAILED_QUERY', false, false);

class db {
	// the name of the class
	var $classname = "db";
	
	// the database connection information
	// don't touch this
	var $database = "";
	var $server   = "";
	var $dbuser   = "";
	var $dbpass   = "";
	
	var $linkid;
	var $queryid;
	var $result;
		
	var $dbdata = array();
	
	var $pconnect = false;
	
	var $querystats = false;
	var $querytime = 0;
	
	var $querylist = array();
	
	// stop or not on errors
	// possible values: true - return the error and close the database connection
	// false - return the error, keep the connection open
	var $error_halt = false;
	
	var $numconnections = 0;
	var $maxconnections = 10;	// maximum connections allowed to the database
	
	var $numqueries = 0;
	
	// default constructor
	// $this_db : the name of the database to connec to
	// $this_server : the server name to connect to
	// $this_user : the username of the database
	// $this_pass : the password of the database
	function db($this_server, $this_db, $this_user, $this_pass) {
		$this->database = $this_db;
		$this->server = $this_server;
		$this->dbuser = $this_user;
		$this->dbpass = $this_pass;
	}
	
	// connect to the database
	function connect() {	
		if ( $this->numconnections < $this->maxconnections ) {
			if ( $this->pconnect == false ) {
				$this->linkid = mysql_connect($this->server, $this->dbuser, $this->dbpass);
			} else {
				$this->linkid = mysql_pconnect($this->server, $this->dbuser, $this->dbpass);
			}
		} else {
			return ERROR_TOO_MANY_CONNECTIONS;
		}
		
		if ( !($this->linkid) ) {
			return ERROR_NO_LINK_ID;
		}

		if ( !(empty($this->database)) ) {
			if ( !(mysql_select_db($this->database)) ) {
				return ERROR_FAILED_DB_SELECT;
			}
		}
		
		$this->numconnections++;
		return true;
	}
	
	// selects the database specified
	function selectdb() {
		return mysql_select_db($this->database);
	}

	// performs a query on the database and returns the query ID
	// $this_query : the query you want to perform
	function dbquery($this_query) {
		//static $query_num;	// this could probably be a class level variable

		if ( empty($this_query) ) {
			return ERROR_EMPTY_QUERY;
		}

		if ($this->querystats == 1) {
			$time_start = $this->gettime();
		}

		$this->result = mysql_query($this_query, $this->linkid);
		
		$this->numqueries++;
		array_push($this->querylist, $this_query);
		
		if ( $this->querystats == true ) {
			$time_end = $this->gettime();
			$this->querytime = ($time_end - $time_start);
		}
		
		if ( !($this->result) ) {
			return ERROR_FAILED_QUERY;
		} else {
			return $this->result;
		}
	}
	
	// frees the current memory from the database
	function freeresult($this_queryid) {
		return mysql_free_result($this_queryid);
	}
	
	// returns an array of data
	function getarray($this_queryid) {
		$data = array();

		if ( !empty($this_queryid) ) {
			$data = mysql_fetch_assoc($this_queryid);
			if ( empty($data) ) {
				return false;
			}
		}
		return $data;
	}
	
	// returns the fields of the latest query
	function getfields($this_queryid) {
		$fields = array();
		
		if ( $this_queryid != -1 ) {
			$numfields = $this->numfields($this_queryid);
			
			for ( $i=0; $i<$numfields; $i++ ) {
				$fields[$i] = mysql_field_name($this_queryid, $i);
			}
		}
		
		return $fields;
	}
				
	// jumps to a certain row after a query
	function jumpto($this_queryid, $this_row) {
		if ( !empty($this_row) && $this_queryid != -1 ) {
			return mysql_data_seek($this->result, $this_row);
		}
	}
	
	function insertid() {
		return mysql_insert_id();
	}
	
	// returns a row in an array of data
	// used in conjunction with $this->jumpto()	
	function getrow($this_queryid) {
		if ( !empty($this_queryid) ) {
			$my_data = mysql_fetch_row($this_queryid);
		}
		
		return $my_data;
	}
	
	// returns the number of rows in a specified query
	function numrows($this_queryid) {
		if ( $this_queryid != -1 ) {
			return mysql_num_rows($this_queryid);
		}
	}
	
	// returns the number of fields in $this_queryid
	function numfields($this_queryid) {
		if ( $this_queryid != -1 ) {
			return mysql_nufields($this_queryid);		
		}
	}
		
	// get a time of the query
	function gettime() {
		list($usec, $sec) = explode(" ", microtime()); 
		return ( (float)$usec + (float)$sec ); 
	}
	
	// close the connection
	function disconnect() {
		if ( $this->linkid != -1 ) {
			return mysql_close($this->linkid);
		}
	}
	
	// returns the number of connections to the database
	function getconnections() {
		return $this->numconnections;
	}
	
	// returns the time it took to perform the query
	function getquerytime() {
		return $this->querytime;
	}
	
	// returns the number of queries executed
	function getqueries() {
		return $this->numqueries;
	}
	
	function getquerylist() {
		for ( $i=0; $i<count($this->querylist); $i++ ) {
			print ($i+1) . " - " . $this->querylist[$i] . "<br />";
		}
	}
	
	// sets the maximum number of allowable connections
	function setmaxconnections($maxconns) {
		if ( is_int($maxconns) ) {
			$this->maxconnections = $maxconns;
			return true;
		}
		
		return false;
	}
	
	// saves the error from mysql_error and returns it
	function dberror() {
		if ( $this->error_halt == true ) {
			$this->disconnect();
		}
		
		return mysql_error();
	}
}

?>