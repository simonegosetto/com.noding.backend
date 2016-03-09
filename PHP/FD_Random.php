<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 20/03/2015
 * Time: 20:05
 */

class FD_Random {

    function FD_Random(){

    }

    //Ritorna una stringa random della dimensione desiderata
    public function Generate($length=16){
        $salt       = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ012345678';
        $len        = strlen($salt);
        $makepass   = '';
        mt_srand(10000000*(double)microtime());
        for ($i = 0; $i < $length; $i++) {
            $makepass .= $salt[mt_rand(0,$len - 1)];
        }
        return $makepass;
    }
} 