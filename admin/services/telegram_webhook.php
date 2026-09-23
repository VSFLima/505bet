<?php

function enviar_telegram($mensagem)
{
    global $mysqli;

    $sql = "SELECT * FROM webhook WHERE status = 1";
    $result = $mysqli->query($sql);

    if(!$result) return;

    while ($webhook = $result->fetch_assoc()) {

        $bot = $webhook['bot_id'];
        $chat = $webhook['chat_id'];

        $url = "https://api.telegram.org/bot{$bot}/sendMessage";

        $data = [
            "chat_id" => $chat,
            "text" => $mensagem,
            "parse_mode" => "HTML"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}