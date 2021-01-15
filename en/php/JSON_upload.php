<?php 

include "myrrorlogin.php";
//require_once("url.php");

//echo "ciao";
	
    $token  = trim($_POST['accesstoken']);
    $email = trim($_POST['mail']);
    $email = urldecode($email);
    

	$response = queryMyrrorT("", $token);
	if(!is_null($response)){
		$fp = fopen('../fileMyrror/past_'. $email . ".json", 'w');
		fwrite($fp, json_encode($response));
		fclose($fp);
		//Risposta per identificare l'avvenuta creazione dei file
		echo "File aggiornato";	
	}
	

	$today = date('Y-m-d');

	$response = queryMyrrorT("?fromDate=".$today,  $token);
	if(!is_null($response)){
	$fp = fopen('../fileMyrror/today_'. $email . ".json", 'w');
	fwrite($fp, json_encode($response));
	fclose($fp);
	}




	


?> 