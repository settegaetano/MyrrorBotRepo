<?php 

//set_error_handler("var_dump");
//ini_set("display_errors", 1);
//ini_set("track_errors", 1);
//ini_set("html_errors", 1);
include "myrrorlogin.php";
//require_once("url.php");
//echo "ciao";
//$username = posix_getpwuid(posix_geteuid())['name'];
//print($username);
	
    $token  = trim($_POST['accesstoken']);
    $email = trim($_POST['mail']);

	$response = queryMyrrorT("", $token);
	if(!is_null($response)){
		#print_r($response);
		$fp = fopen('../fileMyrror/past_'. $email . ".json", 'w');
		#var_dump($php_errormsg);
		fwrite($fp, json_encode($response));
		fclose($fp);
		//Risposta per identificare l'avvenuta creazione dei file
		echo " File aggiornato";	
	}
	

	$today = date('Y-m-d');

	$response = queryMyrrorT("?fromDate=".$today,  $token);
	if(!is_null($response)){
	$fp = fopen('../fileMyrror/today_'. $email . ".json", 'w');
	fwrite($fp, json_encode($response));
	fclose($fp);
	}




	


?> 