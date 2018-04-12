<?php

abstract class SG_DB {

    //Variabili
    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $port = "";
    var $conn;               // Connessione al DB

    var $lastError = "";    // Ultimo errore
    var $lastQuery;         // Ultima query (eseguita/richiesta)
    var $result;            // Ultimo risultato
    var $records;           // Numero di record estratti
    var $affected;          // Numero di righe aggiornate
    var $rawResults;        //
    var $arrayedResult;     // Ultimo array di risultati
    var $connected;         //Connesso si/no

    /* *******************
    * Construct
    * *******************/

    function SG_DB(){}

    /* *******************
	 * PUBLIC
	 * *******************/

    abstract public function Connect();

    abstract public function closeConnection();

    abstract public function executeSQL($query);

    abstract public function countRows($query);

    abstract public function exportJSON($query);

}

