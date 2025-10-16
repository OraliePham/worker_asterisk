<?php
ini_set("log_errors", 1);
ini_set("error_log", "/var/scripts/log/log.htn");
date_default_timezone_set('Asia/Ho_Chi_Minh');

set_time_limit(0);

//mysql config
define("DB", [
	"HOST_DEFAULT" => '10.148.108.16',
	"HOST" => "127.0.0.1",
	"PORT" => 3306,
	"USER" => 'root',
	"PASS" => 'QWQIWI',
	"DBNAME" => 'pbx'
]);
//mongo config
define("MONGODB", [
	"HOST_DEFAULT" => '10.148.108.16',
	"HOST" => "127.0.0.1",
	"PORT" => 27017,
	"USER" => 'root',
	"PASS" => 'QWQIWI',
	"DBNAME" => 'pbx'
]);

//redis config
define('REDIS_MASTER', [
	"HOST" => '10.148.108.16',
	"PORT" => 6799,
	"PASS" => 'bc957bc3715e94e'
]);

define("REDIS_SLAVE", [
	"HOST" => "127.0.0.1",
	"PORT" => 6799,
	"PASS" => 'bc957bc3715e94e'
]);

define("REDIS", [
	"HOST" => "127.0.0.1",
	"PORT" => 6799,
	"PASS" => 'bc957bc3715e94e'
]);
//memcache config
define("MEMCACHE", [
	"HOST" => "127.0.0.1",
	"PORT" => 11211
]);
//AST config
define("ASTMAN", [
	"HOST" => "127.0.0.1",
	"PORT" => 5038,
	"USER" => 'root',
	"SECRET" => 'Admin@Stel7779I',
]);
//queue config
define('QUEUE_SERVER_CLUSTER_A', '10.148.108.17');
define('QUEUE_SERVER', '127.0.0.1');
define('TIMEOUT_EXPIRED_JOB', 30); //set thời gian timeout để trả lại job vào queue
define('TIMEOUT_EXPIRED_QUEUE', 3600); //set thời gian timeout để giải phóng job ra khỏi queue
//other
define('FILE_PATH_OUTBOUND', '/var/spool/asterisk/monitor/');
