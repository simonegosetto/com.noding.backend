<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 14/06/14
 * Time: 12.50
 */

final class FD_Crypt 
{

    var $key;

    function __construct()
    {
        $this->key = strtolower(md5_file("Config/esatto.mp3"));
    }

    //Funzione semplificata di crypt / decrypt
    function simple_crypt($string, $action = 'encrypt')
    {
        $res = '';
        if($action !== 'encrypt'){
            $string = base64_decode($string);
        }
        for( $i = 0; $i < strlen($string); $i++){
            $c = ord(substr($string, $i));
            if($action == 'encrypt'){
                $c += ord(substr($this->key, (($i + 1) ) % strlen($this->key)));
                $res .= chr($c & 0xFF);
            }else{
                $c -= ord(substr($this->key, (($i + 1) % strlen($this->key))));
                $res .= chr(abs($c) & 0xFF);
            }
        }
        if($action == 'encrypt'){
            $res = base64_encode($res);
        }
        return $res;
    }

    //Ritorna il valore decriptato
    public function decrypt($encrypted_string)
    {
        //Sostituisco caratteri jolly di appoggio
        $encrypted_string = str_replace("@","=",$encrypted_string);
        //Decrypto
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        //Ritorno il valore senza spazi
        return trim($decrypted_string);
    }  

    //ritorna il valore per la connessione mysql
    public function mysql_decrypt($encrypted_string, $encryption_key) 
    {
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    //ritorna il nome della procedura decriptato
    public function stored_decrypt($encrypted_string) 
    {
        $encryption_key = strtolower(md5_file("Config/WindowsFormsApplication1.pdb"));
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    //Ritorna la password criptata per Django
    public function django_crypt($password,$salt,$iteration)
    {
        $app = base64_encode(hash_pbkdf2("sha256", $password, $salt, $iteration, 32, true));
        return 'pbkdf2_sha256$'.$iteration.'$'.$salt.'$'.$app;
    }

    //Ritorna key per redis
    public function redis_crypt($command)
    {
        return md5($command);
    }

    //Funzione di fix stringhe con apici o caratteri strani
    public function fixString($string)
    {

        //primo carattere
        if(substr($string,0,1) == "'"){
            $string = "<%first%>".substr($string,1);
        }
        //ultimo carattere
        if(substr($string,-1,1) == "'"){
            $string = substr($string,0,strlen($string)-1)."<%last%>";
        }
        //doppia stringa
        if(strpos($string, "','") !== false){
            $string = str_replace("','","<%twostring%>",$string);
        }
        //stringa sinistra
        if(strpos($string, "',") !== false){
            $string = str_replace("',","<%leftstring%>",$string);
        }
        //stringa destra
        if(strpos($string, ",'") !== false){
            $string = str_replace(",'","<%rightstring%>",$string);
        }

        $fixedString = str_replace("<%rightstring%>",",'",str_replace("<%leftstring%>","',",str_replace("<%twostring%>","','",str_replace("<%last%>","'",str_replace("<%first%>","'",addslashes($string))))));

        //elimino i NULLI presi come stringa
        $fixedString = str_replace("'null'","null",$fixedString);

        //sistemo l'encoding
        return $fixedString;//utf8_encode($fixedString);
    }

}
