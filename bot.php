<?php

include_once 'dbacces.php';
include_once 'menedger-shop.php';
//include_once 'client-shop.php'; в разработке
include_once 'calculate.php';

date_default_timezone_set('asia/yekaterinburg');

class user{
    public $id, $username,$status,$dataAdd,$connect;
    public function __construct($id,$first_name,$status,$dataAdd,$connect){
        $this-> telegrammid = $id;
        $this-> username = $first_name;
        $this-> status = $status;
        $this-> dataAdd = $dataAdd;
        $this->addAnonimUser($connect);
    }

    public function addAnonimUser($connect){

        $id = $this->telegrammid;
        $username = $this->username;
        $status = $this->status;
        $dataAdd = $this->dataAdd;

        $sql = "SELECT * FROM `users` where telegram_id = '$id'";
        $res = $connect -> query($sql);
        if ($res -> num_rows == 0) {

            $sql = "INSERT into users(telegram_id,first_name,status,dateadduser) values ('$id','$username','$status','$dataAdd')";
            mysqli_query($connect, $sql);
            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $id,
                    'text' => error_reporting(E_ALL ^ E_DEPRECATED),
                )
            );
        }else{
            while ($row = $res -> fetch_assoc()) {
                $this->status = $row['status'];
            }
        }
    }

}
class message{

    public $textmessage;

}

$id = $update['message']['chat']['id'];
$first_name = $update['message']['chat']['first_name'];
$status = 'buyer';
$dataAdd = date('Y-m-d');
$newUser = new user($id,$first_name,$status,$dataAdd,$connect);
$names=$newUser->username;
$dbResponseArray=[];

// Получаем данные, переданные от нажатия кнопки. Разбиваем строку по кодовым символам @ # | в зависимости от символа вызываем нужную команду, надо подумать как переделать...

if (isset($update['callback_query'])) {

    $dataAdd = date('Y-m-d');
    $newUser = new user($update['callback_query']['from']['id'],$update['callback_query']['from']['first_name'],'newSeller',$dataAdd,$connect);
    $message = $update['callback_query']['message']['message_id'];
    $callback_tumbler = $update['callback_query']['data'];

    if (strpos($callback_tumbler,'@')>0){

        $arrstr = explode('@',$callback_tumbler);
        $text = $arrstr[0];
        $arrpage = explode('|',$arrstr[1]);

        parsingDBRequest(showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI),$arrpage[0],$arrpage[1],$botAPI,$text);

    }elseif(strpos($callback_tumbler,'*')>0){

        $text = '/'.$callback_tumbler;
        $text = str_replace('*','',$text);
        $itemStart = 4;

        parsingDBRequest(showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI),0,$itemStart,$botAPI,$text);

    }elseif(strpos($callback_tumbler,'#')>0){

        $arrstr = explode('#',$callback_tumbler);
        $text = $arrstr[0];
        $arrpage = strpos($arrstr[1], "|")>0 ? explode('|',$arrstr[1]) : false;
        $text = '/'.$text;

        newmessage(trim($text),$newUser,$connect,$arrpage[0],$message);

    }
    else{

        $data = http_build_query([
            'text' => 'Поступил заказ, артикул - ' . $update['callback_query']['data'] . '. От пользователя: '. $update['callback_query']['from']['id']. '. Имя пользователя: ' . $update['callback_query']["message"]["chat"]["first_name"],
            'chat_id' => '645879928'
        ]);

        file_get_contents($botAPI . "/sendMessage?{$data}");

        $data = http_build_query([
            'text' => 'Мы получили ваш заказ, напишите мне https://t.me/myfunnybant_manager Ваш код заказа: #' . $update['callback_query']['from']['id'],
            'chat_id' => $update['callback_query']['from']['id']
        ]);

        file_get_contents($botAPI . "/sendMessage?{$data}");
    }

}

// Прислали фото. Проверяем является ли автор модератором сайта, если да, то сохраняем в папку fileitems
if (!empty($update['message']['photo']) && ($newUser->status !='buyer'))
{

    $photo = array_pop($update['message']['photo']);
    $text = $update['message']['text'];
    $res = sendTelegram(
        'getFile',
        array(
            'file_id' => $photo['file_id']
        )
    );

    $res = json_decode($res, true);

    if ($res['ok'] && $update['message']['caption'] == '') {

        $src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
        $dest = __DIR__ . '/fotoitems/'. basename($src);

        if (copy($src, $dest)) {

            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $update['message']['chat']['id'],
                    'text' => basename($src) . ' /Add@артикул|название|цена|количество|описание|имя файла'
                )
            );

        }else{

            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $update['message']['chat']['id'],
                    'text' => 'error load file'
                )
            );
        }
    }else{

        $src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
        $dest = __DIR__ . '/saleitems/' . basename($src);
        $idchat = $update['message']['chat']['id'];
        $dateadd = date('Y-m-d');

        if (copy($src, $dest)) {

            $arrData = explode(',',$update['message']['caption']);
            $iditem = explode('.',basename($src));
            $id = explode('_',$iditem[0]);
            $totalPrice = $arrData[0] * $arrData[1];
            $sql = "INSERT into saleitems(id,sale_to_chatID,date_sale,count_items,sale_price,sale_file) values ('$id[1]','$idchat', '$dateadd' ,'$arrData[0]','$totalPrice','$dest')";
            mysqli_query($connect, $sql);
            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $update['message']['chat']['id'],
                    'text' => 'Запись внесена - id' . $id[1]
                )
            );
            sendTelegram(
                'sendPhoto',
                array(
                    'chat_id' => 1454009127,
                    'photo' => curl_file_create(__DIR__ . '/saleitems/'.basename($src) ),
                    'caption'=>$newUser->username.' внес запись: '.$id[1].'На сумму - '. $totalPrice,
                ));

        }else{
            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $update['message']['chat']['id'],
                    'text' => 'error load file'
                )
            );
        }
    }

    exit();
}
// Ответ на текстовые сообщения.
$newTextMessage = new message();
$newTextMessage -> textmessage = $update['message']['text'];
$message = !empty($newTextMessage->textmessage) ? newmessage($newTextMessage->textmessage, $newUser, $connect) : null ;

