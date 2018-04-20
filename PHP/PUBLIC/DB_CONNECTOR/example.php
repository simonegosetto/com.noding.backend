<?php

include "SG_DB.php";

// For hide notice
error_reporting(E_ERROR | E_WARNING | E_PARSE);

#MICROSOFT SQL SERVER
/*
include "SG_MsSql.php";

$ms = new SG_MsSql();
if($ms->connected)
{
    //$result = $ms->exportJSON("SELECT * FROM table");
    //$result = $ms->countRows("SELECT * FROM table");
    $result = $ms->executeSQL("INSERT INTO table values(your_value)");

    if(strlen($ms->lastError) > 0)
    {
        echo $ms->lastError;
        $ms->closeConnection();
    }
    else
    {
        echo $result;
        $ms->closeConnection();
    }

}
else
{
    echo $ms->lastError;
}
*/


#SQLITE
/*
include "SG_SQLite.php";

$lite = new SG_SQLite("SQLite_db_test.db");

if($lite->connected)
{
    //$result = $lite->executeSQL("CREATE TABLE test1(id int, desc varchar(10))");
    //$result = $lite->executeSQL("INSERT INTO test1(id,desc) VALUES(3,'test 3')");
    $result = $lite->exportJSON("SELECT * FROM test1");
    //$result = $lite->countRows("select * from test1");


    if(strlen($lite->lastError) > 0)
    {
        echo $lite->lastError;
        $lite->closeConnection();
    }
    else
    {
        echo $result;
        $lite->closeConnection();
    }
}
else
{
    echo $lite->lastError;
}
*/

#MYSQL
/*
include "SG_Mysql.php";

$my = new SG_Mysql();

if($my->connected)
{
    //$result = $my->executeSQL("CREATE TABLE test1(id int, description varchar(10));");
    //$result = $my->executeSQL("INSERT INTO test1(id,description) VALUES(3,'test 3');");
    $result = $my->exportJSON("SELECT * FROM test1");

    if(strlen($my->lastError) > 0)
    {
        echo $my->lastError;
        $my->closeConnection();
    }
    else
    {
        echo $result;
        $my->closeConnection();
    }
}
else
{
    echo $my->lastError;
}
*/

#POSTGRESSQL
/*
include "SG_PostgreSQL.php";

$pg = new SG_PostgreSQL();

if($pg->connected)
{
    //$result = $pg->executeSQL("CREATE TABLE test1(id int, description varchar(10));");
    //$result = $pg->executeSQL("INSERT INTO test1(id,description) VALUES(3,'test 3');");
    //$result = $pg->exportJSON("SELECT * FROM test1");
    $result = $pg->countRows("SELECT * FROM test1");

    if(strlen($pg->lastError) > 0)
    {
        echo $pg->lastError;
        $pg->closeConnection();
    }
    else
    {
        echo $result;
        $pg->closeConnection();
    }
}
else
{
    echo $pg->lastError;
}
*/

#TEST CROSS JOIN

include "SG_SQLite.php";
include "SG_MsSql.php";

$lite = new SG_SQLite("SQLite_db_test.db");
$ms = new SG_MsSql();

if($lite->connected && $ms->connected)
{
    //prepare parent
    $lite->prepareForCrossJoin(
        'select * from test1', //query master
        'id', // master field name for join
        null
    );

    //prepare child
    $ms->prepareForCrossJoin(
        'select * from stato', //query child
        'Stato', // child field name for join
        'Descr' // child field name to exstract (description) ONLY USED IN CHILD OBJECT (if '*' take all fields of RecordSet)
    );

    $resultJoin = $lite->executeCrossQuery($ms);

    if(strlen($lite->lastError) > 0 || strlen($ms->lastError) > 0)
    {
        echo $lite->lastError." ".$ms->lastError;
        $lite->closeConnection();
        $ms->closeConnection();
    }
    else
    {
        //echo $resultJoin;
        var_dump($resultJoin);
        $lite->closeConnection();
        $ms->closeConnection();
    }
}
else
{
    echo $lite->lastError." ".$ms->lastError;
}
