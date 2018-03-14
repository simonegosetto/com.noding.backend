<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 23/04/2016
 * Time: 21:34
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

include "Tools/FD_Crypt.php";

//prendo parametri in ingresso
$data = file_get_contents("php://input");
$objData = json_decode($data);
if(property_exists((object) $objData, "link")) {
    $link = $objData->link;
}

//Metodo per la richiesta POST al server API
function httpPost($url, $data){//, $token){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$ini_array = parse_ini_file("../Config/config.inc_volontapp.ini");
$crypt = new FD_Crypt();
//echo str_replace(" ","",trim($crypt->mysql_decrypt(str_replace("@","=",$ini_array["userapi"]))))."<br/>".str_replace(" ","",trim($crypt->mysql_decrypt(str_replace("@","=",$ini_array["passapi"]))));

$url = $link.'api-token-auth/';
$data = array(
    'username' => str_replace(" ","",trim($crypt->mysql_decrypt(str_replace("@","=",$ini_array["userapi"]),strtolower(md5_file("../Config/esatto.mp3"))))),
    'password' => str_replace(" ","",trim($crypt->mysql_decrypt(str_replace("@","=",$ini_array["passapi"]),strtolower(md5_file("../Config/esatto.mp3")))))
);

$result = httpPost($url,$data);
/*
//setaccio l'header per prendermi i parametri per il cookie
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}
*/

echo  $result;
