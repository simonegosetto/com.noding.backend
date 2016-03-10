<?php

/**
 * Created by PhpStorm.
 * User: Simone Gosetto
 * Date: 19/11/2015
 * Time: 15:55
 *
 * DATASERVICEGATEWAY
 * Per la gestione di tutte le richieste al DB
 *
 * INPUT:
 * token per autenticare la richiesta
 * query sql
 * tipo di query (query/non query) - OK
 * query criptata si/no (da gestire nelle prossime versioni)
 *
 *
 * OUTPUT:
 * messaggio di errore - OK
 * recordset - OK
 * righe affected - OK
 *
 */

//header('Content-Type: application/json');

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
include "FD_Decrypt.php";

if(!isset($_GET["gest"])){
    echo '{"error" : "Invalid request !"}';
    return;
}

//Oggetto di output (per ora faccio l'output del json in un unico array, successivamente possiamo anche gestire un array di oggetti più complesso)
//$databox = new ArrayObject(array(), ArrayObject::STD_PROP_LIST);

//Parametro GET per capire se i parametri successivi sono POST o JSON o GET
/**
 * gest:
 * 1 -> POST
 * 2 -> JSON
 * 3 -> GET
 */
$gest = $_GET["gest"];
/**
 * $type:
 * 1 -> Query
 * 2 -> Non Query
 */
$cripted=false; //DA IMPLEMENTARE

if($gest == 1){
    if(isset($_POST["query"])) {
        $query = $_POST["query"];
    }
    if(isset($_POST["type"])) {
        $type = $_POST["type"];
    }
    if(isset($_POST["cripted"])) {
        $cripted = $_POST["cripted"];
    }
    if(isset($_POST["token"])) {
        $keyRequest = $_POST["token"];
    }
} else if($gest == 2) {
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    if(property_exists((object) $objData,"query")){
        $query = $objData->query;
    }
    if(property_exists((object) $objData,"type")) {
        $type = $objData->type;
    }
    if(property_exists((object) $objData,"cripted")) {
        $cripted = $objData->cripted;
    }
    if(property_exists((object) $objData,"token")) {
        $keyRequest = $objData->token;
    }
} else if($gest == 3) {
    if(isset($_GET["query"])) {
        $query = $_GET["query"];
    }
    if(isset($_GET["type"])) {
        $type = $_GET["type"];
    }
    if(isset($_GET["cripted"])) {
        $cripted = $_GET["cripted"];
    }
    if(isset($_GET["token"])) {
        $keyRequest = $_GET["token"];
    }
}

$keyRequest = strtolower($keyRequest);
if($keyRequest != strtolower(md5_file("esatto.mp3"))){
    echo '{"error" : "Invalid token !"}';
    return;
}

if(strlen($query) == 0){
    echo '{"error" : "Invalid query !"}';
    return;
}

if(strlen($type) == 0){
    echo '{"error" : "Invalid type !"}';
    return;
}

if(strlen($query) > 0 && strlen($type) > 0){

    //Inizializzo componente SQL
    $sql = new FD_Mysql($keyRequest);

    //Controllo che la connessione al DB sia andata a buon fine
    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }
    /*
    //Se criptata ricavo la query reale
    if($cripted == true) {
        //Oggetto di decriptazione
        $dec = new FD_Decrypt();
        //Descripto la query
        $query = $dec->decrypt($query);
    }
    */

    //Eseguo la query
    if($type == 1){
        $result = $sql->exportJSON($query);
    }else{
        $result = "";
        $sql->executeSQL($query);
    }

    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    $sql->closeConnection();

    /*
        $databox["errormessage"] = $sql->lastError;
        $databox["recordset"] = $result;
        $databox["affected"] = $sql->affected;
    */
    //print_r($databox);
    echo $result;
}else{
    echo '{"error" : "Invalid request !"}';
}


