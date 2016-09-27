<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 20/09/2016
 * Time: 20:45
 */
class FD_PostgreSQL
{
//Variabili
    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $port = "";
    var $conn;      // Connessione al DB

    var $lastError = "";         // Ultimo errore
    var $lastQuery;         // Ultima query (eseguita/richiesta)
    var $result;            // Ultimo risultato
    var $records;           // Numero di record estratti
    var $affected;          // Numero di righe aggiornate
    var $rawResults;        //
    var $arrayedResult;     // Ultimo array di risultati
    var $key;               // key
    var $validatedRequest;  //Richiesta al server validata si/no
    var $connected;         //Connesso si/no

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

        if(!$this->UseDB($this->database)){
            $this->lastError = 'Nessun DB selezionato';
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

    //Controllo abilitazione DB
    public function CheckDB($token,$db){
        $this->executeSQL("call spFD_CheckDB('"+$token+"','"+$db+"'')");
        //echo "call spFD_CheckDB('"+$token+"','"+$db+"');";
    }

    //Esecuzione della query
    public function executeSQL($query){
        $this->lastQuery = $query;
        if($this->result = pg_query($this->conn,$query)){
            if ($this->result) {
                $this->affected = pg_fetch_row($this->conn);
                //$this->records  = @mysqli_num_rows($this->result);
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
            $this->lastError = mysqli_error($this->conn);
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
        $this->arrayedResult = pg_fetch_assoc($this->result) or die (pg_last_error());
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

    //Seleziona il DB interessato (di dafault non importa)
    public function UseDB($db){
        if(strlen($this->database) > 0){
            if(!mysqli_select_db($this->conn,$db)){
                $this->lastError = 'Nessun DB selezionato: ' . pg_last_error();
                return false;
            }else{
                return true;
            }
        }else{
            if(!mysqli_select_db($this->conn,$db)){
                $this->lastError = 'Nessun DB selezionato: ' .pg_last_error();
                return false;
            }else{
                return true;
            }
        }
    }

    //Funzione che mi esporta il risultato della query in XML
    public function exportXML($query){
        $this->executeSQL($query);
        $fcount = mysqli_num_fields($this->result);
        $export = "<export>\n";
        while($row = mysqli_fetch_array($this->result) )
        {
            $export.="\t<record>\n\t\t";
            for($i=0; $i< $fcount; $i++)
            {
                $tag = mysqli_fetch_field_direct($this->result, $i)->name;//mysql_field_name( $this->result, $i );
                $export.="<$tag>".mysqli_real_escape_string($this->conn,$row[$i])."</$tag>";
            }
            $export.="\n\t</record>";
            $export.="\n";
        }
        $export.="</export>\n";

        return $export;
    }

    //Funzione che mi esporta il risultato della query in CSV
    public function exportCSV($query){
        $this->executeSQL($query);
        $fcount = mysqli_num_fields($this->result);
        $export = "";
        while($row = mysqli_fetch_array($this->result) )
        {
            for($i=0; $i< $fcount; $i++)
            {
                $tag = mysqli_fetch_field_direct($this->result, $i)->name;//mysql_field_name( $this->result, $i );
                $export .= mysqli_real_escape_string($this->conn,$row[$i])."\t";
            }
            $export .= "\n";
        }
        $export .= "\n";

        return $export;
    }

    //Funzione che mi esporta il risultato della query in JSON
    public function exportJSON($query){
        $this->executeSQL($query);
        //var_dump($this->arrayedResult);

        /*$table=array();
        while($row=mysql_fetch_object($this->result)){
            $table[]=$row;
            unset($row);
        }*/

        return json_encode($this->arrayedResult);//, JSON_NUMERIC_CHECK );
    }

    //Funzione che mi esporta il risultato della query in XLS
    public function exportXLS($query){
        $this->executeSQL($query);
        $fcount = mysqli_num_fields($this->result);
        $export = "";
        while($row = mysqli_fetch_array($this->result) )
        {
            for($i=0; $i< $fcount; $i++)
            {
                $tag = mysqli_fetch_field_direct($this->result, $i)->name;//mysql_field_name( $this->result, $i );
                $export .= mysqli_real_escape_string($this->conn,$row[$i])."\t";
            }
            $export .= "\n";
        }
        $export .= "\n";

        // filename for download
        $filename = "XLS_" . date('Ymd') . ".xls";
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: application/vnd.ms-excel");

        return $export;
    }
}
