<?php

class FileCache{
    private $root_dir;

    public function __construct($root_dir){
        $this->root_dir = $root_dir;
        if(file_exists($this->root_dir) === false){
            mkdir($this->root_dir, 0700, true);
        }
    }

    public function set($key, $value){
        $file_name = $this->root_dir.'/'.$key;
        $dir = dirname($file_name);
        if(file_exists($dir) === false){
            mkdir($dir, 0700, true);
        }
        return file_put_contents($file_name, $value, LOCK_EX);
    }

    public function get($key){
        $file_name = $this->root_dir.'/'.$key;
        if(file_exists($file_name)){
            return file_get_contents($file_name);
        }
        return null;
    }

    public function remove($key){
        $file = $this->root_dir.'/'.$key;
        if(file_exists($file)){
            @unlink($file);
        }
    }


}