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
		$answer = "I was unable to find information about your name &#x1F62D ;. Check if it is present in your account";
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
		$answer = "I was unable to find information about your age &#x1F62D ;. Check if it is present in your account";
	}else{
		$result = $result['value'];
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

		$answer = str_replace("X",$result['value'],$resp);

	}else{
		$answer = "I was unable to find information about your country &#x1F62D ;. Check if it is present in your account";
	}

	return $answer;
}

//Funzione che legge il parametro Location[{"value"}] del profilo olistico e restituisce
//una risposta relativa alla città dove si vive
function Ultimacitta($email)
{
		$answer = "";
		$city = citta($email);
		if(isset($city)) {
			$answer = "You live in ".$city."";
		}else{
			$answer = "I was unable to find information about your current city. Check if it is present in your account";
		}
		return $answer;

}

//Ultima location per meteo sperimentazione
function citta($email)
{
		$param = "";
		$json_data = queryMyrror($param,$email);
		$result = null;
		$città = null;
		//echo"la mail e".$email;
		foreach ($json_data as $key1 => $value1) {
			if(isset($value1['location'])){

				foreach ($value1['location'] as $key2 => $value2) {
					if ($key2 == "value") {
						$result = $value2;
					} 	
	        	}	
			}
		}

		if(isset($result['value'])){
			$città = $result['value'];
		}
		return $città;

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


		$answer = str_replace("X",$result['value'],$resp);


	}else{
		$answer = "I was unable to find information about your height &#x1F62D ;. Check if it is present in your account";
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
	//print_r($result);

	if (isset($result)) {

		$answer = str_replace("X",$result['value'],$resp); //prima era solo $result, io ho messo $result['value']

	}else{
		$answer = "I was unable to find information about your weight &#x1F62D ;. Check if it is present in your account";
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
		$answer = "I was unable to find information about your job &#x1F62D ;. Check if it is present in your account";
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
		$answer = "I was unable to find information about your email &#x1F62D ;. Check if it is present in your account";
	}

	return $answer;
}

