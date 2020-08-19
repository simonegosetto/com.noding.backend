<?php
/**
 * Created by PhpStorm.
 * User: gosetto
 * Date: 18/04/2019
 * Time: 09:04
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

    // https://www.dropbox.com/developers/documentation/http/documentation#files-upload
    public function upload($path, $file)
    {
        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Dropbox-API-Arg: {"path": "'.$path.'","mode": "overwrite","autorename": true,"mute": false,"strict_conflict": false}';
        $header[] =  "Content-Type: application/octet-stream";

        return $this->_request('https://content.dropboxapi.com/2/files/upload', $header, $file);
    }

    // https://www.dropbox.com/developers/documentation/http/documentation#files-download
    public function download($path)
    {
        ob_clean();
        flush();

        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Content-Type:';
        $header[] = 'Dropbox-API-Arg: {"path": "'.$path.'"}';

        return $this->_request('https://content.dropboxapi.com/2/files/download', $header);
    }

    // https://www.dropbox.com/developers/documentation/http/documentation#files-delete
    public function deletee($path)
    {
        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Content-Type: application/json';
        $data = '{"path": "'.$path.'"}';
        return $this->_request('https://api.dropboxapi.com/2/files/delete_v2', $header, $data);
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
	
	// https://www.dropbox.com/developers/documentation/http/documentation#files-get_temporary_link
	public function get($path)
	{
		$header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = 'Content-Type: application/json';
        $data = '{"path": "'.$path.'"}';
        return $this->_request('https://api.dropboxapi.com/2/files/get_temporary_link', $header, $data);
	}

	// https://www.dropbox.com/developers/documentation/http/documentation#files-get_preview
    public function preview($path)
    {
        $header = array();
        $header[] = "Authorization: Bearer ".$this->AccessToken;
        $header[] = "Dropbox-API-Arg: {"path": "'.$path.'"}";
        return $this->_request('https://content.dropboxapi.com/2/files/get_preview', $header);
    }
}
