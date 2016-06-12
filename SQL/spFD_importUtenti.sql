CREATE PROCEDURE `spFD_importUtenti`()
BEGIN

	declare in_azi_codi int;
	set in_azi_codi = 10;

	/*INSERT INTO `authDB`.`users`
	(`username`,
	`email`,
	`password`,
	`name`,
	`surname`,
	`cod_fisc`,
	`usr_tipo`,
	`telefono`)*/
	select distinct u.email,
			u.email,
			u.password,
			nome,
			cognome,
			'',
			2,
			ifnull(tel1,tel2)
	from get_persona_persona p
	inner join auth_user u on p.user_id = u.id
	where length(ifnull(u.email,''))>0
	and u.email not in ('no@no.it')
    and u.username not in (select username from authDB.users);


	/*
    INSERT INTO `authDB`.`archivio_users_aziende`
	(`azi_codi`,
	`usr_codi`,
	`fromUpdate`,
	`get_id`,
	`dipendente`,
	`staff`,
	`get_imported`,
	`poweruser`)*/
	select in_azi_codi,
			uu.id,
			0,
			p.id,
			p.dipendente,
			u.is_staff,
			1,
			u.is_superuser
	from authDB.users uu
	inner join auth_user u on uu.username = u.email
	inner join get_persona_persona p on u.id = p.user_id;

END