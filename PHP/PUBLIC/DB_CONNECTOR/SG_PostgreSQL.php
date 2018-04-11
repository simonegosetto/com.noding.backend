<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 20/09/2016
 * Time: 20:45
 */
final class SG_PostgreSQL extends SG_DB
{

    /* *******************
	 * Private
	 * *******************/

    //Costruttore
    function SG_PostgreSQL()
    {
        $ini_array = parse_ini_file("config.inc.ini");

        $this->port = $ini_array["PG_PORT"]);
        $this->hostname = $ini_array["PG_HOSTNAME"];
        $this->username = $ini_array["PG_USERNAME"];
        if(strlen($ini_array["PG_PASSWORD"]) > 0)
        {
            $this->password = $ini_array["password"];
        } else
        {
            $this->password = "";
        }
        $this->database = $ini_array["PG_DATABASE"];
        $this->Connect();
    }

    //Connessione al DB
    private function Connect()
    {
        $this->conn = pg_connect("host=".$this->hostname." port=".$this->port." dbname=".$this->database." user=".$this->username." password=".$this->password);
        if(!$this->conn)
        {
            $this->lastError = 'Nessuna connessione al server';
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

    /* *******************
	 * Pubbliche
	 * *******************/

    //Chiusura connessione al DB
    public function closeConnection()
    {
        pg_close($this->conn);
    }

    //Pulisce il buffer della connessione dalle precedenti query
    public function CleanBufferResults($conn)
    {
        while($conn->more_results()){
            $conn->next_result();
            if($res = $conn->store_result())
            {
                $res->free();
            }
        }
    }

    //Esecuzione della query
    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        if($this->result = pg_query($this->conn,$query))
        {
            if ($this->result)
            {
                $this->affected = pg_fetch_row($this->conn);
            } else
            {
                $this->records  = 0;
                $this->affected = 0;
            }

            if($this->affected > 0)
            {
                $this->arrayResults();
                $this->CleanBufferResults($this->conn);
                return $this->arrayedResult;
            } else
            {
                $this->CleanBufferResults($this->conn);
                return true;
            }
            //echo "Query eseguita correttamente !";
        } else
        {
            $this->lastError = pg_last_error($this->conn);
            return false;
        }
    }

    //Ritorna il numero di righe della query
    public function countRows($query)
    {
        $result = $this->executeSQL($query);
        return $this->records;
    }

    //Singolo array
    public function arrayResult()
    {
        $this->arrayedResult = pg_fetch_assoc($this->result) or die (pg_last_error($this->conn));
        return $this->arrayedResult;
    }

    //Array multiplo
    public function arrayResults()
    {
        if($this->records == 1)
        {
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = pg_fetch_assoc($this->result))
        {
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query)
    {
        $this->executeSQL($query);

        return json_encode($this->arrayedResult);//, JSON_NUMERIC_CHECK );
    }


}
