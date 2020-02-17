<?php
	session_start();
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

	require_once("file_upload.php");

	$imageFolder = "../upload/img/articles/";

	if ($_SESSION['verified'] == true){
		
		echo json_encode(array('location' => handle_tinymce($imageFolder)));
		
	} else {
		header("HTTP/1.1 403 Site access denied.");
	}
?>

