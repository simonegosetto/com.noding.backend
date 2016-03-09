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

header('Content-Type: application/json');

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}

include "FD_Mysql.php";
include "FD_Decrypt.php";

if(!isset($_GET["gest"])){
    echo json_encode("Invalid request !");
    return;
}

//Oggetto di output
$databox = new ArrayObject(array(), ArrayObject::STD_PROP_LIST);

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
    $query = $_POST["query"];
    $type = $_POST["type"];
    $cripted = $_POST["cripted"];
    $keyRequest = $_POST["token"];
} else if($gest == 2) {
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    $query = $objData->query;
    $type = $objData->type;
    $cripted = $objData->cripted;
    $keyRequest = $objData->token;
} else if($gest == 3) {
    $query = $_GET["query"];
    $type = $_GET["type"];
    $cripted = $_GET["cripted"];
    $keyRequest = $_GET["token"];
}

$keyRequest = strtolower($keyRequest);

if($keyRequest != strtolower(md5_file("esatto.mp3"))){
    echo json_encode("Invalid token !");
    return;
}

if(strlen($query) == 0){
    echo json_encode("Invalid query !");
    return;
}

if(strlen($type) == 0){
    echo json_encode("Invalid type !");
    return;
}

if(strlen($query) > 0 && strlen($type) > 0){

    //Token di richiesta
    //$keyRequest = md5_file("http://simonegosetto.it/FD_Components/esatto.mp3");
    $sql = new FD_Mysql($keyRequest);

    if(strlen($sql->lastError) > 0){
        echo json_encode($sql->lastError);
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
        echo json_encode($sql->lastError);
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
    echo json_encode("Invalid request !");
}


