<?php
/*
il metodo serve a leggere il file json completo preso da
myrror e cercare al suo interno una data specificata,se la
data viene trovata nel file verrÃ  restituita insieme al valore
corrispondente di restingHeartRate; se non viene trovata la
data specificata verranno restituiti i dati dell'ultima data disponibile
@Parameters sono i parametri sui periodi temporali
individuati da dialogflow
@data Ã¨ la data da cercare nel file
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
date presenti nell'intervallo specificato, viene fatta cosÃ¬
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
i dati del giorno o del periodo scelto la risposta verrÃ  costruita
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
                la risposta di default ($resp) restituita da dialogflow Ã¨
                costruita per la data di oggi, cosÃ¬ sostituiamo alla X presente
                in $resp il valore del battito cardiaco da stampare
                */
                $answer = str_replace('X',$arr['heart'],$resp);
            }else{

                //risposta standard
                $answer = "The latest data in my possession relates to ".$arr['date']
                    .". The heart rate is ".$arr['heart']." bpm";
            }

        }elseif($yesterday ==  $date1){

            //dati ieri
            $arr = cardioToday($parameters,$yesterday,$email);

            if($arr['date'] == $yesterday){
                $answer = "Your heart rate was ".$arr['heart']." bpm"; //risposta oggi
            }else{

                //risposta standard
                $answer = "The latest data in my possession relates to ".$arr['date']
                    .". Your heart rate is ".$arr['heart']." bpm";
            }

        }elseif(isset($parameters['date-period']['startDate'])){

            //dati ultimo giorno trovato
            $startDate =  substr($parameters['date-period']['startDate'],0,10);
            $endDate =  substr($parameters['date-period']['endDate'],0,10);
            $average = cardioInterval($startDate,$endDate,$email);

            if($average != 0){
                $answer = "On average, your heart rate is ".$average." bpm.";
            }else{
                $arr = cardioToday($parameters,"",$email);
                $answer = "The latest data in my possession relates to ".$arr['date']
                    ." and the heart rate was equal to ".$arr['heart']." bpm";
            }

        }else{
            $arr = cardioToday($parameters,"",$email);
            $answer = "The latest data in my possession relates to ".$arr['date']
                ." and the heart rate was equal to ".$arr['heart']." bpm";
        }

    }elseif (isset($parameters['date-period']['startDate'])) {

        //dati intervallo di tempo
        $startDate =  substr($parameters['date-period']['startDate'],0,10);
        $endDate =  substr($parameters['date-period']['endDate'],0,10);
        $average = cardioInterval($startDate,$endDate,$email);
        if($average != 0){
            $answer = "On average, your heart rate is ".$average." bpm.";
        }else{
            $arr = cardioToday($parameters,"",$email);
            $answer = "The latest data in my possession relates tol ".$arr['date']
                ." and the heart rate was equal to ".$arr['heart']." bpm";
        }

    }else{

        //dati ultimo giorno trovato
        $arr = cardioToday($parameters,"",$email);
        $answer = "The latest data in my possession relates tol ".$arr['date']
            ." and the heart rate was equal to ".$arr['heart']." bpm";
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
dati nella data odierna verrÃ  costruita una risposta utilizzando gli
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
            $answer = "I was not able to retrieve data for the period you indicated to me &#x1F62D;";
        }else{
            if(strpos($text, 'good') || strpos($text, 'great') || strpos($text, 'optimal') || strpos($text, 'excellent') || strpos($text, 'in the norm') || strpos($text, 'okay')){

                if($average >= 60 && $average <= 100){
                    $answer = "Yes, on average your pulse is normal. In fact I have detected ".$average." bpm";
                }else{
                    $answer = "No, on average your pulse is not normal. In fact I have detected ".$average." bpm";
                }

            }elseif (strpos($text, 'bad') || strpos($text, 'terrible') || strpos($text, 'bery bad') ||
                strpos($text, 'out of shape') || strpos($text, 'not okay') ) {

                if($average >= 60 && $average <= 100){
                    $answer = "No, on average your pulse is normal. In fact I have detected ".$average." bpm";
                }else{
                    $answer = "Yes, on average your pulse is not normal. In fact I have detected ".$average." bpm";
                }

            }
        }

    }elseif (isset($parameters['date'])) {

        $date1 = substr($parameters['date'],0,10);
        switch ($date1) {

            case $today:
                $arr = cardioToday($parameters,$today,$email);

                if(strpos($text, 'good') || strpos($text, 'great') || strpos($text, 'optimal') || strpos($text, 'excellent') || strpos($text, 'in the norm') || strpos($text, 'okay')){

                    if($arr['date'] == $today){

                        if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "Yes, your pulse is normal. In fact I have detected ".$arr['heart']." bpm";
                        else
                            $answer = "No, your pulse is not normal. In fact I have detected ".$arr['heart']." bpm";
                    }else{

                        if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                        }else{

                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
                        }

                    }

                }elseif (strpos($text, 'bad') || strpos($text, 'terrible') || strpos($text, 'bery bad') ||
                    strpos($text, 'out of shape') || strpos($text, 'not okay') ) {

                    if($arr['date'] == $today){
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "No, your pulse is normal. In fact I have detected ".$arr['heart']." bpm";
                        else
                            $answer = "Yes, your pulse is not normal. In fact I have detected  ".$arr['heart']." bpm";
                    }else{
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was normal, that is ".$arr['heart']." bpm";
                        }else{

                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
                        }

                    }

                }

                break;
            case $yesterday:

                $arr = cardioToday($parameters,$yesterday,$email);
                if(strpos($text, 'good') || strpos($text, 'great') || strpos($text, 'optimal') || strpos($text, 'excellent') || strpos($text, 'in the norm') || strpos($text, 'okay')){

                    if($arr['date'] == $yesterday){
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "Yes, your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                        else
                            $answer = "No, your pulse was not normal, in fact I found ".$arr['heart']." bpm";
                    }else{
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                        }else{

                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
                        }

                    }

                }elseif (strpos($text, 'bad') || strpos($text, 'terrible') || strpos($text, 'bery bad') ||
                    strpos($text, 'out of shape') || strpos($text, 'not okay') ) {

                    if($arr['date'] == $yesterday){
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "No, your pulse was normal, in fact I detected  ".$arr['heart']." bpm";
                        else
                            $answer = "Yes, your pulse was not normal, in fact I found  ".$arr['heart']." bpm";
                    }else{
                        if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was normal, in fact I detected  ".$arr['heart']." bpm";
                        }else{

                            $answer = "The latest data in my possession relates to ".$arr['date'].
                                ". Your pulse was not normal, in fact I detected  ".$arr['heart']." bpm";
                        }

                    }

                }

                break;
            default:

                //ultima data disponibile
                $arr = cardioToday($parameters,"",$email);
                if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                }else{

                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
                }
                break;
        }

    }else{
        $arr = cardioToday($parameters,$today,$email);
        if(strpos($text, 'good') || strpos($text, 'great') || strpos($text, 'optimal') || strpos($text, 'excellent') || strpos($text, 'in the norm') || strpos($text, 'okay')){

            if($arr['date'] == $today){
                if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                    $answer = "Yes, your pulse is normal, in fact I found ".$arr['heart']." bpm";
                else
                    $answer = "Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
            }else{
                if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                }else{

                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
                }

            }

        }elseif (strpos($text, 'bad') || strpos($text, 'terrible') || strpos($text, 'bery bad') ||
            strpos($text, 'out of shape') || strpos($text, 'not okay') ) {

            if($arr['date'] == $today){
                if($arr['heart'] >= 60 && $arr['heart'] <= 100)
                    $answer = "No, your pulse is normal, in fact I found ".$arr['heart']." bpm";
                else
                    $answer = "Yes, your pulse is not normal, in fact I have detected ".$arr['heart']." bpm";
            }else{
                if($arr['heart'] >= 60 && $arr['heart'] <= 100){
                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was normal, in fact I detected ".$arr['heart']." bpm";
                }else{

                    $answer = "The latest data in my possession relates to ".$arr['date'].
                        ". Your pulse was not normal, in fact I detected ".$arr['heart']." bpm";
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
il metodo analizza i parameters se Ã¨ presente la data di ieri
o di oggi chiama il metodo yestSleepBinary per ottenere i minutes di
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
la funzione effettua una media dei minutes trascorsi nel letto
e dei minutes di sonno, successivamente viene costruita una risposta
verificando le parole presenti all'interno della frase digitata
dall'utente e usando dei valori soglia (390 minutes di sonno) per
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
        return "I was not able to retrieve data for the period you indicated to me &#x1F62D;";
    }
    $asleepAV = intval($sumAsleep/$count);
    $inBedAV =intval($sumInBed/$count);

    //Conversione minutes in hours and minutes
    if ($asleepAV < 1) {
        return "you haven't slept &#x1F631;";
    }
    $hours = floor($asleepAV / 60);
    $minutes = ($asleepAV % 60);

    if(strpos($text, 'enough')){

        if($asleepAV >= 390){
            if ($hours == 1) {
                $result = "Yes, you sleep enough. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you sleep enough. On average you sleep" .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "No, you don't sleep enough. On average you sleep" .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "No, you don't sleep enough. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }
    }elseif (strpos($text, 'more')) {

        if($asleepAV >= 390){
            if ($hours == 1) {
                $result = "Yes, you sleep a lot. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you sleep a lot. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "No, you don't sleep much. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            } else{
                $result = "No, you don't sleep much. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }

    }elseif (strpos($text, 'good')) {

        if($asleepAV >= 390){
            if ($hours == 1) {
                $result = "Yes, you sleep well. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you sleep well. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "No, you don't sleep well. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "No, you don't sleep well. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }
    }elseif (strpos($text, 'less')) {
        if($asleepAV >= 480){
            if ($hours == 1) {
                $result = "Yes, you should sleep less. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you should sleep less. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "No, you sleep enough. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "No, you sleep enough. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }
    }elseif (strpos($text, 'most')) {

        if($asleepAV >= 390){
            if ($hours == 1) {
                $result = "No, you shouldn't sleep any more. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "No, you shouldn't sleep any more. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "Yes, you should sleep more. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you should sleep more. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }

    }elseif (strpos($text, 'little')){

        if($asleepAV >= 390){
            if ($hours == 1) {
                $result = "No, you sleep enough. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "No, you sleep enough. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }else{
            if ($hours == 1) {
                $result = "Yes, you should sleep more. On average you sleep " .$hours. " hour and " . $minutes . " minutes";
            }else{
                $result = "Yes, you should sleep more. On average you sleep " .$hours. " hours and " . $minutes . " minutes";
            }
        }

    }else{
        if ($hours == 1) {
            $result = "On average you sleep " .$hours. " hour and " . $minutes . " minutes";;
        }else{
            $result = "On average you sleep " .$hours. " hours and " . $minutes . " minutes";;
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
come parametro , se non la trova verrÃ  presa l'ultima data disponibile ,
questa distinzione avviene tramite il flag.
Viene costruita una risposta in base ai token rilevati nella frase
 usando dei valori soglia (390 minutes di sonno) per
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
            return "I was unable to retrieve data related to your sleep &#x1F62D; Check if they are present in your profile!";
        }

        if($result['minutesAsleep'] != null){
            $data = $data2;
            $minutesAsleep = $result['minutesAsleep'];
            $timeinbed = $result['timeInBed'];

        }else{
            return "I was unable to retrieve data related to your sleep &#x1F62D; Check if they are present in your profile!";
        }
    }

    //Conversione minutes in hours and minutes
    if ($minutesAsleep < 1) {
        return "You don't sleep &#x1F631;";
    }
    $hours = floor($minutesAsleep / 60);
    $minutes = ($minutesAsleep % 60);


    if(strpos($text, 'enough') || strpos($text, 'good')){

        if($minutesAsleep >= 390 ){

            if($flag == true){
                if ($hours == 1) {
                    $answer = "Yes, you've slept enough. You slept well ".$hours. " hour e " . $minutes . " minutes";
                }else{
                    $answer = "Yes, you've slept enough. You slept well ".$hours. " hours e " . $minutes . " minutes";
                }
            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and you have slept enough. Or " .$hours. " hour e " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and you have slept enough. Or ".$hours. " hours e " . $minutes . " minutes";
                }

            }

        }else{
            if($flag == true){
                if ($hours == 1) {
                    $answer = "No, you haven't slept enough. You only slept for ".$hours. " hour e " . $minutes . " minutes";
                }else{
                    $answer = "No, you haven't slept enough. You only slept for ".$hours. " hours e " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I see that you haven't slept enough. In fact only for  "
                        .$hours. " hour e " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I see that you haven't slept enough. In fact only for  "
                        .$hours. " hours e " . $minutes . " minutes";
                }

            }

        }
        /*
          }

          elseif( strpos($text, 'bene')){

              if($minutesAsleep >= 390 ){

               if($flag == true){
                  if ($hours == 1) {
                    $answer = "Si, hai dormito bene. Hai dormito ben ".$hours. " hour and " . $minutes . " minutes";
                  }else{
                    $answer = "Si, hai dormito bene. Hai dormito ben ".$hours. " hours and " . $minutes . " minutes";
                  }

               }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." e noto che hai dormito bene ovvero per ben "
                  .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." e noto che hai dormito bene ovvero per ben "
                  .$hours. " hours and " . $minutes . " minutes";
                }
               }

            }else{
                if($flag == true){
                  if ($hours == 1) {
                    $answer = "No, non hai dormito bene. Hai dormito solo per ".$hours. " hour and " . $minutes . " minutes";
                  }else{
                    $answer = "No, non hai dormito bene. Hai dormito solo per ".$hours. " hours and " . $minutes . " minutes";
                  }

               }else{
                if ($hours == 1) {
                  $answer ="The last data is from ".$data." e non hai dormito molto bene. Infatti hai dormito solo per "
                  .$hours. " hour and " . $minutes . " minutes";
                }else{
                  $answer ="The last data is from ".$data." e non hai dormito molto bene. Infatti hai dormito solo per "
                  .$hours. " hours and " . $minutes . " minutes";
                }

               }

            }
        */
    }elseif (strpos($text, 'more')) {

        if($minutesAsleep >= 390 ){

            if($flag == true){
                if ($hours == 1) {
                    $answer = "Yes, you've slept a lot. You slept well ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "Yes, you've slept a lot. You slept well ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I notice that you have slept so much. That is to say "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I notice that you have slept so much. That is to say "
                        .$hours. " hours and " . $minutes . " minutes";
                }

            }

        }else{
            if($flag == true){
                if ($hours == 1) {
                    $answer = "No, you haven't slept much. You only slept for ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "No, you haven't slept much. You only slept for ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I notice that you haven't slept so much. Only "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I notice that you haven't slept so much. Only "
                        .$hours. " hours and" . $minutes . " minutes";
                }

            }

        }

    }

    elseif(strpos($text, 'meno')){

        if($minutesAsleep >= 480 ){

            if($flag == true){
                if ($hours == 1) {
                    $answer = "Yes, you should sleep less. I see you slept for ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "Yes, you should sleep less. I see you slept for ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The latest data is on  ".$data." and I know you should sleep less. You slept for "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The latest data is on  ".$data." and I know you should sleep less. You slept for "
                        .$hours. " hours and " . $minutes . " minutes";
                }
            }

        }else{
            if($flag == true){
                if ($hours == 1) {
                    $answer = "No, you haven't slept enough. You only slept for ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "No, you haven't slept enough. You only slept for ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I notice that you haven't slept enough. Only "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I notice that you haven't slept enough. Only "
                        .$hours. " hours and " . $minutes . " minutes";
                }
            }

        }

    }elseif (strpos($text,'little')) {

        if($minutesAsleep >= 390 ){

            if($flag == true){
                if ($hours == 1) {
                    $answer = "No, you've slept enough. In fact you slept ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "No, you've slept enough. In fact you slept ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I notice that you have slept enough or "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I notice that you have slept enough or "
                        .$hours. " hours and " . $minutes . " minutes";
                }

            }

        }else{
            if($flag == true){
                if ($hours == 1) {
                    $answer = "Yes, you should sleep more. You have slept ".$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer = "Yes, you should sleep more. You have slept ".$hours. " hours and " . $minutes . " minutes";
                }

            }else{
                if ($hours == 1) {
                    $answer ="The last data is from ".$data." and I notice that you should sleep more. You only slept "
                        .$hours. " hour and " . $minutes . " minutes";
                }else{
                    $answer ="The last data is from ".$data." and I notice that you should sleep more. You only slept "
                        .$hours. " hours and " . $minutes . " minutes";
                }

            }
        }

    }else{

        //Conversione minutes in hours and minutes
        if ($minutesAsleep < 1) {
            return "I don't know you slept &#x1F631;";
        }
        $hours = floor($minutesAsleep / 60);
        $minutes = ($minutesAsleep % 60);

        if ($hours == 1) {
            $answer = "You have slept ". $hours ." hour and " .$minutes . ' minutes';
        }else{
            $answer = "You have slept ". $hours ." hours and " .$minutes . ' minutes';
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

        //Conversione minutes in hours and minutes
        $hoursSleep = floor($minutesAsleep / 60);
        $minutesSleep = ($minutesAsleep % 60);

        //Conversione minutes in hours and minutes
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
            return "I was unable to retrieve data related to your sleep &#x1F62D; Check if they are present in your profile!";
        }

        $answer = "The latest data in my possession relates to ".$data2."<br>";

        if($result['minutesAsleep'] != null){
            $answer .= $resp;

            $minutesAsleep = $result['minutesAsleep'];
            $timeinbed = $result['timeInBed'];

            //Conversione minutes in hours and minutes
            $hoursSleep = floor($minutesAsleep / 60);
            $minutesSleep = ($minutesAsleep % 60);

            //Conversione minutes in hours and minutes
            $hoursBed = floor($timeinbed / 60);
            $minutesBed = ($timeinbed % 60);

            $answer = str_replace("X1",$hoursSleep,$answer);
            $answer = str_replace('X2', $minutesSleep, $answer);
            $answer = str_replace("Y1",$hoursBed,$answer);
            $answer = str_replace('Y2', $minutesBed, $answer);

        }else{
            $answer = "I was unable to retrieve data for the period you indicated to me &#x1F62D;";
        }

        return $answer;

    }
}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
il metodo analizza i parameters se Ã¨ presente la data di ieri,di oggi
oppure Ã¨ stato riconosciuto un verbo al passato prossimo nella frase,
 chiama quindi il metodo fetchYesterdaySleep per ottenere i minutes di
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
                $result = "from ".$startDate ." to ".$endDate;

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

    //Conversione minutes in hours and minutes
    $hoursSleep = floor($asleepAV / 60);
    $minutesSleep = ($asleepAV % 60);

    //Conversione minutes in hours and minutes
    $hoursBed = floor($inBedAV / 60);
    $minutesBed = ($inBedAV % 60);

    $result .= " on average you slept ".$hoursSleep ." hours and " .$minutesSleep ." minutes, spending in bed ".$hoursBed." hours and " .$minutesSleep ." minutes";

    return $result;


}










//FORMA FISICA
function getFormaFisica($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);

    $bmi = getBmi($json_data);

    if ($bmi != null) {

        switch ($parameters['FormaFisica']) {
            case 'normal weight':
                if (isNormopeso($bmi)) {
                    $answer = 'Yes, you are normal weight';
                } else {
                    $answer = 'No, you are not normal weight';
                }
                break;
            case 'obese':
            case'overweight':
                if (isSovrappeso($bmi) == "sovrappeso") {
                    $answer = "You are overweight, you should lose weight";
                } else if (isSovrappeso($bmi) == "obeso classe 1") {
                    $answer = "You are slightly obese, you should lose a few pounds and exercise";
                } else if (isSovrappeso($bmi) == "obeso classe 2") {
                    $answer = "You are moderately obese, you should lose a few pounds and exercise";
                } else if (isSovrappeso($bmi) == "obeso classe 3") {
                    $answer = "You are in a condition of severe obesity, you should go to a specialist";
                } else {
                    $answer = "No, you are not overweight";
                }
                break;
            case 'underweight':
                if (isSottopeso($bmi) == "sottopeso") {
                    $answer = "You are underweight, you should lose a few pounds";
                } else if (isSottopeso($bmi) == "grave magrezza") {
                    $answer = "You are a condition of severe thinness, you should go to a specialist";
                } else {
                    $answer = "No, you are not underweight";
                }
                break;
            default:
                $answer = str_replace("X", $bmi, $resp);
        }
    } else {
        $answer = "
I was unable to find the information relating to your Bmi & # x1F62D ;. Make sure it's in your account";
    }

    return $answer;

}

function isNormopeso($bmi)
{
    if ($bmi <= 24.99 && $bmi >= 18.50) {
        $value = true;
    } else {
        $value = false;
    }
    return $value;
}

function isSovrappeso($bmi)
{
    if ($bmi <= 29.99 && $bmi >= 24.99) {
        $value = "sovrappeso";
    } else if ($bmi <= 34.99 && $bmi >= 30.00) {
        $value = "obeso classe 1";
    } else if ($bmi <= 39.99 && $bmi >= 35.00) {
        $value = "obeso classe 2";
    } else if ($bmi >= 40.00) {
        $value = "obeso classe 3";
    } else {
        $value = "";
    }
    return $value;
}

function isSottopeso($bmi)
{
    if ($bmi <= 18.49 && $bmi >= 16.00) {
        $value = "sottopeso";
    } else if ($bmi < 16.00) {
        $value = "grave magrezza";
    } else {
        $value = "";
    }
    return $value;
}

function getBmi($json)
{
    foreach ($json as $key1 => $value1) {
        if (isset($value1['body'])) {

            $max = 0;

            foreach ($value1['body'] as $key2 => $value2) {

                if ($value2['nameBody'] == 'BMI') {

                    $timestamp = $value2['timestamp'];
                    $foundBmi = $value2['bodyBmi'];

                    if ($timestamp > $max) {
                        $max = $timestamp;
                        $foundBmi = $value2['bodyBmi'];
                    }
                }
            }
        }
    }
    return $foundBmi;
}


/*@eta è l'età dell'utente
   questo metodo restituisce, in base all'età dell'utente, quante ore avrebbe bisogno di dormire.
*/
function getOreSonno($eta)
{

    if ($eta >= 1 && $eta <= 2) {
        $ore = [11, 14];
    } else if ($eta >= 3 && $eta <= 5) {
        $ore = [10, 13];
    } else if ($eta >= 6 && $eta <= 13) {
        $ore = [9, 11];
    } else if ($eta > 14 && $eta <= 17) {
        $ore = [8, 10];
    } else if (($eta >= 18 && $eta <= 25) || ($eta >= 26 && $eta <= 64)) {
        $ore = [7, 9];
    } else if ($eta >= 65) {
        $ore = [7, 8];
    }
    return $ore;
}

/*@json contiene le informazioni dell'utente presenti in Myrror
   questo metodo restituisce quanti minuti l'utente ha dormito.
*/


function getMinutesAsSleep($json)
{
    $minutesAsleep = 0;

    $today = date("Y-m-d");

    foreach ($json as $key1 => $value1) {
        if (isset($value1['sleep'])) {

            foreach ($value1['sleep'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'] / 1000;
                $diff = abs(strtotime($timestamp) - strtotime($today));
                if ($diff <= 86400)
                    $minutesAsleep = $value2['minutesAsleep'];
            }
        }
    }

    return $minutesAsleep;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters null
  @text contiene il testo inserito dall'utente con il quale il metodo gestisce l'interazione
  Il metodo restituisce una risposta all'utente dandogli informazioni sulle sue ore di sonno e di quanto dovrebbe dormire
*/

function getOreDiSonno($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $eta = getEtaFromMyrror($json_data);

    if ($eta != null) {
        $oreDiSonno = getOreSonno($eta);

        if (strpos($text, "rest")) {
            $minutesAsleep = getMinutesAsSleep($json_data);
            if ($minutesAsleep != 0 && $minutesAsleep <= $oreDiSonno){
                $answer = "Yeah, you should rest. You haven't slept enough tonight";
            }else{
                $answer = "Yeah, you should rest. You haven't slept enough tonight";
            }
        }else if(strpos($text,"fall asleep")){
            $minutesToFallAsleep = getMinutesToFallAsleepFromMyrror($json_data);
            if ($minutesToFallAsleep != 0 ){
                $answer = "You took ".$minutesToFallAsleep." minutes to fall asleep";
            }else{
                $answer = "I have no data on your sleep, check your profile";
            }
        }else {
            $answer = str_replace("X", $oreDiSonno[0], $resp);
            $answer = str_replace("Y", $oreDiSonno[1], $answer);
        }
    } else {
        $answer = "I am not able to give you this information & # x1F62D ;. Make sure your date of birth is in your account";
    }
    return $answer;

}

/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene il periodo o la data inserita dall'utente
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla quantità di proteine assunte in quel periodo o data
*/

function getProteine($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $proteine = 0;
    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($parameters['date'] != null) {
                    $date = strtotime($parameters['date']);


                    $difference = abs($date - $timestamp);

                    if ($difference <= 86400) {
                        if ($value2['protein'] != null) {
                            $proteine = $value2["protein"];
                        }
                    }

                }

            }
        }
    }
    if ($proteine == 0) {
        $answer = "My data does not show that you have eaten protein";
    } else {
        $answer = str_replace("X", $proteine, $resp);
    }
    return $answer;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene il periodo o la data inserita dall'utente
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla quantità di carboidrati assunte in quel periodo o data
*/
function getCarboidrati($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $carboidrati = 0;
    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($parameters['date'] != null) {
                    $date = strtotime($parameters['date']);

                    $difference = abs($date - $timestamp);

                    if ($difference <= 86400) {
                        if ($value2['carbs'] != null) {
                            $carboidrati = $value2['carbs'];
                        }
                    }

                }

            }
        }
    }
    if ($carboidrati == 0) {
        $answer = "My data does not show that you have consumed carbohydrates";
    } else {
        $answer = str_replace("X", $carboidrati, $resp);
    }
    return $answer;
}


/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene il periodo o la data inserita dall'utente
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla quantità di grassi assunti in quel periodo o data
*/
function getGrassi($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $grassi = 0;
    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($parameters['date'] != null) {
                    $date = strtotime($parameters['date']);

                    $difference = abs($date - $timestamp);

                    if ($difference <= 86400) {
                        if ($value2['fat'] != null) {
                            $grassi = $value2['fat'];
                        }
                    }

                }

            }
        }
    }
    if ($grassi == 0) {
        $answer = "My data does not show that you have consumed fats";
    } else {
        $answer = str_replace("X", $grassi, $resp);
    }
    return $answer;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene il periodo o la data inserita dall'utente
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla quantità di fibre assunte in quel periodo o data
*/

function getFibre($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $fibre = 0;
    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($parameters['date'] != null) {
                    $date = strtotime($parameters['date']);

                    $difference = abs($date - $timestamp);

                    if ($difference <= 86400) {
                        if ($value2['fiber'] != null) {
                            $fibre = $value2['fiber'];
                        }
                    }

                }

            }
        }
    }
    if ($fibre == 0) {
        $answer = "My data does not show that you have consumed fibers";
    } else {
        $answer = str_replace("X", $fibre, $resp);
    }
    return $answer;
}


