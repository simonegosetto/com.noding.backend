<?php
/**
 * Created by PhpStorm.
 * User: gosetto
 * Date: 18/04/2019
 * Time: 09:04
 */
require __DIR__ . '/vendor/autoload.php';

/*
    App key 10xdw9e056r7uzg
    App secret nt5l7z95k199n1l
    Access Token xsJG5lfQUHkAAAAAAAAcJjL_wX_Yqi-6kwIQUcDAHz1GgF2mQJqZhzuNzDYlj75O
 */

class FD_DropboxAPI
{
    var $AppKey;
    var $AppSecret;
    var $AccessToken;
    var $AppInfo;

    function __construct()
    {
        $ini_array = parse_ini_file("config.ini");
        $this->AppKey = $ini_array["appkey"];
        $this->AppSecret = $ini_array["appsecret"];
        $this->AccessToken = $ini_array["accesstoken"];
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

    public function upload($path, $file)
    {
        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Dropbox-API-Arg: {"path": "'.$path.'","mode": "overwrite","autorename": true,"mute": false,"strict_conflict": false}';
        $header[] =  "Content-Type: application/octet-stream";

        return $this->_request('https://content.dropboxapi.com/2/files/upload',$header, $file);
        /*
        curl -X POST https://content.dropboxapi.com/2/files/upload \
        --header "Authorization: Bearer " \
        --header "Dropbox-API-Arg: {\"path\": \"/Homework/math/Matrices.txt\",\"mode\": \"add\",\"autorename\": true,\"mute\": false,\"strict_conflict\": false}" \
        --header "Content-Type: application/octet-stream" \
        --data-binary @local_file.txt

        https://content.dropboxapi.com/2/files/upload

        {
            "path": "/Homework/math/Matrices.txt",
            "mode": "add",
            "autorename": true,
            "mute": false,
            "strict_conflict": false
        }
        */
    }

    // https://www.dropbox.com/developers/documentation/http/documentation#files-download
    public function download($path) {
        ob_clean();
        flush();

        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Content-Type:';
        $header[] = 'Dropbox-API-Arg: {"path": "'.$path.'"}';

        $file = sys_get_temp_dir().'\test.mp3';
        file_put_contents($file,$this->_request('https://content.dropboxapi.com/2/files/download',$header));
        // TODO trovare il modo di aprire il file lato browser
        return $file;
    }
}
