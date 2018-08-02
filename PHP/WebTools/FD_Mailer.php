<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 16/05/2016
 * Time: 23:54
 */

require 'PHPMailer/PHPMailerAutoload.php';

final class FD_Mailer
{

    function __construct()
    {
        $this->mail = new PHPMailer;
    }

    public function SendMail($gestione, $item, $resetToken = '')
    {
        $this->mail->isSMTP();
        if($gestione == "volontapp")
        {
            /**
             * $item->tipo
             * 1-> Segnalazione associazione non registrata
             * 2-> Segnalazione interesse modulo
             * 3-> Registrazione
             * 4-> Reset password
             */
            $obj = json_decode(json_encode($item), True);
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
            $this->mail->SMTPSecure = 'tls'; // abilitazione ssl
            $this->mail->Port = 587;
            $this->mail->setFrom('app@volontapp.it', 'VolontApp');
            if($obj["tipo"] != 4)
            {
                $this->mail->addAddress('commerciale@volontapp.it', 'Commerciale VolontApp');
            }else
            {
                //Controllo validità mail
                if (!filter_var($obj["email"], FILTER_VALIDATE_EMAIL) === false)
                {
                    $this->mail->addAddress($obj["email"], 'Staff VolontApp'); // il nome è opzionale
                } else
                {
                    return;
                }
            }
            //$mail->addAddress('ellen@example.com');
            //$mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');
            //$mail->addAttachment('/var/tmp/file.tar.gz');
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

            $this->mail->isHTML(true);
            if($obj["tipo"] == 1)
            {
                $this->mail->Subject = 'Segnalazione nuova Associazione';
                $this->mail->Body = '<p>Volontario: '.$obj["nome"].' '.$obj["cognome"].'</p>'.
                                    '<p>Mail/Username: '.$obj["username"].'</p>'.
                                    '<p>Associazione segnalata: "'.$obj["associazione"].'"</p>';
            }else if($obj["tipo"] == 2)
            {
                $this->mail->Subject = 'Interesse modulo "'.$obj["modulo"].'"';
                $this->mail->Body = '<p>Volontario: '.$obj["nome"].' '.$obj["cognome"].'</p>'.
                                    '<p>Mail/Username: '.$obj["username"].'</p>'.
                                    '<p>Associazione: '.$obj["associazione"].'</p>';
            }else if($obj["tipo"] == 3)
            {
                $this->mail->Subject = 'Registrazione VolontApp';
                $this->mail->Body = '<p>Gentile <b>'.$obj["nome"].' '.$obj["cognome"].'</b></p>'.
                    '<p>grazie per esserti registrato su VolontApp, le tue credenziali di accesso sono:</p>'.
                    '<p>Username: '.$obj["username"].'</p>'.
                    '<p>Password: '.$obj["password"].'</p>'.
                    '<p>Cordiali saluti</p>';
            }else if($obj["tipo"] == 4)
            {
                $this->mail->Subject = 'Recupero password VolontApp';
                $this->mail->Body = '<p>Hai richiesto di cambiare la password al tuo account VolontApp</p>'.
                    '<p>Per procedere clicca sul seguente link:</p>'.
                    '<p><a href="http://my.volontapp.it/#/app/reset/'.$resetToken.'">http://my.volontapp.it</a></p>'.
                    '<p>Cordiali saluti</p>';
            }
            //$this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }

        if(!$this->mail->send())
        {
            echo '{"error" : "'.$this->mail->ErrorInfo.'"}';
        }
    }
}

