<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


//Connecting to Redis server on localhost
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
//    echo "Connection to server sucessfully"; 
//check whether server is running or not
// $redis->set("tutorial-name", "Redis tutorial");
// Get the stored data and print it
$redis->set("antani-2", "sblinda-2");
echo $redis->get("antani-2");
$some_unique_key = "antani-2";
$redis->expire($some_unique_key, 60);
echo $redis->exists($some_unique_key);


