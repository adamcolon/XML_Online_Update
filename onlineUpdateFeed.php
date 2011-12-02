<?php
define('DEBUG', true);

if(!empty($argv[0])){
	$filename = $argv[0];
	main($filename);
}else{
	die("usage".__FILE__." <full_path_to_xml_file>\n");
}

function main($filename){
	$db_settings = array(
		'host' => 'localhost'
		,'db_name' => 'mirror_test'
		,'user' => 'mirror'
		,'password' => 'm1rr0r!'
	);
	
	$onlineUpdateFeed = new OnlineUpdateFeed($db_settings, $filename);
}

class OnlineUpdateFeed {
	var $db = null;
	var $feed_type = null;
	var $online_list = array();
	var $offline_list = array();
	
	function __construct($db_settings, $filename){
		$this->db = new DataSource($db_settings) or ErrorHandler::raise(__METHOD__.' ['.__LINE__."] Failed To Connect to Databse.", true);
		
		if(!file_exists($filename)) ErrorHandler::raise(__METHOD__.' ['.__LINE__."] XML File Does Not Exist [{$filename}]", true);
		if(!is_file($filename)) ErrorHandler::raise(__METHOD__.' ['.__LINE__."] XML File Is Not a File [{$filename}]", true);
		
		$this->parseXml($filename);
	}
	
	function parseXml($filename){
		$this->feed_type = 'delta|full';
		$this->online_list = array();
		$this->offline_list = array();
		
		// Do the parsing here
	}
	
	function updateState(){
		if($this->feed_type == 'full'){
			$this->clearState();
			$this->makeOnline($this->online_list);
			$this->makeOffline($this->offline_list);
		}elseif($this->feed_type == 'delta'){
			$validate = true;
			$this->makeOnline($this->online_list, $validate);
			$this->makeOffline($this->offline_list, $validate);
		}else{
			ErrorHandler::raise(__METHOD__.' ['.__LINE__."] Unknown Feed Type or Type Not Found [{$this->feed_type}]", true);
		}
	}
	
	function clearState(){
		$sql = "DELETE FROM `online`;"
		$this->db->Execute($sql);
		
		$sql = "DELETE FROM `offline`;"
		$this->db->Execute($sql);
	}
	
	function makeOnline($user_list, $validate_state = false){
		if(!empty($user_list)){
			if($validate_state){
				foreach($user_list as $user){
					//if no offline record or online record exists, spit out a non-critical error
					//ErrorHandler::raise($message);
				}
			}
			
			$this->insertState('online', $user_list);
			$this->removeState('offline', $user_list);
		}
	}

	function makeOffline($user_list, $validate_state = false){
		if(!empty($user_list)){
			if($validate_state){
				foreach($user_list as $user){
					//if no online record or offline record exists, spit out a non-critical error
					//ErrorHandler::raise($message);
				}
			}
			
			$this->insertState('offline', $user_list);
			$this->removeState('online', $user_list);
		}
	}
	
	function insertState($table, $user_list){
		if(!empty($user_list)){
			$values = '('.implode('),(', $user_list).')';
			$sql = "REPLACE INTO `{$table}` (`user_id`) VALUES {$values};"

			$result = $this->db->Execute($sql);
			return $result['rows_affected'];
		}
	}
	
	function removeState($table, $user_list){
		if(!empty($user_list)){
			$count = count($user_list);
			$id_list = implode(',', $user_list);
			$sql = "DELETE FROM `{$table}` WHERE user_id IN ({$id_list}) LIMIT {$count}'";

			$result = $this->db->Execute($sql);
			return $result['rows_affected'];
		}
	}
}

class DataSource{
	var $host = '';
	var $db_name = '';
	var $user = '';
	var $password = '';
	var $connection = null;

	function __construct($db_settings){
		$this->host = $db_settings['host'];
		$this->db_name = $db_settings['db_name'];
		$this->user = $db_settings['user'];
		$this->password = $db_settings['password'];

		$this->connection = mysql_connect($this->host, $this->user, $this->password) or ErrorHandler::raise('Could not connect: ' . mysql_error($this->connection));
		if(DEBUG) echo __METHOD__." connected to {$this->host}.<br/>";

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		if(DEBUG) echo __METHOD__." Database Selected: {$this->db_name}.<br/>";
	}

	function __destruct(){
		if($this->connection) mysql_close($this->connection);
	}

	function Query($sql){
		if(DEBUG) echo __METHOD__." Running: {$sql}.<br/>";
		$dataset = array();

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		$result = mysql_query($sql, $this->connection) or ErrorHandler::raise(('['.__METHOD__.'::'.__LINE__.'] Query failed: ' . mysql_error($this->connection), true);
		while($rs = mysql_fetch_array($result, MYSQL_ASSOC)){
			$dataset[] = $rs;
		}

		if(DEBUG) echo __METHOD__." Results:".print_r($dataset, true)."<br/>";
		return $dataset;
	}

	function Execute($sql){
		if(DEBUG) echo __METHOD__." Running: {$sql}<br/>.";

		mysql_select_db($this->db_name, $this->connection) or ErrorHandler::raise("Could not select database [{$this->db_name}]". mysql_error($this->connection), true);
		$result = mysql_query($sql, $this->connection) or ErrorHandler::raise('['.__METHOD__.'::'.__LINE__.'] Query failed: ' . mysql_error($this->connection), true);

		if(DEBUG) echo __METHOD__." Last Inserted Id: ".mysql_insert_id($this->connection).", Rows Affected:".mysql_affected_rows($this->connection)."<br/>";
		return array('id'=>mysql_insert_id($this->connection), 'rows_affected'=>mysql_affected_rows($this->connection));
	}

	function escape_string($string){
		$result = $string;
		if($string){
			$result = mysql_real_escape_string($string, $this->connection);
		}
		return $result;
	}
}

class ErrorHandler{
	public static $log_file = dirname(__FILE__).'/error.log';
	
	public static function raise($message, $break = false){
		echo "{$message}\n";
		if(file_put_contents(self::$log_file, $message) === false){
			echo "!* Failed to Write Error to Log [".self::$log_file."]\n";
		}
		if($break) die($message);
	}
}
?>