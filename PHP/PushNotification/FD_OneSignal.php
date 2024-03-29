<?php

/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 07/06/2016
 * Time: 00:56
 */

final class FD_OneSignal extends FD_PushNotification
{
    var $url = '';
    var $app_id = '';
    var $Authorization = '';

    //Costruttore
    function __construct($url,$app_id,$Authorization='')
    {
        $this->url = $url;
        $this->app_id = $app_id;
        $this->Authorization = $Authorization;
    }

    //Metodo per la richiesta POST
    public function Send($data,$push)
    {
        $curl = curl_init($this->url);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$this->Authorization));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"app_id\":\"".$this->app_id."\",\"isIos\": true,\"isAndroid\":false, \"include_player_ids\": [".$data."],\"contents\": {\"en\":\"".$object."\"}}");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        //echo $response;
    }

}
