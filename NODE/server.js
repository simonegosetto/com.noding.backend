"use strict";

const express = require('express');
const port = 3000;
const app = express();
const body_parser = require('body-parser');
const db_mysql = require('./db_mysql');

//abilito il parse delle richieste json (POST)
app.use(body_parser.json());
//abilito un livello più avanzato di parsing (oggetti nidificati)
app.use(body_parser.urlencoded({extended: true}));


//gestione del POST
app.post('/dataservicegateway', 
    (req,resp) =>
    {
        //parametri req.body
        let host = req.get('host').split(':')[0];

        var db_mysql_local = new db_mysql();
        db_mysql_local.connection(host).then(
            function(result) 
            {
                console.log(result);
                db_mysql_local.execute("select * from test1");
            }, 
            function(err) 
            {
                console.log(err);
            }
        );

        /*db_mysql_local.query("select * from test1",
            (err, result) => {
                if (err) throw err;
                console.log(result);
                resp.send(result);
            }
        );*/
    }
);

//server in ascolto
app.listen(port,
    function(error)
    {
        if(error) return console.log('errore');
        console.log('server in ascolto sulla porta ',port);
    }
);