/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene il la data inserita dall'utente
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla quantità d'acqua assunta  in quella data
*/
function getIdratazione($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $acqua = 0;
    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($parameters['date'] != null) {
                    $date = strtotime($parameters['date']);

                    $difference = abs($date - $timestamp);

                    if ($difference <= 86400) {
                        if ($value2['water'] != null) {
                            $acqua = $value2['water'];
                        }
                    }

                }

            }
        }
    }
    if ($acqua == 0) {
        $answer = "Check your profile or drink because my data does not show that you have drunk water";
    } else {
        $answer = str_replace("X", $acqua, $resp);
    }
    return $answer;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  Il metodo è in grado di restituire una risposta all'utente dandogli informazioni sulla sua percentuale di massa grassa corporea
*/
function getMassaGrassa($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $fat = 0;

    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['body'])) {

            $max = 0;

            foreach ($value1['body'] as $key2 => $value2) {

                if ($value2['nameBody'] == 'fat') {

                    $timestamp = $value2['timestamp'];
                    $foundBmi = $value2['bodyFat'];

                    if ($timestamp > $max) {
                        $max = $timestamp;
                        $fat = $value2['bodyFat'];
                    }
                }
            }
        }
    }

    return str_replace("X", $fat, $resp);
}


/*@sesso contiene il sesso dell'utente
  @eta contiene l'età dell'utente
  il metodo resituisce in base all'età e al sesso dell'utente la quantità d'acqua che deve bere
*/
function getSogliaAcqua($sesso, $eta)
{
    if ($eta >= 1 && $eta <= 3) {
        return 1.3;
    } else if ($eta >= 4 && $eta <= 8) {
        return 1.6;
    } else if ($eta >= 9 && $eta <= 13 && $sesso == "MALE") {
        return 2.1;
    } else if ($eta >= 9 && $eta <= 13 && $sesso == "FEMALE") {
        return 1.9;
    } else if ($eta > 13 && $sesso == "FEMALE") {
        return 2;
    } else if ($eta > 13 && $sesso == "MALE") {
        return 2.5;
    }
}


