<?php


require_once('url.php');

if(isset($_POST{'mail'})){

    $email = $_POST{'mail'};
    $value = $_POST{'value'};

    $preference = [
        'username'=> $email,
        'value'=> $value,
    ];

    if (isset($_COOKIE['x-access-token'])) {
		$token =  $_COOKIE['x-access-token']; 

		$ch = curl_init();
	    $headers =[
	        "x-access-token:".$token
	    ];

	    curl_setopt($ch, CURLOPT_URL, "http://".$GLOBALS['url'].
	    	":5000/api/remove/");

	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($preference));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
	    curl_setopt($ch, CURLOPT_TIMEOUT, 60);   


	    curl_exec($ch);

	    curl_close ($ch);
	}
	

}
	

?>