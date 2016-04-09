<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 09/04/2016
 * Time: 11:32
 */
//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: localhost");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

//Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

if(isset($_GET['csrftoken'])){
    setcookie("csrftoken", $_GET['csrftoken'], time()+31556952000, "/");
}

if(isset($_GET['sessionid'])){
    setcookie("sessionid", $_GET['sessionid'], time()+31556952000, "/");
}
