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

    var $arrayForCrossJoin;

    //Object for crossjoin
    /*public var $prepareForCrossJoin = array (
        'query' => '',
        'fieldID' => '',
        'fieldDesc' => ''
    );*/

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

    abstract public function prepareForCrossJoin($query,$fieldID,$fieldDesc /*ONLY USED IN SLAVE OBJECT (if '*' take all fields)*/);

    public function joinQuery($child)
    {

        //controllo validità dei parametri in ingresso
        if(!$child)
        {
            return "Impossibile eseguire il JOIN CROSS DB perchè l'oggetto passato non è valido";
        }

        //altri controlli volendo...

        //first query
        $first_query = $this->executeSQL($this->arrayForCrossJoin["query"]);

        if(strlen($this->lastError) > 0)
        {
            echo $this->lastError;
            if($this->connected)
            {
                $this->closeConnection();
            }
        }
        else
        {

            //second query
           $second_query = $child->executeSQL($child->arrayForCrossJoin["query"]);

            if(strlen($child->lastError) > 0)
            {
                echo $child->lastError;
                if($child->connected)
                {
                    $child->closeConnection();
                }
            }

            //join of recordsets
            $this->result = '';

            //close all DB connection
            $this->closeConnection();
            $child->closeConnection();
        }


        /*function toJSON()
        {
            return json_encode($result, JSON_NUMERIC_CHECK);
        }*/
    }

}

