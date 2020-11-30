<?php
/*
@startDate data iniziale dell'intervallo in cui cercare
@endDate data finale dell'intervallo in cui cercare
la funzione ricerca all'interno del file json i dati i minuti
di attività fisica svolti 'fairlyActive' 'lightlyActive' 'veryActive'.
Viene effettuata una media di tutti e 3 i valori e viene restituito un array con 
le medie dei 3 valori , in caso non vengano trovati viene restituito un array di 0
return array di valori con l'attività fisica media
*/
function attivitaInterval($startDate,$endDate,$email){

 $activity = array(0,0,0);
 $param = "";
 $json_data = queryMyrror($param,$email);
 $count =  array(0,0,0);
 $sum =  array(0,0,0);

	foreach ($json_data as $key1 => $value1) {

	if(isset($value1['fromActivity'])){
		
     	
	foreach ($value1['fromActivity'] as $key2 => $value2) {
        
             $timestamp = $value2['timestamp'];
          $date = date('Y-m-d',$timestamp/1000);
       
       if($date <= $endDate && $date >= $startDate && $value2['nameActivity'] != "calories" 
        && $value2['nameActivity'] != "steps" 	&& $value2['nameActivity'] != "minutesSedentary" 
         && $value2['nameActivity'] != "distance"){
   
            
         	switch ($value2['nameActivity']) {
         	case 'fairly':

         		$sum[0] += $value2['minutesFairlyActive'];
         		$count[0]++; 		
                

         		break;

         	case 'minutesLightlyActive':
         			
         		$sum[1] += $value2['minutesLightlyActive'];
         		$count[1]++;
         		
         		break;

         	case 'veryActive':
         			
         		$sum[2] += $value2['minutesVeryActive'];
         	    $count[2]++;

         		break;

         	default:
         		# code...
         		break;
         }

         }

	}
		
	}
}

if($count[0] != 0 && $count[1] != 0 && $count[2] != 0 ){
	 $activity[0] = intval($sum[0] / $count[0]);
	 $activity[1] = intval($sum[1] / $count[1]);
	 $activity[2] = intval($sum[2] / $count[2]);
}
    
return $activity;
}
/*
@data data da cercare nel file
il metodo effettua una ricerca all'interno del file json
della data specificata, se vengono trovati i dati dell'attività
fisica corrispondenti a quella data saranno restitiuti 
tramite un array i dati presenti alle voci minutesfairlyActive
minutesVeryActive e minutesLightlyActive.Se non ci sono informazioni
riguardanti la data scelta verranno presi i dati dell'ultima data disponibile
return array con i 3 valori riguardanti i minuti di attività fisica
e la data corrispondente 
*/
function attivitaData($data,$email){

   $activity = array(0,0,0,"");
   $param = "";
   $json_data = queryMyrror($param,$email);


foreach ($json_data as $key1 => $value1) {

	if(isset($value1['fromActivity'])){
		
     	
	foreach ($value1['fromActivity'] as $key2 => $value2) {
        
          $timestamp = $value2['timestamp'];
          $date = date('Y-m-d',$timestamp/1000);
       
       if($date == $data && $value2['nameActivity'] != "calories"  && $value2['nameActivity'] != "steps" && 
          $value2['nameActivity'] != "minutesSedentary"  && $value2['nameActivity'] != "distance"){
   
            $activity[3] = $date;
         	switch ($value2['nameActivity']) {
         	case 'fairly':

         		$activity[0] = $value2['minutesFairlyActive']; 		
                

         		break;

         	case 'minutesLightlyActive':
         			
         		$activity[1] = $value2['minutesLightlyActive'];
         		
         		break;

         	case 'veryActive':
         			
         		$activity[2] = $value2['minutesVeryActive'];
         	
         		break;

         	default:
         		# code...
         		break;
         }

         }

	}
		
	}
}

if($activity[0] == 0 && $activity[1] == 0 && $activity[2] == 0){
	//dati non trovati per il giorno selezionato

$max = 0;

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
		
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];


       if($timestamp >= $max && $value2['nameActivity'] != "calories"  && $value2['nameActivity'] != "steps" && 
          $value2['nameActivity'] != "minutesSedentary"  && $value2['nameActivity'] != "distance"){

         	switch ($value2['nameActivity']) {
         	case 'fairly':

         		$activity[0] = $value2['minutesFairlyActive']; 		
                $max = $timestamp;

         		break;

         	case 'minutesLightlyActive':
         			
         		$activity[1] = $value2['minutesLightlyActive'];
         		$max = $timestamp;
         		break;

         	case 'veryActive':
         			
         		$activity[2] = $value2['minutesVeryActive'];
         		$max = $timestamp;
         		break;

         	default:
         		# code...
         		break;
         }

         }

	}
		
	}
}
 $activity[3] = date('Y-m-d',$max/1000);


}

