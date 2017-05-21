<?php

/**
 * Created by PhpStorm.
 * User: simone
 * Date: 21/05/2017
 * Time: 10:45
 */
class FD_CryptoCurrency
{

    var $key;

    function FD_CryptoCurrency($key){
        $this->key = $key;
    }

    /**
     * @return mixed
     * FUNZIONI PUBBLICHE
     */

    //Ritorna il JSON dal servizio "WorldCoinIndex"
    public function getWorldCoinIndex(){ //, $encryption_key) {
        $url = 'https://www.worldcoinindex.com/apiservice/json?key='.$this->key;
        return file_get_contents($url);
    }


    /**
     * @return mixed
     * FUNZIONI PRIVATE
     */

    //Metodo per la richiesta POST al server API
    private function httpPost($url, $data){//, $token){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        if($data != null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

}