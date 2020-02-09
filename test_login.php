<?php
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
?>

<html lang="en">
  <head>
    <meta name="google-signin-scope" content="profile email">
    <meta name="google-signin-client_id" content="48119294740-b0gms2q5oqs81m6mas8ps3t5nio7j4qg.apps.googleusercontent.com">
    <script src="https://apis.google.com/js/platform.js" async defer></script>
  </head>
  <body>
    <div class="g-signin2" data-onsuccess="onSignIn" data-theme="dark"></div>
    <script>
      function onSignIn(googleUser) {
        // Useful data for your client-side scripts:
        var profile = googleUser.getBasicProfile();
        console.log("ID: " + profile.getId()); // Don't send this directly to your server!
        console.log('Full Name: ' + profile.getName());
        console.log('Given Name: ' + profile.getGivenName());
        console.log('Family Name: ' + profile.getFamilyName());
        console.log("Image URL: " + profile.getImageUrl());
        console.log("Email: " + profile.getEmail());

        // The ID token you need to pass to your backend:
        var id_token = googleUser.getAuthResponse().id_token;
        console.log("ID Token: " + id_token);
		  
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'https://nms.bilwis.de/oauth2.php');
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onload = function() {
		  	console.log('Response: ' + xhr.responseURL);
			window.location.replace('https://nms.bilwis.de/test_loggedin.php');
		};
		xhr.send('idtoken=' + id_token);
	
      }
    </script>
  </body>
</html>