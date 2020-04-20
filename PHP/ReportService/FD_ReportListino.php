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

if (isset($_GET["params"]))
{
    $params = $_GET["params"];
}
if (isset($_GET["categoria"]))
{
    $categoria = $_GET["categoria"];
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
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 5,
        'margin_bottom' => 0,
        'margin_header' => 0,
        'margin_footer' => 20,
        'default_font_size' => 14,
        'tempDir' => '../ReportService/mPDF/tmp'
    ]);
    $mpdf->SetTitle("Listino");
    $mpdf->SetAuthor("Riccardo Valore");
    $mpdf->SetDisplayMode('fullpage');

    ob_start();
    $htmlTotale = ob_get_contents();
    ob_end_clean();

    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => "j8nnE18DvMJLJn72/pLhFHuvrOA97O1D70EeY0K28/otWy0tSVYtWy1k5ipImnHJdjEgS2084WWHruF1SwXI5uZouBYSWHPGGQ@@",
        "params" => $params.",".$categoria
    );
    $listino = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $listino = json_decode($listino, true);
    $numero = count($listino["recordset"]);

    $htmlTotale .= '<div class="row text-center" ><div class="col-xs-12"><h3>'.$listino["recordset"][0]["listinonome"].' ('.$listino["recordset"][0]["categorianome"].')</h3></div></div>';

    for ($i=0;$i<$numero;$i++)
    {
        $htmlTotale .= '<li class="row ingredienti px-3">';
        $htmlTotale .= '<div class="col-xs-3">'.$listino["recordset"][$i]["descrizione"].'</div>';
        $htmlTotale .= '<div class="col-xs-2 text-right">'.number_format($listino["recordset"][$i]["scarto"],2).'%</div>';
        $htmlTotale .= '<div class="col-xs-2 text-right">'.number_format($listino["recordset"][$i]["grammatura"],2).'g</div>';
        $htmlTotale .= '<div class="col-xs-2 text-right">'.number_format($listino["recordset"][$i]["prezzo"],2).'€</div>';
        $htmlTotale .= '<div class="col-xs-2 text-right">'.number_format($listino["recordset"][$i]["kcal"],2).'kcal</div>';
        $htmlTotale .= '</li><hr style="padding:0;margin:0">';
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
