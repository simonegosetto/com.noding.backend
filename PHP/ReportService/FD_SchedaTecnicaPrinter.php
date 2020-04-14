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

// require("Config/FD_Define.php");
require("../ReportService/mPDF/vendor/autoload.php");
require("../WebTools/FD_Logger.php");
require("../WebTools/FD_HTTP.php");
require("../Tools/FD_JWT.php");
require("../Tools/FD_Crypt.php");
//require("../Tools/FD_Date.php");
require("../DB/FD_DB.php");
require("../DB/FD_Mysql.php");

$http = new FD_HTTP();
$url_gateway = "https://riccardovalore.com/BackEnd/";

//istanzio logger
$log = new FD_Logger(null);

if(!isset($_GET["gest"]))
{
    echo '{"error" : "Invalid request !"}';
    $log->lwrite('[ERRORE] - Invalid request !');
    return;
}

// $crypt = new FD_Crypt();
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
if (isset($_GET["report"]))
{
    $report = $_GET["report"];
}
if (isset($_GET["foodcost"]))
{
    $foodcost = $_GET["foodcost"];
}
if (isset($_GET["listino"]))
{
    $listino = $_GET["listino"];
}
if (isset($_GET["descrizione"]))
{
    $descrizione = $_GET["descrizione"];
}

