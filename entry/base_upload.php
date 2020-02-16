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
	$facilities_string = $_POST['facilities'];
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
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/bases/');        
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
			echo('Planet does not exist. Given planet parameter: "' . $planet .'".');
			return;
		}
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
	//Process facilities input
	//--------------------

	//Resource string is given as comma delimited list
	$facilities = explode(',', $facilities_string);

    $facilities = array_unique($facilities);

	//Loop through each resource in the array, looking up or inserting
	//the resource into the resource_table and changing the REFERENCED resource array entry to the id
	foreach ($facilities as &$facility)
	{
		$facility = find_or_insert_item($conn, $facility, $base_facilities_table);
	}

	//Glue ids together with comma for sorage as string
	$facilities = implode(',', $facilities);

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
				  $facilities,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  ];

	$sql = 'INSERT INTO '.$bases_table.' (
	id,
	parent_id,
	orig_name,
	name,
	planet_lat,
    planet_long,
	facilities,
	discovery_date,
	screenshot,
	discoverer
	) VALUES (?,?,?,?,?,?,?,?,?,?)';

	$types = 'ssssddssss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=base',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>
