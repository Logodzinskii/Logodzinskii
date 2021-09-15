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
                'text' => 'нет полномочий',
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

    $newUser = new user($id,$first_name,$newStatus,$dataAdd);

    $subject = $newStatus == 'seller' ? '🙋 Добро пожаловать!' : '🙋 До скорой встречи!';
    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idSallers,
            'text' => $subject,
        )
    );
}

function saleItems($text,$newUser,$connect){

    if ($newUser->status != 'manager'){

        exit();

    }

    $idchat = $newUser->telegrammid;
    $dateadd = date('Y-m-d');
    $arrData = explode("|",$text);

    if (count($arrData)=== 3) {
        //получаю id
        $arrId = explode('.',$arrData[0]);
        $id = $arrId[1];
        //получаем количество товаров по id
        $sql = "SELECT * FROM `mybant` where id = '$id'";

        // Отправляем запрос;
        $res = $connect -> query($sql);
        if ($res -> num_rows > 0) {
            while ($row = $res -> fetch_assoc()) {
                //проверим если количество товаров на складе <= количества продаваемого товара, то производим изменения и вносим данные о продаже
                if ($arrData[1]<=$row["items"]){

                    $total = $row["items"];
                    $newItems =  $total - $arrData[1];
                    $totalPrice = $arrData[1] * $arrData[2];

                    $sql = "INSERT into saleitems(id,sale_to_chatID,date_sale,count_items,sale_price) values ('$arrData[0]','$idchat', '$dateadd' ,'$arrData[1]','$totalPrice')";
                    mysqli_query($connect, $sql);

                    $sqli = "UPDATE mybant SET items='$newItems' WHERE id='$id'";
                    mysqli_query($connect, $sqli);

                    sendTelegram(
                        'sendMessage',
                        array(
                            'chat_id' => $idchat,
                            'text' => 'Продажа id - '.$arrData[0]. ' - ' .$arrData[1] .' шт., осталось- '.$newItems,
                        )
                    );
                }else{
                    sendTelegram(
                        'sendMessage',
                        array(
                            'chat_id' => $idchat,
                            'text' => 'количество товаров к продаже '. $arrData[1] . ' болше чем есть ' . $row["items"],
                        )
                    );
                }

            }
        }



    }else{
        sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $idchat,
                'text' => 'Заполнены не все разделы товара',
            )
        );
    }
}

function saleToDay($text,$newUser,$connect){

    if (($newUser->status === 'buyer')){
        sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => 'нет полномочий на просмотр',
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
            'sendMessage',
            array(
                'chat_id' => $newUser->telegrammid,
                'text' => $str,
            )
        );
        ///home/u643288077/domains/myfunnybant.ru/public_html/saleitems/file_43.jpg




    }


}

function otchet($text,$newUser,$connect){

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
                'sendPhoto',
                array(
                    'chat_id' => $newUser->telegrammid,
                    'photo' => curl_file_create(__DIR__ . '/saleitems/'.$filename[7] ),
                    'caption'=>'Запись: '.$row['id'].' На сумму - '. $row['sale_price'],
                ));

        }

    }

}

function toAll($text,$connect,$newUser){
    if ($newUser->status != 'manager'){
        exit();
    }


    $arrData = explode("|",$text);

    $sql = "SELECT * FROM `users`";
// Отправляем запрос;
    $res = $connect -> query($sql);
    if ($res -> num_rows > 0) {
        // Цикл будет работать пока не пройдёт все строки;
        // При каждой новой итерации цикла,
        // Он переходит на новое значение;
        while ($row = $res -> fetch_assoc()) {
            // Вывод на экран;

            /*sendTelegram(
                'sendPhoto',
                array(
                    'chat_id' => $row['telegram_id'],
                    'photo' => curl_file_create(__DIR__ . '/fotoitems/'. $row["foto"])
                ));*/
            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $row['telegram_id'],
                    'text' => $arrData[0],
                )
            );
            //echo "Название: {$row["name"]}; <br>Цена: {$row["price"]}";
        }
        // Если таблица пустая, будет выведено "Данных нет";
    } else {
        echo "Данных нет";
    }
}

function addItems($text,$newUser,$connect){

    if ($newUser->status != 'manager'){
        exit();
    }

    $idchat = $newUser->telegrammid;
    $dateadd = date('Y-m-d');
    $arrData = explode("|",$text);
    if (count($arrData)=== 6) {
        //получу последнюю запись в бд
        $result = "SELECT * FROM `mybant` ORDER BY id DESC LIMIT 1";
        // Отправляем запрос;
        $res = $connect -> query($result);
        if ($res -> num_rows > 0) {
            while ($row = $res -> fetch_assoc()) {
                $newid = ($row['id'] + 1);
                $arrData[0]='/'.$arrData[0].'.'.$newid;

            }
        }

        $sql = "INSERT into mybant(article,name,price,items,options,dateadd,foto) values ('$arrData[0]','$arrData[1]','$arrData[2]','$arrData[3]','$arrData[4]','$dateadd','$arrData[5]')";

        mysqli_query($connect, $sql);

        $result = "SELECT * FROM `mybant` ORDER BY id DESC LIMIT 1";
        // Отправляем запрос;
        $res = $connect -> query($result);
        if ($res -> num_rows > 0) {
            while ($row = $res -> fetch_assoc()) {
                sendTelegram(
                    'sendMessage',
                    array(
                        'chat_id' => $idchat,
                        'text' => 'Артикул добавленного товара - ' . $row['article'],
                    )
                );
            }
        }
    }else{
        sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $idchat,
                'text' => 'Заполнены не все разделы товара',
            )
        );
    }
}

function updateItems($text,$newUser,$connect){
    if ($newUser->status != 'manager'){

        exit();

    }
    $arrData = explode("|",$text);
    //получаю id
    $arrId = explode('.',$arrData[0]);
    $id = $arrId[1];
    $idchat = $newUser->telegrammid;

    $sqli = "UPDATE mybant SET items='$arrData[1]' WHERE id='$id'";
    mysqli_query($connect, $sqli);

    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idchat,
            'text' => 'Количество у id - '.$arrData[0]. ' изменено на - ' .$arrData[1] .' шт.',
        )
    );
}