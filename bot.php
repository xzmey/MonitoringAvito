<?php
include "vk_api.php";
include 'pChart/pData.class.php';
include 'pChart/pCache.class.php';
include 'pChart/pChart.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/db.php';

const VK_KEY = "4dec5adac64862cecd0ebf2cef7e2aa01bb1e86b42abf2df5731c299d7d1204b80173798e8458dc7243b1";  // Токен сообщества
const ACCESS_KEY = "a024f3e1";  // Тот самый ключ из сообщества
const VERSION = "5.0"; // Версия API VK

error_reporting(0);
$vk = new vk_api(VK_KEY, VERSION);
$data = json_decode(file_get_contents('php://input'));

if ($data->type == 'confirmation')
{
    exit(ACCESS_KEY);
}
$vk->sendOK();

$id = $data->object->user_id; // Узнаем ID пользователя, кто написал нам
$message = $data->object->body; // Само сообщение от пользователя
$cmd = mb_strtolower($message, 'utf-8');
$userInfo = $vk->request("users.get", ["user_ids" => $id]); // при помощи метода users.get мы получаем информацию о пользователи
$first_name = $userInfo[0]['first_name']; // Вытащили из пришедший информации имя пользователя


if ($data->type == 'message_new')
{
    // начало
    if($cmd == 'СТАРТ' || $cmd == 'старт' || $cmd == 'cnfhn' ||  $cmd == '!старт')
    {
        $link = mysqli_connect ("localhost","mysql","mysql","avito"); // для задания
        $vk->sendMessage($id, "
        Чтобы пользоваться ботом, нужно оплатить подписку✅ 
        После оплаты напишите ОПЛАТИЛ✅ 
            ");
        $vk_user = R::findOne('users', 'user_id = ?', array($id));
        if (!$vk_user)
        {
            $new_user = R::dispense('users');
            $new_user->user_id = $id;
            $new_user->urlcount=0; // счетчик url - максимум 2 url на юзера
            $new_user->status='без оплаты'; // статус оплаты, меняется админом
            R::store($new_user);
        }
        $vk_user2 = R::findOne('vk_users', 'vk_id = ?', array($id));// для задания
        if(!$vk_user2)// для задания
        {// для задания
            $insert = mysqli_query($link, "INSERT INTO `vk_users`(vk_id) VALUES('$id')");// для задания
        }// для задания

    }
    // оплата, если отправилась команда, то отправить оповещение админу
    if($cmd == '!оплатил' || $cmd == 'ОПЛАТИЛ' || $cmd == 'оплатил' ||  $cmd == 'Оплатил')
    {
        $userBuy = R::findOne('users', 'user_id = ?', array($id));
        if ($userBuy)
        {
            $vk->sendMessage($id, "Ожидайте, пока админ проверит статус оплаты💤 
            ");

            // оповещения для админа об оплате
            // id - мой
            $vk->sendMessage('580612278', " https://vk.com/id{$id} оплатил подписку,
         проверить оплату и поменять ему статус   http://ae839705.ngrok.io/adminpage.php‼
            ");

        }
        else
         {
             $vk->sendMessage($id, "Для начала напишите команду СТАРТ, чтобы вас внесли в базу 
            ");
         }
    }

    if(mb_substr($cmd,0,3) == 'URL' || mb_substr($cmd,0,3) == 'url')
    {
        $url = mb_substr($message, 4);
        $parse_url = parse_url($url);
        // если мобильная версия url
        if (stristr($parse_url['host'],'m') == true)
        {
            $url_new['host'] = substr($parse_url['host'],2);
            $url_new['query'] = substr($parse_url['query'],5);
            $url_new = $parse_url['scheme'].'://www.'.$url_new['host'].$parse_url['path'].'?q'.$url_new['query'];
            $url = $url_new;
        }

        $link = mysqli_connect ("localhost","mysql","mysql","avito");
        $sql = mysqli_query($link, "SELECT `status` FROM `users` WHERE `user_id` = '$id'");
        $sqlUrlCount = mysqli_query($link, "SELECT `urlcount` FROM `users` WHERE `user_id` = '$id'");
        $row = mysqli_fetch_array($sql);
        $rowUrlCount = mysqli_fetch_array($sqlUrlCount);
            if ($row['status'] == 'оплатил')
            {
                if ($url=='')
                {   // если пусто
                    $vk->sendMessage($id, "💬введите url
                    ");
                }
                else
                {
                    // добавить +1 url в url count
                    if ($rowUrlCount['urlcount'] == '0')
                    {
                        $sql = mysqli_query($link, "UPDATE `users` SET  `urlcount` = '1' WHERE `user_id` = '$id'") or die;

                        $vk->sendMessage($id, "💬Поздравляем, {$first_name}, Вы начали мониторинг
                    ");

                        // запись в бд url_req по user_id
                        $new_req = R::dispense('requests');
                        $new_req->user_id = $id;
                        $new_req->url_request = $url;
                        R::store($new_req );
                    }


                    if ($rowUrlCount['urlcount'] == '1')
                    {
                        $sql = mysqli_query($link, "UPDATE `users` SET  `urlcount` = '2' WHERE `user_id` = '$id'") or die;
                        $vk->sendMessage($id, "💬Поздравляем, {$first_name}, Вы мониторите еще 1 url
                    ");
                        // запись в бд url_req по user_id
                        $new_req = R::dispense('requests');
                        $new_req->user_id = $id;
                        $new_req->url_request = $url;
                        R::store($new_req );
                        // тут надо добавить парсинг по url
                    }
                    if ($rowUrlCount['urlcount'] == '2')
                    {
                        $vk->sendMessage($id, "💬Вы мониторите максимальное кол-во url, напишите СТОП, чтобы закончить мониторинг и ввести новые url
                    ");
                    }

                }
            }
            else
            $vk->sendMessage($id, "💬Вам не доступна эта команда
            ");
    }


    if($cmd == '!динамика' || $cmd == 'Динамика' || $cmd == 'динамика' ||  $cmd == 'Динамика')
    {
        if ( R::findOne('requests', 'user_id = ?', array($id))) {
            $link = mysqli_connect("localhost", "mysql", "mysql", "avito");
            $urls = mysqli_query($link, "SELECT `url_request` FROM `requests` WHERE  `user_id`= '$id'");

            $Requests = [];
            while ($rowUrls = mysqli_fetch_array($urls)) {
                array_push($Requests, $rowUrls[0]);
            }
            foreach ($Requests as $req => $r) {
                $selectId = mysqli_query($link, "SELECT `user_id` FROM `vk_users` WHERE `vk_id`='{$id}'");
                $user_id = mysqli_fetch_array($selectId);
                $myData = new pData();
                $sql = mysqli_query($link, "SELECT `parse_date`,`price` FROM `avg_price` WHERE `user_id`='{$user_id['user_id']}' AND `url_req`='$r'");


                while ($row = mysqli_fetch_array($sql)) {

                    var_dump($row['price']);
                    var_dump($row['parse_date']);

                    $myData->AddPoint($row['price'], "price");
                    $myData->AddPoint($row['parse_date'], "date");

                }

// x — это ось абсцисс, а y —  ось ординат

//устанавливаем точки с датами
//на ось абсцисс
                $myData->SetAbsciseLabelSerie("date");
//помечаем данные как предназначеные для
//отображения
                $myData->AddSerie("price");
//создаем график шириной в 1000 и высотой в 500 px
                $graph = new pChart(1000, 500);
//устанавливаем шрифт и размер шрифта
                $graph->setFontProperties("Fonts/tahoma.ttf", 8);
//устанавливаем имена
                $myData->SetSerieName(
                    mb_convert_encoding("Сумма", 'utf-8', 'utf-8'),
                    "price");
//координаты левой верхней вершины и правой нижней
//вершины графика
                $graph->setGraphArea(85, 30, 950, 400);
//рисуем заполненный четырехугольник
                $graph->drawFilledRoundedRectangle(7, 7, 993, 493, 5, 240,
                    240, 240);
//теперь незаполненный для эффекта тени
                $graph->drawRoundedRectangle(5, 5, 995, 495, 5, 230,
                    230, 230);
//устанавливаем данные для графиков
                $graph->drawScale($myData->GetData(),
                    $myData->GetDataDescription(),
                    SCALE_NORMAL, 150, 150, 150, true, 0, 2);
//рисуем сетку для графика
                $graph->drawGrid(4, TRUE, 230, 230, 230, 50);
//прорисовываем линейные графики
                $graph->drawLineGraph($myData->GetData(),
                    $myData->GetDataDescription());
// рисуем точки на графике
                $graph->drawPlotGraph($myData->GetData(),
                    $myData->GetDataDescription(), 4, 0, 255, 255, 255);
//ложим легенду
                $graph->drawLegend(90, 35, $myData->GetDataDescription(), 150, 150, 150);
//Пишем заголовок
                $graph->setFontProperties("Fonts/tahoma.ttf", 13);
                $graph->drawTitle(480, 22,
                    mb_convert_encoding("Динамика цены",
                        'utf-8', 'utf-8'),
                    50, 50, 50, -1, -1, false);
//выводим в браузер
//$graph->Stroke();
                $number = $req + 1;
                $graph->Render("graph{$number}.png");
                $vk->sendMessage($id, "💬Динамика цены👉🏻
            http://963e9f4e9779.ngrok.io/graph{$number}.png
            ____________________________________
            ✅по запросу👉🏻 {$r}
            {$row['price']}
            
                    ");
            }
        }
        else
            $vk->sendMessage($id, "💬Вам не доступна эта команда
            ");
    }


    //  если юзер закончит мониторинг, надо добавить условие, что эта ф-я доступная только тем кто оплатил
    if($cmd == 'Стоп' || $cmd == 'СТОП' || $cmd == 'стоп' ||  $cmd == '!стоп')
    {
        if ( R::findOne('requests', 'user_id = ?', array($id))) {
            $linkDel = mysqli_connect("localhost", "mysql", "mysql", "avito");
            // доделать удаление урл из бд
            $sql = mysqli_query($linkDel, "UPDATE `users` SET  `urlcount` = '0' WHERE `user_id` = '$id'") or die;
            $sqlToDel = mysqli_query($linkDel, "SELECT `url_request` FROM `requests` WHERE `user_id` = '$id'") or die;
            $sqlToDel2 = mysqli_query($linkDel, "DELETE FROM `requests` WHERE `user_id` = '$id'") or die;

            while ($rowSqlDel = mysqli_fetch_array($sqlToDel)) {
                // тут по url_request удалять url_ads
                $delSql = $rowSqlDel['url_request'];
                $Del = mysqli_query($linkDel, "DELETE FROM `ads` WHERE `url_request` = '$delSql'") or die;
            }
            $vk->sendMessage($id, "Мониторинг завершен
            ");
        }
        else
            $vk->sendMessage($id, "💬Вам не доступна эта команда
            ");
    }

    if
    (($cmd != 'СТАРТ'&&  $cmd != 'старт' && $cmd != 'cnfhn' &&  $cmd != '!старт')
        && ($cmd != '!оплатил' && $cmd != 'ОПЛАТИЛ' && $cmd != 'оплатил' &&  $cmd != 'Оплатил')
        && ($cmd != 'Стоп' && $cmd != 'СТОП' && $cmd != 'стоп' &&  $cmd != '!стоп')
        && (mb_substr($cmd,0,3) != 'URL' && mb_substr($cmd,0,3) != 'url')
        && ($cmd != '!динамика' && $cmd != 'Динамика' && $cmd != 'динамика' &&  $cmd != 'Динамика'))
    {
    $vk->sendMessage($id, "💬 Привет, {$first_name}
            
            ‼ Команды для нашего бота: 
            СТАРТ
            URL (вставить url после пробела)
            СТОП
            
            ✅ Правила использования - https://vk.com/topic-194502565_41311409
            ");
    }
}

$fileName = 'log.bot.txt';
$f = fopen($fileName, 'a+');
file_put_contents($fileName, json_encode($data). PHP_EOL, FILE_APPEND);
fclose($f);