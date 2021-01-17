<?php
/*
Parameters contiene gli argomenti restituiti da dialogflow
Tramite parameters controlliamo quale sia il parametro individuato
da dialogflow e costruiamo di conseguenza il link di ricerca in 
base alla categoria.
Se viene trovato almeno un articolo della categoria scelta il suo
URL verrà restituito , altrimenti viene restituito un messaggio d'errore
*/
function getNewsTopic($parameters){
$arr  = array('','','');
$list = array();
$link = "https://newsapi.org/v2/top-headlines?country=it&category=";
$apiKey = "&apiKey=17c1953c3cc7450d958ff14f9e262c02";
$val = "";

if ($parameters['Sports'] != null) {
	$val = $parameters['Sports'];
	$link .= "Sports&q=".$val. $apiKey;
}elseif ($parameters['Health'] != null) {
	$val = $parameters['Health'];
	$link .= "Health&q=".$val. $apiKey;
}elseif ($parameters['Science'] != null) {
	$val = $parameters['Science'];
	$link .= "Science".$apiKey;
}elseif ($parameters['Entertainment'] != null) {
	$val = $parameters['Entertainment'];
	$link .= "Entertainment&q=".$val. $apiKey;
}elseif ($parameters['Technology'] != null) {
	$val = $parameters['Technology'];
	$link .= "Technology".$apiKey;
}elseif ($parameters['Business'] != null) {
	$val = $parameters['Business'];
	//$link .= "Business&q=".$val. $apiKey;
    $link .= "Business". $apiKey;
}else{
	return "";
}



$json = googleNewsQuery($link);
$url = "";

if(!isset($json['articles'] )){

if ($parameters['Sports'] != null) {
	$val = $parameters['Sports'];
	$link .= "Sports". $apiKey;
}elseif ($parameters['Health'] != null) {
	$val = $parameters['Health'];
	$link .= "Health". $apiKey;
}elseif ($parameters['Science'] != null) {
	$val = $parameters['Science'];
	$link .= "Science".$apiKey;
}elseif ($parameters['Entertainment'] != null) {
	$val = $parameters['Entertainment'];
	$link .= "Entertainment". $apiKey;
}elseif ($parameters['Technology'] != null) {
	$val = $parameters['Technology'];
	$link .= "Technology".$apiKey;
}elseif ($parameters['Business'] != null) {
	$val = $parameters['Business'];
	//$link .= "Business&q=".$val. $apiKey;
    $link .= "Business". $apiKey;
}else{
	return "";
}

$json = googleNewsQuery($link);
$url = "";
  
}



foreach ($json['articles'] as $key => $value) {
	$url = $value['url'];
	$image = $value['urlToImage'];
	$title = $value['title'];
   if($url != ""){
     $arr[0] = $url;
	 $arr[1] = $image;
	 $arr[2] = $title;
	 array_push($list, $arr);
	
    }

	    if (count($list) == 30) {
    	$i = rand(0,9);
    	$arr = $list[$i];
    	return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
        }
}

if(count($list) == 0){
$every= "https://newsapi.org/v2/everything?q=".$val
."&language=it&sortBy=publishedAt&apiKey=17c1953c3cc7450d958ff14f9e262c02";
$json = googleNewsQuery($every);
foreach ($json['articles'] as $key => $value) {
	$url = $value['url'];
	$image = $value['urlToImage'];
	$title = $value['title'];
      if($url != ""){
     $arr[0] = $url;
	 $arr[1] = $image;
	 $arr[2] = $title;
	 array_push($list, $arr);
	
    }

	    if (count($list) == 30) {
    	$i = rand(0,9);
    	$arr = $list[$i];
    	return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
        }
}

if($url == "")
	return getTodayNews();


}else{
	$i = rand(0,count($list)-1);
    $arr = $list[$i];
    	return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
}

}

function retrieveContent($res){

$arr = array();

 if (($h = fopen("../Recommender/newsIta.csv", "r")) !== FALSE) {
    
    while (($data = fgetcsv($h, 1000, ";")) !== FALSE) { 
        
        if(isset($data[1])){
            #print_r($data);
            if (in_array($data[1], $res)){
                array_push($arr,$data);
        }
        }
       
    }

 }

return $arr;


 }



/*
il metodo chiama getInterestsList per ottenere la lista
dei 30 maggiori interessi dell'utente , per ogni interesse 
effettua una ricerca tramite googleNewsQuery fin quando 
non viene trovato un articolo,una volta trovato l'articolo
viene restituito l'url altrimenti un messaggio d'errore
*/

