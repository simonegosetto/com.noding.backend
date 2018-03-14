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
    var $validatedRequest;  //Richiesta al server validata si/no
    var $connected;         //Connesso si/no

    //Costruttore
    function FD_DB($keyRequest="",$suffix=""){}

    /* *******************
	 * Private
	 * *******************/

    abstract private function Connect(){}

    private function decrypt($encrypted_string, $encryption_key) {}

    private function SecureData($data, $types){}

    private function cleanData(&$str) {}

    private function CleanData_test($data, $type = ''){}

    /* *******************
	 * PUBLIC
	 * *******************/

    abstract public function closeConnection(){}

    public function CleanBufferResults($conn){}

    public function CheckDB($token,$db){}

    abstract public function executeSQL($query){}

    public function countRows($query){}

    public function arrayResult(){}

    public function UseDB($db){}

    public function exportXML($query){}

    public function exportCSV($query){}

    abstract public function exportJSON($query){}

    public function exportXLS($query){}

}

