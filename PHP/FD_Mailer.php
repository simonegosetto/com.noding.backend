<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 16/05/2016
 * Time: 23:54
 */

include 'PHPMailer/PHPMailerAutoload.php';

class FD_Mailer {

    function FD_Mailer(){
        $this->mail = new PHPMailer;
    }

    public function SendMail($gestione, $item){
        $this->mail->isSMTP();
        if($gestione == "volontapp") {
            /**
             * $item->tipo
             * 1-> Segnalazione associazione non registrata
             * 2-> Segnalazione interesse modulo
             */
            $this->mail->Host = 'smtp.volontapp.it';
            $this->mail->SMTPAuth = true;
            //forzatura
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $this->mail->Username = 'app@volontapp.it';
            $this->mail->Password = 'VappStiip10';
            $this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $this->mail->Port = 587;
            $this->mail->setFrom('app@volontapp.it', 'VolontApp');
            $this->mail->addAddress('simone.gosetto@gmail.com', 'Commerciale VolontApp'); // Name is optional
            //$mail->addAddress('ellen@example.com');
            //$mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');
            //$mail->addAttachment('/var/tmp/file.tar.gz');
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            $obj = json_decode(json_encode($item), True);
            $this->mail->isHTML(true);
            if($obj["tipo"] == 1) {
                $this->mail->Subject = 'Segnalazione nuova Associazione';
                $this->mail->Body = '<p>Volontario: '.$obj["nome"].' '.$obj["cognome"].'</p>'.
                                    '<p>Mail/Username: '.$obj["username"].'</p>'.
                                    '<p>Associazione segnalata: "'.$obj["associazione"].'"</p>';
            }else if($obj["tipo"] == 2){
                $this->mail->Subject = 'Interesse modulo "'.$obj["modulo"].'"';
                $this->mail->Body = '<p>Volontario: '.$obj["nome"].' '.$obj["cognome"].'</p>'.
                                    '<p>Mail/Username: '.$obj["username"].'</p>'.
                                    '<p>Associazione: '.$obj["associazione"].'</p>';
            }else if($obj["tipo"] == 3){
                $this->mail->Subject = 'Registrazione VolontApp';
                $this->mail->Body = '<p>Gentile <b>'.$obj["nome"].' '.$obj["cognome"].'</b></p>'.
                    '<p>grazie per esserti registrato su VolontApp, le tue credenziali di accesso sono:</p>'.
                    '<p>Username: '.$obj["username"].'</p>'.
                    '<p>Password: '.$obj["password"].'</p>'.
                    '<p>Cordiali saluti</p>';
            }
            //$this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }

        if(!$this->mail->send()) {
            echo '{"error" : "'.$this->mail->ErrorInfo.'"}';
        }
    }
}