function getInterestsNews($email){

$arr  = array('','','','');
$articles = array();
#$list = getInterestsList($email);
if(isset($_COOKIE['technique'])){
	$technique = $_COOKIE['technique'];
}else{
	$technique = "W2V";
}


 $res  = array();
 $file = "";
 switch ($technique) {
 	case 'W2V':
 		$file = "rec_news_w2v_it.csv";
 		break;
 	case 'D2V':
 		$file = "rec_news_d2v_it.csv";
 		break;
 	case 'LSI':
 		$file = "rec_news_lsi_it.csv";
 		break;
 	case 'Fasttext':
 		$file = "rec_news_ft_it.csv";
 		break;
 }

 if (($h = fopen("../Recommender/".$file, "r")) !== FALSE) {
    $counter = 0;
    while (($data = fgetcsv($h, 1000, ";")) !== FALSE) { 
        if($data[0] == $email && $data[2] > 0.3 &&  !(in_array($data[1], $res))){
            array_push($res,$data[1]);

            if(++$counter == 5)
                break;
            #print_r($data);
             #echo "<br>";
        }
    }
    if($counter == 0){
    	return "";
    }
     $lista = retrieveContent($res);
     $r = rand(0,$counter-1);
     return array('link' => $lista[$r][5],'url' => $lista[$r][1],'image' => $lista[$r][2], 'title' => $lista[$r][0],'explain' => '' );
 }
/*
//echo $list[0];
foreach ($list as $key => $value){

	$link = "https://newsapi.org/v2/everything?q=".$key."&sortBy=publishedAt&apiKey=17c1953c3cc7450d958ff14f9e262c02";
	$link = str_replace(' ', '%20', $link);
	$json = googleNewsQuery($link);
	
	if(!isset($json['articles'] ))
        return "";


	if($json['totalResults'] != 0){
		foreach ($json['articles'] as $key2 => $value) {
			$image = $value['urlToImage'];
	        $title = $value['title'];
		    $url =  $value['url'];
	        if($url != ""){
              $arr[0] = $url;
	          $arr[1] = $image;
	          $arr[2] = $title;
	          $arr[3] = "Ti ho consigliato questo articolo perchè sei interessato a ".$key;
	          array_push($articles, $arr);

            }
		    if (count($articles) == 10) {
    	       $i = rand(0,9);
    	       $arr = $articles[$i];
        	   return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2],'explain' => $arr[3] );
            }
		}
		
	}
if(count($articles) == 0){
	return "";

}else{
	//print_r($articles);
    $i = rand(0,count($articles)-1);
    $arr = $articles[$i];
    return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2],'explain' => $arr[3] );
}

    
}
*/


/*
se non viene trovato alcun articolo relativo agli interessi
principali dell'utente vengono restituite le notizie odierne
*/
return getTodayNews();


}

function explainNews($email){

$list = getInterestsList($email);
//echo $list[0];
foreach ($list as $key => $value){

	$link = "https://newsapi.org/v2/everything?q=".$key."&sortBy=publishedAt&apiKey=17c1953c3cc7450d958ff14f9e262c02";
	$link = str_replace(' ', '%20', $link);
	$json = googleNewsQuery($link);
	
	if(!isset($json['articles'] ))
        return "Non è possibile fornire una spiegazione &#x1f614";


	if($json['totalResults'] != 0){
		foreach ($json['articles'] as $key2 => $value2) {
			$image = $value2['urlToImage'];
	        $title = $value2['title'];
		    $url =  $value2['url'];

			return "Ti ho consigliato questo articolo perchè sei interessato a ".$key ."  &#x1f600";
			
		}
		
	}
    
}

/*
se non viene trovato alcun articolo relativo agli interessi
principali dell'utente vengono restituite le notizie odierne
*/
return "Non ho trovato articoli adatti a te e ti ho dato l'ultima notizia  &#x1f600";

}


function checkTopic($topic,$file){

        // Open the file for reading
     if (($h = fopen("../fileMyrror/".$file, "r")) !== FALSE) {
          

          		$flag = false;
                $bestk = 10;
                $best = "";
            // Convert each line into the local $data variable
            while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {      
                
                // Read the data from a single line
                $i = 0;
        
                    //echo "<br>";
                while (isset($data[$i])){
                    //print($data[$i]."\r\n");
                    $k = abs(strcmp(strtolower($data[$i]),strtolower($topic)));
                   if (strpos(strtolower($data[$i]),strtolower($topic)) !== false   || strpos(strtolower($topic),strtolower($data[$i])) !== false)  {
                    //|| 
                        $flag = true;
                        return $data[0];
                    }else if($k <= 3 && $k<$bestk){
                        $best = $data[0];
                        $bestk = $k;
                    }
  
                    $i++;
                }
                
            }

            if($flag == false){
                return $best;
            }
            

            // Close the file
            fclose($h);
        }
}



