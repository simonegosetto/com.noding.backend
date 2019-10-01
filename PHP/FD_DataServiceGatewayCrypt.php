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
//require("DB/FD_Redis.php");
require("DB/FD_Mysql.php");
require("Tools/FD_Crypt.php");
require("WebTools/FD_Mailer.php");
require("WebTools/FD_Logger.php");
require("WebTools/FD_HTTP.php");
require("PushNotification/FD_PushNotification.php");
require("PushNotification/FD_OneSignal.php");
require("Tools/FD_Random.php");
require("Tools/FD_JWT.php");
if (parse_ini_file("Config/config.inc.ini")["GOOGLE_ENABLED"])
{
    require("Google/FD_GoogleService.php");
}
require("Dropbox/FD_DropboxAPI.php");

//istanzio logger
$log = new FD_Logger(null);
$crypt = new FD_Crypt();

//////////// consultazione del log /////////////////////////
if(isset($_GET["log"]))
{
    $log_file = 'Log/'.$_GET["log"].'.txt';
    if(file_exists($log_file))
    {
        echo str_replace("\n","<br/>",file_get_contents($log_file));
    }
    else
    {
        echo "Non ci sono log del ".$_GET["log"].".";
    }
    return;
}
//////////// ricerca nel log /////////////////
if(isset($_GET["log_search"]))
{
    $search = strtolower($_GET["log_search"]);
    foreach (glob("Log/*.txt") as $file)
    {
        $content = file_get_contents($file);
        if (strpos(strtolower($content), $search) !== false)
        {
            //leggo il file di log
            $handle = fopen($file, "r");
            if ($handle)
            {
                while (($line = fgets($handle)) !== false)
                {
                    //trovo la riga del login
                    if (strpos(strtolower($line), $search) !== false)
                    {
                        echo str_replace($search,"<span style=\"font-weight:bold;background-color: orange\">".$search."</span>",
                                        str_replace(strtoupper($search),"<span style=\"font-weight:bold;background-color: orange\">".strtoupper($search)."</span>",
                                            $line
                                        )
                              )."<br/><br/>";
                    }
                }
                fclose($handle);
            }
            else
            {
                echo "Nessun risultato per la ricerca";
            }
        }
    }
    return;
}
//////////// consultazione della sessione nel log /////////////////
if(isset($_GET["ses"]))
{
    $ses_id = "(".$_GET["ses"].")";
    foreach (glob("Log/*.txt") as $file)
    {
        $content = file_get_contents($file);
        if (strpos($content, $ses_id) !== false)
        {
            //leggo il file di log
            $handle = fopen($file, "r");
            if ($handle)
            {
                while (($line = fgets($handle)) !== false)
                {
                    //trovo la riga del login
                    if (strpos($line, $ses_id) !== false)
                    {
                        echo $line;
                        return;
                    }
                }
                fclose($handle);
            }
            else
            {
                echo "Sessione non trovata";
            }
            return;
        }
    }
    echo "Sessione non trovata";
    return;
}
///////////////////////////////////////////////////////////

if ($_SERVER['SERVER_NAME'] != $crypt->host_decrypted())
{
    echo '{"error" : "Host not enabled !"}';
    $log->lwrite('[DENIED] - Host not enabled ('.$_SERVER['SERVER_NAME'].') !');
    return;
}


if(!isset($_GET["gest"]))
{
    echo '{"error" : "Invalid request !"}';
    $log->lwrite('[ERRORE] - Invalid request !');
    return;
}

