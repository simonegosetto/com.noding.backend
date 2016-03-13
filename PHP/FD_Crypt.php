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

    //Ritorna il valore criptato
    function login_encrypt($string) {
        srand((double) microtime() * 1000000); //for sake of MCRYPT_RAND
        $key = $this->key;//md5($this->key); //to improve variance
        /* Open module, and create IV */
        $td = mcrypt_module_open('des', '','cfb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            /* Encrypt data */
            $c_t = mcrypt_generic($td, $string);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            $c_t = $iv.$c_t;
            return $c_t;
        } //end if
    }

    function login_decrypt($string) {
        $key = $this->key;//md5($this->key); //to improve variance
        /* Open module, and create IV */
        $td = mcrypt_module_open('des', '','cfb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = substr($string,0,$iv_size);
        $string = substr($string,$iv_size);
        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            /* Encrypt data */
            $c_t = mdecrypt_generic($td, $string);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return $c_t;
        }
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