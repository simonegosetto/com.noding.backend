<?php
/**
 * Created by PhpStorm.
 * User: Simone Gosetto
 * Date: 19/11/2013
 * Time: 15:55
 *
 * DATASERVICEGATEWAY - CRYPTATO
 * Per la gestione di tutte le richieste al DB
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
 * VERSIONE 5.1.4
 */

// Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
}

//remove the notice
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require("Config/FD_Define.php");
require("DB/FD_DB.php");
require("DB/FD_Mysql.php");
require("DB/FD_Redis.php");
require("Tools/FD_Crypt.php");
require("WebTools/FD_Logger.php");
require("WebTools/FD_Url.php");

$log = new FD_Logger(null);
$crypt = new FD_Crypt();
$url = new FD_Url();

if (!isset($_GET["gest"])) {
    echo '{"error" : "Invalid request !"}';
    $log->lwrite('[ERRORE] - Invalid request !');
    http_response_code(422);
    return;
}

$token = $url->getBearerToken();

try {
    // Parametro GET per capire se i parametri successivi sono POST o JSON o GET
    /**
     * gest:
     * 1 -> POST
     * 2 -> JSON
     * 3 -> GET
     */
    $gest = $_GET["gest"];
    if ($gest == 1) {
        if (isset($_POST["process"])) {
            $process = $_POST["process"];
        }
        if (isset($_POST["params"])) {
            $params = $_POST["params"];
        }
    } else if ($gest == 2) {
        $data = file_get_contents("php://input");
        $objData = json_decode($data);
        if (property_exists((object)$objData, "process")) {
            $process = $objData->process;
        }
        if (property_exists((object)$objData, "params")) {
            $params = $objData->params;
        }
    } else if ($gest == 3) {
        if (isset($_GET["process"])) {
            $process = $_GET["process"];
        }
        if (isset($_GET["params"])) {
            $params = $_GET["params"];
        }
    }

    // $log->lwrite('[INFO] - token - <a href="?ses='.$token.'" target="_blank">'.$token.'</a>');

    if (strlen($process) == 0) {
        echo '{"error" : "Invalid process !"}';
        $log->lwrite('[ERRORE] - Invalid process !');
        // http_response_code(422);
        return;
    }

    // get query from cache
    $config = parse_ini_file("Config/config.inc.ini");
    if ($config["REDIS"] == "1") {
        $redis = new FD_Redis('127.0.0.1', 6379);
        $cachedQuery = $redis->queryGetFromCache($token, $process, $params);
        // $log->lwrite('[REDIS] - query: '.$cachedQuery);
        $redis->close();
        if (strlen($cachedQuery) > 0) {
            header("AAA-From-Cache: true");
            echo $cachedQuery;
            return;
        }
    }

    $query = '';

    // Compongo la query
    $stored = '';
    if (strlen($query) == 0) {
        if (is_null($params)) {
            $params = '';
        }
        $stored = str_replace(" ", "", trim($crypt->stored_decrypt(str_replace("@", "=", $process))));
        $query = "call " . $stored . "(" . $crypt->fixString($params) . ");";

        // in caso di server di test rendo il nome della stored in chiaro
        if (substr($_SERVER['HTTP_HOST'], 0, 5) == "test.") {
            header("AAA-Stored: " . $stored, " " . $_SERVER['QUERY_STRING']);
        }
    }

    // Capisco se ci sono parametri di output e compongo la query
    $pos = strpos($params, ",@");
    if ($pos > 0) {
        $localOutputParams = substr($params, strpos($params, ",@") + 1, strlen($params) - strpos($params, ",@"));
        $outputP = explode(",", $localOutputParams);
        $count_output = count($outputP);
        $OUTPUT = "select ";
        for ($i = 0; $i < $count_output; $i++) {
            if(strpos($outputP[$i],"@") !== false) { // if (str_contains($outputP[$i], "@")) {
                $OUTPUT .= $outputP[$i] . " as " . str_replace("@", "", $outputP[$i]) . ",";
            }
        }
        $OUTPUT = substr($OUTPUT, 0, strlen($OUTPUT) - 1);
        $OUTPUT .= ";";
    } else {
        $OUTPUT = '';
    }

    $log->lwrite('[INFO] - query - ' . $query);

    if (strlen($query) > 0) {
        // Inizializzo componente SQL
        $sql = new FD_Mysql();

        // Controllo che la connessione al DB sia andata a buon fine
        if (strlen($sql->lastError) > 0) {
            echo '{"error" : "' . $sql->lastError . '"}';
            $log->lwrite('[ERRORE] - ' . $sql->lastError);
            if ($sql->connected) {
                $sql->closeConnection();
            }
            http_response_code(400);
            return;
        }

        // verifico che il token passato sia presente nelle sessioni di login
        if (!$sql->tokenCheck($token)) {
            echo '{"error" : "Invalid token 4 !"}';
            $log->lwrite('[ERRORE] - Invalid token ! ');
            if ($sql->connected) {
                $sql->closeConnection();
            }
            // http_response_code(400);
            return;
        }

        if (strlen($sql->lastError) > 0) {
            echo '{"error" : "' . $sql->lastError . '"}';
            $log->lwrite('[ERRORE] - ' . $sql->lastError);
            if ($sql->connected) {
                $sql->closeConnection();
            }
            http_response_code(400);
            return;
        }

        // Eseguo la query
        $result = $sql->exportJSON($query);

        if (strlen($sql->lastError) > 0) {
            echo '{"error" : "' . $sql->lastError . '"}';
            $log->lwrite('[ERRORE] - ' . $sql->lastError);
            if ($sql->connected) {
                $sql->closeConnection();
            }
            http_response_code(400);
            return;
        }

        // Gestisco gli output
        $result_ouput = '{}';
        if (strlen($OUTPUT) > 0) {
            $result_ouput = $sql->exportJSON($OUTPUT);
            $log->lwrite('[INFO] - output - ' . $result_ouput);
        }

        if (strlen($sql->lastError) > 0) {
            echo '{"error" : "' . $sql->lastError . '"}';
            $log->lwrite('[ERRORE] - ' . $sql->lastError);
            if ($sql->connected) {
                $sql->closeConnection();
            }
            http_response_code(400);
            return;
        }

        $sql->closeConnection();

        $resultArray = json_decode($result, true);
        if (array_key_exists("error", $resultArray[0])) {
            echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "error": "' . $resultArray[0]["error"] . '"}';
            if (array_key_exists("code", $resultArray[0])) {
                http_response_code($resultArray[0]["code"]);
            } else {
                http_response_code(400);
            }
            exit;
        } else {
            if ($result == "[0]") $result = "[]";
            $result = '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . '}';

            // query caching
            if (isset(getallheaders()['Caching']) && $config["REDIS"] == "1") {
                $redis = new FD_Redis('127.0.0.1', 6379);
                $cacheKey = $redis->queryCache($token, $process, $params, $result, getallheaders()['Caching']);
                $redis->close();
                header("AAA-Cached: ".$cacheKey);
            }

            echo $result;
        }
    } else {
        echo '{"error" : "Invalid request !"}';
        $log->lwrite('[ERRORE] - Invalid request !');
        http_response_code(400);
    }
} catch (Exception $e) {
    echo '{"error" : "' . $e->getMessage() . '"}';
    $log->lwrite('[ERRORE] - ' . $e->getMessage());
    http_response_code(400);
}
