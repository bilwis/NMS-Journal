<?php
	require_once('libs/autoload.php');
	require_once('db.php');
	require_once('style/color_vars.php');

	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();

	if (isset($_SESSION['verified']))
	{
		if (!$_SESSION['verified']){
			header("HTTP/1.1 401 Unauthorized. Login to access site.");
			exit();
		} 
	} else {
		header("HTTP/1.1 401 Unauthorized. Login to access site.");
		exit();
	}
    
	define('SECURE_PAGE', true);


	//--------------------
	//Process GET content
	//--------------------

    $uuid = '';

	//Get UUID from URL via GET
	if (empty($_GET))
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br> <br> No GET data.');
		return;
	} else {
		if (!empty($_GET['uuid'])) {
            if (Ramsey\Uuid\Uuid::isValid($_GET['uuid']))
            {
                $uuid = $_GET['uuid'];
            } else {
                header("HTTP/1.1 400 Malformed request.");
                echo('Error 400: Malformed request. <br> <br> Malformed UUID.');
                return;
            }
		}
	}

	//Check if valid UUID
	

	$item_type = $_GET['type'];

	switch ($item_type)
	{
		case 'system':
			require_once('entry/system_entry.php');
			break;
			
		case 'planet':
			require_once('entry/planet_entry.php');
			break;
            
        default:
            header("HTTP/1.1 501 Not implemented.");
            echo('Error 501: Entry for type "' .$item_type. '" is not implemented.');
            exit();
            break;
	}

	
?>
