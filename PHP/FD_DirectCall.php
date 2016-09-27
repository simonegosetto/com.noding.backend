<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 14/06/2016
 * Time: 00:03
 */

error_reporting(E_ERROR | E_PARSE);

include "FD_Mysql.php";
include "FD_PushNotification.php";

if(isset($argv)) {
    $database = $argv[1];
    $suffix = $argv[2];
    $query = $argv[3];
    $title = $argv[4];
    $tipo = $argv[5];
    $checklist = $argv[6];
}else{
    echo '{"error" : "Invalid request !"}';
    return;
}

if(strlen($database) == 0){
    echo '{"error" : "Invalid database !"}';
    return;
}

if(strlen($query) == 0){
    echo '{"error" : "Invalid query !"}';
    return;
}

if(strlen($query) > 0 && strlen($database) > 0){

    //Inizializzo componente SQL
    $sql = new FD_Mysql(strtolower(md5_file("/var/www/html/FD_Framework/esatto.mp3")),$suffix);
    //Controllo che la connessione al DB sia andata a buon fine
    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    if(!$sql->UseDB($database)){
        echo '{"error" : "Invalid database"}';
        return;
    }

    $result = $sql->exportJSON($query);
    if(strlen($sql->lastError) > 0){
        echo '{"error" : "'.$sql->lastError.'"}';
        if($sql->connected){
            $sql->closeConnection();
        }
        return;
    }

    //push
    $pushNotification = new FD_PushNotification('https://onesignal.com/api/v1/notifications','5683f6e0-4499-4b0e-b797-0ed2a6b1509b','MjhlZTJmNGItMWQ1YS00NTAzLTljZTMtZmNlNTZiNzQzMDQz');
    $array_push = json_decode($result, true);
    $array_push_length = count($array_push);
    if(isset($array_push['id'])){
        $array_push = array($array_push);
    }
    if($array_push_length > 0) {
        for($i = 0;$i<$array_push_length;$i++){
            $push = array('tipo' => $tipo,
                            'tipo2' => $array_push[$i]["Tipo"],
                            'ora' => $array_push[$i]["Ora"],
                            'associazione' => $title,
                            'checklist' => $checklist
            );
            //var_dump($push);
            $pushNotification->SendOneSignal('"'.$array_push[$i]["device_id"].'"',$push);
        }
    }

}