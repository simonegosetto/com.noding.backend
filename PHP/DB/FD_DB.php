<?php

abstract class FD_DB {

    //Variabili
    public $hostname = "";
    public $username = "";
    public $password = "";
    public $database = "";
    public $conn;      // Connessione al DB

    public $lastError = "";         // Ultimo errore
    public $lastQuery;         // Ultima query (eseguita/richiesta)
    public $result;            // Ultimo risultato
    public $records;           // Numero di record estratti
    public $affected;          // Numero di righe aggiornate
    public $rawResults;        //
    public $arrayedResult;     // Ultimo array di risultati
    public $key;               // key
    public $connected;         //Connesso si/no

    //Costruttore
    function __constructor(){}

    /* *******************
	 * PUBLIC
	 * *******************/

    abstract public function Connect();

    public function decrypt($encrypted_string, $encryption_key) {}

    abstract public function closeConnection();

    public function CleanBufferResults($conn){}

    abstract public function executeSQL($query);

    public function countRows($query){}

    public function arrayResult(){}

    public function exportXML($query){}

    public function exportCSV($query){}

    abstract public function exportJSON($query);

}

