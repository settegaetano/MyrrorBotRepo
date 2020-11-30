<?php
namespace Google\Cloud\Samples\Dialogflow;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Guzzle\Http\Exception\ClientErrorResponseException;

require __DIR__.'/vendor/autoload.php';


include 'SpotifyIntent.php';
include 'Video.php';
include 'News.php';
include 'Meteo.php';

$city = "Roma";
header('Content-type: text/plain; charset=utf-8');
 ini_set('display_errors', 1);
//Controllo se la variabile 'testo' ricevuta è nulla
if (isset($_POST{'testo'})) {
    $testo = $_POST{'testo'};
}

if(isset($_POST{'city'})){
    $city = $_POST{'city'};
}
$lang = "en";
if(isset($_POST['lang'])){ 
        $lang = $_POST['lang']; 
} 
require_once('Language/lan_'.$lang.'.php');
define("DIALOGFLOW", $GLOBALS['DIALOGFLOW_LANG']);
//Email utente anonimo
$email = "UtenteAnonimo";


function detect_intent_texts($projectId,$city,$email, $text, $sessionId, $languageCode = DIALOGFLOW)
{
    // new session
    $test = array('credentials' => 'myrrorbot-4f360-cbcab170b890.json');
    $sessionsClient = new SessionsClient($test);
    $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
    
    // create text input
    $textInput = new TextInput();
    $textInput->setText($text);
    $textInput->setLanguageCode($languageCode);

    // create query input
    $queryInput = new QueryInput();
    $queryInput->setText($textInput);
    
    // get response and relevant info
    $response = $sessionsClient->detectIntent($session, $queryInput);
    $queryResult = $response->getQueryResult();
 

    $parameters=json_decode($queryResult->getParameters()->serializeToJsonString(), true);
    
    $queryText = $queryResult->getQueryText();
    $intent = $queryResult->getIntent();
    
    //risposta intent
    $fulfilmentText = $queryResult->getFulfillmentText();

    if(!is_null($intent)){
        $displayName = $intent->getDisplayName(); //Nome dell'intent
        $confidence = $queryResult->getIntentDetectionConfidence(); //Livello di confidence
        selectIntent($email,$displayName,$confidence,$text,$fulfilmentText,$parameters,$city);
        
    }else{

        $answer =$GLOBALS['dontunderstand'];

        //Stampo la risposta relativa all'intent non identificato
        $arr = array('intentName' => "Non identificato", 'confidence' => "0",'answer' => $answer);
        printf(json_encode($arr,'UTF8'));  //JSON_UNESCAPED_UNICODE utilizzato per il formato UTF8

    }
    
    $sessionsClient->close();
}



function selectIntent($email,$intent, $confidence,$text,$resp,$parameters,$city){

    if(($confidence > 0.86 ||  str_word_count($text) >= 2) && $confidence >= 0.60){              

        $answer = null;

        switch ($intent) {

            case 'Meteo citta':
        
            $answer = getCityWeather($parameters,$text);
            break;

            case 'meteo binario':
             //$city = "Bari";
                $answer = binaryWeather($city,$parameters,$text);
                break; 

            case 'Meteo':
               //$city = "Bari";
                $answer = getWeather($city,$parameters,$text);
                break;

            case 'attiva debug':
                $answer = $resp;
                break;

            case 'disattiva debug':
                $answer = $resp;
                break;

            case 'Default Welcome Intent':
                $answer = $resp;
                break;

            case '':
                $answer = $resp;
                break; 

            default:
               
                    $answer = $GLOBALS['nologin'];

               
                break;
        }

    }else {
            $answer = $GLOBALS['nologin'];

    }


     //SPOTIFY --> Valori soglia diversi
    if(($confidence > 0.86 ||  str_word_count($text) >= 2) && $confidence >= 0.50 && ($intent == 'Musica')){

        $answer = getMusic($resp,$parameters,$text,$email);
    }

    //YOUTUBE --> Valori soglia diversi
    if(($confidence > 0.86 ||  str_word_count($text) >= 2) && $confidence >= 0.50 && ( $intent == 'Ricerca Video'     )){
        switch ($intent) {

            case 'Ricerca Video':
                $answer = getVideoBySearch($resp,$parameters,$text,$email);
                break;

            default:
                  $answer = $GLOBALS['nologin'];

                break;
        }
    }
    
    //GOOGLE-NEWS--> Valori soglia diversi
    if($confidence >= 0.50  && ($intent == 'News')){

        $answer = getNews($parameters,$email,$text);

    }

    //Stampo la risposta
    $arr = array('intentName' => $intent, 'confidence' => $confidence,'answer' => $answer);

    if ($arr['intentName'] == 'Canzone per nome') {
        printf(json_encode($arr)); //JSON_UNESCAPED_UNICODE utilizzato per il formato UTF8
    }else{
        printf(json_encode($arr,JSON_UNESCAPED_UNICODE)); //JSON_UNESCAPED_UNICODE utilizzato per il formato UTF8
    }
}

//date_default_timezone_set('Europe/Madrid'); //Imposto la stessa timezone di Dialogflow (per gli orari)

try {
  detect_intent_texts('myrrorbot-4f360',$city,"",$testo,'123456');
} catch (ClientErrorResponseException $exception) {
    $responseBody = $exception->getResponse()->getBody(true);
    echo $responseBody;
}



