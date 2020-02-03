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

    public function closeConnection()
    {
        sqlsrv_close($this->conn);
        $this->connected = false;
    }

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
        } else
        {
            $this->lastError = $this->GetError();
            return false;
        }
    }

    public function countRows($query)
    {
        $result = $this->executeSQL($query);
        return $this->records;
    }

    public function arrayResult()
    {
        $this->arrayedResult = sqlsrv_fetch_array( $this->result, SQLSRV_FETCH_ASSOC ) or die ($this->GetError());
        sqlsrv_free_stmt($this->result);
        return $this->arrayedResult;
    }

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

}