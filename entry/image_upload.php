<?php
	session_start();
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

	require_once("file_upload.php");

	$imageFolder = "../upload/img/";

	if ($_SESSION['verified'] == true){
		
		handle_upload($imageFolder, TRUE);
		
	} else {
		header("HTTP/1.1 403 Site access denied.");
	}


    //@TODO: Make images smaller pls
?>

