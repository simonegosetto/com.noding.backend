<?php

/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 07/06/2016
 * Time: 00:56
 */

/*

Notifiche per Volontari, dipendenti e Staff

- turno (24 ore prima e 1 ora prima) (5)
- corso (24 ore prima e 1 ora prima) (6)
- news riservate (1)
- quando viene accettato il collegamento da parte dell'associazione (2)


Notifiche per Associazione
- quando qualcuno commenta (3)
- quando un volontario chiede il "collegamento" (4)

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
        curl_setopt($curl, CURLOPT_POST, 1); //"4f49e745-f0f7-478f-8454-855b04da52b8"
        if($push->tipo == 1) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Nuovo messaggio privato ricevuto\"}}");
        }else if($push->tipo == 2) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Sei stato accettato da un'associazione !\"}}");
        }else if($push->tipo == 3) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Hai ricevuto un commento ad un post !\"}}");
        }else if($push->tipo == 4) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Un volontario vuole far parte della tua asoociazione !\"}}");
        }else if($push->tipo == 5) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Hai un turno in associazione previsto ".$push["turno"]."\"}}");
        }else if($push->tipo == 5) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"Hai un corso in associazione previsto ".$push["corso"]."\"}}");
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        //echo $response;
    }

}