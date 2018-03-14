<?php

/**
 * Created by PhpStorm.
 * User: Simone Gosetto
 * Date: 19/11/2015
 * Time: 15:55
 *
 * DATASERVICEGATEWAY - CRYPTATO
 * Per la gestione di tutte le richieste al DB
 *
 * INPUT:
 * token per autenticare la richiesta
 * process -> stored sql cryptata
 * params -> parametri per stored sql
 * tipo di query (query/non query) - OK
 *
 * OUTPUT:
 * messaggio di errore - OK
 * recordset - OK
 * righe affected - da implementare
 *
 * VERSIONE 2.0.0
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

include "DB/FD_Mysql.php";
include "Tools/FD_Crypt.php";
include "WebTools/FD_Mailer.php";
include "PushNotification/FD_PushNotification.php";
include "Tools/FD_Random.php";
include "Tools/FD_JWT.php";

if(!isset($_GET["gest"])){
    echo '{"error" : "Invalid request !"}';
    return;
}

try {

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

    if($gest == 1){
        if(isset($_POST["process"])) {
            $process = $_POST["process"];
        }
        if(isset($_POST["params"])) {
            $params = $_POST["params"];
        }
        if(isset($_POST["type"])) {
            $type = $_POST["type"];
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
        if (isset($_POST["totem"])) {
            $totem = $_POST["totem"];
        }
        if (isset($_POST["mail"])) {
            $mail = $_POST["mail"];
        }
        if (isset($_POST["push"])) {
            $push = $_POST["push"];
        }
    } else if($gest == 2) {
        $data = file_get_contents("php://input");
        $objData = json_decode($data);
        if(property_exists((object) $objData,"process")){
            $process = $objData->process;
        }
        if(property_exists((object) $objData,"params")){
            $params = $objData->params;
        }
        if(property_exists((object) $objData,"type")) {
            $type = $objData->type;
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
        if(property_exists((object) $objData,"totem")) {
            $totem = $objData->totem;
        }
        if(property_exists((object) $objData,"mail")) {
            $mail = $objData->mail;
        }
        if(property_exists((object) $objData,"push")) {
            $push = $objData->push;
        }
    } else if($gest == 3) {
        if(isset($_GET["process"])) {
            $process = $_GET["process"];
        }
        if(isset($_GET["params"])) {
            $params = $_GET["params"];
        }
        if(isset($_GET["type"])) {
            $type = $_GET["type"];
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
        if (isset($_GET["totem"])) {
            $totem = $_GET["totem"];
        }
        if (isset($_GET["mail"])) {
            $mail = $_GET["mail"];
        }
        if (isset($_GET["push"])) {
            $push = $_GET["push"];
        }
    }

    //Prendo il token di sessione dell'utente e controllo che sia valido
    $jwt = new FD_JWT();
    if(strlen($token)>0){
        $keyRequest = $jwt->decode($token,strtolower(md5_file("../Config/esatto.mp3"))); //ritorna il payload
        if(strlen($keyRequest) == 0) {
            echo '{"error" : "Invalid token !"}';
            return;
        }
        /*$token2 = $crypt->simple_crypt($token,"decrypt");
        $token_array = explode(",",$token2);
        if(count($token_array) == 3) {
            $keyRequest = strtolower($token_array[1]);
        }else{
            echo '{"error" : "Invalid token !"}';
            return;
        }*/
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

    /*if($keyRequest != strtolower(md5_file("esatto.mp3"))){
        echo '{"error" : "Invalid token !"}';
        return;
    }*/

    if(strlen($process) == 0){
        echo '{"error" : "Invalid process !"}';
        return;
    }

    if(strlen($type) == 0){
        echo '{"error" : "Invalid type !"}';
        return;
    }

    $random = new FD_Random();
    $query = '';

    //Gestione invio mail
    if(isset($mail)) {
        $mailer = new FD_Mailer();
        if ($mail->gestione == 1) {
            $mailer->SendMail("volontapp",$mail);
            return;
        }
    }

    //Capisco se ci sono parametri di output e compongo la query
    $pos = strpos($params,"@");
    if($pos > 0){
        $outputP = explode(",",$params);
        $count_output = count($outputP);
        $OUTPUT = "select ";
        for($i=0;$i<$count_output;$i++){
            if(strpos($outputP[$i],"@") === false){

            }else{
                $OUTPUT .= $outputP[$i]." as ".str_replace("@","",$outputP[$i]).",";
            }
        }
        $OUTPUT = substr($OUTPUT,0,strlen($OUTPUT)-1);
        $OUTPUT .= ";";
    }else{
        $OUTPUT = '';
    }

    //Compongo la query
    if(strlen($query) == 0) {
        if (is_null($params)) {
            $params = '';
        }
        $query = "call " . str_replace(" ", "", trim($crypt->stored_decrypt(str_replace("@", "=", $process)))) . "(" . $crypt->fixString($params) . ");";
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

        //Gestisco gli output
        $result_ouput = '{}';
        if(strlen($OUTPUT)>0){
            $result_ouput = $sql->exportJSON($OUTPUT);
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

        echo '{"input" : '.$result.',"output" : '.$result_ouput.'}';
    }else{
        echo '{"error" : "Invalid request !"}';
    }
} catch (Exception $e) {
    echo '{"error" : "'.$e->getMessage().'"}';
}


