<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 30/05/14
 * Time: 14.26
 */

class FD_Url
{


    var $SERVER_PORT;
    var $REMOTE_ADDR;
    var $SERVER_NAME;
    var $INFO;
    var $BROWSER;
    var $DOCUMENT_ROOT;
    var $DEVICE;
    var $MOBILE_TYPE;
    var $IP_ADDRESS;
    var $LOCALE = "";
    var $defLocale = 'it-IT';

   /*
    'GATEWAY_INTERFACE',
    'SERVER_ADDR',
    'SERVER_NAME',
    'SERVER_SOFTWARE',
    'SERVER_PROTOCOL',
    'REQUEST_METHOD',
    'REQUEST_TIME',
    'REQUEST_TIME_FLOAT',
    'QUERY_STRING',
    'DOCUMENT_ROOT',
    'HTTP_ACCEPT',
    'HTTP_ACCEPT_CHARSET',
    'HTTP_ACCEPT_ENCODING',
    'HTTP_ACCEPT_LANGUAGE',
    'HTTP_CONNECTION',
    'HTTP_HOST',
    'HTTP_REFERER',
    'HTTP_USER_AGENT',
    'HTTPS',
    'REMOTE_HOST',
    'REMOTE_PORT',
    'REMOTE_USER',
    'REDIRECT_REMOTE_USER',
    'SCRIPT_FILENAME',
    'SERVER_ADMIN',
    'SERVER_SIGNATURE',
    'PATH_TRANSLATED',
    'SCRIPT_NAME',
    'REQUEST_URI',
    'PHP_AUTH_DIGEST',
    'PHP_AUTH_USER',
    'PHP_AUTH_PW',
    'AUTH_TYPE',
    'PATH_INFO',
    'ORIG_PATH_INFO'
    */

    function __constructor($defLocale="it")
    {
        $this->SERVER_PORT = $_SERVER['SERVER_PORT'];
        $this->SERVER_NAME = $_SERVER['SERVER_NAME'];
        $this->REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        $this->DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        $this->INFO = $_SERVER['HTTP_USER_AGENT'];

        //Trovo il browser
        if(stristr($this->INFO,'MSIE') == TRUE)
        {
            $this->BROWSER = "Internet Explorer";
        } else if(stristr($this->INFO,'Firefox') == TRUE)
        {
            $this->BROWSER = "Mozilla Firefox";
        } else if(stristr($this->INFO,'Chrome') == TRUE)
        {
            $this->BROWSER = "Google Chrome";
        } else{
            $this->BROWSER = "Non definito";
        }

        //Trovo il tipo di Device
        if( strstr($this->INFO,'Android') ||  strstr($this->INFO,'webOS') || strstr($this->INFO,'iPhone') || strstr($this->INFO,'iPod') || strstr($this->INFO,'iPad'))
        {
            $this->DEVICE = "Mobile";
            if(strstr($this->INFO,'Android'))
            {
                $this->MOBILE_TYPE="Android";
            } else if(strstr($this->INFO,'iPhone') || strstr($this->INFO,'iPod') || strstr($this->INFO,'iPad'))
            {
                $this->MOBILE_TYPE="iOS";
            } else
            {
                $this->MOBILE_TYPE="Altro";
            }
        } else
        {
            $this->DEVICE = "Web";
        }

        $this->IP_ADDRESS = $_SERVER['REMOTE_ADDR'];


        //Prende lingua browser
        /*
        $supportedLangs = array('it-IT', 'en');

        $languages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

        foreach($languages as $lang)
        {
            if(in_array($lang, $supportedLangs))
            {
                $this->LOCALE = substr($lang,0,2);
                break;
            }
        }
        if(strlen($this->LOCALE) == 0){
            $this->LOCALE = $defLocale;
        }
        */

    }

}
