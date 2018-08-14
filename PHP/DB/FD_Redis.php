<?php
/**
 * Created by VSCode.
 * User: Simone Gosetto
 * Date: 14/08/2018
 * Time: 16:20
 **/

require "predis/autoload.php";
PredisAutoloader::register();

final class FD_Redis
{
    var $redis;

    // costruttore con connessione automatica
    function __construct($scheme,$host,$port = 6379)
    {
        try {
            if($host == null)
                $this->redis = new PredisClient();
            else 
            {
                //connessione a server remoto
                $redis = new PredisClient(array(
                    "scheme" => $scheme,
                    "host" => $host, //"153.202.124.2",
                    "port" => $port //6379
                ));
            }
            return true;
        }
        catch (Exception $e) {
            //die($e->getMessage());
            return false;
        }
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    public function exists($key)
    {
        return $this->redis->exists($key);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function set($key,$value,$type)
    {
        //  DA FINIRE !!!!!!!!!!!
        $this->redis->set($key, $value);
    }

    public function hmset($key, $value)
    {
        $this->redis->hset($key, $value);
    }

    public function hgetall($key)
    {
        return $this->redis->hgetall($key);
    }

    public function del($key)
    {
        $this->redis->del($key);
    }

     /* *******************
	 * EXPIRY
	 * *******************/

    public function persist($key)
    {
        $this->redis->persist($key);
    }

}