return $activity;

}
/*
@resp risposta standard da Dialogflow
@parameters parametri con le info sulle date 
@text domanda scritta dall'utente
La funziona controlla parameters per verificare se c'è una sola
data oppure un intervallo di tempo, in base a questo vengono
chiamati metodi diversi attivitaInterval in caso di 
intervalli di tempo e attivitaData in caso di una singola data 
come i giorni di oggi e ieri. Tramite i dati ottenuti verrà
costruita una risposta
return risposta da stampare

*/
function attivitaFisica($resp,$parameters,$text,$email){

$answer = "";

if(isset($parameters['date-period']['startDate'])){
	//dati periodo di tempo
$startDate = substr($parameters['date-period']['startDate'],0,10);
$endDate = substr($parameters['date-period']['endDate'],0,10);
    $arr = attivitaInterval($startDate,$endDate,$email);
    if($arr[0] == 0 && $arr[1] == 0 && $arr[2] == 0){

    	 $activity = attivitaData($startDate,$email);

    if($activity[0] == 0 && $activity[1] == 0 && $activity[2] ==  0)
    	return $GLOBALS['behaviors1'];
	
	
		$answer = $GLOBALS['behaviors2'].$activity[3].$GLOBALS['behaviors3'];
		$answer .=  $activity[2] .$GLOBALS['behaviors4'].
         $activity[1] .$GLOBALS['behaviors5'];

        $answer .=  $activity[0].$GLOBALS['behaviors6'];

    }else{

       //risposta con intervallo 
    	$answer = $GLOBALS['behaviors7'];
    	$answer .= $arr[0].$GLOBALS['behaviors8'];
        $answer .= $arr[1].$GLOBALS['behaviors9'];
        $answer .= $arr[2].$GLOBALS['behaviors10'];


    }

}elseif (isset($parameters['date'])) {
	#dati oggi - ieri 


	$date = substr($parameters['date'],0,10);
    $activity = attivitaData($date,$email);

    if($activity[0] == 0 && $activity[1] == 0 && $activity[2] ==  0)
    	return $GLOBALS['behaviors1'];
	
	if($date  == $activity[3]){
        $answer = $GLOBALS['behaviors11'];
     
    $answer .=  $activity[2] .$GLOBALS['behaviors4'].
         $activity[1] .$GLOBALS['behaviors5'];

        $answer .=  $activity[0].$GLOBALS['behaviors6'];

	}else{
	$answer = $GLOBALS['behaviors12'].$activity[3].$GLOBALS['behaviors13'];
    $answer .=  $activity[2] .$GLOBALS['behaviors4'].
         $activity[1] .$GLOBALS['behaviors5'];

        $answer .=  $activity[0].$GLOBALS['behaviors6'];


	}
	

}else{
	//ultimi dati trovati
  $activity = attivitaData("",$email);

 
 $answer = $GLOBALS['behaviors14'].$activity[3].$GLOBALS['behaviors13'];
    $answer .=  $activity[2] .$GLOBALS['behaviors4'].
         $activity[1] .$GLOBALS['behaviors5'];

        $answer .=  $activity[0].$GLOBALS['behaviors6'];

}


return $answer;

}

