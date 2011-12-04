<?php
define('ENTRYPOINT', true);

require_once('db_settings.php');
require_once('online_update_feed.php');

if(!empty($argv[1])){
	$filename = $argv[1];
	main($db_settings, $filename);
}else{
	die("usage: ".__FILE__." <full_path_to_xml_file>\n");
}

function main($db_settings, $filename){
	$onlineUpdateFeed = new OnlineUpdateFeed($db_settings, $filename);	// Instantiate
	$onlineUpdateFeed->updateState();	// Act on Data
	
	if($user_list = $onlineUpdateFeed->getOnlineUsers()){	// Print out online users
		if(defined('DEBUG') && DEBUG) echo "Debug is ON.\n-----Results-----\n";
		foreach($user_list as $user_id){
			echo "{$user_id}\n";
		}
	}
	
}

?>
