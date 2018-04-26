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

include "DB/FD_DB.php";
include "DB/FD_Mysql.php";
include "Tools/FD_Random.php";
include "Tools/FD_Crypt.php";
include "WebTools/FD_Url.php";
include "Tools/FD_JWT.php";

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
    if ($keyRequest != strtolower(md5_file("/home/vol13_8/0fees.net/fees0_11553437/htdocs/nuovo/BackEnd/Config/esatto.mp3")))
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
    //echo $username." ".$password." ".$keyRequest;
    //echo getcwd();
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

    /*
    //salt
    $result = $sql->exportJSON("call spFD_Salt('".$username."')");
    //Controllo che la connessione al DB sia andata a buon fine
    if (strlen($sql->lastError) > 0) {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }
    $salt = json_decode($result, true);

    $password = $crypt->Django_Crypt($password,$salt["salt"],20000);
    */
    $crypt = new FD_Crypt();
    $password = $crypt->simple_crypt($password);

    //Eseguo la query di login
    $result = $sql->exportJSON("call spFD_login('" . $username . "','" . $password . "');");

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
    $jwt = new FD_JWT();
    $token = $jwt->encode($random->Generate(25),$keyRequest);
    //$token = $crypt->simple_crypt($random->Generate(10).",".$keyRequest.",".$date->format('Y-m-d H:i:s'));

    //Prendo IP client
    $url = new FD_Url();
    //Salvo nel log delle sessioni
    $sql->executeSQL("call spFD_session_log(" . $array["id"] . ",'" . $token . "','".$url->IP_ADDRESS."');");

    //Chiudo connessione
    $sql->closeConnection();

    echo '{"user":' . $result .',"token": {"token" : "'.$token.'"}}';
} else
{
    echo '{"error" : "Invalid request !"}';
}


