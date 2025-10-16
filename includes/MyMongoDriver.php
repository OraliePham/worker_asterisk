<?php
//DEV PHAMTON
function show_error($message, $timeout = 500) {
	throw new Exception("{$message} {$timeout}");
}

class MyMongoDriver {

	private $CI;
	private $config = array();
	private $param = array();
	private $connect;
	private $db;
	private $version = 3.6;
	private $hostname;
	private $port;
	private $database;
	private $username;
	private $password;
	private $debug = TRUE;
	private $write_concerns = 1;
	private $legacy_support = TRUE;
	private $read_concern = 'majority';
	private $read_preference = 'nearest';
	private $journal  = FALSE;
	private $selects = array();
	private $updates = array();
	private $wheres	= array();
	private $limit	= 999999;
	private $offset	= 0;
	private $sorts	= array();
	private $return_as = 'array';
	public $benchmark = array();

	/**
	* --------------------------------------------------------------------------------
	* Class Constructor
	* --------------------------------------------------------------------------------
	*
	* Automatically check if the Mongo PECL extension has been installed/enabled.
	* Get Access to all CodeIgniter available resources.
	* Load mongodb config file from application/config folder.
	* Prepare the connection variables and establish a connection to the MongoDB.
	* Try to connect on MongoDB server.
	*/

	function __construct($mongodbname, $mongohost = 'localhost', $mongoport = 27017)
	{

		if ( ! class_exists('MongoDB\Driver\Manager'))
		{
			show_error("The MongoDB PECL extension has not been installed or enabled", 500);
		}
		
		if(is_array($mongodbname) ){
            $this->hostname = $mongodbname['mongo_host'];
            $this->database = $mongodbname['mongo_db'];
            $this->port 	= $mongodbname['mongo_port'];
            $this->username = $mongodbname['mongo_user'];
            $this->password = $mongodbname['mongo_pass'];
        } else {
            $this->hostname = $mongohost;
            $this->database = $mongodbname;
            $this->port = $mongoport;
        }
		
		$this->connect();
	}

	/**
	* --------------------------------------------------------------------------------
	* Class Destructor
	* --------------------------------------------------------------------------------
	*
	* Close all open connections.
	*/
	// function __destruct()
	// {
	// 	if(is_object($this->connect))
	// 	{
	// 		$this->connect->close();
	// 	}
	// }

	/**
	* --------------------------------------------------------------------------------
	* Connect to MongoDB Database
	* --------------------------------------------------------------------------------
	* 
	* Connect to mongoDB database or throw exception with the error message.
	*/

