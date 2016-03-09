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

include "FD_Mysql.php";
//include "FD_Decrypt.php";

if(!isset($_GET["gest"])){
    echo json_encode("Invalid request !");
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

if($gest == 1){
    $keyRequest = $_POST["token"];
    $username = $_POST["username"];
    $password = $_POST["password"];
} else if($gest == 2) {
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    $keyRequest = $objData->token;
    $username =  $objData->username;
    $password =  $objData->password;
} else if($gest == 3) {
    $keyRequest = $_GET["token"];
    $username = $_GET["username"];
    $password = $_GET["password"];
}

$keyRequest = strtolower($keyRequest);
if($keyRequest != strtolower(md5_file("esatto.mp3"))){
    echo json_encode("Invalid token !");
    return;
}

if(strlen($keyRequest) > 0){

    //Inizializzo componente SQL
    $sql = new FD_Mysql($keyRequest);

    //Controllo che la connessione al DB sia andata a buon fine
    if(strlen($sql->lastError) > 0){
        echo json_encode($sql->lastError);
        echo "errore";
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    //Eseguo la query di login
    $result = $sql->exportJSON("call spFD_login('".$username."','".md5($password)."');");
    echo "stored";

    if(strlen($sql->lastError) > 0){
        echo json_encode($sql->lastError);
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    //Se l'utente non esiste o le credenziali immesse sono errate
    if($sql->affected == 0){
        echo json_encode("Invalid username or password !");
        return;
    }

    //Alloco variabile di sessione
    session_start();
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $sql->result[0]["email"];
    $_SESSION['admin'] = $sql->result[0]["admin"];

    //Chiudo connessione
    $sql->closeConnection();

    echo $result;
}else{
    echo json_encode("Invalid request !");
}


