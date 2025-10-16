<?php defined('MEMCACHE') OR exit('No direct script access allowed');

class MemcacheConnection {

    private $memcache = null;

    private function __construct(){
        try {
			$this->memcache = new Memcached;
			$this->memcache->addServer(MEMCACHE["HOST"], MEMCACHE["PORT"]);
			$this->memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        } catch(Exception $ex) {
            die ("Could not connect to memcached server. ".$ex->getMessage());	
        }
    }

    public function __destruct(){
        try {
            static::$instance = null;
            if($this->memcache != null){
                $this->memcache->quit();
            }
        } catch(Exception $ex) {
            die ("Could not connect to memcached server. ".$ex->getMessage());  
        }
    }

    private static $instance;

    public static function getInstance(){
        if(static::$instance==null){
            static::$instance = new MemcacheConnection();
        }
        return static::$instance;
    }

    public function get($key){
        try {
            return $this->memcache->get($key);
        } catch(Exception $ex) {
            return false;
        }
        
    }

    public function set($key, $value, $timeout=5){
        try {
            return $this->memcache->set($key, $value, $timeout);
        } catch(Exception $ex) {
            return false;
        }
    }

    // public function delete($key){
        // try {
            // return $this->memcache->delete($key);
        // } catch(Exception $ex) {
            // return false;
        // }
    // }

}