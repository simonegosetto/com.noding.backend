<?php
/**
 * Created by PhpStorm.
 * User: gosetto
 * Date: 20/04/2020
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
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require("../ReportService/mPDF/vendor/autoload.php");
require("../WebTools/FD_Logger.php");
require("../WebTools/FD_HTTP.php");
require("../Tools/FD_JWT.php");
require("../Tools/FD_Crypt.php");

//istanzio logger
$log = new FD_Logger(null);

if (isset($_GET["menu"]))
{
    $menu = $_GET["menu"];
}
if (isset($_GET["listino"]))
{
    $menuRighe = $_GET["listino"];
}
if (isset($_GET["descrizione"]))
{
    $descrizione = $_GET["descrizione"];
}
if (isset($_GET["token"]))
{
    $token = $_GET["token"];
}

$http = new FD_HTTP();
$url_gateway = "https://riccardovalore.com/BackEnd/";

try
{
    // inizializzazione
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 20,
        'margin_right' => 20,
        'margin_top' => 5,
        'margin_bottom' => 0,
        'margin_header' => 0,
        'margin_footer' => 20,
        'default_font_size' => 14,
        'tempDir' => '../ReportService/mPDF/tmp'
    ]);
    $mpdf->SetTitle("Menù");
    $mpdf->SetAuthor("Riccardo Valore");
    $mpdf->SetDisplayMode('fullpage');

    ob_start();
    $htmlTotale = ob_get_contents();
    ob_end_clean();

    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => "psv6VQSAtEEbFZMQFsqECpPHcS39sQCLkeYlWTsSz+QtWy0tSVYtWy3Yp+JgJC//klU7QOi+O80sdHpj9guvh0v/3I34nk2LMg@@",
        "params" => $menu.",".$menuRighe
    );
    $menuRighe = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $menuRighe = json_decode($menuRighe, true);
    $numero = count($menuRighe["recordset"]);

    $htmlTotale .= '<div class="row text-center" ><div class="col-xs-12"><h2>'.$descrizione.'</h2></div></div>';

    for ($i=0;$i<$numero;$i++)
    {
        if ($menuRighe["recordset"][$i]["tipo"] == 1)
        {
            $htmlTotale .= '<li class="row ingredienti px-3 pt-3 pb-3 text-center">';
            $htmlTotale .= '<div>'.$menuRighe["recordset"][$i]["categoria"].'</div>';
            $htmlTotale .= '</li>';
        }
        else
        {
            $htmlTotale .= '<li class="row ingredienti px-3 pb-2">';
            $htmlTotale .= '<div class="col-xs-8">- '.$menuRighe["recordset"][$i]["descrizione"].'</div>';
            $htmlTotale .= '<div class="col-xs-3 text-right">'.number_format($menuRighe["recordset"][$i]["prezzo_lordo_vendita"],2).'€</div>';
            $htmlTotale .= '</li>';
        }
    }

    $css = file_get_contents('../Reports/bootstrap.css');
    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($htmlTotale,\Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output();

}
catch (Exception $e)
{
    echo $e->getMessage();
}
