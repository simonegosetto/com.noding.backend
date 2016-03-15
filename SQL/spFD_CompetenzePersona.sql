DROP PROCEDURE IF EXISTS spFD_CompetenzePersona;

DELIMITER $$

CREATE PROCEDURE spFD_CompetenzePersona(in_persona_id int)
  BEGIN

    SELECT a.id as mansione_id,
			a.nome,
            case when b.persona_id is null then 0 else 1 end as Abil
FROM get_persona_mansione a
left join get_persona_persona_competenze b on a.id = b.mansione_id
and b.persona_id = in_persona_id;


  	END$$

DELIMITER ;
