<?php
/**
 * Created by PhpStorm.
 * User: Simone Gosetto
 * Date: 19/11/2013
 * Time: 15:55
 *
 * DATASERVICEGATEWAY - CRYPTATO
 * Per la gestione di tutte le richieste al DB + le varie estensioni (mail, push, report)
 *
 * INPUT:
 * token -> per autenticare la richiesta (implementato formato JWT)
 * process -> stored sql cryptata
 * params -> parametri per stored sql
 * type -> tipo di query (query/non query)
 *
 * OUTPUT:
 * error/debug
 * recordset
 * output
 *
 * Tutte le richieste vengono loggate nella cartella "Log" e viene fatto un file per giorno
 *
 * VERSIONE 3.2.1
 *
 * CREARE POLIMORFISMO PER PUSH / MAIL
 *
 */

//header('Content-Type: application/json');

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

require("Config/FD_Define.php");
require("DB/FD_DB.php");
require("DB/FD_Mysql.php");
require("Tools/FD_Crypt.php");
require("WebTools/FD_Logger.php");
require("WebTools/FD_HTTP.php");
require("Tools/FD_JWT.php");

//istanzio logger
$log = new FD_Logger(null);


if(!isset($_GET["gest"]))
{
    echo '{"error" : "Invalid request !"}';
    $log->lwrite('[ERRORE] - Invalid request !');
    return;
}

