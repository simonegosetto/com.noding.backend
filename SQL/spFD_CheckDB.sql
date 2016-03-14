DROP PROCEDURE IF EXISTS spFD_CheckDB;

DELIMITER $$

CREATE PROCEDURE spFD_CheckDB(in_token varchar(1000),
                              in_db varchar(100))
BEGIN
	IF (select 1 from sessions_log u
                inner join users uu on u.usr_id = uu.id
	              inner join aziende a on uu.azi_codi = a.azi_codi
	              where a.azi_db = in_db
	              and u.usr_token = in_token) > 0 or (select 1 from sessions_log u
                                          inner join users uu on u.usr_id = uu.id
                                                where uu.admin = 1
                                                and u.usr_token = in_token ) then
   BEGIN
      SELECT 1 as Abil;
   END;
   END IF;
END$$

DELIMITER ;