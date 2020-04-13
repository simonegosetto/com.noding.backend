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
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
// $origin = str_replace("menudelgiorno.php","",str_replace("/","",str_replace("http://","",str_replace("https://","",$http->REQUEST_HEADER["REFERER"]))));

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

function getRicettaImage($id_storage)
{
    $http2 = new FD_HTTP();
    $data = array(
        "action" => (object) array(
            "mode" => 4,
            "path" => $id_storage
        ),
        "token" => $GLOBALS["token"]
    );
    $response = $http2->Post($GLOBALS["url_gateway"]."FD_DropboxGateway.php?gest=1", $data);
    return json_decode($response, true);
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
    // prendo informazioni testata ricetta
    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => "3K2t3jzxjc+0a0dmj+eRVnotvAfJAoDjYQ/o8SAF2/wtWy0tSVYtWy15LcFBExarLwaeb6649Zrl8Rdbv9FDSmJwaBBc8C3e8g@@",
        "params" => $params
    );
    $testata = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $testata = json_decode($testata, true);
    // var_dump($testata); return;

    // prendo immagine ricetta
    if ($testata["recordset"][0]["id_storage"] != null && $testata["recordset"][0]["id_storage"] != "")
    {
        $immagine = getRicettaImage($testata["recordset"][0]["id_storage"]);
    }

    // prendo ingredienti ricetta
    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => "SK1mkQH9EPMbEjkXjVKh208J+h4RyoSZdYvjFW/IwVEtWy0tSVYtWy13aAC10tFq5lY4fyaPFRki0Z709DrH0ocLUEzAss/mUw@@",
        "params" => $params
    );
    $ingredienti = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $ingredienti = json_decode($ingredienti, true);
    $numero = count($ingredienti["recordset"]);

    for ($i=0;$i<$numero;$i++)
    {
        $htmlIngredienti .= '<li class="row ingredienti">';
        $htmlIngredienti .= '<div class="col-xs-8">'.$ingredienti["recordset"][$i]["nome"].'</div><div class="col-xs-3">'.($ingredienti["recordset"][$i]["quantita"] > 0 ? $ingredienti["recordset"][$i]["quantita"].'g' : '').'</div>';
        $htmlIngredienti .= '</li>';
    }

    // prendo ricette collegate
    $data = array(
        "type" => "1",
        "token" => $token,
        "process" => "VwCLXZp2b7f0ntDBmDtjQiMqA71icSP05BfmU0Opi4ydvEX+uB0U2OIODrusGLmNjWldKOX7EhnVg0nsIWMR4S1bLS1JVi1bLQBR2FofNJE57bLSH6oD630781d1Qx+bHhnMTeAjnNz7",
        "params" => $params
    );
    $ricette = $http->Post($url_gateway."FD_DataServiceGatewayCrypt.php?gest=1", $data);
    $ricette = json_decode($ricette, true);
    $numero = count($ricette["recordset"]);
    if ($numero > 0)
    {
        $htmlRicette = '<div class="row pt-3">';
        for ($i=0;$i<$numero;$i++)
        {
            $htmlRicette .= '<div class="col-xs-4 p-1"><div class="card" style="width: 100%;">';
            if ($ricette["recordset"][$i]["id_storage"] != null && $ricette["recordset"][$i]["id_storage"] != "")
            {
                $htmlRicette .= '<img width="100%" src="'.getRicettaImage($ricette["recordset"][$i]["id_storage"])["link"].'" class="card-img-top" >';
            }
            $htmlRicette .= '<div class="card-body">';
            $htmlRicette .= '<h5 class="card-title">'.$ricette["recordset"][$i]["nome_ric"].'</h5>';
            // prendo ingredienti della sotto ricetta
            $ingredienti = getRicettaIngredienti($ricette["recordset"][$i]["ricettaid"]);
            $numeroIngredientiFigli = count($ingredienti["recordset"]);
            for ($j=0;$j<$numeroIngredientiFigli;$j++)
            {
                $htmlRicette .= '<li class="row"><div class="col-xs-8" style="font-size: 10px">'.$ingredienti["recordset"][$j]["nome"].'</div><div class="col-xs-3 text-right" style="font-size: 10px">'.$ingredienti["recordset"][$j]["quantita"].'g</div></li>';
            }
            $htmlRicette .= '<hr>';
            $htmlRicette .= '<li class="row"><div class="col-xs-12 text-right" style="font-size: 10px;padding-right: 13px">'.array_sum(array_column($ingredienti["recordset"], 'quantita')).'g</div></li>';

            $htmlRicette .= '<p class="card-text">'.$ricette["recordset"][$i]["procedimento2"].'</p>';
            $htmlRicette .= '</div>';
            $htmlRicette .= '</div></div>';
        }
        $htmlRicette .= '</div>';
    }

    // inizializzazione
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
        'margin_header' => 0,
        'margin_footer' => 2,
        'default_font_size' => 14,
        // 'default_font' => 'potatoesandpeas',
        // 'debugfonts' => true,
        'tempDir' => '../ReportService/mPDF/tmp'
    ]);
    $mpdf->SetTitle("Ricetta");
    $mpdf->SetAuthor("Riccardo Valore");
    $mpdf->SetDisplayMode('fullpage');

    ob_start();
    $html = ob_get_contents();
    ob_end_clean();

    // CSS
    $html = html_entity_decode(htmlentities(file_get_contents('../Reports/'.$report)));

    $html = str_replace('<%nome_ric%>', $testata["recordset"][0]["nome_ric"], $html);
    $html = str_replace('<%procedimento%>', $testata["recordset"][0]["procedimento2"], $html);
    $html = str_replace('<%ingredienti%>', $htmlIngredienti, $html);
    if ($immagine)
    {
        $html = str_replace('<%col-ingredienti%>', "col-xs-7", $html);
        $html = str_replace('<%immagine%>','<div class="col-xs-4" style="text-align: right"><img width="100%" src="'.$immagine["link"].'" ></div>', $html);
    }
    else
    {
        $html = str_replace('<%col-ingredienti%>', "col-xs-12", $html);
        $html = str_replace('<%immagine%>', "", $html);
    }
    if ($ricette)
    {
        $html = str_replace('<%ricette%>', $htmlRicette, $html);
    }
    else
    {
        $html = str_replace('<%ricette%>', "", $html);
    }

    $css = file_get_contents('../Reports/bootstrap.css');// str_replace('<%SFONDO%>','FD_DropboxGateway.php?gest=3&id='.$result[0]["id_storage"],$css);

    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html,\Mpdf\HTMLParserMode::HTML_BODY);

    if ($foodcost == '1') {
        $htmlFoodcost = ob_get_contents();
        $mpdf->AddPage();
        $foodcostRighe = getFoodcost($params, $listino);
        $numeroFoodcostRighe = count($foodcostRighe["recordset"]);
        $htmlFoodcost .= '<div class="row"><div class="col-xs-12 px-3 pb-2 pt-3"><h4>Foodcost</h4>';
        for ($j=0;$j<$numeroFoodcostRighe;$j++)
        {
            $htmlFoodcost .= '<li class="row"><div class="col-xs-5" >'.$foodcostRighe["recordset"][$j]["descrizione"].'</div><div class="col-xs-3 text-right" >'.$foodcostRighe["recordset"][$j]["peso"].'g</div><div class="col-xs-3 text-right" >'.number_format($foodcostRighe["recordset"][$j]["foodcost"],2).'€</div></li>';
        }
        $totaliFoodcost = getFoodcostTotali($params, $listino);
        $htmlFoodcost .= '<hr>';
        $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Foodcost</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["foodcost"],2).'€</div></li>';
        $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Prezzo Vendita Lordo</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["prezzo_lordo_vendita"],2).'€</div></li>';
        $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Ratio</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["ratio"],2).'€</div></li>';
        $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Prezzo Vendita Netto</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["prezzo_netto_vendita"],2).'€</div></li>';
        $htmlFoodcost .= '<li class="row" style="font-weight: bold"><div class="col-xs-8 text-right">Margine Netto</div><div class="col-xs-3 text-right" >'.number_format($totaliFoodcost["recordset"][0]["margine_netto"],2).'€</div></li>';
        $htmlFoodcost .= '</div></div>';
        $mpdf->WriteHTML($htmlFoodcost,\Mpdf\HTMLParserMode::HTML_BODY);
    }

    $mpdf->Output();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