try
{
    $crypt = new FD_Crypt();

    //Parametro GET per capire se i parametri successivi sono POST o JSON o GET
    /**
     * gest:
     * 1 -> POST
     * 2 -> JSON
     * 3 -> GET
     */
    $gest = $_GET["gest"];
    /**
     * $type:
     * 1 -> Query
     * 2 -> Non Query
     */
    if($gest == 1)
    {
        if(isset($_POST["process"]))
        {
            $process = $_POST["process"];
        }
        if(isset($_POST["params"]))
        {
            $params = $_POST["params"];
        }
        if(isset($_POST["type"]))
        {
            $type = $_POST["type"];
        }
        if(isset($_POST["token"]))
        {
            $token = $_POST["token"];
        }
        if (isset($_POST["report"]))
        {
            $report = $_POST["report"];
        }
        if (isset($_POST["redirect"]))
        {
            $redirect = $_POST["redirect"];
        }
    } 
    else if($gest == 2) 
    {
        $data = file_get_contents("php://input");
        $objData = json_decode($data);
        if(property_exists((object) $objData,"process"))
        {
            $process = $objData->process;
        }
        if(property_exists((object) $objData,"params"))
        {
            $params = $objData->params;
        }
        if(property_exists((object) $objData,"type"))
        {
            $type = $objData->type;
        }
        if(property_exists((object) $objData,"token"))
        {
            $token = $objData->token;
        }
        if(property_exists((object) $objData,"report"))
        {
            $report = $objData->report;
        }
        if(property_exists((object) $objData,"redirect"))
        {
            $redirect = $objData->redirect;
        }
    } 
    else if($gest == 3) 
    {
        if(isset($_GET["process"]))
        {
            $process = $_GET["process"];
        }
        if(isset($_GET["params"]))
        {
            $params = $_GET["params"];
        }
        if(isset($_GET["type"]))
        {
            $type = $_GET["type"];
        }
        if(isset($_GET["token"]))
        {
            $token = $_GET["token"];
        }
        if (isset($_GET["report"]))
        {
            $report = $_GET["report"];
        }
        if (isset($_GET["redirect"]))
        {
            $redirect = $_GET["redirect"];
        }
    }

    //Prendo il token di sessione dell'utente e controllo che sia valido
    $jwt = new FD_JWT();
    if(strlen($token)>0)
    {
        $keyRequest = $jwt->decode($token,strtolower(md5_file("Config/esatto.mp3"))); //ritorna il payload
        if(strlen($keyRequest) == 0)
        {
            echo '{"error" : "Invalid token 1 !"}';
            $log->lwrite('[DENIED] - Invalid token !');
            return;
        }
    }
    else
    {
        echo '{"error" : "Invalid token 2 !"}';
        $log->lwrite('[DENIED] - Invalid token !');
        return;
    }

    $log->lwrite('[INFO] - token - <a href="?ses='.$token.'" target="_blank">'.$token.'</a>');

    if(strlen($keyRequest) == 0)
    {
        echo '{"error" : "Invalid token 3 !"}';
        $log->lwrite('[ERRORE] - Invalid token !');
        return;
    }

    if(strlen($process) == 0)
    {
        echo '{"error" : "Invalid process !"}';
        $log->lwrite('[ERRORE] - Invalid process !');
        return;
    }

    if(strlen($type) == 0)
    {
        echo '{"error" : "Invalid type !"}';
        $log->lwrite('[ERRORE] - Invalid type !');
        return;
    }

    $debug_result = '{"token" : "'.$token.'"';

    $query = '';

    //Capisco se ci sono parametri di output e compongo la query
    $pos = strpos($params,",@");
    if($pos > 0)
    {
        $outputP = explode(",",$params);
        $count_output = count($outputP);
        $OUTPUT = "select ";
        for($i=0;$i<$count_output;$i++)
        {
            if(strpos($outputP[$i],"@") !== false)
            {
                $OUTPUT .= $outputP[$i]." as ".str_replace("@","",$outputP[$i]).",";
            }
        }
        $OUTPUT = substr($OUTPUT,0,strlen($OUTPUT)-1);
        $OUTPUT .= ";";
    }
    else
    {
        $OUTPUT = '';
    }

    $debug_result .= ',"params" : "'.$params.'"';

    //Compongo la query
    if(strlen($query) == 0)
    {
        if (is_null($params))
        {
            $params = '';
        }
        $query = "call " . str_replace(" ", "", trim($crypt->stored_decrypt(str_replace("@", "=", $process)))) . "(" . $crypt->fixString($params) . ");";
    }

    $debug_result .= ',"query" : "'.$query.'"';
    $log->lwrite('[INFO] - query - '.$query);

    if(strlen($query) > 0 && strlen($type) > 0)
    {
        //Inizializzo componente SQL
        $sql = new FD_Mysql();

        //Controllo che la connessione al DB sia andata a buon fine
        if(strlen($sql->lastError) > 0)
        {
            echo '{"error" : "'.$sql->lastError.'"}';
            $log->lwrite('[ERRORE] - '.$sql->lastError.' - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }

        //Eseguo la query
        if($type == EXECUTE_TYPE::QUERY)
        {
            $result = $sql->exportJSON($query);
        }
        else
        {
            $result = "";
            $sql->executeSQL($query);
        }

        $debug_result .= ',"query_result" : '.$result.'';

        if(strlen($sql->lastError) > 0)
        {
            echo '{"error" : "'.$sql->lastError.'"}';
            $log->lwrite('[ERRORE] - '.$sql->lastError.' - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }

        //Gestisco gli output
        $result_ouput = '{}';
        if(strlen($OUTPUT)>0)
        {
            $debug_result .= ',"query_output" : "'.$OUTPUT.'"';
            $result_ouput = $sql->exportJSON($OUTPUT);
            $debug_result .= ',"query_output_result" : '.$result_ouput.'';
            $log->lwrite('[INFO] - output - '.$result_ouput);
        }

        if(strlen($sql->lastError) > 0)
        {
            echo '{"error" : "'.$sql->lastError.'"}';
            $log->lwrite('[ERRORE] - '.$sql->lastError.' - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }

        $sql->closeConnection();

        if(strpos($result,"error") !== false)
        {
            if(isset($debug))
            {
                if(isset($report) && $report != "" && $report != null)
                {
                    $log->lwrite('[DENIED] - report - debug - '.$report.' - '.$result);
                    echo json_decode($result, true)[0]["error"];
                }
                else
                {
                    echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "error": "' . json_decode($result, true)[0]["error"] . '", "debug": ' . $debug_result . '}}';
                }
            }
            else
            {
                if(isset($report) && $report != "" && $report != null)
                {
                    $log->lwrite('[DENIED] - report - '.$report.' - '.$result);
                    echo json_decode($result, true)[0]["error"];
                }
                else
                {
                    echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "error": "' . json_decode($result, true)[0]["error"] . '"}';
                }
            }
        }
        else
        {
            if(isset($report) && $report != "" && $report != null)
            {
                $log->lwrite('[INFO] - report - '.$report.' - '.$result);
                // lancio report allocando i parametri in una sessione
                session_start();
                $_SESSION["ReportData"] = array(
                    "template" => "../Reports/".$report,
                    "data_object" => json_decode($result,true) //count(json_decode($result,true)) == 1 ? json_decode($result,true)[0] : json_decode($result,true)
                );
                Header("Location: ReportService/FD_ReportService.php");
            }
            else if(isset($redirect))
            {
                Header("Location: ".$redirect);
            }
            else
            {
                if($result == "[0]") $result = "[]";
                echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . '}';
            }
        }
    }
    else
    {
        echo '{"error" : "Invalid request !"}';
        $log->lwrite('[ERRORE] - Invalid request ! - '.$debug_result);
    }
}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
    $log->lwrite('[ERRORE] - '.$e->getMessage());
}