/*@json contiene le informazioni relative l'utente contenute in Myrror
  questo metodo restituisce il sesso dell'utente. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getSessoFromMyrror($json)
{
    foreach ($json as $key1 => $value1) {
        if (isset($value1['gender'])) {

            foreach ($value1['gender'] as $key2 => $value2) {
                if (isset($value2["value"])) {
                    $gender = $value2["value"];
                }
            }
        }
    }

    return $gender;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  @date contiene la data inserita dall'utente
  questo metodo restituisce la quantità d'acqua bevuta dall'utente in quella data. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getAcquaDateFromMyrror($json, $date)
{
    $acqua = 0.0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                $difference = abs($date - $timestamp);

                if ($difference <= 86400) {
                    if ($value2['water'] != null) {
                        $acqua = $value2['water'];
                    }
                }
            }
        }
    }
    return $acqua;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  @startdate contiene la data iniziale del periodo inserito dall'utente
  @startdate contiene la data finale del periodo inserito dall'utente
  questo metodo restituisce la quantità d'acqua bevuta dall'utente in quel periodo. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getAcquaPeriodFromMyrror($json, $startDate, $endDate)
{
    $water = 0.0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($timestamp >= $startDate && $timestamp <= $endDate) {
                    $water += $value2["water"];
                }

            }
        }
    }
    return $water;
}


/*@json contiene le informazioni relative l'utente contenute in Myrror
  @date contiene la data inserita dall'utente
  questo metodo restituisce i minuti di attività cardio svolta dall'utente in quella data. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getCardioMinutesDateFromMyrror($json, $date)
{
    $cardio_minutes = 0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['heart'])) {
            foreach ($value1['heart'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                $difference = abs($date - $timestamp);

                if ($difference <= 86400) {
                    if ($value2['cardio_minutes'] != null) {
                        $cardio_minutes = $value2['cardio_minutes'];
                    }
                }
            }
        }
    }
    return $cardio_minutes;
}


/*@json contiene le informazioni relative l'utente contenute in Myrror
  @startdate contiene la data iniziale del periodo inserito dall'utente
  @startdate contiene la data finale del periodo inserito dall'utente
  questo metodo restituisce i minuti di attività cardio svolta dall'utente in quel periodo. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getCardioMinutesPeriodFromMyrror($json, $startDate, $endDate)
{
    $cardio_minutes = 0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['heart'])) {
            foreach ($value1['heart'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($timestamp >= $startDate && $timestamp <= $endDate) {
                    $cardio_minutes += $value2["cardio_minutes"];
                }

            }
        }
    }
    return $cardio_minutes;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  @startdate contiene la data iniziale del periodo inserito dall'utente
  @startdate contiene la data finale del periodo inserito dall'utente
  questo metodo restituisce quante volte l'utente in quel periodo ha svolto attività cardio in quel periodo. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getCountCardioPeriodFromMyrror($json, $startDate, $endDate)
{
    $count = 0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['heart'])) {
            foreach ($value1['heart'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                if ($timestamp >= $startDate && $timestamp <= $endDate && $value2['cardio_minuts'] != null) {
                    $count++;
                }

            }
        }
    }
    return $count;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  @parameters contiene la data o il periodo inserita dell'utente
  questo metodo restituisce sottoforma di risposta all'utente i minuti di cardio svolti dall'utente in quel periodo o data
*/

