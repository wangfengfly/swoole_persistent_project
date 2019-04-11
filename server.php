<?php
set_time_limit(0);
ini_set("memory_limit", "-1");

require_once(__DIR__.'/FileCache.php');
require_once(__DIR__.'/Log.php');
require_once(__DIR__.'/Curl.php');

class Server{
    //redis配置
    const REDIS_IP = '127.0.0.1';
    const REDIS_PORT = '6379';
    const REDIS_PASSWD = 'passwd';

    //redis订阅的mq名
    const SUB_CHANNEL = 'IMEI_PUSH_MSG';

    // http服务接口地址
    const URL = 'http://127.0.0.1:8080/position/api/platform/receive/message/v1?param=';
    
    //tcp服务器监听的本地ip地址和端口
    const IP = '172.17.16.22';
    const PORT = 32767;

    const IMEIKEY_PREFIX = 'imei_';
    const FDKEY_PREFIX = 'fd_';

    const REACTOR_NUM = 6;
    const WORKER_NUM = 16;
    const BACKLOG = 128;
    const MAX_REQUEST = 0;

    private $fc;
    private $serv;

    public function __construct(){
        $this->fc = new FileCache('/dev/shm');
        $this->serv = new swoole_server(self::IP, self::PORT);
    }

    public function subFunc($redis, $chan, $msg){
        switch($chan){
            case self::SUB_CHANNEL:
	        Log::getInstance('server')->write('subscribe msg: '.$msg, 'debug');
                $res = json_decode($msg, true);
                if(isset($res['imei']) && isset($res['resp'])){
                    $fd = $this->fc->get(self::IMEIKEY_PREFIX.trim($res['imei']));
                    if($fd){
		    	$this->serv->send($fd, $res['resp']);
	  	    }
                }
                break;
        }
    }


    public function run(){
        $serv = $this->serv;
        $serv->set(array(
            'reactor_num' => self::REACTOR_NUM,
            'worker_num' => self::WORKER_NUM,
            'backlog' => self::BACKLOG,
            'max_request' => self::MAX_REQUEST,
        ));

        $serv->on('ManagerStart', function($serv){
            $redis = new Redis();
            $redis->pconnect(self::REDIS_IP, self::REDIS_PORT);
            $redis->auth(self::REDIS_PASSWD);
            //设置读超时无限，否则会因为长时间没有读数据，redis关闭连接。
            $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
            try{
                $redis->subscribe([self::SUB_CHANNEL], array($this, 'subFunc'));
            }catch(Exception $ex){
                Log::getInstance('server')->write('redis subscribe error.', 'debug');
            }
        });

        $serv->on('receive', function($serv, $fd, $from_id, $data){
            $http_resp = Curl::get(self::URL.$data);
            $res = json_decode($http_resp, true);
            if(isset($res['imei'])){
	        Log::getInstance('server')->write("client msg received", 'debug');
                $imei = trim($res['imei']);
                $imeikey = self::IMEIKEY_PREFIX.$imei;
                $fdkey = self::FDKEY_PREFIX.$fd;
                if($imei){
                    $this->fc->set($imeikey, $fd);
                    $this->fc->set($fdkey, $imei);
                }
                //有resp才需要返回
                if(isset($res['resp']) && $res['resp']){
                    $serv->send($fd, $res['resp']);
                }
            }else{
                Log::getInstance('server')->write("fd=$fd, curl http response without imei.", 'err');
            }

        });

        $serv->on('close', function($serv, $fd){
            $fdkey = self::FDKEY_PREFIX.$fd;
            $imei = $this->fc->get($fdkey);
            $imeikey = self::IMEIKEY_PREFIX.$imei;
            $this->fc->remove($fdkey);
            $this->fc->remove($imeikey);
            Log::getInstance('server')->write("Client: Close. fd:$fd", 'debug');
        });

        $serv->start();

    }

}

$server = new Server();
$server->run();
