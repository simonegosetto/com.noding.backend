
Ho pensato di realizzare un insieme di classi "connector" per i vari DB partendo da un'unica classe astratta in modo che abbiamo pressocchè le stesse funzioni. 

Configurazione:

nel file "config.inc.ini" dovete andare a configurare le credenziali ai vari DB a cui volete acccedere, esse verranno usate dalle relative classi.

TUTTI I MIEI TEST SONO STATI FATTI CON PC WINDOWS 7/10 CON PHP 7.2.6

###################### SQL SERVER (MSSQL) ######################

EstenzionI: 
    "extension=php_sqlite3.dll"

Link utili per il download e la configurazione della libreria srvsql in PHP 7:
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56567 
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56729
 
 Files utili:
  - SG_MsSQL_msodbcsql_17.1.0.1_x64.msi -> dll per estensione php a 64bit
  - SG_MsSQL_msodbcsql_17.1.0.1_x86.msi -> dll per estensione php a 32bit
  - SG_MsSQL_SQLSRV52.EXE -> driver ODBC per php 7
  
############################ SQLITE ############################

Estenzione: "extension=php_sqlite3.dll"
  
Files utili:
 - SQLite_db_test.db -> esempio di db
  
############################# MYSQL #############################

MySql si basa sulla libreria "mysqli" standard per PHP 5+

########################## POSTGRESSQL ##########################

Estensione: "extension=php_pgsql.dll"

Testato con PostgresSQL 10


#################################################################

ESEMPI:
  trovate tutti gli esempi di codice dei vari DB nel file "example.php"
  
  
  
  