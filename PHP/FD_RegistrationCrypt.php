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

include "DB/FD_Mysql.php";
include "Tools/FD_Crypt.php";
include "Tools/FD_Random.php";
include "WebTools/FD_Mailer.php";

if(!isset($_GET["gest"])){
    echo '{"error" : "Invalid request !"}';
    return;
}

try {

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
        if (isset($_POST["mail"])) {
            $mail = $_POST["mail"];
        }
        if (isset($_POST["reset"])) {
            $reset = $_POST["reset"];
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
        if(property_exists((object) $objData,"mail")) {
            $mail = $objData->mail;
        }
        if(property_exists((object) $objData,"reset")) {
            $reset = $objData->reset;
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
        if (isset($_GET["mail"])) {
            $mail = $_GET["mail"];
        }
        if (isset($_GET["reset"])) {
            $reset = $_GET["reset"];
        }
    }

    if(strlen($keyRequest)>0) {
        $keyRequest = strtolower($keyRequest);
        if ($keyRequest != strtolower(md5_file("../Config/esatto.mp3"))) {
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

    if($keyRequest != strtolower(md5_file("../Config/esatto.mp3"))){
        echo '{"error" : "Invalid token !"}';
        return;
    }

    if(strlen($process) == 0){
        echo '{"error" : "Invalid process !"}';
        return;
    }

    if(strlen($type) == 0){
        echo '{"error" : "Invalid type !"}';
        return;
    }

    $random = new FD_Random();
    $crypt = new FD_Crypt();
    $query = '';

    //Gestione invio mail
    if(isset($mail)) {
        $mailer = new FD_Mailer();
        if ($mail->gestione == 1) {
            $mailer->SendMail("volontapp",$mail);
            return;
        }else if($mail->gestione == 2) {
            $resetToken = $random->Generate(32);
            $query = "call spFD_resetPasswordRequest('".$resetToken. "','".$mail->email."');";
        }
    }

    //reset password
    if(isset($reset)) {
        $password = $crypt->Django_Crypt($reset->password,$random->Generate(12),20000);
        //echo "call spFD_resetPassword('".$reset->token. "','".$password."');";
        $query = "call spFD_resetPassword('".$reset->token. "','".$password."');";
    }

    //Compongo la query
    if(strlen($query) == 0) {
        if (is_null($params)) {
            $params = '';
        }
        $query = "call " . str_replace(" ", "", trim($crypt->stored_decrypt(str_replace("@", "=", $process)))) . "(" . $params . ");";
    }

    $pos = strpos($query,"registration(");
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

    if($mail->gestione == 2) {
        $pos = strpos($result,"error");
        if($pos <= 0){
            $mailer->SendMail("volontapp", $mail, $resetToken);
        }
    }

} catch (Exception $e) {
    echo '{"error" : "'.$e->getMessage().'"}';
}
