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

function getTotali()
{
    $http2 = new FD_HTTP();
    $data = array(
        "type" => "1",
        "token" => $GLOBALS["token"],
        "process" => "AhzhmjtF+z/CNSybI+oPMsvY8bgo02n2+Kccc5ifI44tWy0tSVYtWy2A3OleBbhIztblymLHcptfGLTotAqZ5MFdbdj4eyjhaA@@",
        "params" => $GLOBALS["menu"].",".$GLOBALS["listino"]
    );
    $result = $http2->Post($GLOBALS["url_gateway"]."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    return json_decode($result, true);
}

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
    $listino = $_GET["listino"];
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
        "process" => "gmWVJZP+UGV9KGcRG53D30i0ozWILb/EMajQiDrIEastWy0tSVYtWy3BMu7OQxzscLI2Tq9rx7i26t6Ra97143uOpKI178zF1w@@",
        "params" => $menu.",".$listino
    );
    $menuRighe = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $menuRighe = json_decode($menuRighe, true);
    $numero = count($menuRighe["recordset"]);

    $htmlTotale .= '<div class="row text-center" ><div class="col-xs-12"><h1>'.$descrizione.'</h1></div></div>';

    for ($i=0;$i<$numero;$i++)
    {
        $htmlTotale .= '<li class="row ingredienti px-3">';
        $htmlTotale .= '<div class="col-xs-8">'.$menuRighe["recordset"][$i]["descrizione"].'</div>';
        $htmlTotale .= '<div class="col-xs-3 text-right">'.number_format($menuRighe["recordset"][$i]["prezzo_lordo_vendita"],2).'€</div>';
        $htmlTotale .= '</li>';
    }

    $totaliFoodcost = getTotali();
    $htmlFoodcost .= '<hr>';
    $htmlFoodcost .= '<div class="row"><div class="col-xs-12 pb-2 pt-3">';
    $htmlFoodcost .= '<li class="row" style="font-weight: bold">';
    $htmlFoodcost .= '<div class="col-xs-3" >N. Coperti</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["pax"],0).'</div>';
    $htmlFoodcost .= '</div></li>';
    $htmlFoodcost .= '<li class="row" style="font-weight: bold">';
    $htmlFoodcost .= '<div class="col-xs-3" >% Scheda Tecnica</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["perc_ricetta"],1).'%</div>';
    $htmlFoodcost .= '</div></li>';
    $htmlFoodcost .= '<br>';
    $htmlFoodcost .= '<li class="row pb-2" style="font-weight: bold">';
    $htmlFoodcost .= '<div class="col-xs-3" >&nbsp;</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-center" >Costo</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-center" >Prezzo Netto</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-center" >Iva</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-center" >Prezzo Lordo</div>';
    $htmlFoodcost .= '</div></li>';
    $htmlFoodcost .= '<li class="row" style="font-weight: bold">';
    $htmlFoodcost .= '<div class="col-xs-3" >Coperto</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["foodcost"],2).'€</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["netto"],2).'€</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["iva"],2).'%</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["lordo"],2).'€</div>';
    $htmlFoodcost .= '</div></li>';
    $htmlFoodcost .= '<li class="row" style="font-weight: bold">';
    $htmlFoodcost .= '<div class="col-xs-3" >Totali Menù</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["foodcost_tot"],2).'€</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["netto_tot"],2).'€</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["iva"],2).'%</div>';
    $htmlFoodcost .= '<div class="col-xs-2 text-right" >'.number_format($totaliFoodcost["recordset"][0]["lordo_tot"],2).'€</div>';
    $htmlFoodcost .= '</div></li>';
    $htmlFoodcost .= '</div></div>';

    $htmlTotale .= $htmlFoodcost;

    $css = file_get_contents('../Reports/bootstrap.css');
    // $css .= 'div { border: 1px solid }';
    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($htmlTotale,\Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output();

}
catch (Exception $e)
{
    echo $e->getMessage();
}
