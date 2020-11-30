<?php

//IDENTITA' UTENTE
function identitaUtente($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	foreach ($json_data as $key1 => $value1) {
	
		if(isset($value1['name'])){

			foreach ($value1['name'] as $key2 => $value2) {

				if ($key2 == "value") {
					$result = $value2;
				} 	
        	}	
		}
	}

	
	if (isset($result)) {
		$answer = str_replace("X",$result,$resp);
	}else{
		$answer = $GLOBALS['demographics1'];
	}

	return $answer;
}


//ETA'
function getEta($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;
	$answer = "";

	foreach ($json_data as $key1 => $value1) {
	
		if(isset($value1['dateOfBirth'])){

			foreach ($value1['dateOfBirth'] as $key2 => $value2) {

				if ($key2 == "value") {
					$result = $value2;
				} 	
        	}	
		}
	}

	if($result == null){
		$answer = $GLOBALS['demographics2'];
	}else{
		$today = date("Y-m-d");
		$diff = abs(strtotime($today) - strtotime($result));
    	$years = floor($diff / (365*60*60*24));
		$answer = str_replace("X",$years,$resp);
	}

	return $answer;
}



//LUOGO DI NASCITA
function getCountry($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	foreach ($json_data as $key1 => $value1) {
		if(isset($value1['country'])){

			foreach ($value1['country'] as $key2 => $value2) {
				if ($key2 == "value") {
					$result = $value2;
				} 	
        	}	
		}
	}

	if (isset($result)) {

		$answer = str_replace("X",$result,$resp);

	}else{
		$answer = $GLOBALS['demographics3'];
	}

	return $answer;
}



//ALTEZZA
function getHeight($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	foreach ($json_data as $key1 => $value1) {
		if(isset($value1['height'])){

			foreach ($value1['height'] as $key2 => $value2) {
				if ($key2 == "value") {
					$result = $value2;
				} 	
        	}	
		}
	}

	if (isset($result)) {


		$answer = str_replace("X",$result,$resp);


	}else{
		$answer = $GLOBALS['demographics4'];
	}

	return $answer;
}


//PESO
function getWeight($resp,$parameters,$text,$email){ 

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	foreach ($json_data as $key1 => $value1) {
		if(isset($value1['weight'])){

			foreach ($value1['weight'] as $key2 => $value2) {
				if ($key2 == "value") {
					$result = $value2;
				} 	
        	}	
		}
	}

	if (isset($result)) {

		$answer = str_replace("X",$result,$resp);

	}else{
		$answer = $GLOBALS['demographics5'];
	}

	return $answer;
}



//LAVORO
function lavoro($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	foreach ($json_data as $key1 => $value1) {

		if(isset($value1['industry'])){

			$max = 0;

			foreach ($value1['industry'] as $key2 => $value2) {

				$timestamp = $value2['timestamp'];
				$industry = $value2['value'];
		 
         		if($timestamp > $max ){
         
           			$max = $timestamp;
           			$industry = $value2['value'];
         		}	
        	}	
		}
	}

	if (isset($industry)) {

		$answer = str_replace("X",$industry,$resp);


	}else{
		$answer = $GLOBALS['demographics6'];
	}

	return $answer;

}


//EMAIL
function email($resp,$parameters,$text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;
    

	foreach ($json_data as $key1 => $value1) {

		if(isset($value1['email'])){
           
			$max = 0;

			foreach ($value1['email'] as $key2 => $value2) {

				$timestamp = $value2['timestamp'];
				$email = $value2['value'];
		 
         		if($timestamp > $max ){
         
           			$max = $timestamp;
           			$email = $value2['value'];
         		}	
        	}	
		}
	}

	if (isset($email)) {
	
		$answer = str_replace("X",$email,$resp);

	}else{
		$answer = $GLOBALS['demographics7'];
	}

	return $answer;
}

