<?php

/*
il metodo serve a leggere il file json completo preso da
myrror e cercare al suo interno una data specificata,se la
data viene trovata nel file verrÃƒÂ  restituita insieme al valore
corrispondente di restingHeartRate; se non viene trovata la
data specificata verranno restituiti i dati dell'ultima data disponibile
@Parameters sono i parametri sui periodi temporali
individuati da dialogflow
@data ÃƒÂ¨ la data da cercare nel file
return data e battito cardiaco
*/
function cardioToday($parameters, $data, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;
    $dateR = null;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['heart'])) {

            foreach ($value1['heart'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $tempDate = date('Y-m-d', $timestamp / 1000);

                if ($tempDate == $data) {
                    $result = $value2;
                    $dateR = $tempDate;
                }
            }
        }

    }

    if (isset($result['restingHeartRate'])) {
        $heart = $result['restingHeartRate'];

    } else {

        foreach ($json_data as $key1 => $value1) {

            if (isset($value1['heart'])) {

                $max = -1;
                foreach ($value1['heart'] as $key2 => $value2) {

                    $timestamp = $value2['timestamp'];
                    $tempDate = date('Y-m-d', $timestamp / 1000);
                    if ($timestamp > $max) {

                        $result = $value2;
                        $max = $timestamp;
                        $dateR = $tempDate;

                    }
                }
            }

        }

        if (isset($result['restingHeartRate'])) {
            $heart = $result['restingHeartRate'];

        } else {
            $heart = 0;
        }

    }

    return array('date' => $dateR, 'heart' => $heart);

}

