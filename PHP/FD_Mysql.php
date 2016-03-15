<?php

class FD_Mysql {

    //Variabili
    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
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
    function FD_Mysql($keyRequest=""){
        $this->key = strtolower(md5_file("esatto.mp3"));
        if($keyRequest == $this->key){
            $this->validatedRequest=true;

            $ini_array = parse_ini_file("config.inc.ini");
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
        $this->conn = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
        if(!$this->conn){
            $this->lastError = 'Nessuna connessione al server: ' . mysqli_connect_error().PHP_EOL;
            $this->connected = false;
            return false;
        }

        if(!$this->UseDB($this->database)){
            $this->lastError = 'Nessun DB selezionato: ' . mysqli_connect_error().PHP_EOL;
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

    //Gestione array/stringhe
    private function SecureData($data, $types){
        if(is_array($data)){
            $i = 0;
            foreach($data as $key=>$val){
                if(!is_array($data[$key])){
                    $data[$key] = $this->CleanData($data[$key], $types[$i]);
                    $data[$key] = mysqli_real_escape_string($this->conn,$data[$key]);
                    $i++;
                }
            }
        }else{
            $data = $this->CleanData($data, $types);
            $data = mysqli_real_escape_string($this->conn,$data);
        }
        return $data;
    }

    function cleanData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }

    // Pulizia delle variabili a seconda del tipo
    // possible types: none, str, int, float, bool, datetime, ts2dt
    private function CleanData_test($data, $type = ''){
        switch($type) {
            case 'none':
                $data = $data;
                break;
            case 'str':
                $data = settype( $data, 'string');
                break;
            case 'int':
                $data = settype( $data, 'integer');
                break;
            case 'float':
                $data = settype( $data, 'float');
                break;
            case 'bool':
                $data = settype( $data, 'boolean');
                break;
            // Y-m-d H:i:s
            // 2014-01-01 12:30:30
            case 'datetime':
                $data = trim( $data );
                $data = preg_replace('/[^\d\-: ]/i', '', $data);
                preg_match( '/^([\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2})$/', $data, $matches );
                $data = $matches[1];
                break;
            case 'ts2dt':
                $data = settype( $data, 'integer');
                $data = date('Y-m-d H:i:s', $data);
                break;

            // bonus types
            case 'hexcolor':
                preg_match( '/(#[0-9abcdef]{6})/i', $data, $matches );
                $data = $matches[1];
                break;
            case 'email':
                $data = filter_var($data, FILTER_VALIDATE_EMAIL);
                break;
            default:
                $data = '';
                break;
        }
        return $data;
    }

    /* *******************
	 * Pubbliche
	 * *******************/

    //Chiusura connessione al DB
    public function closeConnection(){
        mysqli_close($this->conn);
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
        if($this->result = mysqli_query($this->conn,$query)){
            if ($this->result) {
                $this->affected = mysqli_affected_rows($this->conn);
                $this->records  = @mysqli_num_rows($this->result);
            } else {
                $this->records  = 0;
                $this->affected = 0;
            }

            if($this->records > 0){
                $this->arrayResults();
                $this->CleanBufferResults($this->conn);
                return $this->arrayedResult;
            }else{
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
        $this->arrayedResult = mysqli_fetch_assoc($this->result) or die (mysqli_error($this->conn));
        return $this->arrayedResult;
    }

    //Array multiplo
    public function arrayResults(){
        if($this->records == 1){
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = mysqli_fetch_assoc($this->result)){
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    //Seleziona il DB interessato (di dafault non importa)
    public function UseDB($db){
        if(strlen($this->database) > 0){
            if(!mysqli_select_db($this->conn,$db)){
                $this->lastError = 'Nessun DB selezionato: ' . mysqli_error($this->conn);
                return false;
            }else{
                return true;
            }
        }else{
            if(!mysqli_select_db($this->conn,$db)){
                $this->lastError = 'Nessun DB selezionato: ' . mysqli_error($this->conn);
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

        /*$table=array();
        while($row=mysql_fetch_object($this->result)){
            $table[]=$row;
            unset($row);
        }*/

        return json_encode($this->arrayedResult);
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
