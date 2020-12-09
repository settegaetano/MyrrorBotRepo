<?php

//Inserisci la preferenza dell'utente relativo ai Programmi Tv
function insertPreferenceProgrammitv($parameters,$text,$email){


	if (isset($_COOKIE['x-access-token'])) {
		$token =  $_COOKIE['x-access-token']; 


		if ($parameters['preferencepositive'] != "") { //Preference POSITIVE

			if ($parameters['canaleprogramma'] != ""){//Conosco il canale (canale 5, italia 1)

				$canale = checkCanale($parameters['canaleprogramma']);
				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> null,
			        'canale'=> $canale,
			        'genere'=>null,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];

			}else if ($parameters['genereprogramma'] != ""){//Conosco il genere (calcistico, fantasy etc)

				$genere = checkGenere($parameters['genereprogramma']);
				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> null,
			        'canale'=> null,
			        'genere'=>$genere,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];

			}else if ($parameters['any'] != ""){

				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> $parameters['any'],//Conosco il programma (don matteo, l'eredità)
			        'canale'=> null,
			        'genere'=>null,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}


		} elseif ($parameters['preferencenegative'] != "") {//Preference NEGATIVE

			if ($parameters['canaleprogramma'] != ""){//Conosco il canale (canale 5, italia 1)

				$canale = checkCanale($parameters['canaleprogramma']);
				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> null,
			        'canale'=> $canale,
			        'genere'=>null,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];

			}else if ($parameters['genereprogramma'] != ""){//Conosco il genere (calcistico, fantasy etc)

				$genere = checkGenere($parameters['genereprogramma']);
				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> null,
			        'canale'=> null,
			        'genere'=>$genere,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];

			}else if ($parameters['any'] != ""){

				$programmaPreference = [
			        'username'=> $email,
			        'programmatv'=> $parameters['any'],//Conosco il programma (don matteo, l'eredità)
			        'canale'=> null,
			        'genere'=>null,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}
		}


	    $ch = curl_init();
        $headers =[
            "x-access-token:".$token
        ];

        curl_setopt($ch, CURLOPT_URL, "http://".$GLOBALS['url'].
        	":5000/api/programmatv/");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($programmaPreference));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);   

        curl_exec($ch);

        //Decode JSON
        //$json_data = json_decode($result2,true);

        curl_close ($ch);

        return "Tv program preference added";

	}

}



//Controlla il canale tv in relazione ai generi predefiniti inseriti nel file programmitv.csv
function checkCanale($genere){

        // Open the file for reading
        if (($h = fopen("../fileMyrror/programmitv.csv", "r")) !== FALSE) {
          
            // Convert each line into the local $data variable
            while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {      
                
                // Read the data from a single line
                $i = 0;
                $flag = false;
                while (isset($data[$i])){
                    if (strpos(strtolower($genere), strtolower($data[$i])) !== false) {
                        $flag = true;
                        return $data[0];
                    }
                  
                    $i++;
                }

            }

            // Close the file
            fclose($h);
        }
}

//Controlla il genere televisivo in relazione ai generi predefiniti inseriti nel file generitv.csv
function checkGenere($genere){

        // Open the file for reading
        if (($h = fopen("../fileMyrror/generitv.csv", "r")) !== FALSE) {
          
            // Convert each line into the local $data variable
            while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {      
                
                // Read the data from a single line
                $i = 0;
                $flag = false;
                while (isset($data[$i])){
                    if (strpos(strtolower($genere), strtolower($data[$i])) !== false) {
                        $flag = true;
                        return $data[0];
                    }
                  
                    $i++;
                }

            }

            // Close the file
            fclose($h);
        }
}

?>