function getCardioMinutes($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";

    if ($parameters['date'] != null) {
        $date = strtotime($parameters['date']);
        $cardio = getCardioMinutesDateFromMyrror($json_data, $date);

        if ($cardio != 0) {
            $answer = str_replace("X", $cardio, $resp);
        } else {
            $answer = "From the data at my disposal it does not appear that you did cardio on this day, check the profile or do some cardio that never hurts.";
        }
    } else if (isset($parameters['date-period'])) {
        $startDate = $parameters['date-period']['startDate'];
        $endDate = $parameters['date-period']['endDate'];
        $cardio = getCardioMinutesPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($cardio != 0) {
            $answer = "In the period you requested, you played minutes of " . $cardio;
        } else {
            $answer = "From the data at my disposal it does not appear that you did cardio in the period you indicated, check the profile or do some cardio that never hurts.";
        }

    }
    return $answer;
}

/*@resp null
  @parameters contiene la data o il periodo inserita dell'utente
  questo metodo restituisce sottoforma di risposta all'utente se l'utente ha raggiunto la soglia d'acqua minima da bere
*/

function getIdratazioneBinario($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";
    $acqua = 0.0;

    if ($parameters['date'] != null) {
        $date = strtotime($parameters['date']);
        $sesso = getSessoFromMyrror($json_data);
        $eta = getEtaFromMyrror($json_data);
        $sogliaAcqua = getSogliaAcqua($eta, $sesso);

        $acqua = getAcquaDateFromMyrror($json_data, $date);

        if ($acqua != 0.0) {

            if (strpos($text, "enough")) {
                if ($acqua >= $sogliaAcqua) {
                    $answer = "Yes, you have drunk enough";
                } else {
                    $answer = "No, you haven't drunk enough you should be drinking more";
                }
            } else if (strpos($text, "little")) {
                if ($acqua < $sogliaAcqua) {
                    $answer = "Yes, you drank little you should drink more";
                } else {
                    $answer = "No, you have drunk enough";
                }
            }
        } else {
            $answer = "I have no data on this day";
        }
    } else if (isset($parameters['date-period'])) {
        $sesso = getSessoFromMyrror($json_data);
        $eta = getEtaFromMyrror($json_data);
        $sogliaAcqua = getSogliaAcqua($eta, $sesso);
        $startDate = $parameters['date-period']['startDate'];
        $endDate = $parameters['date-period']['endDate'];
        $acqua = getAcquaPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($acqua != 0.0) {

            if (strpos($text, "enough")) {
                if ($acqua >= $sogliaAcqua) {
                    $answer = "Yes, you have drunk enough";
                } else {
                    $answer = "No, you haven't drunk enough you should be drinking more";
                }
            } else if (strpos($text, "little")) {
                if ($acqua < $sogliaAcqua) {
                    $answer = "Yes, you drank little you should drink more";
                } else {
                    $answer = "No, hai bevuto abbastanza";
                }
            }
        } else {
            $answer = "I have no data on this period";
        }

    }
    return $answer;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  @date contiene la data inserita dall'utente
  questo metodo restituisce le calorie assunte dall'utente in quella data. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getCalorieAssunteDateFromMyrror($json, $date)
{
    $caloriesIn = 0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['food'])) {
            foreach ($value1['food'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                $difference = abs($date - $timestamp);

                if ($difference <= 86400) {
                    if ($value2['caloriesIn'] != null) {
                        $caloriesIn = $value2['caloriesIn'];
                    }
                }
            }
        }
    }
    return $caloriesIn;
}

/*@resp contiene la risposta ricevuta da DialogFlow
  @date contiene la data inserita dall'utente
  questo metodo restituisce da quante ore l'utente è sveglio in quella data. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/

function getCalorieAssunte($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";

    if ($parameters['date'] != null) {
        $date = strtotime($parameters['date']);
        $caloriesIn = getCalorieAssunteDateFromMyrror($json_data, $date);

        if ($caloriesIn != 0) {
            $answer = str_replace("X", $caloriesIn, $resp);
        } else {
            $answer = "From the data at my disposal it does not appear that you have taken calories this day, check the profile";
        }
    }
    return $answer;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  @date contiene la data inserita dall'utente
  questo metodo restituisce da quante ore l'utente è sveglio in quella data. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/


function getOreDivegliaDateFromMyrror($json,$date){
    $oreDiVeglia = 0;
    foreach ($json as $key1 => $value1) {

        if (isset($value1['sleep'])) {
            foreach ($value1['sleep'] as $key2 => $value2) {
                $timestamp = $value2['timestamp'] / 1000;

                $difference = abs($date - $timestamp);

                if ($difference <= 86400) {
                    if ($value2['minutesAfterWakeup'] != null) {
                        $oreDiVeglia = $value2['minutesAfterWakeup'];
                    }
                }
            }
        }
    }
    return $oreDiVeglia*60;
}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  questo metodo restituisce da quanti minuti l'utente ci ha messo ad addormentarsi l'ultima notte. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/
function getMinutesToFallAsleepFromMyrror($json){
    $minutesToFallAsleep = 0;

    $today = date("Y-m-d");

    foreach ($json as $key1 => $value1) {
        if (isset($value1['sleep'])) {

            foreach ($value1['sleep'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'] / 1000;
                $diff = abs(strtotime($timestamp) - strtotime($today));
                if ($diff <= 86400)
                    $minutesToFallAsleep = $value2['minutesToFallAsleep'];
            }
        }
    }

    return $minutesToFallAsleep;

}

/*@json contiene le informazioni relative l'utente contenute in Myrror
  questo metodo restituisce da quanti minuti l'utente ci ha messo ad addormentarsi l'ultima notte. A differenza del metodo già esistente questo metodo restituisce il valore e non la risposta
*/
function getOreDiVeglia($resp, $parameters, $text, $email){
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";
    $oreDiVeglia = 0;

    if($parameters['date']!=null) {
        $date = strtotime($parameters['date']);
        $oreDiVeglia = getOreDivegliaDateFromMyrror($json_data,$date);
        if ($oreDiVeglia != 0) {

            $answer = str_replace("X", $oreDiVeglia, $resp);
        } else {
            $answer = "I have no data available to answer you, check the profile";
        }

    }
    return $answer;
}

/*@resp contiene le informazioni relative l'utente contenute in Myrror
  @parameters contiene la data o il periodo inserito dall'utente
  questo metodo restituisce sottoforma di risposta la quantità di calorie assunte dall'utente in quella data o periodo
*/

function getCalorieAssunteBinario($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";

    $altezza = getHeightFromMyrror($json_data);
    $peso = getWeightFromMyrror($json_data);
    $eta = getEtaFromMyrror($json_data);

    $metabolismo = 66.5 + (13.8 * $peso) + (5 * $altezza) - (6.8 * $eta);


    if ($parameters['date'] != null) {
        $date = strtotime($parameters['date']);
        $caloriesIn = getCalorieAssunteDateFromMyrror($json_data, $date);

        if ($caloriesIn != 0.0) {
            if (strpos($text, "enough")) {
                if ($caloriesIn >= $metabolismo ) {
                    $answer = "Yes, you've got enough calories";
                } else {
                    $answer = "No, you haven't eaten enough calories";
                }
            } else if (strpos($text, "little")) {
                if ($caloriesIn < $metabolismo) {
                    $answer = "Yes, you should be taking in more calories";
                } else {
                    $answer = "No, you've got enough calories";
                }
            }
        } else {
            $answer = "I have no data on this day, check your profile";
        }
    } else if (strpos($text, "deficit")) {
        $today = date("Y-m-d");
        $caloriesIn = getCalorieAssunteDateFromMyrror($json_data, $today);

        if ($caloriesIn != 0) {
            if ($caloriesIn < $metabolismo) {
                $answer = "Yes, you are in a calorie deficit you should take in more calories";
            } else {
                $answer = "No, you are not in a calorie deficit you have eaten enough calories";
            }
        } else {
            $answer = "I have no data on this day, check the profile";
        }
    } else {
        $answer = "You didn't tell me what day, specify";
    }

    return $answer;
}
/*@resp null
  @paramters contiene la data o il periodo inserito dall'utente
  questo metodo restituisce sottoforma di risposta se l'utente ha svolto il cardio minimo per quel periodo o quella data
*/
function getCardioMinutesBinario($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = "";
    $cardioMinutes = 0;

    if ($parameters['date'] != null) {
        $date = strtotime($parameters['date']);

        $cardioMinutes = getAcquaDateFromMyrror($json_data, $date);

        if ($cardioMinutes != 0) {

            if (strpos($text, "enough")) {
                if ($cardioMinutes >= 30) {
                    $answer = "Yes, you've done enough cardio";
                } else {
                    $answer = "No, you haven't done enough cardio do more";
                }
            } else if (strpos($text, "little")) {
                if ($cardioMinutes < 30) {
                    $answer = "Yes, you did little cardio you should do more";
                } else {
                    $answer = "No, you've done enough cardio";
                }
            }
        } else {
            $answer = "I have no data on this day";
        }
    } else if (isset($parameters['date-period'])) {
        $startDate = 0;
        $endDate = 0;
        $startDate = $parameters['date-period']['startDate'];
        $endDate = $parameters['date-period']['endDate'];
        $cardioCount = getCountCardioPeriodFromMyrror($json_data, $startDate, $endDate);
        $cardioMinutes = getCardioMinutesPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($cardioMinutes != 0) {

            if (strpos($text, "enough")) {
                if ($cardioCount >= 5 && $cardioMinutes >= 5 * 30) {
                    $answer = "Yes, you've done enough cardio";
                } else {
                    $answer = "No, you haven't done enough cardio you should be doing more";
                }
            } else if (strpos($text, "little")) {
                if ($cardioMinutes < 5 * 30 || $cardioCount < 5) {
                    $answer = "Yes, you haven't done enough cardio you should do more";
                } else {
                    $answer = "No, you've done enough cardio";
                }
            }
        } else {
            $answer = "I have no data on this period";
        }

    }
    return $answer;
}


/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco indicizzato contenente tutte le analisi
*/
function getAnalysis($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $analysisArray = array();
   

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){
                        if (isset($value['analysisName'])) {//Verifico se è valorizzata la variabile 'analysisName'

                            $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                            $timestamp = $value['timestamp'];
                            $data = date('d-m-Y', $timestamp / 1000);
                            $string = $analysis . " " . $data;

                            $analysisArray[] = $string;                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le analisi
	if (isset($analysisArray)) {
        $answer = $resp;
        $num = 0;

		if (count($analysisArray) != 0) {
			foreach ($analysisArray as $key => $value){
                ++$num;
   				$answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Type \"Analysis\" with the corresponding number for more details (example: Analysis 1)";

		}else {
			$answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your analysis &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'ultima analisi inserita in HAB
*/
function getLastAnalysis($resp,$parameters,$email){

    $param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo = 0;
   

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['analysisName'])) {//Verifico se è valorizzata la variabile 'analysisName'

                            $startDate=$value['timestamp']/1000;
                            if($startDate > $ultimo){
                                $ultimo = $startDate;
                                $lastAnalysis = $value['analysisName'];
                            }
                        }
                    }

                    foreach($value1 as $key => $value){
                        if($value['analysisName'] == $lastAnalysis){
        
                                  $analysisName = $value['analysisName'];
                                  $min = $value['min'];
                                  $max = $value['max'];
                                  $unit = $value['unit'];
                                  $result = $value['result'];

                                  $answer = $resp . " " . $analysisName . ".<br>";

                                  if(isset($min) && isset($max)){
                                       $answer = $answer . "The result should be between" . $min . $unit . " and " . $max . $unit . ".<br>";
                                  }
                                  if(isset($result) ){
                                       $answer = $answer . "The result is " . $result . $unit . ".";
                                  }                             
                        }
                    }
                     
                }
            }
				
		}
    }	
    return $answer;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la data dell'ultima analisi di un certo tipo*/
