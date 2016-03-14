DROP PROCEDURE IF EXISTS spFD_Anagrafica_Persone_lst;

DELIMITER $$

CREATE PROCEDURE spFD_Anagrafica_Persone_lst ()
BEGIN
   select u.*,
		p.*
	from auth_user u
	inner join get_persona_persona p on u.id = p.user_id;
END$$

DELIMITER ;