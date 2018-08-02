<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 09/04/2016
 * Time: 11:32
 */
final class FD_Cookie {

    function __construct(){}

    //Set the coockie
    public function SetCoockie($name,$value,$time = null)
    {
        if($time != null){
            setcookie($name, $value, $time, "/");
        } else {
            setcookie($name, $value, time()+31556952000, "/");
        }
    }

    //Remove the coockie
    public function RemoveCoockie($name)
    {
        unset($_COOKIE[$name]);
    }

}
