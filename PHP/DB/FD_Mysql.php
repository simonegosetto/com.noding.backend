<?php

final class FD_Mysql extends FD_DB
{

    /* *******************
	 * Private
	 * *******************/

    //Costruttore
    function __construct()
    {
        $ini_array = parse_ini_file("Config/config.inc.ini");
        $this->key = strtolower(md5_file("Config/esatto.mp3"));

        $this->hostname = str_replace(" ", "", trim($this->decrypt(str_replace("@", "=", $ini_array["hostname"]), $this->key)));
        $this->username = str_replace(" ", "", trim($this->decrypt(str_replace("@", "=", $ini_array["username"]), $this->key)));
        if (strlen($ini_array["password"]) > 0) {
            $this->password = str_replace(" ", "", trim($this->decrypt(str_replace("@", "=", $ini_array["password"]), $this->key)));
        } else {
            $this->password = "";
        }
        $this->database = str_replace(" ", "", trim($this->decrypt(str_replace("@", "=", $ini_array["database"]), $this->key)));
        $this->Connect();
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    //Ritorna il valore decriptato
    public function decrypt($encrypted_string, $encryption_key)
    {
        $decryption_iv = '1234567891011121';
        return openssl_decrypt($encrypted_string, "AES-128-CTR", $encryption_key, 0, $decryption_iv);

        /*$encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;*/
    }

    //Connessione al DB
    public function Connect()
    {
        //echo $this->hostname.$this->username.$this->password.$this->database;
        $this->conn = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
        mysqli_options($this->conn, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        if (!$this->conn) {
            $this->lastError = 'Nessuna connessione al serverExpress: ' . mysqli_connect_error() . PHP_EOL;
            $this->connected = false;
            return false;
        }

        if (!$this->UseDB($this->database)) {
            $this->lastError = 'Nessun DB selezionato: ' . mysqli_connect_error() . PHP_EOL;
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        //Imposto il charset che mi aiuta nell'estrazione corretta dei dati
        mysqli_set_charset($this->conn, 'utf8');
        return true;
    }

    //Chiusura connessione al DB
    public function closeConnection()
    {
        mysqli_close($this->conn);
    }

    //Controllo coerenza token
    public function tokenCheck($token)
    {
        $this->executeSQL("call sys_token_check('" . $token . "');");
        if ($this->affected > 0) {
            return true;
        } else {
            return false;
        }
    }

    //Pulisce il buffer della connessione dalle precedenti query
    public function CleanBufferResults($conn)
    {
        while ($conn->more_results()) {
            $conn->next_result();
            if ($res = $conn->store_result()) {
                $res->free();
            }
        }
    }

    //Esecuzione della query
    public function executeSQL($query)
    {
        $this->lastQuery = $query;
        if ($this->result = mysqli_query($this->conn, $query)) {
            if ($this->result) {
                $this->affected = mysqli_affected_rows($this->conn);
                if (!is_bool($this->result)) {
                    $this->records = @mysqli_num_rows($this->result);
                } else {
                    $this->records = 0;
                }
            } else {
                $this->records = 0;
                $this->affected = 0;
            }

            if ($this->records > 0) {
                $this->arrayResults();
                $this->CleanBufferResults($this->conn);
                return $this->arrayedResult;
            } else {
                $this->CleanBufferResults($this->conn);
                return true;
            }
            //echo "Query eseguita correttamente !";
        } else {
            $this->lastError = mysqli_error($this->conn);
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
        $this->arrayedResult = mysqli_fetch_assoc($this->result) or die (mysqli_error($this->conn));
        return $this->arrayedResult;
    }

    //Array multiplo
    public function arrayResults()
    {
        if ($this->records == 1) {
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = mysqli_fetch_assoc($this->result)) {
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    //Seleziona il DB interessato (di dafault non importa)
    public function UseDB($db)
    {
        if (strlen($this->database) > 0) {
            if (!mysqli_select_db($this->conn, $db)) {
                $this->lastError = 'Nessun DB selezionato: ' . mysqli_error($this->conn);
                return false;
            } else {
                return true;
            }
        } else {
            if (!mysqli_select_db($this->conn, $db)) {
                $this->lastError = 'Nessun DB selezionato: ' . mysqli_error($this->conn);
                return false;
            } else {
                return true;
            }
        }
    }

    //Funzione che mi esporta il risultato della query in XML
    public function exportXML($query)
    {
        $this->executeSQL($query);
        $fcount = mysqli_num_fields($this->result);
        $export = "<export>\n";
        while ($row = mysqli_fetch_array($this->result)) {
            $export .= "\t<record>\n\t\t";
            for ($i = 0; $i < $fcount; $i++) {
                $tag = mysqli_fetch_field_direct($this->result, $i)->name;//mysql_field_name( $this->result, $i );
                $export .= "<$tag>" . mysqli_real_escape_string($this->conn, $row[$i]) . "</$tag>";
            }
            $export .= "\n\t</record>";
            $export .= "\n";
        }
        $export .= "</export>\n";

        return $export;
    }

    //Funzione che mi esporta il risultato della query in CSV
    public function exportCSV($query)
    {
        $this->executeSQL($query);
        $fcount = mysqli_num_fields($this->result);
        $export = "";
        while ($row = mysqli_fetch_array($this->result)) {
            for ($i = 0; $i < $fcount; $i++) {
                $tag = mysqli_fetch_field_direct($this->result, $i)->name;//mysql_field_name( $this->result, $i );
                $export .= mysqli_real_escape_string($this->conn, $row[$i]) . "\t";
            }
            $export .= "\n";
        }
        $export .= "\n";

        return $export;
    }

    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query)
    {
        $this->executeSQL($query);

        if ($this->affected == 1) {
            $rows[] = $this->arrayedResult;
        } else {
            $rows = $this->arrayedResult;
        }
        return $this->json_encode($rows);
    }

    public static function json_encode($data)
    {
        $numeric = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        $nonnumeric = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        preg_match_all("/\"[0\+]+(\d+)\"/", $nonnumeric, $vars);
        foreach ($vars[0] as $k => $v) {
            // echo $k." ".$v;
            if ($v[1] != "0") {
                $numeric = preg_replace("/\:\s*{$vars[1][$k]},/", ": {$v},", $numeric);
            }
        }
        return $numeric;
    }

}
