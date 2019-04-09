<?php

abstract class SG_DB {

    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $conn;

    var $lastError = "";
    var $lastQuery;
    var $result;
    var $records;
    var $affected;
    var $rawResults;
    var $arrayedResult;
    var $key;
    var $connected;

    function __construct(){}

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

