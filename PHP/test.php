<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 11/04/2016
 * Time: 16:41
 */


$password = "volontapp";
$iterations = 20000;

// Generate a random IV using mcrypt_create_iv(),
// openssl_random_pseudo_bytes() or another suitable source of randomness
//$salt = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
$salt = "dpvPEF4ULic6";

//$app = sha1($salt.$password);
$app = base64_encode(hash_pbkdf2("sha256", $password, $salt, $iterations, 32, true));

echo 'Get pass: pbkdf2_sha256$20000$dpvPEF4ULic6$owb9r+a/Td9LRzCuZVKWQnmAGQN+Ugnod9TOQvoaD4g='.'<br/>';
echo 'Vap pass: pbkdf2_sha256$20000$'.$salt.'$'.$app;
