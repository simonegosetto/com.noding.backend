var fs = require('fs');
var mysql = require("mysql");

var obj_connection = JSON.parse(fs.readFileSync('Config/config.json', 'utf8'));

var con = mysql.createConnection({
    host: obj_connection.host,
    port:  3306,
    user: obj_connection.user,
    password: obj_connection.pass,
    database: obj_connection.database
});

con.connect(function(err) {
    if (err) throw err;
    console.log("Connected!");
});