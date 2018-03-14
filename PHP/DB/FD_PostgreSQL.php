<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 20/09/2016
 * Time: 20:45
 */
class FD_PostgreSQL extends FD_DB
{

    /* *******************
	 * Private
	 * *******************/

    //Costruttore
    function FD_PostgreSQL($keyRequest="",$suffix=""){
        $this->key = strtolower(md5_file("esatto.mp3"));
        if($keyRequest == $this->key){
            $this->validatedRequest=true;

            if(strlen($suffix) > 0){
                $ini_array = parse_ini_file("config.inc_".$suffix.".ini");
            }else {
                $ini_array = parse_ini_file("config.inc.ini");
            }
            $this->port = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["port"]),$this->key)));
            $this->hostname = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["hostname"]),$this->key)));
            $this->username = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["username"]),$this->key)));
            if(strlen($ini_array["password"]) > 0){
                $this->password = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["password"]),$this->key)));
            }else{
                $this->password = "";
            }
            $this->database = str_replace(" ","",trim($this->decrypt(str_replace("@","=",$ini_array["database"]),$this->key)));
            $this->Connect();
        }else{
            $this->validatedRequest=false;
            $this->lastError="Richiesta al server non valida";
        }
    }

    //Connessione al DB
    private function Connect(){
        $this->conn = pg_connect("host=".$this->hostname." port=".$this->port." dbname=".$this->database." user=".$this->username." password=".$this->password);
        if(!$this->conn){
            $this->lastError = 'Nessuna connessione al server';
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

    //Ritorna il valore decriptato
    private function decrypt($encrypted_string, $encryption_key) {
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    function cleanData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }

    /* *******************
	 * Pubbliche
	 * *******************/

    //Chiusura connessione al DB
    public function closeConnection(){
        pg_close($this->conn);
    }

    //Pulisce il buffer della connessione dalle precedenti query
    public function CleanBufferResults($conn){
        while($conn->more_results()){
            $conn->next_result();
            if($res = $conn->store_result())
            {
                $res->free();
            }
        }
    }

    //Esecuzione della query
    public function executeSQL($query){
        $this->lastQuery = $query;
        if($this->result = pg_query($this->conn,$query)){
            if ($this->result) {
                $this->affected = pg_fetch_row($this->conn);
            } else {
                $this->records  = 0;
                $this->affected = 0;
            }

            if($this->affected > 0){
                $this->arrayResults();
                $this->CleanBufferResults($this->conn);
                return $this->arrayedResult;
            }else{
                $this->CleanBufferResults($this->conn);
                return true;
            }
            //echo "Query eseguita correttamente !";
        }else{
            $this->lastError = pg_last_error($this->conn);
            return false;
        }
    }

    //Ritorna il numero di righe della query
    public function countRows($query){
        $result = $this->executeSQL($query);
        return $this->records;
    }

    //Singolo array
    public function arrayResult(){
        $this->arrayedResult = pg_fetch_assoc($this->result) or die (pg_last_error($this->conn));
        return $this->arrayedResult;
    }

    //Array multiplo
    public function arrayResults(){
        if($this->records == 1){
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = pg_fetch_assoc($this->result)){
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query){
        $this->executeSQL($query);

        return json_encode($this->arrayedResult);//, JSON_NUMERIC_CHECK );
    }


}
