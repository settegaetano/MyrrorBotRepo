<?php

/*Permette di fornire dati in relazione alla domanda richiesta
Viene fornito un flag per determinare se ci si riferisce alle emozioni oppure alla personalità
*/ 
function getSentiment($flag, $resp, $parameters,$email){

    //flag 1 --> emozioni
    //flag 0 --> l'umore

    if($flag == 1){ //EMOZIONI (Fear, sad, anger, joy, disgust, surprise, none)

      if ($parameters['date'] != "") { //La data inserita dall'utente è stata riconosciuta
        
        //DATA RICHIESTA DALL'UTENTE
        $dataR = substr($parameters['date'], 0, 10);
        $data = str_replace('-', '/', $dataR);

        //OGGI
        $oggi = date("Y/m/d");

        //IERI
        $date1 = str_replace('-', '/', date("Y/m/d"));
        $ieri = date('Y/m/d',strtotime($date1 . "-1 days"));

        //Controllo se la data si riferisce a ieri/oggi
        if ($data == $ieri) {
          $answer = getPast($ieri,$email);
      
        }elseif ($data == $oggi) {
          $answer = getToday($oggi,$email);
        }

    
      }else{//DATA NON RICONOSCIUTA --> imposto "oggi" come default
        
        //OGGI
        $oggi = date("Y/m/d");
        $answer = getToday($oggi,$email);
      }

  
    }else{ //UMORE (negative, neuter, positive)

       if ($parameters['date'] != "") { //La data inserita dall'utente è stata riconosciuta
        
        //DATA RICHIESTA DALL'UTENTE
        $dataR = substr($parameters['date'], 0, 10);
        $data = str_replace('-', '/', $dataR);

        //OGGI
        $oggi = date("Y/m/d");

        //IERI
        $date1 = str_replace('-', '/', date("Y/m/d"));
        $ieri = date('Y/m/d',strtotime($date1 . "-1 days"));

        //Controllo se la data si riferisce a ieri/oggi
        if ($data == $ieri) {
          $answer = getPastUmore($ieri,$email);
      
        }elseif ($data == $oggi) {
          $answer = getTodayUmore($oggi,$email);
        }

    
      }else{//DATA NON RICONOSCIUTA --> imposto "oggi" come default
        
        //OGGI
        $oggi = date("Y/m/d");
        $answer = getTodayUmore($oggi,$email);
      }
    }

    return $answer;

}


//OGGI: determina l'umore in relazione ad oggi
function getTodayUmore($oggi,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);


    if($data == $oggi){
      $result = $value1;
    }
  }

  if(isset($result['sentiment'])){

    $mood = $result['sentiment'];

    if($mood == 1){
      $answer = $GLOBALS['goodmood'];
    }else if($mood == -1){
      $answer = $GLOBALS['badmood'];
    }else{
      $answer = $GLOBALS['normalmood'];
    }

  }else{ //Se non sono presenti dati relativi ad oggi

      $param = "past";
      $json_data = queryMyrror($param,$email);
      $result = null;
      $max = "";
      $emotion = "";

    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    if(isset($result['sentiment'])){


    $mood = $result['sentiment'];

    if($mood == 1){
      $response =$GLOBALS['goodmoodp'];
    }else if($mood == -1){
      $response = $GLOBALS['badmoodp'];
    }else{
      $response = $GLOBALS['normalmoodp'];
    }
    
    $answer = $GLOBALS['lastdata'].$date . ", ". $response;
  }else{
    return $GLOBALS['nolastdata'];
  }
  }

  return $answer;
}


//IERI: determina l'umore in relazione a ieri
function getPastUmore($ieri,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);


    if($data == $ieri){
      $result = $value1;
    }
  }

  if(isset($result['sentiment'])){

    $mood = $result['sentiment'];

    if($mood == 1){
      $answer = $GLOBALS['goodmoodp'];
    }else if($mood == -1){
      $answer = $GLOBALS['badmoodp'];
    }else{
      $answer = $GLOBALS['normalmoodp'];
    }

  }else{ //Se non sono presenti dati relativi a ieri

      $param = "past";
      $json_data = queryMyrror($param,$email);
      $result = null;
      $max = "";
      $emotion = "";

    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $mood = $result['sentiment'];

    if($mood == 1){
      $response = $GLOBALS['goodmoodp'];
    }else if($mood == -1){
      $response = $GLOBALS['badmoodp'];
    }else{
      $response = $GLOBALS['normalmoodp'];
    }
    
  $answer = $GLOBALS['lastdata'] .$date . ", ". $response;

  }

  return $answer;

}


