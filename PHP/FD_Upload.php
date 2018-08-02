<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 13/04/2016
 * Time: 00:38
 */

//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
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

//Imposto le variabili di sistema che mi servono per favorire l'upload
ini_set('display_errors', 1);
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_input_time', 300);
ini_set('max_execution_time', 300);

if (!isset($_GET["gest"]))
{
    echo '{"error" : "Invalid request !"}';
    return;
}

include "Tools/FD_Random.php";

$gest = $_GET["gest"];
$token = '';
$tipo = "";
$url_gateway = $_SERVER["HTTP_REFERER"].explode("/",$_SERVER['REQUEST_URI'])[1]."/";


if ($gest == 1)
{
    if (isset($_POST["tipo"]))
    {
        $tipo = $_POST["tipo"];
    }
    if (isset($_POST["token"]))
    {
        $token = $_POST["token"];
    }
    if (isset($_POST["cod_p"]))
    {
        $cod_p = $_POST["cod_p"];
    }
} else if ($gest == 2) {
    $data = file_get_contents("php://input");
    $objData = json_decode($data);
    if (property_exists((object)$objData, "tipo"))
    {
        $tipo = $objData->tipo;
    }
    if (property_exists((object)$objData, "token"))
    {
        $token = $objData->token;
    }
    if (property_exists((object)$objData, "cod_p"))
    {
        $cod_p = $objData->cod_p;
    }
} else if ($gest == 3) {
    if (isset($_GET["tipo"]))
    {
        $tipo = $_GET["tipo"];
    }
    if (isset($_GET["token"]))
    {
        $token = $_GET["token"];
    }
    if (isset($_GET["cod_p"]))
    {
        $cod_p = $_GET["cod_p"];
    }
}

//Dichiarazione variabili
$name = '';
$size = '';
$error = '';

/*
 * Metodo di compressione delle immagini
 */
function compress_image($source_url, $destination_url, $quality)
{
    $info = getimagesize($source_url);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source_url);
    elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source_url);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source_url);
    imagejpeg($image, $destination_url, $quality);
    return $destination_url;
}

/*
 * Metodo di resize delle immagini
 */
function resize($width, $height)
{
    /* Get original image x y*/
    list($w, $h) = getimagesize($_FILES['file']['tmp_name']);
    /* calculate new image size with ratio */
    $ratio = max($width/$w, $height/$h);
    $h = ceil($height / $ratio);
    $x = ($w - $width / $ratio) / 2;
    $w = ceil($width / $ratio);
    /* new file name */
    $path = '../Upload/'.$width.'x'.$height.'_'.$_FILES['file']['name'];
    /* read binary data from image file */
    $imgString = file_get_contents($_FILES['file']['tmp_name']);
    /* create image from string */
    $image = imagecreatefromstring($imgString);
    $tmp = imagecreatetruecolor($width, $height);
    imagecopyresampled($tmp, $image,
        0, 0,
        $x, 0,
        $width, $height,
        $w, $h);
    /* Save image */
    switch ($_FILES['file']['type'])
    {
        case 'image/jpeg':
            imagejpeg($tmp, $path, 100);
            break;
        case 'image/png':
            imagepng($tmp, $path, 0);
            break;
        case 'image/gif':
            imagegif($tmp, $path);
            break;
        default:
            exit;
            break;
    }
    return $path;
    /* cleanup memory */
    imagedestroy($image);
    imagedestroy($tmp);
}

function resize_and_compression($width, $height, $destination_url, $quality)
{
    /* Get original image x y*/
    list($w, $h) = getimagesize($_FILES['file']['tmp_name']);
    /* calculate new image size with ratio */
    $ratio = max($width/$w, $height/$h);
    $h = ceil($height / $ratio);
    $x = ($w - $width / $ratio) / 2;
    $w = ceil($width / $ratio);
    /* new file name */
    $path = $destination_url;//'../upload/avatar/'.$width.'x'.$height.'_'.$_FILES['file']['name'];
    /* read binary data from image file */
    $imgString = file_get_contents($_FILES['file']['tmp_name']);
    /* create image from string */
    $image = imagecreatefromstring($imgString);
    $tmp = imagecreatetruecolor($width, $height);
    imagecopyresampled($tmp, $image,
        0, 0,
        $x, 0,
        $width, $height,
        $w, $h);
    /* Save image */
    switch ($_FILES['file']['type'])
    {
        case 'image/jpeg':
            imagejpeg($tmp, $path, $quality);
            break;
        case 'image/png':
            imagepng($tmp, $path, 0);
            break;
        case 'image/gif':
            imagegif($tmp, $path);
            break;
        default:
            exit;
            break;
    }
    //return compress_image($path,$destination_url,$quality);
    /* cleanup memory */
    imagedestroy($image);
    imagedestroy($tmp);
}

/*
 * Metodo per la richiesta POST
 */
function httpPost($url, $data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


//INIT
if ($_FILES["file"]["error"] > 0)
{
    if($_FILES["file"]["error"] == 1)
    {
        echo '{"error" : "Superato il limite di 10MB !"}';
    }
    else if($_FILES["file"]["error"] == 4)
    {
        echo '{"error" : "Nessun file caricato !"}';
    }
    else
    {
        echo '{"error" : "Errore durante il caricamento del file('.$_FILES["file"]["error"].')"}';
    }
    return;
}
else //if (($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/jpeg") || ($_FILES["file"]["type"] == "image/png") || ($_FILES["file"]["type"] == "image/pjpeg"))
{
    $random = new FD_Random();
    $name = $random->Generate(20);
    $imageFileType = strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
    $url = '../Upload/'.$name.'.'.$imageFileType;
    move_uploaded_file($_FILES['file']['tmp_name'], $url);
    //$filename = resize_and_compression(150 ,150, $url, 90);

    //Chiamata al databox per il salvataggio del file nel db
    if($cod_p > 0)
    {
        $data = array(
            "type" => "1",
            "token" => $token,
            "process" => "yXttChwR18PsutyQwqmD84J41+C5cTOdm/gu+jbG3qEtWy0tSVYtWy0Re7hHXn4Ns4zHYLP6L9QbeenqSIDFtjFliOjvxpsJmw@@",
            "params" => "'" . $_SERVER["HTTP_REFERER"]. "Upload/" . $name.'.'.$imageFileType . "'," . $cod_p
        );
    }
    else
    {
        $data = array(
            "type" => "1",
            "token" => $token,
            "process" => "aQWu1SmmaITFs0wPPtAi4mvH+ZEphShyYYeUM98pg4ItWy0tSVYtWy1e+2qzPC9u9O9elSJdaZHz9f7O3jqBQcRNBlkfcApwog@@",
            "params" => "'" . $_FILES['file']['name'] . "','" . $name.'.'.$imageFileType . "','" . $tipo . "'," . $_FILES['file']['size'] . ",'" . $_FILES['file']['type'] . "'"
        );
    }
    echo httpPost($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
}
