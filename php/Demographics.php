<?php

//IDENTITA' UTENTE
function identitaUtente($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['name'])) {

            foreach ($value1['name'] as $key2 => $value2) {

                if ($key2 == "value") {
                    $result = $value2;
                }
            }
        }
    }


    if (isset($result)) {
        $answer = str_replace("X", $result, $resp);
    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo nome &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}


function getEtaFromMyrror($json_data)
{
    $years = null;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['dateOfBirth'])) {

            foreach ($value1['dateOfBirth'] as $key2 => $value2) {

                if ($key2 == "value") {
                    $result = $value2;
                }
            }
        }
    }

    if (isset($result)) {
        $today = date("Y-m-d");
        $diff = abs(strtotime($today) - strtotime($result["value"]));
        $years = floor($diff / (365 * 60 * 60 * 24));
    }

    return $years;
}

//ETA'
function getEta($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $eta = getEtaFromMyrror($json_data);
    $answer = "";


    if ($eta == null) {
        $answer = "Non sono riuscito a reperire le informazioni relative alla tua data di nascita &#x1F62D;. Verifica che sia presente nel tuo account";
    } else {

        $answer = str_replace("X", $eta, $resp);
    }

    return $answer;
}


//LUOGO DI NASCITA
function getCountry($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['country'])) {

            foreach ($value1['country'] as $key2 => $value2) {
                if ($key2 == "value") {
                    $result = $value2;
                }
            }
        }
    }

    if (isset($result)) {

        $answer = str_replace("X", $result, $resp);

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo luogo di nascita &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}

function getHeightFromMyrror($json){
    $altezza = 0.0;

    foreach ($json as $key1 => $value1) {
        if (isset($value1['height'])) {

            $max = 0;

            foreach ($value1['height'] as $key2 => $value2) {
                if ($key2 == "value") {
                    $timestamp = $value2['timestamp'];
                    $altezza = $value2['value'];

                    if ($timestamp > $max) {
                        $max = $timestamp;
                        $altezza = $value2['value'];
                    }
                }
            }
        }
    }
    return $altezza;
}

//ALTEZZA
function getHeight($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);

    $altezza = getHeightFromMyrror($json_data);



    if ($altezza !=0.0) {
        $answer = str_replace("X", $altezza, $resp);

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative alla tua altezza &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}

function getWeightFromMyrror($json){
    $peso = 0.0;
    foreach ($json as $key1 => $value1) {
        if (isset($value1['weight'])) {

            $max = 0;

            foreach ($value1['weight'] as $key2 => $value2) {
                if ($key2 == "value") {
                    $timestamp = $value2['timestamp'];
                    $peso = $value2['value'];

                    if ($timestamp > $max) {
                        $max = $timestamp;
                        $peso = $value2['value'];
                    }
                }
            }
        }
    }
    return $peso;
}

//PESO
function getWeight($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    $peso = getWeightFromMyrror($json_data);

    //print_r($result);

    if ($peso != 0.0) {

        $answer = str_replace("X", $peso, $resp); //prima era solo $result, io ho messo $result['value']

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo peso &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}


//Ultima location per meteo sperimentazione
function citta($email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;
    $cittÃ  = null;
    //echo"la mail e".$email;
    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['location'])) {

            foreach ($value1['location'] as $key2 => $value2) {
                if ($key2 == "value") {
                    $result = $value2;
                }
            }
        }
    }

    if (isset($result['value'])) {
        $cittÃ  = $result['value'];
    }
    return $cittÃ ;

}


//Funzione che legge il parametro Location[{"value"}] del profilo olistico e restituisce
//una risposta relativa alla cittÃ  dove si vive
function Ultimacitta($email)
{
    $answer = "";
    $city = citta($email);
    if (isset($city)) {
        $answer = "Vivi a " . $city . "";
    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative alla tua ultima cittÃ . Verifica che sia presente nel tuo account";
    }
    return $answer;

}


//LAVORO
function lavoro($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['industry'])) {

            $max = 0;

            foreach ($value1['industry'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $industry = $value2['value'];

                if ($timestamp > $max) {

                    $max = $timestamp;
                    $industry = $value2['value'];
                }
            }
        }
    }

    if (isset($industry)) {

        $answer = str_replace("X", $industry, $resp);


    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo lavoro &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;

}


//EMAIL
function email($resp, $parameters, $text, $email)
{

    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;


    foreach ($json_data as $key1 => $value1) {

        if (isset($value1['email'])) {

            $max = 0;

            foreach ($value1['email'] as $key2 => $value2) {

                $timestamp = $value2['timestamp'];
                $email = $value2['value'];

                if ($timestamp > $max) {

                    $max = $timestamp;
                    $email = $value2['value'];
                }
            }
        }
    }

    if (isset($email)) {

        $answer = str_replace("X", $email, $resp);

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative alla tua email &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}

function getSesso($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['gender'])) {

            foreach ($value1['gender'] as $key2 => $value2) {
                if (isset($value2["value"])) {
                    $gender = $value2["value"];

                    if ($gender == 'MALE') {
                        $result = 'Uomo';
                    } elseif ($gender == 'FEMALE') {
                        $result = 'Donna';
                    } else {
                        $result = 'Non specificato';
                    }
                }
            }
        }
    }

    if (isset($gender)) {

        $answer = str_replace("X", $result, $resp);

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative al tuo sesso &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}

function getNazione($resp, $parameters, $text, $email)
{
    $param = "";
    $json_data = queryMyrror($param, $email);
    $result = null;

    foreach ($json_data as $key1 => $value1) {
        if (isset($value1['country'])) {

            foreach ($value1['country'] as $key2 => $value2) {
                if ($key2 == "value") {
                    $result = $value2;
                }
            }
        }
    }

    if (isset($result)) {

        $answer = str_replace("X", $result['value'], $resp);

    } else {
        $answer = "Non sono riuscito a reperire le informazioni relative alla tua nazionalitÃ  &#x1F62D;. Verifica che sia presente nel tuo account";
    }

    return $answer;
}

