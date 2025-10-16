<?php defined('DB') OR exit('No direct script access allowed');

class MysqliConnection {

    private $mysqli = null;

    private function __construct(){
        $this->mysqli = new mysqli(DB["HOST"], DB["USER"], DB["PASS"], DB["DBNAME"]);
        if ($this->mysqli->connect_errno) {
            printf("Connect failed: %s\n", $this->mysqli->connect_error);
            exit();
        }
		if (!$this->mysqli->set_charset("utf8")) {
			printf("Error loading character set utf8: %s\n", $this->mysqli->error);
			exit();
		}
    }

    public function __destruct(){
        static::$instance = null;
        if($this->mysqli != null){
            $this->mysqli->close();
        }
    }

    private static $instance;

    public static function getInstance(){
        if(static::$instance==null){
            static::$instance = new MysqliConnection();
        }
        return static::$instance;
    }

    public function query($sql){
        return $this->mysqli->query($sql);
    }

    public function count($sql){
        try {
            $result = $this->mysqli->query($sql);
            $response = $result->num_rows;
            $result->close();
            return $response;
        } catch(Exception $ex){
            return false;
        }
    }

    public function getRow($sql){
        try {
            $response = array();
            $result = $this->mysqli->query($sql);
            if($result->num_rows&&($row = $result->fetch_assoc())){
                $response = $row;
            }
            $result->close();			
            return $response;
        } catch(Exception $ex){
			echo $ex->getMessage();
            return false;
        }
    }

    public function get($sql){
        try {
            $response = array();
            $result = $this->mysqli->query($sql);
            if($result->num_rows){
                while($row = $result->fetch_assoc()){
                    $response[] = $row;
                }
            }
            $result->close();
            return $response;
        } catch(Exception $ex){
            return false;
        }
    }
	
	public function escape($str, $like = false) {
		if (is_array($str)) {
			foreach ($str as $key => $val) {
				$str[$key] = $this->escape($val, $like);
			}
			return $str;
		}

		if($this->mysqli instanceof mysqli &&  method_exists($this->mysqli, 'real_escape_string')){
			$str = $this->mysqli->real_escape_string($str);
		} else {
			$str = addslashes($str);
		}
		// escape LIKE condition wildcards
		if ($like) {
			$str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
		}
		return $str;
	} 
}