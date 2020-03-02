// https://<dc>.api.mailchimp.com/3.0/
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
    var $ClientID;
    var $ClientSecret;

    function __construct()
    {
        $ini_array = parse_ini_file("config.ini");
        $this->AppKey = $ini_array["appkey"];
        $this->$ClientID = $ini_array["clientid"];
        $this->$ClientSecret = $ini_array["clientsecret"];
        //$this->AppInfo = new Dropbox\AppInfo($key, $secret);
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
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Content-Type: application/json';
        $data = '{"path": "'.$path.'", "include_media_info": false, "include_deleted": false, "include_has_explicit_shared_members": false}';
        return $this->_request('https://api.dropboxapi.com/2/files/get_metadata', $header, $data);
    }

}