/*
@resp risposta standard da Dialogflow
@parameters parametri con le info sulle date 
@text domanda scritta dall'utente
questa funzione se trova in parameters i dati su un periodo di 
tempo avvia attivitaInterval per ottenere i dati medi sull'attività
fisica, la risposta sarà costruita in base ai token
riconosciuti nella frase e sarà affermativa se il valore è maggiore di 30.
Se nei parametri c'è una sola data viene chiamata la funzione 
attivitaData dopo un controllo lessicale viene effettuato un 
controllo per verificare che l'attività fisica svolta sia maggiore di 30 
minuti costruendo così una risposta affermativa o negativa a seconda 
dei casi
return risposta da stampare 
*/
function attivitaFisicaBinary($resp,$parameters,$text,$email){

if(isset($parameters['date-period']['startDate'])){

  $startDate = substr($parameters['date-period']['startDate'],0,10);
  $endDate = substr($parameters['date-period']['endDate'],0,10);
  $arr = attivitaInterval($startDate,$endDate,$email);
  $sum = $arr[0] + $arr[1] + $arr[2];

  if(strpos($text, $GLOBALS['behaviors15'])){
        
        if($sum >= 30 ){
           $answer =$GLOBALS['behaviors16'].$sum.$GLOBALS['behaviors17'];
        }else{
           $answer=$GLOBALS['behaviors18'].$sum.$GLOBALS['behaviors17'];
        }
        
  }elseif(strpos($text,$GLOBALS['behaviors21'])  || strpos($text,$GLOBALS['behaviors22']) || 
    strpos($text,$GLOBALS['behaviors23']) || strpos($text,$GLOBALS['behaviors24'])) {
  	
  	    if($sum >= 30 ){
           $answer =$GLOBALS['behaviors19'].$sum.$GLOBALS['behaviors17'];
        }else{
           $answer=$GLOBALS['behaviors20'].$sum.$GLOBALS['behaviors17'];
        }
        
  }

}elseif (isset($parameters['date'])) {
   
   $date = substr($parameters['date'],0,10);
   $activity = attivitaData($date,$email);
   $sum = $activity[0] + $activity[1] + $activity[2];

$answer = $GLOBALS['behaviors25'];
     if(strpos($text, $GLOBALS['behaviors15'])){
        
        if($sum >= 30 ){
           $answer .=$GLOBALS['behaviors26'].$sum.$GLOBALS['behaviors17'];
        }else{
           $answer .=$GLOBALS['behaviors27'].$sum.$GLOBALS['behaviors17'];
        }
        
  }elseif(strpos($text,$GLOBALS['behaviors21'])  || strpos($text,$GLOBALS['behaviors22']) || 
    strpos($text,$GLOBALS['behaviors23']) || strpos($text,$GLOBALS['behaviors24'])) {
  	
  	    if($sum >= 30 ){
           $answer .=$GLOBALS['behaviors26'].$sum.$GLOBALS['behaviors17'];
        }else{
           $answer .=$GLOBALS['behaviors27'].$sum.$GLOBALS['behaviors17'];
        }
        
  }



}else{

    $answer = $GLOBALS['behaviors28'];
}

return $answer;

}

/*
@startDate data iniziale dell'intervallo
@endDate data finale dell'intervallo
la funzione effettua una ricerca nel file json cercando le calorie 
bruciate dall'utente nel periodo di tempo specificato,
se non vengono trovate delle informazioni in quell'intervallo 
vengono presi in considerzione tutti i dati presenti nel file.
Viene effettuata così una media delle calorie bruciate
return media calorie bruciate 
*/
function caloriesInterval($startDate,$endDate,$email){

$param = "";
$json_data = queryMyrror($param,$email);
$result = null;
$sum = 0;
$count = 0;


foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
	
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($startDate <= $date2 && $date2 <= $endDate  && $value2['nameActivity'] == "calories"){
         
           $sum += $value2['activityCalories'];
           $count++;
         }

	}	
		
	}
}

if($count != 0){
   $result = intval($sum/$count);
}
else{
	$result = 0;
}

return $result;

}

function caloriesDay($data,$email){

$param = "";
$json_data = queryMyrror($param,$email);
$result = null;


foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
	
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($data == $date2  && $value2['nameActivity'] == "calories"){
        
           $result = $value2;
           

         }

	}
	
		
	}
}

if(isset($result['activityCalories'])){
   
   return $result['activityCalories'];

}else{

$max = 0;
foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
		
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($timestamp > $max  && $value2['nameActivity'] == "calories"){
         
           $result = $value2;
           $max = $timestamp;

         }

	}
	
		
	}
}


if(isset($result['activityCalories'])){
   
   return $result['activityCalories'];

}else{
	return 0;
}

}



}

/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
la funzione analizza parameters e decide quale funzione 
invocare per costruire la risposta.
Se in parameters c'è un intervallo di date viene chiamata 
la funzione caloriesInterval, se invece viene trovata 
solo una singola data chiamiamo caloriesDay sostituendo in resp
il valore corrispondente dei battiti.
return risposta da stampare
*/
function getCalories($resp,$parameters,$text,$email){

$answer = "";
if(isset($parameters['date-period']['startDate'])){
	//dati periodo di tempo
$startDate = substr($parameters['date-period']['startDate'],0,10);
$endDate = substr($parameters['date-period']['endDate'],0,10);

$calAv = caloriesInterval($startDate,$endDate,$email);

	$answer = $GLOBALS['behaviors29'].$calAv.$GLOBALS['behaviors30'];

	


}else{
	if ($parameters['date']) {
	$date = substr($parameters['date'],0,10);
}else{
	$date = date('Y-m-d');
}

$cal = caloriesDay($date,$email);

    $answer = str_replace('X', $cal, $resp);

}

return $answer;

}


