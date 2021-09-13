<?php
include_once 'dbacces.php';
date_default_timezone_set('Europe/Moscow');

$url = "https://api.telegram.org/bot1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw/setWebhook?url=https://myfunnybant.ru/bot.php";
$update = json_decode(file_get_contents('php://input'), TRUE);
$botToken = "1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw";
$botAPI = "https://api.telegram.org/bot" . $botToken;
$dbResponseArray=[];
    
class user{
    public $id, $username,$status,$dataAdd;
    public function __construct($id,$first_name,$status,$dataAdd){
        $this-> telegrammid = $id;
        $this-> username = $first_name;
        $this-> status = $status;
        $this-> dataAdd = $dataAdd;
        $this->addAnonimUser();
    }

    public function addAnonimUser(){
        $connect = new mysqli("localhost", "u643288077_myfunnyadmin", "6^f;yZPW]F", "u643288077_myfunnybant");
        $connect->query("SET NAMES 'utf8' ");
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

$newUser = new user($id,$first_name,$status,$dataAdd);

//$newUser->id = $data['message']['chat']['id'];
//$newUser->username = $data['message']['chat']['first_name'];

$names=$newUser->username;

if (empty($newUser->telegrammid)) {
    //exit();
}

define('TOKEN', '1917661632:AAHNJoHhxhaJu_7NgVo5y5Vii_X1cus9nqw');

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

    // Check if callback is set
    if (isset($update['callback_query'])) {
    
        $callback_tumbler = $update['callback_query']['data'];
        
        if (strpos($callback_tumbler,'@')>0){

            $arrstr = explode('@',$callback_tumbler);
            $text = $arrstr[0];
            $arrpage = explode('|',$arrstr[1]);

            //showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI);
            parsingDBRequest(showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI),$arrpage[0],$arrpage[1],$botAPI,$text);

        }elseif(strpos($callback_tumbler,'*')>0){

            $text = '/'.$callback_tumbler;
            $text = str_replace('*','',$text);

            //showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI);
            
            
            $itemStart = 4; 
            
            parsingDBRequest(showMeTOP10($update['callback_query']['from']['id'],$connect,$text,$dbResponseArray,$botAPI),0,$itemStart,$botAPI,$text);
            
        }elseif(strpos($callback_tumbler,'#')>0){
            
            $text = '/'.$callback_tumbler;
            $text = str_replace('#','',$text);
            
            newmessage(trim($text),$update['callback_query']['from']['id'],$connect);
           
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
if (!empty($update['message']['photo']) && ($newUser->status=='manager'))
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

function newmessage($fullStr,$newUser,$connect){
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
            $arrMenu=array(
                'teenager'=>'Бантики для всех',
                'school'=>'Школьная коллекция',
                'baby'=>'Коллекция для садика',
                'buy'=>'Как сделать заказ',
            );
            
             $keyboard = [
                        'inline_keyboard' => [
                                [
                                    ['text' => '🎀 Цветные банты к школьной форме', 'callback_data' => 's5'.'*']
                                   
                                ],
                                [
                                    ['text' => '🎀 К синей форме', 'callback_data' => 's1'.'*'],
                                    ['text' => '🎀 К бордовой форме', 'callback_data' => 's2'.'*']
                                ],
                                [
                                    ['text' => '🎀 К серой форме', 'callback_data' => 's3'.'*'],
                                    ['text' => '🎀 Белые банты', 'callback_data' => 's4'.'*']
                                ],
                                
                                [
                                    ['text' => '👶 Для малышей, первые хвостики', 'callback_data' => 'b1'.'*'],
                                ],
                                [
                                    ['text' => '👧 Дошколенок', 'callback_data' => 'b2'.'*'],
                                ],
                                [
                                    ['text' => '😻 Значки', 'callback_data' => 'z1'.'*'],
                                    ['text' => '🎁 Подвески', 'callback_data' => 'p1'.'*']
                                ],
                                [
                                    ['text' => '👸 Ободки', 'callback_data' => 'o1'.'*'],
                                    ['text' => '💰 Как купить?', 'callback_data' => 'buy#']
                                ],
                                [
                                    ['text' => '🔎 Проверить по артикулу', 'callback_data' => 'findid'.'#']
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
        case("/saletodayid"):
        
            saleToDay('i',$idchat,$connect);
            
        break;
        case("/saletoday"):
        
            saleToDay('d',$idchat,$connect);
            
        break;
        case("/manager"):

            if ($newUser->status === 'manager'){
                
            
            $keyboard = [
                        'inline_keyboard' => [
                               
                                [
                                    ['text' => '💵 Продажа по артикулу', 'callback_data' => 'saleinfo#'],
                                    ['text' => '📥 Добавить по артикулу', 'callback_data' => 'updateinfo#']
                                ],
                                [
                                    ['text' => '🔎 Продажи за сегодня по артикулу', 'callback_data' => 'saletodayid#'],
                                    ['text' => '🔎 Продажи за сегодня всего', 'callback_data' => 'saletoday#']
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

function showMeTOP10($idchat,$connect,$text,&$dbResponseArray,$botAPI):array
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

                    $path = str_replace('https://myfunnybant.ru/', '', $rows["guid"]);
                    // запишем в массив все записи которые получили из базы данных

                     $dbResponseArray[] = [
                        'chat_id' => $idchat,
                        'photo' => curl_file_create(__DIR__ . '/' . $path),
                        'caption' => "Название: {$row["sku"]}; \n Артикул: #{$row["sku"]} \n Цена: {$row["max_price"]}. ",
                        'reply_markup'=>$reply_markup,
                    ];
                }
            }
        }
    }

    //parsingDBRequest($dbResponseArray,$rowStart,$rowOfset,$botAPI);

    return $dbResponseArray;
}

function parsingDBRequest($dbResponseArray,$rowStart,$rowOfset,$botAPI,$text){
$idchat = $dbResponseArray[0]['chat_id'];
   $sum = $rowStart;
    for ($i=$rowStart;$i<=$rowOfset;$i++){

        sendTelegram('sendPhoto',$dbResponseArray[$i]);

        // Вывод на экран;


        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup={$keyboard}");
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
function saleToDay($text,$newUser,$connect){

    if ($newUser->status != 'manager'){
        //exit();
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
                        $str = $str.  $dat . ' Артикул - ' . $article . ' Количество - ' .$count. ' 💰 Итого - '. $salePrice ."\n";
              }  
               sendTelegram(
                            'sendMessage',
                            array(
                                'chat_id' => $newUser,
                                'text' => $str,
                            )
                        );
               
           
            }
            
    
}

function otchet(){
    
}

   

