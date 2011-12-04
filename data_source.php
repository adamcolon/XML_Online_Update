<?php
require_once('error_handler.php');
if(!defined('DEBUG')) define('DEBUG', false);	// For verbose output, set this to true

/**
 * DataSource Class
 * This is a simple DataSource class that is a wrapper around mysql interaction
 * The constuct connects to the database given settings parameters
 * Query is used for reads returning an array
 * Execute is used for writes returning last id and rows affected
 * 
 * Requires the ErrorHandler Class as imported above
 * @author adamc
 *
 */
class DataSource{
	var $host = '';
	var $db_name = '';
	var $user = '';
	var $password = '';
	var $connection = null;

	/**
	 * 
	 * construct, grab settings and connect to database
	 * @param array $db_settings
	 */
	function __construct($db_settings){
		if(DEBUG) echo "[".__METHOD__."] Entered.\n";
		
		$this->host = $db_settings['host'];
		$this->db_name = $db_settings['db_name'];
		$this->user = $db_settings['user'];
		$this->password = $db_settings['password'];

		$this->connection = mysql_connect($this->host, $this->user, $this->password) or ErrorHandler::raise('Could not connect: ' . mysql_error($this->connection));
		if(DEBUG) echo "[".__METHOD__."] connected to {$this->host}.\n";

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		if(DEBUG) echo "[".__METHOD__."] Database Selected: {$this->db_name}.\n";
	}

	/**
	 * 
	 * close database connection
	 */
	function __destruct(){
		if($this->connection) mysql_close($this->connection);
	}

	/**
	 * 
	 * query the database and return a array of the resulting dataset
	 * @param string $sql
	 */
	function Query($sql){
		if(DEBUG) echo "[".__METHOD__."] Running {$sql}.\n";
		
		$dataset = array();

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		$result = mysql_query($sql, $this->connection) or ErrorHandler::raise('['.__METHOD__.'::'.__LINE__.'] Query failed: ' . mysql_error($this->connection), true);
		while($rs = mysql_fetch_array($result, MYSQL_ASSOC)){
			$dataset[] = $rs;
		}

		if(DEBUG) echo "[".__METHOD__."] Results: ".print_r($dataset, true)."\n";
		return $dataset;
	}

	/**
	 * 
	 * query the database and return the last inserted id and number of rows affected
	 * @param string $sql
	 * @return array('id'=><last inserted id>, 'rows_affected'=><number of rows affected>)
	 */
	function Execute($sql){
		if(DEBUG) echo "[".__METHOD__."] Running {$sql}.\n";

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		$result = mysql_query($sql, $this->connection) or ErrorHandler::raise('['.__METHOD__.'::'.__LINE__.'] Query failed: ' . mysql_error($this->connection), true);

		if(DEBUG) echo "[".__METHOD__."] Last Inserted Id: ".mysql_insert_id($this->connection).", Rows Affected:".mysql_affected_rows($this->connection)."\n";
		return array('id'=>mysql_insert_id($this->connection), 'rows_affected'=>mysql_affected_rows($this->connection));
	}

	/**
	 * 
	 * connection based wrapper for mysql_real_escape_string 
	 * @param string $string
	 */
	function escape_string($string){
		$result = $string;
		if($string){
			$result = mysql_real_escape_string($string, $this->connection);
		}
		return $result;
	}
}
?>