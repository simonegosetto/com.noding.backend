<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 13/07/2018
 * Time: 21:56
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

require("DB/FD_DB.php");
require("DB/FD_Mysql.php");
require("WebTools/FD_Logger.php");

//istanzio logger
$log = new FD_Logger(null);

// funzione group by
function group_by($data, $key)
{
    $result = array();
    foreach($data as $val)
    {
        if(array_key_exists($key, $val))
        {
            $result[$val[$key]][] = $val;
        }
        else
        {
            $result[""][] = $val;
        }
    }
    return $result;
}

// merge con somma delle proprietà
function merge($start_array,$data)
{
    //in_array
    if(!$start_array) $start_array = array();
    $keys = array_keys($data);
    for($i=0;$i<count($keys);$i++)
    {
        $start_array[$keys[$i]] += count($data[$keys[$i]]);
    }
    return $start_array;
}

//output of array fot pie chart
function graph_output($data)
{
    $output = '';
    $keys = array_keys($data);
    for($i=0;$i<count($keys);$i++)
    {
        $output .= '["'.$keys[$i].'",'.$data[$keys[$i]].'],';
    }
    return substr($output,0,strlen($output)-1);
}

try
{
    //Inizializzo componente SQL
    $sql = new FD_Mysql();

    //Controllo che la connessione al DB sia andata a buon fine
    if(strlen($sql->lastError) > 0)
    {
        $log->lwrite('[ERRORE] - '.$sql->lastError);
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $query = "call spFD_clienti_list();";
    $result = $sql->exportJSON($query);

    if(strlen($sql->lastError) > 0)
    {
        $log->lwrite('[ERRORE] - '.$sql->lastError) ;
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $sql->closeConnection();

    //Clienti censiti
    $fp = fopen("../Import/Clienti.json", 'w');
    fwrite($fp, $result);
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file Clienti.json');

    //ciclo i clienti per prendermi i dati relativi
    $array_result = json_decode($result, true);
    $log_error = "";
    for($i=0;$i<count($array_result);$i++)
    {
        $log->lwrite('[INFO] - prendo dati di '.$array_result[$i]["descrizione"]);

        $dettaglio_cliente .= '["'.$array_result[$i]["descrizione"].'",';

        if($array_result[$i]["folder"] == "test") // escludo test dai totali principali, vediamo poi se farlo anche per i demo
        {
            //clienti
            $clienti_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=1");
            $dettaglio_cliente .= count(json_decode($clienti_json, true)).',';

            //articoli
            $articoli_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=2");
            $dettaglio_cliente .= count(json_decode($articoli_json, true)).',';

            //preventivi
            $preventivi_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=3");
            $dettaglio_cliente .= count(json_decode($preventivi_json, true));

            $dettaglio_cliente .= '],';
        }
        else
        {
            //clienti
            $clienti_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=1");
            $tot_clienti += count(json_decode($clienti_json, true));
            $dettaglio_cliente .= count(json_decode($clienti_json, true)).',';

            //articoli
            $articoli_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=2");
            $tot_articoli += count(json_decode($articoli_json, true));
            $dettaglio_cliente .= count(json_decode($articoli_json, true)).',';

            //preventivi
            $preventivi_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=3");
            $tot_preventivi += count(json_decode($preventivi_json, true));
            $dettaglio_cliente .= count(json_decode($preventivi_json, true));

            $dettaglio_cliente .= '],';

            //sessioni
            $sessioni_json = file_get_contents("https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_ExportData.php?tipo=4");
            $platform = group_by(json_decode($sessioni_json, true),"platform");
            $browser = group_by(json_decode($sessioni_json, true),"browser");
        }

        $platform_global = merge($platform_global,$platform);
        $browser_global = merge($browser_global,$browser);

        //errori log
        $file = "https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/Log/".@date('d_m_Y').".txt";
        $log_service = "https://".$array_result[$i]["folder"].".costofacile.it/BackEnd/FD_DataServiceGatewayCrypt.php?log=".@date('d_m_Y');
        $content = file_get_contents($file);
        $errori = substr_count($content, "[ERRORE]");
        $blocchi = substr_count($content, "[DENIED]");

        if ($errori == 0) $errori_label = "Nessun errore riscontrato";
        else $errori_label = '<span style=\"color: red\">'.$errori.' errori riscontrati !</span>';

        if ($blocchi == 0) $blocchi_label = "Nessun blocco riscontrato";
        else $blocchi_label = '<span style=\"color: red\">'.$blocchi.' blocchi riscontrati !</span>';

        $log_error .= '{"cliente": "'.$array_result[$i]["descrizione"].'", "esito": "'.$errori_label.'<br/>'.$blocchi_label.'", "link": "'.$log_service.'"},';
    }

    //log error
    $log_error = '['.substr($log_error,0,strlen($log_error)-1).']';
    $fp = fopen("../Import/LogError.json", 'w');
    fwrite($fp, $log_error);
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file LogError.json');

    //grafico dettaglio clienti
    $dettaglio_cliente = substr($dettaglio_cliente,0,strlen($dettaglio_cliente)-1);
    $fp = fopen("../Import/GraficoClienti.json", 'w');
    fwrite($fp, $dettaglio_cliente);
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file GraficoClienti.json');

    //platform
    $platform_global = graph_output($platform_global);
    $fp = fopen("../Import/Platform.json", 'w');
    fwrite($fp, $platform_global);
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file Platform.json');

    //browser
    $browser_global = graph_output($browser_global);
    $fp = fopen("../Import/Browser.json", 'w');
    fwrite($fp, $browser_global);
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file Browser.json');

    //totali
    $fp = fopen("../Import/Totali.json", 'w');
    fwrite($fp, '[{"clienti":'.$tot_clienti.',"articoli":'.$tot_articoli.',"preventivi":'.$tot_preventivi.'}]');
    fclose($fp);
    $log->lwrite('[INFO] - Scrivo file Totali.json');

}
catch (Exception $e)
{
    $log->lwrite('[ERRORE] - '.$e->getMessage());
}