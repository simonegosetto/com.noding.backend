<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 07/07/14
 * Time: 22.04
 */
class FD_PushNotifications {

	var $conn;
    var $validatedRequest;  //Richiesta al serverExpress validata si/no

    public function FD_PushNotifications($keyRequest=""){
        if($keyRequest == md5_file("http://simonegosetto.it/FD_Components/esatto.mp3")){
            $this->validatedRequest=true;

            $hostname = "mysql51-80.perso";
            $username = "simonego";
            $password = "gose90";
            $database = "simonego";
            /*
            $hostname = "flexibledevs.com.mysql";
            $username = "flexibledevs_co";
            $password = "hKYsKUBu";
            $database = "flexibledevs_co";
             */
            $conn = mysql_connect($hostname, $username,$password);
            if(!$conn){
                echo 'Nessuna connessione al serverExpress: ' . mysql_error($conn);
                return;
            }

            mysql_select_db($database, $conn);

            echo "Connesso al db<br>";
        }else{
            $this->validatedRequest=false;
            echo "Richiesta al serverExpress non valida";
        }
    }

    //Ritorna il valore decriptato
    private function decrypt($encrypted_string, $encryption_key) {
        $encrypted_string = base64_decode($encrypted_string);
        $iv = substr($encrypted_string, strrpos($encrypted_string, "-[--IV-[-") + 9);
        $encrypted_string = str_replace("-[--IV-[-".$iv, "", $encrypted_string);
        $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $encrypted_string, MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    public function Push($mess,$ios_pass,$ios_pem,$android_key,$android_title,$tipo,$redirect_page='',$app_id,$token=''){
        //Android
		if($tipo == 0 || $tipo == 1){
			echo 'Device Android<br/>';
			$ids =  array();

			if(strlen($token)==0){
				$rs = mysql_query("SELECT * FROM PUSH_android WHERE app_id = $app_id");
			}else{
				$rs = mysql_query("SELECT * FROM PUSH_android WHERE device = '$token'");
			}

			while($row = mysql_fetch_array( $rs ) ){
				$ids[] = $row['device'];
			}

			$fields = array(
				'registration_ids'  => $ids,
				'data'              =>  array('title' => $android_title, 'alert' => $mess, 'type' => 'sketch_created', 'id' => $android_key)
			);

			$headers = array(
				'Authorization: key=' . $android_key,
				'Content-Type: application/json'
			);

			// Open connection
			$ch = curl_init();

			// Set the url, number of POST vars, POST data
			curl_setopt( $ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );

			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );

			// Execute post
			$result = curl_exec($ch);

			//Controllo errore
			if ( curl_errno( $ch ) ){
				echo 'GCM error: ' . curl_error( $ch );
			}

			// Close connection
			curl_close($ch);
			echo $result."<br/>";
		}


        //iOS
		if($tipo == 0 || $tipo == 2){
			if(strlen($ios_pass) > 0 && strlen($ios_pem) > 0){
				echo 'Device Apple<br>';

				if(strlen($token)==0){
					$rs = mysql_query("SELECT * FROM PUSH_ios WHERE app_id = $app_id");
				}else{
					$rs = mysql_query("SELECT * FROM PUSH_ios WHERE device = '$token'");
				}

				////////////////////////////////////////////////////////////////////////////////

				while($row = mysql_fetch_array( $rs ) ){
					// Put your device token here (without spaces):
					$deviceToken = $row['device']; //'6DA37E9D0C1758FA461A6F760210CA69508BF7877FB24A26428D7DF576C244D7';

					// Put your private key's passphrase here:
					$passphrase = $ios_pass;

					// Put your alert message here:
					$message = $mess;

					////////////////////////////////////////////////////////////////////////////////

					//Controllo esistenza del certificato
					if(file_exists($ios_pem)){
						echo "File esistente !";
					}else{
						echo "File non esiste !";
						break;
					}

					echo $ios_pem;

					$ctx = stream_context_create();
					stream_context_set_option($ctx, 'ssl', 'local_cert', $ios_pem);
					stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

					// Open a connection to the APNS serverExpress
					$fp = stream_socket_client(
						'ssl://gateway.sandbox.push.apple.com:2195', $err,
						$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

					if (!$fp)
						exit("Failed to connect: $err $errstr" . PHP_EOL);

					echo 'Connected to APNS' . PHP_EOL;

					// Create the payload body
					$body['aps'] = array(
						'alert' => $message,
						'sound' => 'default'
						);

					// Encode the payload as JSON
					$payload = json_encode($body);

					// Build the binary notification
					$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

					// Send it to the serverExpress
					$result = fwrite($fp, $msg, strlen($msg));

					if (!$result)
						echo 'Message not delivered' . PHP_EOL;
					else
						echo 'Message successfully delivered' . PHP_EOL;

					// Close the connection to the serverExpress
					fclose($fp);

				}
				mysql_close($conn);
			}
		}

		//Redirect alla pagina interessata
		if(strlen($redirect_page)>0){
			//echo "<a href='".$redirect_page."'>Indietro</a>";
			header("Location: $redirect_page");
		}
    }
}

?>
