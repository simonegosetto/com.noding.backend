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

// include
const fs = require('fs');
const express = require('express');
const https = require('https');
const options = {
    key: fs.readFileSync('/etc/letsencrypt/live/backend.noding.it/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/backend.noding.it/fullchain.pem'),
    // ca: fs.readFileSync('./0000_csr-certbot.pem'),
    requestCert: false,
    rejectUnauthorized: false,
};
const body_parser = require('body-parser');
const mysql = require('./DB/mysql');
const faceapi = require('./FaceApi/faceApi');
const pdf = require('html-pdf');
const st = require('./Tools/StringTool');
const stringTool = new st();

const data = new Date().toISOString().substr(0,10);
const logger = require('logger').createLogger('./Log/'+data+'.log'); //'fatal', 'error', 'warn', 'info', 'debug'

// variabili
const port = 8080;
const app = express();
// abilito il parse delle richieste json (POST)
app.use(body_parser.json({extended: true, limit: '20mb'}));
// abilito un livello più avanzato di parsing (oggetti nidificati)
app.use(body_parser.urlencoded({extended: true, limit: '20mb'}));
app.use(function(req, res, next) {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Credentials', 'true');
    res.header('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, x-client-key, x-client-token, x-client-secret, Authorization');
    next();
});

// server in ascolto
const server = https.createServer(options, app);
server.listen(port, () => {
    console.log('server in ascolto sulla porta ',port);
    logger.info('server in ascolto sulla porta ',port);
});
/*app.listen(port,
    function(error) {
        if (error) {
            logger.fatal(error);
            return console.log(error);
        } 
        
        console.log('server in ascolto sulla porta ',port);
        logger.info('server in ascolto sulla porta ',port);
    }
);*/

//gestione del POST
app.post('/dataservicegateway', (req, resp) => {
        // prendo l'host corrente che mi serve per la connesione al db corretto
        const host = req.get('host').split(':')[0];
        // parametri in ingresso
        const params = req.body;
        // logger.info('parametri post: ',req.body);

        // istanzio componente mysql e lo connetto
        const dbMysql = new mysql();
        dbMysql.connection(host).then(
            (result) => {
                // eseguo query passata in ingresso
                dbMysql.execute(params.process, params.params).then(
                    function(data) {
                        // ritorno risposta al client
                        resp.send(data);
                    },
                    (err) => {
                        // ritorno errore al client
                        resp.send(err);
                    }
                );

                //chiudo la connessione al DB
                dbMysql.close();
            }, 
            function(err) {
                console.log("errore di connessione");
                logger.fatal(err);
                resp.send('{"error": "' + err + '"}');
            }
        );
    });

app.get('/generate-invoice',(req, resp) => {
    const host = req.get('host').split(':')[0];
    const params = req.query;
    // leggo file template
    let html = fs.readFileSync('./Templates/fattura.html', 'utf8');

    const dbMysql = new mysql();
    dbMysql.connection(host).then((result) => {
            // eseguo query passata in ingresso
            dbMysql.execute('n_fattura_report', `${params.id},${params.anno},'${params.sezionale}'`).then(
                function(data) {
                    // imposto valori corretti al template
                    const testata = JSON.parse(data).recordset[0];
                    html = html.replace('<%NumeroFattura%>', testata.numero);
                    html = html.replace('<%DataFattura%>', testata.data);
                    html = html.replace('<%Nome%>', testata.nome);
                    html = html.replace('<%Iban%>', testata.iban);
                    html = html.replace('<%MarcaBolloID%>', stringTool.isnull(testata.marcabolloid));
                    html = html.replace('<%RagioneSociale%>', testata.ragionesociale);
                    html = html.replace('<%Indirizzo%>', testata.indirizzo);
                    html = html.replace('<%Localita%>', `${testata.localita} ${testata.cap}(${testata.provincia})`);
                    html = html.replace('<%CodiceFiscale%>', testata.codicefiscale);
                    html = html.replace('<%PartitaIVA%>', testata.partitaiva);
                    html = html.replace('<%Email%>', testata.pec || testata.email);

                    let totale = 0;
                    const righe = JSON.parse(data).recordset.map(item => {
                        totale += item.prezzo;
                        return `
                        <tr>
                            <td style="border-right: 3px solid #ffffff" colspan=2 height="27" align="left" valign=middle bgcolor="#F2F2F2">${item.descrizione}</td>
                            <td align="right" valign=middle bgcolor="#D9D9D9" sdval="500" sdnum="1040;0;[&gt;0]&quot; &euro; &quot;* #.##0,00&quot; &quot;;[&lt;0]&quot;-&euro; &quot;* #.##0,00&quot; &quot;;&quot; &euro; &quot;* -#&quot; &quot;;&quot; &quot;@&quot; &quot;"> &euro; ${item.prezzo},00 </td>
                        </tr>
                        `;
                    }).join('');
                    html = html.replace('<%Righe%>', righe);
                    html = html.replace('<%Totale%>', totale);

                    // genero PDF
                    let filename = `fattura_${testata.numero}_${testata.data.substring(6, 10)}.pdf`;
                    const filepath = `./Temp/${filename}`;
                    pdf.create(html).toFile(filepath, (err, res) => {
                        if (err) return console.log(err);
                        console.log(res);
                        // send('Fattura generata correttamente !');

                        const stream = fs.createReadStream(filepath);
                        filename = encodeURIComponent(filename);
                        resp.setHeader('Content-disposition', 'inline; filename="' + filename + '"');
                        resp.setHeader('Content-type', 'application/pdf');
                        stream.pipe(resp);
                    });
                },
                (err) => {
                    // ritorno errore al client
                    resp.send(err);
                }
            );
        },
        (err) => {
            // ritorno errore al client
            resp.send(err);
        }
    );
});

