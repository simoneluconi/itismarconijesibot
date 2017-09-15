<html>
   <head>
      <title>ITIS Marconi Jesi Bot</title>
      <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
      <!-- Compiled and minified CSS -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.0/css/materialize.min.css">
      <!-- Compiled and minified JavaScript -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.0/js/materialize.min.js"></script>
      <link rel="shortcut icon" href="./files/favicon.ico" type="image/x-icon">
      <link rel="icon" href="./files/favicon.ico" type="image/x-icon">
   </head>
   <body>
      <nav class="light-blue lighten-1" role="navigation">

    <div class="nav-wrapper container">
    <a href="#" class="brand-logo">ITIS Marconi Jesi Bot</a>
    <ul class="right hide-on-med-and-down">
        <li><a href="https://t.me/itismarconijesibot">Aggiungi a Telegram</a></li>
        <li><a href="https://t.me/simoneluconi">Contattami</a></li>
        <li><a href="https://github.com/simoneluconi/itismarconijesibot">Github</a></li>
      </ul>
      <ul id="nav-mobile" class="side-nav">
        <li><a href="https://t.me/itismarconijesibot">Aggiungi a Telegram</a></li>
        <li><a href="https://t.me/simoneluconi">Contattami</a></li>
        <li><a href="https://github.com/simoneluconi/itismarconijesibot">Github</a></li>
      </ul>
      <a href="#" data-activates="nav-mobile" class="button-collapse"><i class="material-icons">menu</i></a>
    </div>
      </nav>
      <div class="container">
         <?php
            include 'simple_html_dom.php';
            include 'random.php';
            include 'config.php';
            define("Telegram", "https://api.telegram.org/bot" . TELEGRAM_TOKEN);
            define("ITIS_URL", "https://www.itismarconi-jesi.gov.it");
            define("TYPING", "typing");
            define("UPLOAD_PHOTO", "upload_photo");
            define("UPLOAD_DOCUMENT", "upload_document");

            $HOST_URL = HOST_URL();

            date_default_timezone_set('Europe/Rome');
            define("message_circolari", "\xF0\x9F\x94\x8D Puoi cercare circolari scrivendo ad esempio <b>\"Circolare 220\"</b>, <b>\"Circolare sciopero\"</b>, <b>\"Circolari di ieri\"</b>, <b>\"Circolari di oggi\"</b> o <b>\"Circolari del 4/03/17\"</b>.");
            function Download_Html($url) {
                $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
                $curl = curl_init();
                // set user agent
                curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                // grab content from the website
                $content = curl_exec($curl);
                curl_close($curl);
                return $content;
            }

            function getChat($chat_id) {
                return Download_Html(Telegram . "/getChat?chat_id=$chat_id");
            }

            function sendDocument($chat_id, $document, $caption) {
                 if(sendAction(Telegram . "/sendDocument?chat_id=$chat_id&document=$document&caption=" . urlencode($caption)) == 403)
                    deleteUser($chat_id);
            }
            
            function sendMessage($chat_id, $message) {
                if(sendAction(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&parse_mode=HTML") == 403)
                    deleteUser($chat_id);
            }
            
            function sendPhoto($chat_id, $link) {
                sendAction(Telegram . "/sendPhoto?chat_id=$chat_id&photo=" . urlencode($link));
            }

            function sendChatAction($chat_id, $action) {
                sendAction(Telegram . "/sendChatAction?chat_id=$chat_id&action=$action");
            }
            
            function remove_keyboard($chat_id, $message) {
                $resp = array("remove_keyboard" => true);
                $reply = json_encode($resp);
                sendAction(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply) . "&parse_mode=HTML");
            }
            function sendKeyboard($chat_id, $message, $keyboard) {
                $keyboard[] = array("❌ Annulla");
                $resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
                $reply = json_encode($resp);
                sendAction(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply) . "&parse_mode=HTML");
            }

            function sendInlineKeyboard($chat_id, $message, $keyboard) {
                $resp = array("inline_keyboard" => $keyboard);
                $reply = json_encode($resp);
                sendAction(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=".urlencode($reply) . "&parse_mode=HTML");
            }

            function sendInlineKeyboardwithDocument($chat_id, $document, $caption, $keyboard) {
                $resp = array("inline_keyboard" => $keyboard);
                $reply = json_encode($resp);
                sendAction(Telegram . "/sendDocument?chat_id=$chat_id&document=$document&caption=" . urlencode($caption). "&reply_markup=".urlencode($reply));
            }

            function answerCallbackQuery($callback_id) {
                sendAction(Telegram . "/answerCallbackQuery?callback_query_id=$callback_id");
            }

            function errCircolari($chat_id) {
                remove_keyboard($chat_id, "\xE2\x9D\x97 Formato del messaggio non valido!");
                sendMessage($chat_id, message_circolari);
            }
            function getLinkEvento($link_evento) {
                preg_match_all('/".*?"|\'.*?\'/', $link_evento, $link_evento);
                $link_evento = $link_evento[0][0];
                $link_evento = str_replace("'", "", $link_evento);
                $link_evento = ITIS_URL . $link_evento;
                return $link_evento;
            }
            function fatal_handler() {
                $last_error = error_get_last();
                  if ($last_error && $last_error['type']==E_ERROR)
                  {
                    header("HTTP/1.1 500 Internal Server Error");
                    echo "Ops...";
                  }
            }

            function isOnline($domain) {
                $curlInit = curl_init($domain);
                $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
                curl_setopt($curlInit, CURLOPT_USERAGENT, $useragent);
                curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
                curl_setopt($curlInit,CURLOPT_HEADER,true);
                curl_setopt($curlInit,CURLOPT_NOBODY,true);
                curl_setopt($curlInit, CURLOPT_TIMEOUT, 10);
                curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

                //get answer
                $response = curl_exec($curlInit);

                if ($response) 
                {
                    $httpcode = curl_getinfo($curlInit, CURLINFO_HTTP_CODE);
                    $info = curl_getinfo($curlInit);
                    $time = $info['total_time'];
                    curl_close($curlInit);
                    if ($httpcode == 200)
                    {
                        if ($time > 2)
                        return 0;
                        else return 1;
                    }
                    else return -1;
                }
                return -1;
            }

            function sendAction($url)
            {
                $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
                $curl = curl_init();
                // set user agent
                curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_exec($curl);
                $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                return $httpcode;
            }

            function getMessageOnline($status)
            {
                if ($status == 1)
                {
                   return "\xF0\x9F\x94\xB5";
                } else if ($status == -1) {
                    return "\xF0\x9F\x94\xB4";
                } else if ($status == 0)
                {
                    return "\xE2\x8F\xB3";
                } else return "\xE2\x9D\x97";
            }

            register_shutdown_function("fatal_handler");
            function shortUrl($long_url) {
                $url = 'https://www.googleapis.com/urlshortener/v1/url?key=' . Google_Api_Key;
                $data = array('longUrl' => $long_url);
                // use key 'http' even if you send the request to https://...
                $options = array('http' => array('header' => "Content-Type: application/json", 'method' => 'POST', 'content' => json_encode($data)));
                $context = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                if ($result === FALSE) {
                    return FALSE;
                }
                $result = json_decode($result);
                $short_link = $result->{'id'};
                return $short_link;
            }
            $ip;
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            $input = file_get_contents("php://input");
            $updates = json_decode($input, true);
            $user_name = $updates['message']['from']['first_name'];
            $chat_id = $updates['message']['chat']['id'];
            $message = $updates['message']['text'];
            
            function updateLastCommand($chat_id, $command)
            {            
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);   
                if ($command) {
                $command = $mysqli->real_escape_string($command);
                $result = $mysqli->query("UPDATE db_bot_telegram_itis SET last_command = '$command' where chat_id='$chat_id'");
                } else 
                {
                $result = $mysqli->query("UPDATE db_bot_telegram_itis SET last_command = NULL where chat_id='$chat_id'");
                }
                $mysqli->close();
            }
            
            function getLastCommand($chat_id)
            {
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
                $result = $mysqli->query("SELECT last_command from db_bot_telegram_itis where chat_id='$chat_id'");
                $row = $result->fetch_assoc();
                $mysqli->close();
                return $row['last_command'];
            }

            function updateLastNews($title, $time)
            {            
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                $title = $mysqli->real_escape_string($title);
                $time = $mysqli->real_escape_string($time);
                $result = $mysqli->query("TRUNCATE TABLE db_last_news");
                $result = $mysqli->query("INSERT INTO db_last_news (title, time) VALUES ('$title', '$time')");
                $mysqli->close();
            }
            
            function getLastNews()
            {
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
                $result = $mysqli->query("SELECT * from db_last_news");
                $row = $result->fetch_assoc();
                $mysqli->close();
                return $row;
            }

            function deleteUser($chat_id)
            {            
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                $result = $mysqli->query("DELETE from db_bot_telegram_itis where chat_id='$chat_id'");
                $result = $mysqli->query("INSERT INTO db_deleted (chat_id) VALUES ('$chat_id')");
                $mysqli->close();
            }

            function trovaAllegatiPerCircolare($titolo)
            {
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
                $titolo = $mysqli->real_escape_string($titolo);
                $result = $mysqli->query("SELECT * from db_allegati where circolare='$titolo'");
                $results = array();
                while($row = $result->fetch_assoc()){
                    $results[] = $row;
                }
                $mysqli->close();
                return $results;
            }

            function inviaCircolare($chat_id, $row) //Ricorda: Questa funzione non invia le nuove circolari, solo quelle prese dal DB
            {
                $allegati = trovaAllegatiPerCircolare($row['titolo']);
                    if (!count($allegati)){
                        sendDocument($chat_id, $row['allegato'], $row['titolo']);
                    } else {
                        $keyboard = array();
                        foreach ($allegati as &$allegato) {
                            $keyboard[] = array(array("text" => "\xF0\x9F\x93\x8E ".$allegato['titolo'], "callback_data" => "all://".$allegato['id']));
                        }
                        sendInlineKeyboardwithDocument($chat_id, $row['allegato'], $row['titolo'], $keyboard);
                    }
            }

            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

            $result = $mysqli->query("SELECT * FROM db_bot_telegram_itis");
            $utenti = array();
            while ($row = $result->fetch_assoc()) {
                $utenti[] = $row;
            }
            
            if ($message)
            {
                $last_command = getLastCommand($chat_id);
                if ($last_command != "/circolari" && $last_command != "/circolari@itismarconijesibot")
                    updateLastCommand($chat_id, $message);
            }

            if (isset($updates['callback_query'])) 
             {
                    $user_name = $updates['callback_query']['message']['from']['first_name'];
                    $user_surname = $updates['callback_query']['message']['from']['last_name'];
                    $user_id = $updates['callback_query']['message']['from']['id'];
                    $chat_id = $updates['callback_query']['message']['chat']['id'];
                    $message_id = $updates['callback_query']['message']['message_id'];
                    $message = $updates['callback_query']['message']['text'];
                    $callback_id = $updates['callback_query']['id'];
                    $callback_data = $updates['callback_query']['data'];

                    if((strpos($callback_data, 'circolare://') !== false))
                    {
                        sendChatAction($chat_id, UPLOAD_DOCUMENT);
                        $cerca = str_replace("circolare://", "", $callback_data);
                        $cerca = "circolare n." . $cerca;
                        $cerca = $mysqli->real_escape_string($cerca);
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE titolo LIKE '$cerca%'");
                        $num_rows = $result->num_rows;
                        if ($num_rows == 0) {
                            sendMessage($chat_id, "Mi dispiace, non ho trovato nessuna circolare numero $cerca \xF0\x9F\x98\x94");
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                sendDocument($chat_id, $row['allegato'], $row['titolo']);
                            }
                        }

                        answerCallbackQuery($callback_id);
                    } else if((strpos($callback_data, 'all://') !== false))
                    {
                        sendChatAction($chat_id, UPLOAD_DOCUMENT);
                        $cerca = str_replace("all://", "", $callback_data);
                        $cerca = $mysqli->real_escape_string($cerca);
                        $result = $mysqli->query("SELECT * FROM db_allegati WHERE id='$cerca'");
                        $num_rows = $result->num_rows;
                        if ($num_rows == 0) {
                            sendMessage($chat_id, "Mi dispiace, non ho trovato nessuna allegato con questo ID \xF0\x9F\x98\x94");
                        } else {
                            $exts = ["pdf", "zip", "gif"];
                            if ($row = $result->fetch_assoc()) {
                                $allegato = $row['allegato'];
                                $ext = substr($allegato, strripos($allegato, ".") + 1);
                                if (in_array($ext, $exts))
                                    sendDocument($chat_id, $allegato , $row['titolo']); 
                                else {
                                    $keyboard = array();
                                    $keyboard[] = array(array("text" => "\xF0\x9F\x93\x8E ".$row['titolo'], "url" => $allegato));
                                    sendInlineKeyboard($chat_id, "Al momento non posso inviare questo tipo di allegato <b>($ext)</b> \xF0\x9F\x98\x94, per scaricarlo manualmente clicca il pulsante qui sotto:", $keyboard);
                                }
                            }
                        }

                        answerCallbackQuery($callback_id);
                    }

                }

                if ($message == "❌ Annulla")
                {
                    remove_keyboard($chat_id, "Ultimo comando cancellato \xF0\x9F\x98\x89");
                    updateLastCommand($chat_id, NULL);
                } else if ($message == "/invia" && $chat_id == ADMIN_CHAT_ID)
                {
                    sendMessage($chat_id, "\xE2\x9C\x8F Invia il messaggio che vuoi inoltrare:");
                    updateLastCommand($chat_id, $message);
                } else if ($last_command == "/invia" && $chat_id == ADMIN_CHAT_ID)
                {
                    if ($message == "Scrutini") {

                        $message = "\xE2\x9D\x97 Si informano tutti gli studenti che gli <b>scrutini</b> sono stati pubblicati nell'apposita sezione del registro elettronico.";

                        $keyboard[] = array(array("text" => "\xF0\x9F\x93\x91 Vai agli scrutini", "url" => "https://web.spaggiari.eu/sol/app/default/documenti_sol.php"));     

                         foreach ($utenti as & $utente) {
                            sendInlineKeyboard($utente['chat_id'], $message, $keyboard);
                        }

                    } else {
                        foreach ($utenti as & $utente) {
                            sendMessage($utente['chat_id'], $message);
                        }
                    }
                    sendMessage($chat_id, "\xE2\x9C\x94 Messaggio inoltrato!");
                    updateLastCommand($chat_id, NULL);
                }
                else if (strpos($last_command, 'Studenti') !== false)
                {
                    $tmp = explode(" ", $message);
                    $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?classe=".$tmp[1]));
                    if ($response->link) {
                        sendChatAction($chat_id, TYPING);
                        remove_keyboard($chat_id, "\xF0\x9F\x93\xA9 Ti invio l'orario della <b>classe ". $tmp[1]."</b>");
                        sendChatAction($chat_id, UPLOAD_PHOTO);
                        sendPhoto($chat_id, $response->link);
                    } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario della <b>classe ". $tmp[1])."</b> \xF0\x9F\x98\x94";
                        updateLastCommand($chat_id, NULL);
                } else if (strpos($last_command, 'Laboratori') !== false)
                {
                    sendChatAction($chat_id, TYPING);
                    $tmp = str_replace(' ', '%20' , $message);
                    $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?laboratorio=".$tmp));
                if ($response->link) {
                    remove_keyboard($chat_id, "\xF0\x9F\x93\xA9	Ti invio l'orario del <b>$message</b>");
                    sendChatAction($chat_id, UPLOAD_PHOTO);
                    sendPhoto($chat_id, $response->link);
                    } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario del <b>$message</b> \xF0\x9F\x98\x94");
                        updateLastCommand($chat_id, NULL);                
                } else if (strpos($last_command, 'Docenti') !== false)
                {
                    sendChatAction($chat_id, TYPING);
                    $tmp = str_replace(' ', '%20' , $message);
                    $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?docente=".$tmp));
                if ($response->link) {
                    remove_keyboard($chat_id, "\xF0\x9F\x93\xA9	Ti invio l'orario del docente <b>$message</b>");
                    sendChatAction($chat_id, UPLOAD_PHOTO);
                    sendPhoto($chat_id, $response->link);
                    } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario del docente <b>$message</b> \xF0\x9F\x98\x94");
                        updateLastCommand($chat_id, NULL);
                }
                else if ($message == "/start" || $message == "/start@itismarconijesibot") {
                    sendChatAction($chat_id, TYPING);

                    $result = $mysqli->query("SELECT * FROM db_deleted where chat_id='$chat_id'");
                    $num_rows = $result->num_rows;
                    if ($num_rows > 0) {
                        sendMessage($chat_id, "Hei $user_name mi ricordo di te! Bentornato! \xF0\x9F\x98\x83");
                        $mysqli->query("DELETE FROM db_deleted where chat_id='$chat_id'");
                        $mysqli->query("INSERT INTO db_bot_telegram_itis (chat_id) VALUES ('$chat_id')");
                    }
                    else {
                        $result = $mysqli->query("SELECT * FROM db_bot_telegram_itis where chat_id='$chat_id'");
                        $num_rows = $result->num_rows;
                        if ($num_rows == 0) {
                            $result = $mysqli->query("INSERT INTO db_bot_telegram_itis (chat_id) VALUES ('$chat_id')");
                            if ($result == 1) sendMessage($chat_id, "Benvenuto! Da questo momento iniziarai a ricevere notifiche di nuove circolari, eventi e altre comunicazioni \xF0\x9F\x98\x89");
                            else sendMessage($chat_id, "Ops...c'è stato un problema nell'avviare il bot \xF0\x9F\x98\x94");
                        } else {
                            sendMessage($chat_id, "Sei già stato aggiunto \xF0\x9F\x98\x85");
                            updateLastCommand($chat_id, NULL);
                        }
                    }
            }
            else if ($message == "/orario" || $message == "/orario@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
            $array = array(array("Studenti"), array("Docenti"), /*array("Laboratori"), array("Recupero/Potenziamento")*/);
                sendKeyboard($chat_id, "\xF0\x9F\x95\x90 Seleziona un orario: ", $array);
            } else if ($message == "Studenti" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?tipo_orario=Studenti"));

                $array = array(); $row = array(); $count = 0;
                foreach ($response as $classe)
                {
                    $row[] = "Classe ".$classe;
                    $count++;
                    if ($count == 3) {
                        $array[] = $row;
                        $row = array();
                        $count = 0;
                    }
                }
                
                if ($count != 0)
                    $array[] = $row;
            
                sendKeyboard($chat_id, "\xF0\x9F\x93\x9A Seleziona una classe:", $array);
            
            }  else if ($message == "Laboratori" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?tipo_orario=Laboratori"));
            
                $array = array(); $row = array(); $count = 0;
                foreach ($response as $laboratorio)
                {
                    $row[] = $laboratorio;
                    $count++;
                    if ($count == 3) {
                        $array[] = $row;
                        $row = array();
                        $count = 0;
                    }
                }

                if ($count != 0)
                    $array[] = $row;

                sendKeyboard($chat_id, "\xF0\x9F\x9A\xAA Seleziona un laboratorio:", $array);
            }  else if ($message == "Docenti" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $response = json_decode(Download_Html($HOST_URL.ORARIO_URL."?tipo_orario=Docenti"));
            
                $array = array(); $row = array(); $count = 0;
                foreach ($response as $docente)
                {
                    $row[] = $docente;
                    $count++;
                    if ($count == 3) {
                        $array[] = $row;
                        $row = array();
                        $count = 0;
                    }
                }

                if ($count != 0)
                    $array[] = $row;
                    
                sendKeyboard($chat_id, "\xF0\x9F\x8E\x93 Seleziona un docente:", $array);
            }
             else if ($message == "Recupero/Potenziamento" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, UPLOAD_DOCUMENT);
                sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itismarconi-jesi_orario_potenziamento_aprile-maggio-giugno-2017.pdf", "Orario Recupero/Potenziamento");
                remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 31/3/2017");
                updateLastCommand($chat_id, NULL);
            } else if ($message == "/calendario" || $message == "/calendario@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
                $reply = "<b>Calendario Scolastico 2017-2018</b>\n";
                $reply.= "\xF0\x9F\x93\x85 Inizio lezioni: <b>Venerdì 15 Settembre 2017</b>\n";
                $reply.= "\xF0\x9F\x93\x85 Fine lezioni: <b>Venerdì 8 Giugno 2018</b>\n";
                $reply.= "\n";
                $reply.= "\xF0\x9F\x8E\x89 La scuola resterà chiusa nelle seguenti <b>giornate di festività</b>:\n";
                $reply.= "• 22 Settembre 2017 (festa del Patrono)\n";
                $reply.= "• 01 Novembre 2017 (festa di tutti i Santi)\n";
                $reply.= "• 02 Novembre 2017 (commemorazione dei defunti)\n";
                $reply.= "• 08 Dicembre 2017 (Immacolata Concezione)\n";
                $reply.= "• 25 Dicembre 2017 (Santo Natale)\n";
                $reply.= "• 26 Dicembre 2017 (Santo Stefano)\n";
                $reply.= "• 01 Gennaio 2018 (Capodanno)\n";
                $reply.= "• 06 Gennaio 2018 (Epifania)\n";
                $reply.= "• 2 Aprile 2018 (Lunedì dell'Angelo)\n";
                $reply.= "• 25 Aprile 2018 (anniversario della Liberazione)\n";
                $reply.= "• 01 Maggio 2018 (festa del Lavoro)\n";
                $reply.= "• 02 Giugno 2018 (festa nazionale della Repubblica)\n";
                $reply.= "\n";
                $reply.= "\xF0\x9F\x8E\x84 <b>Vacanze di Natale</b>: dal 24 Dicembre 2017 al 5 Gennaio 2018\n";
                $reply.= "\xF0\x9F\x90\xB0 <b>Vacanze di Pasqua</b>: dal 29 Marzo 2018 al 3 Aprile 2018\n";
                sendMessage($chat_id, $reply);
                updateLastCommand($chat_id, NULL);
            } else if ($message == "/id" || $message == "/id@itismarconijesibot" ) {
                sendMessage($chat_id, "Il tuo chat id è: " . $chat_id);
                updateLastCommand($chat_id, NULL);
            } else if ($message == "/info" || $message == "/info@itismarconijesibot" ) {
                $reply = "<b>Cosa può fare questo bot?</b>\n";
                $reply .= "Questo bot nasce dall'esigenza pratica di tenersi costantemente aggiornati riguardo alle nuove circolari e i nuovi eventi che vengono pubblicati nel <a href=\"http://www.itismarconi-jesi.gov.it/\">sito della scuola</a> (I.T.I.S. G. Marconi di Jesi). Il bot inoltrerà nella chat le nuove circolari uscite e gli eventi presenti nel sito. Sarà inoltre possibile ricercare circolari, visualizzare l'orario di studenti, professori e laboratori ed il calendario scolastico.";
                $reply .= "\n\n<b>Contribuisci allo sviluppo</b>\n";
                $reply .= "Questo bot è opensuorce \xF0\x9F\x8E\x86 Puoi visualizzare il sorgente su <a href='https://github.com/simoneluconi/itismarconijesibot/'>Github</a> e contribuire al suo sviluppo. In alternativa, per propormi dei suggerimenti contattami a @simoneluconi.";
                sendMessage($chat_id, $reply);
                updateLastCommand($chat_id, NULL);
            } else if ($message == "/circolari" || $message == "/circolari@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
                $keyboard = array(); //Vuoto perchè viene già aggiunto dal metodo
                sendKeyboard($chat_id, message_circolari, $keyboard);
            } else if ((strpos(strtolower($message), 'circolare') !== false ) && (strpos($last_command, '/circolari') !== false)) {
                sendChatAction($chat_id, TYPING);
                $tmp = explode(" ", $message);
                if (count($tmp) > 1) {
                    $numero = intval($tmp[1]);
                    if ($numero == 0) {
                        //sendMessage($chat_id, "Formato del messaggio non valido \xF0\x9F\x98\x94 \nDevi scrivere ad esempio \"Circolare 220\"");
                        $message_escaped = $mysqli->real_escape_string($message);
                        $message_escaped = str_replace("..", "", $message_escaped);
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE titolo LIKE LOWER('$message_escaped%')");
                        if ($result->num_rows > 0) {
                            sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            remove_keyboard($chat_id, "\xF0\x9F\x93\xA9	Ti invio la circolare:");
                            while ($row = $result->fetch_assoc()) {
                                inviaCircolare($chat_id, $row);
                            }
                                updateLastCommand($chat_id, NULL);
                        } else {
                            $result = $mysqli->query("SELECT * FROM db_circolari");
                            if ($result->num_rows > 0) sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            $circolari_keyboard = array();
                            $cerca = strtolower($message);
                            $cerca = str_replace("circolare ", "", $cerca);
                            while ($row = $result->fetch_assoc()) {
                                $titolo = strtolower($row['titolo']);
                                if (strpos($titolo, $cerca) !== false) {
                                    $circolari_keyboard[] = array($row['titolo']);
                                }
                            }
                            if (count($circolari_keyboard) == 0) remove_keyboard($chat_id, "Non ho trovato nessuna circolare con nome $cerca \xF0\x9F\x98\x94");
                            else {
                                $n_circolari = count($circolari_keyboard);
                                if ($n_circolari == 1) sendKeyboard($chat_id, "\xF0\x9F\x93\x91	Ho trovato una circolare: ", $circolari_keyboard);
                                else sendKeyboard($chat_id, "\xF0\x9F\x93\x91 Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                            }
                        }
                    } else {
                        $cerca = "circolare n.$numero ";
                        $cerca = $mysqli->real_escape_string($cerca);
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE titolo LIKE '$cerca%'");
                        $num_rows = $result->num_rows;
                        if ($num_rows == 0) {
                            remove_keyboard($chat_id, "Mi dispiace, non ho trovato nessuna circolare numero $numero \xF0\x9F\x98\x94");
                        } else {
                            sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            if ($num_rows == 1) remove_keyboard($chat_id, "\xF0\x9F\x93\x91	Ho trovato questa circolare:");
                            else remove_keyboard($chat_id, "\xF0\x9F\x93\x91 Ho trovato queste circolari:");
                            while ($row = $result->fetch_assoc()) {
                                inviaCircolare($chat_id, $row);
                            }
                        }
                            updateLastCommand($chat_id, NULL);
                    }
                } else {
                    errCircolari($chat_id);
                    updateLastCommand($chat_id, NULL);
                }
            } else if (strpos(strtolower($message), 'circolari') !== false && (strpos($last_command, '/circolari') !== false)) {
                sendChatAction($chat_id, TYPING);
                $tmp = explode(" ", strtolower($message));
                if ($tmp[1] == "di") {
                    if ($tmp[2] == "ieri") {
                        $ieri = new DateTime();
                        $ieri->sub(new DateInterval('P1D'));
                        $ieri_str = $ieri->format('d-m-Y');
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE data = '$ieri_str'");
                        $circolari_keyboard = array();
                        while ($row = $result->fetch_assoc()) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Ieri non è uscita nessuna circolare \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "\xF0\x9F\x93\x91	Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "\xF0\x9F\x93\x91 Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    } else if ($tmp[2] == "oggi") {
                        $oggi = new DateTime();
                        $oggi = $oggi->format('d-m-Y');
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE data = '$oggi'");
                        $circolari_keyboard = array();
                        while ($row = $result->fetch_assoc()) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Oggi non è uscita nessuna circolare \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "\xF0\x9F\x93\x91	Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "\xF0\x9F\x93\x91 Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    } else errCircolari($chat_id);
                } else if ($tmp[1] == "del") {
                    $date_circolare = DateTime::createFromFormat('d/m/y', $tmp[2]);
                    if (is_bool($date_circolare)) {
                        sendMessage($chat_id, "\xE2\x9D\x97	Formato della data non valido!");
                        sendMessage($chat_id, "La data deve essere nel formato <b>gg/mm/aa</b>");
                    } else {
                        $date_circolare_format = $date_circolare->format('d-m-Y');
                        $result = $mysqli->query("SELECT * FROM db_circolari WHERE data = '$date_circolare_format'");
                        $circolari_keyboard = array();
                        while ($row = $result->fetch_assoc()) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Non c'è nessuna circolare in data $date_circolare_format \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "\xF0\x9F\x93\x91	Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "\xF0\x9F\x93\x91 Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    }
                } else 
                {
                    updateLastCommand($chat_id, NULL);
                    errCircolari($chat_id);
                }
            } else if ($message == "/stato" || $message == "/stato@itismarconijesibot") {
            
            sendChatAction($chat_id, TYPING);
            $message = "<b>Stato dei servizi scolastici:</b>\n";
            $message .= "\xF0\x9F\x94\xB4 = Offline | \xF0\x9F\x94\xB5 = Online | \xE2\x8F\xB3 = Rallentato\n\n";
            
            $icon = getMessageOnline(isOnline("https://web.spaggiari.eu/auth/app/default/AuthApi4.php?a=aLoginPwd"));
            $message .=  $icon .= "\tRegistro elettronico\n";

            sendChatAction($chat_id, TYPING);
            $icon = getMessageOnline(isOnline("https://elearning.itis.jesi.an.it/login/index.php"));
            $message .= $icon .= "\tMoodle\n";

            sendChatAction($chat_id, TYPING);
            $icon = getMessageOnline(isOnline("https://www.itismarconi-jesi.gov.it/"));
            $message .= $icon .= "\tSito della scuola\n";

             sendMessage($chat_id, $message);
             updateLastCommand($chat_id, NULL);
            } else if (!isset($updates['callback_query']) && !is_null($message))
            { 
                remove_keyboard($chat_id, "Mi dispiace, <b>$message</b> non è un comando valido.");
                updateLastCommand($chat_id, NULL);
            }

              if (!$message && !isset($updates['callback_query'])) {
                $n_users = count($utenti);

                $result = $mysqli->query("SELECT * FROM db_deleted");
                $n_deleted_users =  $result->num_rows;

                $result = $mysqli->query("SELECT * FROM db_circolari");
                $n_circolari=  $result->num_rows;

                $result = $mysqli->query("SELECT * FROM db_eventi");
                $n_eventi =  $result->num_rows;
                ?>
                <div class="row">

                <div class="col s3">
                <div class="card-panel">
                <span class="green-text text-darken-2">Utenti registrati: <b><?php echo $n_users?></b></span>
                </div>
                </div>

                <div class="col s3">
                <div class="card-panel">
                <span class="red-text text-darken-2">Utenti cancellati: <b><?php echo $n_deleted_users?></b></span>
                </div>
                </div>

                <div class="col s3">
                <div class="card-panel">
                <span class="blue-text text-darken-2">Numero circolari: <b><?php echo $n_circolari?></b></span>
                </div>
                </div>

                <div class="col s3">
                <div class="card-panel">
                <span class="blue-text text-darken-2">Numero eventi: <b><?php echo $n_eventi?></b></span>
                </div>
                </div>

                </div>
            
                <table class="centered">
                <thead>
                <tr>
                <th>Circolare</th>
                <th>Data</th>
                </tr>
                </thead>
                <tbody>

                <?php
                $dom = new DomDocument();
                $content = Download_Html(ITIS_URL . "/docenti-ata/circolari-e-comunicazioni.html");
                @$dom->loadHTML($content);
                $table = $dom->getElementsByTagName('table')->item(0); //Circolari
                $rows = $table->getElementsByTagName('tr');
                $circolari = array();
                foreach ($rows as $row) {
                    $link_circolare = $row->getElementsByTagName('a')->item(0)->getattribute('href');
                    $tds = $row->getElementsByTagName('td'); // get the columns in this row
                    $title = $tds->item(0)->nodeValue; // Nome
                    $data = $tds->item(1)->nodeValue; // Data
                    if (strlen($title) > 0) {
                        $title = trim(str_replace("\n", "", $title));
                        $data = trim(str_replace("\n", "", $data));
                        $title_esc = $mysqli->real_escape_string($title);
                        $data = $mysqli->real_escape_string($data);
                        $link_circolare = ITIS_URL . $link_circolare;
                        $t_title = strlen($title) > 100 ? substr($title,0,100)."..." : $title;
                        echo "<tr>\n<td><a href='$link_circolare' target=\"_blank\">$t_title</a></td>\n<td>$data<td>\n</tr>\n";
                        $result = $mysqli->query("SELECT * FROM db_circolari where titolo='$title_esc' AND data='$data'", $link);
                        $num_rows = $result->num_rows;
                        if ($num_rows == 0) {
                            $dom = new DomDocument();
                            $content = Download_Html($link_circolare);
                            @$dom->loadHTML($content);
                            $table = $dom->getElementsByTagName('table');
                            $table_length = $table->length - 2;
                            if ($table_length >= 0) {
                                $table = $table->item($table_length);
                                $lastrow = $table->getElementsByTagName('tr')->item(0);
                                $tds = $lastrow->getElementsByTagName('td');
                                $allegato = $tds->item(0)->getElementsByTagName('a')->item(0);
                                if (!is_null($allegato)) {
                                    $allegato = $allegato->getattribute('href');
                                    $result = $mysqli->query("INSERT INTO db_circolari (titolo, data, allegato) VALUES ('$title_esc', '$data', '$allegato')");
                                    echo $result."-> $title_esc <br>";
                                    if ($table->getElementsByTagName('tr')->length == 1) {
                                        $circolare = array("title" => $title, "allegato" => $allegato);
                                    } else{
                                        
                                        $allegati = array();
                                        for ($i = 1; $i < $table->getElementsByTagName('tr')->length; $i++)
                                        {
                                            $tmpAllegatoOgg = $table->getElementsByTagName('tr')->item($i);
                                            $tmpDatiAllegato = $tmpAllegatoOgg->getElementsByTagName('td')->item(0);
                                            $tmpDatiAllegato2= $tmpDatiAllegato->getElementsByTagName('a')->item(1);
                                            $AllegatoTitolo = $tmpDatiAllegato2->nodeValue;
                                            $AllegatoLink = $tmpDatiAllegato2->getattribute('href');
                                            $AllegatoId = generateRandomString();
                                            $allegato2 = array("title" => $AllegatoTitolo, "allegato" => $AllegatoLink, "id" => $AllegatoId);
                                            $result = $mysqli->query("INSERT INTO db_allegati (titolo, allegato, id, circolare) VALUES ('$AllegatoTitolo', '$AllegatoLink', '$AllegatoId' , '$title_esc')");
                                            $allegati[] = $allegato2;
                                        }

                                        $circolare = array("title" => $title, "allegato" => $allegato, "allegati" => $allegati);
                                    }
                                    $circolari[] = $circolare;
                                }
                            }
                        }
                    }
                }

                ?>
                
                </tbody>
                </table>

                <?php
                $circolari = array_reverse($circolari);

                foreach ($utenti as & $utente) {
                    foreach ($circolari as $circolare) {
                        if (!$circolare['allegati']) { 
                            sendDocument($utente['chat_id'], $circolare['allegato'], $circolare['title']);
                        } else {
                                $keyboard = array();
                                    foreach ($circolare['allegati'] as &$allegato) {
                                        $keyboard[] = array(array("text" => "\xF0\x9F\x93\x8E ".$allegato['title'], "callback_data" => "all://".$allegato['id']));
                         
                               }
                                sendInlineKeyboardwithDocument($utente['chat_id'], $circolare['allegato'], $circolare['title'], $keyboard);
                        }
                    }

                }
                
                ?>

                <br><br>
                
                <table class="centered">
                <thead>
                <tr>
                <th>Evento</th>
                <th>Data</th>
                </tr>
                </thead>
                <tbody>

                <?php
                $table = $dom->getElementsByTagName('table')->item(0); //Eventi
                $rows = $table->getElementsByTagName('tr');
                $eventi = array();
                foreach ($rows as $row) {
                    if ($row->nodeValue != "Nessun evento") {
                        $data_inizio = $row->getElementsByTagName('span')->item(0)->nodeValue;
                        $p_link = $row->getElementsByTagName('span')->length;
                        if ($p_link > 2) {
                            $data_fine_ora = $row->getElementsByTagName('span')->item(1)->nodeValue;
                            $testo = $row->getElementsByTagName('span')->item(2)->nodeValue;
                            $link_evento = $row->getElementsByTagName('span')->item(2)->getElementsByTagName('a')->item(0)->getattribute('onclick');
                        } else {
                            $data_fine_ora = $data_inizio;
                            $testo = $row->getElementsByTagName('span')->item(1)->nodeValue;
                            $link_evento = $row->getElementsByTagName('span')->item(1)->getElementsByTagName('a')->item(0)->getattribute('onclick');
                        }
                        $link_evento = getLinkEvento($link_evento);
                        echo "<tr>\n<td><a href='$link_evento' target=\"_blank\">$testo </a></td>\n<td> $data_inizio - $data_fine_ora </td>\n</tr>\n";
                        $tag_array;
                        if (strpos($data_fine_ora, ':') !== false) {
                            $tag_array = "ora";
                        } else {
                            $tag_array = "data_fine";
                        }
                        $evento = array("data_inizio" => $data_inizio, $tag_array => $data_fine_ora, "testo" => $testo, "link" => $link_evento);
                        $eventi[] = $evento;
                    }
                }

                ?>
                </tbody>
                </table>

                <?php
                $eventi = array_reverse($eventi);
                foreach ($eventi as & $evento) {
                    $data_inizio = $evento['data_inizio'];
                    $testo = $evento['testo'];
                    $result = $mysqli->query("SELECT * FROM db_eventi where evento='$testo' AND data_inizio='$data_inizio'", $link);
                    $num_rows = $result->num_rows;
                    if ($num_rows == 0) {
                        $dom = new DomDocument();
                        $content = Download_Html($evento['link']);
                        @$dom->loadHTML($content);
                        $table = $dom->getElementsByTagName('div');
                        $circolare_allegata;
                        foreach ($table as $row) {
                            if ($row->getAttribute("class") == "jev_evdt_desc") {
                                $nodo = $row->getElementsByTagName("a")->item(0)->nodeValue;
                                if (strpos(strtolower($nodo), 'circolare') !== false) $circolare_allegata = strtolower($nodo);
                                break;
                            }
                        }
                        if (!is_null($circolare_allegata)) {
                            $numero_circolare = filter_var($circolare_allegata, FILTER_SANITIZE_NUMBER_INT);
                        }
                        if (!is_null($evento['ora'])) {
                            $keyboard = array();
                            $ora = $evento['ora'];
                            $message = "\xF0\x9F\x93\x86 <b> $data_inizio </b>\n";
                            $message.= "\xF0\x9F\x95\x90 <b> $ora </b>\n";
                            $message.= "\xE2\x9C\x8F $testo \n";
                            if (!is_null($circolare_allegata))  $keyboard[] = array(array("text" => "\xF0\x9F\x93\x8E Circolare N. $numero_circolare", "callback_data" => "circolare://$numero_circolare"));
                            $testo = $mysqli->real_escape_string($testo);
                            $evento_link = $evento['link'];
                            $result = $mysqli->query("INSERT INTO db_eventi (evento, data_inizio, ora, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$ora', '$evento_link', '$numero_circolare')");

                            $keyboard[] = array(array("text" => "\xF0\x9F\x8C\x8D Guarda nel sito", "url" => $evento_link));

                            foreach ($utenti as & $utente) {
                                sendInlineKeyboard($utente['chat_id'], $message, $keyboard);
                            }

                        } else {
                            $keyboard = array();
                            $data_fine = $evento['data_fine'];
                            $message = "\xF0\x9F\x93\x86 <b>" . $data_inizio . " - " . $data_fine . "</b>\n";
                            $message.= "\xE2\x9C\x8F $testo \n";
                            if (!is_null($circolare_allegata))  $keyboard[] = array(array("text" => "\xF0\x9F\x93\x8E Circolare N. $numero_circolare", "callback_data" => "circolare://$numero_circolare"));
                            $testo = $mysqli->real_escape_string($testo);
                            $evento_link = $evento['link'];
                            $result = $mysqli->query("INSERT INTO db_eventi (evento, data_inizio, data_fine, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$data_fine', '$evento_link', '$numero_circolare')");
                            $keyboard[] = array(array("text" => "\xF0\x9F\x8C\x8D Guarda nel sito", "url" => $evento_link));                    

                            foreach ($utenti as & $utente) {
                                sendInlineKeyboard($utente['chat_id'], $message, $keyboard);
                            }
                        }
                    }
                }
            }

            $dom = new DomDocument();
            $content = Download_Html(ITIS_URL . "/news.html");
            @$dom->loadHTML($content);
            $table = $dom->getElementById('itemListPrimary'); //Tabella News
            $last_news = $table->getElementsByTagName('div')->item(0);
            
            $title = $last_news->getElementsByTagName('h3')->item(0)->getElementsByTagName("a")->item(0); //Titolo
            $link = ITIS_URL.$title->getattribute('href'); //Link
            $title = trim($title->nodeValue); //Titolo testo
            $time = $last_news->getElementsByTagName('span')->item(0)->nodeValue;
            $time = trim(str_replace("Ultima modifica il ", "", $time)); //Orario

            $oldNews = getLastNews();

            if ($oldNews['time'] != $time || $oldNews['title'] != $title)
            {
                sendMessage("150543610", "Nuova News:\n<b>Titolo:</b> $title\n<b>Orario:</b> $time\n<b>Link:</b> $link");
                echo "\xF0\x9F\x93\xB0 Nuova News:\n<b>Titolo:</b> $title\n<b>Orario:</b> $time\n<b>Link:</b> $link";
                updateLastNews($title, $time);
            }

            if(isset($_GET["usr"]))
            {
                $usr = $_GET["usr"];
                if ($usr == SHOW_USER)
                {
                    $tutti_utenti = array();

                    foreach ($utenti as & $utente) {
                        $tutti_utenti[] = $utente['chat_id'];
                    }

                    $result = $mysqli->query("SELECT * FROM db_deleted");
                    $deleted = array();
                    while ($row = $result->fetch_assoc()) {
                        $deleted[] = $row['chat_id'];
                        $tutti_utenti[] = $row['chat_id'];
                    }

                    echo "<br><br>\n";
                    echo "<table class=\"centered\">\n";
                    echo "<thead>\n";
                    echo "<tr>\n";
                    echo "<th>ID</th>\n";
                    echo "<th>Username</th>\n";
                    echo "<th>Nome</th>\n";
                    echo "<th>Cognome</th>\n";
                    echo "<th>Stato</th>\n";
                    echo "</tr>\n";
                    echo "</thead>\n";
                    echo "<tbody>\n";

                    
                    foreach ($tutti_utenti as & $id) {
                        if (intval($id) > 0) {
                            $info = json_decode(getChat($id));
                            $info = $info->{'result'};

                            $first_name = $info->{'first_name'};
                            $last_name = $info->{'last_name'};
                            $user_name = $info->{'username'};
                            $status = array_search($id, $deleted);

                            echo "<tr>\n";
                            echo "<td>$id</td>\n";
                            echo "<td>$user_name</td>\n";
                            echo "<td>$first_name</td>\n";
                            echo "<td>$last_name</td>\n";
                            if ($status !== false)
                                echo "<td>❌</td>\n";
                            else echo "<td>✔️</td>\n";

                            echo "</tr>";
                        }
                }

                  echo "</tbody>\n";
                  echo "</table>\n";
            }
          }
            $mysqli->close();
            ?>
      </div>
   </body>

   <script>

(function($){
  $(function(){

    $('.button-collapse').sideNav();

  }); // end of document ready
})(jQuery); // end of jQuery name space

   </script>
</html>