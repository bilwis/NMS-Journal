<?php

	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	require_once('libs/autoload.php');
	require_once('db.php');

	// Get $id_token via HTTPS POST.
	$id_token = $_POST['idtoken'];

	$clientId = '48119294740-b0gms2q5oqs81m6mas8ps3t5nio7j4qg.apps.googleusercontent.com';

	$client = new Google_Client();
	$client->setClientId($clientId);

	$payload = $client->verifyIdToken($id_token);
	if ($payload) {
		$userid = $payload['sub'];
		
		$result = $conn->query("SELECT id, name FROM users WHERE google_id='".$userid."'");
		
		if ($result->num_rows > 0) {
			while($row =  $result->fetch_assoc()) {
				if ($row['id'] == 1) {
					if (session_set_cookie_params(86400, '/', 'nms.bilwis.de', TRUE, TRUE))
					{
						session_start();
						$_SESSION['userid'] = $userid;
						$_SESSION['verified'] = true;
						$_SESSION['name'] = $row['name'];
					}					
					//exit;
				}
			}
		}
	} else {
	  	
	}
?>