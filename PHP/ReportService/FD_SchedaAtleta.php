<?php
/**
 * Created by PhpStorm.
 * User: gosetto
 * Date: 05/06/2019
 * Time: 09:50
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

//remove the notice
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require("../Config/FD_Define.php");
require("mPDF/vendor/autoload.php");
require("../WebTools/FD_Logger.php");
require("../WebTools/FD_HTTP.php");
require("../Tools/FD_JWT.php");

// istanzio logger
$log = new FD_Logger(null);

$http = new FD_HTTP();
$url_gateway = "https://silvanofedi.pistoiacorre.it/BackEnd/";

if (isset($_GET["process"]))
{
    $process = $_GET["process"];
}
if (isset($_GET["params"]))
{
    $params = $_GET["params"];
}
if (isset($_GET["token"]))
{
    $token = $_GET["token"];
}

$log->lwrite('[INFO] - Richiesta Report - '.$params);

try
{
    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => $process,
        "params" => $params
    );
    $result = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $result = json_decode($result, true);

    ob_start();
    $html = ob_get_contents();
    ob_end_clean();
    // $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    // $fontData = $defaultFontConfig['fontdata'];

    // CSS
    $css = file_get_contents('../Reports/atleti/style.css');
    $css = str_replace('<%SFONDO%>','/assets/SchedaAtleta.png',$css);

    // Gestisco il tamplate con un file custom per ogni cliente
    $customTemplate = file_get_contents('../Reports/atleti/template.php');
    if (strlen($customTemplate) > 0)
    {
        eval($customTemplate);
    }
    // echo '<pre>'.$html.'</pre>';return;

    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html,\Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
