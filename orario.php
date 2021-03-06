<?php

include "config.php";
$tipo_orario = $_GET['tipo_orario'];
$classe = $_GET['classe'];
$laboratorio = $_GET['laboratorio'];
$docente = $_GET['docente'];
$recupero = $_GET['recupero'];

$HOST_URL = HOST_URL();

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
   
if ($tipo_orario) {
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
    } else if ($tipo_orario == "Docenti")
    {
        $docenti = array();
        $files = array_diff(scandir("files/orario/docenti/"), array('..', '.'));
    
        foreach ($files as $file)
        {
            $file = str_replace(".jpg", "", $file);
            $docenti[] = $file;
        }
        
        echo json_encode($docenti);
    } else if ($tipo_orario == "Laboratori")
    {
        $laboratori = array();
        $files = array_diff(scandir("files/orario/laboratori/"), array('..', '.'));
    
        foreach ($files as $file)
        {
            $file = str_replace(".jpg", "", $file);
            $laboratori[] = $file;
        }
        
        echo json_encode($laboratori);
    } else if ($tipo_orario == "Recuperi")
    {
        $laboratori = array();
        $files = array_diff(scandir("files/orario/recuperi/"), array('..', '.'));
    
        foreach ($files as $file)
        {
            $file = str_replace("_"," ", $file);
            $file = str_replace(".jpg", "", $file);
            $laboratori[] = $file;
        }
        
        echo json_encode($laboratori);
    }
} else if ($classe) {
    $path = "files/orario/classi/Classe_$classe.jpg";
    if (realpath($path))
    {
        $array = array("link" => $path);
        echo json_encode($array);
    } else 
    {
        $array = array("link" => null);
        echo json_encode($array);
    }
}
else if ($laboratorio)
{   
    $path = "files/orario/laboratori/$laboratorio.jpg";
    if (realpath($path))
    {
        $array = array("link" => $path);
        echo json_encode($array);
    } else 
    {
        $array = array("link" => null);
        echo json_encode($array);
    }
}
else if ($docente)
{   
    $url = "files/orario/docenti/$docente.jpg";
    if (realpath($url))
    {
        $array = array("link" => $url);
        echo json_encode($array);
    } else 
    {
        $array = array("link" => null);
        echo json_encode($array);
    }
} else if ($recupero)
{   
    $url = "files/orario/recuperi/$recupero.jpg";
    if (realpath($url))
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