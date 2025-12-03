<?php
date_default_timezone_set("UTC");

error_reporting(E_ALL);
ini_set('display_errors', 0);

define("ROOT", __DIR__);
define("SETTINGS_PATH", ROOT . "/settings/settings.json");
define("GROUPS_PATH", ROOT . "/groups/");
define("LOGS_PATH", ROOT . "/logs/");

require ROOT."/bot_commands.php";
require ROOT."/telegram_api.php";


// --- Load settings ---
$settings = json_decode(file_get_contents(SETTINGS_PATH), true);
function log_write($msg, $level='info') {
    global $settings;

    $log_level = $settings['log_level'] ?? 'info';

    // порядок уровней важности
    $levels = ['none' => 0, 'error' => 1, 'info' => 2, 'debug' => 3];

    // если текущий уровень ниже настроенного — не пишем
    if (!isset($levels[$level]) || $levels[$level] > ($levels[$log_level] ?? 2)) {
        return;
    }

    $file = LOGS_PATH . date("Y-m-d") . ".log";
    file_put_contents($file, "[".date("Y-m-d H:i:s")."][$level] $msg\n", FILE_APPEND);
}
// --- Read raw update ---
$raw = file_get_contents("php://input");

log_write("RAW UPDATE: ".$raw, "debug");

if (!$raw) exit;
$update = json_decode($raw, true);
if (!$update) {
    log_write("JSON decode failed", "error");
    exit;
}

// --- Handle my_chat_member events ---
if (isset($update['my_chat_member'])) {
    handleMyChatMember($update['my_chat_member']);
}

// --- Handle message commands ---
if (isset($update['message'])) {
    handleMessage($update['message']);
}

http_response_code(200);
echo "OK";
