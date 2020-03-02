<?php
/**
 * Created by WebStorm.
 * User: gosetto
 * Date: 21/02/2020
 * Time: 23:22
 */
class FD_MailChimpAPI
{
    var $AppKey;

    function __construct()
    {
        $ini_array = parse_ini_file("config.ini");
        $this->AppKey = $ini_array["appkey"];
    }

    private function _request($url, $header, $file = null)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($file != null)
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $file);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    // https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
    public function info($path)
    {
        $header = array();
        $header[] = "api-key: ".$this->AppKey;
        $header[] = 'Content-Type: application/json';
        $data = '{"email": "'.$path.'"}';
        return $this->_request('https://api.sendinblue.com/v3/', $header, $data);
    }

}
