<?php


if (isset($_POST['title']) && isset($_POST['url'])  && isset($_POST['rating']) && isset($_POST['tec']) && isset($_POST['email'])) {

	$title = $_POST['title'];
	$url = $_POST['url'];
	$rating = $_POST['rating'];
	$tecnique = $_POST['tec'];
	$email = $_POST['email'];


	$file = fopen("../rec_news_log.txt", "a") or die("Unable to open file!");

    //fwrite($file, "time: ".$timestamp."\r\n");
    fwrite($file, "user: ".$email."\r\n");
    fwrite($file, "article_title: ".$title."\r\n");
    fwrite($file, "article_url: ".$url."\r\n");
    fwrite($file,  "recommendation_strategy: ".$tecnique."\r\n");
    fwrite($file,  "feedback: ".$rating."\r\n");
    fclose($file);

    echo "news log updated";


}




if (isset($_POST['artist'])  && isset($_POST['rating']) && isset($_POST['tec']) && isset($_POST['email'])) {

    $artista = $_POST['artist'];
    $rating = $_POST['rating'];
    $tecnique = $_POST['tec'];
    $email = $_POST['email'];
    $genre = "";

    $file = fopen("../rec_music_log.txt", "a") or die("Unable to open file!");

    // recupero il genere dell'artista consigliato
    $handle = fopen("artistCleanOutput.csv", "r");
	for ($i = 0; $row = fgetcsv($handle ); ++$i) {
	    if ($row[1] == $artista){
	    	$genre = $row[2];
	    }
	}
	fclose($handle);


    //fwrite($file, "time: ".$timestamp."\r\n");
    fwrite($file, "user: ".$email."\r\n");
    fwrite($file, "artist: ".$artista."\r\n");
    fwrite($file, "genre: ".$genre."\r\n");
    fwrite($file,  "recommendation_strategy: ".$tecnique."\r\n");
    fwrite($file,  "feedback: ".$rating."\r\n");
    fclose($file);

    echo "music log updated";


}





?>	