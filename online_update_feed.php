<?php 
if(!defined('DEBUG')) define('DEBUG', true);	// For verbose output, set this to true

require_once('error_handler.php');
require_once('data_source.php');

/**
 * 
 * OnlineUpdateFeed Class
 * takes db_settings and a filename for an xml file to process
 * construct loads the xml file
 * updateState will process the data from the xml file (moving users from offline to online... etc.)
 * getOnlineUsers will return the current list of online users
 * @author adamc
 *
 */
class OnlineUpdateFeed {
	var $db = null;
	var $feed_type = null;
	var $online_list = array();
	var $offline_list = array();
	
	/**
	 * 
	 * Instatntiates Class and loads XML File into class properties
	 * @param array $db_settings
	 * @param string $filename
	 */
	function __construct($db_settings, $filename){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		$this->db = new DataSource($db_settings) or ErrorHandler::raise(__METHOD__.' ['.__LINE__."] Failed To Connect to Databse.", true);
		
		if(!file_exists($filename)) ErrorHandler::raise(__METHOD__.' ['.__LINE__."] XML File Does Not Exist [{$filename}]", true);
		if(!is_file($filename)) ErrorHandler::raise(__METHOD__.' ['.__LINE__."] XML File Is Not a File [{$filename}]", true);
		
		$this->parseXml($filename);
		$this->validateData();
	}
	
	/**
	 * 
	 * Reads XML file and loads data into class properties
	 * Note: Using DOMDocument and DOMXPath instead of SimpleXML per Requested Spec
	 * @param string $filename
	 */
	function parseXml($filename){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		$xml = new DOMDocument( "1.0", "ISO-8859-1" );
    	$xml->load($filename) or ErrorHandler::raise(__METHOD__.' ['.__LINE__."] Failed to open XML File [{$filename}].", true);
    	$xpath = new DOMXPath($xml);

	    $element = $xpath->query("/presence/@type");
	    // I don't like the way I have to loop over the query of a single attribute, but it works... I'm going to circle back and refactor this later
	    foreach ($element as $node) {
	    	switch ($node->nodeValue){
	    		case 'd':
	    			$this->feed_type = 'delta';
	    			break;
	    		case 'f':
	    			$this->feed_type = 'full';
	    			break;
	    	}
	    	
			break; // foreach
		}
		
		$elements = $xpath->query("/presence/on/u");
    	foreach ($elements as $node) {
		    $this->online_list[] = $node->nodeValue;
		}

	    $elements = $xpath->query("/presence/off/u");
    	foreach ($elements as $node) {
		    $this->offline_list[] = $node->nodeValue;
		}
	}
	
	/**
	 * 
	 * Error when feed_type is missing or there's no data to act on
	 */
	function validateData(){
		if(empty($this->feed_type) || (empty($this->online_list) && empty($this->offline_list))){
			ErrorHandler::raise(__METHOD__.' ['.__LINE__."] Failed Validation, Nothing To Do [{$this->feed_type}]", true);
		}
	}
	
	/**
	 * Public
	 * Update Online/Offline State in Datasource
	 */
	public function updateState(){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
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
	
	/**
	 * 
	 * returns an array of currently online users
	 */
	public function getOnlineUsers(){
		$table = 'users_online';
		return $this->getStateList($table);
	}
	
	/**
	 * 
	 * Used for a full refresh, wipes both tables
	 */
	function clearState(){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		$sql = "DELETE FROM `users_online`;";
		$this->db->Execute($sql);
		
		$sql = "DELETE FROM `users_offline`;";
		$this->db->Execute($sql);
	}
	
	/**
	 * 
	 * Make the users in the user list online (this includes taking them off the offline list)
	 * @param array $user_list
	 * @param bool $validate_state
	 */
	function makeOnline($user_list, $validate_state = false){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		if(!empty($user_list)){
			if($validate_state){
				foreach($user_list as $user_id){
					if($this->inStateList('users_online', $user_id)) ErrorHandler::raise("User Already Found Online [{$user_id}]");
					if(!$this->inStateList('users_offline', $user_id)) ErrorHandler::raise("User Not Previously Offline [{$user_id}]");
				}
			}
			
			$this->insertState('users_online', $user_list);
			$this->removeState('users_offline', $user_list);
		}
	}

	/**
	 * 
	 * Make the users in the user list offline (this includes taking them off the online list)
	 * @param array $user_list
	 * @param bool $validate_state
	 */
	function makeOffline($user_list, $validate_state = false){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		if(!empty($user_list)){
			if($validate_state){
				foreach($user_list as $user_id){
					if($this->inStateList('users_offline', $user_id)) ErrorHandler::raise("User Already Found Offline [{$user_id}]");
					if(!$this->inStateList('users_online', $user_id)) ErrorHandler::raise("User Not Previously Online [{$user_id}]");
				}
			}
			
			$this->insertState('users_offline', $user_list);
			$this->removeState('users_online', $user_list);
		}
	}
	
	/**
	 * 
	 * Insert user list into the specified table
	 * @param string $table
	 * @param array $user_list
	 */
	function insertState($table, $user_list){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		if(!empty($user_list)){
			$values = '('.implode('),(', $user_list).')';
			$sql = "REPLACE INTO `{$table}` (`user_id`) VALUES {$values};";

			$result = $this->db->Execute($sql);
			return $result['rows_affected'];
		}
	}
	
	/**
	 * 
	 * Remove user list from the specified table
	 * @param string $table
	 * @param array $user_list
	 */
	function removeState($table, $user_list){
		if(DEBUG) echo "[".__METHOD__."] Running.\n";
		
		if(!empty($user_list)){
			$count = count($user_list);
			$id_list = implode(',', $user_list);
			$sql = "DELETE FROM `{$table}` WHERE user_id IN ({$id_list}) LIMIT {$count};";

			$result = $this->db->Execute($sql);
			return $result['rows_affected'];
		}
	}
	
	/**
	 * 
	 * get all users from a specified table
	 * @param string $table
	 * @return array $user_list
	 */
	function getStateList($table){
		$user_list = array();
		
		$sql = "SELECT * FROM `{$table}`;";
		$results = $this->db->query($sql);
		foreach($results as $result){
			$user_list[] = $result['user_id'];
		}
		
		return $user_list;
	}
	
	/**
	 * 
	 * return whether the given user_id is in the specified table
	 * @param string $table
	 * @param int $user_id
	 * @return boolean
	 */
	function inStateList($table, $user_id){
		$sql = "SELECT * FROM `{$table}` WHERE user_id={$user_id};";
		if($this->db->query($sql)){
			return true;
		}
	}
}

?>