<?php

function sendMessage($chat_id, $text) {
    global $settings;
    $token = $settings['bot_token'];
    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    file_get_contents($url . "?" . http_build_query([
        "chat_id"=>$chat_id,
        "text"=>$text,
        "parse_mode"=>"HTML"
    ]));
}

function setChatPermissions($chat_id, $permissions) {
    global $settings;
    $token = $settings['bot_token'];
    $url = "https://api.telegram.org/bot{$token}/setChatPermissions";

    return file_get_contents($url . "?" . http_build_query([
        "chat_id"=>$chat_id,
        "permissions"=>json_encode($permissions)
    ]));
   
}



function isAdmin($chat_id, $user_id) {
    $data = getChatMember($chat_id, $user_id);
    if (!$data || !$data['ok']) return false;
    $status = $data['result']['status'];
    return in_array($status, ['creator','administrator']);
}

function getChatMember($chat_id, $user_id) {
    global $settings;
    $token = $settings['bot_token'];
    $url = "https://api.telegram.org/bot{$token}/getChatMember";
    $res = file_get_contents($url . "?" . http_build_query([
        "chat_id"=>$chat_id,
        "user_id"=>$user_id
    ]));
    return json_decode($res, true);
}

function applyNightMode($chat_id, $enable=true) {
    global $settings;

    $permissions = $enable
        ? $settings['night_mode_permissions']['enable']
        : $settings['night_mode_permissions']['disable'];

    return setChatPermissions($chat_id, $permissions);
}


function loadLang($language) {
    $lang_file = __DIR__ . "/settings/lang/" . ($language ?? "EN") . ".json";
    if (file_exists($lang_file)) {
        return json_decode(file_get_contents($lang_file), true);
    }
    // fallback 
    $default_file = __DIR__ . "/settings/lang/EN.json";
    if (file_exists($default_file)) {
        return json_decode(file_get_contents($default_file), true);
    }
    return [];
}

