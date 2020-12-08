const express = require('express');
const fs = require('fs');
const app = require('express')();
app.use(express.static('public'));
// const http = require('http');
const https = require('https');
const options = {
    key: fs.readFileSync('/etc/letsencrypt/live/socket.qeat.it/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/socket.qeat.it/fullchain.pem'),
    // ca: fs.readFileSync('./0000_csr-certbot.pem'),
    requestCert: false,
    rejectUnauthorized: false,
};
const server = https.createServer(options,app);
const io = require('socket.io')(server, { origins: '*:*'});

io.on('connection', (socket) => {
    console.log('nuovo client connesso', socket.id);

    // evento di join nella room del cliente di appartenenza
    socket.on('join', (domain) => {
        console.log(`${socket.id} entrato nella room ${domain}`);
        socket.join(domain);
    });

    // test esempio chat di base
    socket.on('chat', (obj) => {
        const {name, message, room} = obj;
        console.log(`(${room})[${name}]: ${message}`);
    });

    // notifica di REFRESH con emissione evento dinamico
    // TODO finire di implementare con tutte le casistiche ed i logs del caso
    socket.on('refresh', (obj) => {
        const {event, data, room} = obj;
        console.log(`(${room})[refresh] evento: ${event}`);
        socket.broadcast.to(room).emit(event, data); // tutti tranne mittente
        // io.sockets.in(room).emit(event, data); // tutti
    });

    socket.on('notify', (obj) => {
        const {event, data, room} = obj;
        console.log(`(${room})[notify] evento: ${event}`);
        socket.broadcast.to(room).emit(event, data); // tutti tranne mittente
        // io.sockets.in(room).emit(event, data); // tutti
    });

});

server.listen(3000);