function insertNewsPreference($parameters,$text,$email){

$res = "";

if ($parameters['sports'] != null) {
	$val = $parameters['sports'];
	$res = 'Topic:'.checkTopic($val,'sport.csv');

	
}elseif ($parameters['health'] != null) {
	$val = $parameters['health'];
	$res = 'Topic:'.checkTopic($val,'health.csv');
	
}elseif ($parameters['science'] != null) {
	$res = 'Topic:'.'scienza';
	
}elseif ($parameters['entertainment'] != null) {
	$val = $parameters['entertainment'];
	$res = checkTopic($val,'entertainment.csv');
	
}elseif ($parameters['Technology'] != null) {
	$res = 'Topic:'.'tecnologia';
	
}elseif ($parameters['business'] != null) {
	$val = $parameters['business'];
	$res = 'Topic:'.checkTopic($val,'business.csv');
	
    
}elseif($parameters['any'] != null){
    $res = $parameters['any'];
    
}else{
	return "non ho capito la tua preferenza,riprova";

}

if($parameters['preferencenegative'] != null){
	$like = 0;
}else{
	$like = 1;
}

        $Preference = [
			        'email'=> $email,
			        'topic'=> $res,
			        'like'=> $like,
			        'timestamp'=> time()
			    ];

	if (isset($_COOKIE['x-access-token'] )) {
		$token =  $_COOKIE['x-access-token'];
		
		$ch = curl_init();
        $headers =[
            "x-access-token:".$token
        ];

        curl_setopt($ch, CURLOPT_URL, "http://".$GLOBALS['url'].
        	":5000/api/news/");


        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($Preference));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);   

        curl_exec($ch);

        //Decode JSON
        //$json_data = json_decode($result2,true);

        curl_close ($ch);

        return "preferenza inserita correttamente";

	}

		return "non ho capito la tua preferenza,riprova.";
	


}




/*
Questo metodo usa googleNewsQuery($link) per ottenere
l'elenco delle maggiori notizie odierne e ne resituisce la prima
in output
*/

function getTodayNews(){

$arr  = array('','','');
$list = array();
$link = "https://newsapi.org/v2/top-headlines?country=it&apiKey=17c1953c3cc7450d958ff14f9e262c02";
$json = googleNewsQuery($link);
$url = "";

if(!isset($json['articles'] ))
   return "";

foreach ($json['articles'] as $key => $value) {
	$url = $value['url'];
	$image = $value['urlToImage'];
	$title = $value['title'];
    if($url != ""){
     $arr[0] = $url;
	 $arr[1] = $image;
	 $arr[2] = $title;
	 array_push($list, $arr);

	 
    }
	    if (count($list) == 30) {
    	$i = rand(0,9);
    	$arr = $list[$i];
    	return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
    }
}

if(count($list) == 0){
	return "";

}else{
	
    $i = rand(0,count($list)-1);
    $arr = $list[$i];
    return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
}

}
/*
@parameters parametri con i dati sul testo da cercare
la funzione prende il parametro any individuato da DIalogflow ed effettua una
ricerca tramite il metodo GoogleNewsQuery.
Se vengono trovati articoli ne viene restituito il link,
altrimenti viene restituita una notizia di oggi tramite il
metodo getTodayNews
*/
function cercaNews($parameters){

$arr  = array('','','');
$list = array();
if(isset($parameters['any'])){
$val = $parameters['any'];	
$val = str_replace(' ', '%20', $val);
$link = "https://newsapi.org/v2/everything?q=".$val.
"&sortBy=publishedAt&Language=it&apiKey=17c1953c3cc7450d958ff14f9e262c02";
$json = googleNewsQuery($link);

if(!isset($json['articles'] ))
   return "";

foreach ($json['articles'] as $key => $value) {
	$url = $value['url'];
	$image = $value['urlToImage'];
	$title = $value['title'];
	
    if($url != ""){
     $arr[0] = $url;
	 $arr[1] = $image;
	 $arr[2] = $title;
	 array_push($list, $arr);
	 
    }

    if (count($list) == 30) {
    	$i = rand(0,9);
    	$arr = $list[$i];
    	 return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
    }
}
if(count($list) == 0){
	return getTodayNews();
}else{
    $i = rand(0,count($list)-1);
    $arr = $list[$i];
    return array('link' => $link,'url' => $arr[0],'image' => $arr[1], 'title' => $arr[2] );
}

}else{
	return getTodayNews();
}

}
/*
la funzione usa il parametro $link pre fare
una chiamata REST A GOOGLE NEWS e restituisce il risultato
*/
function googleNewsQuery($link){

$ch = curl_init();
$json_data = null;

curl_setopt($ch, CURLOPT_URL,$link);
curl_setopt($ch, CURLOPT_POST, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);
$result = json_decode($server_output,true);
curl_close ($ch);

return $result;

}

function getNews($parameters,$email,$text){

if ($parameters['Sports'] != null || $parameters['Health'] != null || $parameters['Science'] != null ||
 $parameters['Entertainment'] != null || $parameters['Technology'] != null || $parameters['Business'] != null ) {	
	$answer = getNewsTopic($parameters);
}elseif($parameters['any'] != null ){
    $answer = cercaNews($parameters);
}elseif (stripos($text, 'oggi') !== false || stripos($text, 'odierne') !== false || stripos($text, 'quotidiane')  ||
 stripos($text, 'ultime')  !== false ){
	$answer = getTodayNews();
}elseif (stripos($text, 'interessi') !== false || stripos($text, 'consigliami') !== false || 
	stripos($text, 'interessano') !== false || stripos($text, 'interessano') !== false || 
	stripos($text, 'raccom') !== false) {

	if ($email == '') {
		return '';
	}
	$answer = getInterestsNews($email);
}else{
      $answer = getTodayNews();
}

return $answer;

}


?>