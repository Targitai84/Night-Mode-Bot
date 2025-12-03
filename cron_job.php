<?php
date_default_timezone_set("UTC");

require_once __DIR__ . '/telegram_api.php'; 

define('GROUPS_PATH', __DIR__ . '/groups/');
define('LOGS_PATH', __DIR__ . '/logs/');
define("SETTINGS_PATH", __DIR__  . "/settings/settings.json");
$settings = json_decode(file_get_contents(SETTINGS_PATH), true);



// night_log
function log_write_night($msg, $level="info") {
    global $settings;
    $log_level = $settings['log_level'] ?? 'info';

    $levels = ['none' => 0, 'error' => 1, 'info' => 2, 'debug' => 3];

    if (!isset($levels[$level]) || $levels[$level] > ($levels[$log_level] ?? 2)) {
        return;
    }

    $file = LOGS_PATH . "night_log_" . date("Y-m-d") . ".log";

    file_put_contents($file, "[".date("H:i:s")."][".$level."] ".$msg.PHP_EOL, FILE_APPEND);
}




// Check all groups
foreach (glob(GROUPS_PATH."*.json") as $file) {
    $cfg = json_decode(file_get_contents($file), true);
    if (!is_array($cfg)) {
        continue;
    }
    $chat_id = basename($file, ".json");
    $bot_enabled = $cfg['bot_enabled'] ?? false;
    if ($bot_enabled == false){
        continue;
    }
    $group_lang =  $cfg['lang'];
    $lang = loadLang( $group_lang);

    $lock_from = $cfg['lock_from'] ?? "23:00";
    $lock_to   = $cfg['lock_to'] ?? "07:00";
    $locked    = $cfg['locked'] ?? false;

    
    $now = new DateTime("now", new DateTimeZone("UTC"));
    $now_hm = intval($now->format("H"))*60 + intval($now->format("i")); 

    // Group Time Zone
    $tz_offset = $cfg['timezone'] ?? "+0";       
    $offset_minutes = intval($tz_offset) * 60;   
    $now_hm = ($now_hm + $offset_minutes + 24*60) % (24*60); // day correction

    // Time sheduled
    list($from_h, $from_m) = explode(":", $lock_from);
    list($to_h, $to_m)     = explode(":", $lock_to);
    $from_minutes = intval($from_h)*60 + intval($from_m);
    $to_minutes   = intval($to_h)*60 + intval($to_m);

    //Check time interval
    $in_night = false;
    if ($from_minutes < $to_minutes) {
        // in one day
        if ($now_hm >= $from_minutes && $now_hm < $to_minutes) $in_night = true;
    } else {
        // in tomorrow
        if ($now_hm >= $from_minutes || $now_hm < $to_minutes) $in_night = true;
    }

    if ($in_night && !$locked) {
        // Disabling permissions
        if (applyNightMode($chat_id, true)) {

            $cfg['locked'] = true;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_write_night("Nighmode Enabled for group: $chat_id", "debug");

            sendMessage($chat_id,  sprintf($lang['night_enabled'], $lock_to));
        } else {
        log_write_night("Error setings up Nightmode $chat_id", "error");
        return false;
        }

    } elseif (!$in_night && $locked) {
        // Enable permissions
        if (applyNightMode($chat_id, false)) {
            $cfg['locked'] = false;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_write_night("Nightmode is Off for group:  $chat_id", "debug");

            sendMessage($chat_id, sprintf($lang['night_disabled']));
        } else {
        log_write_night("Error setings up Nightmode  $chat_id", "error");
        return false;
        }
    } else {
        log_write_night("No need changes for: $chat_id )", "debug");
    } 
}
