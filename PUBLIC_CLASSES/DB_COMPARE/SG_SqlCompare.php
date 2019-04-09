<?php
/**
 * Created by VSCode.
 * User: simon
 * Date: 02/08/2018
 * Time: 00:23
 */

//Imposto qualsiasi orgine da cui arriva la richiesta come abilitata e la metto in cache per un giorno
if (isset($_SERVER['HTTP_ORIGIN']))
{
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

//Imposto tutti i metodi come abilitati
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
{
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

//remove the notice
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

require("DB/SG_DB.php");
require("DB/SG_Mysql.php");

$id_master = $_GET["id_master"];
$id_slave = $_GET["id_slave"];

$master_db = json_decode(file_get_contents("config.json"),true)[array_search($id_master,array_column(json_decode(file_get_contents("config.json"),true),"id"))]["db"];
$slave_db = json_decode(file_get_contents("config.json"),true)[array_search($id_slave,array_column(json_decode(file_get_contents("config.json"),true),"id"))]["db"];

if(strlen($id_master) == 0 || strlen($id_slave) == 0)
{
    echo '{"error": "invalid parameters"}';
    return;
}

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ FUNCTIONS @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function remove_auto_increment_definer($string)
{
    if(strpos($string,"AUTO_INCREMENT") !== false)
    {
        $path_to_replace = substr(strstr( $string, 'AUTO_INCREMENT=' ),0,stripos(strstr( $string, 'AUTO_INCREMENT=' )," "));
        return str_replace("DEFINER=``@`%`","",str_replace($path_to_replace,"",$string));
    }
    else
    {
        return str_replace("DEFINER=``@`%`","",$string);
    }
}


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

try
{
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ MASTER @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    $sql = new SG_Mysql($id_master);

    if(strlen($sql->lastError) > 0)
    {
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    /*
      SHOW TABLES;
      SHOW CREATE TABLE ;

      SHOW PROCEDURE STATUS;
      SHOW CREATE PROCEDURE ;

      SHOW FUNCTION STATUS;
      SHOW CREATE FUNCTION ;

    */
    $query = "SHOW TABLES;";
    $master_result = $sql->exportJSON($query);

    if(strlen($sql->lastError) > 0)
    {
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $master_result_array = json_decode($master_result,true);
    $key = array_keys($master_result_array[0])[0];

    // master tables
    $final_result = array();
    for($i=0;$i<count($master_result_array);$i++)
    {
      $query = "SHOW CREATE TABLE ".$master_result_array[$i][$key].";";
      $entity_definition = $sql->exportJSON($query);

      if(strlen($sql->lastError) > 0)
      {
          if($sql->connected)
          {
              $sql->closeConnection();
          }
          return;
      }

      array_push($final_result,
        array(
          "type" => (array_keys(json_decode($entity_definition,true)[0])[0] == "Table" ? "table" : "view"),
          "master" => $master_db,
          "entity_master" => $master_result_array[$i][$key],
          "entity_definition_master" => str_replace($master_db,"",(array_keys(json_decode($entity_definition,true)[0])[0] == "Table" ? json_decode($entity_definition,true)[0]["Create Table"] : json_decode($entity_definition,true)[0]["Create View"])),
          "slave" => $slave_db,
          "entity_slave" => null,
          "entity_definition_slave" => null,
          "is_different" => false
        )
      );
    }

    // master procedures
    $query = "SHOW PROCEDURE STATUS WHERE Db = '".$sql->database."';";
    $master_result = $sql->exportJSON($query);

    if(strlen($sql->lastError) > 0)
    {
        echo $sql->lastError;
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    var_dump($master_result);

    $master_result_array = json_decode($master_result,true);

    for($i=0;$i<count($master_result_array);$i++)
    {
      $query = "SHOW CREATE PROCEDURE ".$master_result_array[$i]["Name"].";";
      $entity_definition = $sql->exportJSON($query);

      echo $sql->database." ".$sql->lastError;

      if(strlen($sql->lastError) > 0)
      {
          if($sql->connected)
          {
              $sql->closeConnection();
          }
          return;
      }

      array_push($final_result,
        array(
          "type" => "procedure",
          "master" => $master_db,
          "entity_master" => $master_result_array[$i]["Name"],
          "entity_definition_master" => str_replace($master_db,"",json_decode($entity_definition,true)[0]["Create Procedure"]),
          "slave" => $slave_db,
          "entity_slave" => null,
          "entity_definition_slave" => null,
          "is_different" => false
        )
      );
    }

    $sql->closeConnection();

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ SLAVE @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    $sql = new SG_Mysql($id_slave);

    if(strlen($sql->lastError) > 0)
    {
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $query = "SHOW TABLES;";
    $slave_result = $sql->exportJSON($query);

    if(strlen($sql->lastError) > 0)
    {
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $slave_result_array = json_decode($slave_result,true);
    $key = array_keys($slave_result_array[0])[0];

    // slave tables
    for($i=0;$i<count($slave_result_array);$i++)
    {
      $query = "SHOW CREATE TABLE ".$slave_result_array[$i][$key].";";
      $entity_definition = $sql->exportJSON($query);

      if(strlen($sql->lastError) > 0)
      {
          if($sql->connected)
          {
              $sql->closeConnection();
          }
          return;
      }

      $index = array_search($slave_result_array[$i][$key],array_column($final_result,"entity_master"));
      if(is_numeric($index))
      {
        $final_result[$index]["entity_slave"] = $slave_result_array[$i][$key];
        $final_result[$index]["entity_definition_slave"] = str_replace($slave_db,"",(array_keys(json_decode($entity_definition,true)[0])[0] == "Table" ? json_decode($entity_definition,true)[0]["Create Table"] : json_decode($entity_definition,true)[0]["Create View"]));
        $final_result[$index]["is_different"] = ($final_result[$index]["entity_definition_slave"] != $final_result[$index]["entity_definition_master"] ? true : false);
      }
      else
      {
        array_push($final_result,
          array(
            "type" => (array_keys(json_decode($entity_definition,true)[0])[0] == "Table" ? "table" : "view"),
            "master" => $master_db,
            "entity_master" => null,
            "entity_definition_master" => null,
            "slave" => $slave_db,
            "entity_slave" => $slave_result_array[$i][$key],
            "entity_definition_slave" => str_replace($slave_db,"",(array_keys(json_decode($entity_definition,true)[0])[0] == "Table" ? json_decode($entity_definition,true)[0]["Create Table"] : json_decode($entity_definition,true)[0]["Create View"])),
            "is_different" => true
          )
        );
      }
    }

    $query = "SHOW PROCEDURE STATUS WHERE Db = '".$sql->database."';";
    $slave_result = $sql->exportJSON($query);

    if(strlen($sql->lastError) > 0)
    {
        if($sql->connected)
        {
            $sql->closeConnection();
        }
        return;
    }

    $slave_result_array = json_decode($slave_result,true);

    // slave procedure
    for($i=0;$i<count($slave_result_array);$i++)
    {
      $query = "SHOW CREATE PROCEDURE ".$slave_result_array[$i]["Name"].";";
      $entity_definition = $sql->exportJSON($query);

      if(strlen($sql->lastError) > 0)
      {
          if($sql->connected)
          {
              $sql->closeConnection();
          }
          return;
      }

      $index = array_search($slave_result_array[$i]["Name"],array_column($final_result,"entity_master"));
      if(is_numeric($index))
      {
        $final_result[$index]["entity_slave"] = $slave_result_array[$i]["Name"];
        $final_result[$index]["entity_definition_slave"] = str_replace($slave_db,"",json_decode($entity_definition,true)[0]["Create Procedure"]);
        $final_result[$index]["is_different"] = ($final_result[$index]["entity_definition_slave"] != $final_result[$index]["entity_definition_master"] ? true : false);
      }
      else
      {
        array_push($final_result,
          array(
            "type" => "procedure",
            "master" => $master_db,
            "entity_master" => null,
            "entity_definition_master" => null,
            "slave" => $slave_db,
            "entity_slave" => $slave_result_array[$i]["Name"],
            "entity_definition_slave" => str_replace($slave_db,"",json_decode($entity_definition,true)[0]["Create Procedure"]),
            "is_different" => true
          )
        );
      }

    }

    $sql->closeConnection();

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ FINAL RESULT @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // compose the final array of results
    for($i=0;$i<count($final_result);$i++)
    {
        $final_result[$i]["entity_definition_master"] = remove_auto_increment_definer($final_result[$i]["entity_definition_master"]);
        $final_result[$i]["entity_definition_slave"] = remove_auto_increment_definer($final_result[$i]["entity_definition_slave"]);
        $final_result[$i]["is_different"] = ($final_result[$i]["entity_definition_slave"] != $final_result[$i]["entity_definition_master"] ? true : false);
    }

    echo json_encode($final_result);
}
catch (Exception $e)
{
    echo '{"error" : "'.$e->getMessage().'"}';
}
