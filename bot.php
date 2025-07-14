<?php

$token = "YOUR_BOT_TOKEN";
$api = "https://api.telegram.org/bot$token/";

$content = file_get_contents("php://input");
$update = json_decode($content, true);

$message = $update['message'] ?? null;
$callback = $update['callback_query'] ?? null;

$chat_id = $message['chat']['id'] ?? $callback['message']['chat']['id'] ?? null;
$data_path = "tree.json";

$tree = file_exists($data_path) ? json_decode(file_get_contents($data_path), true) : [];

function buildKeyboard($path, $tree) {
    $current = &$tree;
    foreach (explode(".", $path) as $part) {
        if ($part !== '') {
            $current = &$current[$part];
        }
    }

    $keyboard = [];
    $i = 0;
    foreach ($current as $key => $value) {
        $keyboard[] = [[
            "text" => "🔘 زر ($key)",
            "callback_data" => "$path.$key"
        ]];
        $i++;
    }

    $keyboard[] = [[
        "text" => "➕ إضافة زر",
        "callback_data" => "add|$path"
    ]];
    return $keyboard;
}

if ($callback && str_starts_with($callback['data'], "add|")) {
    $path = str_replace("add|", "", $callback['data']);

    $current = &$tree;
    if ($path !== '') {
        foreach (explode(".", $path) as $p) {
            if (!isset($current[$p])) $current[$p] = [];
            $current = &$current[$p];
        }
    }

    $new_key = count($current);
    $current[$new_key] = [];

    file_put_contents($data_path, json_encode($tree));

    $keyboard = buildKeyboard($path, $tree);
    file_get_contents($api . "editMessageReplyMarkup?" . http_build_query([
        'chat_id' => $callback['message']['chat']['id'],
        'message_id' => $callback['message']['message_id'],
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]));
}

if ($callback && !str_starts_with($callback['data'], "add|")) {
    $path = $callback['data'];
    $keyboard = buildKeyboard($path, $tree);

    file_get_contents($api . "editMessageReplyMarkup?" . http_build_query([
        'chat_id' => $callback['message']['chat']['id'],
        'message_id' => $callback['message']['message_id'],
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]));
}

if ($message && isset($message['text']) && $message['text'] == "/start") {
    $keyboard = buildKeyboard("root", $tree);
    file_get_contents($api . "sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => "مرحباً بك! 👇 اضغط لإضافة الأزرار",
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]));
}
