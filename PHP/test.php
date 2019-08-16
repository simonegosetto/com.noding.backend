<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require("Dropbox/FD_DropboxAPI.php");

$origin = $_SERVER["SERVER_NAME"];

$dp = new FD_DropboxAPI();
/*
$result = $dp->upload("/".$origin."/asd.mp3", file_get_contents('Config/esatto.mp3'));
echo $result;
if(strpos($result,"error") !== false)
{
    // {"error_summary": "path/insufficient_space/..", "error": {".tag": "path", "reason": {".tag": "insufficient_space"}, "upload_session_id": "AAAAAAAAHCidn026PInD3A"}}
    echo json_decode($result, true)["error"]["reason"][".tag"];
}
else
{
    // {"name": "FD_Upload.php", "path_lower": "/fd_upload.php", "path_display": "/FD_Upload.php", "id": "id:kYe03Rd5rwAAAAAAAAAACw", "client_modified": "2019-08-16T13:01:27Z", "server_modified": "2019-08-16T13:01:27Z", "rev": "015903b969c1bb7000000016bbb2ab0", "size": 62, "is_downloadable": true, "content_hash": "aed09c1b1f002b268424c3985848358d5c5c4ffbe1006829d0c9f978593cdcfc"}
}
*/
$result = $dp->download("id:kYe03Rd5rwAAAAAAAAAADw");
echo $result;
// header('Location: '.$result);


/*
require 'PHPMailer/PHPMailerAutoload.php';

$mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'smtp.volontapp.it';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'app@volontapp.it';                 // SMTP username
$mail->Password = 'VappStiip10';                           // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to

$mail->setFrom('app@volontapp.it', 'VolontApp');
$mail->addAddress('simone.gosetto@gmail.com', 'Joe User');     // Add a recipient
//$mail->addAddress('ellen@example.com');               // Name is optional
//$mail->addReplyTo('info@example.com', 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = 'Here is the subject';
$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}
*/


