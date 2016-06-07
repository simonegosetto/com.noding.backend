<?php

/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 07/06/2016
 * Time: 00:56
 */
class FD_PushNotification
{
    var $url = '';
    var $app_id = '';
    var $Authorization = '';

    //Costruttore
    function FD_PushNotifications($url,$app_id,$Authorization=''){
        $this->url = $url;
        $this->app_id = $app_id;
        $this->Authorization = $Authorization;
    }

    //Metodo per la richiesta POST
    public function SendOneSignal($data,$tipo){
        echo $tipo;
        $curl = curl_init($this->url);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$this->Authorization));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1); //"4f49e745-f0f7-478f-8454-855b04da52b8"
        if($tipo == 1) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\".$this->app_id.\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [" . $data . "],\"contents\": {\"en\":\"Nuovo messaggio privato ricevuto\"}}");
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        echo $response;
    }

}