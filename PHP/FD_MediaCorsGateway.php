<?php

/**
 * Created by WebStorm.
 * User: Simone
 * Date: 28/03/2021
 * Time: 13:29
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

// ini_set('display_errors', 1);
//remove the notice
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$url = ($_POST['url']) ? $_POST['url'] : $_GET['url'];
// $headers = ($_POST['headers']) ? $_POST['headers'] : $_GET['headers'];
// $mimeType = ($_POST['mimeType']) ? $_POST['mimeType'] : $_GET['mimeType'];
$session = curl_init($url);

if ($_POST['url']) {
    $postvars = '';

    while ($element = current($_POST)) {
        $postvars .= key($_POST) . '=' . $element . '&';
        next($_POST);
    }

    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, $postvars);
}

// curl_setopt($session, CURLOPT_HEADER, $headers == 'true');
curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($session);

if ($mimeType != '') {
    header('Content-Type: ' . $mimeType);
}

echo $response;

curl_close($session);
