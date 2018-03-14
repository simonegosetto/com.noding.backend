<?php

/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 07/06/2016
 * Time: 00:56
 */

abstract class FD_PushNotification
{

    //Costruttore
    function FD_PushNotification(){}

    //Metodo per la richiesta POST
    abstract public function Send($data,$push){}

}
