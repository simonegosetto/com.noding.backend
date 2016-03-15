DROP PROCEDURE IF EXISTS spFD_Anagrafica_Persone_lst;

DELIMITER $$

CREATE DEFINER=`root`@`%` PROCEDURE `spFD_Anagrafica_Persone_lst`()
	BEGIN
		select u.is_superuser, u.email, u.is_active, u.is_staff,
			p.nome, p.cognome, p.indirizzo, p.nascita, p.tel1
		from auth_user u
			inner join get_persona_persona p on u.id = p.user_id;
	END$$

DELIMITER ;