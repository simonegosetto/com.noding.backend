<?php

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
        if($ms->connected)
        {
            $ms->closeConnection();
        }
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
include "SG_SQLite.php";

$lite = new SG_SQLite("SQLite_db_test.db");

if($lite->connected)
{
    //$result = $lite->executeSQL("CREATE TABLE test1(id int, desc varchar(10))");
    //$result = $lite->executeSQL("INSERT INTO test1(id,desc) VALUES(1,'test')");
    $result = $lite->exportJSON("SELECT * FROM test1");


    if(strlen($lite->lastError) > 0)
    {
        echo $lite->lastError;
        if($lite->connected)
        {
            $lite->closeConnection();
        }
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
