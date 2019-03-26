<?php

class Log {
    public static $LEVEL = 0;
    private static $instance;
    private $filename;

    const LEVEL_INFO = 0;
    const LEVEL_DEBUG = 1;
    const LEVEL_WARN = 2;
    const LEVEL_ERR = 3;
    const LOG_LEVEL = [
        'info' => self::LEVEL_INFO,
        'debug' => self::LEVEL_DEBUG,
        'warn' => self::LEVEL_WARN,
        'err' => self::LEVEL_ERR,
    ];

    private function __construct($filename){
        $dir = __DIR__.'/log/';
        if(!file_exists($dir)){
            mkdir($dir);
        }
        $this->filename = $dir.$filename.'-'.date('YmdH').'.log';
    }

    public static function getInstance($filename){
        if(!isset(self::$instance[$filename])){
            self::$instance[$filename] = new Log($filename);
        }
        return self::$instance[$filename];
    }

    public function write($message, $level){
        if(!isset(self::LOG_LEVEL[$level])){
            return;
        }
        $levelint = self::LOG_LEVEL[$level];
        if($levelint < self::$LEVEL){
            return;
        }
        return file_put_contents($this->filename, date('Y-m-d H:i:s')."\t".$level."\t".$message.PHP_EOL, LOCK_EX|FILE_APPEND);
    }

}