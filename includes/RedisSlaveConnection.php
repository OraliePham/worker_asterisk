<?php (defined('REDIS') AND class_exists('Redis') ) OR exit('No direct script access allowed');

class RedisSlaveConnection {

    private $redis = null;

    private function __construct(){
        try {
            $this->redis = new Redis;
            $this->redis->connect(REDIS["HOST"], REDIS["PORT"]);
            if(REDIS["PASS"]) $this->redis->auth(REDIS["PASS"]);
            if(!$this->redis->ping()){
                throw new Exception("Ping to Redis server failed.");
            }
        } catch(Exception $ex) {
            show_error("Connect Redis Error: ".$ex->getMessage());	
        }
    }

    public function __destruct(){
        static::$instance = null;
        if($this->redis != null){
            $this->redis->close();
        }
    }

    private static $instance;

    public static function getInstance(){
        if(static::$instance==null){
            static::$instance = new RedisSlaveConnection();
        }
        return static::$instance;
    }

    public function exists($key){
        try {
            return $this->redis->exists($key);
        } catch(Exception $ex) {
            return false;
        }
        
    }

    public function get($key){
        try {
            return $this->redis->get($key);
        } catch(Exception $ex) {
            return false;
        }
        
    }

    public function set($key, $value, $timeout=false){
        try {
            $flag = $this->redis->set($key, $value);
            if($timeout){
                $this->redis->expire($key, $timeout);
            }
            return $flag;
        } catch(Exception $ex) {
            return false;
        }
    }

    public function del($key){
        try {
            return $this->redis->del($key);
        } catch(Exception $ex) {
            return false;
        }
    }
	
	public function hkeys($keys){
		try {
			return $this->redis->hKeys($keys);
		} catch (Exception $e){
			return false;
		}
	}
	
	public function hset($key, $field, $value){
		try {
			return $this->redis->hSet($key, $field, $value);
		} catch (Exception $e){
			return false;
		}
	}
	
	public function hget($key, $field){
		try {
			return $this->redis->hGet($key, $field);
		} catch (Exception $e){
			return false;
		}
	}
	
	public function hmset($key, $data = array()){
		try {
			if(!is_array($data)){
				throw new Exception("");
			}
			return $this->redis->hMSet($key, $data);
		} catch (Exception $e){
			return false;
		}
	}
	
	public function hmget($key, $data = array()){
		try {
			return $this->redis->hMGet($key, $data);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Add key -> value 
	public function sAdd($key, $value){
		try {
			return $this->redis->sAdd($key, $value);
		} catch (Exception $e){
			return false;
		}
	}
	
	//lat tat ca gia tri trong key dc them tu sAdd 
	public function sMembers($key){
		try {
			return $this->redis->sMembers($key);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Chuyen value tu key1 qua key2 (sAdd)
	public function sMove($key1,$key2,$value){
		try {
			return $this->redis->sMove($key1,$key2,$value);
		} catch (Exception $e){
			return false;
		}
	}
	
	//check value co trong key dc them tu sAdd 
	public function sIsMember($key, $value){
		try {
			return $this->redis->sIsMember($key, $value);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Lay data da add vao boi sAdd
	public function sInter($key){
		try {
			return $this->redis->sInter($key);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Lay random data da add vao boi sAdd
	public function sRandMember($key, $count=1){
		try {
			return $this->redis->sRandMember($key, $count);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Xoa value trong key da add vao boi sAdd
	public function sRem($key, ...$value){
		try {
			foreach($value as $item){
				$this->redis->sRem($key, $item);
			}
			return true;
		} catch (Exception $e){
			return false;
		}
	}
	//Xoa value trong key da add vao boi sAdd (tuong tu sRem va se bi xoa trong tuong lai)
	public function sRemove($key, ...$value){
		try {
			foreach($value as $item){
				$this->redis->sRem($key, $item);
			}
			return true;
		} catch (Exception $e){
			return false;
		}
	}
	
	//Increment key
	public function incr($key, $value=1){
		try {
			return $this->redis->incr($key, $value);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Increment member of key
	public function hIncrBy($key, $member, $value){
		try {
			return $this->redis->hIncrBy($key, $member, $value);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Check key có timeout hay ko
	public function ttl($key){
		try {
			return $this->redis->ttl($key);
		} catch (Exception $e){
			return false;
		}
	}
	
	//Set timeout key vào thoi gian timestamp
	public function expireAt($key, $timestamp){
		try {
			return $this->redis->expireAt($key, $timestamp);
		} catch (Exception $e){
			return false;
		}
	}

}