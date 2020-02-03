<?php

// Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
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

error_reporting(E_ERROR | E_WARNING | E_PARSE);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require("Config/FD_Define.php");
require("WebTools/FD_HTTP.php");
require("WebTools/FD_Logger.php");
require("Dropbox/FD_DropboxAPI.php");

$http = new FD_HTTP();

$log = new FD_Logger(null);

try
{

    if (!isset($_GET["gest"]))
    {
        echo '{"error" : "Invalid request !"}';
        $log->lwrite('[ERRORE] - Invalid request !');
        return;
    }

    $gest = $_GET["gest"];

    if ($gest == 2)
    {
        $data = file_get_contents("php://input");
        $objData = json_decode($data);
        if(property_exists((object) $objData,"action"))
        {
            $action = $objData->action;
        }
        if(property_exists((object) $objData,"token"))
        {
            $token = $objData->token;
        }
    }
    else if ($gest == 1)
    {
        if(isset($_POST["action"]))
        {
            $action = (object)$_POST["action"];
        }
        if(isset($_POST["token"]))
        {
            $token = $_POST["token"];
        }
    }
    else
    {
        if (isset($_GET["id"]))
        {
            $id = $_GET["id"];
        }
    }

    if (isset($action))
    {
        $dp = new FD_DropboxAPI();
        if ((int)$action->mode == DROPBOX::UPLOAD)
        {
            $result = $dp->upload("/".$action->path.$action->id."/".$action->name, file_get_contents($action->data));
            if (strpos($result,"error") !== false)
            {
                $error = json_decode($result, true)["error"]["reason"][".tag"];
                echo '{"error" : "'.$error.'"}';
                $log->lwrite('[ERRORE] - '.$error);
                return;
            }
            else
            {
                $result = json_decode($result, true);
                $data = array(
                    "type" => "1",
                    "token" => $token,
                    "process" => "oXfIlWxyi+Q85qR8Ievvqpv3EHDlJZKqrqYpcoFIduYtWy0tSVYtWy2zGTaQKXHUNHnChMn3qtr4M6n7tSoH3nNq4DfPtNZj2Q@@",
                    "params" => "'".$result['id']."','".$result['name']."','".$result['path_display']."','".$result['path_display']."','".$result['content_hash']."',".$action->id.",'".str_replace('/','',$action->path)."','".$token."'"
                );
                echo $http->Post('https://'.$_SERVER["HTTP_HOST"]."/BackEnd/FD_DataServiceGatewayCrypt.php?gest=1", $data);
                return;
            }
        }
        else if ((int)$action->mode == DROPBOX::DELETE)
        {
            $result = $dp->deletee($action->path);
            if (strpos($result,"error") !== false)
            {
                $error = json_decode($result, true)["error"]["reason"][".tag"];
                echo '{"error" : "'.$error.'"}';
                $log->lwrite('[ERRORE] - '.$error);
                return;
            }
            else
            {
                $data = array(
                    "type" => "1",
                    "token" => $token,
                    "process" => "sLW756Hubbu8KFVwtLbsQpnIzArhmLJYHTJUWBkI1/ctWy0tSVYtWy0LE+5QbgBcq6/e8efFqPTw6vJICJloclPn+hf64OJjng@@",
                    "params" => "'".$action->path."'"
                );
                echo $http->Post('https://'.$_SERVER["HTTP_HOST"]."/BackEnd/FD_DataServiceGatewayCrypt.php?gest=1", $data);
                return;
            }
        }
		else if ((int)$action->mode == DROPBOX::GET)
        {
            $result = $dp->get($action->path);
            if (strpos($result,"error") !== false)
            {
                $error = json_decode($result, true)["error"]["reason"][".tag"];
                echo '{"error" : "'.$error.'"}';
                $log->lwrite('[ERRORE] - '.$error);
                return;
            }
            else
            {
                echo '{"link" : "'.json_decode($result, true)["link"].'"}';
            }
        }
    }

    // download
    if (isset($id))
    {
        // $path = "id:".$id;
        $dp = new FD_DropboxAPI();
        //header("Location: ".$dp->download("id:".$action->id));
        $result = $dp->download($id);
        if (strpos($result,"error") !== false)
        {
            $error = json_decode($result, true)["error"]["path"][".tag"];
            echo '{"error" : "'.$error.'"}';
            $log->lwrite('[ERRORE] - '.$error);
            return;
        }
        else
        {
            $file = json_decode($dp->info($id), true);
            $fp = fopen("Temp/".@date('d_m_Y_H_i_s')."_".$file["name"], 'w');
            fwrite($fp, $result);
            fclose($fp);
            header("Location: Temp/".@date('d_m_Y_H_i_s')."_".$file["name"]);
        }
    }


}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
    $log->lwrite('[ERRORE] - '.$e->getMessage());
}
