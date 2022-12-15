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
    var $MOBILE_TYPE = "";
    var $IP_ADDRESS;
    var $LOCALE = "";
    var $defLocale = 'it-IT';
    var $USER_AGENT;

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

    function __construct($defLocale = "it")
    {
        $this->SERVER_PORT = $_SERVER['SERVER_PORT'];
        $this->SERVER_NAME = $_SERVER['SERVER_NAME'];
        $this->REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        $this->DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        $this->INFO = $_SERVER['HTTP_USER_AGENT'];

        $this->USER_AGENT = $this->parse_user_agent($this->INFO);

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

    /**
     * Get header Authorization
     * */
    function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    function getBearerToken($key = 'Bearer')
    {
        $headers = getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/' . $key . '\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    function parse_user_agent($u_agent = null)
    {
        if (is_null($u_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $u_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                throw new \InvalidArgumentException('parse_user_agent requires a user agent');
            }
        }
        $platform = null;
        $browser = null;
        $version = null;
        $empty = array('platform' => $platform, 'browser' => $browser, 'version' => $version);
        if (!$u_agent) return $empty;
        if (preg_match('/\((.*?)\)/im', $u_agent, $parent_matches)) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
                    (?:\ [^;]*)?
                    (?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);
            $priority = array('Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11');
            $result['platform'] = array_unique($result['platform']);
            if (count($result['platform']) > 1) {
                if ($keys = array_intersect($priority, $result['platform'])) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }
        if ($platform == 'linux-gnu' || $platform == 'X11') {
            $platform = 'Linux';
        } elseif ($platform == 'CrOS') {
            $platform = 'Chrome OS';
        }
        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
                    TizenBrowser|(?:Headless)?Chrome|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|UCBrowser|Puffin|SamsungBrowser|
                    Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
                    Valve\ Steam\ Tenfoot|
                    NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
                    (?:\)?;?)
                    (?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent, $result, PREG_PATTERN_ORDER);
        // If nothing matched, return null (to avoid undefined index errors)
        if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
            if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result)) {
                return array('platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null);
            }
            return $empty;
        }
        if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $u_agent, $rv_result)) {
            $rv_result = $rv_result['version'];
        }
        $browser = $result['browser'][0];
        $version = $result['version'][0];
        $lowerBrowser = array_map('strtolower', $result['browser']);
        $find = function ($search, &$key, &$value = null) use ($lowerBrowser) {
            $search = (array)$search;
            foreach ($search as $val) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if ($xkey !== false) {
                    $value = $val;
                    $key = $xkey;
                    return true;
                }
            }
            return false;
        };
        $key = 0;
        $val = '';
        if ($browser == 'Iceweasel' || strtolower($browser) == 'icecat') {
            $browser = 'Firefox';
        } elseif ($find('Playstation Vita', $key)) {
            $platform = 'PlayStation Vita';
            $browser = 'Browser';
        } elseif ($find(array('Kindle Fire', 'Silk'), $key, $val)) {
            $browser = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif ($find('Kindle', $key, $platform)) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } elseif ($find('OPR', $key)) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } elseif ($find('Opera', $key, $browser)) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($find('Puffin', $key, $browser)) {
            $version = $result['version'][$key];
            if (strlen($version) > 3) {
                $part = substr($version, -2);
                if (ctype_upper($part)) {
                    $version = substr($version, 0, -2);
                    $flags = array('IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows');
                    if (isset($flags[$part])) {
                        $platform = $flags[$part];
                    }
                }
            }
        } elseif ($find(array('IEMobile', 'Edge', 'Midori', 'Vivaldi', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome'), $key, $browser)) {
            $version = $result['version'][$key];
        } elseif ($rv_result && $find('Trident', $key)) {
            $browser = 'MSIE';
            $version = $rv_result;
        } elseif ($find('UCBrowser', $key)) {
            $browser = 'UC Browser';
            $version = $result['version'][$key];
        } elseif ($find('CriOS', $key)) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } elseif ($browser == 'AppleWebKit') {
            if ($platform == 'Android') {
                // $key = 0;
                $browser = 'Android Browser';
            } elseif (strpos($platform, 'BB') === 0) {
                $browser = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($pKey = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser']))) {
            $pKey = reset($pKey);
            $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $pKey);
            $browser = 'NetFront';
        }
        return array('platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null);
    }

    /**
     * Get header Authorization
     * */
    function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    function getBearerToken(): ?string
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

}
