<?php

abstract class FD_Define
{
    var $reflectionClass;

    function __construct()
    {
        $this->reflectionClass = new ReflectionClass($this);
    }

    public function getConst()
    {
        return $this->reflectionClass->getConstants();
    }
}

/////////////////////////////////////////////////////

final class EXECUTE_TYPE extends FD_Define
{
    const QUERY = 1;
    const NON_QUERY = 2;
}

final class DB_TYPE extends FD_Define
{
    const MYSQL = 1;
    const MSSQL = 2;
    const POSTGRES = 3;
    const SQLITE = 4;
}

final class REDIS_DATA_TYPE extends FD_Define
{
    const STRING = 1;
    const LIST = 2;
    const HASH = 3;
    const SET = 4;
    const SORTED_SET = 5;
    const JSON = 6;
}
