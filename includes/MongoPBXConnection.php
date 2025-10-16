<?php defined('MONGODB') OR exit('No direct script access allowed');

require_once "MyMongoDriver.php";

class MongoPBXConnection {

	private static $instance = null;
	
	private  function __construct(){
	}
	
	public function __destruct(){
		//static::$instance = null;
	}
	
	public static function getInstance(){
		if(static::$instance==null){
			$connect_info=array(
				"mongo_host" => MONGODB["HOST"],
				"mongo_db"   => MONGODB["DBNAME"],
				"mongo_port" => MONGODB["PORT"],
				"mongo_user" => MONGODB["USER"],
				"mongo_pass" => MONGODB["PASS"]
			);
			
			static::$instance = new MyMongoDriver($connect_info);
		}
		
		return static::$instance;
	}
	
}