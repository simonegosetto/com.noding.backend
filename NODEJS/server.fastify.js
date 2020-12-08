const fs = require('fs')
// const path = require('path')
const fastify = require('fastify')({
    logger: true,
    https: {
        key: fs.readFileSync('/etc/letsencrypt/live/rack.myvirtualfarm.it/privkey.pem'),
        cert: fs.readFileSync('/etc/letsencrypt/live/rack.myvirtualfarm.it/fullchain.pem'),
        requestCert: false,
        rejectUnauthorized: false,
    }
})
fastify.register(require('fastify-express'))
fastify.register(require('fastify-cors'))
fastify.register(require('fastify-mysql'), {
    promise: true,
    host: 'localhost',
    user: 'rack',
    database: 'rack',
    password: '77DSEFAasda17!',
})

fastify.post('/settings', async (request, reply) => {
    console.log('headers', request.headers);
    if (request.headers.hasOwnProperty('authorization')) {
        try {
            const connection = await fastify.mysql.getConnection();
            const [rows, fields] = await connection.query(`call sys_host_settings (?);`, [request.body.host])
            connection.release()
            return rows[0];
        } catch (e) {
            reply
                .code(500)
                .header('Content-Type', 'application/json; charset=utf-8')
                .send({ error: e.message })
        }
    } else {
     reply
        .code(403)
        .header('Content-Type', 'application/json; charset=utf-8')
        .send({ error: 'Richiesta non autorizzata' })
    }
})

fastify.listen(5000, '0.0.0.0')
