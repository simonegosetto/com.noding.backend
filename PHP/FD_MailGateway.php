<?php

// Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN']))
{
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

//Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
{
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require("Config/FD_Define.php");
require("WebTools/FD_Mailer.php");
require("WebTools/FD_Logger.php");
require("Tools/FD_JWT.php");

$log = new FD_Logger(null);

$data = file_get_contents("php://input");
$objData = json_decode($data);
if(property_exists((object) $objData,"token"))
{
    $token = $objData->token;
}
if(property_exists((object) $objData,"mail"))
{
    $mail = $objData->mail;
}

// Prendo il token di sessione dell'utente e controllo che sia valido
$jwt = new FD_JWT();
if(strlen($token)>0)
{
    $keyRequest = $jwt->decode($token,strtolower(md5_file("Config/esatto.mp3"))); //ritorna il payload
    if(strlen($keyRequest) == 0)
    {
        echo '{"error" : "Invalid token 1 !"}';
        $log->lwrite('[DENIED] - Invalid token !');
        return;
    }
}
else
{
    echo '{"error" : "Invalid token 2 !"}';
    $log->lwrite('[DENIED] - Invalid token !');
    return;
}

if (!isset($mail))
{
    echo '{"error" : "Invalid request !"}';
    $log->lwrite('[DENIED] - Invalid request !');
    return;
}

$mailer = new FD_Mailer();
$mailer->SendMail($mail->gestione, $mail);
