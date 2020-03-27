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
    $mpdf->SetTitle("Tabella Tecnica");
    $mpdf->SetAuthor("Riccardo Valore");
    $mpdf->SetDisplayMode('fullpage');

    ob_start();
    $htmlTotale = ob_get_contents();
    ob_end_clean();

    for ($r=0;$r<$numeroRicette;$r++)
    {

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

        // prendo ricette collegate
        $data = array(
            "type" => "1",
            "token" => $token,
            "process" => "VwCLXZp2b7f0ntDBmDtjQiMqA71icSP05BfmU0Opi4ydvEX+uB0U2OIODrusGLmNjWldKOX7EhnVg0nsIWMR4S1bLS1JVi1bLQBR2FofNJE57bLSH6oD630781d1Qx+bHhnMTeAjnNz7",
            "params" => $ricetteRichieste[$r]
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

                $htmlRicette .= '<div class="card-body">';
                $htmlRicette .= '<h5 class="card-title">'.$ricette["recordset"][$i]["nome_ric"].'</h5>';
                // prendo ingredienti della sotto ricetta
                $ingredienti = getRicettaIngredienti($ricette["recordset"][$i]["ricettaid"]);
                $numeroIngredientiFigli = count($ingredienti["recordset"]);
                for ($j=0;$j<$numeroIngredientiFigli;$j++)
                {
                    $htmlRicette .= '<li class="row"><div class="col-xs-8" style="font-size: 10px">'.$ingredienti["recordset"][$j]["nome"].'</div><div class="col-xs-3" style="font-size: 10px">'.$ingredienti["recordset"][$j]["quantita"].'g</div></li>';
                }
                // $htmlRicette .= '<hr>';
                // $htmlRicette .= '<li class="row"><div class="col-xs-12 text-right" style="font-size: 10px;padding-right: 13px">'.array_sum(array_column($ingredienti["recordset"], 'quantita')).'g</div></li>';

                $htmlRicette .= '<p class="card-text">'.$ricette["recordset"][$i]["procedimento"].'</p>';
                $htmlRicette .= '</div>';
                $htmlRicette .= '</div></div>';
            }
            $htmlRicette .= '</div>';
        }

        // CSS
        $html = html_entity_decode(htmlentities(file_get_contents('../Reports/'.$report)));

        $html = str_replace('<%nome_ric%>', $testata["recordset"][0]["nome_ric"], $html);
        $html = str_replace('<%procedimento%>', $testata["recordset"][0]["procedimento"], $html);
        $html = str_replace('<%ingredienti%>', $htmlIngredienti, $html);
        $htmlTotale .= $html;
    }

    $css = file_get_contents('../Reports/bootstrap.css');// str_replace('<%SFONDO%>','FD_DropboxGateway.php?gest=3&id='.$result[0]["id_storage"],$css);

    $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($htmlTotale,\Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
