<?php
require_once('online_update_feed.php');

if(!empty($argv[1])){
	$filename = $argv[1];
	main($filename);
}else{
	die("usage: ".__FILE__." <full_path_to_xml_file>\n");
}

function main($filename){
	$db_settings = array(
		'host' => 'mysql.adamcolon.com'
		,'db_name' => 'adc_test'
		,'user' => 'adamc_test'
		,'password' => 'adctest01'
	);
	
	$onlineUpdateFeed = new OnlineUpdateFeed($db_settings, $filename);	// Instantiate
	$onlineUpdateFeed->updateState();	// Act on Data
	if($user_list = $onlineUpdateFeed->getOnlineUsers()){	// Print out online users
		foreach($user_list as $user_id){
			echo "{$user_id}\n";
		}
	}
	
}

?>