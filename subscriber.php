<?php

set_time_limit(0);
ini_set("memory_limit", "-1");
ini_set('default_socket_timeout', -1);

require_once(__DIR__.'/Log.php');
require_once(__DIR__.'/Config.php');

class Subscriber{

    private $tcp_socket;

    public function __construct(){
    	$this->tcp_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create  socket\n");
    	socket_connect($this->tcp_socket, Config::IP, Config::PORT) or die("Could not connect server\n");
    }

    public function subFunc($redis, $chan, $msg){
        switch($chan){
            case Config::SUB_CHANNEL:
		        $logger = new Log('subscriber');
	            $logger->write('subscribe msg: '.$msg, 'debug');
                $res = socket_write($this->tcp_socket, $msg);
                if($res === false){
                	$logger->write('write to tcp socket fail', 'err');
                }else{
                	$logger->write('write to tcp socket success, msg: '.$msg, 'debug');
                }
                break;
        }
    }


    public function run(){
    	$redis = new Redis();
        $redis->pconnect(Config::REDIS_IP, Config::REDIS_PORT);
        $redis->auth(Config::REDIS_PASSWD);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        try{
            $redis->config("SET", "tcp-keepalive", "60");
            $redis->subscribe([Config::SUB_CHANNEL], array($this, 'subFunc'));
        }catch(Exception $ex){
	        $logger = new Log('subscriber');
            $logger->write('exception: '.$ex->getMessage(), 'debug');
        }

    }
}

$subscriber = new Subscriber();
$subscriber->run();