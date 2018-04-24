<?php

abstract class FD_DB {

    //Variabili
    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $conn;      // Connessione al DB

    var $lastError = "";         // Ultimo errore
    var $lastQuery;         // Ultima query (eseguita/richiesta)
    var $result;            // Ultimo risultato
    var $records;           // Numero di record estratti
    var $affected;          // Numero di righe aggiornate
    var $rawResults;        //
    var $arrayedResult;     // Ultimo array di risultati
    var $key;               // key
    var $connected;         //Connesso si/no

    //Costruttore
    function FD_DB(){}

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

