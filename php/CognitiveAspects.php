<?php

//Permette di determinare i 5 tipi di personalità retivi ad un individuo
function personalita($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	$openness = "";
	$conscientiousness = "";
	$extroversion = "";
	$agreeableness = "";
	$neuroticism = "";
	$confidence = "";

	foreach ($json_data as $key1 => $value1) {

		if(isset($value1['personalities'])){

			$max = 0;

			foreach ($value1['personalities'] as $key2 => $value2) {

				$timestamp = $value2['timestamp'];

				$openness = $value2['openness'];
				$conscientiousness = $value2['conscientiousness'];
				$extroversion = $value2['extroversion'];
				$agreeableness = $value2['agreeableness'];
				$neuroticism = $value2['neuroticism'];
				$confidence = $value2['confidence'];
		 
         		if($timestamp > $max ){
         
           			$max = $timestamp;
           			$openness = $value2['openness'];
					$conscientiousness = $value2['conscientiousness'];
					$extroversion = $value2['extroversion'];
					$agreeableness = $value2['agreeableness'];
					$neuroticism = $value2['neuroticism'];
					$confidence = $value2['confidence'];
         		}	
        	}	
		}
	}


	//openness
	if ($openness > 0.5){
		$personalita1 = $GLOBALS['cognitive1'];
	} else{
		$personalita1 = $GLOBALS['cognitive2'];
	}

	//conscientiousness
	if ($conscientiousness > 0.5){
		$personalita2 = $GLOBALS['cognitive3'];
	} else{
		$personalita2 = $GLOBALS['cognitive4'];
	}

	//extroversion
	if ($extroversion > 0.5){
		$personalita3 = $GLOBALS['cognitive5'];
	} else{
		$personalita3 = $GLOBALS['cognitive6'];
	}

	//agreeableness 
	if ($agreeableness  > 0.5){
		$personalita4 = $GLOBALS['cognitive7'];
	} else{
		$personalita4 = $GLOBALS['cognitive8'];
	}
	
	//neuroticism
	if ($neuroticism > 0.5){
		$personalita5 = $GLOBALS['cognitive9'];
	} else{
		$personalita5 = $GLOBALS['cognitive10'];
	}

	$answer = $resp . " " . $personalita1 . ", " . $personalita2 . ", " . $personalita3 . $GLOBALS['cognitive11'] . $personalita4 . ", " . $personalita5;

	return $answer;

}


//Funzione che permette di fornire risposte binarie relative a domande sulla personalità
function personalitaBinario($resp,$parameters,$email){

    $answer = $GLOBALS['cognitive12'];
	$param = "";
	$json_data = queryMyrror($param,$email);
	$result = null;

	$openness = "";
	$conscientiousness = "";
	$extroversion = "";
	$agreeableness = "";
	$neuroticism = "";
	$confidence = "";

	//Prendo le personalità più recenti 
	foreach ($json_data as $key1 => $value1) {

		if(isset($value1['personalities'])){

			$max = 0;

			foreach ($value1['personalities'] as $key2 => $value2) {

				$timestamp = $value2['timestamp'];

				$openness = $value2['openness'];
				$conscientiousness = $value2['conscientiousness'];
				$extroversion = $value2['extroversion'];
				$agreeableness = $value2['agreeableness'];
				$neuroticism = $value2['neuroticism'];
				$confidence = $value2['confidence'];
		 
         		if($timestamp > $max ){
           			$max = $timestamp;
           			$openness = $value2['openness'];
					$conscientiousness = $value2['conscientiousness'];
					$extroversion = $value2['extroversion'];
					$agreeableness = $value2['agreeableness'];
					$neuroticism = $value2['neuroticism'];
					$confidence = $value2['confidence'];
         		}	
        	}	
		}
	}

	/*Prendo la entity dai parameters per capire a quale personalità mi riferisco ed effettuo i controlli
	Ad esempio se nella frase è presente la parola estroverso, verrà effettuato un controllo se effettivamente quella persona è estroversa
	*/
	if ($parameters['OpennessSi'] != "") {
		$entity = $parameters['OpennessSi'];
		
		if ($openness > 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive14'];
		}
		
	}else if ($parameters['OpennessNo'] != ""){
		$entity = $parameters['OpennessNo'];

		if ($openness <= 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive15'];
		}

	}else if ($parameters['ConscientiousnessSi'] != ""){
		$entity = $parameters['ConscientiousnessSi'];

		if ($conscientiousness > 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive16'];
		}

	}else if ($parameters['ConscientiousnessNo'] != ""){
		$entity = $parameters['ConscientiousnessNo'];

		if ($conscientiousness <= 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive17'];
		}
	}else if ($parameters['ExtroversionSi'] != ""){
		$entity = $parameters['ExtroversionSi'];

		if ($extroversion > 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive18'];
		}

	}else if ($parameters['ExtroversionNo'] != ""){
		$entity = $parameters['ExtroversionNo'];

		if ($extroversion <= 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive19'];
		}
	}else if ($parameters['AgreeablenessSi'] != ""){
		$entity = $parameters['AgreeablenessSi'];

		if ($agreeableness > 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive20'];
		}

	}else if ($parameters['AgreeablenessNo'] != ""){
		$entity = $parameters['AgreeablenessNo'];

		if ($agreeableness <= 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive21'];
		}
	}else if ($parameters['NeuroticismSi'] != ""){
		$entity = $parameters['NeuroticismSi'];

		if ($neuroticism > 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive22'];
		}

	}else if ($parameters['NeuroticismNo'] != ""){
		$entity = $parameters['NeuroticismNo'];

		if ($neuroticism <= 0.5) {
			$answer = $GLOBALS['cognitive13'] . $entity;
		}else{
			$answer = $GLOBALS['cognitive23'];
		}
	}


	return $answer;

}