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

include "FD_Mysql.php";
include "FD_Random.php";
include "FD_Crypt.php";
include "FD_Url.php";

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
        echo '{"error" : "Invalid token !"}';
        return;
    }
}else{
    echo '{"error" : "Invalid token !"}';
    return;
}

if (strlen($keyRequest) > 0) {
    //echo $username." ".$password." ".$keyRequest;
    //echo getcwd();
    //Inizializzo componente SQL
    $sql = new FD_Mysql($keyRequest,$suffix);

    //Controllo che la connessione al DB sia andata a buon fine
    if (strlen($sql->lastError) > 0) {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

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
    $crypt = new FD_Crypt();
    $password = $crypt->Django_Crypt($password,$salt["salt"],20000);

    //Eseguo la query di login
    $result = $sql->exportJSON("call spFD_login('" . $username . "','" . $password . "');");

    if (strlen($sql->lastError) > 0) {
        echo '{"error" : "' . $sql->lastError . '"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Se l'utente non esiste o le credenziali immesse sono errate
    if ($sql->affected == 0) {
        echo '{"error" : "Le credenziali inserite non sono corrette !"}';
        if ($sql->connected) {
            $sql->closeConnection();
        }
        return;
    }

    //Alloco variabile di sessione
    $random = new FD_Random();
    $crypt = new FD_Crypt();
    $array = json_decode($result, true);
    $date = new DateTime();
    $token = $crypt->simple_crypt($random->Generate(10).",".$keyRequest.",".$date->format('Y-m-d H:i:s'));

    //Inizializzo la sessione
    /*
    session_start();
    $_SESSION['user_id'] = $array["id"];
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $array["email"];
    $_SESSION['admin'] = $array["admin"];
    $_SESSION['ID'] = $random->Generate(50);
    */

    /*
    //Gestisco l'ID di sessione con lo standard JWT
    $token = (new Builder())->setIssuer('http://example.com')// Configures the issuer (iss claim)
    ->setAudience('http://example.org')// Configures the audience (aud claim)
    ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
    ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
    ->setNotBefore(time() + 60)// Configures the time that the token can be used (nbf claim)
    ->setExpiration(time() + 3600)// Configures the expiration time of the token (exp claim)
    ->set('uid', 1)// Configures a new claim, called "uid"
    ->getToken(); // Retrieves the generated token
    $token->getHeaders(); // Retrieves the token headers
    $token->getClaims(); // Retrieves the token claims
    echo $token; // The string representation of the object is a JWT string (pretty easy, right?)
    */
    /*
     * unset($_SESSION['username']);
          unset($_SESSION['email']);
          unset($_SESSION['name']);
          unset($_SESSION['userid']);
          session_destroy();
          session_regenerate_id();
     *
     */

    //Prendo IP client
    $url = new FD_Url();

    //Salvo nel log delle sessioni
    $sql->executeSQL("call spFD_session_log(" . $array["id"] . ",'" . $token . "','".$url->IP_ADDRESS."');");

    //Chiudo connessione
    $sql->closeConnection();

    echo '{"user":' . $result .',"token": {"token" : "'.$token.'"}}';
} else {
    echo '{"error" : "Invalid request !"}';
}


