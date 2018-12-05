<?php
/**
 * Created by VS Code.
 * User: Simone
 * Date: 24/11/18
 * Time: 19.01
 */

require __DIR__ . '/vendor/autoload.php';
//require("../WebTools/FD_Logger.php");

final class FD_GoogleService
{

    var $client;
    var $service;
    var $access_token;
    var $code;
    var $error = "";

    //istanzio logger
    //var $log = new FD_Logger(null);

    function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Costo Facile');
        //$this->client->setScopes(Google_Service_Calendar::CALENDAR);
        $this->client->setScopes(array(
            Google_Service_Calendar::CALENDAR,
            Google_Service_Oauth2::USERINFO_EMAIL
            ));
        $this->client->setAuthConfig('Google/credentials.json');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');  
        $this->client->setRedirectUri('https://' . $_SERVER['HTTP_HOST']);
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    public function engine($action,$params)
    {
        if($action == GOOLE_SERVICE_ACTION::AUTHENTICATION_URL_GET)
        {
            echo '{"url":"'.$this->_authorization_url_get().'"}';
        }
        else if($action == GOOLE_SERVICE_ACTION::AUTHENTICATION_CODE_SET)
        {
            echo $this->_authorization_token_set($params->code);
        }
        else if($action == GOOLE_SERVICE_ACTION::USER_INFO_GET)
        {
            echo '{"info":"'.$this->_user_info_get().'"}';
        }
        else if($action == GOOLE_SERVICE_ACTION::CALENDAR_GEST)
        {
            return $this->_calendar_event_gest($params);
        }   
    }

    /* *******************
	 * Private
	 * *******************/

    private function _authorization_token_set($auth_code)
    {
        $tokenPath = 'Google/token.json';
        $this->access_token = $this->client->fetchAccessTokenWithAuthCode($auth_code);
        $this->client->setAccessToken($this->access_token);
        
        // Check to see if there was an error.
        if (array_key_exists('error', $this->access_token)) 
        {
            $this->error = (join(', ', $this->access_token));
            return;
        }

        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) 
        {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        
        return $this->access_token;
    }

    private function _authorization_url_get()
    {
        return $this->client->createAuthUrl();
    }

    private function _user_info_get()
    {
        if($this->_access_token_get()) 
        {
            $oauth = new Google_Service_Oauth2($this->client);
            return $oauth->userinfo_v2_me->get()->email;
        }
    }

    private function _access_token_get()
    {
        //controllo esistenza token
        $token_path = 'Google/token.json';
        if (file_exists($token_path)) 
        {
            $access_token = json_decode(file_get_contents($token_path), true);
            $this->client->setAccessToken($access_token);
        }
        else
        {
            $this->error = "Token non trovato !";
            return false;
        }

        //controllo scadenza token
        if ($this->client->isAccessTokenExpired()) 
        {
            if ($this->client->getRefreshToken()) 
            {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } 
            else 
            {
                $this->error = "Token scaduto !";
                return false;
            }

            //mi salvo il token rinnovato nel file
            if (!file_exists(dirname($token_path))) {
                mkdir(dirname($token_path), 0700, true);
            }
            file_put_contents($token_path, json_encode($this->client->getAccessToken()));
        }
        return true;
    }

    /**
     * 0 -> id
     * 1 -> descrizione
     * 4 -> indirizzo
     * 5 -> note
     * 7 -> data
     */
    private function _calendar_event_gest($params)
    {
        if($this->_access_token_get()) 
        {

            //istanzio il servizio
            $this->service = new Google_Service_Calendar($this->client);

            //per ora prendo il calendario principale dell'account
            $calendar = 'primary';

            $event_id = $params[0]["google_event_id"];
            //echo "prima:".$event_id."\n";
            if (strlen($event_id) > 0 && $event_id > 0) 
            {
                //echo "entra in edit:".$event_id."\n";
                $event = $this->service->events->get($calendar, $event_id);

                if($event->getStatus() != "cancelled")
                {
                    //echo "passa il controllo dello stato:".$event_id."\n";
                    $event->setSummary("Costo Facile - ".$params[0]["descrizione"]);
                    $event->setLocation($params[0]["indirizzo"]);
                    $event->setDescription($params[0]["note"]);
                    $event->setStart(new Google_Service_Calendar_EventDateTime(array(
                        'date' => $params[0]["data_pagamento"],//'T00:00:00+01:00',//'2018-11-30T09:00:00+01:00',
                        'timeZone' => 'Europe/Rome'
                    )));
                    $event->setEnd(new Google_Service_Calendar_EventDateTime(array(
                        'date' => $params[0]["data_pagamento"],//.'T00:00:00+01:00',//'2018-11-30T09:00:00+01:00',
                        'timeZone' => 'Europe/Rome'
                    )));

                    $event = $this->service->events->update($calendar, $event->getId(), $event);
                    //echo "evento aggiornato:".$event_id."\n";
                }
            } 
            else 
            {
                //echo "entra in insert:".$event_id."\n";
                $event = new Google_Service_Calendar_Event(array(
                    'summary' => "Costo Facile - ".$params[0]["descrizione"],
                    'location' => $params[0]["indirizzo"],
                    'description' => $params[0]["note"],
                    'start' => array(
                        'date' => $params[0]["data_pagamento"],//.'T00:00:00+01:00',//'2018-11-30T09:00:00+01:00',
                        'timeZone' => 'Europe/Rome'
                    ),
                    'end' => array(
                        'date' => $params[0]["data_pagamento"],//.'T00:00:00+01:00',//'2018-11-30T17:00:00+01:00',
                        'timeZone' => 'Europe/Rome'
                    )/*,
                'recurrence' => array(
                  'RRULE:FREQ=DAILY;COUNT=2'
                ),
                'attendees' => array(
                  array('email' => 'lpage@example.com'),
                  array('email' => 'sbrin@example.com'),
                ),
                'reminders' => array(
                  'useDefault' => FALSE,
                  'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 10),
                  ),
                ),*/
                ));
                $event = $this->service->events->insert($calendar, $event);
                //echo "evento inserito:".$event_id."\n";
            }

            return $event->id;
        }
    }


}