/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
la funzione effettua il calcolo del fabbisogno giornaliero
dell'uomo, a seconda dei parametri verrà chiamata la funzione
per gli intervallli di tempo caloriesInterval oppure
quella per i giorni singoli caloriesDay.
Successivamente vengono analizzati i token presenti nella frase,
se il valore delle calorie bruciate è maggiore del fabbisogno 
energetico l'utente ha bruciato abbastanza calorie
la risposta viene formulata di conseguenza.
return risposta da stampare 
*/
function getCaloriesBinary($resp,$parameters,$text,$email){

$peso = 80;
$eta = 22;
$altezza = 185;

$metabolismo = 66.5 + (13.8 * $peso) + (5 * $altezza) - (6.8 * $eta);


if(isset($parameters['date-period']['startDate'])){
	//dati periodo di tempo
$startDate = substr($parameters['date-period']['startDate'],0,10);
$endDate = substr($parameters['date-period']['endDate'],0,10);

$calAv = caloriesInterval($startDate,$endDate,$email);

if(strpos($text, $GLOBALS['behaviors15'])){

	if($calAv >= $metabolismo)
	 $answer = $GLOBALS['behaviors31'];
    else
	 $answer = $GLOBALS['behaviors32'];

}elseif (strpos($text, $GLOBALS['behaviors24'])) {

	if($calAv >= $metabolismo)
	 $answer = $GLOBALS['behaviors33'];
    else
	 $answer = $GLOBALS['behaviors34'];

}

	


}else{

	if ($parameters['date']) {
	$date = substr($parameters['date'],0,10);
    }else{
	$date = date('Y-m-d');
    }

$cal = caloriesDay($date,$email);



if(strpos($text, $GLOBALS['behaviors15'])){

	if($cal >= $metabolismo)
	 $answer = $GLOBALS['behaviors35'];
    else
	 $answer = $GLOBALS['behaviors36'];

}elseif (strpos($text, $GLOBALS['behaviors24'])) {

	if($cal >= $metabolismo)
	 $answer = $GLOBALS['behaviors37'];
    else
	 $answer = $GLOBALS['behaviors38'];

}

}

return $answer;
}

/*
@day giorno da cercare
la funzione cerca nel file il numero di passi in 
un determinato giorno e li restituisce in output.
Se non vengono trovati i dati allora viene preso in 
considerazioe l'ultimo giorno disponibile.
*/
function stepsDay($day,$email){

$result = null;
$param = "";
$json_data = queryMyrror($param,$email);

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
			
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($day == $date2 && $value2['nameActivity'] == "steps"){     
           $result = $value2;   
         }
	}
		
	}
}

if(isset($result['steps'])){

return  array($day,$result['steps']);


}else{

$max = -1;
foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
			
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
        
         if($timestamp > $max && $value2['nameActivity'] == "steps"){
         
           $result = $value2;   
           $max = $timestamp;
              
         }
	}
		
	}
}

if(isset($result['steps'])){
 $date2 = date('Y-m-d',$max/1000);
return  array($date2,$result['steps']);

}else{
	return array("",0);
}

}




}
/*
@startDate data iniziale dell'intervallo
@endDate data finale dell'intervallo
la funzione effettua una ricerca del numero di passi 
effettuati dall'utente in un determinato intervallo di tempo
se non vengono trovati dati in quell'intervallo vengono
considerati i dati nell'intero file.
Viene effettuata una media dei passi effettuati.
return media passi

*/
function stepsInterval($startDate,$endDate,$email){

$result = null;
$param = "";
$json_data = queryMyrror($param,$email);
$sum = 0;
$count = 0;

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
			
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($startDate <= $date2 && $date2 <= $endDate && $value2['nameActivity'] == "steps"){     
           $sum += $value2['steps'];
           $count++;

         }
	}
		
	}
}

if ($count != 0) {
	return array($startDate,intval($sum/$count));
}else{

    $sum = 0;
	foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
			
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if( $value2['nameActivity'] == "steps"){     
           $sum += $value2['steps'];
           $count++;

         }
	}
		
	}
}
	
