<?php

$tipo_orario = $_GET['tipo_orario'];
$classe = $_GET['classe'];

function is_url_exist($url){
    $ch = curl_init($url);    
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($code == 200){
       $status = true;
    }else{
      $status = false;
    }
    curl_close($ch);
   return $status;
}

if (!$classe) {
if ($tipo_orario == "Studenti")
{
    $classi = array();
    $files = array_diff(scandir("files/orario/classi/"), array('..', '.'));

    foreach ($files as $file)
    {
        $file = str_replace("Classe_","", $file);
        $file = str_replace(".jpg", "", $file);
        $classi[] = $file;
    }
    
    echo json_encode($classi);
}
} else {
    $url = "http://simoneluconi.altervista.org/telegram/itisbot/files/orario/classi/Classe_".$classe.".jpg";
    if (is_url_exist($url))
    {
        $array = array("link" => $url);
        echo json_encode($array);
    } else 
    {
        $array = array("link" => null);
        echo json_encode($array);
    }
}

?>