function getLastAnalysisSpecified($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo = 0;

    if($parameters['Analisi'] == null){
        $answer = $resp;
        return $answer;
    }
   

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if($value['analysisName'] == $parameters['Analisi']){
                            if (isset($value['analysisName'])) {//Verifico se è valorizzata la variabile 'analysisName'

                                $startDate=$value['timestamp']/1000;
                                if($startDate > $ultimo){
                                    $ultimo = $startDate;
                                    $lastAnalysis = $value['timestamp']/1000;
                                }
                            }
                        }
                    }


                    if(isset($lastAnalysis)){
                    $answer = $resp . " " . date('d/m/Y', $lastAnalysis);
                    }
                    else{
                        $answer = "You have never done this analysis before!";
                    }
        
                        
                        
                }
            }
				
		}
    }	
    return $answer;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco indicizzato contenente tutte le analisi
*/
function getAnalysisPeriod($resp,$parameters, $text,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$analysisArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['analysisName'])) {//Verifico se è valorizzata la variabile 'analysisName'

                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                            $timestamp = $value['timestamp'] / 1000;
                            $data = date("d-m-Y", $timestamp);
                            $string = $analysis . " " . $data;

                            if($timestamp <= $endDate && $timestamp >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                                $analysisArray[] = $string;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le analisi
	
        $answer = $resp;
        $num = 0;

		if (count($analysisArray) != 0) {
			foreach ($analysisArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
        

		}else {
			$answer = "There are not analysis in the specified period";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your analysis &#x1F613; Please try again later";
	}

	return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce un elenco con tutte le analisi da tenere
sotto controllo
*/
function getAnalysisControl($resp,$parameters,$email){

    $param = "";
	$json_data = queryMyrror($param,$email);

    $analysisArray = array();

    foreach ($json_data as $key2 => $value2) {

        if($key2 == "physicalStates"){
            foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
			if($value1 == null){
                          $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                          return $answer;
                        }

                    foreach($value1 as $key => $value){
                        if (isset($value['result'])) {//Verifico se è valorizzata la variabile 'result'

                            $result = $value['result'];
                            $min = $value['min'];
                            $max = $value['max'];

                            if($result >= $max || $result<= $min){

                                $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                                $timestamp = $value['timestamp'];
                                $data = date('d-m-Y', $timestamp / 1000);
                                $string = $analysis . " " . $data;

                                $analysisArray[] = $string;                            }
                    

                        }
                    }
                }
            }
        }
    }  
    
    //Se è valorizzato l'array, stampo le analisi
    if (isset($analysisArray)) {
        $answer = $resp . "<br>";

        if (count($analysisArray) != 0) {
            foreach ($analysisArray as $key => $value){
                $answer = $answer . " " . $value . "<br>" ;
        	    }

        	
		$answer = $answer . "<br>" . "The results are out of range.";
            

        }else {
            $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
        }

    }else{
        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
    }

    //A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
    if ($answer == null) {
        $answer = "I was unable to load your analysis &#x1F613; Please try again later";
    }

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce una risposta che indica se il risultato dell'analisi
passata come parameters è sotto, sopra o nella media
*/
function getAnalysisControlBinary($resp,$parameters,$email){


    $param = "";
    $json_data = queryMyrror($param,$email);
    $ultimo = 0;

    foreach ($json_data as $key2 => $value2) {

        if($key2 == "physicalStates"){
            foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){
                        if (isset($value['analysisName'])) {//Verifico se è valorizzata la variabile 'analysisName'

                            if($parameters['Analisi'] == $value['analysisName']){
                                $startDate=$value['timestamp'];
                                if($startDate > $ultimo){
                                    $ultimo = $startDate;
                                }
                            }
                        }
                    }

                    foreach($value1 as $key => $value){
                        if (isset($value['result'])) {//Verifico se è valorizzata la variabile 'result'
                            
                            if($ultimo == 0){
                                break;
                            }

                                if($parameters['Analisi'] == $value['analysisName'] && $value['timestamp'] == $ultimo){
                                    $result = $value['result'];
                                    $min = $value['min'];
                                    $max = $value['max'];

                                    if($result <= $max && $result>= $min){
                                        $answer =  $resp . " in range";
                                    }else{
                                        if($result > $max){
                                            $answer = $resp . " above average";
                                        }else{
                                            $answer = $resp . " below average";
                                        }
                                    }
                                }
                                
                        }
                    }
                }
            }
        }
    }

    if($parameters['Analisi'] == null){
        $answer = $resp;
    }
    
    if(!(isset($answer))){
        $answer = "You have never done this analysis before!";
    }
    
    return $answer;             

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters l'Analisi di cui si vuole sapere il risultato ricevuta da dialogflow
il metodo restituisce il risultato dell'analisi passata come parameters
*/
function getAnalysisResult($resp,$parameters,$email){

	$param = "";
    $json_data = queryMyrror($param,$email);
    $ultimo = 0;


	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {
                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){
                        if (isset($value['analysisName'])){//Verifico se è valorizzata la variabile 'analysisName'

                            if($parameters['Analisi'] == $value['analysisName']){
                                $startDate=$value['timestamp'];
                                if($startDate > $ultimo){
                                    $ultimo = $startDate;
                                }
                            }
                        }
                    }

                    
                    foreach($value1 as $key => $value){
                        if ($value['analysisName'] == $parameters['Analisi'] && $value['timestamp'] == $ultimo) {//Verifico se il nome dell'analisi è uguale a quello cercato

                            $result = $value['result']; //Prendo il risultato dell'analisi
                            break;
                            
                        }
                    }
                }
				
			}
        }	
    }
    
    if($parameters['Analisi'] == null){
        return $answer = $resp;
    }

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if (!isset($result)) {
		$answer = "You have never done this analysis before!";
    }
    else {
        $answer = $resp . " " . $result;
    }

	return $answer;

}
/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce l'Analisi richiesta dall'utente indicata mediante l'indice
*/
function getAnalysisDetails($parameters,$email){
    $param = "";
    $json_data = queryMyrror($param,$email);
    $numAnalysis = 0;


	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        ++$numAnalysis;
                        if($numAnalysis == $parameters['number']){

                            $answer = "The result of " . $value['analysisName'] . " should be between " . $value['min'] . $value['unit'] .  " and " . $value['max'] . $value['unit'] . ". Your result is " . $value['result'] . $value['unit'];

                        }
                    }
                }
            }
        }
    }

    if($parameters['number'] > $numAnalysis){
        $answer = "There is not an analysis with the specified number";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo di presenza
il metodo restituisce si o no se l'analisi è stata effettuata o meno dall'utente
*/
function getAnalysisBinary($resp, $parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);


	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if ($value['analysisName'] == $parameters['Analisi']  ) {//Verifico se il nome dell'analisi è uguale a quello cercato
                            $answer = "Yes, you have done this analysis";
                        }
                    }
			    }
            }	
        }    
    }
    
	if($parameters['Analisi'] == null){
        $answer = $resp;
    }
    
    if(!(isset($answer))){
        $answer = "You have never done this analysis";
    }

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow di cui si vuole il risultato
il metodo restituisce l'andamento dell'analisi
*/
function getAnalysisTrend($resp,$parameters,$email){

    $param = "";
    $json_data = queryMyrror($param,$email);
    $found=0;

    $resultsArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {
                if($key1 == "analysis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){
                        if (isset($parameters['Analisi'])){
                            if (isset($value['analysisName'])){//Verifico se è valorizzata la variabile 'analysisName'

                            
                                if($parameters['Analisi'] == $value['analysisName']){
                                    $data = date('d-m-Y', ($value['timestamp']/1000));
                                    $resultsArray[] = $value['result'] . $value['unit'] . " " . $data;
				    $found=1;
                                }
                            }
                        }
                    }

                }
				
			}
        }	
    }
    

    if($parameters['Analisi'] == null){
        return $answer = $resp;
    }
    if($found=0){
       $answer="The " . $parameters['Analisi'] . " is not between your analysis";
       return $answer;
    }

    //Se è valorizzato l'array, stampo i risultati
    if (isset($resultsArray)) {
        $answer = $resp;

		if (count($resultsArray) != 0) {
			foreach ($resultsArray as $key => $value){
                $answer = $answer . "<br>" . $value;
            }

		}else {
			$answer = "The " . $parameters['Analisi'] . " is not between your analysis";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your analysis &#x1F613; Please try again later or check if your analysis is in your profile!";
	}
	return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'elenco delle diagnosi effettuate
*/
function getDiagnosis($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $diagnosisArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diagnosis"){
                    foreach($value1 as $key => $value){
                        if (isset($value['diagnosis_name'])) {//Verifico se è valorizzata la variabile 'diagnosis_name'

                            $diagnosis = $value['diagnosis_name']; //Prendo il nome delle diagnosi
                            
                            $diagnosisArray[] = $diagnosis;
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le diagnosi
	if (isset($diagnosisArray)) {
        $answer = $resp;
        $num = 0;

		if (count($diagnosisArray) != 0) {
			foreach ($diagnosisArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "Unfortunately I was unable to retrieve your diagnosis &#x1F613; Please try again later or check if your diagnosis are in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your diagnosis &#x1F613; Please try again later or check if your diagnosis are in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your diagnosis &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco indicizzato contenente tutte le diagnosi
*/
function getDiagnosisPeriod($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$diagnosisArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diagnosis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your diagnosis &#x1F613; Please try again later or check if your diagnosis are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['diagnosis_name'])) {//Verifico se è valorizzata la variabile 'diagnosis_name'

                            $timestamp = $value['timestamp'];
                            $data = substr($timestamp, 0, 10);

                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            if($data <= $endDate && $data >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                            $diagnosis = $value['diagnosis_name']; //Prendo il nome della diagnosi
                            $diagnosisArray[] = $diagnosis;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le diagnosi
	
        $answer = $resp;
        $num = 0;

		if (count($diagnosisArray) != 0) {
			foreach ($diagnosisArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "There are not diagnosis in the specified period";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your diagnosis &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la diagnosi più recente
confrontando i relativi timestamp tra loro 
*/
function getLastDiagnosy($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo = 0;
   

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diagnosis"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your diagnosis &#x1F613; Please try again later or check if your diagnosis are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['diagnosis_name'])) {//Verifico se è valorizzata la variabile 'diagnosis_name'

                            $startDate=$value['timestamp']/1000;
                            if($startDate > $ultimo){
                                $ultimo = $startDate;
                                $lastDiagnosis = $value['diagnosis_name'];
                            }
                        }
                    }

                    foreach($value1 as $key => $value){
                        if($value['diagnosis_name'] == $lastDiagnosis){
        
                            $answer = $resp . " " . $value['diagnosis_name'];
        
                        }
                    }
                        
                }
            }
				
		}
    }	
    return $answer;
}

//restituisce il giorno della settimana
function giorno($d){
 
    //attento la data deve essere nel formato yyyy-mm-gg
    //anche come separatori (se altri separatori devi modificare)
    $d_ex=explode("-", $d);//attento al separatore
    $d_ts=mktime(0,0,0,$d_ex[1],$d_ex[2],$d_ex[0]);
    $num_gg=(int)date("N",$d_ts);//1 (for Monday) through 7 (for Sunday)
    //per nomi in italiano
    $giorno=array('','monday','thursday','wednesday','tuesday','friday','saturday','sunday');//0 vuoto
    return $giorno[$num_gg];
}


//restituisce il numero di giorni trascorsi tra due date
function delta_tempo ($data_iniziale,$data_finale,$unita) {
    
        switch($unita) {
               case "m": $unita = 1/60; break;       //MINUTI
               case "h": $unita = 1; break;          //ORE
               case "g": $unita = 24; break;         //GIORNI
               case "a": $unita = 8760; break;         //ANNI
        }
     
     $differenza = (($data_finale-$data_iniziale)/3600)/$unita;
     return $differenza;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco contenente tutte le terapie
*/
function getTherapies($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $therapiesArray = array();
    $answerDrug = $resp;
    
    
	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){

                    foreach($value1 as $key => $value){


                         if(isset($value['therapyName'])) {//Verifico se è valorizzata la variabile 'therapiesName'

                            $therapy = $value['therapyName']; //Prendo il nome delle terapie
                            $therapiesArray[] = $therapy; //tutte le terapie

                        }
                        

                    }
                    
                    if(isset($parameters['date'])){
                        return $answerDrug;
                    }

                    
				}
            }	
        }
    }

    //Se è valorizzato l'array, stampo le terapie
	if (isset($therapiesArray)) {
        $answer = $resp;
        $num = 0;

		if (count($therapiesArray) != 0) {
			foreach ($therapiesArray as $key => $value){
                ++$num;
                $answer = $answer . "<br> " . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Type \"Therapy\" with the corresponding number for more details (example: Therapy 1)";

		}else {
			$answer = "I was unable to load your therapies &#x1F613; Please try again later";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your therapies &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la terapia più recente
confrontando le relative date tra loro 
*/
function getLastTherapy($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo=0;
    
    
    
	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){


                         if(isset($value['therapyName'])) {//Verifico se è valorizzata la variabile 'therapiesName'

                            $startDate=$value['timestamp']/1000;
                            if($startDate > $ultimo){
                                $ultimo = $startDate;
                                $lastTherapy = $value['therapyName'];;
                            }
                        }
                        

                    }
                    
                    $answer = $resp . " " . $lastTherapy . "<br>";

                    foreach($value1 as $key => $value){
                        if($value['therapyName'] == $lastTherapy){

                            $type=$value['type'];
                            $today = strtotime("now");
                            $endDate = strtotime($value['end_date']);


                            if($value['end_date'] == null){
                                $answer = $answer . "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'];
                            }else if ($endDate > $today){
                                $answer = $answer . "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'] . " and will end the " . $value['end_date'];
                            }else {
                                $answer = $answer . "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'] . " and is ended the " . $value['end_date'];
                            }

                                switch($type){

                                    case "EVERY_DAY":
                                        $answer = $answer . " and provides everyday " . $value['drug_name'] . " ";
                                        if(isset($value['dosage'])){
                                            $answer = $answer . " " . $value['dosage'];
                                        }
                                        if(isset($value['hour'])){
                                            $answer = $answer . " at the hour " . $value['hour'];
                                        } 
                                        break;
                                        

                                    case "INTERVAL":
                
                                            $answer = $answer . " and provides every " . $value['interval_days'] . " days " . $value['drug_name'] . " ";
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            } 
                                        break;

                                    case "SOME_DAY":
                                        
                                            $answer = $answer . " and provides " . $value['day'] . " " . $value['drug_name'];
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            } 
                                        
                                        break;

                                    case "ODD_DAY":
                                            $answer = $answer . " and provides in alternate days " . $value['drug_name'];
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            }                                         
                                        break;

                                }
                            break;
                        }
                    }
                    
				}
            }	
        }
    }
    return $answer;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters La data di oggi restituita da dialogflow
