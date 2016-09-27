<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 14/06/14
 * Time: 12.50
 */

class FD_Crypt {

    var $key;

    function FD_Crypt(){
        $this->key = md5_file("esatto.mp3");
    }

    //Funzione semplificata di crypt / decrypt
    function simple_crypt($string, $action = 'encrypt'){
        $res = '';
        if($action !== 'encrypt'){
            $string = base64_decode($string);
        }
        for( $i = 0; $i < strlen($string); $i++){
            $c = ord(substr($string, $i));
            if($action == 'encrypt'){
                $c += ord(substr($this->key, (($i + 1) )% strlen($this->key)));
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
    public function decrypt($encrypted_string){ //, $encryption_key) {
        //Sostituisco caratteri jolly di appoggio
        $encrypted_string = str_replace("@","=",$encrypted_string);
        //Decrypto
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        //Ritorno il valore senza spazi
        return str_replace(" ","",trim($decrypted_string));
    }

    //ritorna il valore per la connessione mysql
    public function mysql_decrypt($encrypted_string, $encryption_key) {
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    //ritorna il nome della procedura decriptato
    public function stored_decrypt($encrypted_string) {
        $encryption_key = strtolower(md5_file("WindowsFormsApplication1.pdb"));
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    //Ritorna la password criptata per Django
    public function Django_Crypt($password,$salt,$iteration){
        $app = base64_encode(hash_pbkdf2("sha256", $password, $salt, $iteration, 32, true));
        return 'pbkdf2_sha256$'.$iteration.'$'.$salt.'$'.$app;
    }

} 