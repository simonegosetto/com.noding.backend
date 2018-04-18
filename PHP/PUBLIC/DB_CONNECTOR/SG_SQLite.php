<?php

final class SG_SQLite extends SG_DB
{


    /* *******************
    * Construct
    * *******************/

    function SG_SQLite($dbName="")
    {
        $this->hostname = $dbName;
        $this->connect();
    }

    /* *******************
    * Private
    * *******************/

    private function sqlite_open($location)
    {
        $handle = new SQLite3($location);
        return $handle;
    }

    private function sqlite_query($dbhandle,$query)
    {
        $result = $dbhandle->query($query);
        return $result;
    }

    private function sqlite_fetch_array(&$result)
    {
        #Get Columns
        $i = 0;
        while ($result->columnName($i))
        {
            $columns[ ] = $result->columnName($i);
            $i++;
        }

        $resx = $result->fetchArray(SQLITE3_ASSOC);
        return $resx;
    }

    private function sqlite_close($dbhandle)
    {
        $dbhandle->close();
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    //Connessione al DB
    public function connect()
    {
        $this->conn = $this->sqlite_open($this->hostname);
        if(!$this->conn)
        {
            $this->lastError = 'Nessuna connessione al DB: ' . lastErrorMsg();
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

    //Chiusura connessione al DB
    public function closeConnection()
    {
        $this->sqlite_close($this->conn);
        $this->connected = false;
    }


    //Esecuzione della query
    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        $this->result = $this->sqlite_query($this->conn,$query);
    }

    //Ritorna il numero di righe della query
    public function countRows($query)
    {
        $result = $this->executeSQL($query);
        return $this->records;
    }


    //Array multiplo
    public function arrayResults()
    {
        $this->arrayedResult = array();
        $count = 0;
        while ($data = $this->sqlite_fetch_array($this->result))
        {
            $count = $count + 1;
            $this->arrayedResult[] = $data;
        }
        $this->records = $count;
        $this->affected = $count;
        return $this->arrayedResult;
    }


    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query)
    {
        $this->executeSQL($query);
        if($this->result)
        {
            if ($this->result)
            {
                $this->records  = 0;
                $this->affected = 0;
                $this->arrayResults();
            } else
            {
                $this->records  = 0;
                $this->affected = 0;
            }
        } else
        {
            $this->lastError = lastErrorMsg();
            return false;
        }

        $rows = $this->arrayedResult;

        return json_encode($rows, JSON_NUMERIC_CHECK);
    }

    public function prepareForCrossJoin($query,$fieldID,$fieldDesc)
    {
        $this->arrayForCrossJoin = array(
            'query' => $query,
            'fieldID' => $fieldID,
            'fieldDesc' => $fieldDesc // ONLY USED IN SLAVE OBJECT (if '*' take all fields)
        );
    }

}