il metodo restituisce il farmaco da prendere nella giornata odierna 
confrontando la data di oggi con le date in cui prendere il farmaco
*/
function getDrugToday($resp,$parameters,$email){

    $param = "";
	$json_data = queryMyrror($param,$email);

    $therapiesArray = array();
    $answerDrug = $resp . "<br>";
    $numDrug = 0;
    

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
                        return $answer;
                    }

                    foreach($value1 as $key => $value){

                        if(isset($parameters['date'])){
                            $today = strtotime($parameters['date']);
                            $giornoToday = giorno(substr($parameters['date'], 0, 10));
                            $startDate = strtotime($value['start_date']);
                            $endDate = strtotime($value['end_date']);


                            if(($value['end_date'] == null) || ($endDate > $today)){


                                
                                $type=$value['type'];
                                $drugName = $value['drug_name']; //Prendo il nome del farmaco

                                switch($type){

                                    case "EVERY_DAY":
                                        $answerDrug = $answerDrug . "-" . $drugName;
                                        if(isset($value['dosage'])){
                                            $answerDrug = $answerDrug . " " . $value['dosage'];
                                        }
                                        if(isset($value['hour'])){
                                            $answerDrug = $answerDrug . " at the hour " . $value['hour'];
                                        } 
                                        $answerDrug = $answerDrug . "<br>" ;
                                        ++$numDrug;
                                        break;
                                        

                                    case "INTERVAL":
                                        $intervalDays = $value['interval_days'];
                                        $giorniDaStartDate = delta_tempo($startDate, $today, "g");
                                        if((int)($giorniDaStartDate % $intervalDays) == 0){
                
                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if(isset($value['dosage'])){
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answerDrug = $answerDrug . " at the hour " . $value['hour'];
                                            } 
                                            $answerDrug = $answerDrug . "<br>";
                                            ++$numDrug;
                                        }
                                        
                                        break;

                                    case "SOME_DAY":
                                        if($value['day'] == $giornoToday){
                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if(isset($value['dosage'])){
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answerDrug = $answerDrug . " at the hour " . $value['hour'];
                                            } 
                                            $answerDrug = $answerDrug . "<br>";
                                            ++$numDrug;
                                        }
                                        break;

                                    case "ODD_DAY":
                                        $giorniDaStartDate = delta_tempo($startDate, $today, "g");
                                        $resto = (int)($giorniDaStartDate % 2);
                                        if($resto == 0){
                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if(isset($value['dosage'])){
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answerDrug = $answerDrug . " at the hour " . $value['hour'];
                                            } 
                                            $answerDrug = $answerDrug . "<br>";
                                            ++$numDrug;
                                        }
                                        
                                        break;
                                            
                                }
                            } 
                        }
                    }
                }
            }
        }
    }
    if($numDrug!=0){
        //Rimuovo lo spazio con la virgola finale
        $answerDrug = substr($answerDrug, 0, -2);
    }
    else {
        $answerDrug = "Today you not have any therapy to do";
        }

    return $answerDrug;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco contenente tutte le terapie nel periodo specificato
