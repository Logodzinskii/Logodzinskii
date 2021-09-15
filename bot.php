<?php
include_once 'dbacces.php';
include_once 'menedger-shop.php';
include_once 'client-shop.php';

date_default_timezone_set('asia/yekaterinburg');



class user{
    public $id, $username,$status,$dataAdd,$connect;
    public function __construct($id,$first_name,$status,$dataAdd){
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
    public function getUserId()
    {
        return $this -> telegrammid;
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

//$newUser->id = $data['message']['chat']['id'];
//$newUser->username = $data['message']['chat']['first_name'];

$names=$newUser->username;

if (empty($newUser->telegrammid)) {
    //exit();
}

$dbResponseArray=[];

// Check if callback is set
if (isset($update['callback_query'])) {
    $dataAdd = date('Y-m-d');
    $newUser = new user($update['callback_query']['from']['id'],$update['callback_query']['from']['first_name'],'newSeller',$dataAdd,$connect);

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


        newmessage(trim($text),$newUser,$connect,$arrpage[0]);

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

// Прислали фото. Проверяем является ли автор модератором сайта, если да то сохраняем в папку fileitems
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

function newmessage($fullStr,$newUser,$connect,$deftext=0){
    //проверим текст на содержание кодовых символов @
    $pos = !is_null(strpos($fullStr,"@"))  ? explode('@' , $fullStr) : $fullStr;
    $text = count($pos) > 0 ? strtolower($pos[0]) : strtolower($pos);
    $idchat = is_int($newUser) ? $newUser : $newUser->telegrammid;
    //$idchat = $newUser->telegrammid;
    //$idchat = $newUser;
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
                        ['text' => '🙋 Приступить к работе', 'callback_data' => 'startshoptoday#']

                    ],
                ]
            ];
            $reply_markup = json_encode($keyboard);


            //Отправляю картинку с teenager
            sendTelegram(
                'sendPhoto',
                array(
                    'chat_id' => $idchat,
                    'photo' => curl_file_create(__DIR__ . '/fotoitems/intro/teenager.jpg' ),
                    'reply_markup'=>$reply_markup,
                ));

            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");
            break;
        case ("/buy"):

            $sendtext = <<<EOD
🙋 Как выбрать из наличия и сделать заказ?📑
📥 Отправте мне артикул товара сообщением https://t.me/myfunnybant_manager;
📫 Я свяжусь с вами для обсуждения способа доставки;
🚛 После выбора способа доставки, 💳 оплачиваете заказ (перевод на карту сбербанка).

Способы доставки:
-Самовывоз из г. Ектеринбург, район ЦПКиО им.Маяковского;
-Отправка "Почтой России", СДЭК, СБЕРЛОГИСТИКА.

EOD;

            break;

        case ("/toall"):

            toAll($pos[1],$connect,$newUser);
            break;

        case ("/add"):

            addItems($pos[1],$newUser,$connect);

            break;
        case("/sale"):

            saleItems($pos[1],$newUser,$connect);

            break;
        case("/update"):

            updateItems($pos[1],$newUser,$connect);

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
            otchet($text,$newUser,$connect);
            //saleToDay($arrpage[0],$newUser,$connect);

            break;
        case("/saletoday"):

            saleToDay($arrpage[0],$newUser,$connect);

            break;
        case("/manager"):

            if ($newUser->status != 'buyer'){


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
                $reply_markup = json_encode($keyboard);

                //Отправляю картинку с teenager
                sendTelegram(
                    'sendMessage',
                    array(
                        'chat_id' => $idchat,
                        'text' => 'Команды для менеджера магазина',
                        'reply_markup'=>$reply_markup,
                    ));

                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");
            }else {
                sendTelegram(
                    'sendMessage',
                    array(
                        'chat_id' => $idchat,
                        'text' => 'Нет полномочий! Код 1',
                    ));
            }
            break;
        case ("/saleinfo"):

            $sendtext = strpos($fullStr,"@") . "Для внесения продажи - команда:\n /sale@артикул|количество|цена продажи";

            break;

        case ("/updateinfo"):

            $sendtext = strpos($fullStr,"@") . "Для добавления количества по артикулу - команда:\n /update@артикул|количество";

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