<?php

//Inserisci la preferenza dell'utente relativo all'ALLENAMENTO
function insertPreferenceTraining($parameters,$text,$email){


	if (isset($_COOKIE['x-access-token'])) {
		$token =  $_COOKIE['x-access-token']; 


		if ($parameters['preferencepositive'] != "") { //Preference POSITIVE

			if ($parameters['allenamentoabs'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentoabs']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];

			}else if ($parameters['AllenamentoWellness'] != ""){

				$genre = checkGenreAllenamento($parameters['AllenamentoWellness']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentolowerbody'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentolowerbody']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];

			    echo $genre;
			}else if ($parameters['allenamentoyoga'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentoyoga']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentocardio'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentocardio']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['AllenamentoUpperBody'] != ""){

				$genre = checkGenreAllenamento($parameters['AllenamentoUpperBody']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentohiit'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentohiit']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentostretching'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentostretching']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 1,
			        'timestamp'=> time()
			    ];
			}


		} elseif ($parameters['preferencenegative'] != "") {//Preference NEGATIVE
			if ($parameters['allenamentoabs'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentoabs']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['AllenamentoWellness'] != ""){

				$genre = checkGenreAllenamento($parameters['AllenamentoWellness']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentolowerbody'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentolowerbody']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentoyoga'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentoyoga']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentocardio'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentocardio']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['AllenamentoUpperBody'] != ""){

				$genre = checkGenreAllenamento($parameters['AllenamentoUpperBody']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentohiit'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentohiit']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
			        'like'=> 0,
			        'timestamp'=> time()
			    ];
			}else if ($parameters['allenamentostretching'] != ""){

				$genre = checkGenreAllenamento($parameters['allenamentostretching']);
				$allenamentoPreference = [
			        'username'=> $email,
			        'genre'=> $genre,
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
        	":5000/api/training/");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($allenamentoPreference));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);   

        curl_exec($ch);

        //Decode JSON
        //$json_data = json_decode($result2,true);

        curl_close ($ch);

        return "Training preference added";

	}

}



//Controlla il genere dell'allenamento in relazione ai generi predefiniti inseriti nel file allenamenti.csv
function checkGenreAllenamento($genere){

        // Open the file for reading
        if (($h = fopen("../fileMyrror/allenamenti.csv", "r")) !== FALSE) {
          
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