function newmessage($fullStr,$newUser,$connect,$deftext=0,$message=''){
    //проверим текст на содержание кодовых символов @
    $pos = !is_null(strpos($fullStr,"@"))  ? explode('@' , $fullStr) : $fullStr;
    $text = count($pos) > 0 ? strtolower($pos[0]) : strtolower($pos);
    $idchat = is_int($newUser) ? $newUser : $newUser->telegrammid;
    $status = $newUser->status;

    switch ($text){
        case("/start"):
            $sendtext = $newUser->username . ", добро пожаловать! \n Чтобы узнать что умеет наш бот напиши команду /help";
            break;
        case ("/help"):
            //создаю массив с данными меню
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🙋 Приступить к работе', 'callback_data' => 'startshoptoday#'],

                    ],
                ]
            ];
            $reply_markup = json_encode($keyboard);

            //Отправляю картинку заставку
            sendTelegram(
                'sendPhoto',
                array(
                    'chat_id' => $idchat,
                    'photo' => curl_file_create(__DIR__ . '/fotoitems/intro/teenager.jpg' ),
                    'reply_markup'=>$reply_markup,
                ));

            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");

            if ($status !='buyer'){
                sendTelegram(
                    'sendMessage',
                    array(
                        'chat_id' => $idchat,
                        'text' => 'Просмотреть список команд /manager',
                    ));
            }
            break;
        case("/sale"):
            saleItems($pos[1],$newUser,$connect);
            break;
        case('/startshoptoday'):
            startshoptoday($newUser,$connect);
            break;
        case('/closeshoptoday'):
            startshoptoday($newUser,$connect);
            break;
        case('/yes_start'):
            updateStatusUser($newUser,$connect,'seller',$deftext);
            break;
        case('/no_start'):
            updateStatusUser($newUser,$connect,'buyer',$deftext);
            break;
        case("/saletodayid"):
            otchet($text,$newUser,$connect,$message);
            break;
        case("/saletoday"):
            saleToDay($arrpage[0],$newUser,$connect,$message);
            break;
        case("/saleall"):
            callReport($arrpage[0],$newUser,$connect,$message);
            break;
        case("/manager"):
            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $idchat,
                    'text' => 'Тут будут данные по продажам',
                ));
            if ($newUser->status == 'seller'){//доступно для нанятого продавца

                $keyboard = [
                    'inline_keyboard' => [

                        [
                            ['text' => '💵 Продажи за сегодня по артикулу', 'callback_data' => 'saletodayid#|i'],
                            ['text' => '💰 Продажи за сегодня всего', 'callback_data' => 'saletoday#|d']
                        ],
                        [
                            ['text' => '🏪 Завершить работу', 'callback_data' => 'closeshoptoday#'],
                        ],
                    ]
                ];

            }elseif ($newUser->status == 'manager'){//доступно для управляющего магазином

                $keyboard = [
                    'inline_keyboard' => [

                        [
                            ['text' => '💵 Продажи за сегодня по артикулу', 'callback_data' => 'saletodayid#|i'],
                            ['text' => '💰 Продажи за сегодня всего', 'callback_data' => 'saletoday#|d'],
                        ],
                        [
                            ['text' => '💰 Продажи всего', 'callback_data' => 'saleall#|d'],
                        ],
                    ]
                ];

            }elseif($newUser->status == 'buyer') {//если обратился тот у кого не полномочий
                sendTelegram(
                    'sendMessage',
                    array(
                        'chat_id' => $idchat,
                        'text' => 'Нет полномочий! Код 1',
                    ));
            }

            $reply_markup = json_encode($keyboard);

            sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $idchat,
                    'text' => 'Команды для менеджера магазина /manager',
                    'reply_markup'=>$reply_markup,
                ));

            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");

            break;

        default:

            $sendtext = strpos($fullStr,"@") . "Неправльная команда, для просмотра команд нажмите /help";

    }
    sendTelegram(
        'sendMessage',
        array(
            'chat_id' => $idchat,
            'text' => $sendtext
        )
    );
}
