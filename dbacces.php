<?php

$url = "https://api.telegram.org/bot1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw/setWebhook?url=https://myfunnybant.ru/bot.php";
$update = json_decode(file_get_contents('php://input'), TRUE);
$botToken = "1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw";
$botAPI = "https://api.telegram.org/bot" . $botToken;

define('TOKEN', '1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw');

//Подключаемся к БД Хост, Имя пользователя MySQL, его пароль, имя нашей базы
// Функция вызова методов API.
function sendTelegram($method, $response){
    $ch = curl_init('https://api.telegram.org/bot' . TOKEN . '/' . $method);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}

$connect = new mysqli("localhost", "u643288077_myfunnyadmin", "6^f;yZPW]F", "u643288077_myfunnybant");
$connect->query("SET NAMES 'utf8' ");


//Кодировка данных получаемых из базы

