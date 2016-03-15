DROP PROCEDURE IF EXISTS spFD_databases;

DELIMITER $$

CREATE PROCEDURE spFD_databases ()

BEGIN

 show databases;

END$$

DELIMITER ;