//IERI: determina l'emozione in relazione ad oggi
function getPast($ieri,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $ieri){
      $result = $value1;
    }
  }

  if(isset($result['emotion'] )){

    $emotion = getEmotion($result,$email);
    $answer =  $GLOBALS['felt']. $emotion ;

  }else{ //Se non sono presenti dati relativi a ieri
    
    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $emotion = getEmotion($result,$email);
    $answer = $GLOBALS['lastdays'] .$date . ", ".$GLOBALS['felt']. $emotion;

  }

  return $answer;

}

//OGGI: determina l'emozione in relazione ad oggi
function getToday($oggi,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $oggi){
      $result = $value1;
    }
  }

  if(isset($result['emotion'] )){

    $emotion = getEmotion($result,$email);

      switch (rand(1,2)) {
      case '1':
        $answer = $GLOBALS['feel1'] . $result;
        break;
      case '2':
        $answer = $GLOBALS['feel2'] . $result;
        break;
    }

  }else{ //Se non sono presenti dati relativi ad oggi

      $param = "past";
      $json_data = queryMyrror($param,$email);
      $result = null;
      $max = "";
      $emotion = "";

    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $emotion = getEmotion($result,$email);
    $answer = $GLOBALS['lastdays'] .$date . ", ".$GLOBALS['felt']  . $emotion;
  }

  return $answer;

}

//EMOZIONE: ritorna l'emozione corrispondente
function getEmotion($result,$email){


  if(isset($result['emotion'])){



    if (strpos($result['emotion'], 'joy') !== false) {
      $emotion = "gioia";
    }else if (strpos($result['emotion'], 'fear') !== false) {
       $emotion = "paura";
    }else if (strpos($result['emotion'], 'anger') !== false) {
      $emotion = "rabbia";
    }else if (strpos($result['emotion'], 'disgust') !== false) {
      $emotion = "disgusto";
    }else if (strpos($result['emotion'], 'sad') !== false) {
      $emotion = "tristezza";
    }else if (strpos($result['emotion'], 'surprise') !== false) {
      $emotion = "sorpresa";
    }else{
         return $GLOBALS['nofeel'];
    }


    }else{
      return $GLOBALS['nofeel'];
    }


  return $emotion;

}


//Funzione utilizzata per gestire le risposte binarie
function getSentimentBinario($flag, $resp, $parameters,$email){

    //flag 1 --> emozioni
    //flag 0 --> l'umore

    if($flag == 1){ //EMOZIONI (Fear, sad, anger, joy, disgust, surprise, none)

      if ($parameters['date'] != "") { //La data inserita dall'utente è stata riconosciuta
        
        //DATA RICHIESTA DALL'UTENTE
        $dataR = substr($parameters['date'], 0, 10);
        $data = str_replace('-', '/', $dataR);

        //OGGI
        $oggi = date("Y/m/d");

        //IERI
        $date1 = str_replace('-', '/', date("Y/m/d"));
        $ieri = date('Y/m/d',strtotime($date1 . "-1 days"));

        //Controllo se la data si riferisce a ieri/oggi
        if ($data == $ieri) {
          $answer = getPastBinario($ieri, $parameters,$email);
      
        }elseif ($data == $oggi) {
          $answer = getTodayBinario($oggi, $parameters,$email);
        }

    
      }else{//DATA NON RICONOSCIUTA --> imposto "oggi" come default
        
        //OGGI
        $oggi = date("Y/m/d");

        $answer = getTodayBinario($oggi, $parameters,$email);
      }

  
    }else{ //UMORE (negative, neuter, positive)

      if ($parameters['date'] != "") { //La data inserita dall'utente è stata riconosciuta
        
        //DATA RICHIESTA DALL'UTENTE
        $dataR = substr($parameters['date'], 0, 10);
        $data = str_replace('-', '/', $dataR);

        //OGGI
        $oggi = date("Y/m/d");

        //IERI
        $date1 = str_replace('-', '/', date("Y/m/d"));
        $ieri = date('Y/m/d',strtotime($date1 . "-1 days"));

        //Controllo se la data si riferisce a ieri/oggi
        if ($data == $ieri) {
          $answer = getPastUmoreBinario($ieri, $parameters,$email);
      
        }elseif ($data == $oggi) {
          $answer = getTodayUmoreBinario($oggi, $parameters,$email);
        }

    
      }else{//DATA NON RICONOSCIUTA --> imposto "oggi" come default
        
        //OGGI
        $oggi = date("Y/m/d");

        $answer = getTodayUmoreBinario($oggi, $parameters,$email);
      }
    }

    return $answer;

}