function getRicettaIngredienti($cod_p)
{
    $http2 = new FD_HTTP();
    $data = array(
        "type" => "1",
        "token" => $GLOBALS["token"],
        "process" => "SK1mkQH9EPMbEjkXjVKh208J+h4RyoSZdYvjFW/IwVEtWy0tSVYtWy13aAC10tFq5lY4fyaPFRki0Z709DrH0ocLUEzAss/mUw@@",
        "params" => $cod_p
    );
    $ingredienti = $http2->Post($GLOBALS["url_gateway"]."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    return json_decode($ingredienti, true);
}
function getFoodcost($cod_p, $listino)
{
    $http2 = new FD_HTTP();
    $data = array(
        "type" => "1",
        "token" => $GLOBALS["token"],
        "process" => "7c3nI1n1F+7U+NXIpgCDf+pC34FjTNYTw4jNa+K3KTAtWy0tSVYtWy2iLNJ3IBm9iOz/OUH8uUnOneEwIi4Rp5oXYz8toKLFyg@@",
        "params" => $cod_p.",".$listino
    );
    $result = $http2->Post($GLOBALS["url_gateway"]."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    return json_decode($result, true);
}
function getFoodcostTotali($cod_p, $listino)
{
    $http2 = new FD_HTTP();
    $data = array(
        "type" => "1",
        "token" => $GLOBALS["token"],
        "process" => "qowr/0gbIcivI7tzuXF3CcuV980mPwzGQU+See/fYNMtWy0tSVYtWy3CP0PrzJ6X9m1jn4Z+ig0OxeCGFi2Pu2EwfFZDO7S8pw@@",
        "params" => $cod_p.",".$listino
    );
    $result = $http2->Post($GLOBALS["url_gateway"]."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    return json_decode($result, true);
}

try
{
    // echo $params;return;
    $ricetteRichieste = explode(",", $params);
    $numeroRicette = count($ricetteRichieste);

    // inizializzazione
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 5,
        'margin_bottom' => 0,
        'margin_header' => 0,
        'margin_footer' => 20,
        'default_font_size' => 14,
        // 'default_font' => 'potatoesandpeas',
        // 'debugfonts' => true,
        'tempDir' => '../ReportService/mPDF/tmp'
    ]);
    $mpdf->SetTitle("Scheda Produzione");
    $mpdf->SetAuthor("Riccardo Valore");
    $mpdf->SetDisplayMode('fullpage');

    ob_start();
    $htmlTotale = ob_get_contents();
    ob_end_clean();

    $totIngredienti = 0;

    $css = file_get_contents('../Reports/bootstrap.css');// str_replace('<%SFONDO%>','FD_DropboxGateway.php?gest=3&id='.$result[0]["id_storage"],$css);
    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);

    for ($r=0;$r<$numeroRicette;$r++)
    {
        $htmlTotale = ob_get_contents();
        $htmlIngredienti = '';
        if ($r == 0)
        {
            $htmlTotale .= '<div class="row text-center" ><div class="col-xs-12"><h3>'.$descrizione.'</h3></div></div>';
        }

        // prendo informazioni testata ricetta
        $data = array(
            "type" => "1",
            "token" => $token,
            "process" => "3K2t3jzxjc+0a0dmj+eRVnotvAfJAoDjYQ/o8SAF2/wtWy0tSVYtWy15LcFBExarLwaeb6649Zrl8Rdbv9FDSmJwaBBc8C3e8g@@",
            "params" => $ricetteRichieste[$r]
        );
        $testata = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
        $testata = json_decode($testata, true);
        // var_dump($testata); return;

        // prendo ingredienti ricetta
        if ($foodcost == '0') {
            $data = array(
                "type" => "1",
                "token" => $token,
                "process" => "SK1mkQH9EPMbEjkXjVKh208J+h4RyoSZdYvjFW/IwVEtWy0tSVYtWy13aAC10tFq5lY4fyaPFRki0Z709DrH0ocLUEzAss/mUw@@",
                "params" => $ricetteRichieste[$r]
            );
            $ingredienti = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
            $ingredienti = json_decode($ingredienti, true);
            $numero = count($ingredienti["recordset"]);

            for ($i=0;$i<$numero;$i++)
            {
                $htmlIngredienti .= '<li class="row ingredienti px-3">';
                $htmlIngredienti .= '<div class="col-xs-8">'.$ingredienti["recordset"][$i]["nome"].'</div><div class="col-xs-3">'.($ingredienti["recordset"][$i]["quantita"] > 0 ? $ingredienti["recordset"][$i]["quantita"].'g' : '').'</div>';
                $htmlIngredienti .= '</li><hr style="padding:0;margin:0">';
            }
        }
        else
        {
            $resultFoodcost = getFoodcost($ricetteRichieste[$r],$listino);
            $numero = count($resultFoodcost["recordset"]);
            for ($i=0;$i<$numero;$i++)
            {
                $htmlIngredienti .= '<li class="row ingredienti px-3">';
                $htmlIngredienti .= '<div class="col-xs-7">'.$resultFoodcost["recordset"][$i]["descrizione"].'</div><div class="col-xs-2">'.($resultFoodcost["recordset"][$i]["peso"] > 0 ? $resultFoodcost["recordset"][$i]["peso"].'g' : '').'</div><div class="col-xs-2 text-right" >'.number_format($resultFoodcost["recordset"][$i]["foodcost"],2).'€</div>';
                $htmlIngredienti .= '</li><hr style="padding:0;margin:0">';
            }

            $totaliFoodcost = getFoodcostTotali($ricetteRichieste[$r], $listino);
            $htmlFoodcost = '<div class="row"><div class="col-xs-12 px-3 pb-2 pt-3">';
            $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Foodcost</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["foodcost"],2).'€</div></li>';
            $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Prezzo Vendita Lordo</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["prezzo_lordo_vendita"],2).'€</div></li>';
            $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Ratio</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["ratio"],2).'€</div></li>';
            $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Prezzo Vendita Netto</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["prezzo_netto_vendita"],2).'€</div></li>';
            $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Margine Netto</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["margine_netto"],2).'€</div></li>';
            $htmlFoodcost .= '</div></div>';
            $testata["recordset"][0]["procedimento"] = $htmlFoodcost;
        }

        // gestione del pagebreak in base al numero degli ingredienti ed al numero di ricette
        if ((intval($totIngredienti) + intval($numero) + intval($numeroRicette)) >= 35 && (intval($totIngredienti) + intval($numeroRicette)) < 35)
        {
            $mpdf->AddPage();
        }
        $totIngredienti = intval($totIngredienti) + intval($numero);

        $html = html_entity_decode(htmlentities(file_get_contents('../Reports/'.$report)));
        $html = str_replace('<%nome_ric%>', $testata["recordset"][0]["nome_ric"], $html);
        $html = str_replace('<%procedimento%>', $testata["recordset"][0]["procedimento"], $html);
        $html = str_replace('<%ingredienti%>', $htmlIngredienti, $html);
        $htmlTotale .= $html;

        $mpdf->WriteHTML($htmlTotale,\Mpdf\HTMLParserMode::HTML_BODY);
    }

    $mpdf->Output();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
