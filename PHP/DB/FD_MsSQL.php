<?php

final class FD_MsSQL extends FD_DB
{


    /* *******************
	 * Private
	 * *******************/

    //Costruttore
    function FD_MsSQL()
    {
        $this->key = strtolower(md5_file("../Config/esatto.mp3"));
        $ini_array = parse_ini_file("../Config/config.inc.ini");

        $this->hostname = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["hostname"]),$this->key)));
        $this->username = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["username"]),$this->key)));
        if(strlen($ini_array["password"]) > 0)
        {
            $this->password = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["password"]),$this->key)));
        } else
        {
            $this->password = "";
        }
        $this->database = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["database"]),$this->key)));
        $this->Connect();
    }

    //Connessione al DB
    private function Connect()
    {
        $this->conn = mssql_connect($this->hostname, $this->username, $this->password);
        if(!$this->conn)
        {
            $this->lastError = 'Nessuna connessione al server: ' . mssql_get_last_message   ().PHP_EOL;
            $this->connected = false;
            return false;
        }

        if(!$this->UseDB($this->database))
        {
            $this->lastError = 'Nessun DB selezionato: ' . mssql_get_last_message().PHP_EOL;
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
        mssql_close($this->conn);
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
        if($this->result = mssql_query($this->conn,$query))
        {
            if ($this->result)
            {
                $this->affected = mssql_num_rows($this->conn);
                $this->records  = @mssql_num_rows($this->result);
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
            $this->lastError = mssql_get_last_message($this->conn);
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
        $this->arrayedResult = mssql_fetch_assoc($this->result) or die (mssql_get_last_message($this->conn));
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
        while ($data = mssql_fetch_assoc($this->result))
        {
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    //Seleziona il DB interessato (di dafault non importa)
    public function UseDB($db)
    {
        if(strlen($this->database) > 0)
        {
            if(!mssql_select_db($this->conn,$db))
            {
                $this->lastError = 'Nessun DB selezionato: ' . mssql_get_last_message($this->conn);
                return false;
            } else
            {
                return true;
            }
        } else
        {
            if(!mssql_select_db($this->conn,$db))
            {
                $this->lastError = 'Nessun DB selezionato: ' . mssql_get_last_message($this->conn);
                return false;
            } else
            {
                return true;
            }
        }
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
