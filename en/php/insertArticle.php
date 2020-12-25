 <?php
require_once("url.php");
$email = trim($_POST['mail']);
$link = trim($_POST['url']);
$res = trim($_POST['descrizione']);
$like = trim($_POST['like']);
 $Preference = [
                    'email'=> $email,
                    'topic'=> $res."::en",
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



        $ch = curl_init();

        $PrefURL = [
                    'email'=> $email,
                    'url'=> $link,
                    'like'=> $like,
                    'timestamp'=> time()
                ]; 

        curl_setopt($ch, CURLOPT_URL, "http://".$GLOBALS['url'].
            ":5000/api/newsfeed/");


        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($PrefURL));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);   

        curl_exec($ch);

        //Decode JSON
        //$json_data = json_decode($result2,true);
        curl_close ($ch);
        echo "ok";



}
?> 