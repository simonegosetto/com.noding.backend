<?php
/**
 * Created by WebStorm.
 * User: Simone
 * Date: 27/08/2020
 * Time: 10:17
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

error_reporting(E_ALL);
ini_set('display_errors', 1);

// includo libreria Wordpress
$serverPath = explode("/", __DIR__);
array_splice($serverPath, count($serverPath)-3, 3);
$wpLibrary =  implode("/", $serverPath) . '/wp-load.php';
require_once $wpLibrary;

try {
	/*
	$userID = wp_get_current_user()->data->ID;

	$args = array(
		"customer_id" => $userID
	);
	$orders = wc_get_orders($args);

	// echo wc_get_order_statuses();

	var_dump($orders);
	*/
	// https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
	$customer = wp_get_current_user();
	// Get all customer orders
    $customer_orders = get_posts(array(
        'numberposts' => -1,
        'meta_key' => '_customer_user',
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_value' => get_current_user_id(),
        'post_type' => wc_get_order_types(),
        'post_status' => array_keys(wc_get_order_statuses()), 'post_status' => array('wc-processing'),
    ));

	// var_dump($customer_orders);

    $Order_Array = []; //
    foreach ($customer_orders as $customer_order)
	{
        $orderq = wc_get_order($customer_order);
		foreach($orderq->get_items() as $item)
		{
		    // var_dump($item->get_product());
			// $item->get_product()->get_data()["slug"]
			$Order_Array[] = [
				"id" => $orderq->get_id(),
				"name" => $item->get_name(),
				"image" => wp_get_attachment_url( $item->get_product()->get_image_id() ),
				"date" => $orderq->get_date_created()->date_i18n('Y-m-d'),
				"productid" => $item->get_product_id(),
				"anno" => $item->get_product()->get_length(),
				"daunit" => $item->get_product()->get_width(),
				"aunit" => $item->get_product()->get_height()
			];
		}

    }
	echo json_encode($Order_Array);
}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
}