//IERI: determina l'umore in relazione a ieri per le domande con risposta binaria
function getPastUmoreBinario($ieri, $parameters,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $ieri){
      $result = $value1;
    }
  }

  if(isset($result['sentiment'] )){

     $mood = $result['sentiment'];

     if ($mood == 1 && $parameters['UmoreBuono'] != "") {
        $answer = $GLOBALS['mood1'];
     }else if ($mood == -1 && $parameters['UmoreBuono'] != ""){
        $answer = $GLOBALS['mood2'];
     }else if ($mood == 0 && $parameters['UmoreBuono'] != ""){
        $answer = $GLOBALS['mood3'];
     }

    if ($mood == -1 && $parameters['UmoreCattivo'] != "") {
        $answer = $GLOBALS['mood4'];
     }else if ($mood == 1 && $parameters['UmoreCattivo'] != ""){
        $answer = $GLOBALS['mood5'];
     }else if ($mood == 0 && $parameters['UmoreCattivo'] != ""){
        $answer =$GLOBALS['mood6'] ;
     }

      if ($mood == 0 && $parameters['UmoreNeutro'] != "") {
        $answer = $GLOBALS['mood7'];
     }else if ($mood == 1 && $parameters['UmoreNeutro'] != ""){
        $answer = $GLOBALS['mood8'] ;
     }else if ($mood == -1 && $parameters['UmoreNeutro'] != ""){
        $answer = $GLOBALS['mood9'];
     }

  }else{ //Se non sono presenti dati relativi a ieri
    
    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $mood = $result['sentiment'];

     if ($mood == 1 && $parameters['UmoreBuono'] != "") {
        $risposta = $GLOBALS['mood10'];
     }else if ($mood == -1 && $parameters['UmoreBuono'] != ""){
        $risposta = $GLOBALS['mood11'];
     }else if ($mood == 0 && $parameters['UmoreBuono'] != ""){
        $risposta = $GLOBALS['mood12'];
     }

    if ($mood == -1 && $parameters['UmoreCattivo'] != "") {
        $risposta = $GLOBALS['mood13'];
     }else if ($mood == 1 && $parameters['UmoreCattivo'] != ""){
        $risposta = $GLOBALS['mood14'];
     }else if ($mood == 0 && $parameters['UmoreCattivo'] != ""){
        $risposta = $GLOBALS['mood15'];
     }

      if ($mood == 0 && $parameters['UmoreNeutro'] != "") {
        $risposta = $GLOBALS['mood16'];
     }else if ($mood == 1 && $parameters['UmoreNeutro'] != ""){
        $risposta = $GLOBALS['mood17'];
     }else if ($mood == -1 && $parameters['UmoreNeutro'] != ""){
        $risposta = $GLOBALS['mood18'];
     }

       $answer = $GLOBALS['lastdays'] .$date . ", " . $risposta;

  }

  return $answer;

}


