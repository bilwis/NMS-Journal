<?php
	function handle_upload($imageFolder, $echo)
	{
		$accepted_origins = array("https://localhost", "https://nms.bilwis.de");
		
		reset ($_FILES);
		$temp = current($_FILES);
		echo($_FILES['tmp_name']);

		if (is_uploaded_file($temp['tmp_name'])){
			if (isset($_SERVER['HTTP_ORIGIN'])) {
				// same-origin requests won't set an origin. If the origin is set, it must be valid.
				if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
					header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
				} else {
					header("HTTP/1.1 403 Origin Denied");
					return;
				}
			}

			// Sanitize input
			if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
				header("HTTP/1.1 400 Invalid file name.");
				return;
			}

			// Verify extension
			if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
				header("HTTP/1.1 400 Invalid extension.");
				return;
			}

			// Accept upload if there was no origin, or if it is an accepted origin
			$filename = reset(explode('.', $temp['name']));
			$extension = end(explode('.', $temp['name']));
			$filetowrite = $imageFolder . $filename . '-' . time() . '.' . $extension;
            
			move_uploaded_file($temp['tmp_name'], $filetowrite);

			// Respond to the successful upload with JSON.
			// Use a location key to specify the path to the saved image resource.
			// { location : '/your/uploaded/image/file'}
			if ($echo){
				echo json_encode(array('location' => $filetowrite));
				}
			return($filetowrite);

		} else {
			// Notify editor that the upload failed
			header("HTTP/1.1 500 Server Error");
		}
	}

    function handle_base64($img, $format, $imageFolder = 'upload/img/')
    {
        if (strpos($img, 'data:image/jpeg;base64,') === 0) {
            $img = str_replace('data:image/jpeg;base64,', '', $img);
            $ext = '.jpg';
        }
        
        if (strpos($img, 'data:image/png;base64,') === 0) {
            $img = str_replace('data:image/png;base64,', '', $img);
            $ext = '.png';
        }

        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $file = $imageFolder . time() . '_' . $format . $ext;

        if (file_put_contents($file, $data)) {
            return $file;
        } else  {
            throw new Exception("<p>The image could not be saved.</p>");
        }
    }

?>