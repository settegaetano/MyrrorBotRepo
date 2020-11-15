<?php

    //Recupero email e password
    $email      = 'rakes@rakes.it';
    $password  = 'rakes';

    //Url per inviare la richiesta POST
    $url = 'http://192.168.1.9:5000/auth/login';

    //Dati da inviare nella richiesta
    $credenziali = [
        'email' => $email,
        'password' => $password
    ];
                

    //url-ify the data for the POST
    $fields_string = http_build_query($credenziali);

    $ch = curl_init();
    $json_data = null;

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);


    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    $result = json_decode($server_output);
    curl_close($ch);
    $out = $result -> auth;

    $username = $result -> username;
    //$token = $result -> token;
    //echo $token;
    
    $musicPreference = [
        'username'=> $username,
        'song'=> 'one ciu tri',
        'artist'=> 'Tiziano Ferro',
        'genre'=> 'pop',
        'like'=> 1,
        'timestamp'=> time()
    ];

    
    // Further processing ...
    if ($out == 1) {
         $token = $result -> token;
         
         if(!isset($_COOKIE['x-access-token'])){
             setcookie('x-access-token', $token, time() + 60 * 60 * 24 * 30, "/", false);
         }
        

        $ch = curl_init();
        $headers =[
            "x-access-token:".$token
        ];

        curl_setopt($ch, CURLOPT_URL, "http://192.168.1.9:5000/api/music/");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($musicPreference));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);   

        curl_exec($ch);

        //Decode JSON
        //$json_data = json_decode($result2,true);

        curl_close ($ch);

        /*Creating file
        $fp = fopen('../prova'. $email . ".json", 'w+');
        fwrite($fp, json_encode($json_data));
        fclose($fp);
        */


        

        
    }


    if(isset($_COOKIE['x-access-token'])){
        echo $_COOKIE['x-access-token']; 
    }

?>

					