<?php
/**
 * Author: wangfeng
 * Date: 2019/3/26
 * Time: 16:20
 */

/*监听demo频道，打印收到的信息*/
function process($redis, $chan, $msg){
    var_dump($msg);
}

$redis = new Redis();

$res = $redis->connect('127.0.0.1', '10087');
$redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
$redis->subscribe(array('foo'), 'process');
