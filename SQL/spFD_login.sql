DROP PROCEDURE IF EXISTS spFD_login;

DELIMITER $$

CREATE PROCEDURE spFD_login (
	IN in_username varchar(100),
	IN in_password varchar(128)
)

BEGIN
  -- Controllo che esista l'utente
  IF (select 1 from users where username = in_username and password = in_password) > 0 then
   BEGIN
    select u.*,
          a.azi_desc,
          a.azi_db
    from users u
      inner join aziende a on u.azi_codi = a.azi_codi
    where username = in_username and password = in_password;
     end;
   END IF;

END$$

DELIMITER ;