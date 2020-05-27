<?php

include "vk_api.php";
require $_SERVER['DOCUMENT_ROOT'].'/db.php';

const VK_KEY = "4dec5adac64862cecd0ebf2cef7e2aa01bb1e86b42abf2df5731c299d7d1204b80173798e8458dc7243b1";  // Токен сообщества
const ACCESS_KEY = "5a0c82b5";  // Тот самый ключ из сообщества
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
         проверить оплату и поменять ему статус   http://388f125d.ngrok.io/adminpage.php‼
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

    //  если юзер закончит мониторинг, надо добавить условие, что эта ф-я доступная только тем кто оплатил
    if($cmd == 'Стоп' || $cmd == 'СТОП' || $cmd == 'стоп' ||  $cmd == '!стоп')
    {
        $linkDel = mysqli_connect ("localhost","mysql","mysql","avito");
        // доделать удаление урл из бд
        $sql = mysqli_query($linkDel, "UPDATE `users` SET  `urlcount` = '0' WHERE `user_id` = '$id'") or die;
        $sqlToDel = mysqli_query($linkDel, "SELECT `url_request` FROM `requests` WHERE `user_id` = '$id'") or die;
        $sqlToDel2 = mysqli_query($linkDel, "DELETE FROM `requests` WHERE `user_id` = '$id'") or die;

        while($rowSqlDel = mysqli_fetch_array($sqlToDel))
        {
            // тут по url_request удалять url_ads
            $delSql=$rowSqlDel['url_request'];
            $Del =  mysqli_query($linkDel, "DELETE FROM `ads` WHERE `url_request` = '$delSql'") or die;
        }
        $vk->sendMessage($id, "Мониторинг завершен
            ");
    }

    if (($cmd != 'СТАРТ'&&  $cmd != 'старт' && $cmd != 'cnfhn' &&  $cmd != '!старт')
        && ($cmd != '!оплатил' && $cmd != 'ОПЛАТИЛ' && $cmd != 'оплатил' &&  $cmd != 'Оплатил')
        && ($cmd != 'Стоп' && $cmd != 'СТОП' && $cmd != 'стоп' &&  $cmd != '!стоп')
        && (mb_substr($cmd,0,3) != 'URL' && mb_substr($cmd,0,3) != 'url'))
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