if ($count != 0) {
	return array("",intval($sum/$count));
}else{
    return array("",0);
}

}

}
/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
La funzione se in parameters è presente un intervallo di tempo
chiama il metodo stepsInterval per ottnere una media dei passi 
effettuati e costruisce la risposta di conseguenza;
 se è presente una singola data viene chiamata stepsDay
che restituisce i passi nel giorno selezionato in questo caso verrà
utilizzata la risposta resp a cui verranno aggiunti i dati otteuti,
return risposta da stampare
*/
function getSteps($resp,$parameters,$text,$email){

if(isset($parameters['date-period']['startDate'])){

$startDate = substr($parameters['date-period']['startDate'],0,10);
$endDate = substr($parameters['date-period']['endDate'],0,10);

$arr = stepsInterval($startDate,$endDate,$email);

if($arr[0] == $startDate){
	//risposta corretta
	$answer = $GLOBALS['behaviors39'].$arr[1].$GLOBALS['behaviors40'];

}else{
	//intervallo completo
     $answer = $GLOBALS['behaviors41'];
     $answer .= $GLOBALS['behaviors42'].$arr[1].$GLOBALS['behaviors43'];
}
}else{

if(isset($parameters['date'])){

$date = substr($parameters['date'],0,10);
$arr = stepsDay($date,$email);

if($arr[0] == $date){
	$answer = str_replace('X', $arr[1], $resp);
}else{
	$answer = $GLOBALS['behaviors44'].$arr[0].". ";
	$answer .= str_replace('X', $arr[1], $resp);
}

}else{

$today = date('Y-m-d');
$arr = stepsDay($today,$email);

if($arr[0] == $date){
	$answer = str_replace('X', $arr[1], $resp);
}else{
	$answer = $GLOBALS['behaviors44'].$arr[0].". ";
	$answer .= str_replace('X', $arr[1], $resp);
}

}
}

return $answer;

}
/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
La funzione prende inizialmetnte una media dei passi presenti nel file
Se in parameters è presente un intervallo di tempo
chiama il metodo stepsInterval per ottnere una media dei passi 
effettuati nella costruzione della risposta verifica che la media dei passi
nel periodo selezionato sia maggiore della media generale, in tal caso la 
risposta sarà affermativa.
Se è presente una singola data viene chiamata stepsDay
che restituisce i passi nel giorno selezionato in questo caso verrà
confrontato il numero di passi ottenuti con la media ricavata in precedenza,
se questo valore è maggiore la risposta sarà affermativa.
return risposta da stampare
*/
function getStepsBinary($resp,$parameters,$text,$email){

$answer = "";
//media generale passi
$average = stepsInterval("","",$email);

if(isset($parameters['date-period']['startDate'])){

$startDate = substr($parameters['date-period']['startDate'],0,10);
$endDate = substr($parameters['date-period']['endDate'],0,10);

$intAv = stepsInterval($startDate,$endDate,$email);

if($intAv[0] == $startDate){

  if($intAv[1] >= $average[1]){
     $answer = $GLOBALS['behaviors45'].$intAv[1].$GLOBALS['behaviors46'];
  }else{
     $answer = $GLOBALS['behaviors47'].$intAv[1].$GLOBALS['behaviors46'];
  }

}else{

$answer = $GLOBALS['behaviors48'];
  
 $answer .= $GLOBALS['behaviors49'].$intAv[1].$GLOBALS['behaviors46'];
  
  
  

}


}elseif(isset($parameters['date'])){

$date = substr($parameters['date'], 0,10);
$arr = stepsDay($date,$email);

if($arr[1] >= $average[1]){

$answer = $GLOBALS['behaviors50'].$arr[1];	
}else{
$answer = $GLOBALS['behaviors51'].$arr[1];	
}

}else{

$date = date('Y-m-d');
$arr = stepsDay($date,$email);
$answer = $GLOBALS['behaviors52'].$arr[0]."<br>";	
if($arr[1] >= $average[1]){
$answer .= $GLOBALS['behaviors53'].$arr[1];
}else{
$answer .= $GLOBALS['behaviors54'].$arr[1];	
}

}

return $answer;

}