app.get('/generate-quotation',(req, resp) => {
    const host = req.get('host').split(':')[0];
    const params = req.query;
    // leggo file template
    let html = fs.readFileSync('./Templates/preventivo.html', 'utf8');

    const dbMysql = new mysql();
    dbMysql.connection(host).then((result) => {
            // eseguo query passata in ingresso
            dbMysql.execute('n_preventivo_report', `${params.id}`).then(
                function(data) {
                    // imposto valori corretti al template
                    const testata = JSON.parse(data).recordset[0];
                    html = html.replace('<%NumeroPreventivo%>', testata.numero);
                    html = html.replace('<%DataPreventivo%>', testata.data);
                    html = html.replace('<%Nome%>', testata.nome);
                    html = html.replace('<%RagioneSociale%>', testata.ragionesociale);
                    html = html.replace('<%Indirizzo%>', testata.indirizzo);
                    html = html.replace('<%Localita%>', `${testata.localita} ${testata.cap}(${testata.provincia})`);
                    html = html.replace('<%CodiceFiscale%>', testata.codicefiscale);
                    html = html.replace('<%PartitaIVA%>', testata.partitaiva);
                    html = html.replace('<%Email%>', testata.pec || testata.email);

                    let totale = 0;
                    const righe = JSON.parse(data).recordset.map(item => {
                        totale += item.prezzo;
                        return `
                        <tr>
                            <td style="border-right: 3px solid #ffffff" colspan=2 height="27" align="left" valign=middle bgcolor="#F2F2F2">${item.descrizione}</td>
                            <td align="right" valign=middle bgcolor="#D9D9D9" sdval="500" sdnum="1040;0;[&gt;0]&quot; &euro; &quot;* #.##0,00&quot; &quot;;[&lt;0]&quot;-&euro; &quot;* #.##0,00&quot; &quot;;&quot; &euro; &quot;* -#&quot; &quot;;&quot; &quot;@&quot; &quot;"> &euro; ${item.prezzo},00 </td>
                        </tr>
                        `;
                    }).join('');
                    html = html.replace('<%Righe%>', righe);
                    html = html.replace('<%Totale%>', totale);

                    // genero PDF
                    let filename = `preventivo_${testata.numero}_${testata.data.substring(6, 10)}.pdf`;
                    const filepath = `./Temp/${filename}`;
                    pdf.create(html).toFile(filepath, (err, res) => {
                        if (err) return console.log(err);
                        console.log(res);
                        // send('Fattura generata correttamente !');

                        const stream = fs.createReadStream(filepath);
                        filename = encodeURIComponent(filename);
                        resp.setHeader('Content-disposition', 'inline; filename="' + filename + '"');
                        resp.setHeader('Content-type', 'application/pdf');
                        stream.pipe(resp);
                    });
                },
                (err) => {
                    // ritorno errore al client
                    resp.send(err);
                }
            );
        },
        (err) => {
            // ritorno errore al client
            resp.send(err);
        }
    );
});

// TODO implementare la validità della richiesta con un token
app.post('/face-api', async (req, resp) => {
    if (req.body.hasOwnProperty('image')) {
        const {image} = req.body;
        logger.info(`url to evaluate ${image}`);
        try {
            const faceApi = new faceapi();
            const result = await faceApi.evaluate(image);
            logger.info(result);

            // TODO salvare il risultato sul db

            resp.send(result);
        } catch (e) {
            resp.send(JSON.stringify(e));
        }
    } else {
        resp.send('{"error": "Invalid image url !"}');
    }
});

app.get('/report',(req,resp) => {
        /*
        jsreport.render({
            template: {
                content: "<h1>Hello world from {{this.name}}</h1>",
                recipe: "html"
            },
            data: { name: "jsreport" }
        }).then((out) => {
            //pipes plain text with Hello world from jsreport
            out.stream.pipe(resp);
        });
        */
    });
