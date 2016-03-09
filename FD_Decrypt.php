<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 14/06/14
 * Time: 12.50
 */

class FD_Decrypt {

    var $key;               // key

    function FD_Decrypt(){
        $this->key = md5_file("http://simonegosetto.it/FD_Components/esatto.mp3");
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



} 