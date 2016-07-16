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
include "FD_Crypt.php";

if(isset($argv)) {
    $keyRequest = $argv[2];
    $database = $argv[1];
    $suffix = $argv[3];
    $query = $argv[4];
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

    $crypt = new FD_Crypt();

    $ini_array = parse_ini_file("config.inc_volontapp.ini");
    $hostname = str_replace(" ","",trim($crypt->decrypt(str_replace("@","=",$ini_array["hostname"]),strtolower(md5_file("esatto.mp3")))));
    $username = str_replace(" ","",trim($crypt->decrypt(str_replace("@","=",$ini_array["username"]),strtolower(md5_file("esatto.mp3")))));
    if(strlen($ini_array["password"]) > 0){
        $password = str_replace(" ","",trim($crypt->decrypt(str_replace("@","=",$ini_array["password"]),strtolower(md5_file("esatto.mp3")))));
    }else{
        $password = "";
    }
    $database = str_replace(" ","",trim($crypt->decrypt(str_replace("@","=",$ini_array["database"]),strtolower(md5_file("esatto.mp3")))));

    $conn = mysqli_connect($hostname, $username, $password, $database);
    mysqli_select_db($conn,$database);

    $result = mysqli_query($conn,$query);

    if ($result) {
        $affected = mysqli_affected_rows($conn);
        $records  = @mysqli_num_rows($result);
    } else {
        $records  = 0;
        $affected = 0;
    }

    if($records > 0){
        $arrayedResult = array();
        while ($data = mysqli_fetch_assoc($result)){
            $arrayedResult[] = $data;
        }
        json_encode($arrayedResult);
    }else{
        mysqli_close($conn);
    }

    mysqli_close($conn);


    //push
    $pushNotification = new FD_PushNotification('https://onesignal.com/api/v1/notifications','5683f6e0-4499-4b0e-b797-0ed2a6b1509b','MjhlZTJmNGItMWQ1YS00NTAzLTljZTMtZmNlNTZiNzQzMDQz');
    $array_push = json_decode($result, true);
    $array_push_length = count($array_push);
    if($array_push_length > 0) {
        $ids = [];
        if (isset($array_push["device_id"])) {
            $ids[0] = '"'.$array_push["device_id"].'"';
        } else {
            for ($i = 0; $i < $array_push_length; $i++) {
                $ids[$i] = '"'.$array_push[$i]["device_id"].'"';
            }
        }
        $app = implode(",", $ids);
        $pushNotification->SendOneSignal($app,$push);
    }

}