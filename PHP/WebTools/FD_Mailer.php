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
    var $SMTP_HOST;
    var $SMTP_PORT;
    var $SMTP_AUTH;
    var $SMTP_USERNAME;
    var $SMTP_PASSWORD;
    var $SMTP_FROM;


    function __construct()
    {
        $this->mail = new PHPMailer;
        $ini_array = parse_ini_file("mail.ini");
        $this->mail->isSMTP();
        $this->mail->Host = $ini_array["SMTP_HOST"];
        $this->mail->Port = $ini_array["SMTP_PORT"];
        $this->mail->SMTPAuth = $ini_array["SMTP_AUTH"];
        $this->mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Username = $ini_array["SMTP_USERNAME"];
        $this->mail->Password = $ini_array["SMTP_PASSWORD"];
        $this->mail->setFrom($this->mail->Username, $ini_array["SMTP_FROM"]);
        $this->mail->isHTML(true);
    }

    public function SendMail($item)
    {
        $obj = json_decode(json_encode($item), true);
        $this->mail->addAddress('simone.gosetto@gmail.com', 'Conferma Prenotazione');
        //$mail->addAddress('ellen@example.com');
        //$mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');
        //$mail->addAttachment('/var/tmp/file.tar.gz');
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');
        $this->mail->Subject = 'Segnalazione nuova Associazione';
        $this->mail->Body = '<p>Volontario: '.$obj["nome"].' '.$obj["cognome"].'</p>'.
                            '<p>Mail/Username: '.$obj["username"].'</p>'.
                            '<p>Associazione segnalata: "'.$obj["associazione"].'"</p>';

        if(!$this->mail->send())
        {
            echo '{"error" : "'.$this->mail->ErrorInfo.'"}';
        }
    }
}

