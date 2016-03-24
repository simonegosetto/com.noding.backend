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

$url = 'http://xxx.volontapp.it/form_login/';
httpGet($url);

$ch = curl_init('http://xxx.volontapp.it/form_login/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// get headers too with this line
curl_setopt($ch, CURLOPT_HEADER, 1);
$result = curl_exec($ch);
// get cookie
// multi-cookie variant contributed by @Combuster in comments
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}
var_dump($cookies);


//Metodo per la richiesta POST al server API
function httpPost($url, $data){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


/*

$url = 'http://xxx.volontapp.it/api-auth/login/';
setcookie("csrftoken", 'WsR9AJCYpMJGbyoJx43mARgatu1VdKZY');
$data = array( 'csrfmiddlewaretoken' => 'WsR9AJCYpMJGbyoJx43mARgatu1VdKZY',
               //'csrftoken' => 'WsR9AJCYpMJGbyoJx43mARgatu1VdKZY',
               //'csrf_token' => 'WsR9AJCYpMJGbyoJx43mARgatu1VdKZY',
               'next' => '/persone/rest_api/persone/',
               'username' => 'xxx',
               'password' => 'volontapp');

echo httpPost($url,$data);

*/

/*
include("restclient.php");

$api = new RestClient(array(
        'base_url' => "http://xxx.volontapp.it/",
        'format' => "json")
);
$result = $api->get("api-auth/login/");

if($result->info->http_code < 400) {
    echo "success:<br/><br/>";
} else {
    echo "failed:<br/><br/>";
}
echo $result->response;
*/