<?php
/*
il metodo serve a leggere il file json completo preso da
myrror e cercare al suo interno una data specificata,se la 
data viene trovata nel file verrà restituita insieme al valore
corrispondente di restingHeartRate; se non viene trovata la
data specificata verranno restituiti i dati dell'ultima data disponibile
@Parameters sono i parametri sui periodi temporali 
individuati da dialogflow
@data è la data da cercare nel file
return data e battito cardiaco
*/
function cardioToday($parameters,$data,$email){

$param = "";
$json_data = queryMyrror($param,$email);
$result = null;
$dateR = null;

foreach ($json_data as $key1 => $value1) {

if(isset($value1['heart'])){

   foreach ($value1['heart'] as $key2 => $value2) {
   
       $timestamp = $value2['timestamp'];
       $tempDate = date('Y-m-d',$timestamp/1000);
      
       if($tempDate == $data){        
        $result = $value2;
        $dateR = $tempDate;
       }
   }
}

}

if(isset($result['restingHeartRate'])){
   $heart = $result['restingHeartRate'];

}else{

foreach ($json_data as $key1 => $value1) {

if(isset($value1['heart'])){

   $max = -1;
   foreach ($value1['heart'] as $key2 => $value2) {
   
       $timestamp = $value2['timestamp'];
        $tempDate = date('Y-m-d',$timestamp/1000);
       if($timestamp > $max){
        
        $result = $value2;
        $max = $timestamp;
        $dateR = $tempDate;

       }
   }
}

}

if(isset($result['restingHeartRate'])){
   $heart = $result['restingHeartRate'];

}else{
  $heart = 0;
}

}

return  array('date' => $dateR, 'heart' => $heart);

}
/*
@startDate data iniziale dell'intervallo
@endDate data finale dell'intervallo
Il seguente metodo ricerca all'interno del file json
restituito da myrror il dato restingHeartRate di tutte le
date presenti nell'intervallo specificato, viene fatta così
una media dei valori del battito cardiaco. 
return media battito cardiaco al minuto 
*/
function cardioInterval($startDate,$endDate,$email){

$param = "";
$json_data = queryMyrror($param,$email);
$count = 0;
$sum = 0;

foreach ($json_data as $key1 => $value1) {

if(isset($value1['heart'])){

   foreach ($value1['heart'] as $key2 => $value2) {
   
       $timestamp = $value2['timestamp'];
       $tempDate = date('Y-m-d',$timestamp/1000);
       if($tempDate <= $endDate && $tempDate >= $startDate){        
        $sum  += $value2['restingHeartRate'];
        $count++;
       }
   }
}

}

if ($count != 0) {
  $average = $sum/$count;
}else{
  $average = 0;
}

return $average;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
Il metodo controlla la presenza in parameters di date o
date-period e a seconda dei casi chiama il metodo corrispondente 
per ottenere i dati del battito cardiaco di un singolo giorno o 
di un intervallo di tempo. Nel caso nel file json non troviamo
i dati del giorno o del periodo scelto la risposta verrà costruita
utilizzando gli ultimi dati disponibli.
@return risposta da stampare a schermo

*/
function getCardio($resp,$parameters,$text,$email){

  $answer = "";
  $today = date("Y-m-d"); 
  $yesterday = date("Y-m-d",strtotime("-1 days")); 


  if(isset($parameters['date']) ){

    $date1 = substr($parameters['date'],0,10);

    if($today ==  $date1){

      //dati oggi
      $arr = cardioToday($parameters,$today,$email);
      
      if($arr['date'] == $today){
        
        /*
        la risposta di default ($resp) restituita da dialogflow è
        costruita per la data di oggi, così sostituiamo alla X presente 
        in $resp il valore del battito cardiaco da stampare
        */
        $answer = str_replace('X',$arr['heart'],$resp);
      }else{

        //risposta standard
        $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical2'].$arr['heart']." bpm";
      }

    }elseif($yesterday ==  $date1){

      //dati ieri
      $arr = cardioToday($parameters,$yesterday,$email);

      if($arr['date'] == $yesterday){
        $answer = $GLOBALS['physical3'].$arr['heart']." bpm"; //risposta oggi
      }else{

        //risposta standard
        $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical2'].$arr['heart']." bpm";
      }

   }elseif(isset($parameters['date-period']['startDate'])){

    //dati ultimo giorno trovato
    $startDate =  substr($parameters['date-period']['startDate'],0,10);
    $endDate =  substr($parameters['date-period']['endDate'],0,10);
    $average = cardioInterval($startDate,$endDate,$email);

    if($average != 0){
      $answer = $GLOBALS['physical4'].$average." bpm.";
    }else{
      $arr = cardioToday($parameters,"",$email);
      $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical5'].$arr['heart']." bpm";
    }

    }else{
       $arr = cardioToday($parameters,"",$email);
       $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical5'].$arr['heart']." bpm";
    }

  }elseif (isset($parameters['date-period']['startDate'])) {

    //dati intervallo di tempo
    $startDate =  substr($parameters['date-period']['startDate'],0,10);
    $endDate =  substr($parameters['date-period']['endDate'],0,10);
    $average = cardioInterval($startDate,$endDate,$email);
    if($average != 0){
      $answer = $GLOBALS['physical4'].$average." bpm.";
    }else{
      $arr = cardioToday($parameters,"",$email);
      $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical5'].$arr['heart']." bpm";
    }

  }else{

   //dati ultimo giorno trovato
     $arr = cardioToday($parameters,"",$email);
     $answer = $GLOBALS['physical1'].$arr['date']
        .$GLOBALS['physical5'].$arr['heart']." bpm";
  }
  return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
Il metodo serve a costruire delle risposte binarie (si,no) per rispondere
a specifiche domande dell'utente.Le risposte saranno costruite tramite
i token riconosciuti nel testo,in particolare vengono distinti
buono/ottimo da pessimo/cattivo.
Viene effettuato un controllo sui parametri per verificare se abbiamo dati 
riguardanti una singola data o un intervallo. Nel caso non ci siano parametri 
con riferimenti al tempo utilizzeremo la data odierna. Se non vengono trovati 
dati nella data odierna verrà costruita una risposta utilizzando gli 
ultimi dati presenti nel file.
@return risposta da stampare a schermo
*/
function getCardioBinary($resp,$parameters,$text,$email){

    $answer = "";
    $today = date("Y-m-d");
    //$today = "2019-03-27";

    $yesterday = date("Y-m-d",strtotime("-1 days")); 

    if(isset($parameters['date-period']['startDate'])){

      $startDate =  substr($parameters['date-period']['startDate'],0,10);
      $endDate =  substr($parameters['date-period']['endDate'],0,10);
      $average = cardioInterval($startDate,$endDate,$email);

      if($average == 0){
        $answer = $GLOBALS['physical6']; 
      }else{
          if(strpos($text, $GLOBALS['physical7']) || strpos($text, $GLOBALS['physical8']) || strpos($text, $GLOBALS['physical9']) || strpos($text, $GLOBALS['physical10']) || strpos($text, $GLOBALS['physical11']) || strpos($text, $GLOBALS['physical12'])){
       
            if($average >= 60 && $average <= 100){
              $answer = $GLOBALS['physical18'].$average." bpm";
            }else{
              $answer = $GLOBALS['physical19'].$average." bpm";
            }

          }elseif (strpos($text, $GLOBALS['physical13']) || strpos($text, $GLOBALS['physical14']) || strpos($text, $GLOBALS['physical15']) ||
            strpos($text, $GLOBALS['physical16']) || strpos($text, $GLOBALS['physical17']) ) {
        
            if($average >= 60 && $average <= 100){
             $answer = $GLOBALS['physical20'].$average." bpm";
            }else{
             $answer = $GLOBALS['physical21'].$average." bpm";
            }

          }
      }

    }elseif (isset($parameters['date'])) {

      $date1 = substr($parameters['date'],0,10);
      switch ($date1) {

        case $today:
          $arr = cardioToday($parameters,$today,$email);

          if(strpos($text, $GLOBALS['physical7']) || strpos($text, $GLOBALS['physical8']) || strpos($text, $GLOBALS['physical9']) || strpos($text, $GLOBALS['physical10']) || strpos($text, $GLOBALS['physical11']) || strpos($text, $GLOBALS['physical12'])){

            if($arr['date'] == $today){

                if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                  $answer = $GLOBALS['physical22'].$arr['heart']." bpm";
                else
                   $answer = $GLOBALS['physical23'].$arr['heart']." bpm";
            }else{

               if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                  $answer = $GLOBALS['physical1'].$arr['date'].
                            $GLOBALS['physical24'] .$arr['heart']." bpm";
               }else{

                   $answer = $GLOBALS['physical1'].$arr['date'].
                   $GLOBALS['physical25'].$arr['heart']." bpm";
               }
                 
            }
                    
            }elseif (strpos($text, $GLOBALS['physical13']) || strpos($text, $GLOBALS['physical14']) || strpos($text, $GLOBALS['physical15']) ||
            strpos($text, $GLOBALS['physical16']) || strpos($text, $GLOBALS['physical17'])) {

               if($arr['date'] == $today){
                if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                  $answer = $GLOBALS['physical26'].$arr['heart']." bpm";
                else
                   $answer = $GLOBALS['physical27'].$arr['heart']." bpm";
            }else{
               if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                  $answer = $GLOBALS['physical1'].$arr['date'].
                            $GLOBALS['physical28'].$arr['heart']." bpm";
               }else{

                   $answer = $GLOBALS['physical1'].$arr['date'].
                   $GLOBALS['physical29'].$arr['heart']." bpm";
               }
                 
            }

            }

            break;
        case $yesterday:
        
          $arr = cardioToday($parameters,$yesterday,$email);  
          if(strpos($text, $GLOBALS['physical7']) || strpos($text, $GLOBALS['physical8']) || strpos($text, $GLOBALS['physical9']) || strpos($text, $GLOBALS['physical10']) || strpos($text, $GLOBALS['physical11']) || strpos($text, $GLOBALS['physical12'])){

          if($arr['date'] == $yesterday){
              if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                $answer = $GLOBALS['physical30'].$arr['heart']." bpm";
              else
                 $answer = $GLOBALS['physical31'].$arr['heart']." bpm";
          }else{
             if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                $answer = $GLOBALS['physical1'].$arr['date'].
                          $GLOBALS['physical24'].$arr['heart']." bpm";
             }else{

                 $answer = $GLOBALS['physical1'].$arr['date'].
                 $GLOBALS['physical25'].$arr['heart']." bpm";
             }
               
          }
                  
          }elseif (strpos($text, $GLOBALS['physical13']) || strpos($text, $GLOBALS['physical14']) || strpos($text, $GLOBALS['physical15']) ||
            strpos($text, $GLOBALS['physical16']) || strpos($text, $GLOBALS['physical17'])) {

             if($arr['date'] == $yesterday){
              if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                $answer = $GLOBALS['physical32'].$arr['heart']." bpm";
              else
                 $answer = $GLOBALS['physical33'].$arr['heart']." bpm";
          }else{
             if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                $answer = $GLOBALS['physical1'].$arr['date'].
                          $GLOBALS['physical24'].$arr['heart']." bpm";
             }else{

                 $answer = $GLOBALS['physical1'].$arr['date'].
                 $GLOBALS['physical25'].$arr['heart']." bpm";
             }
               
          }

          }
       
          break;
        default:

             //ultima data disponibile
             $arr = cardioToday($parameters,"",$email);
            if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                $answer = $GLOBALS['physical1'].$arr['date'].
                          $GLOBALS['physical24'].$arr['heart']." bpm";
             }else{

                 $answer = $GLOBALS['physical1'].$arr['date'].
                 $GLOBALS['physical25'].$arr['heart']." bpm";
             }
          break;
      }
      
    }else{
         $arr = cardioToday($parameters,$today,$email);
             if(strpos($text, $GLOBALS['physical7']) || strpos($text, $GLOBALS['physical8']) || strpos($text, $GLOBALS['physical9']) || strpos($text, $GLOBALS['physical10']) || strpos($text, $GLOBALS['physical11']) || strpos($text, $GLOBALS['physical12'])){

          if($arr['date'] == $today){
              if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                $answer = $GLOBALS['physical22'].$arr['heart']." bpm";
              else
                 $answer = $GLOBALS['physical23'].$arr['heart']." bpm";
          }else{
             if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                $answer = $GLOBALS['physical1'].$arr['date'].
                          $GLOBALS['physical24'].$arr['heart']." bpm";
             }else{

                 $answer = $GLOBALS['physical1'].$arr['date'].
                 $GLOBALS['physical25'].$arr['heart']." bpm";
             }
               
          }
                  
          }elseif (strpos($text, $GLOBALS['physical13']) || strpos($text, $GLOBALS['physical14']) || strpos($text, $GLOBALS['physical15']) ||
            strpos($text, $GLOBALS['physical16']) || strpos($text, $GLOBALS['physical17'])) {

             if($arr['date'] == $today){
              if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                $answer = $GLOBALS['physical26'].$arr['heart']." bpm";
              else
                 $answer = $GLOBALS['physical27'].$arr['heart']." bpm";
          }else{
             if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                $answer = $GLOBALS['physical1'].$arr['date'].
                          $GLOBALS['physical24'].$arr['heart']." bpm";
             }else{

                 $answer = $GLOBALS['physical1'].$arr['date'].
                 $GLOBALS['physical25'].$arr['heart']." bpm";
             }
               
          }

          }
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
il metodo analizza i parameters se è presente la data di ieri
o di oggi chiama il metodo yestSleepBinary per ottenere i minuti di 
sonno dell'ultima notte,altrimenti viene fatta una distinzione in base al 
verbo riconosciuto da dialogflow, se i verbi sono al passato prossimo
viene chiamata la funzione yestSleepBinary altrimenti viene chiamata la 
funzione pastSleepBinary che costruisce la risposta con i dati storici
return risposta da stampare  
*/
function getSleepBinary($resp,$parameters,$text,$email){


$yesterday = date("Y-m-d",strtotime("-1 days")); 
if(isset($parameters['date'])  ||  isset($parameters['Passato'])){
$date1 = substr($parameters['date'],0,10);

if($date1 >= $yesterday){
//dati di ieri
  
 $answer = yestSleepBinary($resp,$parameters,$text,$yesterday,$email);


}else if($parameters['Passato']){
  //dati di ieri
  
  $answer = yestSleepBinary($resp,$parameters,$text,$yesterday,$email);
  //$answer = yestSleepBinary($resp,$parameters,$text,'2019-02-22');

}else{
  //dati storici
  $answer = pastSleepBinary($resp,$parameters,$text,$email);
}

}else{
  //dati storici
   $answer = pastSleepBinary($resp,$parameters,$text,$email);
}

return $answer;

}
/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
la funzione effettua una media dei minuti trascorsi nel letto
e dei minuti di sonno, successivamente viene costruita una risposta
verificando le parole presenti all'interno della frase digitata 
dall'utente e usando dei valori soglia (390 minuti di sonno) per
rispondere in maniera positiva o negativa
return risposta da stampare
*/
function pastSleepBinary($resp,$parameters,$text,$email){

  $param = "";
  $json_data = queryMyrror($param,$email);
  $result = "";

  $count = 0;
  $sumInBed = 0;
  $sumAsleep = 0;

  foreach ($json_data as $key1 => $value1) {

    if(isset($value1['sleep'])){
      
    //ricerca per periodo   
     foreach ($value1['sleep'] as $key2 => $value2) {
         $sumInBed += $value2['timeInBed'];
         $sumAsleep += $value2['minutesAsleep'];
         $count++;         
      }
    }

  }

  if($count == 0){
    //non ci sono riferimenti per quel periodo
    return $GLOBALS['physical6'];
  }
  $asleepAV = intval($sumAsleep/$count);
  $inBedAV =intval($sumInBed/$count);

  //Conversione minuti in ore e minuti
  if ($asleepAV < 1) {
    return $GLOBALS['physical34'];
  }
  $hours = floor($asleepAV / 60);
  $minutes = ($asleepAV % 60);

  if(strpos($text, $GLOBALS['physical35'])){

     if($asleepAV >= 390){
          if ($hours == 1) {
            $result = $GLOBALS['physical45'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $result = $GLOBALS['physical45'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
     }else{
        if ($hours == 1) {
          $result = $GLOBALS['physical46'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $result = $GLOBALS['physical46'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
      }
  }elseif (strpos($text, $GLOBALS['physical36'])) {

     if($asleepAV >= 390){
        if ($hours == 1) {
          $result = $GLOBALS['physical47'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
            $result = $GLOBALS['physical47'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
      }else{
        if ($hours == 1) {
          $result = $GLOBALS['physical48'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        } else{
          $result = $GLOBALS['physical48'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
      }

  }elseif (strpos($text, $GLOBALS['physical37'])) {

     if($asleepAV >= 390){
        if ($hours == 1) {
         $result = $GLOBALS['physical49'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
         $result = $GLOBALS['physical49'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
     }else{
        if ($hours == 1) {
         $result = $GLOBALS['physical50'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $result = $GLOBALS['physical50'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
      }
  }elseif (strpos($text, $GLOBALS['physical38'])) {
      if($asleepAV >= 480){
        if ($hours == 1) {
          $result = $GLOBALS['physical51'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $result = $GLOBALS['physical51'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
     }else{
        if ($hours == 1) {
         $result = $GLOBALS['physical52'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $result = $GLOBALS['physical52'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
    }
  }elseif (strpos($text, $GLOBALS['physical39'])) {

     if($asleepAV >= 390){
      if ($hours == 1) {
       $result = $GLOBALS['physical53'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
      }else{
        $result = $GLOBALS['physical53'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
      }
     }else{
      if ($hours == 1) {
        $result = $GLOBALS['physical54'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
      }else{
        $result = $GLOBALS['physical54'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
      }
    }
    
  }elseif (strpos($text, $GLOBALS['physical40'])){
    
     if($asleepAV >= 390){
      if ($hours == 1) {
       $result = $GLOBALS['physical55'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
      }else{
        $result = $GLOBALS['physical55'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
      }
     }else{
      if ($hours == 1) {
       $result = $GLOBALS['physical56'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
      }else{
       $result = $GLOBALS['physical56'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
      }
     }

  }else{
    if ($hours == 1) {
      $result = $GLOBALS['physical57'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];;
    }else{
      $result = $GLOBALS['physical57'] .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];;
    }
  }

   return $result;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
@data da cercare
la funzione ricerca all'interno del file json la data che viene passata
come parametro , se non la trova verrà presa l'ultima data disponibile ,
questa distinzione avviene tramite il flag.
Viene costruita una risposta in base ai token rilevati nella frase
 usando dei valori soglia (390 minuti di sonno) per
rispondere in maniera positiva o negativa.
return risposta da stampare
*/
function yestSleepBinary($resp,$parameters,$text,$data,$email){

  $param = "";
  $json_data = queryMyrror($param,$email);
  $result = null;

  //serve a capire se vengono presi i dati della data corretta oppure gli ultimi presenti nel file
  $flag = false;

  //cerco data di ieri
  foreach ($json_data as $key1 => $value1) {
    if(isset($value1['sleep'])){

      foreach ($value1['sleep'] as $key2 => $value2) {

         $timestamp = $value2['timestamp'];
         $tempDate = date('Y-m-d',$timestamp/1000);
         if($data == $tempDate){
           $result = $value2;
         }
      }
    }
  }

  if($result['minutesAsleep'] != null){
    
    //risposta con data di ieri corretta
    $minutesAsleep = $result['minutesAsleep'];
    $timeinbed = $result['timeInBed'];
    $flag = true;

  }else{

    /*risposta standard con ultima data
    algoritmo ultima data*/
    foreach ($json_data as $key1 => $value1) {

      if(isset($value1['sleep'])){
        $max = -1;

        foreach ($value1['sleep'] as $key2 => $value2) {
           $timestamp = $value2['timestamp'];
           if($timestamp > $max){
              $result = $value2;
              $max = $timestamp;
           }
         }   
      }
    }

    if (isset($timestamp)) {
      $data2 = date('d-m-Y',$timestamp/1000);
    }else{
      return $GLOBALS['physical58'];
    }

    if($result['minutesAsleep'] != null){
      $data = $data2;
      $minutesAsleep = $result['minutesAsleep'];
      $timeinbed = $result['timeInBed'];

    }else{
      return $GLOBALS['physical58'];
    }
  }

  //Conversione minuti in ore e minuti
  if ($minutesAsleep < 1) {
    return $GLOBALS['physical34'];
  }
  $hours = floor($minutesAsleep / 60);
  $minutes = ($minutesAsleep % 60);


  if(strpos($text, $GLOBALS['physical35']) || strpos($text, $GLOBALS['physical37'])){

    if($minutesAsleep >= 390 ){
       
       if($flag == true){
          if ($hours == 1) {
            $answer = $GLOBALS['physical59'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer = $GLOBALS['physical59'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
       }else{
          if ($hours == 1) {
            $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical61'] .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43']; 
          }else{
              $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical61'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];   
          }
          
       }
       
    }else{
        if($flag == true){
          if ($hours == 1) {
            $answer = $GLOBALS['physical62'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer = $GLOBALS['physical62'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical63'] 
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical63'] 
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }

       }

    }

  }elseif (strpos($text, $GLOBALS['physical36'])) {

      if($minutesAsleep >= 390 ){
       
       if($flag == true){
        if ($hours == 1) {
          $answer = $GLOBALS['physical64'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer = $GLOBALS['physical64'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical65']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical65']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }
       
    }else{
        if($flag == true){
          if ($hours == 1) {
            $answer = $GLOBALS['physical66'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer = $GLOBALS['physical66'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
          
       }else{
          if ($hours == 1) {
              $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical67']
            .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical67']
            .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
          
       }

    }
    
  }

  elseif(strpos($text, $GLOBALS['physical41'])){

      if($minutesAsleep >= 480 ){
       
       if($flag == true){
        if ($hours == 1) {
          $answer = $GLOBALS['physical68'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer = $GLOBALS['physical68'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }else{
        if ($hours == 1) {
            $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical69']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical69']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }
       
    }else{
        if($flag == true){
          if ($hours == 1) {
            $answer = $GLOBALS['physical70'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer = $GLOBALS['physical70'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical71']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical71']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }

    }

  }elseif(strpos($text,$GLOBALS['physical39'])){

        if($minutesAsleep >= 390 ){
       
       if($flag == true){
        if ($hours == 1) {
          $answer = $GLOBALS['physical72'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer = $GLOBALS['physical72'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical73']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical73']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }
       
    }else{
        if($flag == true){
          if ($hours == 1) {
            $answer = $GLOBALS['physical74'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
          }else{
            $answer = $GLOBALS['physical74'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
          }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical75']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical75']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }

    }

  }elseif (strpos($text,$GLOBALS['physical40'])) {

    if($minutesAsleep >= 390 ){
       
       if($flag == true){
        if ($hours == 1) {
          $answer = $GLOBALS['physical76'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer = $GLOBALS['physical76'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical77']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical77']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
       }
       
    }else{
      if($flag == true){
        if ($hours == 1) {
          $answer = $GLOBALS['physical78'].$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer = $GLOBALS['physical78'].$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
      }else{
        if ($hours == 1) {
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical79']
          .$hours. $GLOBALS['physical44'] . $minutes . $GLOBALS['physical43'];
        }else{
          $answer =$GLOBALS['physical60'].$data.$GLOBALS['physical79']
          .$hours. $GLOBALS['physical42'] . $minutes . $GLOBALS['physical43'];
        }
          
      }
    }

  }else{

      //Conversione minuti in ore e minuti
      if ($minutesAsleep < 1) {
        return $GLOBALS['physical34'];
      }
      $hours = floor($minutesAsleep / 60);
      $minutes = ($minutesAsleep % 60);

      if ($hours == 1) {
          $answer = $GLOBALS['physical80']. $hours .$GLOBALS['physical44'] .$minutes . $GLOBALS['physical43']; 
      }else{
          $answer = $GLOBALS['physical80'] . $hours .$GLOBALS['physical42'] .$minutes . $GLOBALS['physical43'];
      }
  }

  return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@data da cercare
la funzione costruisce una risposta cercando la data passata come
parametro nel file, se questa data non viene trovata verranno 
presi i dati dell'ultima data disponibile. I dati verranno quindi inseriti
nella risposta restituita da dialogflow tramite la funzione str_replace.
return risposta da stampare
*/
function fetchYesterdaySleep($resp,$data,$email){

  $param = "";
  $json_data = queryMyrror($param,$email);
  $result = null;

  //cerco data di ieri
  foreach ($json_data as $key1 => $value1) {
    if(isset($value1['sleep'])){

          foreach ($value1['sleep'] as $key2 => $value2) {

               $timestamp = $value2['timestamp'];
               $tempDate = date('Y-m-d',$timestamp/1000);
               if($data == $tempDate)
                 $result = $value2;
          }
    }
  }

  if($result['minutesAsleep'] != null){
    //risposta con data di ieri corretta
   $minutesAsleep = $result['minutesAsleep'];
   $timeinbed = $result['timeInBed'];

    //Conversione minuti in ore e minuti
    $hoursSleep = floor($minutesAsleep / 60);
    $minutesSleep = ($minutesAsleep % 60);

    //Conversione minuti in ore e minuti
    $hoursBed = floor($timeinbed / 60);
    $minutesBed = ($timeinbed % 60);

    $answer = str_replace("X1",$hoursSleep,$answer);
    $answer = str_replace('X2', $minutesSleep, $answer);
    $answer = str_replace("Y1",$hoursBed,$answer);
    $answer = str_replace('Y2', $minutesBed, $answer);

    return $answer;

  }else{
    //risposta standard con ultima data
    //algoritmo ultima data
    foreach ($json_data as $key1 => $value1) {

      if(isset($value1['sleep'])){
        $max = -1;
        foreach ($value1['sleep'] as $key2 => $value2) {
         
             $timestamp = $value2['timestamp'];
             if($timestamp > $max){
              
              $result = $value2;
              $max = $timestamp;
              
             }
         }   
      }
    }


    if (isset($timestamp)) {
      $data2 = date('d-m-Y',$timestamp/1000);
    }else{
      return $GLOBALS['physical58'];
    }



    $answer = $GLOBALS['physical1'].$data2."<br>";

    if($result['minutesAsleep'] != null){
        $answer .= $resp;

       $minutesAsleep = $result['minutesAsleep'];
       $timeinbed = $result['timeInBed'];

      //Conversione minuti in ore e minuti
      $hoursSleep = floor($minutesAsleep / 60);
      $minutesSleep = ($minutesAsleep % 60);

      //Conversione minuti in ore e minuti
      $hoursBed = floor($timeinbed / 60);
      $minutesBed = ($timeinbed % 60);

     $answer = str_replace("X1",$hoursSleep,$answer);
     $answer = str_replace('X2', $minutesSleep, $answer);
     $answer = str_replace("Y1",$hoursBed,$answer);
     $answer = str_replace('Y2', $minutesBed, $answer);

    }else{
      $answer = $GLOBALS['physical6'];
    }

     return $answer;

  }
}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
il metodo analizza i parameters se è presente la data di ieri,di oggi
oppure è stato riconosciuto un verbo al passato prossimo nella frase,
 chiama quindi il metodo fetchYesterdaySleep per ottenere i minuti di 
sonno dell'ultima notte,altrimenti viene chiamata la 
funzione fetchPastSleep che costruisce la risposta con i dati storici
return risposta da stampare  
*/
function getSleep($resp,$parameters,$text,$email){

  $yesterday = date("Y-m-d",strtotime("-1 days")); 
  $timestamp = strtotime($yesterday);



  if(isset($parameters['date'])  ||  isset($parameters['Passato']) || isset($parameters['date-period']) ){
  $date1 = substr($parameters['date'],0,10);

  //echo $yesterday;
  if($date1 == $yesterday){
    //dati di ieri 
   $answer = fetchYesterdaySleep($resp,$yesterday,$email);
    //$answer = fetchYesterdaySleep($resp,'2019-02-22');
  }else if(isset($parameters['date-period']['endDate']) && isset($parameters['date-period']['startDate'])){
   
   
   
  foreach ($parameters['date-period'] as $keyP => $valueP) {

    if($keyP == 'endDate' )
      $endDate = substr($valueP,0,10);
    else
      $startDate = substr($valueP,0,10);
    
  }

  $answer = fetchPastSleep($endDate,$startDate,$email);

  }else if(isset($parameters['Passato'])){
  //dati di ieri
     
  $answer = fetchYesterdaySleep($resp,$yesterday,$email);

  }else{
     
  //dati storici
    $answer = fetchPastSleep("","",$email);
  }

  }else{
    
  //dati storici
    $answer = fetchPastSleep("","",$email);
  }

  return $answer;

}
/*
@startDate data iniziale dell'intervallo
@endDate data finale dell'intervallo
questa funzione ricerca all'interno del file json i dati del
sonno dell'utente filtrati per data, effettua quindi una media 
dei dati sul sonno e costruisce la risposta da restituire all'utente
Se non vengono trovati dati viene effettuata una media su tutto il file

return risposta da stampare  
*/
function fetchPastSleep($endDate,$startDate,$email){

  $param = "";
  $json_data = queryMyrror($param,$email);
  $result = "";

  $count = 0;
  $sumInBed = 0;
  $sumAsleep = 0;
  foreach ($json_data as $key1 => $value1) {
    if(isset($value1['sleep'])){
       if($endDate != "" && $startDate != ""){
         //ricerca per periodo
        
      foreach ($value1['sleep'] as $key2 => $value2) {
        $timestamp = $value2['timestamp']; 
        $data = date('Y-m-d',$timestamp/1000);

        if($data >= $startDate && $data <= $endDate){
           $sumInBed += $value2['timeInBed'];
           $sumAsleep += $value2['minutesAsleep'];
           $count++;  
        }
      }
        $result = $GLOBALS['physical81'].$startDate .$GLOBALS['physical82'] .$endDate;

       }else{


     foreach ($value1['sleep'] as $key2 => $value2) {
         
         $sumInBed += $value2['timeInBed'];
         $sumAsleep += $value2['minutesAsleep'];
         $count++;      
      
     }   
  }
  }


  }

  if($count == 0){
    //non ci sono riferimenti per quel periodo
    return fetchPastSleep("","",$email);
  }
  $asleepAV = intval($sumAsleep/$count);
  $inBedAV =intval($sumInBed/$count);

  //Conversione minuti in ore e minuti
  $hoursSleep = floor($asleepAV / 60);
  $minutesSleep = ($asleepAV % 60);

  //Conversione minuti in ore e minuti
  $hoursBed = floor($inBedAV / 60);
  $minutesBed = ($inBedAV % 60);

  $result .= $GLOBALS['physical83'].$hoursSleep .$GLOBALS['physical42'] .$minutesSleep .$GLOBALS['physical84'].$hoursBed.$GLOBALS['physical42'] .$minutesSleep .$GLOBALS['physical43'];

  return $result;


}







