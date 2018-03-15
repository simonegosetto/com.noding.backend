<?php

final class FD_SQLite extends FD_DB
{


    /* *******************
	 * Private
	 * *******************/

    //Costruttore
    function FD_SQLite($dbName="")
    {
        $this->hostname = $dbName;
        $this->Connect();
    }

    //Connessione al DB
    private function Connect()
    {
        $this->conn = sqlite_open ($this->hostname);
        if(!$this->conn)
        {
            $this->lastError = 'Nessuna connessione al DB: ' . sqlite_error_string().PHP_EOL;
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

    //Ritorna il valore decriptato
    private function decrypt($encrypted_string, $encryption_key)
    {
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    private function cleanData(&$str)
    {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }


    /* *******************
	 * PUBLIC
	 * *******************/

    //Chiusura connessione al DB
    public function closeConnection()
    {
        sqlite_close($this->conn);
    }

    //Pulisce il buffer della connessione dalle precedenti query
    public function CleanBufferResults($conn)
    {
        while($conn->more_results())
        {
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
        if($this->result = sqlite_query($this->conn,$query))
        {
            if ($this->result)
            {
                $this->affected = sqlite_num_rows($this->conn);
                $this->records  = @sqlite_num_rows($this->result);
            } else
            {
                $this->records  = 0;
                $this->affected = 0;
            }

            if($this->records > 0)
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
            $this->lastError = sqlite_error_string($this->conn);
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
        $this->arrayedResult = sqlite_fetch_array($this->result) or die (sqlite_error_string($this->conn));
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
        while ($data = sqlite_fetch_array($this->result))
        {
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }


    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query)
    {
        $this->executeSQL($query);

        if($this->affected == 1)
        {
            $rows[] = $this->arrayedResult;
        } else
        {
            $rows = $this->arrayedResult;
        }

        return json_encode($rows, JSON_NUMERIC_CHECK);
    }

}