/*
@startDate data iniziale dell'intervallo
@endDate data finale dell'intervallo
Il seguente metodo ricerca all'interno del file json
restituito da myrror il dato restingHeartRate di tutte le
date presenti nell'intervallo specificato, viene fatta cosÃƒÂ¬
una media dei valori del battito cardiaco.
return media battito cardiaco al minuto
*/
function cardioInterval($startDate, $endDate, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $count = 0;
    $sum = 0;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['heart'])) {

            foreach ($value1['heart'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $tempDate = date('Y-m-d', $timestamp / 1000);
                if ($tempDate <= $endDate && $tempDate >= $startDate) {
                    $sum += $value2['restingHeartRate'];
                    $count++;
                }
            }
        }

    }

    if ($count != 0) {
        $average = $sum / $count;
    } else {
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
i dati del giorno o del periodo scelto la risposta verrÃƒÂ  costruita
utilizzando gli ultimi dati disponibli.
@return risposta da stampare a schermo

*/
function getCardio($resp, $parameters, $text, $email)
{

    $answer = "";
    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 days"));


    if (isset($parameters['date'])) {

        $date1 = substr($parameters['date'], 0, 10);

        if ($today == $date1) {

            //dati oggi
            $arr = cardioToday($parameters, $today, $email);

            if ($arr['date'] == $today) {

                /*
                la risposta di default ($resp) restituita da dialogflow ÃƒÂ¨
                costruita per la data di oggi, cosÃƒÂ¬ sostituiamo alla X presente
                in $resp il valore del battito cardiaco da stampare
                */
                $answer = str_replace('X', $arr['heart'], $resp);
            } else {

                //risposta standard
                $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
                    . ". Il battito cardiaco ÃƒÂ¨ di " . $arr['heart'] . " bpm";
            }

        } elseif ($yesterday == $date1) {

            //dati ieri
            $arr = cardioToday($parameters, $yesterday, $email);

            if ($arr['date'] == $yesterday) {
                $answer = "Il tuo battito cardiaco era di " . $arr['heart'] . " bpm"; //risposta oggi
            } else {

                //risposta standard
                $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
                    . ". Il battito cardiaco ÃƒÂ¨ di " . $arr['heart'] . " bpm";
            }

        } elseif (isset($parameters['date-period']['startDate'])) {

            //dati ultimo giorno trovato
            $startDate = substr($parameters['date-period']['startDate'], 0, 10);
            $endDate = substr($parameters['date-period']['endDate'], 0, 10);
            $average = cardioInterval($startDate, $endDate, $email);

            if ($average != 0) {
                $answer = "In media, il tuo battito cardiaco ÃƒÂ¨ di " . $average . " bpm.";
            } else {
                $arr = cardioToday($parameters, "", $email);
                $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
                    . " ed il battito cardiaco era pari a " . $arr['heart'] . " bpm";
            }

        } else {
            $arr = cardioToday($parameters, "", $email);
            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
                . " ed il battito cardiaco era pari a " . $arr['heart'] . " bpm";
        }

    } elseif (isset($parameters['date-period']['startDate'])) {

        //dati intervallo di tempo
        $startDate = substr($parameters['date-period']['startDate'], 0, 10);
        $endDate = substr($parameters['date-period']['endDate'], 0, 10);
        $average = cardioInterval($startDate, $endDate, $email);
        if ($average != 0) {
            $answer = "In media, il tuo battito cardiaco ÃƒÂ¨ di " . $average . " bpm.";
        } else {
            $arr = cardioToday($parameters, "", $email);
            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
                . " ed il battito cardiaco era pari a " . $arr['heart'] . " bpm";
        }

    } else {

        //dati ultimo giorno trovato
        $arr = cardioToday($parameters, "", $email);
        $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date']
            . " ed il battito cardiaco era pari a " . $arr['heart'] . " bpm";
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
dati nella data odierna verrÃƒÂ  costruita una risposta utilizzando gli
ultimi dati presenti nel file.
@return risposta da stampare a schermo
*/
function getCardioBinary($resp, $parameters, $text, $email)
{

    $answer = "";
    $today = date("Y-m-d");
    //$today = "2019-03-27";

    $yesterday = date("Y-m-d", strtotime("-1 days"));

    if (isset($parameters['date-period']['startDate'])) {

        $startDate = substr($parameters['date-period']['startDate'], 0, 10);
        $endDate = substr($parameters['date-period']['endDate'], 0, 10);
        $average = cardioInterval($startDate, $endDate, $email);

        if ($average == 0) {
            $answer = "Non sono riuscito a recuperare i dati relativi al periodo che mi hai indicato &#x1F62D;";
        } else {
            if (strpos($text, 'buono') || strpos($text, 'buone') || strpos($text, 'bene') || strpos($text, 'ottimo') || strpos($text, 'nella norma') || strpos($text, 'buona')) {

                if ($average >= 60 && $average <= 100) {
                    $answer = "Si, in media le tue pulsazioni sono nella norma. Infatti ho rilevato " . $average . " bpm";
                } else {
                    $answer = "No, in media le tue pulsazioni non sono nella norma. Infatti ho rilevato " . $average . " bpm";
                }

            } elseif (strpos($text, 'pessimo') || strpos($text, 'cattivo') || strpos($text, 'cattive') ||
                strpos($text, 'male ') || strpos($text, 'fuori norma')) {

                if ($average >= 60 && $average <= 100) {
                    $answer = "No, in media le tue pulsazioni sono nella norma. Infatti ho rilevato " . $average . " bpm";
                } else {
                    $answer = "Si, in media le tue pulsazioni non sono nella norma. Infatti ho rilevato " . $average . " bpm";
                }

            }
        }

    } elseif (isset($parameters['date'])) {

        $date1 = substr($parameters['date'], 0, 10);
        switch ($date1) {

            case $today:
                $arr = cardioToday($parameters, $today, $email);

                if (strpos($text, 'buono') || strpos($text, 'buone') || strpos($text, 'bene') || strpos($text, 'ottimo')
                    || strpos($text, 'nella norma') || strpos($text, 'buona')) {

                    if ($arr['date'] == $today) {

                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "Si, le tue pulsazioni sono nella norma. Infatti ho rilevato " . $arr['heart'] . " bpm";
                        else
                            $answer = "No, le tue pulsazioni non sono nella norma. Infatti ho rilevato " . $arr['heart'] . " bpm";
                    } else {

                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        } else {

                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        }

                    }

                } elseif (strpos($text, 'pessimo') || strpos($text, 'cattivo') || strpos($text, 'cattive') ||
                    strpos($text, 'male ') || strpos($text, 'fuori norma')) {

                    if ($arr['date'] == $today) {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "No, le tue pulsazioni sono nella norma. Infatti ho rilevato " . $arr['heart'] . " bpm";
                        else
                            $answer = "Si, le tue pulsazioni non sono nella norma. Infatti ho rilevato  " . $arr['heart'] . " bpm";
                    } else {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazioni erano nella norma, ovvero " . $arr['heart'] . " bpm";
                        } else {

                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        }

                    }

                }

                break;
            case $yesterday:

                $arr = cardioToday($parameters, $yesterday, $email);
                if (strpos($text, 'buono') || strpos($text, 'buone') || strpos($text, 'bene') || strpos($text, 'ottimo') || strpos($text, 'nella norma') || strpos($text, 'buona')) {

                    if ($arr['date'] == $yesterday) {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "Si, le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        else
                            $answer = "No, le tue pulsazioni non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                    } else {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        } else {

                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                        }

                    }

                } elseif (strpos($text, 'pessimo') || strpos($text, 'cattivo') || strpos($text, 'cattive') ||
                    strpos($text, 'male ') || strpos($text, 'fuori norma')) {

                    if ($arr['date'] == $yesterday) {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                            $answer = "No, le tue pulsazioni erano nella norma, infatti ho rilevato  " . $arr['heart'] . " bpm";
                        else
                            $answer = "Si, le tue pulsazioni non erano nella norma, infatti ho rilevato  " . $arr['heart'] . " bpm";
                    } else {
                        if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazioni erano nella norma, infatti ho rilevato  " . $arr['heart'] . " bpm";
                        } else {

                            $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                                ". Le tue pulsazione non erano nella norma, infatti ho rilevato  " . $arr['heart'] . " bpm";
                        }

                    }

                }

                break;
            default:

                //ultima data disponibile
                $arr = cardioToday($parameters, "", $email);
                if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                } else {

                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                }
                break;
        }

    } else {
        $arr = cardioToday($parameters, $today, $email);
        if (strpos($text, 'buono') || strpos($text, 'buone') || strpos($text, 'bene') || strpos($text, 'ottimo') || strpos($text, 'nella norma') || strpos($text, 'buona')) {

            if ($arr['date'] == $today) {
                if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                    $answer = "Si, le tue pulsazioni sono nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                else
                    $answer = "No, le tue pulsazioni non sono nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
            } else {
                if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                } else {

                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                }

            }

        } elseif (strpos($text, 'pessimo') || strpos($text, 'cattivo') || strpos($text, 'cattive') ||
            strpos($text, 'male ') || strpos($text, 'fuori norma')) {

            if ($arr['date'] == $today) {
                if ($arr['heart'] >= 60 && $arr['heart'] <= 100)
                    $answer = "No, le tue pulsazioni sono nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                else
                    $answer = "Si,le tue pulsazioni non sono nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
            } else {
                if ($arr['heart'] >= 60 && $arr['heart'] <= 100) {
                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazioni erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
                } else {

                    $answer = "Gli ultimi dati in mio possesso sono relativi al " . $arr['date'] .
                        ". Le tue pulsazione non erano nella norma, infatti ho rilevato " . $arr['heart'] . " bpm";
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
il metodo analizza i parameters se ÃƒÂ¨ presente la data di ieri
o di oggi chiama il metodo yestSleepBinary per ottenere i minuti di
sonno dell'ultima notte,altrimenti viene fatta una distinzione in base al
verbo riconosciuto da dialogflow, se i verbi sono al passato prossimo
viene chiamata la funzione yestSleepBinary altrimenti viene chiamata la
funzione pastSleepBinary che costruisce la risposta con i dati storici
return risposta da stampare
*/
function getSleepBinary($resp, $parameters, $text, $email)
{


    $yesterday = date("Y-m-d", strtotime("-1 days"));
    if (isset($parameters['date']) || isset($parameters['Passato'])) {
        $date1 = substr($parameters['date'], 0, 10);
        $date1 = substr($parameters['date'], 0, 10);

        if ($date1 >= $yesterday) {
//dati di ieri

            $answer = yestSleepBinary($resp, $parameters, $text, $yesterday, $email);


        } else if ($parameters['Passato']) {
            //dati di ieri

            $answer = yestSleepBinary($resp, $parameters, $text, $yesterday, $email);
            //$answer = yestSleepBinary($resp,$parameters,$text,'2019-02-22');

        } else {
            //dati storici
            $answer = pastSleepBinary($resp, $parameters, $text, $email);
        }

    } else {
        //dati storici
        $answer = pastSleepBinary($resp, $parameters, $text, $email);
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
function pastSleepBinary($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = "";

    $count = 0;
    $sumInBed = 0;
    $sumAsleep = 0;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['sleep'])) {

            //ricerca per periodo
            foreach ($value1['sleep'] as $key2 => $value2) {
                $sumInBed += $value2['timeInBed'];
                $sumAsleep += $value2['minutesAsleep'];
                $count++;
            }
        }

    }

    if ($count == 0) {
        //non ci sono riferimenti per quel periodo
        return "Non sono riuscito a recuperare i dati relativi al periodo che mi hai indicato &#x1F62D;";
    }
    $asleepAV = intval($sumAsleep / $count);
    $inBedAV = intval($sumInBed / $count);

    //Conversione minuti in ore e minuti
    if ($asleepAV < 1) {
        return "Non mi risulta che tu abbia dormito &#x1F631;";
    }
    $hours = floor($asleepAV / 60);
    $minutes = ($asleepAV % 60);

    if (strpos($text, 'abbastanza')) {

        if ($asleepAV >= 390) {
            if ($hours == 1) {
                $result = "Si, dormi abbastanza. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dormi abbastanza. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "No, non dormi abbastanza. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, non dormi abbastanza. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        }
    } elseif (strpos($text, 'tanto')) {

        if ($asleepAV >= 390) {
            if ($hours == 1) {
                $result = "Si, dormi tanto. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dormi tanto. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "No, non dormi tanto. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, non dormi tanto. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        }

    } elseif (strpos($text, 'bene')) {

        if ($asleepAV >= 390) {
            if ($hours == 1) {
                $result = "Si, dormi bene. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dormi bene. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "No, non dormi bene. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, non dormi bene. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        }
    } elseif (strpos($text, 'di meno')) {
        if ($asleepAV >= 480) {
            if ($hours == 1) {
                $result = "Si, dovresti dormire di meno. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dovresti dormire di meno. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "No, dormi abbastanza. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, dormi abbastanza. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        }
    } elseif (strpos($text, 'di piÃƒÂ¹')) {

        if ($asleepAV >= 390) {
            if ($hours == 1) {
                $result = "No, non dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, non dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "Si, dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        }

    } elseif (strpos($text, 'poco')) {

        if ($asleepAV >= 390) {
            if ($hours == 1) {
                $result = "No, dormi abbastanza. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "No, dormi abbastanza. In media dormi " . $hours . " ore e " . $minutes . " minuti";
            }
        } else {
            if ($hours == 1) {
                $result = "Si, dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            } else {
                $result = "Si, dovresti dormire di piÃƒÂ¹. In media dormi " . $hours . " ora e " . $minutes . " minuti";
            }
        }

    } else {
        if ($hours == 1) {
            $result = "In media dormi " . $hours . " ora e " . $minutes . " minuti";;
        } else {
            $result = "In media dormi " . $hours . " ore e " . $minutes . " minuti";;
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
come parametro , se non la trova verrÃƒÂ  presa l'ultima data disponibile ,
questa distinzione avviene tramite il flag.
Viene costruita una risposta in base ai token rilevati nella frase
 usando dei valori soglia (390 minuti di sonno) per
rispondere in maniera positiva o negativa.
return risposta da stampare
*/
function yestSleepBinary($resp, $parameters, $text, $data, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    //serve a capire se vengono presi i dati della data corretta oppure gli ultimi presenti nel file
    $flag = false;

    //cerco data di ieri
    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['sleep'])) {

            foreach ($value1['sleep'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $tempDate = date('Y-m-d', $timestamp / 1000);
                if ($data == $tempDate) {
                    $result = $value2;
                }
            }
        }
    }

    if ($result['minutesAsleep'] != null) {

        //risposta con data di ieri corretta
        $minutesAsleep = $result['minutesAsleep'];
        $timeinbed = $result['timeInBed'];
        $flag = true;

    } else {

        /*risposta standard con ultima data
        algoritmo ultima data*/
        foreach ($json_data as $key1 => $value1) {

            if (isset($value1['sleep'])) {
                $max = -1;

                foreach ($value1['sleep'] as $key2 => $value2) {
                    $timestamp = $value2['timestamp'];
                    if ($timestamp > $max) {
                        $result = $value2;
                        $max = $timestamp;
                    }
                }
            }
        }

        if (isset($timestamp)) {
            $data2 = date('d-m-Y', $timestamp / 1000);
        } else {
            return "Non sono riuscito a recuperare i dati relativi al tuo sonno &#x1F62D; Controlla se sono presenti nel tuo profilo!";
        }

        if ($result['minutesAsleep'] != null) {
            $data = $data2;
            $minutesAsleep = $result['minutesAsleep'];
            $timeinbed = $result['timeInBed'];

        } else {
            return "Non sono riuscito a recuperare i dati relativi al tuo sonno &#x1F62D; Controlla se sono presenti nel tuo profilo!";
        }
    }

    //Conversione minuti in ore e minuti
    if ($minutesAsleep < 1) {
        return "Non mi risulta che tu abbia dormito &#x1F631;";
    }
    $hours = floor($minutesAsleep / 60);
    $minutes = ($minutesAsleep % 60);


    if (strpos($text, 'abbastanza') || strpos($text, 'bene')) {

        if ($minutesAsleep >= 390) {

            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "Si, hai dormito abbastanza. Hai dormito per ben " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Si, hai dormito abbastanza. Hai dormito per ben " . $hours . " ore e " . $minutes . " minuti";
                }
            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " ed hai dormito abbastanza. Ovvero " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " ed hai dormito abbastanza. Ovvero " . $hours . " ore e " . $minutes . " minuti";
                }

            }

        } else {
            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "No, non hai dormito abbastanza. Hai dormito solo per " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "No, non hai dormito abbastanza. Hai dormito solo per " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e vedo che non hai dormito abbastanza. Infatti solo per  "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e vedo che non hai dormito abbastanza. Infatti solo per  "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        }
        /*
          }

          elseif( strpos($text, 'bene')){

              if($minutesAsleep >= 390 ){

               if($flag == true){
                  if ($hours == 1) {
                    $answer = "Si, hai dormito bene. Hai dormito ben ".$hours. " ora e " . $minutes . " minuti";
                  }else{
                    $answer = "Si, hai dormito bene. Hai dormito ben ".$hours. " ore e " . $minutes . " minuti";
                  }

               }else{
                if ($hours == 1) {
                    $answer ="Gli ultimi in mio possesso risalgono al ".$data." e noto che hai dormito bene ovvero per ben "
                  .$hours. " ora e " . $minutes . " minuti";
                }else{
                    $answer ="Gli ultimi in mio possesso risalgono al ".$data." e noto che hai dormito bene ovvero per ben "
                  .$hours. " ore e " . $minutes . " minuti";
                }
               }

            }else{
                if($flag == true){
                  if ($hours == 1) {
                    $answer = "No, non hai dormito bene. Hai dormito solo per ".$hours. " ora e " . $minutes . " minuti";
                  }else{
                    $answer = "No, non hai dormito bene. Hai dormito solo per ".$hours. " ore e " . $minutes . " minuti";
                  }

               }else{
                if ($hours == 1) {
                  $answer ="Gli ultimi in mio possesso risalgono al ".$data." e non hai dormito molto bene. Infatti hai dormito solo per "
                  .$hours. " ora e " . $minutes . " minuti";
                }else{
                  $answer ="Gli ultimi in mio possesso risalgono al ".$data." e non hai dormito molto bene. Infatti hai dormito solo per "
                  .$hours. " ore e " . $minutes . " minuti";
                }

               }

            }
        */
    } elseif (strpos($text, 'tanto')) {

        if ($minutesAsleep >= 390) {

            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "Si, hai dormito tanto. Hai dormito per ben " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Si, hai dormito tanto. Hai dormito per ben " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che hai dormito tanto. Ovvero per "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che hai dormito tanto. Ovvero per "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        } else {
            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "No, non hai dormito tanto. Hai dormito solo per " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "No, non hai dormito tanto. Hai dormito solo per " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non hai dormito tanto. Solo "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non hai dormito tanto. Solo "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        }

    } elseif (strpos($text, 'meno')) {

        if ($minutesAsleep >= 480) {

            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "Si, dovresti dormire di meno. Vedo che hai dormito per " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Si, dovresti dormire di meno. Vedo che hai dormito per " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono a " . $data . " e noto che dovresti dormire di meno. Hai dormito per "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono a " . $data . " e noto che dovresti dormire di meno. Hai dormito per "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        } else {
            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "No, non hai dormito abbastanza. Hai dormito solamente per " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "No, non hai dormito abbastanza. Hai dormito solamente per " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non hai dormito abbastanza. Solo "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non hai dormito abbastanza. Solo "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        }

    } elseif (strpos($text, 'di piÃƒÂ¹')) {

        if ($minutesAsleep >= 390) {

            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "No, non dovresti dormire di piÃƒÂ¹ perchÃƒÂ¨ hai dormito per " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "No, non dovresti dormire di piÃƒÂ¹ perchÃƒÂ¨ hai dormito per " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non dovresti dormire di piÃƒÂ¹ visto che hai dormito "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che non dovresti dormire di piÃƒÂ¹ visto che hai dormito "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        } else {
            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "Si, dovresti dormire di piÃƒÂ¹. Infatti hai dormito " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Si, dovresti dormire di piÃƒÂ¹. Infatti hai dormito " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che dovresti dormire di piÃƒÂ¹ visto che hai dormito solamente per "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che dovresti dormire di piÃƒÂ¹ visto che hai dormito solamente per "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        }

    } elseif (strpos($text, 'poco')) {

        if ($minutesAsleep >= 390) {

            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "No, hai dormito abbastanza. Infatti hai dormito " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "No, hai dormito abbastanza. Infatti hai dormito " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che hai dormito abbastanza ovvero "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che hai dormito abbastanza ovvero "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }

        } else {
            if ($flag == true) {
                if ($hours == 1) {
                    $answer = "Si, dovresti dormire di piÃƒÂ¹. Hai dormito " . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Si, dovresti dormire di piÃƒÂ¹. Hai dormito " . $hours . " ore e " . $minutes . " minuti";
                }

            } else {
                if ($hours == 1) {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che dovresti dormire di piÃƒÂ¹. Hai dormito solamente "
                        . $hours . " ora e " . $minutes . " minuti";
                } else {
                    $answer = "Gli ultimi in mio possesso risalgono al " . $data . " e noto che dovresti dormire di piÃƒÂ¹. Hai dormito solamente "
                        . $hours . " ore e " . $minutes . " minuti";
                }

            }
        }

    } else {

        //Conversione minuti in ore e minuti
        if ($minutesAsleep < 1) {
            return "Non mi risulta che tu abbia dormito &#x1F631;";
        }
        $hours = floor($minutesAsleep / 60);
        $minutes = ($minutesAsleep % 60);

        if ($hours == 1) {
            $answer = "Hai dormito " . $hours . " ora e " . $minutes . ' minuti';
        } else {
            $answer = "Hai dormito " . $hours . " ore e " . $minutes . ' minuti';
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
function fetchYesterdaySleep($resp, $data, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    //cerco data di ieri
    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['sleep'])) {

            foreach ($value1['sleep'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $tempDate = date('Y-m-d', $timestamp / 1000);
                if ($data == $tempDate)
                    $result = $value2;
            }
        }
    }

    if ($result['minutesAsleep'] != null) {
        //risposta con data di ieri corretta
        $minutesAsleep = $result['minutesAsleep'];
        $timeinbed = $result['timeInBed'];

        //Conversione minuti in ore e minuti
        $hoursSleep = floor($minutesAsleep / 60);
        $minutesSleep = ($minutesAsleep % 60);

        //Conversione minuti in ore e minuti
        $hoursBed = floor($timeinbed / 60);
        $minutesBed = ($timeinbed % 60);

        $answer = str_replace("X1", $hoursSleep, $answer);
        $answer = str_replace('X2', $minutesSleep, $answer);
        $answer = str_replace("Y1", $hoursBed, $answer);
        $answer = str_replace('Y2', $minutesBed, $answer);

        return $answer;

    } else {
        //risposta standard con ultima data
        //algoritmo ultima data
        foreach ($json_data as $key1 => $value1) {

            if (isset($value1['sleep'])) {
                $max = -1;
                foreach ($value1['sleep'] as $key2 => $value2) {

                    $timestamp = $value2['timestamp'];
                    if ($timestamp > $max) {

                        $result = $value2;
                        $max = $timestamp;

                    }
                }
            }
        }


        if (isset($timestamp)) {
            $data2 = date('d-m-Y', $timestamp / 1000);
        } else {
            return "Non sono riuscito a recuperare i dati relativi al tuo sonno &#x1F62D; Controlla se sono presenti nel tuo profilo!";
        }


        $answer = "Gli ultimi dati in mio possesso sono relativi al " . $data2 . "<br>";

        if ($result['minutesAsleep'] != null) {
            $answer .= $resp;

            $minutesAsleep = $result['minutesAsleep'];
            $timeinbed = $result['timeInBed'];

            //Conversione minuti in ore e minuti
            $hoursSleep = floor($minutesAsleep / 60);
            $minutesSleep = ($minutesAsleep % 60);

            //Conversione minuti in ore e minuti
            $hoursBed = floor($timeinbed / 60);
            $minutesBed = ($timeinbed % 60);

            $answer = str_replace("X1", $hoursSleep, $answer);
            $answer = str_replace('X2', $minutesSleep, $answer);
            $answer = str_replace("Y1", $hoursBed, $answer);
            $answer = str_replace('Y2', $minutesBed, $answer);

        } else {
            $answer = "Non sono riuscito a recuperare i dati relativi al periodo che mi hai indicato &#x1F62D;";
        }

        return $answer;

    }
}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
@text frase scritta dall'utente
il metodo analizza i parameters se ÃƒÂ¨ presente la data di ieri,di oggi
oppure ÃƒÂ¨ stato riconosciuto un verbo al passato prossimo nella frase,
 chiama quindi il metodo fetchYesterdaySleep per ottenere i minuti di
sonno dell'ultima notte,altrimenti viene chiamata la
funzione fetchPastSleep che costruisce la risposta con i dati storici
return risposta da stampare
*/
function getSleep($resp, $parameters, $text, $email)
{

    $yesterday = date("Y-m-d", strtotime("-1 days"));
    $timestamp = strtotime($yesterday);


    if (isset($parameters['date']) || isset($parameters['Passato']) || isset($parameters['date-period'])) {
        $date1 = substr($parameters['date'], 0, 10);

        //echo $yesterday;
        if ($date1 == $yesterday) {
            //dati di ieri
            $answer = fetchYesterdaySleep($resp, $yesterday, $email);
            //$answer = fetchYesterdaySleep($resp,'2019-02-22');
        } else if (isset($parameters['date-period']['endDate']) && isset($parameters['date-period']['startDate'])) {


            foreach ($parameters['date-period'] as $keyP => $valueP) {

                if ($keyP == 'endDate')
                    $endDate = substr($valueP, 0, 10);
                else
                    $startDate = substr($valueP, 0, 10);

            }

            $answer = fetchPastSleep($endDate, $startDate, $email);

        } else if (isset($parameters['Passato'])) {
            //dati di ieri

            $answer = fetchYesterdaySleep($resp, $yesterday, $email);

        } else {

            //dati storici
            $answer = fetchPastSleep("", "", $email);
        }

    } else {

        //dati storici
        $answer = fetchPastSleep("", "", $email);
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
function fetchPastSleep($endDate, $startDate, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = "";

    $count = 0;
    $sumInBed = 0;
    $sumAsleep = 0;
    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['sleep'])) {
            if ($endDate != "" && $startDate != "") {
                //ricerca per periodo

                foreach ($value1['sleep'] as $key2 => $value2) {
                    $timestamp = $value2['timestamp'];
                    $data = date('Y-m-d', $timestamp / 1000);

                    if ($data >= $startDate && $data <= $endDate) {
                        $sumInBed += $value2['timeInBed'];
                        $sumAsleep += $value2['minutesAsleep'];
                        $count++;
                    }
                }
                $result = "dal " . $startDate . " al " . $endDate;

            } else {


                foreach ($value1['sleep'] as $key2 => $value2) {

                    $sumInBed += $value2['timeInBed'];
                    $sumAsleep += $value2['minutesAsleep'];
                    $count++;

                }
            }
        }


    }

    if ($count == 0) {
        //non ci sono riferimenti per quel periodo
        return fetchPastSleep("", "", $email);
    }
    $asleepAV = intval($sumAsleep / $count);
    $inBedAV = intval($sumInBed / $count);

    //Conversione minuti in ore e minuti
    $hoursSleep = floor($asleepAV / 60);
    $minutesSleep = ($asleepAV % 60);

    //Conversione minuti in ore e minuti
    $hoursBed = floor($inBedAV / 60);
    $minutesBed = ($inBedAV % 60);

    $result .= " in media hai dormito " . $hoursSleep . " ore e " . $minutesSleep . " minuti, trascorrendo nel letto " . $hoursBed . " ore e " . $minutesSleep . " minuti";

    return $result;
}

/*
 @resp contiene la risposta ricevuta da DialogFlow
 @parameters contiene l'entità ricevuta da DialogFlow relativa alla stato di forma fisico
 Il metodo restituisce una risposta relativa alla stato di forma fisico dell'utente
*/

//FORMA FISICA
function getFormaFisica($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);

    $bmi = getBmi($json_data);

    if ($bmi != null) {

        switch ($parameters['FormaFisica']) {
            case 'normopeso':
                if (isNormopeso($bmi)) {
                    $answer = 'Si, sei normopeso';
                } else {
                    $answer = 'No, non sei normopeso';
                }
                break;
            case 'obeso':
            case'sovrappeso':
                if (isSovrappeso($bmi) == "sovrappeso") {
                    $answer = "Sei sovrappeso,dovresti scendere di peso";
                } else if (isSovrappeso($bmi) == "obeso classe 1") {
                    $answer = "Sei lievemente obeso, dovresti perdere qualche chilo e fare attivitÃƒÂ  fisica";
                } else if (isSovrappeso($bmi) == "obeso classe 2") {
                    $answer = "Sei moderatamente obeso, dovresti perdere qualche chilo e fare attivitÃƒÂ  fisica";
                } else if (isSovrappeso($bmi) == "obeso classe 3") {
                    $answer = "Sei in una condizione di obesità  severa, dovresti rivolgerti da uno specialista";
                } else {
                    $answer = "No, non sei sovrappeso";
                }
                break;
            case 'sottopeso':
                if (isSottopeso($bmi) == "sottopeso") {
                    $answer = "Sei sottopeso,dovresti perdere qualche chilo";
                } else if (isSottopeso($bmi) == "grave magrezza") {
                    $answer = "Sei una condizione di grave magreza, dovresti rivolgerti da uno specialista";
                } else {
                    $answer = "No, non sei sottopeso";
                }
                break;
            default:
                $answer = str_replace("X", $bmi, $resp);
        }
    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo Bmi &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;

}

/*@bmi è il valore del BMI dell'utente
  il seguente metodo restituisce true se l'utente è obeso, false altrimenti
*/
function isNormopeso($bmi)
{
    if ($bmi <= 24.99 && $bmi >= 18.50) {
        $value = true;
    } else {
        $value = false;
    }
    return $value;
}

/*@bmi è il valore del BMI dell'utente
  il seguente metodo restituisce true se l'utente è sovrappeso, false altrimenti
*/
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

/*@bmi è il valore del BMI dell'utente
  il seguente metodo restituisce true se l'utente è sottopeso, false altrimenti
*/
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
/*@json contiene le informazioni le relative l'utente provenienti da Myrror
  Il metodo trova e restituisce il valore del BMI dell'utente
*/
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

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco indicizzato contenente tutte le analisi
*/
function getAnalysis($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $analysisArray = array();


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                            $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                            $timestamp = $value['timestamp'];
                            $data = date('d-m-Y', $timestamp / 1000);
                            $string = $analysis . " " . $data;

                            $analysisArray[] = $string;
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le analisi
    if (isset($analysisArray)) {
        $answer = $resp;
        $num = 0;

        if (count($analysisArray) != 0) {
            foreach ($analysisArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Digita \"Analisi\" con il corrispondete numero per maggiori dettagli (esempio:Analisi 1)";

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue analisi &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'ultima analisi inserita in HAB
*/
function getLastAnalysis($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                            $startDate = $value['timestamp'] / 1000;
                            if ($startDate > $ultimo) {
                                $ultimo = $startDate;
                                $lastAnalysis = $value['analysisName'];
                            }
                        }
                    }

                    foreach ($value1 as $key => $value) {
                        if ($value['analysisName'] == $lastAnalysis) {

                            $analysisName = $value['analysisName'];
                            $min = $value['min'];
                            $max = $value['max'];
                            $unit = $value['unit'];
                            $result = $value['result'];

                            $answer = $resp . " " . $analysisName . ".<br>";

                            if (isset($min) && isset($max)) {
                                $answer = $answer . "Il risultato dovrebbe essere compreso tra " . $min . $unit . " e " . $max . $unit . ".<br>";
                            }
                            if (isset($result)) {
                                $answer = $answer . "Il risultato &#232 " . $result . $unit . ".";
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
function getLastAnalysisSpecified($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;

    if ($parameters['Analisi'] == null) {
        $answer = $resp;
        return $answer;
    }


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if ($value['analysisName'] == $parameters['Analisi']) {
                            if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                                $startDate = $value['timestamp'] / 1000;
                                if ($startDate > $ultimo) {
                                    $ultimo = $startDate;
                                    $lastAnalysis = $value['timestamp'] / 1000;
                                }
                            }
                        }
                    }


                    if (isset($lastAnalysis)) {
                        $answer = $resp . " " . date('d/m/Y', $lastAnalysis);
                    } else {
                        $answer = "Non hai mai effettuato quest'analisi";
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
function getAnalysisPeriod($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $analysisArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                            $timestamp = $value['timestamp'] / 1000;
                            $data = date("d-m-Y", $timestamp);
                            $string = $analysis . " " . $data;

                            if ($timestamp <= $endDate && $timestamp >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $analysisArray[] = $string;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le analisi

    $answer = $resp;
    $num = 0;

    if (count($analysisArray) != 0) {
        foreach ($analysisArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }


    } else {
        $answer = "Non ci sono analisi nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue analisi &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce un elenco con tutte le analisi da tenere
sotto controllo
*/
function getAnalysisControl($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $analysisArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($value['result'])) {//Verifico se Ã¨ valorizzata la variabile 'result'

                            $result = $value['result'];
                            $min = $value['min'];
                            $max = $value['max'];

                            if ($result >= $max || $result <= $min) {

                                $analysis = $value['analysisName']; //Prendo il nome dell'analisi
                                $timestamp = $value['timestamp'];
                                $data = date('d-m-Y', $timestamp / 1000);
                                $string = $analysis . " " . $data;

                                $analysisArray[] = $string;
                            }


                        }
                    }
                }
            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le analisi
    if (isset($analysisArray)) {
        $answer = $resp;

        if (count($analysisArray) != 0) {
            foreach ($analysisArray as $key => $value) {
                $answer = $answer . " " . $value . ", ";
            }

            //Rimuovo lo spazio con la virgola finale
            $answer = substr($answer, 0, -2);
            $answer = $answer . ".<br> I risultati sono fuori dall'intervallo.";


        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue analisi &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce una risposta che indica se il risultato dell'analisi
passata come parameters Ã¨ sotto, sopra o nella media
*/
function getAnalysisControlBinary($resp, $parameters, $email)
{


    $param = "";
    $json_data = queryMyrror($param, $email);
    $ultimo = 0;

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                            if ($parameters['Analisi'] == $value['analysisName']) {
                                $startDate = $value['timestamp'];
                                if ($startDate > $ultimo) {
                                    $ultimo = $startDate;
                                }
                            }
                        }
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($value['result'])) {//Verifico se Ã¨ valorizzata la variabile 'result'

                            if ($ultimo == 0) {
                                break;
                            }

                            if ($parameters['Analisi'] == $value['analysisName'] && $value['timestamp'] == $ultimo) {
                                $result = $value['result'];
                                $min = $value['min'];
                                $max = $value['max'];

                                if ($result <= $max && $result >= $min) {
                                    $answer = $resp . " nella media";
                                } else {
                                    if ($result > $max) {
                                        $answer = $resp . " sopra la media";
                                    } else {
                                        $answer = $resp . " sotto la media";
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
    }

    if ($parameters['Analisi'] == null) {
        $answer = $resp;
    }

    if (!(isset($answer))) {
        $answer = "Non hai mai effettuato quest'analisi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters l'Analisi di cui si vuole sapere il risultato ricevuta da dialogflow
il metodo restituisce il risultato dell'analisi passata come parameters
*/
function getAnalysisResult($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $ultimo = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {
                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                            if ($parameters['Analisi'] == $value['analysisName']) {
                                $startDate = $value['timestamp'];
                                if ($startDate > $ultimo) {
                                    $ultimo = $startDate;
                                }
                            }
                        }
                    }


                    foreach ($value1 as $key => $value) {
                        if ($value['analysisName'] == $parameters['Analisi'] && $value['timestamp'] == $ultimo) {//Verifico se il nome dell'analisi Ã¨ uguale a quello cercato

                            $result = $value['result']; //Prendo il risultato dell'analisi
                            break;

                        }
                    }
                }

            }
        }
    }

    if ($parameters['Analisi'] == null) {
        return $answer = $resp;
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if (!isset($result)) {
        $answer = "Non hai mai effettuato quest'analisi";
    } else {
        $answer = $resp . " " . $result;
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo
il metodo restituisce l'Analisi richiesta dall'utente indicata mediante l'indice
*/
function getAnalysisDetails($parameters, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $numAnalysis = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        ++$numAnalysis;
                        if ($numAnalysis == $parameters['number']) {

                            $answer = "Il risultato di " . $value['analysisName'] . " dovrebbe essere compreso tra " . $value['min'] . $value['unit'] . " e " . $value['max'] . $value['unit'] . ". Il tuo risultato &#232 " . $value['result'] . $value['unit'];

                        }
                    }
                }
            }
        }
    }

    if ($parameters['number'] > $numAnalysis) {
        $answer = "Non c'&#232 un'analisi con questo numero";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow su cui effettuare il controllo di presenza
il metodo restituisce si o no se l'analisi Ã¨ stata effettuata o meno dall'utente
*/
function getAnalysisBinary($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if ($value['analysisName'] == $parameters['Analisi']) {//Verifico se il nome dell'analisi Ã¨ uguale a quello cercato
                            $answer = "Si, hai effettuato quest'analisi.";
                        }
                    }
                }
            }
        }
    }

    if ($parameters['Analisi'] == null) {
        $answer = $resp;
    }

    if (!(isset($answer))) {
        $answer = "Non hai mai effettuato quest'analisi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters Analisi ricevuta da dialogflow di cui si vuole il risultato
il metodo restituisce l'andamento dell'analisi
*/
function getAnalysisTrend($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $found = 0;

    $resultsArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {
                if ($key1 == "analysis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {
                        if (isset($parameters['Analisi'])) {
                            if (isset($value['analysisName'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'


                                if ($parameters['Analisi'] == $value['analysisName']) {
                                    $data = date('d-m-Y', ($value['timestamp'] / 1000));
                                    $resultsArray[] = $value['result'] . $value['unit'] . " " . $data;
                                    $found = 1;
                                }
                            }
                        }
                    }

                }

            }
        }
    }


    if ($parameters['Analisi'] == null) {
        return $answer = $resp;
    }
    if ($found = 0) {
        $answer = "Non Ã¨ presente " . $parameters['Analisi'] . " tra le tue analisi";
        return $answer;
    }

    //Se Ã¨ valorizzato l'array, stampo i risultati
    if (isset($resultsArray)) {
        $answer = $resp;

        if (count($resultsArray) != 0) {
            foreach ($resultsArray as $key => $value) {
                $answer = $answer . "<br>" . $value;
            }

        } else {
            $answer = "Non &#232 presente " . $parameters['Analisi'] . " tra le tue analisi";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue analisi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
    }
    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'elenco delle diagnosi effettuate
*/
function getDiagnosis($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $diagnosisArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diagnosis") {
                    foreach ($value1 as $key => $value) {
                        if (isset($value['diagnosis_name'])) {//Verifico se Ã¨ valorizzata la variabile 'diagnosis_name'

                            $diagnosis = $value['diagnosis_name']; //Prendo il nome delle diagnosi

                            $diagnosisArray[] = $diagnosis;
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le diagnosi
    if (isset($diagnosisArray)) {
        $answer = $resp;
        $num = 0;

        if (count($diagnosisArray) != 0) {
            foreach ($diagnosisArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue diagnosi!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue diagnosi!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco indicizzato contenente tutte le diagnosi
*/
function getDiagnosisPeriod($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $diagnosisArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diagnosis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['diagnosis_name'])) {//Verifico se Ã¨ valorizzata la variabile 'diagnosis_name'

                            $timestamp = $value['timestamp'];
                            $data = substr($timestamp, 0, 10);

                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            if ($data <= $endDate && $data >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $diagnosis = $value['diagnosis_name']; //Prendo il nome della diagnosi
                                $diagnosisArray[] = $diagnosis;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le diagnosi

    $answer = $resp;
    $num = 0;

    if (count($diagnosisArray) != 0) {
        foreach ($diagnosisArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }

    } else {
        $answer = "Non ci sono diagnosi nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la diagnosi piÃ¹ recente
confrontando i relativi timestamp tra loro
*/
function getLastDiagnosy($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diagnosis") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue diagnosi &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['diagnosis_name'])) {//Verifico se Ã¨ valorizzata la variabile 'diagnosis_name'

                            $startDate = $value['timestamp'] / 1000;
                            if ($startDate > $ultimo) {
                                $ultimo = $startDate;
                                $lastDiagnosis = $value['diagnosis_name'];
                            }
                        }
                    }

                    foreach ($value1 as $key => $value) {
                        if ($value['diagnosis_name'] == $lastDiagnosis) {

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
function giorno($d)
{

    //attento la data deve essere nel formato yyyy-mm-gg
    //anche come separatori (se altri separatori devi modificare)
    $d_ex = explode("-", $d);//attento al separatore
    $d_ts = mktime(0, 0, 0, $d_ex[1], $d_ex[2], $d_ex[0]);
    $num_gg = (int)date("N", $d_ts);//1 (for Monday) through 7 (for Sunday)
    //per nomi in italiano
    $giorno = array('', 'lunedÃ¬', 'martedÃ¬', 'mercoledÃ¬', 'giovedÃ¬', 'venerdÃ¬', 'sabato', 'domenica');//0 vuoto
    return $giorno[$num_gg];
}


//restituisce il numero di giorni trascorsi tra due date
function delta_tempo($data_iniziale, $data_finale, $unita)
{

    switch ($unita) {
        case "m":
            $unita = 1 / 60;
            break;       //MINUTI
        case "h":
            $unita = 1;
            break;          //ORE
        case "g":
            $unita = 24;
            break;         //GIORNI
        case "a":
            $unita = 8760;
            break;         //ANNI
    }

    $differenza = (($data_finale - $data_iniziale) / 3600) / $unita;
    return $differenza;
}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco contenente tutte le terapie
*/
function getTherapies($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $therapiesArray = array();
    $answerDrug = $resp;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {

                    foreach ($value1 as $key => $value) {


                        if (isset($value['therapyName'])) {//Verifico se Ã¨ valorizzata la variabile 'therapiesName'

                            $therapy = $value['therapyName']; //Prendo il nome delle terapie
                            $therapiesArray[] = $therapy; //tutte le terapie

                        }


                    }

                    if (isset($parameters['date'])) {
                        return $answerDrug;
                    }


                }
            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le terapie
    if (isset($therapiesArray)) {
        $answer = $resp;
        $num = 0;

        if (count($therapiesArray) != 0) {
            foreach ($therapiesArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br> " . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Digita \"Terapia\" con il corrispondete numero per maggiori dettagli (esempio:Terapia 1)";

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue terapie!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue terapie!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue terapie &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce la terapia piÃ¹ recente
confrontando le relative date tra loro
*/
function getLastTherapy($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {


                        if (isset($value['therapyName'])) {//Verifico se Ã¨ valorizzata la variabile 'therapiesName'

                            $startDate = $value['timestamp'] / 1000;
                            if ($startDate > $ultimo) {
                                $ultimo = $startDate;
                                $lastTherapy = $value['therapyName'];;
                            }
                        }


                    }

                    $answer = $resp . " " . $lastTherapy . "<br>";

                    foreach ($value1 as $key => $value) {
                        if ($value['therapyName'] == $lastTherapy) {

                            $type = $value['type'];
                            $today = strtotime("now");
                            $endDate = strtotime($value['end_date']);


                            if ($value['end_date'] == null) {
                                $answer = $answer . "La terapia " . $value['therapyName'] . " e' iniziata il " . $value['start_date'];
                            } else if ($endDate > $today) {
                                $answer = $answer . "La terapia " . $value['therapyName'] . " e' iniziata il " . $value['start_date'] . " e finir il " . $value['end_date'];
                            } else {
                                $answer = $answer . "La terapia " . $value['therapyName'] . " e' iniziata il " . $value['start_date'] . " ed e' finita il " . $value['end_date'];
                            }

                            switch ($type) {

                                case "EVERY_DAY":
                                    $answer = $answer . " e prevede tutti i giorni " . $value['drug_name'] . " ";
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }
                                    break;


                                case "INTERVAL":

                                    $answer = $answer . " e prevede ogni " . $value['interval_days'] . " giorni " . $value['drug_name'] . " ";
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }
                                    break;

                                case "SOME_DAY":

                                    $answer = $answer . " e prevede il " . $value['day'] . " " . $value['drug_name'];
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }

                                    break;

                                case "ODD_DAY":
                                    $answer = $answer . " e prevede a giorni alterni " . $value['drug_name'];
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
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
function getDrugToday($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $therapiesArray = array();
    $answerDrug = $resp . "<br>";
    $numDrug = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }

                    foreach ($value1 as $key => $value) {

                        if (isset($parameters['date'])) {
                            $today = strtotime($parameters['date']);
                            $giornoToday = giorno(substr($parameters['date'], 0, 10));
                            $startDate = strtotime($value['start_date']);
                            $endDate = strtotime($value['end_date']);


                            if (($value['end_date'] == null) || ($endDate > $today)) {


                                $type = $value['type'];
                                $drugName = $value['drug_name']; //Prendo il nome del farmaco

                                switch ($type) {

                                    case "EVERY_DAY":
                                        $answerDrug = $answerDrug . "-" . $drugName;
                                        if (isset($value['dosage'])) {
                                            $answerDrug = $answerDrug . " " . $value['dosage'];
                                        }
                                        if (isset($value['hour'])) {
                                            $answerDrug = $answerDrug . " alle ore " . $value['hour'];
                                        }
                                        $answerDrug = $answerDrug . "<br>";
                                        ++$numDrug;
                                        break;


                                    case "INTERVAL":
                                        $intervalDays = $value['interval_days'];
                                        $giorniDaStartDate = delta_tempo($startDate, $today, "g");
                                        if ((int)($giorniDaStartDate % $intervalDays) == 0) {

                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if (isset($value['dosage'])) {
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if (isset($value['hour'])) {
                                                $answerDrug = $answerDrug . " alle ore " . $value['hour'];
                                            }
                                            $answerDrug = $answerDrug . "<br>";
                                            ++$numDrug;
                                        }

                                        break;

                                    case "SOME_DAY":
                                        if ($value['day'] == $giornoToday) {
                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if (isset($value['dosage'])) {
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if (isset($value['hour'])) {
                                                $answerDrug = $answerDrug . " alle ore " . $value['hour'];
                                            }
                                            $answerDrug = $answerDrug . "<br>";
                                            ++$numDrug;
                                        }
                                        break;

                                    case "ODD_DAY":
                                        $giorniDaStartDate = delta_tempo($startDate, $today, "g");
                                        $resto = (int)($giorniDaStartDate % 2);
                                        if ($resto == 0) {
                                            $answerDrug = $answerDrug . "-" . $drugName;
                                            if (isset($value['dosage'])) {
                                                $answerDrug = $answerDrug . " " . $value['dosage'];
                                            }
                                            if (isset($value['hour'])) {
                                                $answerDrug = $answerDrug . " alle ore " . $value['hour'];
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
    if ($numDrug != 0) {
        //Rimuovo lo spazio con la virgola finale
        $answerDrug = substr($answerDrug, 0, -2);
    } else {
        $answerDrug = "Oggi non devi prendere nessuna terapia";
    }

    return $answerDrug;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco contenente tutte le terapie nel periodo specificato
*/
function getTherapiesPeriod($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $therapiesArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['therapyName'])) {//Verifico se Ã¨ valorizzata la variabile 'therapyName'


                            $data = strtotime($value['start_date']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            if ($data <= $endDate && $data >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $therapies = $value['therapyName']; //Prendo il nome della terapia
                                $therapiesArray[] = $therapies;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le terapie

    $answer = $resp;
    $num = 0;

    if (count($therapiesArray) != 0) {
        foreach ($therapiesArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }

    } else {
        $answer = "Non ci sono terapie nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue terapie &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters startDate e endDate ricevute da dialogflow su cui effettuare la ricerca in base al periodo
il metodo restituisce un elenco di terapie distinguendole se in corso o concluse
*/
function getTherapiesInProgEnded($resp, $parameters, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);

    $therapiesInProgArray = array();
    $therapiesEndedArray = array();
    $today = strtotime("now"); //data odierna

    $question = 0; //0 se la domanda richiede le terapie in progress, 1 altrimenti terapie ended

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['therapyName'])) {//Verifico se Ã¨ valorizzata la variabile 'therapyName'

                            if (isset($parameters['Durata_terapia'])) {
                                $durata = $parameters['Durata_terapia'];
                                $endDate = strtotime($value['end_date']);
                                if ($durata == "concluso" || $durata == "conclusa" || $durata == "conclusi" || $durata == "concluse") {
                                    $question = 1;
                                    if (($value['end_date'] != null) && ($endDate < $today)) {
                                        $therapies = $value['therapyName']; //Prendo il nome della terapia
                                        $therapiesEndedArray[] = $therapies;
                                    }
                                } else {
                                    if (($value['end_date'] == null) || ($endDate > $today)) {
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

    //Se Ã¨ valorizzato l'array, stampo le terapie

    $answer = $resp;

    if ($question == 0) {

        if (count($therapiesInProgArray) != 0) {

            foreach ($therapiesInProgArray as $key => $value) {
                $answer = $answer . " " . $value . ", ";
            }

            //Rimuovo lo spazio con la virgola finale
            $answer = substr($answer, 0, -2);

        } else {
            $answer = "Non ci sono terapie in corso";

        }
    } else {

        if (count($therapiesEndedArray) != 0) {

            foreach ($therapiesEndedArray as $key => $value) {
                $answer = $answer . " " . $value . ", ";
            }

            //Rimuovo lo spazio con la virgola finale
            $answer = substr($answer, 0, -2);
        } else {
            $answer = "Non ci sono terapie concluse";

        }

    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue terapie &#x1F613; Riprova piÃ¹ tardi";
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
function getTherapyDetails($parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $numTherapies = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "therapies") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue terapie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        ++$numTherapies;
                        if ($numTherapies == $parameters['number']) {

                            $type = $value['type'];
                            $today = strtotime("now");
                            $endDate = strtotime($value['end_date']);


                            if ($value['end_date'] == null) {
                                $answer = "La terapia " . $value['therapyName'] . " &#232 iniziata il " . $value['start_date'];
                            } else if ($endDate > $today) {
                                $answer = "La terapia " . $value['therapyName'] . " &#232 iniziata il " . $value['start_date'] . " e finir&#224 il " . $value['end_date'];
                            } else {
                                $answer = "La terapia " . $value['therapyName'] . " &#232 iniziata il " . $value['start_date'] . " ed &#232 finita il " . $value['end_date'];
                            }

                            switch ($type) {

                                case "EVERY_DAY":
                                    $answer = $answer . " e prevede tutti i giorni " . $value['drug_name'] . " ";
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }
                                    break;


                                case "INTERVAL":

                                    $answer = $answer . " e prevede ogni " . $value['interval_days'] . " giorni " . $value['drug_name'] . " ";
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }
                                    break;

                                case "SOME_DAY":

                                    $answer = $answer . " e prevede il " . $value['day'] . " " . $value['drug_name'];
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
                                    }

                                    break;

                                case "ODD_DAY":
                                    $answer = $answer . " e prevede a giorni alterni " . $value['drug_name'];
                                    if (isset($value['dosage'])) {
                                        $answer = $answer . " " . $value['dosage'];
                                    }
                                    if (isset($value['hour'])) {
                                        $answer = $answer . " alle ore " . $value['hour'];
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

    if ($parameters['number'] > $numTherapies) {
        $answer = "Non c'&#232 una terapia con questo numero";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restiuisce un elenco di tutte le
aree mediche ricercate
*/
function getMedicalAreas($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $medicalAreasArray = array();


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalAreas") {
                    foreach ($value1 as $key => $value) {
                        if (isset($value['medicalArea'])) {//Verifico se Ã¨ valorizzata la variabile 'medicalArea'

                            $medicalArea = $value['medicalArea']; //Prendo il nome delle area medica

                            $medicalAreasArray[] = $medicalArea;
                        }
                    }
                }

            }
        }
    }


    //Se Ã¨ valorizzato l'array, stampo le aree mediche
    if (isset($medicalAreasArray)) {
        $answer = $resp;

        if (count($medicalAreasArray) != 0) {
            foreach ($medicalAreasArray as $key => $value) {
                $answer = $answer . " " . $value . ", ";
            }

            //Rimuovo lo spazio con la virgola finale
            $answer = substr($answer, 0, -2);
        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue aree mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue aree mediche!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue aree mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue aree mediche!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue aree mediche &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce l'ultima area medica ricercata
*/
function getLastMedicalArea($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalAreas") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue aree mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['medicalArea'])) {//Verifico se Ã¨ valorizzata la variabile 'medicalArea'

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
function getMedicalVisits($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $medicalVisitsArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalVisits") {
                    foreach ($value1 as $key => $value) {
                        if (isset($value['nameVisit'])) {//Verifico se Ã¨ valorizzata la variabile 'nameVisit'

                            $medicalVisit = $value['nameVisit']; //Prendo il nome delle visita medica

                            $medicalVisitsArray[] = $medicalVisit;
                        }
                    }
                }

            }
        }
    }


    //Se Ã¨ valorizzato l'array, stampo le visite mediche
    if (isset($medicalVisitsArray)) {
        $answer = $resp;
        $num = 0;

        if (count($medicalVisitsArray) != 0) {
            foreach ($medicalVisitsArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Digita \"Visita medica\" con il corrispondete numero per maggiori dettagli (esempio:Visita medica 1)";

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue visite mediche!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue visite mediche!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restituisce la visita medica piÃ¹ recente
confrontando le relative date tra loro
*/
function getLastMedicalVisit($resp, $parameters, $email)
{


    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalVisits") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['nameVisit'])) {//Verifico se Ã¨ valorizzata la variabile 'nameVisit'

                            $startDate = $value['timestamp'] / 1000;
                            if ($startDate > $ultimo) {
                                $ultimo = $startDate;
                                $lastMedicalVisit = $value['nameVisit'];
                            }
                        }
                    }


                    foreach ($value1 as $key => $value) {
                        if ($value['nameVisit'] == $lastMedicalVisit) {
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

                            if (isset($typology)) {
                                $answer = $answer . " (" . $typology . ")";
                            }
                            if (isset($startDate)) {
                                $answer = $answer . " in data " . $dateVisit;
                            }
                            if (isset($nameDoctor) || isset($surnameDoctor)) {
                                $answer = $answer . " eseguita dal dottor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if (isset($nameFacility)) {
                                $answer = $answer . " presso la struttura" . $nameFacility;
                            }
                            if (isset($cityFacility)) {
                                $answer = $answer . " della citt&#224 di " . $cityFacility;
                            }
                            if (isset($descriptionFacility)) {
                                $answer = $answer . "(" . $descriptionFacility . ")";
                            }
                            if (isset($diagnosis)) {
                                $answer = $answer . ". La diagnosi &#232 stata " . $diagnosis;
                            }
                            if (isset($medicalPrescription)) {
                                $answer = $answer . ". Il dottore ti ha prescritto " . $medicalPrescription;
                            }
                            if (isset($note)) {
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
function getLastMedicalVisitSpecified($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;

    if ($parameters['TipologiaVisitaMedica'] == null) {
        $answer = $resp;
        return $answer;
    }

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalVisits") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if ($value['typology'] == $parameters['TipologiaVisitaMedica']) {
                            if (isset($value['nameVisit'])) {//Verifico se Ã¨ valorizzata la variabile 'analysisName'

                                $dateVisit = strtotime($value['dateVisit']);
                                if ($dateVisit > $ultimo) {
                                    $ultimo = $dateVisit;
                                    $lastMedicalVisit = $value['dateVisit'];
                                }
                            }
                        }
                    }


                    if (isset($lastMedicalVisit)) {
                        $answer = $resp . " " . $lastMedicalVisit;
                    } else {
                        $answer = "Non hai mai fatto questa visita medica";
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
se la data della visita medica Ã¨ compresa in questo periodo
viene inserita nell'array che sarÃ  stampato
*/
function getMedicalVisitsPeriod($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $medicalVisitsArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalVisits") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['nameVisit'])) {//Verifico se Ã¨ valorizzata la variabile 'nameVisit'


                            $data = strtotime($value['dateVisit']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            if ($data <= $endDate && $data >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $medicalVisit = $value['nameVisit']; //Prendo il nome della visita medica
                                $medicalVisitsArray[] = $medicalVisit;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le visite mediche

    $answer = $resp;
    $num = 0;

    if (count($medicalVisitsArray) != 0) {
        foreach ($medicalVisitsArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }

    } else {
        $answer = "Non ci sono visite mediche nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi";
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
function getMedicalVisitDetails($parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $numMedicalVisits = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "medicalVisits") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue visite mediche &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        ++$numMedicalVisits;
                        if ($numMedicalVisits == $parameters['number']) {

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

                            $answer = "La visita medica " . $nameVisit;

                            if (isset($typology)) {
                                $answer = $answer . " (" . $typology . ")";
                            }
                            if (isset($startDate)) {
                                $answer = $answer . " &#232 stata effettuata in data " . $dateVisit;
                            }
                            if (isset($nameDoctor) || isset($surnameDoctor)) {
                                $answer = $answer . " eseguita dal dottor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if (isset($nameFacility)) {
                                $answer = $answer . " presso la struttura " . $nameFacility;
                            }
                            if (isset($cityFacility)) {
                                $answer = $answer . " della citt&#224 di " . $cityFacility;
                            }
                            if (isset($descriptionFacility)) {
                                $answer = $answer . "(" . $descriptionFacility . ")";
                            }
                            if (isset($diagnosis)) {
                                $answer = $answer . ". La diagnosi &#232 stata " . $diagnosis;
                            }
                            if (isset($medicalPrescription)) {
                                $answer = $answer . ". Il dottore ti ha prescritto " . $medicalPrescription;
                            }
                            if (isset($note)) {
                                $answer = $answer . ". NOTE: " . $note;
                            }

                            break;
                        }
                    }
                }
            }
        }
    }

    if ($parameters['number'] > $numMedicalVisits) {
        $answer = "Non c'&#232 una visita medica con questo numero";
    }

    return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restiuisce un elenco indicizzato di tutte le patologie
*/
function getDiseases($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $diseasesArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diseases") {
                    foreach ($value1 as $key => $value) {

                        if (isset($parameters['Patologia'])) {
                            if ($value['nameDisease'] == $parameters['Patologia']) {
                                $dateDiagnosis = $value['dateDiagnosis'];
                                $nameDoctor = $value['nameDoctor'];
                                $surnameDoctor = $value['surnameDoctor'];
                                $placeDiagnosis = $value['placeDiagnosis'];
                                $completeDiagnosis = $value['completeDiagnosis'];
                                $note = $value['note'];

                                $answer = $resp;

                                if ($dateDiagnosis != NULL) {
                                    $answer = $answer . " in data " . $dateDiagnosis;
                                }


                                if ($nameDoctor != NULL || $surnameDoctor != NULL) {
                                    $answer = $answer . " dal dottor " . $surnameDoctor . " " . $nameDoctor;
                                }
                                if ($placeDiagnosis != NULL) {
                                    $answer = $answer . " presso " . $placeDiagnosis;
                                }
                                if ($completeDiagnosis != NULL) {
                                    $answer = $answer . ". La diagnosi completa &#232 " . $completeDiagnosis . ". ";
                                }
                                if ($note != NULL) {
                                    $answer = $answer . "NOTE: " . $note;
                                }
                                return $answer;
                            }


                        }

                        if (isset($value['nameDisease'])) {//Verifico se Ã¨ valorizzata la variabile 'nameDisease'

                            $disease = $value['nameDisease']; //Prendo il nome delle patologia

                            $diseasesArray[] = $disease;
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le patologie
    if (isset($diseasesArray)) {
        $answer = $resp;
        $num = 0;

        if (count($diseasesArray) != 0) {
            foreach ($diseasesArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Digita \"Patologia\" con il corrispondete numero per maggiori dettagli (esempio:Patologia 1)";

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue patologie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue patologie!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue patologie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue patologie!";
    }

    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue patologie &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@parameters parametri contenenti il nome di una patologia
il metodo analizza il parametro e ricerca il nome della
patologia nell'elenco
restituisce una risposta binaria
*/
function getDiseasesBinary($parameters, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $answer = NULL;

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diseases") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue patologie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {

                        if (isset($parameters['Patologia'])) {

                            if ($value['nameDisease'] == $parameters['Patologia']) {
                                $answer = "Si. Ti &#232 stata diagnosticata in data " . $value['dateDiagnosis'] . ".";
                            }
                        }
                    }
                }
            }
        }
    }
    if ($answer == NULL) {
        $answer = "No, non ti &#232 mai stata diagnosticata.";
    }
    return $answer;
}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
il metodo analizza i parameters start_date e end_date
se la data della patologia Ã¨ compresa in questo periodo
viene inserita nell'array che sarÃ  stampato
*/
function getDiseasesPeriod($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $diseasesArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diseases") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue patologie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['nameDisease'])) {//Verifico se Ã¨ valorizzata la variabile 'nameDisease'


                            $data = strtotime($value['dateDiagnosis']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            if ($data <= $endDate && $data >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $disease = $value['nameDisease']; //Prendo il nome della patologia
                                $diseasesArray[] = $disease;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo le terapie

    $answer = $resp;
    $num = 0;

    if (count($diseasesArray) != 0) {
        foreach ($diseasesArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }

    } else {
        $answer = "Non ci sono patologie nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue patologie &#x1F613; Riprova piÃ¹ tardi";
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
function getDiseaseDetails($parameters, $email)
{


    $param = "";
    $json_data = queryMyrror($param, $email);
    $numDiseases = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "diseases") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue patologie &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        ++$numDiseases;
                        if ($numDiseases == $parameters['number']) {

                            $nameDisease = $value['nameDisease'];
                            $dateDiagnosis = $value['dateDiagnosis'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $placeDiagnosis = $value['placeDiagnosis'];
                            $completeDiagnosis = $value['completeDiagnosis'];
                            $note = $value['note'];

                            $answer = "La patologia " . $nameDisease;

                            if ($dateDiagnosis != NULL) {
                                $answer = $answer . " &#232 stata diagnosticata in data " . $dateDiagnosis;
                            }
                            if ($nameDoctor != NULL || $surnameDoctor != NULL) {
                                $answer = $answer . " dal dottor " . $surnameDoctor . " " . $nameDoctor;
                            }
                            if ($placeDiagnosis != NULL) {
                                $answer = $answer . " presso " . $placeDiagnosis;
                            }
                            if ($completeDiagnosis != NULL) {
                                $answer = $answer . ". La diagnosi completa &#232 " . $completeDiagnosis . ". ";
                            }
                            if ($note != NULL) {
                                $answer = $answer . "NOTE: " . $note;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    if ($parameters['number'] > $numDiseases) {
        $answer = "Non c'&#232 una patologia con questo numero";
    }

    return $answer;


}


/*
@resp frase di risposta standard ricevuta da dialogflow
il metodo restituisce un elenco indicizzato di tutte le ospedalizzazioni
*/
function getHospitalizations($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $hospitalizationsArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "hospitalizations") {
                    foreach ($value1 as $key => $value) {
                        if (isset($value['name'])) {//Verifico se Ã¨ valorizzata la variabile 'name'

                            $hospitalization = $value['name']; //Prendo il nome dei ricoveri

                            $hospitalizationsArray[] = $hospitalization;
                        }
                    }
                }

            }
        }
    }


    //Se Ã¨ valorizzato l'array, stampo le ospedalizzazioni
    if (isset($hospitalizationsArray)) {
        $answer = $resp;
        $num = 0;

        if (count($hospitalizationsArray) != 0) {
            foreach ($hospitalizationsArray as $key => $value) {
                ++$num;
                $answer = $answer . "<br>" . $num . ". " . $value;
            }
            $answer = $answer . "<br><br>Digita \"Ospedalizzazione\" con il corrispondete numero per maggiori dettagli (esempio:Ospedalizzazione 1)";

        } else {
            $answer = "Purtroppo non sono riuscito a recuperare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti i tuoi ricoveri!";
        }

    } else {
        $answer = "Purtroppo non sono riuscito a recuperare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti i tuoi ricoveri!";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}


/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters parametri contenenti le info sui periodi di tempo
nella frase rilevati da dialogflow
il metodo analizza i parameters start_date e end_date
se la start_date dell'ospedalizzazione Ã¨ compresa in questo periodo
viene inserita nell'array che sarÃ  stampato
*/
function getHospitalizationsPeriod($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $hospitalizationsArray = array();

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "hospitalizations") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['name'])) {//Verifico se Ã¨ valorizzata la variabile 'name'


                            $data = strtotime($value['start_date']);
                            $startDate = strtotime($parameters['date-period']['startDate']);
                            $endDate = strtotime($parameters['date-period']['endDate']);

                            if ($data <= $endDate && $data >= $startDate) { //se la data Ã¨ inclusa nell'intervallo di tempo
                                $hospitalization = $value['name']; //Prendo il nome dei ricoveri
                                $hospitalizationsArray[] = $hospitalization;
                            }
                        }
                    }
                }

            }
        }
    }

    //Se Ã¨ valorizzato l'array, stampo i ricoveri

    $answer = $resp;
    $num = 0;

    if (count($hospitalizationsArray) != 0) {
        foreach ($hospitalizationsArray as $key => $value) {
            ++$num;
            $answer = $answer . "<br>" . $num . ". " . $value;
        }

    } else {
        $answer = "Non ci sono ospedalizzazioni nel periodo specificato.";
    }


    //A volte la richiesta non restituisce nessun elenco perciÃ² dovrÃ  essere rifatta
    if ($answer == null) {
        $answer = "Non sono riuscito a caricare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi";
    }

    return $answer;

}

/*
@resp frase di risposta standard ricevuta da dialogflow
@parameters null
il metodo restituisce l'ospedalizzazione piÃ¹ recente
confrontandole tra loro
*/
function getLastHospitalization($resp, $parameters, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $ultimo = 0;

    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "hospitalizations") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        if (isset($value['name'])) {//Verifico se Ã¨ valorizzata la variabile 'name'

                            $startDate = $value['timestamp'] / 1000;
                            if ($startDate > $ultimo) {
                                $ultimo = $startDate;
                                $lastHospitalization = $value['name'];
                            }
                        }
                    }

                    foreach ($value1 as $key => $value) {
                        if ($value['name'] == $lastHospitalization) {
                            $startDate = $value['start_date'];
                            $endDate = $value['end_date'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $hospitalWard = $value['hospitalWard'];
                            $diagnosisHospitalization = $value['diagnosisHospitalization'];
                            $medicalPrescription = $value['medicalPrescription'];
                            $note = $value['note'];

                            $answer = $resp . " " . $lastHospitalization;

                            if (isset($startDate) && isset($endDate)) {
                                $answer = $answer . " dal " . $startDate . " al " . $endDate;
                            }
                            if (isset($nameDoctor) || isset($surnameDoctor)) {
                                $answer = $answer . " prescritto dal dottor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if (isset($hospitalWard)) {
                                $answer = $answer . " nel reparto di " . $hospitalWard;
                            }
                            if (isset($diagnosisHospitalization)) {
                                $answer = $answer . " presso la struttura " . $diagnosisHospitalization . ". ";
                            }
                            if (isset($medicalPrescription)) {
                                $answer = $answer . "Il medico ti ha prescritto  " . $medicalPrescription . ". ";
                            }
                            if (isset($note)) {
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
function getHospitalizationDetails($parameters, $email)
{


    $param = "";
    $json_data = queryMyrror($param, $email);
    $numHospitalizations = 0;


    foreach ($json_data as $key2 => $value2) {

        if ($key2 == "physicalStates") {
            foreach ($value2 as $key1 => $value1) {

                if ($key1 == "hospitalizations") {
                    if ($value1 == null) {
                        $answer = "Purtroppo non sono riuscito a recuperare le tue ospedalizzazioni &#x1F613; Riprova piÃ¹ tardi oppure controlla se nel tuo profilo sono presenti le tue analisi!";
                        return $answer;
                    }
                    foreach ($value1 as $key => $value) {
                        ++$numHospitalizations;
                        if ($numHospitalizations == $parameters['number']) {

                            $name = $value['name'];
                            $startDate = $value['start_date'];
                            $endDate = $value['end_date'];
                            $nameDoctor = $value['nameDoctor'];
                            $surnameDoctor = $value['surnameDoctor'];
                            $hospitalWard = $value['hospitalWard'];
                            $diagnosisHospitalization = $value['diagnosisHospitalization'];
                            $medicalPrescription = $value['medicalPrescription'];
                            $note = $value['note'];

                            $answer = "L'ospedalizzazione " . $name;

                            if (isset($startDate) && isset($endDate)) {
                                $answer = $answer . " &#232 durato dal " . $startDate . " al " . $endDate;
                            }
                            if (isset($nameDoctor) || isset($surnameDoctor)) {
                                $answer = $answer . ", &#232 stato prescritto dal dottor " . $nameDoctor . " " . $surnameDoctor;
                            }
                            if (isset($hospitalWard)) {
                                $answer = $answer . " nel reparto di " . $hospitalWard;
                            }
                            if (isset($diagnosisHospitalization)) {
                                $answer = $answer . " presso la struttura " . $diagnosisHospitalization . ". ";
                            }
                            if (isset($medicalPrescription)) {
                                $answer = $answer . "Il medico ti ha prescritto  " . $medicalPrescription . ". ";
                            }
                            if (isset($note)) {
                                $answer = $answer . "NOTE  " . $note . ". ";
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    if ($parameters['number'] > $numHospitalizations) {
        $answer = "Non c'&#232 un'ospedalizzazione con questo numero";
    }

    return $answer;
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

        if (strpos($text, "riposare")) {
            $minutesAsleep = getMinutesAsSleep($json_data);
            if ($minutesAsleep != 0 && $minutesAsleep <= $oreDiSonno){
                $answer = "Si, dovresti riposare non hai dormito abbsastanza questa notte";
            }else{
                $answer = "Non ho dati relativi al tuo sonno, controlla il tuo profilo";
            }
        }else if(strpos($text,"addormentarmi")){
            $minutesToFallAsleep = getMinutesToFallAsleepFromMyrror($json_data);
            if ($minutesToFallAsleep != 0 ){
                $answer = "Hai impiegato ".$minutesToFallAsleep." minuti per addoemrntarti";
            }else{
                $answer = "Non ho dati relativi al tuo sonno, controlla il tuo profilo";
            }
        }else {
            $answer = str_replace("X", $oreDiSonno[0], $resp);
            $answer = str_replace("Y", $oreDiSonno[1], $answer);
        }
    } else {
        $answer = "Non sono in grado di darti questa informazione &#x1F62D;. Verifica che sia presente la tua data di nascita nel tuo account";
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
        $answer = "Dai miei dati non risulta che hai assunto proteine";
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
        $answer = "Dai miei dati non risulta che hai assunto carboidrati";
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
        $answer = "Dai miei dati non risulta che hai assunto carboidrati";
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
        $answer = "Dai miei dati non risulta che hai assunto carboidrati";
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
        $answer = "Controlla il tuo profilo oppure bevi perchÃ¨ dai miei dati non risulta che hai bevuto acqua";
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


                if ($timestamp >= $startDate && $timestamp <= $endDate) {
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
            $answer = "Dai dati a mia disposizione non risulta che hai fatto cardio in questo giorno, controlla il profilo o fai un po' di cardio che non fa mai male.";
        }
    } else if (isset($parameters['date-period'])) {
        $startDate = strtotime($parameters['date-period']['startDate']);
        $endDate = strtotime($parameters['date-period']['endDate']);
        $cardio = getCardioMinutesPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($cardio != 0) {
            $answer = "Nel periodo da te chiesto hai svolto minuti ".$cardio." di cardio";
        } else {
            $answer = "Dai dati a mia disposizione non risulta che hai fatto cardio nel periodo che mi hai indicato, controlla il profilo o fai un po' di cardio che non fa mai male.";
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

            if (strpos($text, "abbastanza")) {
                if ($acqua >= $sogliaAcqua) {
                    $answer = "Si, hai bevuto abbastanza";
                } else {
                    $answer = "No, non hai bevuto abbastanza dovresti bere di più";
                }
            } else if (strpos($text, "poco")) {
                if ($acqua < $sogliaAcqua) {
                    $answer = "Si, hai bevuto poco dovresti bere di più";
                } else {
                    $answer = "No, hai bevuto abbastanza";
                }
            }
        } else {
            $answer = "Non ho dati su questo giorno";
        }
    } else if (isset($parameters['date-period'])) {
        $sesso = getSessoFromMyrror($json_data);
        $eta = getEtaFromMyrror($json_data);
        $sogliaAcqua = getSogliaAcqua($eta, $sesso);
        $startDate = strtotime($parameters['date-period']['startDate']);
        $endDate = strtotime($parameters['date-period']['endDate']);
        $acqua = getAcquaPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($acqua != 0.0) {

            if (strpos($text, "abbastanza")) {
                if ($acqua >= $sogliaAcqua) {
                    $answer = "Si, hai bevuto abbastanza";
                } else {
                    $answer = "No, non hai bevuto abbastanza dovresti bere di più";
                }
            } else if (strpos($text, "poco")) {
                if ($acqua < $sogliaAcqua) {
                    $answer = "Si, hai bevuto poco dovresti bere di più";
                } else {
                    $answer = "No, hai bevuto abbastanza";
                }
            }
        } else {
            $answer = "Non ho dati su queto periodo";
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
            $answer = "Dai dati a mia disposizione non risulta che hai assunto calorie in questo giorno, controlla il profilo";
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
            $answer = "Non ho dati a disposizione per risponderti, controlla il profilo";
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
            if (strpos($text, "abbastanza")) {
                if ($caloriesIn >= $metabolismo ) {
                    $answer = "Si, hai assunto abbastanza calorie";
                } else {
                    $answer = "No, non hai assunto abbastanza calorie";
                }
            } else if (strpos($text, "poche")) {
                if ($caloriesIn < $metabolismo) {
                    $answer = "Si, dovresti assumere più calorie";
                } else {
                    $answer = "No, hai assunto abbastanza calorie";
                }
            }
        } else {
            $answer = "Non ho dati su questo giorno, controlla il tuo profilo";
        }
    } else if (strpos($text, "deficit")) {
        $today = strtotime(date("Y-m-d"));
        $caloriesIn = getCalorieAssunteDateFromMyrror($json_data, $today);

        if ($caloriesIn != 0.0) {
            if ($caloriesIn < $metabolismo) {
                $answer = "Si, sei in deficit calorico dovresti assumere più calorie";
            } else {
                $answer = "No,non sei in deficit calorico hai assunto abbastanza calorie";
            }
        } else {
            $answer = "Non ho dati su questo giorno, controlla il profilo";
        }
    } else {
        $answer = "Non mi hai detto per quale giorno, specifica";
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

        $cardioMinutes = getCardioMinutesDateFromMyrror($json_data, $date);

        if ($cardioMinutes != 0 ) {

            if (strpos($text, "abbastanza")) {
                if ($cardioMinutes >= 30) {
                    $answer = "Si, hai fatto abbastanza cardio";
                } else {
                    $answer = "No, non hai fatto abbastanza cardio fanne di più";
                }
            } else if (strpos($text, "poco")) {
                if ($cardioMinutes < 30 ) {
                    $answer = "Si, hai fatto poco cardio dovresti farne di più";
                } else {
                    $answer = "No, hai fatto abbastanza cardio";
                }
            }
        } else {
            $answer = "Non ho dati su questo giorno";
        }
    } else if (isset($parameters['date-period'])) {
        $startDate = strtotime($parameters['date-period']['startDate']);
        $endDate = strtotime($parameters['date-period']['endDate']);

        $cardioCount = getCountCardioPeriodFromMyrror($json_data,$startDate,$endDate);
        $cardioMinutes = getCardioMinutesPeriodFromMyrror($json_data, $startDate, $endDate);

        if ($cardioMinutes != 0) {

            if (strpos($text, "abbastanza")) {
                if ($cardioCount >= 5 && $cardioMinutes >= 5*30) {
                    $answer = "Si, hai fatto abbastanza cardio";
                } else {
                    $answer = "No, non hai fatto abbastanza cardio dovresti farne di più";
                }
            } else if (strpos($text, "poco")) {
                if ($cardioMinutes < 5*30 || $cardioCount < 5) {
                    $answer = "Si, non hai fatto abbastanza cardio dovresti farne di più";
                } else {
                    $answer = "No, hai fatto abbastanza cardio";
                }
            }
        } else {
            $answer = "Non ho dati su questo periodo";
        }

    }
    return $answer;
}
