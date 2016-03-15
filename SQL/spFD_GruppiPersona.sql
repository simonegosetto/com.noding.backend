DROP PROCEDURE IF EXISTS spFD_GruppiPersona;

DELIMITER $$

CREATE PROCEDURE spFD_GruppiPersona(in_persona_id int)
  BEGIN

    SELECT a.*,
      case when g.persona_id is null then 0 else 1 end as Abil
    FROM get_persona_gruppo a
      left join get_persona_gruppo_componenti g on a.id = g.gruppo_id
                                                   and persona_id = in_persona_id;



  END$$

DELIMITER ;
