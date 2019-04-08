<?php

final class FD_Mysql extends FD_DB
{

    /* *******************
	 * Private
	 * *******************/

    function __construct($id_config)
    {
        $connection_array = json_decode(file_get_contents("config.json"),true);
        $this->hostname = $connection_array[array_search($id_config,array_column($connection_array,"id"))]["host"];
        $this->username = $connection_array[array_search($id_config,array_column($connection_array,"id"))]["user"];
        $this->password = $connection_array[array_search($id_config,array_column($connection_array,"id"))]["pass"];
        $this->database = $connection_array[array_search($id_config,array_column($connection_array,"id"))]["db"];
        $this->Connect();
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    public function Connect()
    {
        $this->conn = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
        if(!$this->conn)
        {
            $this->lastError = mysqli_connect_error().PHP_EOL;
            $this->connected = false;
            return false;
        }

        if(!$this->UseDB($this->database))
        {
            $this->lastError = mysqli_connect_error().PHP_EOL;
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        mysqli_set_charset($this->conn, 'utf8');
        return true;
    }

    public function closeConnection()
    {
        mysqli_close($this->conn);
    }

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

    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        if($this->result = mysqli_query($this->conn,$query))
        {
            if ($this->result)
            {
                $this->affected = mysqli_affected_rows($this->conn);
                $this->records  = @mysqli_num_rows($this->result);
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
        } else
        {
            $this->lastError = mysqli_error($this->conn);
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
        $this->arrayedResult = mysqli_fetch_assoc($this->result) or die (mysqli_error($this->conn));
        return $this->arrayedResult;
    }

    public function arrayResults()
    {
        if($this->records == 1)
        {
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = mysqli_fetch_assoc($this->result))
        {
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    public function UseDB($db)
    {
        if(strlen($this->database) > 0)
        {
            if(!mysqli_select_db($this->conn,$db))
            {
                $this->lastError = mysqli_error($this->conn);
                return false;
            } else
            {
                return true;
            }
        } else
        {
            if(!mysqli_select_db($this->conn,$db))
            {
                $this->lastError = mysqli_error($this->conn);
                return false;
            } else
            {
                return true;
            }
        }
    }

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
