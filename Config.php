<?php

class Config {

    //redis配置
    const REDIS_IP = '127.0.0.1';
    const REDIS_PORT = '6379';
    const REDIS_PASSWD = 'hdkt_test_redis';

    //redis订阅的mq名
    const SUB_CHANNEL = 'IMEI_PUSH_MSG';

    // http服务接口地址
    const URL = 'http://127.0.0.1:8080/position/api/platform/receive/message/v1?param=';

    //tcp服务器监听的本地ip地址和端口
    const IP = '172.17.16.22';
    const PORT = 32767;



}