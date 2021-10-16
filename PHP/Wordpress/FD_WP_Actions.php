<?php
/**
 * Created by WebStorm.
 * User: Simone
 * Date: 04/10/2021
 * Time: 00:10
 */

// Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

// error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// includo libreria Wordpress
$serverPath = explode("/", __DIR__);
array_splice($serverPath, count($serverPath)-3, 3);
$wpLibrary =  implode("/", $serverPath) . '/wp-load.php';
require_once $wpLibrary;

require("../WebTools/FD_Url.php");
require("../Config/FD_Define.php");
$url = new FD_Url();

$token = $url->getBearerToken('AEI');

// prendo parametri input
$data = file_get_contents("php://input");
$objData = json_decode($data);

/**
* 1-> Creazione Utente
* 2-> Update Status Utente
* 3-> Creazione Prodotto
* 4-> Update Prodotto
* 5-> Creazione Ordine
* 6-> Update Ordine
*/
if (property_exists((object) $objData,"action"))
{
    $action = $objData->action;
}
if (property_exists((object) $objData,"params"))
{
    $params = $objData->params;
}

if(strlen($action) == 0 || strlen($params) == 0)
{
    echo '{"error" : "Invalid request!"}';
    return;
}

//Prendo il token di sessione dell'utente e controllo che sia valido
$jwt = new FD_JWT();
if(strlen($token) > 0)
{
    $keyRequest = $jwt->decode($token,strtolower(md5_file("../Config/esatto.mp3"))); //ritorna il payload
    if(strlen($keyRequest) == 0)
    {
        echo '{"error" : "Invalid token 1 !"}';
        return;
    }
}
else
{
    echo '{"error" : "Invalid token 2 !"}';
    return;
}

//TODO controllare che il chiamante sia un amministratore

try
{

    switch($action)
    {
        case WP_ACTIONS::CREATA_USER:
            $user_data = json_decode($params, true);
            echo $user_data;
            /*$user_id = username_exists( $user_data=>user_name );
            if (!$user_id && email_exists($user_data=>user_name) == false)
            {
                // $random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );

                $user_id = wp_create_user( $user_data=>user_name, $user_data=>password, $user_data=>user_email );
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $user_data=>name,
                    'last_name' => $user_data=>surname
                ]);
                echo json_encode($user);
            }
            else
            {
                http_response_code(500);
                echo json_encode( [ 'success' => false , 'message' => 'User already exists!' ]);
                exit();
            }*/
            break;
        /*case WP_ACTIONS::UPDATE_USER:
            // TODO
            break;
        case WP_ACTIONS::CREATE_PRODUCT:
            $post_id = wp_insert_post( array(
                'post_title' => '',
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_content' => '',
            ));
            $product = wc_get_product( $post_id );
            // $product->set_sku( $sku );
            $product->save();
            echo json_encode(array('id' => $product->get_product_id()));
            break;
        case WP_ACTIONS::UPDATE_PRODUCT:
            // update_post_meta($product->id, '_regular_price', (float)$value);
            // update_post_meta($product->id, '_price', (float)$value);
            break;*/
    }

}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
}

