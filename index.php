<html>

<head>
<title>ITIS Marconi Jesi Bot</title>
</head>

<body>

<?php
include 'simple_html_dom.php';
define("Token", "342594609:AAFKHHMTxwsqqVwf5kHmOeRb3BZcslSrOBk");
define("Google_Api_Key", "AIzaSyDSgH3wS8BceILLAq6I1c8pgOuoEaf09Mg");
define("Telegram", "https://api.telegram.org/bot" . Token);
define("ITIS_URL", "http://www.itismarconi-jesi.gov.it");
date_default_timezone_set('Europe/Rome');
define("message_circolari", "Puoi cercare circolari scrivendo ad esempio <b>\"Circolare 220\"</b>,
<b>\"Circolare sciopero\"</b>, <b>\"Circolari di ieri\"</b>, <b>\"Circolari di oggi\"</b> o <b>\"Circolari del 4/03/17\"</b>.");
function Download_Html($url) {
    $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
    $ch = curl_init();
    // set user agent
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // grab content from the website
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}
function sendDocument($chat_id, $document, $caption) {
    file_get_contents(Telegram . "/sendDocument?chat_id=$chat_id&document=$document&caption=" . urlencode($caption));
}
function sendMessage($chat_id, $message) {
    file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&parse_mode=HTML");
}
function remove_keyboard($chat_id, $message) {
    $resp = array("remove_keyboard" => true);
    $reply = json_encode($resp);
    file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply));
}
function sendKeyboard($chat_id, $message, $keyboard) {
    $resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
    $reply = json_encode($resp);
    file_get_contents(Telegram . "/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&reply_markup=" . urlencode($reply));
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
    //header('HTTP/1.1 500 Internal Server Error');
    
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
if ($message == "/start" || $message == "/start@itismarconijesibot") {
    $result = mysql_query("SELECT * FROM db_bot_telegram_itis where chat_id='$chat_id'", $link);
    $num_rows = mysql_num_rows($result);
    if ($num_rows == 0) {
        $result = mysql_query("INSERT INTO db_bot_telegram_itis (chat_id) VALUES ('$chat_id')");
        if ($result == 1) sendMessage($chat_id, "Benvenuto! Da questo momento iniziarai a ricevere notifiche di nuove circolari, eventi e altre comunicazioni \xF0\x9F\x98\x89");
        else sendMessage($chat_id, "Ops...c'è stato un problema nell'avviare il bot \xF0\x9F\x98\x94");
    } else sendMessage($chat_id, "Sei già stato aggiunto \xF0\x9F\x98\x89");
} else if ($message == "/stop" || $message == "/stop@itismarconijesibot") {
    mysql_query("DELETE FROM db_bot_telegram_itis where chat_id='$chat_id'", $link);
    sendMessage($chat_id, "Mi dispiace vederti andar via \xF0\x9F\x98\xA2");
} else if ($message == "/orario" || $message == "/orario@itismarconijesibot") {
    $array = array(array("Studenti"), array("Docenti"), array("Laboratori"), array("Recupero/Potenziamento"));
    sendKeyboard($chat_id, "Seleziona un orario: ", $array);
} else if ($message == "Studenti") {
    sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itis_marconi_jesi_Orario_Classi_2016-2017_DEFINITIVO-2410.pdf", "Orario Studenti");
    remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 24/10/2016");
} else if ($message == "Docenti") {
    sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itis_marconi_jesi_Orario_Docenti_2016-2017_DEFINITIVO-2410.pdf", "Orario Docenti");
    remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 24/10/2016");
} else if ($message == "Recupero/Potenziamento") {
    sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itismarconi-jesi_orario_potenziamento_febbraio-marzo_2017.pdf", "Orario Recupero/Potenziamento");
    remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 3/3/2017");
} else if ($message == "Laboratori") {
    sendDocument($chat_id, ITIS_URL . "/images/stories/orario/online/itis_marconi_jesi_Orario_Laboratori_2016-2017_DEFINITIVO-2410.pdf", "Orario Laboratori");
    remove_keyboard($chat_id, "\xF0\x9F\x93\x86 Aggiornato al: 24/10/2016");
} else if ($message == "/ore" || $message == "/ore@itismarconijesibot") {
    $reply = "<b>Suddivisione oraria dei giorni</b>\n";
    $reply.= "\xF0\x9F\x93\x85 <b>Lunedì - Mercoledì - Venerdì</b>\n";
    $reply.= "\xF0\x9F\x95\x97 <b>1° Ora</b>: 08.00 - 09.00\n";
    $reply.= "\xF0\x9F\x95\x98 <b>2° Ora</b>: 09.00 - 10.00\n";
    $reply.= "\xF0\x9F\x95\x99 <b>Intervallo</b>: 10.00 - 10.15\n";
    $reply.= "\xF0\x9F\x95\x99 <b>3° Ora</b>: 10.15 - 11.15\n";
    $reply.= "\xF0\x9F\x95\x9A <b>4° Ora</b>: 11.15 - 12.15\n";
    $reply.= "\xF0\x9F\x95\x9B <b>5° Ora</b>: 12.15 - 13.15\n";
    $reply.= "\n";
    $reply.= "\xF0\x9F\x93\x85 <b>Martedì - Giovedì - Sabato</b>\n";
    $reply.= "\xF0\x9F\x95\x97 <b>1° Ora</b>: 08.00 - 08.50\n";
    $reply.= "\xF0\x9F\x95\x98 <b>2° Ora</b>: 08.50 - 9.40\n";
    $reply.= "\xF0\x9F\x95\x99 <b>3° Ora</b>: 9.40 - 10.30\n";
    $reply.= "\xF0\x9F\x95\x99 <b>Intervallo</b>: 10.30 - 10.45\n";
    $reply.= "\xF0\x9F\x95\x9A <b>4° Ora</b>: 10.45 - 11.35\n";
    $reply.= "\xF0\x9F\x95\x9B <b>5° Ora</b>: 11.35 - 12.25\n";
    $reply.= "\xF0\x9F\x95\x90 <b>6° Ora</b>: 12.25 - 13.15";
    sendMessage($chat_id, $reply);
} else if ($message == "/id" || $message == "/id@itismarconijesibot" ) {
    sendMessage($chat_id, "Il tuo chat id è: " . $chat_id);
} else if ($message == "/opensuorce") {
    sendMessage($chat_id, "Questo bot è opensuorce \xF0\x9F\x8E\x86 Puoi visualizzare il sorgente su <a href='https://github.com/simoneluconi/itismarconijesibot/'>github</a> e contribuire al suo sviluppo. In alternativa puoi contattarmi a @simoneluconi.");
} else if ($message == "/circolari" || $message == "/circolari@itismarconijesibot") {
    sendMessage($chat_id, message_circolari);
} else if (strpos(strtolower($message), '/circolare_') !== false) {
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
} else if (strpos(strtolower($message), 'circolare') !== false) {
    $tmp = explode(" ", $message);
    if (count($tmp) > 1) {
        $numero = intval($tmp[1]);
        if ($numero == 0) {
            //sendMessage($chat_id, "Formato del messaggio non valido \xF0\x9F\x98\x94 \nDevi scrivere ad esempio \"Circolare 220\"");
            $message_escaped = mysql_real_escape_string($message);
            $message_escaped = str_replace("..", "", $message_escaped);
            $result = mysql_query("SELECT * FROM db_circolari WHERE titolo LIKE '$message_escaped%'");
            if (mysql_num_rows($result) > 0) {
                remove_keyboard($chat_id, "Invio circolare:");
                while ($row = mysql_fetch_assoc($result)) {
                    sendDocument($chat_id, $row['allegato'], $row['titolo']);
                }
            } else {
                $result = mysql_query("SELECT * FROM db_circolari");
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
                if ($num_rows == 1) sendMessage($chat_id, "Ho trovato questa circolare:");
                else sendMessage($chat_id, "Ho trovato queste circolari:");
                while ($row = mysql_fetch_assoc($result)) {
                    sendDocument($chat_id, $row['allegato'], $row['titolo']);
                }
            }
        }
    } else errCircolari($chat_id);
} else if (strpos(strtolower($message), 'circolari') !== false) {
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
            $result = mysql_query("SELECT * FROM db_circolari WHERE data = '$oggi_str'");
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
    } else errCircolari($chat_id);
} else if ($message == "/help" || $message == "/help@itismarconijesibot") {
    $message = "Se hai bisogno di informazioni o vuoi fare delle richieste puoi contattare @simoneluconi";
    sendMessage($chat_id, $message);
} else if (strlen($message) == 0) {
    $result = mysql_query("SELECT * FROM db_bot_telegram_itis");
    $utenti = array();
    while ($row = mysql_fetch_assoc($result)) {
        $utenti[] = $row;
    }

    $n_users = count($utenti);
    echo "Utenti registrati: <b> $n_users </b> <br><br>";

    echo "Controllo circolari...<br /><br />";
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
            echo "<b>Circolare:</b> " . $title . "<br /><b>Data:</b> " . $data . "<br /><b>Link:</b><a href='$link_circolare'>" . $link_circolare . "</a><br /><br />";
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
                        echo "<b>Allegato:</b><a href='$allegato'>$allegato</a><br /><br />";
                        $circolare = array("title" => $title, "allegato" => $allegato);
                        $circolari[] = $circolare;
                    }
                }
            }
        }
    }
    $circolari = array_reverse($circolari);
    foreach ($utenti as & $utente) {
        foreach ($circolari as & $circolare) {
            sendDocument($utente['chat_id'], $circolare['allegato'], $circolare['title']);
        }
    }

    echo "Controllo eventi...<br />";
    $table = $dom->getElementsByTagName('table')->item(1); //Eventi
    $rows = $table->getElementsByTagName('tr');
    $eventi = array();
    foreach ($rows as $row) {
        $data_inizio = $row->getElementsByTagName('span')->item(0)->nodeValue;
        $data_fine_ora = $row->getElementsByTagName('span')->item(1)->nodeValue;
        $testo = $row->getElementsByTagName('span')->item(2)->nodeValue;
        $link_evento = $row->getElementsByTagName('span')->item(2)->getElementsByTagName('a')->item(0)->getattribute('onclick');
        $link_evento = getLinkEvento($link_evento);
        echo "<b> $data_inizio - $data_fine_ora </b>:$testo => <a href='$link_evento'>$link_evento</a> <br /> ";
        $tag_array;
        if (strpos($data_fine_ora, ':') !== false) {
            $tag_array = "ora";
        } else {
            $tag_array = "data_fine";
        }
        $evento = array("data_inizio" => $data_inizio, $tag_array => $data_fine_ora, "testo" => $testo, "link" => $link_evento);
        $eventi[] = $evento;
    }
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
                $evento_link = $evento['link'];
                $message.= "\xF0\x9F\x94\x97	<a href='$evento_link'>Link all'evento</a>";
                $testo = mysql_real_escape_string($testo);
                $result = mysql_query("INSERT INTO db_eventi (evento, data_inizio, ora, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$ora', '$evento_link', $numero_circolare)");
                foreach ($utenti as & $utente) {
                    sendMessage($utente['chat_id'], $message);
                }
            } else {
                $data_fine = $evento['data_fine'];
                $message = "\xF0\x9F\x93\x86 <b>" . $data_inizio . " - " . $data_fine . "</b>\n";
                $message.= "\xE2\x9C\x8F $testo \n";
                if (!is_null($comando_circolare)) $message.= "\xF0\x9F\x93\x8E	Circolare allegata: $comando_circolare\n";
                $evento_link = $evento['link'];
                $message.= "\xF0\x9F\x94\x97	<a href='$evento_link'>Link all'evento</a>";
                $result = mysql_query("INSERT INTO db_eventi (evento, data_inizio, data_fine, link, circolare_allegata) VALUES ('$testo', '$data_inizio', '$data_fine', '$evento_link', $numero_circolare)");
                foreach ($utenti as & $utente) {
                    sendMessage($utente['chat_id'], $message);
                }
            }
        }
    }
} else sendMessage($chat_id, "Mi dispiace, <b>$message</b> non è un comando valido.");
?>

</body>
</html>