<?php

#MICROSOFT SQL SERVER
include "SG_MsSql.php";

$ms = new SG_MsSql();
if($ms->connected)
{
    $result = $ms->exportJSON("select * from Utente");

    if(strlen($ms->lastError) > 0)
    {
        echo $ms->lastError;
        if($ms->connected)
        {
            $ms->closeConnection();
        }
        return;
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


