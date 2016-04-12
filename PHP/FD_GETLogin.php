<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 06/04/2016
 * Time: 00:10
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

include "FD_Mysql.php";
include "FD_Url.php";

if (!isset($_GET["gest"])) {
    echo '{"error" : "Invalid request !""}';
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

if ($gest == 1) {
    if (isset($_POST["token"])) {
        $keyRequest = $_POST["token"];
    }
    if (isset($_POST["username"])) {
        $username = $_POST["username"];
    }
    if (isset($_POST["password"])) {
        $password = $_POST["password"];
    }
    if (isset($_POST["suffix"])) {
        $suffix = $_POST["suffix"];
    }
} else if ($gest == 2) {
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    if(property_exists((object) $objData,"token")) {
        $keyRequest = $objData->token;
    }
    if(property_exists((object) $objData,"username")) {
        $username = $objData->username;
    }
    if(property_exists((object) $objData,"password")) {
        $password = $objData->password;
    }
    if(property_exists((object) $objData,"suffix")) {
        $suffix = $objData->suffix;
    }
} else if ($gest == 3) {
    if (isset($_GET["token"])) {
        $keyRequest = $_GET["token"];
    }
    if (isset($_GET["username"])) {
        $username = $_GET["username"];
    }
    if (isset($_GET["password"])) {
        $password = $_GET["password"];
    }
    if (isset($_GET["suffix"])) {
        $suffix = $_GET["suffix"];
    }
}

if(strlen($keyRequest)>0) {
    $keyRequest = strtolower($keyRequest);
    if ($keyRequest != strtolower(md5_file("esatto.mp3"))) {
        echo '{"error" : "Invalid token !""}';
        return;
    }
}else{
    echo '{"error" : "Invalid token !""}';
    return;
}

if (strlen($keyRequest) > 0) {
    //Inizializzo componente SQL
    $sql = new FD_Mysql($keyRequest,$suffix);

    //Controllo che la connessione al DB sia andata a buon fine
    if (!$sql->connected) {
        echo '{"error" : "Errore di connessione al DB"}';
        return;
    }

    //Prendo IP client
    $url = new FD_Url();

    //Salvo nel log delle richieste GET
    $sql->executeSQL("call spFD_GetRequest('" . $username . "','" . $password . "','".$url->IP_ADDRESS."');");

    if (strlen($sql->lastError) > 0) {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Eseguo la query di login
    $result = $sql->exportJSON("call spFD_login('" . $username . "','" . md5($password) . "');");

    if (strlen($sql->lastError) > 0) {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Se l'utente non esiste o le credenziali immesse sono errate
    if ($sql->affected == 0) {
        echo '{"error" : "Invalid username or password !"}';
        return;
    }

    //Chiudo connessione
    $sql->closeConnection();

    echo '{"user":' . $result .'}';
} else {
    echo '{"error" : "Invalid request !""}';
}


