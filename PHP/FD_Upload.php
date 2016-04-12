<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 13/04/2016
 * Time: 00:38
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

if ($_FILES["file"]["error"] > 0){
    echo "Error Code: " . $_FILES["file"]["error"] . "<br />";
}
else
{
    echo "Uploaded file: " . $_FILES["file"]["name"] . "<br />";
    echo "Type: " . $_FILES["file"]["type"] . "<br />";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kilobytes<br />";

    if (file_exists("/files/".$_FILES["file"]["name"]))
    {
        echo $_FILES["file"]["name"] . " already exists. No joke-- this error is almost <i><b>impossible</b></i> to get. Try again, I bet 1 million dollars it won't ever happen again.";
    }
    else
    {
        move_uploaded_file($_FILES["file"]["tmp_name"],"/var/www/html/upload/avatar/".$_FILES["file"]["name"]);
        echo "Done";
    }
}
