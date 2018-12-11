const fs = require('fs');
const mysql = require("mysql");

var connection;

class MySql {

    //connessione con configurazione dinamica
    connection(host)
    {
        var obj_connection = JSON.parse(fs.readFileSync('Config/'+host+'.json', 'utf8'));
        let connected = false;

        connection = mysql.createConnection({
            host: obj_connection.host,
            port:  3306,
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

    //eseguo query
    execute(query)
    {
        connection.query(query,
            (err, result) => {
                if (err) throw err;
                console.log(result);
                return result;
            }
        );
    }


}

module.exports = MySql;