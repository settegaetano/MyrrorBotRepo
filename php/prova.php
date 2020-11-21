<?php
        $topic = "spettacolo";
        $file = 'entertainment.csv';
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
                        echo $data[0];
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

?>

					