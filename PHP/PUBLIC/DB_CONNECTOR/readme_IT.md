
Ho pensato di realizzare un insieme di classi "connector" per i vari DB partendo da un'unica classe astratta in modo che abbiamo pressocchè le stesse funzioni. 

La funzionalità aggiuntiva che a me è servita e per il quale ho pensato di realizzare tutto questo è quella di poter fare un JOIN tra query di diversi connector 
(l'esempio è presente nell'apposita pagina), in modo da avere in output un array completo con il merge di quello che abbiamo indicato nella configurazione della CROSS QUERY.

Configurazione:

nel file "config.inc.ini" dovete andare a configurare le credenziali dei vari DB a cui volete acccedere, esse verranno usate dalle relative classi.

TUTTI I MIEI TEST SONO STATI FATTI CON PC WINDOWS 7/10 CON XAMPP CON PHP 7.2.3

###################### SQL SERVER (MSSQL) ######################

Estensioni: 
    "extension=sqlsrv_72_ts_x86"
    "extension=sqlsrv_72_ts_x64"

Link utili per il download e la configurazione della libreria srvsql in PHP 7:
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56567 
 - https://www.microsoft.com/en-us/download/confirmation.aspx?id=56729
 
  
############################ SQLITE ############################

Estensione: "extension=php_sqlite3.dll"
  
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
  
  
  
  