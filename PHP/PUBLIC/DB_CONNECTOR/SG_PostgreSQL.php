<?php

final class SG_PostgreSQL extends SG_DB
{


    /* *******************
     * Construct
     * *******************/

    function SG_PostgreSQL()
    {
        $ini_array = parse_ini_file("config.inc.ini");

        $this->port = $ini_array["PG_PORT"];
        $this->hostname = $ini_array["PG_HOSTNAME"];
        $this->username = $ini_array["PG_USERNAME"];
        if(strlen($ini_array["PG_PASSWORD"]) > 0)
        {
            $this->password = $ini_array["PG_PASSWORD"];
        } else
        {
            $this->password = "";
        }
        $this->database = $ini_array["PG_DATABASE"];
        $this->connect();
    }

    /* *******************
     * Private
     * *******************/

    /* *******************
	 * Public
	 * *******************/

    //Connessione al DB
    public function connect()
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

    //Chiusura connessione al DB
    public function closeConnection()
    {
        pg_close($this->conn);
        $this->connected = false;
    }

    //Esecuzione della query
    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        if($this->result = pg_query($this->conn,$query))
        {
            if ($this->result)
            {
                $this->records  = pg_num_rows($this->result);
                $this->affected = pg_num_rows($this->result);
            } else
            {
                $this->records  = 0;
                $this->affected = 0;
            }

            if($this->affected > 0)
            {
                $this->arrayResults();
                return $this->arrayedResult;
            } else
            {
                return true;
            }
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

        return json_encode($this->arrayedResult, JSON_NUMERIC_CHECK );
    }


}