	private function connect()
	{
		try
		{
			$dns = "mongodb://";
			
			if (!empty($this->username) && !empty($this->password)) {
				$dns .= "{$this->username}:{$this->password}@";
			}
			
			if (!empty($this->port)) {
				$dns .= "{$this->hostname}:{$this->port}";
			} else {
				$dns .= "{$this->hostname}";
			}
			
			$dns .= "/{$this->database}";
			
			$options = array();
			if(!empty($this->username) && !empty($this->password))
			{
				$dns .= "?authSource=admin";
				$options["socketTimeoutMS"] = 60000;
			}
			$this->connect = $this->db = new MongoDB\Driver\Manager($dns, $options);
		}
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Unable to connect to MongoDB: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Unable to connect to MongoDB", 500);
			}
		}
	}

	// public function creteCollectionLockClusteredIndex() {
	// 	$command = [
	// 	 'create' => 'documemt_lock',
	// 	 'clusteredIndex' => [
	// 	  'key' => ['uniqueid_peername' => 1],
	// 	  'unique' => true,
	// 	  'name' => 'peername_clustered_index'
	// 	 ]
	// 	];
	// 	if($this->command($command)){
	// 	 return true;
	// 	}
	// 	return false;
	//    }

	public function creteCollectionLockClusteredIndex() {
		$command = [
			'key' => ['uniqueid_peername' => 1],
			'unique' => true,
		];
		if($this->command('documemt_lock',$command)){
			return true;
		}
		return false;
	}
	/**
	* --------------------------------------------------------------------------------
	* //! Insert
	* --------------------------------------------------------------------------------
	*
	* Insert a new document into the passed collection
	*
	* @usage : $this->mongo_db->insert('foo', $data = array());
	*/


	/**
	* --------------------------------------------------------------------------------
	* //! findOneAndUpdate USE mongo v7.0
	* --------------------------------------------------------------------------------
	*
	* Insert a new document into the passed collection
	* $filter = [ 'restaurant_id' => '40361708' ];
	* $update = [ 'address.building' => '761' ];
	* $options = ['projection' => [ 'address' => 1 ],'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER];
	* @usage : $this->mongo_db->findOneAndUpdate('restaurants', $filter, $update, $options);
	*/

	public function findOneAndUpdate($collectionName, $filter, $update, $options = [])
	{
		try {
			// Tạo BulkWrite để cập nhật tài liệu
			$bulk = new MongoDB\Driver\BulkWrite;
			$bulk->update($filter, ['$set' => $update], ['multi' => false, 'upsert' => false]);

			// Thiết lập WriteConcern cho độ tin cậy ghi dữ liệu
			$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
			$this->connect->executeBulkWrite("{$this->database}.{$collectionName}", $bulk, $writeConcern);

			// Truy vấn lại tài liệu đã cập nhật để lấy kết quả cuối cùng
			$query = new MongoDB\Driver\Query($filter, array_merge(['limit' => 1], $options));
			$rows = $this->connect->executeQuery("{$this->database}.{$collectionName}", $query);

			return current($rows->toArray());
		} catch (MongoDB\Driver\Exception\Exception $e) {
			if ($this->debug) {
				die("Error updating document: {$e->getMessage()}");
			}
			return null;
		}
	}


	public function insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to insert into", 500);
		}

		if (!is_array($insert) || count($insert) == 0)
		{
			show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
		}

		if(isset($insert['_id']) === false)
		{
			$insert['_id'] = new MongoDB\BSON\ObjectId;
		}

		$bulk = new MongoDB\Driver\BulkWrite();
		$bulk->insert($insert);
			
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$write = $this->db->executeBulkWrite($this->database.".".$collection, $bulk, $writeConcern);
			return $this->convert_document_id($insert);
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Insert of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Batch Insert
	* --------------------------------------------------------------------------------
	*
	* Insert a multiple document into the collection
	*
	* @usage : $this->mongo_db->batch_insert('foo', $data = array());
	* @return : bool or array : if query fail then false else array of _id successfully inserted.
	*/
	public function batch_insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to insert into", 500);
		}

		if (!is_array($insert) || count($insert) == 0)
		{
			show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
		}

		$doc = new MongoDB\Driver\BulkWrite();

		foreach ($insert as $ins) 
		{
			if(isset($ins['_id']) === false)
			{
				$ins['_id'] = new MongoDB\BSON\ObjectId;
			}
			$doc->insert($ins);
		}
		
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$result = $this->db->executeBulkWrite($this->database.".".$collection, $doc, $writeConcern);
			return $result;
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Insert of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Select
	* --------------------------------------------------------------------------------
	*
	* Determine which fields to include OR which to exclude during the query process.
	* If you want to only choose fields to exclude, leave $includes an empty array().
	*
	* @usage: $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
	*/
	public function select($includes = array(), $excludes = array())
	{
		if ( ! is_array($includes))
		{
			$includes = array();
		}
		if ( ! is_array($excludes))
		{
			$excludes = array();
		}
		if ( ! empty($includes))
		{
			foreach ($includes as $key=> $col)
			{
				if(is_array($col)){
					//support $elemMatch in select
					$this->selects[$key] = $col;
				}else{
					$this->selects[$col] = 1;
				}
			}
		}
		if ( ! empty($excludes))
		{
			foreach ($excludes as $col)
			{
				$this->selects[$col] = 0;
			}
		}
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Where
	* --------------------------------------------------------------------------------
	*
	* Get the documents based on these search parameters. The $wheres array should
	* be an associative array with the field as the key and the value as the search
	* criteria.
	*
	* @usage : $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
	*/
	public function where($wheres, $value = null)
	{
		if (is_array($wheres))
		{
			foreach ($wheres as $wh => $val)
			{
				$this->wheres[$wh] = $val;
			}
		}
		else
		{
			$this->wheres[$wheres] = $value;
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* or where
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field may be something else
	*
	* @usage : $this->mongo_db->where_or(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
	*/
	public function where_or($wheres = array())
	{
		if (is_array($wheres) && count($wheres) > 0)
		{
			if ( ! isset($this->wheres['$or']) || ! is_array($this->wheres['$or']))
			{
				$this->wheres['$or'] = array();
			}
			foreach ($wheres as $wh => $val)
			{
				$this->wheres['$or'][] = array($wh=>$val);
			}
			return ($this);
		}
		else
		{
			show_error("Where value should be an array.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Where in
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is in a given $in array().
	*
	* @usage : $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_in($field = "", $in = array())
	{
		if (empty($field))
		{
			show_error("Mongo field is require to perform where in query.", 500);
		}

		if (is_array($in) && count($in) > 0)
		{
			$this->_w($field);
			$this->wheres[$field]['$in'] = $in;
			return ($this);
		}
		else
		{
			show_error("in value should be an array.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Where in all
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is in all of a given $in array().
	*
	* @usage : $this->mongo_db->where_in_all('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_in_all($field = "", $in = array())
	{
		if (empty($field))
		{
			show_error("Mongo field is require to perform where all in query.", 500);
		}

		if (is_array($in) && count($in) > 0)
		{
			$this->_w($field);
			$this->wheres[$field]['$all'] = $in;
			return ($this);
		}
		else
		{
			show_error("in value should be an array.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Where not in
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is not in a given $in array().
	*
	* @usage : $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_not_in($field = "", $in = array())
	{
		if (empty($field))
		{
			show_error("Mongo field is require to perform where not in query.", 500);
		}

		if (is_array($in) && count($in) > 0)
		{
			$this->_w($field);
			$this->wheres[$field]['$nin'] = $in;
			return ($this);
		}
		else
		{
			show_error("in value should be an array.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Where greater than
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is greater than $x
	*
	* @usage : $this->mongo_db->where_gt('foo', 20);
	*/
	public function where_gt($field = "", $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform greater then query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform greater then query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$gt'] = $x;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where greater than or equal to
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is greater than or equal to $x
	*
	* @usage : $this->mongo_db->where_gte('foo', 20);
	*/
	public function where_gte($field = "", $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform greater then or equal query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform greater then or equal query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where less than
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is less than $x
	*
	* @usage : $this->mongo_db->where_lt('foo', 20);
	*/
	public function where_lt($field = "", $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform less then query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform less then query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$lt'] = $x;
		return($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where less than or equal to
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is less than or equal to $x
	*
	* @usage : $this->mongo_db->where_lte('foo', 20);
	*/
	public function where_lte($field = "", $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform less then or equal to query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform less then or equal to query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$lte'] = $x;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where between
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is between $x and $y
	*
	* @usage : $this->mongo_db->where_between('foo', 20, 30);
	*/
	public function where_between($field = "", $x, $y)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform greater then or equal to query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's start value is require to perform greater then or equal to query.", 500);
		}

		if (!isset($y))
		{
			show_error("Mongo field's end value is require to perform greater then or equal to query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where between and but not equal to
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is between but not equal to $x and $y
	*
	* @usage : $this->mongo_db->where_between_ne('foo', 20, 30);
	*/
	public function where_between_ne($field = "", $x, $y)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform between and but not equal to query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's start value is require to perform between and but not equal to query.", 500);
		}

		if (!isset($y))
		{
			show_error("Mongo field's end value is require to perform between and but not equal to query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where not equal
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is not equal to $x
	*
	* @usage : $this->mongo_db->where_ne('foo', 1)->get('foobar');
	*/
	public function where_ne($field = '', $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform Where not equal to query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform Where not equal to query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$ne'] = $x;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* Where id
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a _id equal to ObjectId($id)
	*
	* @usage : $this->mongo_db->where_id('9dfs12312dsfsd121sde')->get('foobar');
	*/
	function where_id($id)
    {
        $this->wheres["_id"] = new MongoDB\BSON\ObjectId($id);
        return $this;
    }

    /**
	* --------------------------------------------------------------------------------
	* Where object id
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a field equal to ObjectId($id)
	*
	* @usage : $this->mongo_db->where_id('other_id', '9dfs12312dsfsd121sde')->get('foobar');
	*/
	function where_object_id($field, $id)
    {
        $this->wheres[$field] = new MongoDB\BSON\ObjectId($id);
        return $this;
    }

	/**
	* --------------------------------------------------------------------------------
	* Like
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the (string) value of a $field is like a value. The defaults
	* allow for a case-insensitive search.
	*
	* @param $flags
	* Allows for the typical regular expression flags:
	* i = case insensitive
	* m = multiline
	* x = can contain comments
	* l = locale
	* s = dotall, "." matches everything, including newlines
	* u = match unicode
	*
	* @param $enable_start_wildcard
	* If set to anything other than TRUE, a starting line character "^" will be prepended
	* to the search value, representing only searching for a value at the start of
	* a new line.
	*
	* @param $enable_end_wildcard
	* If set to anything other than TRUE, an ending line character "$" will be appended
	* to the search value, representing only searching for a value at the end of
	* a line.
	*
	* @usage : $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
	*/
	public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = FALSE, $enable_end_wildcard = FALSE)
	{
		if (empty($field))
		{
			show_error("Mongo field is require to perform like query.", 500);
		}

		if (empty($value))
		{
			show_error("Mongo field's value is require to like query.", 500);
		}

		$field = (string) trim($field);
		$this->_w($field);
		$value = (string) trim($value);
		$value = quotemeta($value);
		if ($enable_start_wildcard)
		{
			$value = "^" . $value;
		}
		if ($enable_end_wildcard)
		{
			$value .= "$";
		}
		$regex = $value;
		$this->wheres[$field] = array('$regex' => $regex, '$options' => $flags);;
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Get
	* --------------------------------------------------------------------------------
	*
	* Get the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->get('foo');
	*/
	public function get($collection = "")
	{			
		if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}

		try{	

			$read_concern    = new MongoDB\Driver\ReadConcern($this->read_concern);
			$read_preference = new MongoDB\Driver\ReadPreference($this->read_preference);

			$options = array();
			$options['projection'] = $this->selects;
			$options['sort'] = $this->sorts;
			$options['skip'] = (int) $this->offset;
			$options['limit'] = (int) $this->limit;
			$options['readConcern'] = $read_concern;

			$query = new MongoDB\Driver\Query($this->wheres, $options);
			$cursor = $this->db->executeQuery($this->database.".".$collection, $query, $read_preference);
			if ($this->return_as == 'array') {
				$cursor->setTypeMap(['root'=>'array','document' =>'array','array'=>'array']);
			}
			// Clear
			$this->_clear();
			$returns = array();
			
			if ($cursor instanceof MongoDB\Driver\Cursor)
			{
				$it = new \IteratorIterator($cursor);
				$it->rewind();

				while ($doc = (array)$it->current())
				{
					if ($this->return_as == 'object')
					{
						$returns[] = (object) $this->convert_document_id($doc);
					}
					else
					{
						$returns[] = (array) $this->convert_document_id($doc);
					}
					$it->next();
				}
			}

			if ($this->return_as == 'object')
			{
				return (object)$returns;
			}
			else
			{
				return $returns;
			}
		}
		catch (MongoDB\Driver\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB query failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB query failed.", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* // Get where
	* --------------------------------------------------------------------------------
	*
	* Get the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->get_where('foo', array('bar' => 'something'));
	*/
	public function get_where($collection = "", $where = array())
	{
		if (is_array($where) && count($where) > 0)
		{
			return $this->where($where)
			->get($collection);
		}
		else
		{
			show_error("Nothing passed to perform search or value is empty.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* // Find One
	* --------------------------------------------------------------------------------
	*
	* Get the single document based upon the passed parameters
	*
	* @usage : $this->mongo_db->find_one('foo');
	*/
	public function find_one($collection = "")
	{

		if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}

		try{	

			$read_concern    = new MongoDB\Driver\ReadConcern($this->read_concern);
			$read_preference = new MongoDB\Driver\ReadPreference($this->read_preference);

			$options = array();
			$options['projection'] = $this->selects;
			$options['sort'] = $this->sorts;
			$options['skip'] = (int) $this->offset;
			$options['limit'] = (int) 1;
			$options['readConcern'] = $read_concern;
			
			$query = new MongoDB\Driver\Query($this->wheres, $options);
			$cursor = $this->db->executeQuery($this->database.".".$collection, $query, $read_preference);
			if ($this->return_as == 'array') {
				$cursor->setTypeMap(['root'=>'array','document' =>'array','array'=>'array']);
			}

			// Clear
			$this->_clear();
			$returns = array();
			
			if ($cursor instanceof MongoDB\Driver\Cursor)
			{
				$it = new \IteratorIterator($cursor);
				$it->rewind();

				while ($doc = (array)$it->current())
				{
					if ($this->return_as == 'object')
					{
						$returns[] = (object) $this->convert_document_id($doc);
					}
					else
					{
						$returns[] = (array) $this->convert_document_id($doc);
					}
					$it->next();
				}
			}

			if ($this->return_as == 'object')
			{
				return (object)$returns;
			}
			else
			{
				return $returns;
			}
		}
		catch (MongoDB\Driver\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB query failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB query failed.", 500);
			}
		}
	}

	public function getOne($collection = "") 
	{
		$data =	$this->find_one($collection);
		return $data ? $data[0] : null;
	}
	/**
	* --------------------------------------------------------------------------------
	* Count
	* --------------------------------------------------------------------------------
	*
	* Count the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->count('foo');
	*/
	public function count($collection = "") 
	{
		if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}

		try{	

			$read_concern    = new MongoDB\Driver\ReadConcern($this->read_concern);
			$read_preference = new MongoDB\Driver\ReadPreference($this->read_preference);

			$options = array();
			$options['projection'] = array('_id'=>1);
			$options['sort'] = $this->sorts;
			$options['skip'] = (int) $this->offset;
			$options['limit'] = (int) $this->limit;
			$options['readConcern'] = $read_concern;

			$query = new MongoDB\Driver\Query($this->wheres, $options);
			$cursor = $this->db->executeQuery($this->database.".".$collection, $query, $read_preference);
			$array = $cursor->toArray();
			// Clear
			$this->_clear();
			return count($array);
		}
		catch (MongoDB\Driver\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB query failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB query failed.", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Set
	* --------------------------------------------------------------------------------
	*
	* Sets a field to a value
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->set('posted', 1)->update('blog_posts');
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted' => 1, 'time' => time()))->update('blog_posts');
	*/
	public function set($fields, $value = NULL)
	{
		$this->_u('$set');
		if (is_string($fields))
		{
			$this->updates['$set'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
			$this->updates['$set'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Unset
	* --------------------------------------------------------------------------------
	*
	* Unsets a field (or fields)
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->unset('posted')->update('blog_posts');
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted','time'))->update('blog_posts');
	*/
	public function unset_field($fields)
	{
		$this->_u('$unset');
		if (is_string($fields))
		{
			$this->updates['$unset'][$fields] = 1;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field)
			{
				$this->updates['$unset'][$field] = 1;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Add to set
	* --------------------------------------------------------------------------------
	*
	* Adds value to the array only if its not in the array already
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->addtoset('tags', 'php')->update('blog_posts');
	*/
	public function addtoset($field, $values)
	{
		$this->_u('$addToSet');
		$this->updates['$addToSet'][$field] = $values;
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Push
	* --------------------------------------------------------------------------------
	*
	* Pushes values into a field (field must be an array)
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->push('comments', array('text'=>'Hello world'))->update('blog_posts');
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex')->update('blog_posts');
	*/
	public function push($fields, $value = array())
	{
		$this->_u('$push');
		if (is_string($fields))
		{
			$this->updates['$push'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$push'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Pop
	* --------------------------------------------------------------------------------
	*
	* Pops the last value from a field (field must be an array)
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->pop('comments')->update('blog_posts');
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->pop(array('comments', 'viewed_by'))->update('blog_posts');
	*/
	public function pop($field)
	{
		$this->_u('$pop');
		if (is_string($field))
		{
			$this->updates['$pop'][$field] = -1;
		}
		elseif (is_array($field))
		{
			foreach ($field as $pop_field)
			{
				$this->updates['$pop'][$pop_field] = -1;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Pull
	* --------------------------------------------------------------------------------
	*
	* Removes by an array by the value of a field
	*
	* @usage: $this->mongo_db->pull('comments', array('comment_id'=>123))->update('blog_posts');
	*/
	public function pull($field = "", $value = array())
	{
		$this->_u('$pull');
		$this->updates['$pull'] = array($field => $value);
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Rename field
	* --------------------------------------------------------------------------------
	*
	* Renames a field
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->rename_field('posted_by', 'author')->update('blog_posts');
	*/
	public function rename_field($old, $new)
	{
		$this->_u('$rename');
		$this->updates['$rename'] = array($old => $new);
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Inc
	* --------------------------------------------------------------------------------
	*
	* Increments the value of a field
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->inc(array('num_comments' => 1))->update('blog_posts');
	*/
	public function inc($fields = array(), $value = 0)
	{
		$this->_u('$inc');
		if (is_string($fields))
		{
			$this->updates['$inc'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$inc'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Multiple
	* --------------------------------------------------------------------------------
	*
	* Multiple the value of a field
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->mul(array('num_comments' => 3))->update('blog_posts');
	*/
	public function mul($fields = array(), $value = 0)
	{
		$this->_u('$mul');
		if (is_string($fields))
		{
			$this->updates['$mul'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$mul'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Maximum
	* --------------------------------------------------------------------------------
	*
	* The $max operator updates the value of the field to a specified value if the specified value is greater than the current value of the field.
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->max(array('num_comments' => 3))->update('blog_posts');
	*/
	public function max($fields = array(), $value = 0)
	{
		$this->_u('$max');
		if (is_string($fields))
		{
			$this->updates['$max'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$max'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Minimum
	* --------------------------------------------------------------------------------
	*
	* The $min updates the value of the field to a specified value if the specified value is less than the current value of the field.
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->min(array('num_comments' => 3))->update('blog_posts');
	*/
	public function min($fields = array(), $value = 0)
	{
		$this->_u('$min');
		if (is_string($fields))
		{
			$this->updates['$min'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$min'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* //! distinct
	* --------------------------------------------------------------------------------
	*
	* Finds the distinct values for a specified field across a single collection
	*
	* @usage: $this->mongo_db->distinct('collection', 'field');
	*/
	public function distinct($collection = "", $field="")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for update", 500);
		}

		if (empty($field))
		{
			show_error("Need Collection field information for performing distinct query", 500);
		}

		try
		{
			$query = $this->wheres ? $this->wheres : null;
			$command = array('distinct'=>$collection, 'key'=>$field, 'query'=>$query);
			$result = $this->command($command);

			if(!$result) throw new Exception("Error Processing", 1);
			$document = $result[0];
			if ($this->return_as == 'object')
			{
				return $document->values;
			}
			else
			{
				return $document["values"];
			}
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB Distinct Query Failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB failed", 500);
			}
		}
	}	

	/**
	* --------------------------------------------------------------------------------
	* //! Update
	* --------------------------------------------------------------------------------
	*
	* Updates a single document in Mongo
	*
	* @usage: $this->mongo_db->update('foo', $data = array());
	*/
	public function update($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for update", 500);
		}

		if (is_array($data) && count($data)) 
		{
            $this->updates = array_merge($data, $this->updates);
        }

		$bulk = new MongoDB\Driver\BulkWrite();
		$bulk->update($this->wheres, $this->updates, $options);
			
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$write = $this->db->executeBulkWrite($this->database.".".$collection, $bulk, $writeConcern);
			$this->_clear();
			return $write;
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Update of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Update all
	* --------------------------------------------------------------------------------
	*
	* Updates a collection of documents
	*
	* @usage: $this->mongo_db->update_all('foo', $data = array());
	*/
	public function update_all($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to update", 500);
		}
		if (is_array($data) && count($data) > 0)
		{
			$this->updates = array_merge($data, $this->updates);
		}
		if (count($this->updates) == 0)
		{
			show_error("Nothing to update in Mongo collection or update is not an array", 500);	
		}

		$options = array_merge(array('multi' => TRUE), $options);

		$bulk = new MongoDB\Driver\BulkWrite();
		$bulk->update($this->wheres, $this->updates, $options);
			
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$write = $this->db->executeBulkWrite($this->database.".".$collection, $bulk, $writeConcern);
			$this->_clear();
			return $write;
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Update of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Delete
	* --------------------------------------------------------------------------------
	*
	* delete document from the passed collection based upon certain criteria
	*
	* @usage : $this->mongo_db->delete('foo');
	*/
	public function delete($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for update", 500);
		}

		$options = array('limit'=>true);
		$bulk = new MongoDB\Driver\BulkWrite();
		$bulk->delete($this->wheres, $options);
			
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$write = $this->db->executeBulkWrite($this->database.".".$collection, $bulk, $writeConcern);
			$this->_clear();
			return $write;
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Update of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Delete all
	* --------------------------------------------------------------------------------
	*
	* Delete all documents from the passed collection based upon certain criteria
	*
	* @usage : $this->mongo_db->delete_all('foo', $data = array());
	*/
	public function delete_all($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for delete", 500);
		}

		$options = array('limit'=>false);
		$bulk = new MongoDB\Driver\BulkWrite();
		$bulk->delete($this->wheres, $options);
			
		$writeConcern = new MongoDB\Driver\WriteConcern($this->write_concerns, 10000);

		try
		{
			$write = $this->db->executeBulkWrite($this->database.".".$collection, $bulk, $writeConcern);
			$this->_clear();
			return $write;
		}
		// Check if the write concern could not be fulfilled
		catch (MongoDB\Driver\Exception\BulkWriteException $e) 
		{
		    $result = $e->getWriteResult();

		    if ($writeConcernError = $result->getWriteConcernError()) 
		    {
		    	if(isset($this->debug) == TRUE && $this->debug == TRUE)
				{
					show_error("WriteConcern failure : {$writeConcernError->getMessage()}", 500);
				}
				else
				{
					show_error("WriteConcern failure", 500);
				}
		    }
		}
		// Check if any general error occured.
		catch (MongoDB\Driver\Exception\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Delete of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Aggregation Operation
	* --------------------------------------------------------------------------------
	*
	* Perform aggregation on mongodb collection
	*
	* @usage : $this->mongo_db->aggregate('foo', $ops = array());
	*/
	public function aggregate($collection, $operation, $options = array())
	{
        if (empty($collection))
	 	{
	 		show_error("In order to retreive documents from MongoDB, a collection name must be passed", 500);
	 	}
 		
 		if (empty($operation) && !is_array($operation))
	 	{
	 		show_error("Operation must be an array to perform aggregate.", 500);
	 	}

		$command = array_merge(array('aggregate'=>$collection, 'pipeline'=>$operation), $options);
		return $this->command($command);		
    }

    public function aggregate_pipeline($collection, $operation)
    {
    	$array_result = $this->aggregate($collection, $operation);
    	if($this->version > 3.4)
    	{
    		$result = $array_result;
    	} 
    	else
    	{
	    	if( ! $array_result ) 
	    	{
	    		show_error("Not have result.", 500);
	    	}
	    	$result = isset($array_result[0]["result"]) ? $array_result[0]["result"] : [];
    	}
    	return $result;
    }

	/**
	* --------------------------------------------------------------------------------
	* // Order by
	* --------------------------------------------------------------------------------
	*
	* Sort the documents based on the parameters passed. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->order_by(array('foo' => 'ASC'))->get('foobar');
	*/
	public function order_by($fields = array())
	{
		foreach ($fields as $col => $val)
		{
		if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$this->sorts[$col] = -1;
			}
			else
			{
				$this->sorts[$col] = 1;
			}
		}
		return ($this);
	}

	 /**
	* --------------------------------------------------------------------------------
	* Mongo Date
	* --------------------------------------------------------------------------------
	*
	* Create new MongoDate object from current time or pass timestamp to create
	* mongodate.
	*
	* @usage : $this->mongo_db->date($timestamp);
	*/
	public function date($stamp = FALSE)
	{
		if ( $stamp == FALSE )
		{
			return new MongoDB\BSON\UTCDateTime();
		}
		else
		{
			return new MongoDB\BSON\UTCDateTime($stamp * 1000);
		}
		
	}

	public function date_milliseconds($stamp)
	{
		return new MongoDB\BSON\UTCDateTime($stamp);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Limit results
	* --------------------------------------------------------------------------------
	*
	* Limit the result set to $x number of documents
	*
	* @usage : $this->mongo_db->limit($x);
	*/
	public function limit($x = 99999)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->limit = (int) $x;
		}
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Offset
	* --------------------------------------------------------------------------------
	*
	* Offset the result set to skip $x number of documents
	*
	* @usage : $this->mongo_db->offset($x);
	*/
	public function offset($x = 0)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->offset = (int) $x;
		}
		return ($this);
	}

	/**
	 *  Converts document ID and returns document back.
	 *  
	 *  @param   stdClass  $document  [Document]
	 *  @return  stdClass
	 */
	private function convert_document_id($document)
	{
		if ($this->legacy_support === TRUE && isset($document['_id']) && $document['_id'] instanceof MongoDB\BSON\ObjectId)
		{
			$new_id = $document['_id']->__toString();
			unset($document['_id']);
			if(!empty($this->param["_id"])) {
				$document['_id'] = new \stdClass();
				$document['_id']->{'$id'} = $new_id;
			} else {
				$document['id'] = $new_id;
			}
		}
		return $document;
	}
	
	/**
	* --------------------------------------------------------------------------------
	* // Command
	* --------------------------------------------------------------------------------
	*
	* Runs a MongoDB command
	*
	* @param  string : Collection name, array $query The command query
	* @usage : $this->mongo_db->command($collection, array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>true));
	* @access public
        * @return object or array
	*/
	
    public function command($command = array(), $use_cursor = TRUE)
    {
		try{
			if($use_cursor) {
				$command['cursor'] = new stdClass();
			}

			$cursor = $this->db->executeCommand($this->database, new MongoDB\Driver\Command($command));

			if ($this->return_as == 'array') {
				$cursor->setTypeMap(['root'=>'array','document' =>'array','array'=>'array']);
			}

			// Clear
			$this->_clear();
			$returns = array();
			
			if ($cursor instanceof MongoDB\Driver\Cursor)
			{
				$it = new \IteratorIterator($cursor);
				$it->rewind();

				while ($doc = (array)$it->current())
				{
					if ($this->return_as == 'object')
					{
						$returns[] = (object) $this->convert_document_id($doc);
					}
					else
					{
						$returns[] = $this->convert_document_id($doc);
					}
					$it->next();
				}
			}

			if ($this->return_as == 'object')
			{
				return (object)$returns;
			}
			else
			{
				return $returns;
			}
		}
		catch (MongoDB\Driver\Exception $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB query failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB query failed.", 500);
			}
		}
    }


	/**
	* --------------------------------------------------------------------------------
	* //! Add indexes
	* --------------------------------------------------------------------------------
	*
	* Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/
	public function add_index($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to add index to", 500);
		}

		if (empty($keys) || ! is_array($keys))
		{
			show_error("Index could not be created to MongoDB Collection because no keys were specified", 500);
		}

		$nameArr = array();
		foreach ($keys as $col => $val)
		{
			if($val === -1 || $val === FALSE || strtolower($val) === 'desc')
			{
				$keys[$col] = -1;
			}
			else
			{
				$keys[$col] = 1;
			}
			$nameArr[] = $col . "_" . $keys[$col];
		}
		$index = array_merge(array("key" => $keys, "name" => implode($nameArr, "_")), $options);

		$command = array();
		$command['createIndexes'] = $collection;
		$command['indexes'] = array($index);

		return $this->command($command, FALSE);
	}

	/**
	* --------------------------------------------------------------------------------
	* Remove index
	* --------------------------------------------------------------------------------
	*
	* Remove an index of the keys in a collection. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->remove_index($collection, 'index_1');
	*/
	public function remove_index($collection = "", $name = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to remove index from", 500);
		}

		if (empty($name))
		{
			show_error("Index could not be removed from MongoDB Collection because no index name were specified", 500);
		}

		$command = array();
		$command['dropIndexes'] = $collection;
		$command['index'] = $name;

		return $this->command($command);
	}

	/**
	* --------------------------------------------------------------------------------
	* List indexes
	* --------------------------------------------------------------------------------
	*
	* Lists all indexes in a collection.
	*
	* @usage : $this->mongo_db->list_indexes($collection);
	*/
	public function list_indexes($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to list all indexes from", 500);
		}
		$command = array();
		$command['listIndexes'] = $collection;

		return $this->command($command);
	}	

	/**
	* --------------------------------------------------------------------------------
	* //! Switch database
	* --------------------------------------------------------------------------------
	*
	* Switch from default database to a different db
	* $database = "" to reset default database
	* $this->mongo_db->switch_db('foobar');
	*/
	public function switch_db($database = '')
	{
        $this->database = $database;

        try {
            $this->connect();
            return $this;
        } catch (Exception $e) {
            show_error("Unable to switch Mongo Databases: {$e->getMessage()}", 500);
        }
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Drop database
	* --------------------------------------------------------------------------------
	*
	* Drop a Mongo database
	* @usage: $this->mongo_db->drop_db("foobar");
	*/
	public function drop_db($database = '')
	{
		if (empty($database))
		{
			show_error('Failed to drop MongoDB database because name is empty', 500);
		}

		$command = array();
		$command['dropDatabase'] = 1;

		return $this->command($command);
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Drop collection
	* --------------------------------------------------------------------------------
	*
	* Drop a Mongo collection
	* @usage: $this->mongo_db->drop_collection('bar');
	*/
	public function drop_collection($col = '')
	{
		if (empty($col))
		{
			show_error('Failed to drop MongoDB collection because collection name is empty', 500);
		}

		$command = array();
		$command['drop'] = $col;

		return $this->command($command);
	}

	/**
	* --------------------------------------------------------------------------------
	* _clear
	* --------------------------------------------------------------------------------
	*
	* Resets the class variables to default settings
	*/
	private function _clear()
	{
		$this->selects	= array();
		$this->updates	= array();
		$this->wheres	= array();
		$this->limit	= 999999;
		$this->offset	= 0;
		$this->sorts	= array();
	}

	/**
	* --------------------------------------------------------------------------------
	* Where initializer
	* --------------------------------------------------------------------------------
	*
	* Prepares parameters for insertion in $wheres array().
	*/
	private function _w($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Update initializer
	* --------------------------------------------------------------------------------
	*
	* Prepares parameters for insertion in $updates array().
	*/
	private function _u($method)
	{
		if ( ! isset($this->updates[$method]))
		{
			$this->updates[ $method ] = array();
		}
	}
	/**
	* --------------------------------------------------------------------------------
	* @author: Dung
	* --------------------------------------------------------------------------------
	* 
	* Run javascript code.
	*/
	function run($js)
	{
		$jscode = new \MongoDB\BSON\Javascript(<<<NOWDOC
		$js
NOWDOC
);
		$result = $this->command(['eval' => $jscode]);
		if(!$result) throw new Exception("Error Processing", 1);
		return $result[0];
	}

	function listCollections(){
		$listdatabases = new MongoDB\Driver\Command(["listCollections" => 1]);
		$res = $this->db->executeCommand($this->database, $listdatabases);
		$listCollections = [];
		foreach ($res->toArray() as $key => $obj) {
			$listCollections[] = $obj->name;
		}
		return $listCollections;
	}


	//new date 05/09/2025
	public function command_new($command = array(), $use_cursor = TRUE)
	{
		try {
			// Xác định tên command (aggregate, createIndexes, dropIndexes, ...)
			$firstKey = null;
			if (is_array($command)) {
				$keys = array_keys($command);
				$firstKey = isset($keys[0]) ? $keys[0] : null;
			}

			// Chỉ thêm 'cursor' cho lệnh AGGREGATE (Mongo yêu cầu)
			// KHÔNG thêm cho createIndexes/dropIndexes/... để tránh lỗi
			if ($use_cursor && $firstKey === 'aggregate' && empty($command['cursor'])) {
				$command['cursor'] = new stdClass();
			}

			// Thực thi command
			$cursor = $this->db->executeCommand(
				$this->database,
				new MongoDB\Driver\Command($command)
			);

			// Ánh xạ kiểu dữ liệu trả về
			if ($this->return_as == 'array' && $cursor instanceof MongoDB\Driver\Cursor) {
				$cursor->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			}

			// Clear state builder
			$this->_clear();

			// Gom kết quả
			$returns = array();
			if ($cursor instanceof MongoDB\Driver\Cursor) {
				$it = new \IteratorIterator($cursor);
				for ($it->rewind(); $it->valid(); $it->next()) {
					$doc = (array)$it->current();
					if ($this->return_as == 'object') {
						$returns[] = (object) $this->convert_document_id($doc);
					} else {
						$returns[] = $this->convert_document_id($doc);
					}
				}
			}

			return ($this->return_as == 'object') ? (object)$returns : $returns;
		}
		catch (MongoDB\Driver\Exception $e)
		{
			if (isset($this->debug) && $this->debug === TRUE) {
				show_error("MongoDB query failed: {$e->getMessage()}", 500);
			} else {
				show_error("MongoDB query failed.", 500);
			}
		}
	}

	public function add_index_new($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection)) {
			show_error("No Mongo collection specified to add index to", 500);
		}
		if (empty($keys) || !is_array($keys)) {
			show_error("Index could not be created to MongoDB Collection because no keys were specified", 500);
		}

		$nameArr = array();
		foreach ($keys as $col => $val) {
			if ($val === -1 || $val === FALSE || strtolower($val) === 'desc') {
				$keys[$col] = -1;
			} else {
				$keys[$col] = 1;
			}
			$nameArr[] = $col . "_" . $keys[$col];
		}
		$index = array_merge(
			array("key" => $keys, "name" => implode('_', $nameArr)),
			$options
		);

		$command = array(
			'createIndexes' => $collection,
			'indexes'       => array($index),
		);

		// RẤT QUAN TRỌNG: không thêm cursor cho createIndexes
		return $this->command_new($command, FALSE);
	}

	public function remove_index_new($collection = "", $name = "")
	{
		if (empty($collection)) {
			show_error("No Mongo collection specified to remove index from", 500);
		}
		if (empty($name)) {
			show_error("Index could not be removed from MongoDB Collection because no index name were specified", 500);
		}

		$command = array(
			'dropIndexes' => $collection,
			'index'       => $name,
		);

		// Không chèn cursor cho dropIndexes
		return $this->command_new($command, FALSE);
	}


}
