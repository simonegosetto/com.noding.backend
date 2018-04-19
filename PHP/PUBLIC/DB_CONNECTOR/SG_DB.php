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

    public function prepareForCrossJoin($query,$fieldID,$fieldDesc)
    {
        $this->arrayForCrossJoin = array(
            'query' => $query,
            'fieldID' => $fieldID,
            'fieldDesc' => $fieldDesc // ONLY USED IN CHILD OBJECT (if '*' take all fields)
        );
    }

    public function executeCrossQuery($child)
    {

        //controllo validità dei parametri in ingresso
        if (!$child)
        {
            return "Impossibile eseguire il JOIN CROSS DB perchè l'oggetto passato non è valido";
        }

        //altri controlli volendo...

        //first query
        $first_query = $this->exportJSON($this->arrayForCrossJoin["query"]);

        if (strlen($this->lastError) > 0)
        {
            echo $this->lastError;
            if ($this->connected)
            {
                $this->closeConnection();
            }
        }
        else
        {

            //second query
            $second_query = $child->exportJSON($child->arrayForCrossJoin["query"]);

            if (strlen($child->lastError) > 0)
            {
                echo $child->lastError;
                if ($child->connected)
                {
                    $child->closeConnection();
                }
            }

            //join of recordsets
            $a = json_decode($first_query, true);
            $b = json_decode($second_query, true);
            //var_dump($a) . "\n" . var_dump($b);

            for ($i = 0; $i < count($a); $i++)
            {
                //check if the property of master array exist
                if (isset($a[$i][$this->arrayForCrossJoin["fieldID"]]) && array_key_exists($this->arrayForCrossJoin["fieldID"], $a[$i]))
                {
                    $value_a = $a[$i][$this->arrayForCrossJoin["fieldID"]];
                    // now check if the child property exist for join
                    if (array_search($value_a, array_column($b,$child->arrayForCrossJoin["fieldID"])))
                    {
                        if ($child->arrayForCrossJoin["fieldDesc"] == "*")
                        {
                            $a[$i] = array_merge($a[$i], $b[array_search($value_a, array_column($b,$child->arrayForCrossJoin["fieldID"]))]);
                        }
                        else
                        {
                            $a[$i][$child->arrayForCrossJoin["fieldDesc"]] = $b[array_search($value_a, array_column($b,$child->arrayForCrossJoin["fieldID"]))][$child->arrayForCrossJoin["fieldDesc"]];
                        }
                    }
                    else
                    {
                        echo "The fieldID specified in the child connection for join not exist !";
                        //close all DB connection
                        $this->closeConnection();
                        $child->closeConnection();
                        return;
                    }
                }
                else
                {
                    echo "The fieldID specified in the master connection not exist !";
                    //close all DB connection
                    $this->closeConnection();
                    $child->closeConnection();
                    return;
                }
            }

            $this->result = $a;
            return $this->result;
        }


        function toJSON()
        {
            return json_encode($_GLOBAL['result'], JSON_NUMERIC_CHECK);
        }
    }

}

