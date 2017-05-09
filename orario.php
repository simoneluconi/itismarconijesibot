<?php
   $tipo_orario = $_GET['tipo_orario'];
   $classe = $_GET['classe'];
   $laboratorio = $_GET['laboratorio'];
   $docente = $_GET['docente'];
   
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
   }
   } else if ($classe) {
       $classe = str_replace(' ', '%20', $classe);
       $url = "https://www.simoneluconi.com/telegram/itisbot/files/orario/classi/Classe_$classe.jpg";
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
   
   else if ($laboratorio)
   {   
       $laboratorio = str_replace(' ', '%20', $laboratorio);
        $url = "https://www.simoneluconi.com/telegram/itisbot/files/orario/laboratori/$laboratorio.jpg";
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
   
   else if ($docente)
   {   
       $docente = str_replace(' ', '%20', $docente);
        $url = "https://www.simoneluconi.com/telegram/itisbot/files/orario/docenti/$docente.jpg";
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