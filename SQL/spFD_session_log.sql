DROP PROCEDURE IF EXISTS spFD_session_log;

DELIMITER $$

CREATE PROCEDURE spFD_session_log (
	IN in_user_id int,
	IN in_token varchar(50)
)

BEGIN

 insert into sessions_log(usr_id,usr_token,usr_date)
 values (in_user_id,in_token,NOW());

END$$

DELIMITER ;