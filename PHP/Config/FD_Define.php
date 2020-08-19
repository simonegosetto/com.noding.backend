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

final class GOOLE_SERVICE_ACTION extends FD_Define
{
    const AUTHENTICATION_URL_GET = 'authentication_url_get';
    const AUTHENTICATION_CODE_SET = 'authentication_code_set';
    const USER_INFO_GET = 'user_info_get';
    const CALENDAR_GEST = 'calendar_gest';
}

final class GOOLE_SERVICE_ACTION_MODE extends FD_Define
{
    const BEFORE_DB_CALL = 1;
    const AFTER_DB_CALL = 2;
}

final class DROPBOX extends FD_Define
{
    const UPLOAD = 1;
    const DOWNLOAD = 2;
	const DELETE = 3;
	const GET = 4;
	const PREVIEW = 5;
}
