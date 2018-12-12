"use strict";

const fs = require('fs');
const mysql = require("mysql");
const st = require('../Tools/StringTool');

var connection;
var connected = false;
var string_tool = new st();

class MySql {

    //connessione con configurazione dinamica
    connection(host)
    {
        var obj_connection = JSON.parse(fs.readFileSync('Config/'+host+'.json', 'utf8'));
        console.log('tentativo di connesione a ' + host + '...');
        connection = mysql.createConnection({
            host: obj_connection.host,
            port:  obj_connection.port,
            user: obj_connection.user,
            password: obj_connection.pass,
            database: obj_connection.database
        });
    
        return new Promise(function(resolve, reject) {
            connection.connect(
                (err) => {
                    if (err)
                    { 
                        reject(err);
                    }
                    else
                    {
                        resolve("Connected!");
                        connected = true;
                    }
            });
        });
    }

    //eseguo query (stored procedure)
    execute(process, params)
    {
        let query = "call " + process + "(" + string_tool.isnull(params) + ");";

        connection.query(query,
            (err, result) => {
                if (err) throw err;
                console.log(result);
                connection.end();
                return result;
            }
        );
    }


}

module.exports = MySql;