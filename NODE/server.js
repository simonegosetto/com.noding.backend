/**
 * Created by VSCode.
 * User: Simone Gosetto
 * Date: 11/12/2018
 * Time: 23:55
 *
 * DATASERVICEGATEWAY
 * Per la gestione di tutte le richieste al DB + le varie estensioni (mail, push, report)
 *
 * INPUT:
 * token -> per autenticare la richiesta (implementato formato JWT)
 * process -> stored sql cryptata
 * params -> parametri per stored sql
 * type -> tipo di query (query/non query)
 *
 * OUTPUT:
 * error/debug
 * recordset
 * output
 *
 * Tutte le richieste vengono loggate nella cartella "Log" e viene fatto un file per giorno
 *
 * VERSIONE 1.0.0
 *
 */
"use strict";

const express = require('express');
const port = 3000;
const app = express();
const body_parser = require('body-parser');
const db_mysql = require('./DB/db_mysql');

var data = new Date().toISOString().substr(0,10);

const logger = require('logger').createLogger('./Log/'+data+'.log'); //'fatal', 'error', 'warn', 'info', 'debug'

//abilito il parse delle richieste json (POST)
app.use(body_parser.json());
//abilito un livello più avanzato di parsing (oggetti nidificati)
app.use(body_parser.urlencoded({extended: true}));

//server in ascolto
app.listen(port,
    function(error)
    {
        if(error)
        {
            logger.fatal(error);
            return console.log(error);
        } 
        
        console.log('server in ascolto sulla porta ',port);
        logger.info('server in ascolto sulla porta ',port);
    }
);

//gestione del POST
app.post('/dataservicegateway', 
    (req,resp) =>
    {
        //prendo l'host corrente che mi serve per la connesione al db corretto
        let host = req.get('host').split(':')[0];
        //parametri in ingresso
        let params = req.body;
        logger.info('parametri post: ',req.body);

        //istanzio componente mysql e lo connetto
        var db_mysql_local = new db_mysql();
        db_mysql_local.connection(host).then(
            function(connection_result) 
            {
                //eseguo query passata in ingresso
                let result = db_mysql_local.execute(params.process, params.params);
                resp.send(result);
            }, 
            function(err) 
            {
                console.log(err);
                logger.fatal(err);
                resp.send(err);
            }
        );
    }
);

