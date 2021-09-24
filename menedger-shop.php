<?php

function startshoptoday($newUser,$connect){

    $idchat = $newUser->telegrammid;
    $sellerstatus = $newUser ->status;
    $first_name = $newUser -> username;

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🙏 Принять', 'callback_data' => 'yes_start#'.$idchat."|".$first_name]

            ],
            [
                ['text' => '❌ Уволить', 'callback_data' => 'no_start#'.$idchat."|".$first_name]
            ],

        ]
    ];

    $reply_markup = json_encode($keyboard);

    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => 1454009127,//1454009127 645879928
            'text' => "Запрос пользователя:\n id - ". $idchat . "\n статус - ". $sellerstatus . "\n Имя: " . $first_name,
            'reply_markup'=>$reply_markup,
        )
    );

    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");

    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idchat,
            'text' => "Запрос принят, ожидайте",
        )
    );
}

function updateStatusUser($newUser,$connect,$newStatus,$idSallers){

    if ($newUser->status != 'manager'){

        sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => 'нет полномочий код 2',
            )
        );

        exit();

    }

    $idchat = $newUser->telegrammid;
    $sqli = "UPDATE users SET status='$newStatus' WHERE telegram_id='$idSallers'";

    mysqli_query($connect, $sqli);

    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idchat,
            'text' => 'Статус изменен:' . $idSallers . ' новый статус: ' . $newStatus,
        )
    );

    $id = $newUser->telegrammid;
    $first_name = $newUser->username;
    $dataAdd = $newUser->dataAdd;

    $newUser = new user($id,$first_name,$newStatus,$dataAdd,$connect);

    $subject = $newStatus == 'seller' ? '🙋 Добро пожаловать! Команда для просмотра продаж /manager' : '🙋 До скорой встречи!';
    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idSallers,
            'text' => $subject,
        )
    );
}

function saleToDay($text,$newUser,$connect,$messageId){

    if (($newUser->status === 'buyer')){
        sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => 'нет полномочий на просмотр код 3',
            )
        );
        exit();
    }

    $tumbler = $text == 'i' ? $tumbler = 'id' : $tumbler = 'date_sale';
    $dateSale = date('Y-m-d');

    //получаем количество товаров по id
    $sql = "SELECT $tumbler, SUM(sale_price) as totalSale, SUM(count_items) as totalCount FROM `saleitems` where date_sale = '$dateSale' GROUP BY $tumbler";

    $str='';
    // Отправляем запрос;
    $res = $connect -> query($sql);
    if ($res -> num_rows > 0) {
        while ($row = $res -> fetch_assoc()) {

            $article = $row["id"];
            $count = $row["totalCount"];
            $salePrice = $row["totalSale"];
            $dat = $row["date_sale"];
            $link = ($row["sale_file"]);
            $str = $str.  $dat . ' Артикул - ' . $article . ' Количество - ' .$count. ' 💰 Итого - '. $salePrice ."\n" ;

        }

        sendTelegram(
            'editMessageText',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => $str,
                'message_id'=>$messageId-1,
            )
        );

    }else{

        sendTelegram(
            'editMessageText',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => "Еще ничего не продано.",
                'message_id'=>$messageId-1,
            )
        );
    }

}

function otchet($text,$newUser,$connect,$messageId){

    if ($newUser->status === 'buyer'){
        exit();
    }

    $dateSale = date('Y-m-d');
    $sql = "SELECT * FROM `saleitems` where date_sale = '$dateSale'";
    $res = $connect -> query($sql);

    if ($res -> num_rows > 0) {

        while ($row = $res -> fetch_assoc()) {

            $filename = explode("/", $row['sale_file']);

            sendTelegram(
                'sendPhoto', // editMessageMedia method
                array(
                    'chat_id' => $newUser->telegrammid,
                    'photo' => curl_file_create(__DIR__ . '/saleitems/'.$filename[7] ),
                    'caption'=>'Запись: '.$row['id'].' На сумму - '. $row['sale_price'],
                )
            );

        }

    }else{

        sendTelegram(
            'editMessageText',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => "Еще ничего не продано.",
                'message_id'=>$messageId-1,
            )
        );
    }

}

function callReport($text,$newUser,$connect,$messageId){

    if ($newUser->status != 'manager'){
        exit();
    }

    $arr=[];
    $sql = "SELECT * FROM `saleitems` ";
    $res = $connect -> query($sql);

    if ($res -> num_rows > 0) {

        while ($row = $res -> fetch_assoc()) {

            $arr[]=[
                'date'=>$row['date_sale'],
                'saller'=>$row['sale_to_chatID'],
                'totalPrice'=>$row['sale_price'],
            ];

        }
    }

    $report = new report($arr);

    $arrMonth = ['september','october'];
    $arrUsers = [$newUser->telegrammid];
    $message = '';

    foreach ($arrMonth as $month){

        $arr = $report->sortByMonth($month);
        $arrSortMonth = new report($arr);
        $message = $message . "🕐 Продажи за - " . $month . ' - ' . $report->sumArr($arr). PHP_EOL;

        foreach ($arrUsers as $user){

            $message = $message . "🙋 Продажи - " . $user . " - " . $arrSortMonth->sumArr($arrSortMonth->sortBySellerName($user)). PHP_EOL;

        }

    }

    sendTelegram(
        'editMessageText',//sendMessage  editMessageText
        array(
            'chat_id' => $newUser->telegrammid,
            'text' => $message, //. '-' . $messageId . 'sss',
            'message_id'=> $messageId-1,
        )
    );

}