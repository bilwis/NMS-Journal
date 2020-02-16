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

	$orig_name = $_POST['orig_name'];
	$new_name = $_POST['new_name'];
	$planet = $_POST['planet'];
	$poi_type = $_POST['poi_type'];
	$planet_coords = $_POST['planet_coords'];
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
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/pois/');        
        }
        catch (Exception $ex)
        {
            header("HTTP/1.1 500 Malformed request.");
			echo('Failure while uploading images. "' . $ex .'".');
			exit();
        }
    }

	//--------------------
	//Process planet input
	//--------------------

	//Check if input is valid UUID
	if (Ramsey\Uuid\Uuid::isValid($planet))
	{
		$planet_uuid = $planet;
	} else {
		//Check if system exists
		$planet_uuid = get_item_by_name($conn, $planet, $planets_table);
		if ($planet_uuid == NULL)
		{
			header("HTTP/1.1 400 Malformed request.");
			echo('System does not exist. Given system parameter: "' . $planet .'".');
			return;
		}
	}

	//--------------------
	//Process type input
	//--------------------

	//Create array with references to the variables,
	//to replace with existing or new id's for planet table entering
	$check_array = [
		[&$poi_type, $poi_types_table],
	];
	
	//Loop through each paired item & item_table, looking up or inserting
	//the item into the item_table and changing the REFERENCED item variable to the id
	foreach ($check_array as $arr)
	{
		$arr[0] = find_or_insert_item($conn, $arr[0], $arr[1]);
	}

    //--------------------
	//Process coordinate input
	//--------------------

    $lat = 0.0;
    $long = 0.0;

    $coords = explode(',', $planet_coords);

    $lat = floatval($coords[0]);
    $long = floatval($coords[1]);

	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Request

	//Form Request
	$params =   [ $uuid,
				  $planet_uuid,
				  $orig_name,
				  $new_name,
				  $lat,
                  $long,
				  $poi_type,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  ];

	$sql = 'INSERT INTO '.$pois_table.' (
	id,
	parent_id,
	orig_name,
	name,
	planet_lat,
    planet_long,
	type,
	discovery_date,
	screenshot,
	discoverer
	) VALUES (?,?,?,?,?,?,?,?,?,?)';

	$types = 'ssssddssss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=poi',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>
