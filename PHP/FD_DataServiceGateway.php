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
include "FD_Crypt.php";
include "FD_Mailer.php";
include "FD_PushNotification.php";
include "FD_Random.php";

if(!isset($_GET["gest"])){
    echo '{"error" : "Invalid request !"}';
    return;
}

$crypt = new FD_Crypt();

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
        $token = $_POST["token"];
    }
    if(isset($_POST["database"])) {
        $database = $_POST["database"];
    }
    if (isset($_POST["suffix"])) {
        $suffix = $_POST["suffix"];
    }
    if (isset($_POST["timbratura"])) {
        $timbratura = $_POST["timbratura"];
    }
    if (isset($_POST["totem"])) {
        $totem = $_POST["totem"];
    }
    if (isset($_POST["mail"])) {
        $mail = $_POST["mail"];
    }
    if (isset($_POST["push"])) {
        $push = $_POST["push"];
    }
    if (isset($_POST["change"])) {
        $change = $_POST["change"];
    }
    if (isset($_POST["sede"])) {
        $sede = $_POST["sede"];
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
        $token = $objData->token;
    }
    if(property_exists((object) $objData,"database")) {
        $database = $objData->database;
    }
    if(property_exists((object) $objData,"suffix")) {
        $suffix = $objData->suffix;
    }
    if(property_exists((object) $objData,"timbratura")) {
        $timbratura = $objData->timbratura;
    }
    if(property_exists((object) $objData,"totem")) {
        $totem = $objData->totem;
    }
    if(property_exists((object) $objData,"mail")) {
        $mail = $objData->mail;
    }
    if(property_exists((object) $objData,"push")) {
        $push = $objData->push;
    }
    if(property_exists((object) $objData,"change")) {
        $change = $objData->change;
    }
    if(property_exists((object) $objData,"sede")) {
        $sede = $objData->sede;
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
        $token = $_GET["token"];
    }
    if(isset($_GET["database"])) {
        $database = $_GET["database"];
    }
    if (isset($_GET["suffix"])) {
        $suffix = $_GET["suffix"];
    }
    if (isset($_GET["timbratura"])) {
        $timbratura = $_GET["timbratura"];
    }
    if (isset($_GET["totem"])) {
        $totem = $_GET["totem"];
    }
    if (isset($_GET["mail"])) {
        $mail = $_GET["mail"];
    }
    if (isset($_GET["push"])) {
        $push = $_GET["push"];
    }
    if (isset($_GET["change"])) {
        $change = $_GET["change"];
    }
    if (isset($_GET["sede"])) {
        $sede = $_GET["sede"];
    }
}

//Prendo il token di sessione dell'utente e controllo che sia valido
if(strlen($token)>0){
    $token2 = $crypt->simple_crypt($token,"decrypt");
    $token_array = explode(",",$token2);
    if(count($token_array) == 3) {
        $keyRequest = strtolower($token_array[1]);
    }else{
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

$random = new FD_Random();

//cambio password
if(isset($change)) {
    $password = $crypt->Django_Crypt($change->password,$random->Generate(12),20000);
    $query = "call spFD_changePassword('".$token. "','".$password."');";
}

//Gestione timbratura
if(isset($timbratura)) {
    if ($timbratura == 1) { // lettura del QrCode
        if (!$totem) {
            echo '{"error" : "Timbratura non valida !"}';
            return;
        }
        $totem = json_decode(json_encode($totem), true);
        $totem2 = $crypt->simple_crypt($totem["text"], "decrypt");
        $totem_array = json_decode($totem2, true);
        if ($totem_array["action"] == "marcatura_ingresso") {
            $type = 1;
            $query = "call spFD_GestioneTimbratura(" . $totem_array["sede"] . ",NOW(),'" . $token . "');";
        }
    }else if($timbratura == 2){ // Manuale
        $type = 1;
        $query = "call spFD_GestioneTimbraturaManuale(".$sede.",NOW(),".$query.");";
    }
}

//Gestione invio mail
if(isset($mail)) {
    $mailer = new FD_Mailer();
    if ($mail->gestione == 1) {
        $mailer->SendMail("volontapp",$mail);
        return;
    }
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

    /*
    //Se criptata ricavo la query reale
    if($cripted == true) {
        //Oggetto di decriptazione
        $dec = new FD_Decrypt();
        //Descripto la query
        $query = $dec->decrypt($query);
    }
    */
/*
    //Check abilitazione DB
    $sql->CheckDB($token,$database);
    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }
    if($sql->affected == 0){
        echo '{"error" : "Invalid db for this user !"}';
        return;
    }
*/
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

    //Se devo mandare delle notifiche push prendo il recorset che mi fornisce l'sql e lo ciclo
    if(isset($push)) {
        $pushNotification = new FD_PushNotification('https://onesignal.com/api/v1/notifications','5683f6e0-4499-4b0e-b797-0ed2a6b1509b','MjhlZTJmNGItMWQ1YS00NTAzLTljZTMtZmNlNTZiNzQzMDQz');
        $array_push = json_decode($result, true);
        $array_push_length = count($array_push);
        if($array_push_length > 0) {
            $ids = [];
            if (isset($array_push["device_id"])) {
                $ids[0] = '"'.$array_push["device_id"].'"';
            } else {
                for ($i = 0; $i < $array_push_length; $i++) {
                    $ids[$i] = '"'.$array_push[$i]["device_id"].'"';
                }
            }
            $app = implode(",", $ids);
            $pushNotification->SendOneSignal($app,$push);
        }
    }

    $sql->closeConnection();

    echo $result;
}else{
    echo '{"error" : "Invalid request !"}';
}


