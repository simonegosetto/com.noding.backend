I've thought to realize a set of classes “connector” for the varius DB, starting from a one abstract class, in order that the classes have almost the same functions.

The additional functional character consists in doing a JOIN between query of different connectors
(the example is on the relevant page), so that you can have a complete array on output with the merge of that one we have outlined on the configuration of the CROSS QUERY.

Configuration:

Into the file “config.inc.ini” you have to configurate the credentials of the varius DB, in which you want to enter on. The credentials will be used by the relatives classes.

I HAVE DONE ALL MY TESTS WITH PC WWINDOWS 7/10 WITH XAMPP AND PHP 7.2.3

###################### SQL SERVER (MSSQL) ######################

Extensions: 
    "extension=sqlsrv_72_ts_x86"
    "extension=sqlsrv_72_ts_x64"

Useful Links for the download and the configurarion of the library srvsql for PHP 7:
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56567 
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56729
 
 Useful files:
  - SG_MsSQL_msodbcsql_17.1.0.1_x64.msi -> dll per estensione PHP a 64bit
  - SG_MsSQL_msodbcsql_17.1.0.1_x86.msi -> dll per estensione PHP a 32bit
  - SG_MsSQL_SQLSRV52.EXE -> driver ODBC per PHP 7
  
############################ SQLITE ############################

Extension: "extension=php_sqlite3.dll"
  
Files utili:
 - SQLite_db_test.db -> esempio di db
  
############################# MYSQL #############################

MySql is based on the library "mysqli" standard for PHP 5+

########################## POSTGRESSQL ##########################

Extension: "extension=php_pgsql.dll"

Tested with PostgresSQL 10

#################################################################

EXAMPLES:
  Into the file “example.php” you find all the examples of code of the varius DB
  