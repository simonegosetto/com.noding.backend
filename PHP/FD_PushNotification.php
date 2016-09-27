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
    function FD_PushNotification($url,$app_id,$Authorization=''){
        $this->url = $url;
        $this->app_id = $app_id;
        $this->Authorization = $Authorization;
    }

    //Metodo per la richiesta POST
    public function SendOneSignal($data,$push){
        $curl = curl_init($this->url);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$this->Authorization));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        if($push->tipo == 1) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Nuovo messaggio privato ricevuto\"}}");
        }else if($push->tipo == 2) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Sei stato accettato da un'associazione !\"}}");
        }else if($push->tipo == 3) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Hai ricevuto un commento ad un post !\"}}");
        }else if($push->tipo == 4) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Un volontario vuole far parte della tua asoociazione !\"}}");
        }else if($push["tipo"] == 5) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"".ucfirst($push["tipo2"]).": domani alle ".$push["ora"]."\"},\"headings\": {\"en\":\"".$push["associazione"]."\"}}");
        }else if($push["tipo"] == 6) {
            if($push["checklist"] == 0) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"" . $this->app_id . "\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [" . $data . "],\"contents\": {\"en\":\"Sta iniziando il " . $push["tipo2"] . "!\"},\"headings\": {\"en\":\"" . $push["associazione"] . "\"}}");/*delle ".$push["ora"]."\"*/
            }else{
                curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"" . $this->app_id . "\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [" . $data . "],\"contents\": {\"en\":\"Sta iniziando il " . $push["tipo2"] . ", ricordati di compilare la checklist !\"},\"headings\": {\"en\":\"" . $push["associazione"] . "\"}}");/*delle ".$push["ora"]."\"*/
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        //echo $response;
    }

}