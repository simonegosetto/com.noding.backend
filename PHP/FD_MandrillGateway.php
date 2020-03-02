<?php
require_once 'mandrill-api-php/src/Mandrill.php';
$ini_array = parse_ini_file("Mandrill/config.ini");
$mandrill = new Mandrill($ini_array['appkey']);
