DROP PROCEDURE IF EXISTS spFD_CheckDB;

DELIMITER $$

CREATE DEFINER=`root`@`%` PROCEDURE `spFD_CheckDB`(in_token varchar(1000), in_db varchar(100))
  BEGIN

    DECLARE CheckExistsToken int;
    DECLARE CheckExistsAdmin int;

    SET CheckExistsToken = 0;
    SET CheckExistsAdmin = 0;

    SELECT
      COUNT(0)
    INTO CheckExistsToken FROM
      sessions_log u
      INNER JOIN
      users uu ON u.usr_id = uu.id
      INNER JOIN
      aziende a ON uu.azi_codi = a.azi_codi
    WHERE
      a.azi_db = in_db
      AND u.usr_token = in_token
      AND uu.admin = 0;

    SELECT
      COUNT(0)
    INTO CheckExistsAdmin FROM
      sessions_log u
      INNER JOIN
      users uu ON u.usr_id = uu.id
    WHERE
      uu.admin = 1 AND u.usr_token = in_token;

    IF (CheckExistsToken > 0 or CheckExistsAdmin > 0 ) then

      SELECT 1 as Abil;
    else
      SELECT 0 as Abil from users where false;

    END IF;


  END