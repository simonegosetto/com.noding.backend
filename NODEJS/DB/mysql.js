const fs = require('fs');
const mysql = require("mysql");
const st = require('../Tools/StringTool');

const data = new Date().toISOString().substr(0,10);
const logger = require('logger').createLogger('./Log/'+data+'.log'); //'fatal', 'error', 'warn', 'info', 'debug'

let connection;
let connected = false;
const string_tool = new st();

class MySql {

    // connessione con configurazione dinamica
    connection(host) {
        const obj_connection = JSON.parse(fs.readFileSync('Config/'+host+'.json', 'utf8'));
        console.log('tentativo di connesione a ' + obj_connection.host + '...');
        connection = mysql.createConnection({
            host: obj_connection.host,
            port:  obj_connection.port,
            user: obj_connection.user,
            password: obj_connection.password,
            database: obj_connection.database,
            // options
            multipleStatements: true
        });
    
        return new Promise((resolve, reject) => {
            connection.connect(
                (err) => {
                    if (err) {
                        reject(err);
                    } else {
                        console.log('connesso...');
                        resolve("connesso...");
                        connected = true;
                    }
            });
        });
    }

    // eseguo query (stored procedure)
    execute(process, params) {
        let output = false;
        let query = "call " + process + "(" + string_tool.isnull(params) + ");";    

        // controllo parametri di output
        if (string_tool.isnull(params).indexOf(",@") > -1) {
            output = true;
            // gestione parametri di output
            let output_query = "select ";
            let output_params = params.split(",");
            for(let i=0;i<output_params.length;i++) {
                if (output_params[i].substr(0,1) === "@") {
                    output_query += output_params[i] + " as " + output_params[i].replace("@","") + ",";
                }
            }
            output_query = output_query.substr(0,output_query.length-1)+";";
            query += output_query;
        }

        logger.info('query: ',query);

        return new Promise((resolve, reject) => {
            connection.query(query, (err, result) => {
                    if (err) {
                        logger.error(err);
                        reject('{"error": "' + err + '"}');
                    }

                    if (result !== undefined && result.length > 0) {
                        // console.log(result);
                        
                        // controllo se la stored restituisce un errore
                        let stored_error = false;
                        if (JSON.stringify(result[0]).indexOf('"error"') > -1) stored_error = true;

                        resolve('{"recordset":' + JSON.stringify(result[0]) 
                        + (output === true ? ', "output": ' + JSON.stringify(result[1]) : '')
                        + (stored_error === true ? ', "error": ' + JSON.stringify(result[0]["error"]) : '')
                        + '}');
                    } else {
                        resolve('{"recordset":[]}');
                    }
                }
            );
        });
    }

    // chiudo connessione
    close() {
        connection.end();
    }

}

module.exports = MySql;
