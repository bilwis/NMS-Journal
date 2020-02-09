<?php
    require_once('../db.php');
	require_once('../libs/autoload.php');	
	require_once('../entry/file_upload.php');
	
	//--------------------
	//Security Setup
	//--------------------

	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();

	if (isset($_SESSION['verified']))
	{
		if (!$_SESSION['verified']){
			header("HTTP/1.1 403 Site access denied.");
			exit();
		} 
	} else {
		header("HTTP/1.1 403 Site access denied.");
		exit();
	}
	
	//--------------------
	//Handle POST data
	//--------------------

	$text = $_POST['text'];
	$parent_uuid = $_POST['uuid'];
	$parent_table = $_POST['source_table'];
	$parent_type = $_POST['return_type'];
	$author = $_POST['author'];
	$heading = htmlspecialchars($_POST['heading']);
	
	//--------------------
	//Check input
	//--------------------

	//Check if content is empty
	if ($text == '')
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br> <br> No content in article.');
		header("Location: https://nms.bilwis.de/system.php?uuid=" . $parent_uuid,TRUE,303);
		return;
	}

	//Check if headline is empty or default
	if ($heading == '' || $heading == 'Headline')
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br> <br> No headline in article.');
		header("Location: https://nms.bilwis.de/system.php?uuid=" . $parent_uuid,TRUE,303);
		return;
	}

	//--------------------
	//Check uuid input
	//--------------------
	
	//Check if valid UUID
	if (!Ramsey\Uuid\Uuid::isValid($parent_uuid))
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br> <br> Malformed UUID.');
		return;
	}

	//Check wheter parent item with given UUID exists
	$sql = 'SELECT id FROM ' . $parent_table . ' WHERE id = ?';
	$params = [$parent_uuid, ];

	$stmt = prepared_query($conn, $sql, $params);
	$stmt->store_result();

	if ($stmt->num_rows < 1)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Parent object ' . $parent_uuid . ' in table ' . $parent_table . ' does not exist.');
		exit();
	} elseif ($stmt->num_rows > 1) {
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Multiple matches for UUID.');
		exit();
	} 

	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Query
	$params =   [ $uuid,
				  $parent_uuid,
				  $author,
				  $heading,
				  $text,
				  ];

	$sql = 'INSERT INTO ' . $articles_table . ' (
	id,
	parent,
	author,
	heading,
	content
	) VALUES (?,?,?,?,?)';

	$types = 'sssss';

	//Execute Query
	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $parent_uuid . '&type=' . $parent_type,TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>