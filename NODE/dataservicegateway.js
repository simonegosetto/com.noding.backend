var conn = require('connection');

var result;


conn.getConnection(
    function (err, client)
    {

        client.query('SELECT * FROM mt_User', 
        function(err, rows) 
        {
            // And done with the connection.
            if(err)
            {
                console.log('Query Error');
            }
            res.json(rows);
            client.release();
        });
      }
);     

