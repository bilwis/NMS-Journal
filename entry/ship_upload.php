<?php
	require_once('../libs/autoload.php');
	require_once('../db.php');
	require_once('file_upload.php');
	
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

	//--------------------
	//Handle POST data
	//--------------------

	$name = $_POST['name'];
	$system = $_POST['system'];
    $inventory = $_POST['inventory'];
	$type = $_POST['type'];
    $price = $_POST['price'];
	$discovery_date = $_POST['discovery_date'];
	$discoverer = $_POST['discoverer'];
    $screenshot = '';
	
	//--------------------
	//Handle POSTed images
	//--------------------

    $imgs = [['full', $_POST['full_img']], ['header', $_POST['header_img']], ['thumb', $_POST['thumb_img']]];
    $img_paths = [];

    foreach ($imgs as $img_arr)
    {
        try 
        {
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/planets/');        
        }
        catch (Exception $ex)
        {
            header("HTTP/1.1 500 Malformed request.");
			echo('Failure while uploading images. "' . $ex .'".');
			exit();
        }
    }

	//--------------------
	//Process system input
	//--------------------

	//Check if input is valid UUID
	if (Ramsey\Uuid\Uuid::isValid($system))
	{
		$system_uuid = $system;
	} else {
		//Check if system exists
		$system_uuid = get_item_by_name($conn, $system, $systems_table);
		if ($system_uuid == NULL)
		{
			header("HTTP/1.1 400 Malformed request.");
			echo('System does not exist. Given system parameter: "' . $system .'".');
			return;
		}
	}

    //--------------------
	//Process datalist input
	//--------------------

	//Create array with references to the variables,
	//to replace with existing or new id's for ship table entering
	$check_array = [
		[&$type, $ship_types_table],
	];
	
	//Loop through each paired item & item_table, looking up or inserting
	//the item into the item_table and changing the REFERENCED item variable to the id
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_name($conn, $arr[0], $arr[1]);
	}


	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Request

	//Form Request
	$params =   [ $uuid,
				  $system_uuid,
				  $name,
                  $inventory,
				  $type,
                  $price,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  ];

	$sql = 'INSERT INTO '.$ships_table.' (
	id,
	parent_id,
	name,
	inventory,
	type,
    price,
	discovery_date,
	screenshot,
	discoverer
	) VALUES (?,?,?,?,?,?,?,?,?)';

	$types = 'sssssisss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=ship',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>
