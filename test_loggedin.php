<?php
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>

	
<?php
	

	if ($_SESSION['verified'] == true){
		echo('Successfully verified user '.$_SESSION['userid'].', '.$_SESSION['name'].'.');
	} else {
		echo('Not verified. User Id: ' . $_SESSION['userid']);
	}
	
?>

	
	
</body>
</html>