*/
function getTherapiesPeriod($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$therapiesArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['therapyName'])) {//Verifico se è valorizzata la variabile 'therapyName'

                            
                            $data = strtotime($value['start_date']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            if($data <= $endDate && $data >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                            $therapies = $value['therapyName']; //Prendo il nome della terapia
                            $therapiesArray[] = $therapies;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le terapie
	
        $answer = $resp;
        $num = 0;

		if (count($therapiesArray) != 0) {
			foreach ($therapiesArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "There are not therapies in the specified period";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your therapies &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco di terapie distinguendole se in corso o concluse
*/
function getTherapiesInProgEnded($resp,$parameters,$email){
    $param = "";
	$json_data = queryMyrror($param,$email);

    $therapiesInProgArray = array();
    $therapiesEndedArray = array();
    $today = strtotime("now"); //data odierna

    $question = 0; //0 se la domanda richiede le terapie in progress, 1 altrimenti terapie ended    

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['therapyName'])) {//Verifico se è valorizzata la variabile 'therapyName'

                            if(isset($parameters['Durata_terapia'])){
                                $durata = $parameters['Durata_terapia'];
                                $endDate = strtotime($value['end_date']);
                                if($durata == "ended" ){
                                    $question = 1;
                                    if(($value['end_date'] != null) && ($endDate < $today)){
                                        $therapies = $value['therapyName']; //Prendo il nome della terapia
                                        $therapiesEndedArray[] = $therapies;
                                    }
                                }else{
                                    if(($value['end_date'] == null) || ($endDate > $today)){
                                    $therapies = $value['therapyName']; //Prendo il nome della terapia
                                    $therapiesInProgArray[] = $therapies;
                                    }
                                }
                            }
                            
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le terapie
	
		$answer = $resp;

		if($question == 0){

		    if (count($therapiesInProgArray)!=0) {

                foreach ($therapiesInProgArray as $key => $value){
                    $answer = $answer . " " . $value .", " ;
        	    }

        	//Rimuovo lo spazio con la virgola finale
        	$answer = substr($answer, 0, -2);

            } else {
                $answer ="There are not therapies in progress";

            }
        }else {

            if (count($therapiesEndedArray)!=0) {

			    foreach ($therapiesEndedArray as $key => $value){
                    $answer = $answer . " " . $value .", " ;
        	    }

        	    //Rimuovo lo spazio con la virgola finale
        	    $answer = substr($answer, 0, -2);
            }
            else {
                $answer ="There are not therapies ended";

            }

		}
	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your therapies &#x1F613; Please try again later";
	}

	return $answer;
}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters numero della terapia di cui si desidera 
avere maggiori dettagli
il metodo analizza il parametro e prende dal file json n-esimo
elemento delle terapie
*/
function getTherapyDetails($parameters,$email){

    $param = "";
    $json_data = queryMyrror($param,$email);
    $numTherapies = 0;


	foreach ($json_data as $key2 => $value2) {
    
		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "therapies"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your therapies &#x1F613; Please try again later or check if your therapies are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        ++$numTherapies;
                        if($numTherapies == $parameters['number']){

                            $type=$value['type'];
                            $today = strtotime("now");
                            $endDate = strtotime($value['end_date']);


                            if($value['end_date'] == null){
                                $answer = "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'];
                            }else if ($endDate > $today){
                                $answer = "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'] . " and will end the " . $value['end_date'];
                            }else {
                                $answer = "The therapy " . $value['therapyName'] . " is started the " . $value['start_date'] . " and will end the " . $value['end_date'];
                            }

                                switch($type){

                                    case "EVERY_DAY":
                                        $answer = $answer . " and provides everyday " . $value['drug_name'] . " ";
                                        if(isset($value['dosage'])){
                                            $answer = $answer . " " . $value['dosage'];
                                        }
                                        if(isset($value['hour'])){
                                            $answer = $answer . " at the hour " . $value['hour'];
                                        } 
                                        break;
                                        

                                    case "INTERVAL":
                
                                            $answer = $answer . " and provides every " . $value['interval_days'] . " days " . $value['drug_name'] . " ";
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            } 
                                        break;

                                    case "SOME_DAY":
                                        
                                            $answer = $answer . " and provides " . $value['day'] . " " . $value['drug_name'];
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            } 
                                        
                                        break;

                                    case "ODD_DAY":
                                            $answer = $answer . " and provides in alternate days " . $value['drug_name'];
                                            if(isset($value['dosage'])){
                                                $answer = $answer . " " . $value['dosage'];
                                            }
                                            if(isset($value['hour'])){
                                                $answer = $answer . " at the hour " . $value['hour'];
                                            }                                         
                                        break;

                                }
                            break;
                        }
                    }
                }
            }
        }
    }

    if($parameters['number'] > $numTherapies){
        $answer = "There is not a therapy with the specified number";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restiuisce un elenco di tutte le 
aree mediche ricercate
*/
function getMedicalAreas($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $medicalAreasArray = array();
    

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalAreas"){
                    foreach($value1 as $key => $value){
                        if (isset($value['medicalArea'])) {//Verifico se è valorizzata la variabile 'medicalArea'

                            $medicalArea = $value['medicalArea']; //Prendo il nome delle area medica
                            
                            $medicalAreasArray[] = $medicalArea;
                        }
                    }
                }
				
			}
        }	
    }



    //Se è valorizzato l'array, stampo le aree mediche
	if (isset($medicalAreasArray)) {
		$answer = $resp;

		if (count($medicalAreasArray) != 0) {
			foreach ($medicalAreasArray as $key => $value){
   				$answer = $answer . " " . $value .", " ;
        	}

        	//Rimuovo lo spazio con la virgola finale
        	$answer = substr($answer, 0, -2);
		}else {
			$answer = "Unfortunately I was unable to retrieve your medical areas &#x1F613; Please try again later or check if your medical areas are in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your medical areas &#x1F613; Please try again later or check if your medical areas are in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your medical areas &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'ultima area medica ricercata 
*/
function getLastMedicalArea($resp,$parameters,$email){

    $param = "";
	$json_data = queryMyrror($param,$email);

    
	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalAreas"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your medical areas &#x1F613; Please try again later or check if your medical areas are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['medicalArea'])) {//Verifico se è valorizzata la variabile 'medicalArea'

                            $medicalArea = $value['medicalArea']; //Prendo il nome delle area medica
                            
                            $medicalAreasArray[] = $medicalArea;
                        }
                    }
                }
				
			}
        }	
    }

    $ultimo = end($medicalAreasArray);
    $answer = $resp . " " . $ultimo;

    return $answer;


}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restiuisce un elenco indicizzato di tutte le visite mediche
*/
function getMedicalVisits($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$medicalVisitsArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalVisits"){
                    foreach($value1 as $key => $value){
                        if (isset($value['nameVisit'])) {//Verifico se è valorizzata la variabile 'nameVisit'

                            $medicalVisit = $value['nameVisit']; //Prendo il nome delle visita medica
                            
                            $medicalVisitsArray[] = $medicalVisit;
                        }
                    }
                }
				
			}
        }	
    }



    //Se è valorizzato l'array, stampo le visite mediche
	if (isset($medicalVisitsArray)) {
        $answer = $resp;
        $num = 0;

		if (count($medicalVisitsArray) != 0) {
			foreach ($medicalVisitsArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Type \"Medical visit\" with the corresponding number for more details (example: Medical visit 1)";

		}else {
			$answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your medical visits &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restituisce la visita medica più recente
confrontando le relative date tra loro 
*/
function getLastMedicalVisit($resp,$parameters,$email){


    $param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo = 0;

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalVisits"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['nameVisit'])) {//Verifico se è valorizzata la variabile 'nameVisit'

                            $startDate=$value['timestamp']/1000;
                            if($startDate > $ultimo){
                                $ultimo = $startDate;
                                $lastMedicalVisit = $value['nameVisit'];
                            }
                        }
                    }


                        foreach($value1 as $key => $value){
                            if($value['nameVisit'] == $lastMedicalVisit){
                                $dateVisit = $value['dateVisit'];
                                $nameDoctor = $value['nameDoctor'];
                                $surnameDoctor = $value['surnameDoctor'];
                                $nameFacility = $value['nameFacility'];
                                $cityFacility = $value['cityFacility'];
                                $descriptionFacility = $value['descriptionFacility'];
                                $typology = $value['typology'];
                                $diagnosis = $value['diagnosis'];
                                $medicalPrescription = $value['medicalPrescription'];
                                $notePatient = $value['notePatient'];
                
                                $answer = $resp . " " . $lastMedicalVisit;

                                if(isset($typology)){
                                    $answer = $answer . " (" . $typology . ")";
                                }
                                if(isset($startDate)){
                                    $answer = $answer . " in date " . $dateVisit ;
                                }
                                if(isset($nameDoctor) || isset($surnameDoctor)){
                                    $answer = $answer . " executed by the doctor " . $nameDoctor . " " . $surnameDoctor;
                                }
                                if(isset($nameFacility)){
                                    $answer = $answer . " at the facility" . $nameFacility;
                                }
                                if(isset($cityFacility)){
                                    $answer = $answer . " in the city " . $cityFacility;
                                }
                                if(isset($descriptionFacility)){
                                    $answer = $answer . "(" . $descriptionFacility . ")";
                                }
                                if(isset($diagnosis)){
                                    $answer = $answer . ". The diagnosis has been " . $diagnosis;
                                }
                                if(isset($medicalPrescription)){
                                    $answer = $answer . ". The doctor has prescribed to you " . $medicalPrescription;
                                }
                                if(isset($note)){
                                    $answer = $answer . ". NOTE: " . $note;
                                }
                                
                
                
                            }
                
                        }
                }
            }
        }
    }
                
                
    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la data dell'ultima visita medica di un certo tipo*/
