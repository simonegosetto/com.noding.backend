<?php

/**
 * Created by Brackets.
 * User: simone
 * Date: 14/02/2018
 * Time: 15:57
 */


/**
*
* TODO
* - valutare vari try/catch per far si che non schianti mai
* - testare il tutto
* - polling continuo per la ricezione della risposta ???
* - commentare per bene le funzioni e capire il funzionamento di alcune
*/

final class FD_SSH
{

    //Parametri di accesso
    var $host;
    var $user;
    var $pass;
    var $port = 22;

    //
    var $connection;
    var $shell_type;
    var $log;


    function __construct($host,$user,$pass,$port = 22)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
    }

    /**
     * @return mixed
     * FUNZIONI PUBBLICHE
     */

    //Connessione al tunnel SSH
    public function connect()
    {
        if (!function_exists('ssh2_connect'))
        {
            throw new Exception("Server doesn't have SSH2 extension!");
        }

        if (!($this->connection = ssh2_connect($this->host, $this->port))) {
            throw new Exception('Cannot connect to serverExpress');
        }
        /*$fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
        if (strcmp($this->ssh_server_fp, $fingerprint) !== 0) {
            throw new Exception('Unable to verify serverExpress identity!');
        } */
        if (!ssh2_auth_password($this->connection, $this->user, $this->pass)) {
            throw new Exception('Autentication rejected by serverExpress');
        }
    }

    public function isConnected ()
    {
        return $this->connection != false ? true : false;
    }

    //Esecuzione comando
    public function executeCommand ($command, $returnString = true)
    {
        if (!isset($command) || $this->connection == false)
            return false;
        $stream = ssh2_exec($this->connection, $command);
        if (is_resource($stream) == false)
            return false;

        if ($returnString == false)
            return $stream;

        stream_set_blocking($stream, true);
        $streamOut = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($stream);
    }

    //Ritorno della fingerprintkey
    public function getServerFingerprint ($flags = SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX)
    {
        if ($this->connection == false) return false;
        return ssh2_fingerprint($this->connection);
    }

    //Ritorno i negoriatemethods
    public function getNegotiatedMethods ()
    {
        if ($this->connection == false) return false;
        return ssh2_methods_negotiated($this->connection);
    }

    //Disconnessione
    public function disconnect ()
    {
        ssh2_disconnect($this->connection);
        return true;
    }

    //Ritorno l'oggetto connessione
    public function getConnection ()
    {
        return $this->connection;
    }

    /**
     * @return mixed
     * FUNZIONI PRIVATE
     */




}
