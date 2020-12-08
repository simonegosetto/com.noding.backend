const fs = require('fs');
const nodemailer = require('nodemailer');
const dotenv = require('dotenv');
const express = require('express');
const cors = require('cors');
const https = require('https');
const bodyParser = require('body-parser');
const winston = require('winston');

// http://www.google.com/accounts/unlockcaptcha
// https://myaccount.google.com/u/4/lesssecureapps
// https://myaccount.google.com/lesssecureapps

dotenv.config({path: `/var/www/nodejs/.mail`});

const logger = winston.createLogger({
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.prettyPrint()
    ),
    transports: [
        new winston.transports.File({filename: `./log/mail.log`})
    ]
});

/**
 * Configurazioni del server
 */
const app = express();
const options = {
    key: fs.readFileSync('/etc/letsencrypt/live/email.qeat.it/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/email.qeat.it/fullchain.pem'),
    requestCert: false,
    rejectUnauthorized: false,
};

// abilito il parse delle richieste json (POST)
app.use(bodyParser.json({limit: '20mb'}));
app.use(bodyParser.urlencoded({extended: true, limit: '20mb'}));
app.use(cors());

const server = https.createServer(options, app);
server.listen(4000, () => {
    console.log(`server in ascolto sulla porta 4000`);
});


app.post('/send', (req, resp) => {
    try {
        const configuration = req.body.configuration;
        const transporter = nodemailer.createTransport({
            service: process.env[`SERVICE_${configuration.module.toUpperCase()}`],
            auth: {
                user: process.env[`USERNAME_${configuration.module.toUpperCase()}`],
                pass: process.env[`PASSWORD_${configuration.module.toUpperCase()}`]
            }
        });

	let mailTemplate = fs.readFileSync(`/var/www/nodejs/templates/${configuration.module}_${configuration.subject}.html`).toString();

	configuration.bodyParams.forEach((param) => {
		mailTemplate = mailTemplate.replace(`<%${param.variable}%>`, param.value);
	});

        const mailOptions = {
            from: process.env[`USERNAME_${configuration.module.toUpperCase()}`],
            to: configuration.to,
            subject: configuration.subject,
            html: mailTemplate,
	    bcc: process.env[`USERNAME_${configuration.module.toUpperCase()}`]
        };

        transporter.sendMail(mailOptions, (err, info) => {
            if (err) {
                console.log(err);
                resp.sendStatus(500);
                return;
            }
            // console.log(info);
            resp.send(info);
        });
    } catch (e) {
	console.log(e);
        resp.sendStatus(500);
    }
});