try
{
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
        if (isset($_POST["mail"]))
        {
            $mail = $_POST["mail"];
        }
        if (isset($_POST["report"]))
        {
            $report = $_POST["report"];
        }
        if (isset($_POST["push"]))
        {
            $push = $_POST["push"];
        }
        if (isset($_POST["debug"]))
        {
            $debug = $_POST["debug"];
        }
        if (isset($_POST["redis"]))
        {
            $redis = $_POST["redis"];
        }
        if (isset($_POST["google"]))
        {
            $google = $_POST["google"];
        }
        if (isset($_POST["dropbox"]))
        {
            $dropbox = $_POST["dropbox"];
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
        if(property_exists((object) $objData,"mail"))
        {
            $mail = $objData->mail;
        }
        if(property_exists((object) $objData,"report"))
        {
            $report = $objData->report;
        }
        if(property_exists((object) $objData,"push"))
        {
            $push = $objData->push;
        }
        if(property_exists((object) $objData,"debug"))
        {
            $debug = $objData->debug;
        }
        if(property_exists((object) $objData,"redis"))
        {
            $redis = $objData->redis;
        }
        if(property_exists((object) $objData,"google"))
        {
            $google = $objData->google;
        }
        if(property_exists((object) $objData,"dropbox"))
        {
            $dropbox = $objData->dropbox;
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
        if (isset($_GET["mail"]))
        {
            $mail = $_GET["mail"];
        }
        if (isset($_GET["report"]))
        {
            $report = $_GET["report"];
        }
        if (isset($_GET["push"]))
        {
            $push = $_GET["push"];
        }
        if (isset($_GET["debug"]))
        {
            $debug = $_GET["debug"];
        }
        if (isset($_GET["redis"]))
        {
            $redis = $_GET["redis"];
        }
        if (isset($_GET["google"]))
        {
            $google = $_GET["google"];
        }
        if (isset($_GET["dropbox"]))
        {
            $dropbox = $_GET["dropbox"];
        }
        if (isset($_GET["redirect"]))
        {
            $redirect = $_GET["redirect"];
        }
    }

    /*if(parse_ini_file("Config/config.inc.ini")["REDIS_ENABLED"])
    {
        $redis_conn = new FD_Redis("",null);
    }
    */

    //Prendo il token di sessione dell'utente e controllo che sia valido
    $jwt = new FD_JWT();
    if(strlen($token)>0)
    {
        $keyRequest = $jwt->decode($token,strtolower(md5_file("Config/esatto.mp3"))); //ritorna il payload
        if(strlen($keyRequest) == 0)
        {
            echo '{"error" : "Invalid token 1 !", "debug": ' . $debug_result . '}}';
            $log->lwrite('[DENIED] - Invalid token !');
            return;
        }
    }    else
    {
        echo '{"error" : "Invalid token 2 !", "debug": ' . $debug_result . '}}';
        $log->lwrite('[DENIED] - Invalid token !');
        return;
    }

    $log->lwrite('[INFO] - token - <a href="?ses='.$token.'" target="_blank">'.$token.'</a>');

    if(strlen($keyRequest) == 0)
    {
        echo '{"error" : "Invalid token 3 !", "debug": ' . $debug_result . '}}';
        $log->lwrite('[ERRORE] - Invalid token !');
        return;
    }

    // gestione servizi google
    if(isset($google) && parse_ini_file("Config/config.inc.ini")["GOOGLE_ENABLED"])
    {
        if($google->mode == GOOLE_SERVICE_ACTION_MODE::BEFORE_DB_CALL)
        {
            $log->lwrite('[INFO] - google service - '.$google->action);
            $google_service = new FD_GoogleService();
            $google_service->engine($google->action,$google->params);
            return;
        }
    }

    if(strlen($process) == 0)
    {
        echo '{"error" : "Invalid process !", "debug": ' . $debug_result . '}}';
        $log->lwrite('[ERRORE] - Invalid process !');
        return;
    }

    if(strlen($type) == 0)
    {
        echo '{"error" : "Invalid type !", "debug": ' . $debug_result . '}}';
        $log->lwrite('[ERRORE] - Invalid type !');
        return;
    }

    $debug_result .= '{"token" : "'.$token.'"';

    $random = new FD_Random();
    $query = '';

    // Gestione invio mail
    if(isset($mail)) 
    {
        $debug_result .= ',"mail" : "'.(string)$mail.'"';

        $mailer = new FD_Mailer();
        $mailer->SendMail($mail->gestione,$mail);
        return;
    }

    // Gestione Dropbox
    if (isset($dropbox))
    {
        $dp = new FD_DropboxAPI();
        if ($dropbox->mode == DROPBOX::UPLOAD)
        {

        }
        else if ($dropbox->mode == DROPBOX::DOWNLOAD)
        {
            header("Location: ".$dp->download("id:".$dropbox->id));
            return;
        }
    }

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
            echo '{"error" : "'.$sql->lastError.'", "debug": ' . $debug_result . '}}';
            $log->lwrite('[ERRORE] - '.$sql->lastError.' - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }

        //verifico che il token passato sia presente nelle sessioni di login
        /*if(!$sql->tokenCheck($token))
        {
            echo '{"error" : "Invalid token 4 !", "debug": ' . $debug_result . '}}';
            $log->lwrite('[ERRORE] - Invalid token ! - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }*/

        if(strlen($sql->lastError) > 0)
        {
            echo '{"error" : "'.$sql->lastError.'", "debug": ' . $debug_result . '}}';
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
            echo '{"error" : "'.$sql->lastError.'", "debug": ' . $debug_result . '}}';
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
            echo '{"error" : "'.$sql->lastError.'", "debug": ' . $debug_result . '}}';
            $log->lwrite('[ERRORE] - '.$sql->lastError.' - '.$debug_result);
            if($sql->connected)
            {
                $sql->closeConnection();
            }
            return;
        }

        //Possibile integrazione del polimorgismo oppure della gestione del provider
        //Se devo mandare delle notifiche push prendo il recorset che mi fornisce l'sql e lo ciclo
        if(isset($push))
        {
            $debug_result .= ',"push" : "'.(string)$push.'"';

            $pushNotification = new FD_OneSignal('https://onesignal.com/api/v1/notifications','5683f6e0-4499-4b0e-b797-0ed2a6b1509b','MjhlZTJmNGItMWQ1YS00NTAzLTljZTMtZmNlNTZiNzQzMDQz');
            $array_push = json_decode($result, true);
            $array_push_length = count($array_push);
            if($array_push_length > 0)
            {
                $ids = [];
                if (isset($array_push["device_id"]))
                {
                    $ids[0] = '"'.$array_push["device_id"].'"';
                }
                else
                {
                    for ($i = 0; $i < $array_push_length; $i++)
                    {
                        $ids[$i] = '"'.$array_push[$i]["device_id"].'"';
                    }
                }
                $app = implode(",", $ids);
                $pushNotification->Send($app,$push);
            }
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
                    "data_object" => json_decode($result,true)
                );
                Header("Location: ReportService/FD_ReportService.php");
            }
            else if(isset($redirect))
            {
                Header("Location: ".$redirect);
            }
            else
            {
                //$log->lwrite('[INFO] - result - '.$result);
                if($result == "[0]") $result = "[]";
                if(isset($debug))
                {
                    /*if(parse_ini_file("Config/config.inc.ini")["REDIS_ENABLED"] && $redis)
                    {
                        $redis_conn->set($redis_key,'{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "debug": ' . $debug_result . '}}');
                        $log->lwrite('[REDIS] - SALVATAGGIO QUERY');
                    } */
                    echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "debug": ' . $debug_result . '}}';
                }
                else
                {
                    /*if(parse_ini_file("Config/config.inc.ini")["REDIS_ENABLED"] && $redis)
                    {
                        $redis_conn->set($redis_key,'{"recordset" : ' . $result . ',"output" : ' . $result_ouput . ', "debug": ' . $debug_result . '}}');
                        $log->lwrite('[REDIS] - SALVATAGGIO QUERY');
                    }*/
                    echo '{"recordset" : ' . $result . ',"output" : ' . $result_ouput . '}';
                }
            }

            if(isset($google) && parse_ini_file("Config/config.inc.ini")["GOOGLE_ENABLED"])
            {
                if($google->mode == GOOLE_SERVICE_ACTION_MODE::AFTER_DB_CALL)
                {
                    $log->lwrite('[INFO] - google service - '.$google->action);
                    $google_service = new FD_GoogleService();
                    $event_id = $google_service->engine($google->action,json_decode($result, true));
                    
                    if(!is_null($event_id))
                    {
                        $log->lwrite('[INFO] - google calendar - evento id: '.$event_id);
                        // aggiorno l'id dell'evento
                        $data = array(
                            "type" => "1",
                            "token" => $token,
                            "process" => "ye0USzs1oueSkQMa+9U5i4XfnzPAmo26kC+m4K2D6YstWy0tSVYtWy3lyPgdmkV/FOoX9YejmAyvSG3q7lxKqd0Uny89oXPQhg@@",
                            "params" =>  json_decode($result, true)[0]["id"] . ",'" . $event_id ."'"
                        );
                        $http->Post($_SERVER["HTTP_REFERER"].explode("/",$_SERVER['REQUEST_URI'])[1]."/FD_DataServiceGatewayCrypt.php?gest=1",$data);
                    }
                }
            }


        }
    }
    else
    {
        echo '{"error" : "Invalid request !", "debug": ' . $debug_result . '}}';
        $log->lwrite('[ERRORE] - Invalid request ! - '.$debug_result);
    }
}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
    $log->lwrite('[ERRORE] - '.$e->getMessage());
}


