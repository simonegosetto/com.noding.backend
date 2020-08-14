<?php
/**
 * Created by WebStorm.
 * User: Simone
 * Date: 25/07/2020
 * Time: 14:17
 */

//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

//Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

//remove the notice
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// includo libreria Wordpress
$serverPath = explode("/", __DIR__);
array_splice($serverPath, count($serverPath)-2, 2);
$wpLibrary =  implode("/", $serverPath) . '/wp-load.php';
require_once $wpLibrary;

include "DB/FD_DB.php";
include "DB/FD_Mysql.php";
include "Tools/FD_Random.php";
include "WebTools/FD_Url.php";
require("WebTools/FD_Logger.php");
include "Tools/FD_JWT.php";

//istanzio logger
$log = new FD_Logger(null);

try
{
    //Inizializzo componente SQL
    $sql = new FD_Mysql();

    //Controllo che la connessione al DB sia andata a buon fine
    if (strlen($sql->lastError) > 0)
    {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Eseguo la query di login
    $result = wp_get_current_user()->data;

    if (!isset($result->user_login))
    {
        if ($sql->connected)
        {
            $sql->closeConnection();
        }
        echo '{"redirect" : "' . $_SERVER['SERVER_NAME'] . '"}';
        return;
    }

    //Alloco variabile di sessione
    $random = new FD_Random();
    $jwt = new FD_JWT();
    $token = $jwt->encode($random->Generate(25), $keyRequest);
    //Prendo IP client
    $url = new FD_Url();
    //Salvo nel log delle sessioni
    $sql->executeSQL("call sys_session_log(" . $result->ID . ",'" . $token . "','".$url->IP_ADDRESS."','".$url->USER_AGENT["platform"]."','".$url->USER_AGENT["browser"]."','".$url->USER_AGENT["version"]."');");
    if (strlen($sql->lastError) > 0)
    {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }
    //logger
    $log->lwrite('[INFO] - Login effettuato ! - ('.$token.') - ('.$result->ID.')<b>'.$username.'</b> - '.$url->USER_AGENT["platform"].' - '.$url->USER_AGENT["browser"].' - '.$url->USER_AGENT["version"]);

    echo '{"user":' . json_encode($result) .',"token": {"token" : "'.$token.'"}}';

    //Chiudo connessione
    $sql->closeConnection();
}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
    $log->lwrite('[ERRORE] - '.$e->getMessage());
}


