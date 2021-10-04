<?php
/**
 * Created by WebStorm.
 * User: Simone
 * Date: 04/10/2021
 * Time: 00:10
 */

//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

//Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// includo libreria Wordpress
$serverPath = explode("/", __DIR__);
array_splice($serverPath, count($serverPath)-3, 3);
$wpLibrary =  implode("/", $serverPath) . '/wp-load.php';
require_once $wpLibrary;

// prendo parametri input
$data = file_get_contents("php://input");
$objData = json_decode($data);
if (property_exists((object) $objData,"token"))
{
    $token = $objData->token;
}
if (property_exists((object) $objData,"user_name"))
{
    $user_name = $objData->user_name;
}
if (property_exists((object) $objData,"password"))
{
    $password = $objData->password;
}
if (property_exists((object) $objData,"user_email"))
{
    $user_email = $objData->user_email;
}
if (property_exists((object) $objData,"name"))
{
    $name = $objData->name;
}
if (property_exists((object) $objData,"surname"))
{
    $surname = $objData->surname;
}

if(strlen($user_name) == 0)
{
    echo '{"error" : "Invalid request!"}';
    return;
}

//TODO controllare che il chiamante sia un amministratore

// https://wpscholar.com/blog/add-wordpress-admin-user-via-php/
$user_id = username_exists( $user_name );

if (!$user_id && email_exists($user_email) == false)
{
    // $random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
    $user_id = wp_create_user( $user_name, $password, $user_email );
    wp_update_user([
        'ID' => $user_id,
        'first_name' => $name,
        'last_name' => $surname,
    ]);
    echo json_encode($user);
    /* $user = new WP_User( $user_id );
        $user->set_role( 'administrator' ); */
}
else
{
    http_response_code(500);
    echo json_encode( [ 'success' => false , 'message' => 'User already exists!' ]);
    exit();
}
