<?php (defined('REDIS_MASTER') AND class_exists('Redis') ) OR exit('No direct script access allowed');

class RedisMasterConnection {

    private $redis = null;

    private function __construct(){
        try {
            $this->redis = new Redis;
            $this->redis->connect(REDIS_MASTER["HOST"], REDIS_MASTER["PORT"]);
            if(REDIS_MASTER["PASS"]) $this->redis->auth(REDIS_MASTER["PASS"]);
            if(!$this->redis->ping()){
                throw new Exception("Ping to Redis server failed.");
            }
        } catch(Exception $ex) {
            error_log("Connect Redis Error: ".$ex->getMessage());	
            throw new RuntimeException($ex->getMessage());
        }
    }

    public function __destruct(){
        static::$instance = null;
        try {
	        if($this->redis != null){
	            $this->redis->close();
	        }
        } catch(Exception $ex) {
        }
    }

    private static $instance;

    public static function getInstance(){
        if(static::$instance==null){
            static::$instance = new RedisMasterConnection();
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
	
	//Add key -> value 
	public function sAdd($key, $value){
		try {
			return $this->redis->sAdd($key, $value);
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
	
	//Set timeout key vào thoi gian timestamp
	public function expireAt($key, $timestamp){
		try {
			return $this->redis->expireAt($key, $timestamp);
		} catch (Exception $e){
			return false;
		}
	}

	// Set TTL for key
	public function expire($key, $seconds){
		try {
			return $this->redis->expire($key, $seconds);
		} catch (Exception $e){
			return false;
		}
	}

	// Get TTL for key
	public function ttl($key){
		try {
			return $this->redis->ttl($key);
		} catch (Exception $e){
			return false;
		}
	}

	// Get all keys matching pattern
	public function keys($pattern){
		try {
			return $this->redis->keys($pattern);
		} catch (Exception $e){
			return false;
		}
	}

	// Ping Redis server
	public function ping(){
		try {
			return $this->redis->ping();
		} catch (Exception $e){
			return false;
		}
	}

	// Get Redis info
	public function info($section = null){
		try {
			if ($section) {
				return $this->redis->info($section);
			}
			return $this->redis->info();
		} catch (Exception $e){
			return false;
		}
	}

	// Set with options (for distributed locking)
	public function setWithOptions($key, $value, $options = []){
		try {
			// Handle NX (only if not exists) and EX (expire) options
			if (in_array('nx', $options) && isset($options['ex'])) {
				return $this->redis->set($key, $value, ['nx', 'ex' => $options['ex']]);
			} elseif (in_array('nx', $options)) {
				return $this->redis->set($key, $value, ['nx']);
			} elseif (isset($options['ex'])) {
				return $this->redis->setex($key, $options['ex'], $value);
			} else {
				return $this->redis->set($key, $value);
			}
		} catch (Exception $e){
			return false;
		}
	}

}