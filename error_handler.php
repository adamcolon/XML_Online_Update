<?php
/**
 * ErrorHandler Class
 * This is a simple error handler
 * It's a static that you simply call ErrorHandler::raise($message) to log and display an error message 
 * with an options break to halt execution of the script
 * @author adamc
 *
 */
class ErrorHandler{
	public static $log_file = 'error.log';
	
	/**
	 * raise Static Method
	 * raise an error, echo error message and output to the error log
	 * die if break==true
	 * @param string $message
	 * @param bool $break
	 */
	public static function raise($message, $break = false){
		echo "{$message}\n";
		
		$message = "[".date('Y-m-d H:i:s')."] {$message}";
		if(file_put_contents(self::$log_file, $message) === false){
			echo "!* Failed to Write Error to Log [".self::$log_file."]\n";
		}
		if($break) die();
	}
}
?>