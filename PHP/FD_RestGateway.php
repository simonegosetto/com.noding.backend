<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 23/03/2016
 * Time: 16:53
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

//prendo parametri in ingresso
$data = file_get_contents("php://input");
$objData = json_decode($data);
if(property_exists((object) $objData,"username")){
    $username = $objData->username;
}
if(property_exists((object) $objData,"password")) {
    $password = $objData->password;
}
if(property_exists((object) $objData,"link")) {
    $link = $objData->link;
}
if(property_exists((object) $objData,"db")) {
    $db = $objData->db;
}

if(strlen($username) == 0){
    echo '{"error" : "Username non valido !"}';
    return;
}

if(strlen($password) == 0){
    echo '{"error" : "Password non valida !"}';
    return;
}

if(strlen($link) == 0){
    echo '{"error" : "Link non valido !"}';
    return;
}

if(strlen($db) == 0){
    echo '{"error" : "DB non valido !"}';
    return;
}

/*
function httpGet($url){
    // Get cURL resource
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    ));
    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);
    //return $resp;
}
*/
/*
$url = 'http://xxx.volontapp.it/form_login/';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
$result = curl_exec($ch);
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}

echo $cookies["csrftoken"];
*/

//Metodo per la richiesta POST al server API
function httpPost($url, $data){//, $token){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$url = $link.'form_login/';
//setcookie("csrftoken",$cookies["csrftoken"],time()+3600,"/","xxx.volontapp.it");
$data = array( //'csrfmiddlewaretoken' => $cookies["csrftoken"],
               'username' => $username,
               'password' => $password,
               'azi_db' => $db
);

$result = httpPost($url,$data);

//setaccio l'header per prendermi i parametri per il cookie
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
//preg_match_all('/Set-Cookie: (.*?);/is', $result, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}

echo  json_encode($cookies);