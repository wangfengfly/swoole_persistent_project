<?php
set_time_limit(0);
ini_set("memory_limit", "-1");
ini_set('default_socket_timeout', -1);

require_once(__DIR__.'/FileCache.php');
require_once(__DIR__.'/Log.php');
require_once(__DIR__.'/Curl.php');
require_once(__DIR__.'/Config.php');

class Server{

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
        $this->serv = new swoole_server(Config::IP, Config::PORT);
    }


    public function run(){
        $serv = $this->serv;
        $serv->set(array(
            'reactor_num' => self::REACTOR_NUM,
            'worker_num' => self::WORKER_NUM,
            'backlog' => self::BACKLOG,
            'max_request' => self::MAX_REQUEST,
        ));

        $serv->on('receive', function($serv, $fd, $from_id, $data){
            $decoded = json_decode($data, true);
            if(is_array($decoded) && isset($decoded['imei']) && isset($decoded['resp'])){
                $fd = $this->fc->get(self::IMEIKEY_PREFIX.trim($decoded['imei']));
                if($fd){
                    $this->serv->send($fd, $decoded['resp']);
                }else{
                    $logger = new Log('server');
                    $logger->write("fd not exists, msg=".$data, 'err');
                }
            }else{
                $retry = 0;
                do{
                    $http_resp = Curl::get(Config::URL.$data);
                    $retry++;
                }while($http_resp==false && $retry<=3);

                $res = json_decode($http_resp, true);
                if(isset($res['imei'])){
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
    		        $logger = new Log('server');
    		        $logger->write("fd=$fd, curl http response without imei.", 'err');
                }
            }

        });

        $serv->on('close', function($serv, $fd){
            $fdkey = self::FDKEY_PREFIX.$fd;
            $imei = $this->fc->get($fdkey);
            $imeikey = self::IMEIKEY_PREFIX.$imei;
            $this->fc->remove($fdkey);
            $this->fc->remove($imeikey);
            $logger = new Log('server');
            $logger->write("Client: Close. fd:$fd", 'debug');
        });

        $serv->start();

    }

}

$server = new Server();
$server->run();
