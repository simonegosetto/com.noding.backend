<?php

/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 09/03/2016
 * Time: 19:20
 */

//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
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

ini_set('display_errors', 1);
//remove the notice
error_reporting(E_ERROR | E_WARNING | E_PARSE);

include "DB/FD_DB.php";
include "DB/FD_Mysql.php";
include "Tools/FD_Random.php";
include "Tools/FD_Crypt.php";
include "WebTools/FD_Url.php";
require("WebTools/FD_Logger.php");
include "Tools/FD_JWT.php";

//istanzio logger
$log = new FD_Logger(null);

if (!isset($_GET["gest"])) {
    echo '{"error" : "Invalid request !"}';
    return;
}

//Parametro GET per capire se i parametri successivi sono POST o JSON o GET
/**
 * gest:
 * 1 -> POST
 * 2 -> JSON
 * 3 -> GET
 */
$gest = $_GET["gest"];
$keyRequest = "";

if ($gest == 1)
{
    if (isset($_POST["token"]))
    {
        $keyRequest = $_POST["token"];
    }
    if (isset($_POST["username"]))
    {
        $username = $_POST["username"];
    }
    if (isset($_POST["password"]))
    {
        $password = $_POST["password"];
    }
} else if ($gest == 2)
{
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    if(property_exists((object) $objData,"token"))
    {
        $keyRequest = $objData->token;
    }
    if(property_exists((object) $objData,"username"))
    {
        $username = $objData->username;
    }
    if(property_exists((object) $objData,"password"))
    {
        $password = $objData->password;
    }
} else if ($gest == 3)
{
    if (isset($_GET["token"]))
    {
        $keyRequest = $_GET["token"];
    }
    if (isset($_GET["username"]))
    {
        $username = $_GET["username"];
    }
    if (isset($_GET["password"]))
    {
        $password = $_GET["password"];
    }
}

if(strlen($keyRequest)>0)
{
    $keyRequest = strtolower($keyRequest);
    if ($keyRequest != strtolower(md5_file("Config/esatto.mp3")))
    {
        echo '{"error" : "Invalid token !"}';
        return;
    }
} else
{
    echo '{"error" : "Invalid token !"}';
    return;
}

if (strlen($keyRequest) > 0)
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

    $crypt = new FD_Crypt();
    $password = md5($password);//$crypt->simple_crypt($password);

    //Eseguo la query di login
    $result = $sql->exportJSON("call sys_login('" . $username . "','" . $password . "');");

    if (strlen($sql->lastError) > 0)
    {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    //Se l'utente non esiste o le credenziali immesse sono errate
    if ($sql->affected == 0)
    {
        echo '{"error" : "Le credenziali inserite non sono corrette !"}';
        $log->lwrite('[ERRORE] - Le credenziali inserite non sono corrette ! - '.$username.' '.$password);
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Alloco variabile di sessione
    $random = new FD_Random();
    //$crypt = new FD_Crypt();
    $array = json_decode($result, true);
    //$date = new DateTime();

    if(strpos($result,"error") !== false)
    {
        echo '{"user":' . $result .', "error": "'.json_decode($result, true)[0]["error"].'"}';
    }
    else
    {
        $jwt = new FD_JWT();
        $token = $jwt->encode($random->Generate(25),$keyRequest);
        //Prendo IP client
        $url = new FD_Url();
        //Salvo nel log delle sessioni
        $sql->executeSQL("call sys_session_log(" . $array[0]["cod_u"] . ",'" . $token . "','".$url->IP_ADDRESS."','".$url->USER_AGENT["platform"]."','".$url->USER_AGENT["browser"]."','".$url->USER_AGENT["version"]."');");
        //logger
        $log->lwrite('[INFO] - Login effettuato ! - ('.$token.') - ('.$array[0]["cod_u"].')<b>'.$username.'</b> - '.$url->USER_AGENT["platform"].' - '.$url->USER_AGENT["browser"].' - '.$url->USER_AGENT["version"]);

        echo '{"user":' . $result .',"token": {"token" : "'.$token.'"}}';
    }

    //Chiudo connessione
    $sql->closeConnection();
}
else
{
    echo '{"error" : "Invalid request !"}';
}