/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
La funzione in base ai valori presenti in parameters decide se
invocare sedentaryDay per i dati di un singolo giorno, altrimenti
se non ci sono informazioni sulla data viene invocata sendetaryDay 
con la data di oggi, infine costruisce la risposta 
sostituendo i valori di sedentarietà ottenuti a resp, 
return risposta
*/
function getSedentary($resp,$parameters,$text,$email){


$answer = "";
$date = null;
if(isset($parameters['date'])){
$date = substr($parameters['date'], 0,10);
$arr = SedentaryDay($date,$email);


}else{
	$date = date('Y-m-d');
    $arr = SedentaryDay($date,$email);
}

if($arr[0] == $date){
//dati giorno scelto
$answer = str_replace('X', $arr[1], $resp);

}else{
  
//ultimi dati
$answer = $GLOBALS['behaviors44'].$arr[0]. " ";

$hours = floor($arr[1] / 60);
$minutes = ($arr[1] % 60);

if ($hours == 1) {
  $answer .= $GLOBALS['behaviors55'].$hours.$GLOBALS['behaviors56'] .$minutes. $GLOBALS['behaviors57'];
}else{
    $answer .= $GLOBALS['behaviors55'].$hours.$GLOBALS['behaviors56'] .$minutes. $GLOBALS['behaviors57'];
}

}


return $answer;



}

/*
@resp risposta standard ricevuta da dialogflow
@parameters parametri con i dati sul tempo 
@text domanda dell'utente
La funzione prende i dati dell'ultima settimana
presenti nel file json chiamando sedentaryInterval
La risposta viene costruita analizzando i token presenti
nella frase, se i minuti di sedentarietà in una settimana saranno
9930 l'utente sarà definito sedentario
return risposta da stampare

*/

function getSedentaryBinary($resp,$parameters,$text,$email){

$answer = "";
$startWeek = date("Y-m-d",strtotime("-7 days"));
$endWeek = date('Y-m-d');

$result = sedentaryInterval($startWeek,$endWeek,$email);

if($result == null){
 return $GLOBALS['behaviors58'];	
}

if(strpos($text, $GLOBALS['behaviors63']) || strpos($text, $GLOBALS['behaviors64']) || strpos($text, $GLOBALS['behaviors65'])){
 
 if($result >= 9930)
 	$answer = $GLOBALS['behaviors59'];
 else
 	$answer = $GLOBALS['behaviors60'];
   
}else{

 if($result >= 9930)
 	$answer = $GLOBALS['behaviors61'];
 else
 	$answer = $GLOBALS['behaviors62'];

}
 
 return $answer;

}
/*
@startDate data iniziale intervallo
@endDate data finale intervallo
la funziona cerca tutti i dati sui minuti di sedentarietà
nell' intervallo di tempo specificato, i minuti vengono
sommati in result e vengono restituiti 
*/
function sedentaryInterval($startDate,$endDate,$email){

$param = "";
$json_data = queryMyrror($param,$email);
$result = null;

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
	
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($date2 >= $startDate && $date2 <= $endDate && $value2['nameActivity'] == "minutesSedentary"){
           $result += $value2["minutesSedentary"];
         }
	}
		
	}
}

return $result;



}
/*
@date data da cerare nel file
la funzione cerca i minuti di sedentarietà nel file
corrispondenti alla data passata come parametro se li
trova li restituisce altrimenti restituisce i dati dell'ultima data
disponibile
*/
function SedentaryDay($date,$email){


$param = "";
$json_data = queryMyrror($param,$email);
$result = null;

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
	
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         $date2 = date('Y-m-d',$timestamp/1000);
         if($date2 == $date && $value2['nameActivity'] == "minutesSedentary"){
           $result = $value2;
         }
	}
		
	}
}

if(isset($result['minutesSedentary'])){

 return array($date,$result['minutesSedentary']);

}else{

foreach ($json_data as $key1 => $value1) {
	
	if(isset($value1['fromActivity'])){
		$max = 0;
		
	foreach ($value1['fromActivity'] as $key2 => $value2) {
     
         $timestamp = $value2['timestamp'];
         if($timestamp > $max && $value2['nameActivity'] == "minutesSedentary"){
         
           $result = $value2;
           $max = $timestamp;

         }

	}
	
		
	}
}
if(isset($result['minutesSedentary'])){

 $date2 = date('Y-m-d',$timestamp/1000);
return array($date2,$result['minutesSedentary']);

}else{

return array("",0);

}


}

}



//Prendo gli ultimi dati sull'attività fisica
function getLastAttivitaFisica($resp,$parameters,$text,$email){

  $valori = array(); //Array che contiene i valori sull'attività fisica

  //Ultimi dati trovati
  $activity = attivitaData("2019-06-19",$email);

  $valori = [
    'abbastanzaAttiva' => $activity[0],
    'pocoAttiva' => $activity[1],
    'moltoAttiva' => $activity[2],
  ];

  return $valori;

}