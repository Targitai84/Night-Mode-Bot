<?php

function handleMyChatMember($obj) {
    global $lang;
    $chat = $obj['chat'];
    $chat_id = $chat['id'];
    $new_status = $obj['new_chat_member']['status'];
    $file = GROUPS_PATH . "{$chat_id}.json";
    $cfg = json_decode(file_get_contents($file), true);
    log_write("my_chat_member event: chat $chat_id new_status=$new_status", "debug");
    $group_lang =  $cfg['lang'] ?? "EN";
    $lang = loadLang( $group_lang);
    // Bot Added
    if ($new_status === 'member') {
        global $settings;
        if (!file_exists($file)) {
            $default = $settings['default_group_settings'];
            file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_write("Created group settings for $chat_id", "info");

            sendMessage($chat_id, sprintf($lang['make_admin']));
        }
    }

    // Admin rights
    if ($new_status === 'administrator') {
        if (file_exists($file)) {
            $cfg = json_decode(file_get_contents($file), true);
            $cfg['isadmin'] = true;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_write("Bot is now admin in $chat_id", "info");

            sendMessage($chat_id, sprintf($lang['admin_now']));
        }
    }

    // Kikked
    if ($new_status === 'left' || $new_status === 'kicked') {
        if (file_exists($file)) {
            unlink($file);
            log_write("Deleted group settings for $chat_id", "info");
        }
    }
}


function handleMessage($msg) {
    global $lang;
    global $settings;

    $chat_id = $msg['chat']['id'];
    $user_id = $msg['from']['id'];
    $text = $msg['text'] ?? '';

    if (substr($text, 0, 1) !== "/") return;

    $file = GROUPS_PATH . "{$chat_id}.json";
    if (!file_exists($file)) return;
    $cfg = json_decode(file_get_contents($file), true);

    $group_lang =  $cfg['lang'];
    $lang = loadLang( $group_lang);
    $lock_to = $cfg['lock_to'];
    // Check bots rights
    if (!$cfg['isadmin']) {
        sendMessage($chat_id, sprintf($lang['admin_remeber']));
        return;
    }

    // check user rights
    if (!isAdmin($chat_id, $user_id)) {
        sendMessage($chat_id, sprintf($lang['not_allowed']));
        return;
    }

    
    //commands
    switch ($text) {
        //lang Chgange
        case (preg_match('/^\/lang\b/i', $text) ? true : false):

            $parts = explode(" ", trim($text));

            if (count($parts) == 1) {
                sendMessage($chat_id, sprintf($lang['lang_tooltip'], strtoupper($cfg['lang'])));
                break;
            }

            $new_lang = strtoupper($parts[1]);

            if (!in_array($new_lang, ["EN", "RU"])) {
                sendMessage($chat_id, sprintf($lang['lang_tooltip'], strtoupper($cfg['lang'])));
                break;
            }

            $cfg['lang'] = $new_lang;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

            $lang = loadLang($new_lang);

            sendMessage($chat_id, sprintf($lang['lang_set'], $new_lang));
            break;

        //Bot scheduler off-on
        case (preg_match('/^\/bot\b/i', $text) ? true : false):

            $parts = explode(" ", trim($text));

            // команда без аргументов
            if (count($parts) == 1) {
                sendMessage($chat_id, sprintf($lang['bot_tooltip']));
                break;
            }

            $cmd = strtolower($parts[1]);

            // валидные значения
            if (!in_array($cmd, ["on", "off"])) {
                sendMessage($chat_id, sprintf($lang['bot_tooltip']));
                break;
            }

            // сохраняем в конфиг
            $cfg['bot_enabled'] = ($cmd === "on") ? true : false;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // ответ пользователю
            if ($cmd === "on") {
                sendMessage($chat_id, $lang['bot_enabled']);
            } else {
                sendMessage($chat_id, $lang['bot_disabled']);
            }
            break;

        //manual enable
        case "/enable":
            $cfg["enabled"] = true;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            applyNightMode($chat_id, true); 
            sendMessage($chat_id, sprintf($lang['night_enabled'], $lock_to));
            break;
        
        //manual disable
        case "/disable":
            $cfg["enabled"] = false;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            applyNightMode($chat_id, false); 
            sendMessage($chat_id, sprintf($lang['night_disabled']));
            break;



        case "/info":
            $tz_offset = $cfg['timezone'] ?? "+00"; 
            $bot_enabled = $cfg['bot_enabled'] ?? false;

            // выбираем текст через тернарный оператор
            $scheduled = $bot_enabled ? $lang['scheduled'] : $lang['scheduled_off'];

            if ($tz_offset === "+00" || $tz_offset === "0") {
                $tz_name = "UTC";
            } else {
                $sign = substr($tz_offset,0,1); 
                $hour = intval($tz_offset);
                $tz_name = "Etc/GMT" . ($hour >= 0 ? "-" . $hour : "+" . abs($hour));
            }

            $current_time = new DateTime("now", new DateTimeZone($tz_name));

            $lock_from = $cfg['lock_from'] ?? "23:00";
            $lock_to   = $cfg['lock_to'] ?? "07:00";
            $msg = sprintf(
                $lang['info_command'],
                $tz_offset,
                $current_time->format("H:i"),
                $lock_from,
                $lock_to,
                $scheduled
            );
            sendMessage($chat_id, $msg);
            break;

        case "/tz":

            sendMessage($chat_id, sprintf($lang['tz_tooltip']));
            break;

        case (preg_match('/^\/tz\s+([+-]?\d{1,2})$/', $text, $matches) ? true : false):
            $offset = intval($matches[1]);

            if ($offset < -12 || $offset > 14) {
                sendMessage($chat_id, sprintf($lang['tz_wrong']));
                break;
            }

            // Сохраняем в JSON группы
            $cfg['timezone'] = ($offset >= 0 ? "+" : "") . $offset;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            $msg = sprintf(
                $lang['tz_set'],
                $cfg['timezone']
            );
            sendMessage($chat_id, $msg);
            break;

        case "/set":

            sendMessage($chat_id, sprintf($lang['time_tooltip']));
            break;

        case (preg_match('/^\/set\s+(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', $text, $matches) ? true : false):
            $from = $matches[1];
            $to   = $matches[2];

            // Проверка корректности времени
            if (!preg_match('/^\d{1,2}:\d{2}$/', $from) || !preg_match('/^\d{1,2}:\d{2}$/', $to)) {
                sendMessage($chat_id, sprintf($lang['time_wrong']));
                break;
            }

            list($fh, $fm) = explode(":", $from);
            list($th, $tm) = explode(":", $to);

            if ($fh>23 || $fm>59 || $th>23 || $tm>59) {
                sendMessage($chat_id, sprintf($lang['time_wrong_time']));
                break;
            }

            // Сохраняем в JSON
            $cfg['lock_from'] = $from;
            $cfg['lock_to']   = $to;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            $msg = sprintf(
                $lang['time_set'],
                $from,
                $to
            );
            sendMessage($chat_id, $msg);
            break;


        default:
            sendMessage($chat_id, sprintf($lang['unknown_command']));
    }
}



