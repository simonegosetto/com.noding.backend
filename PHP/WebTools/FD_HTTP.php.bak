<?php
/**
 * Created by VSCode.
 * User: Simone
 * Date: 20/07/2018
 * Time: 11:05
 */
final class FD_HTTP {

   function __construct(){}

   //http post
   public function Post($url, $data)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


}
