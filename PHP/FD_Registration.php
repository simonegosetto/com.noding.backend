<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 06/04/2016
 * Time: 16:59
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
include "FD_Crypt.php";
include "FD_Random.php";

if(!isset($_GET["gest"])){
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
    if(isset($_POST["database"])) {
        $database = $_POST["database"];
    }
    if (isset($_POST["suffix"])) {
        $suffix = $_POST["suffix"];
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
    if(property_exists((object) $objData,"database")) {
        $database = $objData->database;
    }
    if(property_exists((object) $objData,"suffix")) {
        $suffix = $objData->suffix;
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
    if(isset($_GET["database"])) {
        $database = $_GET["database"];
    }
    if (isset($_GET["suffix"])) {
        $suffix = $_GET["suffix"];
    }
}

if(strlen($keyRequest)>0) {
    $keyRequest = strtolower($keyRequest);
    if ($keyRequest != strtolower(md5_file("esatto.mp3"))) {
        echo '{"error" : "Invalid token !"}';
        return;
    }
}else{
    echo '{"error" : "Invalid token !"}';
    return;
}

if(strlen($database) == 0){
    echo '{"error" : "Invalid database !"}';
    return;
}

if(strlen($keyRequest) == 0){
    echo '{"error" : "Invalid token !"}';
    return;
}

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

$crypt = new FD_Crypt();
$random = new FD_Random();
$pos = strpos($query,"registration(");
if($pos > 0){
    $take = explode(",",$query);
    $password = $take[2];
    $password = str_replace("'","",$password);
    $password = $crypt->Django_Crypt($password,$random->Generate(12),20000);
    $take[2] = "'".$password."'";
    $query = implode($take,",");
}
$pos = strpos($query,"registration_associazione(");
if($pos > 0){
    $take = explode(",",$query);
    $password = $take[2];
    $password = str_replace("'","",$password);
    $password = $crypt->Django_Crypt($password,$random->Generate(12),20000);
    $take[2] = "'".$password."'";
    $query = implode($take,",");
}

if(strlen($query) > 0 && strlen($type) > 0 && strlen($database) > 0){

    //Inizializzo componente SQL
    $sql = new FD_Mysql($keyRequest,$suffix);

    //Controllo che la connessione al DB sia andata a buon fine
    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    //Seleziono il DB
    if(!$sql->UseDB($database)){
        echo '{"error" : "Invalid database"}';
        return;
    }

    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

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

    echo $result;
}else{
    echo '{"error" : "Invalid request !"}';
}


