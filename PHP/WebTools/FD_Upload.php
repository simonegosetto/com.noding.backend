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

include "FD_Random.php";

$gest = $_GET["gest"];
$token = '';
/**
 * 1 -> Avatar
 * 2 -> Logo
 * 3 -> Post
 */
$tipo = 0;


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
    if (isset($_POST["avatar"]))
    {
        $avatar = $_POST["avatar"];
    }
    if (isset($_POST["post_id"]))
    {
        $post = $_POST["post_id"];
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
    if (property_exists((object)$objData, "avatar"))
    {
        $avatar = $objData->avatar;
    }
    if (property_exists((object)$objData, "post_id"))
    {
        $post = $objData->post_id;
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
    if (isset($_GET["avatar"]))
    {
        $avatar = $_GET["avatar"];
    }
    if (isset($_GET["post_id"]))
    {
        $post = $_GET["post_id"];
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
    $path = '../upload/avatar/'.$width.'x'.$height.'_'.$_FILES['file']['name'];
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

if ($_FILES["file"]["error"] > 0)
{
    if($_FILES["file"]["error"] == 1){
        echo '{"error" : "Superato il limite di 10MB per le immagini !"}';
    }else if($_FILES["file"]["error"] == 4) {
        echo '{"error" : "Nessun file caricato !"}';
    }else{
        echo '{"error" : "Errore durante il caricamento delle immagini('.$_FILES["file"]["error"].')"}';
    }
    return;
} else if (($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/jpeg") || ($_FILES["file"]["type"] == "image/png") || ($_FILES["file"]["type"] == "image/pjpeg"))
{
    $random = new FD_Random();
    $name = $random->Generate(20);
    if($tipo == 1)
    {
        $url = '../upload/avatar/'.$name.'.jpg';
        $filename = resize_and_compression(150 ,150, $url, 90);
    } else if($tipo == 2)
    {
        $url = '../upload/logo/'.$name.'.jpg';
        $filename = resize_and_compression(150 ,150, $url, 90);//compress_image($_FILES["file"]["tmp_name"], $url, 90);
    } else if ($tipo == 3)
    {
        $url = '../upload/post/'.$name.'.jpg';
        $filename = compress_image($_FILES["file"]["tmp_name"], $url, 30);
    } else
    {
        $url = '../upload/timbrature/'.$name.'.jpg';
        $filename = compress_image($_FILES["file"]["tmp_name"], $url, 30);
    }

    //Chiamata al databox per il salvataggio dell'immagine nel db
    if($tipo == 1)
    {
        $data = array(
            "type" => "1",
            "query" => "call spFD_updateAvatar('" . $token . "','" . $name . "');",
            "token" => $token,
            "database" => "authDB",
            "suffix" => "volontapp"
        );
    } else if($tipo == 2)
    {
        $data = array(
            "type" => "1",
            "query" => "call spFD_updateLogo('" . $token . "','" . $name . "');",
            "token" => $token,
            "database" => "authDB",
            "suffix" => "volontapp"
        );
    } else if($tipo == 3)
    {
        $data = array(
            "type" => "1",
            "query" => "call spFD_immaginePost('" . $token . "','" . $name . "',".$post.");",
            "token" => $token,
            "database" => "authDB",
            "suffix" => "volontapp"
        );
    }
} else
{
    echo '{"error" : "L\'immagine deve essere in formato JPG o PNG o GIF !"}';
}
