<?php

final class SG_MsSQL extends SG_DB
{


    /* *******************
	 * Construct
	 * *******************/

    function SG_MsSQL()
    {
        $ini_array = parse_ini_file("config.inc.ini");

        $this->hostname = $ini_array["MS_HOSTNAME"];
        $this->username = $ini_array["MS_USERNAME"];
        if(strlen($ini_array["MS_PASSWORD"]) > 0)
        {
            $this->password = $ini_array["MS_PASSWORD"];
        } else
        {
            $this->password = "";
        }
        $this->database = $ini_array["MS_DATABASE"];
        $this->connect();
    }

     /* *******************
	 * Private
	 * *******************/

    private function GetError()
    {
        if( ($errors = sqlsrv_errors() ) != null)
        {
            return $errors[count($errors)-1][ 'message'];
        }
        else
        {
            return "";
        }
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    //Connessione al DB
    public function connect()
    {
        $this->conn = sqlsrv_connect($this->hostname, array("Database"=>$this->database, "UID"=>$this->username, "PWD"=>$this->password, "CharacterSet" => "UTF-8"));
        if(!$this->conn)
        {
            $this->lastError = 'Nessuna connessione al server: ' . $this->GetError();
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

    //Chiusura connessione al DB
    public function closeConnection()
    {
        sqlsrv_close($this->conn);
    }

    //Esecuzione della query
    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        $params = array();
        $options =  array( "Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED );
        if($this->result = sqlsrv_query($this->conn,$query,$params,$options))
        {
            if ($this->result)
            {
                $this->affected = sqlsrv_num_rows($this->result);
                $this->records  = @sqlsrv_num_rows($this->result);
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
            //echo "Query eseguita correttamente !";
        } else
        {
            $this->lastError = $this->GetError();
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
        $this->arrayedResult = sqlsrv_fetch_array( $this->result, SQLSRV_FETCH_ASSOC ) or die ($this->GetError());
        sqlsrv_free_stmt($this->result);
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
        do {
            while ($row = sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC)){
               $this->arrayedResult[] = $row;
            }
        } while (sqlsrv_next_result($this->result));

        sqlsrv_free_stmt($this->result);
        return $this->arrayedResult;
    }

    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query)
    {
        $this->executeSQL($query);

        if($this->affected == 1)
        {
            $rows[] = $this->arrayedResult;
        }
        else
        {
            $rows = $this->arrayedResult;
        }
        return json_encode($rows, JSON_NUMERIC_CHECK );
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
