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
            <ul id="nav-mobile" class="right hide-on-med-and-down">
               <li><a href="https://t.me/itismarconijesibot">Aggiungi a Telegram</a></li>
               <li><a href="https://t.me/simoneluconi">Contattami</a></li>
            </ul>
         </div>
      </nav>
      <div class="container">
         <?php
            include 'simple_html_dom.php';
            include 'addCal.php';
            include 'random.php';
            define("Token", "342594609:AAFKHHMTxwsqqVwf5kHmOeRb3BZcslSrOBk");
            define("Google_Api_Key", "AIzaSyDSgH3wS8BceILLAq6I1c8pgOuoEaf09Mg");
            define("Telegram", "https://api.telegram.org/bot" . Token);
            define("ITIS_URL", "http://www.itismarconi-jesi.gov.it");
            define("HOST_URL", "http://simoneluconi.altervista.org");
            define("TYPING", "typing");
            define("UPLOAD_PHOTO", "upload_photo");
            define("UPLOAD_DOCUMENT", "upload_document");

            date_default_timezone_set('Europe/Rome');
            define("message_circolari", "Puoi cercare circolari scrivendo ad esempio <b>\"Circolare 220\"</b>, <b>\"Circolare sciopero\"</b>, <b>\"Circolari di ieri\"</b>, <b>\"Circolari di oggi\"</b> o <b>\"Circolari del 4/03/17\"</b>.");
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
            function sendDocument($chat_id, $document, $caption) {
                file_get_contents(Telegram . "/sendDocument?chat_id=$chat_id&document=$document&caption=" . urlencode($caption));
            }
            
            function sendMessage($chat_id, $message) {
                file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&parse_mode=HTML");
            }
            
            function sendPhoto($chat_id, $link) {
                file_get_contents(Telegram . "/sendPhoto?chat_id=$chat_id&photo=" . urlencode($link));
            }

            function sendChatAction($chat_id, $action) {
                file_get_contents(Telegram . "/sendChatAction?chat_id=$chat_id&action=$action");
            }
            
            function remove_keyboard($chat_id, $message) {
                $resp = array("remove_keyboard" => true);
                $reply = json_encode($resp);
                file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply) . "&parse_mode=HTML");
            }
            function sendKeyboard($chat_id, $message, $keyboard) {
                $resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
                $reply = json_encode($resp);
                file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply) . "&parse_mode=HTML");
            }

            function sendInlineKeyboard($chat_id, $message, $keyboard) {
                $resp = array("inline_keyboard" => $keyboard);
                $reply = json_encode($resp);
                file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=".urlencode($reply) . "&parse_mode=HTML");
            }

            function errCircolari($chat_id) {
                sendMessage($chat_id, "\xE2\x9D\x97 Formato del messaggio non valido!");
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
            $link = mysql_connect("localhost", "simoneluconi", "");
            mysql_select_db("my_simoneluconi", $link);
            
            
            function updateLastCommand($chat_id, $command)
            {
                if ($command) {
                $command = mysql_real_escape_string($command);
                $result = mysql_query("UPDATE db_bot_telegram_itis SET last_command = '$command' where chat_id='$chat_id'");
                } else 
                {
                $result = mysql_query("UPDATE db_bot_telegram_itis SET last_command = NULL where chat_id='$chat_id'");
                }
            }
            
            function getLastCommand($chat_id)
            {
                $result = mysql_query("SELECT last_command from db_bot_telegram_itis where chat_id='$chat_id'");
                $row = mysql_fetch_assoc($result);
                return $row['last_command'];
            }
            
            if ($message)
            {
                $last_command = getLastCommand($chat_id);
                if ($last_command != "/circolari" && $last_command != "/circolari@itismarconijesibot")
                    updateLastCommand($chat_id, $message);
            }
            
            $date = date('Y/m/d H:i:s');
            $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
            $ip = mysql_real_escape_string($ip);
            $hostname = mysql_real_escape_string($details->hostname);
            $city = mysql_real_escape_string($details->city);
            $region = mysql_real_escape_string($details->region);
            $country = mysql_real_escape_string($details->country);
            $org = mysql_real_escape_string($details->org);
            $postal = mysql_real_escape_string($details->postal);
            $result = mysql_query("INSERT INTO db_bot_telegram_itis_accessi (ip, hostname, city, region, country, org, postal, time) VALUES ('$ip', '$hostname', '$city', '$region', '$country', '$org', '$postal', '$date')");
            
                if (strpos($last_command, 'Studenti') !== false)
                {
                sendChatAction($chat_id, UPLOAD_PHOTO);
                 $tmp = explode(" ", $message);
                $url = "/files/orario/orario.php";
                $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?classe=".$tmp[1]));
                if ($response->link) {
                    remove_keyboard($chat_id, "Ti invio l'orario della classe ". $tmp[1]);
                    sendPhoto($chat_id, $response->link);
                } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario della classe ". $tmp[1]);
                updateLastCommand($chat_id, NULL);
                } else if (strpos($last_command, 'Laboratori') !== false)
                {
                    sendChatAction($chat_id, UPLOAD_PHOTO);
                    $tmp = str_replace(' ', '%20' , $message);
                    $url = "/files/orario/orario.php";
                    $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?laboratorio=".$tmp));
                if ($response->link) {
                    remove_keyboard($chat_id, "Ti invio l'orario del ". $message);
                    sendPhoto($chat_id, $response->link);
                } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario del ". $message);
                    updateLastCommand($chat_id, NULL);                
                } else if (strpos($last_command, 'Docenti') !== false)
                {
                    sendChatAction($chat_id, UPLOAD_PHOTO);
                    $tmp = str_replace(' ', '%20' , $message);
                    $url = "/files/orario/orario.php";
                    $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?docente=".$tmp));
                if ($response->link) {
                    sendChatAction($chat_id, UPLOAD_PHOTO);
                    remove_keyboard($chat_id, "Ti invio l'orario del docente ". $message);
                    sendPhoto($chat_id, $response->link);
                } else remove_keyboard($chat_id, "Mi dispiace, non ho trovato l'orario del docente". $message);
                    updateLastCommand($chat_id, NULL);
                } 
                else if ($message == "/start" || $message == "/start@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
                $result = mysql_query("SELECT * FROM db_bot_telegram_itis where chat_id='$chat_id'", $link);
                $num_rows = mysql_num_rows($result);
                if ($num_rows == 0) {
                    $result = mysql_query("INSERT INTO db_bot_telegram_itis (chat_id) VALUES ('$chat_id')");
                    if ($result == 1) sendMessage($chat_id, "Benvenuto! Da questo momento iniziarai a ricevere notifiche di nuove circolari, eventi e altre comunicazioni \xF0\x9F\x98\x89");
                    else sendMessage($chat_id, "Ops...c'è stato un problema nell'avviare il bot \xF0\x9F\x98\x94");
                } else sendMessage($chat_id, "Hei $user_name mi ricordo di te! Bentornato! \xF0\x9F\x98\x83");
                 updateLastCommand($chat_id, NULL);
            } else if ($message == "/orario" || $message == "/orario@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
                $array = array(array("Studenti"), array("Docenti"), array("Laboratori"), array("Recupero/Potenziamento"));
                sendKeyboard($chat_id, "Seleziona un orario: ", $array);
            } else if ($message == "Studenti" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $url = "/files/orario/orario.php";
                $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?tipo_orario=Studenti"));
            
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
            
                sendKeyboard($chat_id, "Seleziona una classe", $array);
            
            }  else if ($message == "Laboratori" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $url = "/files/orario/orario.php";
                $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?tipo_orario=Laboratori"));
            
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
                sendKeyboard($chat_id, "Seleziona un laboratorio", $array);
            }  else if ($message == "Docenti" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, TYPING);
                $url = "/files/orario/orario.php";
                $response = json_decode(file_get_contents(HOST_URL."/telegram/itisbot/orario.php?tipo_orario=Docenti"));
            
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
                sendKeyboard($chat_id, "Seleziona un docente", $array);
            }
             else if ($message == "Recupero/Potenziamento" && (strpos($last_command, '/orario') !== false)) {
                sendChatAction($chat_id, UPLOAD_DOCUMENT);
                sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itismarconi-jesi_orario_potenziamento_aprile-maggio-giugno-2017.pdf", "Orario Recupero/Potenziamento");
                remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 31/3/2017");
                updateLastCommand($chat_id, NULL);
            } else if ($message == "/calendario" || $message == "/calendario@itismarconijesibot") {
                sendChatAction($chat_id, TYPING);
                $reply = "<b>Calendario Scolastico 2016-2019</b>\n";
                $reply.= "\xF0\x9F\x93\x85 Inizio lezioni: <b>Giovedì 15 Settembre 2016</b>\n";
                $reply.= "\xF0\x9F\x93\x85 Fine lezioni: <b>Giovedì 8 Giugno 2017</b>\n";
                $reply.= "\n";
                $reply.= "\xF0\x9F\x8E\x89 La scuola resterà chiusa nelle seguenti <b>giornate di festività</b>:\n";
                $reply.= "• 22 Settembre 2016 (festa del Patrono)\n";
                $reply.= "• 01 Novembre 2016 (festa di tutti i Santi)\n";
                $reply.= "• 02 Novembre 2016 (commemorazione dei defunti)\n";
                $reply.= "• 08 Dicembre 2016 (Immacolata Concezione)\n";
                $reply.= "• 25 Dicembre 2016 (Santo Natale)\n";
                $reply.= "• 26 Dicembre 2016 (Santo Stefano)\n";
                $reply.= "• 01 Gennaio 2017 (Capodanno)\n";
                $reply.= "• 06 Gennaio 2017 (Epifania)\n";
                $reply.= "• 07 Gennaio 2017 (scelta dalla scuola)\n";
                $reply.= "• 17 Aprile 2017 (Lunedì dell'Angelo)\n";
                $reply.= "• 25 Aprile 2017 (anniversario della Liberazione)\n";
                $reply.= "• 01 Maggio 2017 (festa del Lavoro)\n";
                $reply.= "• 02 Giugno 2017 (festa nazionale della Repubblica)\n";
                $reply.= "\n";
                $reply.= "\xF0\x9F\x8E\x84 <b>Vacanze di Natale</b>: dal 24 Dicembre 2016 al 5 Gennaio 2017\n";
                $reply.= "\xF0\x9F\x90\xB0 <b>Vacanze di Pasqua</b>: dal 13 Aprile 2017 al 18 Aprile 2017\n";
                sendMessage($chat_id, $reply);
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
                sendMessage($chat_id, message_circolari);
            } else if (strpos(strtolower($message), '/circolare_') !== false) {
                sendChatAction($chat_id, UPLOAD_DOCUMENT);
                $cerca = str_replace("@itismarconijesibot", "", $message);
                $cerca = str_replace("/circolare_", "", $message);
                $cerca = "circolare n." . $cerca;
                $cerca = mysql_real_escape_string($cerca);
                $result = mysql_query("SELECT * FROM db_circolari WHERE titolo LIKE '$cerca%'");
                $num_rows = mysql_num_rows($result);
                if ($num_rows == 0) {
                    sendMessage($chat_id, "Mi dispiace, non ho trovato nessuna circolare numero $cerca \xF0\x9F\x98\x94");
                } else {
                    while ($row = mysql_fetch_assoc($result)) {
                        sendDocument($chat_id, $row['allegato'], $row['titolo']);
                    }
                }
                updateLastCommand($chat_id, NULL);
            } else if (strpos(strtolower($message), '/allegato_') !== false) {
                sendChatAction($chat_id, UPLOAD_DOCUMENT);
                $cerca = str_replace("@itismarconijesibot", "", $message);
                $cerca = str_replace("/allegato_", "", $message);
                $cerca = mysql_real_escape_string($cerca);
                $result = mysql_query("SELECT * FROM db_allegati WHERE id='$cerca'");
                $num_rows = mysql_num_rows($result);
                if ($num_rows == 0) {
                    sendMessage($chat_id, "Mi dispiace, non ho trovato nessuna allegato con questo ID \xF0\x9F\x98\x94");
                } else {
                    while ($row = mysql_fetch_assoc($result)) {
                        sendDocument($chat_id, $row['allegato'], $row['titolo']);
                    }
                }
                updateLastCommand($chat_id, NULL);
            }          
            else if ((strpos(strtolower($message), 'circolare') !== false ) && (strpos($last_command, '/circolari') !== false)) {
                sendChatAction($chat_id, TYPING);
                $tmp = explode(" ", $message);
                if (count($tmp) > 1) {
                    $numero = intval($tmp[1]);
                    if ($numero == 0) {
                        //sendMessage($chat_id, "Formato del messaggio non valido \xF0\x9F\x98\x94 \nDevi scrivere ad esempio \"Circolare 220\"");
                        $message_escaped = mysql_real_escape_string($message);
                        $message_escaped = str_replace("..", "", $message_escaped);
                        $result = mysql_query("SELECT * FROM db_circolari WHERE titolo LIKE '$message_escaped%'");
                        if (mysql_num_rows($result) > 0) {
                            sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            remove_keyboard($chat_id, "Invio circolare:");
                            while ($row = mysql_fetch_assoc($result)) {
                                sendDocument($chat_id, $row['allegato'], $row['titolo']);
                            }
                                updateLastCommand($chat_id, NULL);
                        } else {
                            $result = mysql_query("SELECT * FROM db_circolari");
                            if (mysql_num_rows($result) > 0) sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            $circolari_keyboard = array();
                            $cerca = strtolower($message);
                            $cerca = str_replace("circolare ", "", $cerca);
                            while ($row = mysql_fetch_assoc($result)) {
                                $titolo = strtolower($row['titolo']);
                                if (strpos($titolo, $cerca) !== false) {
                                    $circolari_keyboard[] = array($row['titolo']);
                                }
                            }
                            if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Non ho trovato nessuna circolare con nome $cerca \xF0\x9F\x98\x94");
                            else {
                                $n_circolari = count($circolari_keyboard);
                                if ($n_circolari == 1) sendKeyboard($chat_id, "Ho trovato una circolare: ", $circolari_keyboard);
                                else sendKeyboard($chat_id, "Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                            }
                        }
                    } else {
                        $cerca = "circolare n." . $numero;
                        $cerca = mysql_real_escape_string($cerca);
                        $result = mysql_query("SELECT * FROM db_circolari WHERE titolo LIKE '$cerca%'");
                        $num_rows = mysql_num_rows($result);
                        if ($num_rows == 0) {
                            sendMessage($chat_id, "Mi dispiace, non ho trovato nessuna circolare numero $numero \xF0\x9F\x98\x94");
                        } else {
                            sendChatAction($chat_id, UPLOAD_DOCUMENT);
                            if ($num_rows == 1) sendMessage($chat_id, "Ho trovato questa circolare:");
                            else sendMessage($chat_id, "Ho trovato queste circolari:");
                            while ($row = mysql_fetch_assoc($result)) {
                                sendDocument($chat_id, $row['allegato'], $row['titolo']);
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
                        $result = mysql_query("SELECT * FROM db_circolari WHERE data = '$ieri_str'");
                        $circolari_keyboard = array();
                        while ($row = mysql_fetch_assoc($result)) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Ieri non è uscita nessuna circolare \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    } else if ($tmp[2] == "oggi") {
                        $oggi = new DateTime();
                        $oggi = $oggi->format('d-m-Y');
                        $result = mysql_query("SELECT * FROM db_circolari WHERE data = '$oggi'");
                        $circolari_keyboard = array();
                        while ($row = mysql_fetch_assoc($result)) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Oggi non è uscita nessuna circolare \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    } else errCircolari($chat_id);
                } else if ($tmp[1] == "del") {
                    $date_circolare = DateTime::createFromFormat('d/m/y', $tmp[2]);
                    if (is_bool($date_circolare)) {
                        sendMessage($chat_id, "\xE2\x9D\x97	Formato della data non valido!");
                        sendMessage($chat_id, "La data deve essere nel formato <b>gg/mm/aa</b>");
                    } else {
                        $date_circolare_format = $date_circolare->format('d-m-Y');
                        $result = mysql_query("SELECT * FROM db_circolari WHERE data = '$date_circolare_format'");
                        $circolari_keyboard = array();
                        while ($row = mysql_fetch_assoc($result)) {
                            $circolari_keyboard[] = array($row['titolo']);
                        }
                        if (count($circolari_keyboard) == 0) sendMessage($chat_id, "Non c'è nessuna circolare in data $date_circolare_format \xF0\x9F\x98\x94");
                        else {
                            $n_circolari = count($circolari_keyboard);
                            if ($n_circolari == 1) sendKeyboard($chat_id, "Ho trovato una circolare: ", $circolari_keyboard);
                            else sendKeyboard($chat_id, "Ho trovato $n_circolari circolari: ", $circolari_keyboard);
                        }
                    }
                } else 
                {
                    updateLastCommand($chat_id, NULL);
                    errCircolari($chat_id);
                }
            } else if ($message == "/cancella" || $message == "/cancella@itismarconijesibot") {
                if ($last_command) {
                    remove_keyboard($chat_id, "Ultimo comando cancellato \xF0\x9F\x98\x89");
                    updateLastCommand($chat_id, NULL);
                } else sendMessage($chat_id, "Nessun comando da cancellare \xF0\x9F\x98\x85");
            } else 
            { 
                remove_keyboard($chat_id, "Mi dispiace, <b>$message</b> non è un comando valido.");
                updateLastCommand($chat_id, NULL);
            }

              if (!$message) {
                $result = mysql_query("SELECT * FROM db_bot_telegram_itis");
                $utenti = array();
                while ($row = mysql_fetch_assoc($result)) {
                    $utenti[] = $row;
                }
            
                $n_users = count($utenti);
            
                echo "<div class=\"row\">\n";
                echo "<div class=\"col s6\">\n";
                echo "<div class=\"card-panel\"> <span class=\"blue-text text-darken-2\"> Utenti registrati: <b> $n_users </b> </span> </div>\n";
                echo "</div>\n";
            
                echo "<table class=\"centered\"> <thead> <tr> <th>Circolare</th> <th>Data</th></tr></thead>\n";
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
                        $title_esc = mysql_real_escape_string($title);
                        $data = mysql_real_escape_string($data);
                        $link_circolare = ITIS_URL . $link_circolare;
                        $t_title = strlen($title) > 100 ? substr($title,0,100)."..." : $title;
                        echo "<tbody> <tr> <td> <a href='$link_circolare' target=\"_blank\">$t_title</a> </td> <td> $data <td> </tr> </tbody>\n";
                        $result = mysql_query("SELECT * FROM db_circolari where titolo='$title_esc' AND data='$data'", $link);
                        $num_rows = mysql_num_rows($result);
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
                                    $result = mysql_query("INSERT INTO db_circolari (titolo, data, allegato) VALUES ('$title_esc', '$data', '$allegato')");
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
                                            $result = mysql_query("INSERT INTO db_allegati (titolo, allegato, id, circolare) VALUES ('$AllegatoTitolo', '$AllegatoLink', '$AllegatoId' , '$title_esc')");
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
            
                echo "</table>\n";

                $circolari = array_reverse($circolari);

                foreach ($utenti as & $utente) {
                    foreach ($circolari as $circolare) {
                        sendDocument($utente['chat_id'], $circolare['allegato'], $circolare['title']);

                        if ($circolare['allegati'])
                        {
                            $message_allegati = "\xE2\x9D\x97 <b>Attenzione!</b> Nella precedente circolare sono presenti altri allegati";
                            foreach ($circolare['allegati'] as &$allegato) {
                                $message_allegati .= "\n• ".$allegato['title'].": /allegato_".$allegato['id'];
                            }
                            sendMessage($utente['chat_id'], $message_allegati);
                        }
                    }

                }
                
                                
                echo "<br><br>\n";
                echo "<table class=\"centered\"> <thead> <tr> <th>Evento</th> <th>Data</th> </tr> </thead>\n";
                $table = $dom->getElementsByTagName('table')->item(1); //Eventi
                $rows = $table->getElementsByTagName('tr');
                $eventi = array();
                foreach ($rows as $row) {
                    if ($row->nodeValue != "Nessun evento") {
                        $data_inizio = $row->getElementsByTagName('span')->item(0)->nodeValue;
                        $data_fine_ora = $row->getElementsByTagName('span')->item(1)->nodeValue;
                        $testo = $row->getElementsByTagName('span')->item(2)->nodeValue;
                        $link_evento = $row->getElementsByTagName('span')->item(2)->getElementsByTagName('a')->item(0)->getattribute('onclick');
                        $link_evento = getLinkEvento($link_evento);
                        echo "<tbody><tr> <td> <a href='$link_evento' target=\"_blank\">$testo </a></td> <td> $data_inizio - $data_fine_ora </td> </tr> </tbody>\n";
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
                echo "</table>\n";

                $eventi = array_reverse($eventi);
                foreach ($eventi as & $evento) {
                    $data_inizio = $evento['data_inizio'];
                    $testo = $evento['testo'];
                    $result = mysql_query("SELECT * FROM db_eventi where evento='$testo' AND data_inizio='$data_inizio'", $link);
                    $num_rows = mysql_num_rows($result);
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
                            $comando_circolare = "/circolare_" . $numero_circolare;
                        }
                        if (!is_null($evento['ora'])) {
                            $ora = $evento['ora'];
                            $message = "\xF0\x9F\x93\x86 <b> $data_inizio </b>\n";
                            $message.= "\xF0\x9F\x95\x90 <b> $ora </b>\n";
                            $message.= "\xE2\x9C\x8F $testo \n";
                            if (!is_null($comando_circolare)) $message.= "\xF0\x9F\x93\x8E	Circolare allegata: $comando_circolare\n";
                            $testo = mysql_real_escape_string($testo);
                            $evento_link = $evento['link'];
                            $result = mysql_query("INSERT INTO db_eventi (evento, data_inizio, ora, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$ora', '$evento_link', $numero_circolare)");

                            $add_evento = squarecandy_add_to_gcal($testo, $data_inizio.' '.$ora);
                            $keyboard = array(array(array("text" => "\xF0\x9F\x8C\x8D Guarda nel sito", "url" => $evento_link)),array(array("text"=> "\xF0\x9F\x93\x8C Aggiungi al calendario", "url"  => $add_evento)));

                            foreach ($utenti as & $utente) {
                                sendInlineKeyboard($utente['chat_id'], $message, $keyboard);
                            }

                        } else {
                            $data_fine = $evento['data_fine'];
                            $message = "\xF0\x9F\x93\x86 <b>" . $data_inizio . " - " . $data_fine . "</b>\n";
                            $message.= "\xE2\x9C\x8F $testo \n";
                            if (!is_null($comando_circolare)) $message.= "\xF0\x9F\x93\x8E	Circolare allegata: $comando_circolare\n";
                            $evento_link = $evento['link'];
                            $result = mysql_query("INSERT INTO db_eventi (evento, data_inizio, data_fine, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$data_fine', '$evento_link', $numero_circolare)");
                            
                            $add_evento = squarecandy_add_to_gcal($testo, $data_inizio, $data_fine);
                            $keyboard = array(array(array("text" => "\xF0\x9F\x8C\x8D Guarda nel sito", "url" => $evento_link)),array(array("text"=> "\xF0\x9F\x93\x8C Aggiungi al calendario", "url"  => $add_evento)));

                            foreach ($utenti as & $utente) {
                                sendInlineKeyboard($utente['chat_id'], $message, $keyboard);
                            }
                        }
                    }
                }
            }
            ?>
      </div>
   </body>
</html>