//OGGI: determina l'umore in relazione ad oggi per le domande con risposta binaria
function getTodayUmoreBinario($oggi, $parameters,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
    $dataR = substr($value1['date'], 0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $oggi){
      $result = $value1;
    }
  }


  if(isset($result['sentiment'] )){
    $mood = $result['sentiment'];

     if ($mood == 1 && $parameters['UmoreBuono'] != "") {
        $answer = $GLOBALS['mood19'];
     }else if ($mood == -1 && $parameters['UmoreBuono'] != ""){
        $answer = $GLOBALS['mood20'];
     }else if ($mood == 0 && $parameters['UmoreBuono'] != ""){
        $answer = $GLOBALS['mood21'];
     }

    if ($mood == -1 && $parameters['UmoreCattivo'] != "") {
        $answer = $GLOBALS['mood22'];
     }else if ($mood == 1 && $parameters['UmoreCattivo'] != ""){
        $answer = $GLOBALS['mood23'];
     }else if ($mood == 0 && $parameters['UmoreCattivo'] != ""){
        $answer = $GLOBALS['mood24'];
     }

      if ($mood == 0 && $parameters['UmoreNeutro'] != "") {
        $answer = $GLOBALS['mood25'];
     }else if ($mood == 1 && $parameters['UmoreNeutro'] != ""){
        $answer = $GLOBALS['mood26'];
     }else if ($mood == -1 && $parameters['UmoreNeutro'] != ""){
        $answer = $GLOBALS['mood27'];
     }
      
  }else{ //Se non sono presenti dati relativi ad oggi

      $param = "past";
      $json_data = queryMyrror($param,$email);
      $result = null;
      $max = "";
      $emotion = "";

    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $mood = $result['sentiment'];

     if ($mood == 1 && $parameters['UmoreBuono'] != "") {
        $risposta =  $GLOBALS['mood28'];
     }else if ($mood == -1 && $parameters['UmoreBuono'] != ""){
        $risposta =  $GLOBALS['mood29'];
     }else if ($mood == 0 && $parameters['UmoreBuono'] != ""){
        $risposta =  $GLOBALS['mood30'];
     }

    if ($mood == -1 && $parameters['UmoreCattivo'] != "") {
        $risposta =  $GLOBALS['mood31'];
     }else if ($mood == 1 && $parameters['UmoreCattivo'] != ""){
        $risposta =  $GLOBALS['mood32'];
     }else if ($mood == 0 && $parameters['UmoreCattivo'] != ""){
        $risposta =  $GLOBALS['mood33'];
     }

      if ($mood == 0 && $parameters['UmoreNeutro'] != "") {
        $risposta =  $GLOBALS['mood34'];
     }else if ($mood == 1 && $parameters['UmoreNeutro'] != ""){
        $risposta =  $GLOBALS['mood35'];
     }else if ($mood == -1 && $parameters['UmoreNeutro'] != ""){
        $risposta =  $GLOBALS['mood36'];
     }


        $answer = $GLOBALS['lastdays'] . $date . ", " . $risposta;
  }

  return $answer;

}

   
//IERI: determina l'emozione in relazione a ieri per le domande con risposta binaria
function getPastBinario($ieri, $parameters,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
      
    $dataR = substr($value1['date'],0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $ieri){
      $result = $value1;
    }
  }

  if(isset($result['emotion'] )){

    $emotion = getEmotion($result,$email);

    switch ($emotion) {
      case 'gioia':
        if ($parameters['EmotionJoy'] != "") {
          $entity = $parameters['EmotionJoy'];
          $answer = $GLOBALS['moody'] . $entity;
        }else{
          $answer = $GLOBALS['mood37'];
        }
        break;
      case 'paura':
        if ($parameters['EmotionFear'] != "") {
          $entity = $parameters['EmotionFear'];
          $answer = $GLOBALS['moody'] . $entity;
        }else{
          $answer = $GLOBALS['mood38'];
        }
        break;
      case 'rabbia':
        if ($parameters['EmotionAnger'] != "") {
          $entity = $parameters['EmotionAnger'];
          $answer = $GLOBALS['moody'] . $entity;
        }else{
          $answer = $GLOBALS['mood39'];
        }
        break;
      case 'disgusto':
        if ($parameters['EmotionDisgust'] != "") {
          $entity = $parameters['EmotionDisgust'];
          $answer = $GLOBALS['moody']. $entity;
        }else{
          $answer = $GLOBALS['mood40'];
        }
        break;
      case 'tristezza':
        if ($parameters['EmotionSad'] != "") {
          $entity = $parameters['EmotionSad'];
          $answer = $GLOBALS['moody'] . $entity;
        }else{
          $answer = $GLOBALS['mood41'];
        }
        break;
      case 'sorpresa':
        if ($parameters['EmotionSurprise'] != "") {
          $entity = $parameters['EmotionSurprise'];
          $answer = $GLOBALS['moody'] . $entity;
        }else{
          $answer = $GLOBALS['mood42'];
        }
        break;
      default:
          $answer = $GLOBALS['mood43'];
        break;
    }

  }else{ //Se non sono presenti dati relativi a ieri
    
    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $emotion = getEmotion($result,$email);


    switch ($emotion) {
      case 'gioia':
        if ($parameters['EmotionJoy'] != "") {
          $entity = $parameters['EmotionJoy'];
          $risposta =$GLOBALS['moody']  . $entity;
        }else{
          $risposta = $GLOBALS['mood37'];
        }
        break;
      case 'paura':
        if ($parameters['EmotionFear'] != "") {
          $entity = $parameters['EmotionFear'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood38'];
        }
        break;
      case 'rabbia':
        if ($parameters['EmotionAnger'] != "") {
          $entity = $parameters['EmotionAnger'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood39'];
        }
        break;
      case 'disgusto':
        if ($parameters['EmotionDisgust'] != "") {
          $entity = $parameters['EmotionDisgust'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood40'];
        }
        break;
      case 'tristezza':
        if ($parameters['EmotionSad'] != "") {
          $entity = $parameters['EmotionSad'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood41'];
        }
        break;
      case 'sorpresa':
        if ($parameters['EmotionSurprise'] != "") {
          $entity = $parameters['EmotionSurprise'];
          $risposta = $GLOBALS['moody']  . $entity;
        }else{
          $risposta = $GLOBALS['mood42'];
        }
        break;
      default:
          $risposta = $GLOBALS['mood43'];
        break;
    }

    $answer = $GLOBALS['lastdays'] . $date . ", " . $risposta;

  }

  return $answer;

}

//OGGI: determina l'emozione in relazione ad oggi per le domande con risposta binaria
function getTodayBinario($oggi, $parameters,$email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  foreach ($json_data['affects'] as $key1 => $value1) {
    $dataR = substr($value1['date'], 0, 10);
    $data = str_replace('-', '/', $dataR);

    if($data == $oggi){
      $result = $value1;
    }
  }


  if(isset($result['emotion'] )){
    $emotion = getEmotion($result,$email);

    switch ($emotion) {
      case 'gioia':
        if ($parameters['EmotionJoy'] != "") {
          $entity = $parameters['EmotionJoy'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood44'];
        }
        break;
      case 'paura':
        if ($parameters['EmotionFear'] != "") {
          $entity = $parameters['EmotionFear'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood45'];
        }
        break;
      case 'rabbia':
        if ($parameters['EmotionAnger'] != "") {
          $entity = $parameters['EmotionAnger'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood46'];
        }
        break;
      case 'disgusto':
        if ($parameters['EmotionDisgust'] != "") {
          $entity = $parameters['EmotionDisgust'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood47'];
        }
        break;
      case 'tristezza':
        if ($parameters['EmotionSad'] != "") {
          $entity = $parameters['EmotionSad'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood48'];
        }
        break;
      case 'sorpresa':
        if ($parameters['EmotionSurprise'] != "") {
          $entity = $parameters['EmotionSurprise'];
          $answer = $GLOBALS['moody2'] . $entity;
        }else{
          $answer = $GLOBALS['mood49'];
        }
        break;
      default:
          $answer = $GLOBALS['mood50'];
        break;
    }
      
  }else{ //Se non sono presenti dati relativi ad oggi

      $param = "past";
      $json_data = queryMyrror($param,$email);
      $result = null;
      $max = "";
      $emotion = "";

    //Prendo l'ultima data disponibile
    foreach ($json_data['affects'] as $key1 => $value1) {
      $date = substr($value1['date'],0, 10);

      if($date > $max){
        $result = $value1;
        $max = $date;
      }
    }

    $emotion = getEmotion($result,$email);

     switch ($emotion) {
      case 'gioia':
        if ($parameters['EmotionJoy'] != "") {
          $entity = $parameters['EmotionJoy'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood37'];
        }
        break;
      case 'paura':
        if ($parameters['EmotionFear'] != "") {
          $entity = $parameters['EmotionFear'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood38'];
        }
        break;
      case 'rabbia':
        if ($parameters['EmotionAnger'] != "") {
          $entity = $parameters['EmotionAnger'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood39'];
        }
        break;
      case 'disgusto':
        if ($parameters['EmotionDisgust'] != "") {
          $entity = $parameters['EmotionDisgust'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood40'];
        }
        break;
      case 'tristezza':
        if ($parameters['EmotionSad'] != "") {
          $entity = $parameters['EmotionSad'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood41'];
        }
        break;
      case 'sorpresa':
        if ($parameters['EmotionSurprise'] != "") {
          $entity = $parameters['EmotionSurprise'];
          $risposta = $GLOBALS['moody'] . $entity;
        }else{
          $risposta = $GLOBALS['mood42'];
        }
        break;
      default:
          $risposta = $GLOBALS['mood43'];
        break;
    }

    $answer = $GLOBALS['lastdays'] . $date . ", " . $risposta;
  }

  return $answer;

}


function getLastEmotion($email){
  $param = "past";
  $json_data = queryMyrror($param,$email);
  $result = null;
  $max = "";
  $emotion = "";

  //Prendo l'ultima data disponibile
  foreach ($json_data['affects'] as $key1 => $value1) {
    $date = substr($value1['date'],0, 10);

    if($date > $max){
      $result = $value1;
      $max = $date;
    }
  }

  $emotion = getEmotion($result,$email);

  return $emotion;
}