<?php

function showMeTOP10($idchat, $connect, $text, &$dbResponseArray, $botAPI):array
{

    $str = str_replace("/", "", $text);

    $sql = "SELECT * FROM `wp_wc_product_meta_lookup` where sku LIKE '%" . $str . "%'";

    $res = $connect->query($sql);

    if ($res->num_rows > 0) {

        while ($row = $res->fetch_assoc()) {
            $sqlr = "SELECT * FROM `wp_posts` where post_title = '" . $row["sku"] . "'";
            $resr = $connect->query($sqlr);
            if ($resr->num_rows > 0) {
                while ($rows = $resr->fetch_assoc()) {
                    $i=0;
                    $path = str_replace('https://myfunnybant.ru/', '', $rows["guid"]);
                    // запишем в массив все записи которые получили из базы данных
                    $keyboard1 = [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Перейти к заказу', 'callback_data' => $row["sku"]],
                            ]
                        ]
                    ];
                    $reply_markup = json_encode($keyboard1);
                    $dbResponseArray[] = [
                        'chat_id' => $idchat,
                        'photo' => curl_file_create(__DIR__ . '/' . $path),
                        'caption' => "Название: {$row["sku"]}; \n Артикул: #{$row["sku"]} \n Цена: {$row["max_price"]}. ",
                        'reply_markup'=>$reply_markup,
                    ];
                    $i++;
                }
            }

        }

    }
    return $dbResponseArray;

}

function parsingDBRequest($dbResponseArray,$rowStart,$rowOfset,$botAPI,$text)
{
    $idchat = $dbResponseArray[0]['chat_id'];
    $sum = $rowStart;
    for ($i=$rowStart;$i<=$rowOfset;$i++){

        sendTelegram('sendPhoto',$dbResponseArray[$i]);

        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard1}");

        $sum++;

    }

    $rowStop= ($sum + 4) <= count($dbResponseArray) ? $sum + 4 : count($dbResponseArray);

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'Показать ещё!', 'callback_data' => $text.'@'.$sum.'|'. $rowStop],
            ]
        ]
    ];
    $reply_markup = json_encode($keyboard);
    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idchat,
            'text'=>'Показано товаров ('.($sum).' из '. (count($dbResponseArray)) . ')',
            'reply_markup'=>$reply_markup,
        ));

    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");


}