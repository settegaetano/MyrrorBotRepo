<?php
require_once('url.php');

function GetRecipeslist($file,$ricetta){

        // Open the file for reading
     if (($h = fopen("../fileMyrror/".$file, "r")) !== FALSE) {
          
         $bestk = 50;
         $best = "";
         
         while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {   
          
          
              
             if (strpos(strtolower($data[1]),strtolower($ricetta)) !== false   || strpos(strtolower($ricetta),strtolower($data[1])) !== false)  {
                 return $data[1];  
                 
              }
         
         }
        
          fclose($h);
         
             return $ricetta;
         
     }
}

//echo GetRecipeslist("ricette.csv","pasta al sugo");
function insertRecipesPreference($parameters,$text,$email){

    
        $file = "ricette.csv";
        if($parameters['PreferenceNegative'] != null){
	       $like = 0;
        }else{
	       $like = 1;
        }
    
    
        if($parameters['any'] != null){
            $res = GetRecipeslist($file,$parameters['any']);
        }else if($parameters['FoodType'] != null){
            $res = 'Type:'.$parameters['FoodType'];
        }
    
         $Preference = [
			        'email'=> $email,
			        'food'=> $res,
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
        	":5000/api/recipes/");


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
        

        return "Recipe preference added";

	}
    
    return "I do not understand your preference, please re-write it!";
    
}



?>