function getLastMedicalVisitSpecified($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

    $ultimo = 0;

    if($parameters['TipologiaVisitaMedica'] == null){
        $answer = $resp;
        return $answer;
    }

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalVisits"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if($value['typology'] == $parameters['TipologiaVisitaMedica']){
                            if (isset($value['nameVisit'])) {//Verifico se è valorizzata la variabile 'analysisName'

                                $dateVisit=strtotime($value['dateVisit']);
                                if($dateVisit > $ultimo){
                                    $ultimo = $dateVisit;
                                    $lastMedicalVisit = $value['dateVisit'];
                                }
                            }
                        }
                    }

                    

                    if(isset($lastMedicalVisit)){
                    $answer = $resp . " " . $lastMedicalVisit;
                    }
                    else{
                        $answer = "You have never done this medical visit before!";
                    }
        
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
il metodo analizza i parameters start_date e end_date,
se la data della visita medica è compresa in questo periodo 
viene inserita nell'array che sarà stampato
*/
function getMedicalVisitsPeriod($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$medicalVisitsArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalVisits"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['nameVisit'])) {//Verifico se è valorizzata la variabile 'nameVisit'

                            
                            $data = strtotime($value['dateVisit']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            if($data <= $endDate && $data >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                            $medicalVisit = $value['nameVisit']; //Prendo il nome della visita medica
                            $medicalVisitsArray[] = $medicalVisit;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le visite mediche
	
        $answer = $resp;
        $num = 0;

		if (count($medicalVisitsArray) != 0) {
			foreach ($medicalVisitsArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "There are not medical visits in the specified period";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to retrieve your medical visits &#x1F613; Please try again later";
	}

	return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters numero della visita medica di cui si desidera 
avere maggiori dettagli
il metodo analizza il parametro e prende dal file json n-esimo
elemento delle visite mediche
*/
function getMedicalVisitDetails($parameters,$email){

    $param = "";
    $json_data = queryMyrror($param,$email);
    $numMedicalVisits = 0;


	foreach ($json_data as $key2 => $value2) {
    
		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "medicalVisits"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your medical visits &#x1F613; Please try again later or check if your medical visits are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        ++$numMedicalVisits;
                        if($numMedicalVisits == $parameters['number']){

                            $nameVisit = $value['nameVisit'];
                            $dateVisit = $value['dateVisit'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $nameFacility = $value['nameFacility'];
                            $cityFacility = $value['cityFacility'];
                            $descriptionFacility = $value['descriptionFacility'];
                            $typology = $value['typology'];
                            $diagnosis = $value['diagnosis'];
                            $medicalPrescription = $value['medicalPrescription'];
                            $notePatient = $value['notePatient'];
                
                            $answer = "The medical visit " . $nameVisit ;

                            if(isset($typology)){
                                $answer = $answer . " (" . $typology . ")";
                            }
                            if(isset($startDate)){
                                $answer = $answer . " has been done in date " . $dateVisit ;
                            }
                            if(isset($nameDoctor) || isset($surnameDoctor)){
                                $answer = $answer . " executed by the doctor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if(isset($nameFacility)){
                                $answer = $answer . " at the facility " . $nameFacility;
                            }
                            if(isset($cityFacility)){
                                $answer = $answer . " in the city of " . $cityFacility;
                            }
                            if(isset($descriptionFacility)){
                                $answer = $answer . "(" . $descriptionFacility . ")";
                            }
                            if(isset($diagnosis)){
                                $answer = $answer . ". The diagnosis has been " . $diagnosis;
                            }
                            if(isset($medicalPrescription)){
                                $answer = $answer . ". The doctor has prescribed to you " . $medicalPrescription;
                            }
                            if(isset($note)){
                                $answer = $answer . ". NOTE: " . $note;
                            }

                        break;
                        }
                    }
                }
            }
        }
    }

    if($parameters['number'] > $numMedicalVisits){
        $answer = "There is not a medical visit with the specified number";
    }

    return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restiuisce un elenco indicizzato di tutte le patologie
*/
function getDiseases($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$diseasesArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diseases"){
                    foreach($value1 as $key => $value){

                        if(isset($parameters['Patologia'])){
                            if($value['nameDisease'] == $parameters['Patologia']){
                                $dateDiagnosis = $value['dateDiagnosis'];
                                $nameDoctor = $value['nameDoctor'];
                                $surnameDoctor = $value['surnameDoctor'];
                                $placeDiagnosis = $value['placeDiagnosis'];
                                $completeDiagnosis = $value['completeDiagnosis'];
                                $note = $value['note'];

                                $answer = $resp;
        
                                if($dateDiagnosis != NULL){
                                    $answer = $answer . " in date " . $dateDiagnosis;
                                }


                                if($nameDoctor!= NULL || $surnameDoctor!= NULL){
                                    $answer = $answer . " by the doctor " . $surnameDoctor . " " . $nameDoctor;
                                }
                                if ($placeDiagnosis != NULL){
                                    $answer = $answer . " at " . $placeDiagnosis;
                                }
                                if ($completeDiagnosis != NULL){
                                    $answer = $answer . ". The complete diagnosis is " . $completeDiagnosis . ". ";
                                }
                                if ($note!= NULL){
                                    $answer = $answer . "NOTE: " . $note;
                                }
                                return $answer;  
                            }


                        }

                        if (isset($value['nameDisease'])) {//Verifico se è valorizzata la variabile 'nameDisease'

                            $disease = $value['nameDisease']; //Prendo il nome delle patologia
                            
                            $diseasesArray[] = $disease;
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le patologie
	if (isset($diseasesArray)) {
        $answer = $resp;
        $num = 0;

		if (count($diseasesArray) != 0) {
			foreach ($diseasesArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Type \"Disease\" with the corresponding number for more details (example: Disease 1)";

		}else {
			$answer = "Unfortunately I was unable to retrieve your diseases &#x1F613; Please try again later or check if your diseases are in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your diseases &#x1F613; Please try again later or check if your diseases are in your profile!";
	}

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your diseases &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@parameters parametri contenenti il nome di una patologia
il metodo analizza il parametro e ricerca il nome della
patologia nell'elenco 
restituisce una risposta binaria
*/
function getDiseasesBinary($parameters,$email){
    $param = "";
    $json_data = queryMyrror($param,$email);
    $answer = NULL;
    
    foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diseases"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your diseases &#x1F613; Please try again later or check if your diseases are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){

                        if(isset($parameters['Patologia'])){

                            if($value['nameDisease'] == $parameters['Patologia']){
                                $answer = "Yes, it has been diagnosticated to you in date " . $value['dateDiagnosis'] . ".";
                            }
                        }
                    }
                }
            }
        }
    }
    if($answer == NULL){
        $answer = "No, it has not been diagnosticated to you.";
    }
    return $answer;
}



/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
il metodo analizza i parameters start_date e end_date
se la data della patologia è compresa in questo periodo 
viene inserita nell'array che sarà stampato
*/
function getDiseasesPeriod($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$diseasesArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diseases"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your diseases &#x1F613; Please try again later or check if your diseases are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['nameDisease'])) {//Verifico se è valorizzata la variabile 'nameDisease'

                            
                            $data = strtotime($value['dateDiagnosis']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            if($data <= $endDate && $data >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                            $disease = $value['nameDisease']; //Prendo il nome della patologia
                            $diseasesArray[] = $disease;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo le terapie
	
        $answer = $resp;
        $num = 0;

		if (count($diseasesArray) != 0) {
			foreach ($diseasesArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "There are not diseases diagnosticated to you";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your diseases &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters numero della patologia di cui si desidera 
avere maggiori dettagli
il metodo analizza il parametro e prende dal file json n-esimo
elemento delle patologie
*/
function getDiseaseDetails($parameters, $email){


    $param = "";
    $json_data = queryMyrror($param,$email);
    $numDiseases = 0;


	foreach ($json_data as $key2 => $value2) {
    
		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "diseases"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your diseases &#x1F613; Please try again later or check if your diseases are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        ++$numDiseases;
                        if($numDiseases == $parameters['number']){

                            $nameDisease = $value['nameDisease'];
                            $dateDiagnosis = $value['dateDiagnosis'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $placeDiagnosis = $value['placeDiagnosis'];
                            $completeDiagnosis = $value['completeDiagnosis'];
                            $note = $value['note'];

                            $answer = "The disease " . $nameDisease;
        
                            if($dateDiagnosis != NULL){
                                $answer = $answer . " has been diagnosticated to you in date " . $dateDiagnosis;
                            }
                            if($nameDoctor!= NULL || $surnameDoctor!= NULL){
                                $answer = $answer . " by the doctor " . $surnameDoctor . " " . $nameDoctor;
                            }
                            if ($placeDiagnosis != NULL){
                                $answer = $answer . " at " . $placeDiagnosis;
                            }
                            if ($completeDiagnosis != NULL){
                                $answer = $answer . ". The complete diagnosis is " . $completeDiagnosis . ". ";
                            }
                            if ($note!= NULL){
                                $answer = $answer . "NOTE: " . $note;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    if($parameters['number'] > $numDiseases){
        $answer = "There is not a disease with the specified number";
    }

    return $answer;
    

}


/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco indicizzato di tutte le ospedalizzazioni
*/
function getHospitalizations($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$hospitalizationsArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "hospitalizations"){
                    foreach($value1 as $key => $value){
                        if (isset($value['name'])) {//Verifico se è valorizzata la variabile 'name'

                            $hospitalization = $value['name']; //Prendo il nome dei ricoveri
                            
                            $hospitalizationsArray[] = $hospitalization;
                        }
                    }
                }
				
			}
        }	
    }


    //Se è valorizzato l'array, stampo le ospedalizzazioni
	if (isset($hospitalizationsArray)) {
        $answer = $resp;
        $num = 0;

		if (count($hospitalizationsArray) != 0) {
			foreach ($hospitalizationsArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Type \"Hospitalization\" with the correspondant number for more details (example:Hospitalization 1)";

		}else {
			$answer = "Unfortunately I was unable to retrieve your hospitalizations &#x1F613; Please try again later or check if your hospitalizations are in your profile!";
		}

	}else{
		$answer = "Unfortunately I was unable to retrieve your hospitalizations &#x1F613; Please try again later or check if your hospitalizations are in your profile!";
	}

    

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to retrieve your hospitalizations &#x1F613; Please try again later";
	}

	return $answer;

}



/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
il metodo analizza i parameters start_date e end_date
se la start_date dell'ospedalizzazione è compresa in questo periodo 
viene inserita nell'array che sarà stampato
*/
function getHospitalizationsPeriod($resp,$parameters,$email){

	$param = "";
	$json_data = queryMyrror($param,$email);

	$hospitalizationsArray = array();

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "hospitalizations"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your hospitalizations &#x1F613; Please try again later or check if your hospitalizations are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['name'])) {//Verifico se è valorizzata la variabile 'name'

                            
                            $data = strtotime($value['start_date']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);
                            
                            if($data <= $endDate && $data >= $startDate) { //se la data è inclusa nell'intervallo di tempo
                            $hospitalization = $value['name']; //Prendo il nome dei ricoveri
                            $hospitalizationsArray[] = $hospitalization;
                            }
                        }
                    }
                }
				
			}
        }	
    }

    //Se è valorizzato l'array, stampo i ricoveri
	
        $answer = $resp;
        $num = 0;

		if (count($hospitalizationsArray) != 0) {
			foreach ($hospitalizationsArray as $key => $value){
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

		}else {
			$answer = "There are not hospitalizations in the specified period";
		}

	

	//A volte la richiesta non restituisce nessun elenco perciò dovrà essere rifatta
	if ($answer == null) {
		$answer = "I was unable to load your hospitalizations &#x1F613; Please try again later";
	}

	return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restituisce l'ospedalizzazione più recente
confrontandole tra loro 
*/
function getLastHospitalization($resp, $parameters, $email){

    $param = "";
	$json_data = queryMyrror($param,$email);

	$ultimo = 0;

	foreach ($json_data as $key2 => $value2) {

		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "hospitalizations"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your hospitalizations &#x1F613; Please try again later or check if your hospitalizations are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        if (isset($value['name'])) {//Verifico se è valorizzata la variabile 'name'
                            
                            $startDate=$value['timestamp']/1000;
                            if($startDate > $ultimo){
                                $ultimo = $startDate;
                                $lastHospitalization = $value['name'];
                            }
                        }
                    }

                        foreach($value1 as $key => $value){
                            if($value['name'] == $lastHospitalization){
                                $startDate = $value['start_date'];
                                $endDate = $value['end_date'];
                                $nameDoctor = $value['nameDoctor'];
                                $surnameDoctor = $value['surnameDoctor'];
                                $hospitalWard = $value['hospitalWard'];
                                $diagnosisHospitalization = $value['diagnosisHospitalization'];
                                $medicalPrescription = $value['medicalPrescription'];
                                $note = $value['note'];
                
                                $answer = $resp . " " . $lastHospitalization;
                
                                if(isset($startDate) && isset($endDate)){
                                    $answer = $answer . " from date " . $startDate . " to " . $endDate;
                                }
                                if(isset($nameDoctor) || isset($surnameDoctor)){
                                    $answer = $answer . " prescribed by the doctor " . $nameDoctor . " " . $surnameDoctor;
                                }
                                if(isset($hospitalWard)){
                                    $answer = $answer . " in the hospital ward " . $hospitalWard;
                                }
                                if(isset($diagnosisHospitalization)){
                                    $answer = $answer . " at the facility " . $diagnosisHospitalization . ". ";
                                }
                                if(isset($medicalPrescription)){
                                    $answer = $answer . "The doctor has prescribed to you  " . $medicalPrescription . ". ";
                                }
                                if(isset($note)){
                                    $answer = $answer . "NOTE  " . $note . ". ";
                                }
                            }
                        }
                }
			}
        }	
    }
    return $answer;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters numero dell'ospedalizzazione di cui si desidera 
avere maggiori dettagli
il metodo analizza il parametro e prende dal file json n-esimo
elemento delle ospedalizzazioni
*/
function getHospitalizationDetails($parameters, $email){


    $param = "";
    $json_data = queryMyrror($param,$email);
    $numHospitalizations = 0;


	foreach ($json_data as $key2 => $value2) {
    
		if($key2 == "physicalStates"){
			foreach ($value2 as $key1 => $value1) {

                if($key1 == "hospitalizations"){
                    if($value1 == null){
                        $answer = "Unfortunately I was unable to retrieve your hospitalizations &#x1F613; Please try again later or check if your hospitalizations are in your profile!";
                        return $answer;
                    }
                    foreach($value1 as $key => $value){
                        ++$numHospitalizations;
                        if($numHospitalizations == $parameters['number']){

                            $name = $value['name'];
                            $startDate = $value['start_date'];
                            $endDate = $value['end_date'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $hospitalWard = $value['hospitalWard'];
                            $diagnosisHospitalization = $value['diagnosisHospitalization'];
                            $medicalPrescription = $value['medicalPrescription'];
                            $note = $value['note'];
                
                            $answer = "The hospitalization " . $name;
                
                            if(isset($startDate) && isset($endDate)){
                                $answer = $answer . " lasted from " . $startDate . " to " . $endDate;
                            }
                            if(isset($nameDoctor) || isset($surnameDoctor)){
                                $answer = $answer . ", has been prescribed by the doctor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if(isset($hospitalWard)){
                                $answer = $answer . " in the hospital ward of " . $hospitalWard;
                            }
                            if(isset($diagnosisHospitalization)){
                                $answer = $answer . " at the facility " . $diagnosisHospitalization . ". ";
                            }
                            if(isset($medicalPrescription)){
                                $answer = $answer . "The doctor has prescribed to you  " . $medicalPrescription . ". ";
                            }
                            if(isset($note)){
                                $answer = $answer . "NOTE  " . $note . ". ";
                            }
                        break;
                        }
                    }
                }
            }
        }
    }

    if($parameters['number'] > $numHospitalizations){
        $answer = "There is not an hospitalization with the specified number";
    }

    return $answer;
    

}




