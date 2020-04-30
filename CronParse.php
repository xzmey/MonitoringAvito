<?php
include 'Class.Avito.php';
include 'Class.AvitoContact.php';
include 'Class.Curl.php';
include 'Class.Log.php';
include 'Class.Proxy.php';
include "vk_api.php";
require 'db.php';
error_reporting(0); // отключаем вывод ошибки

const VK_KEY = "4dec5adac64862cecd0ebf2cef7e2aa01bb1e86b42abf2df5731c299d7d1204b80173798e8458dc7243b1";  // Токен сообщества
const ACCESS_KEY = "6306747e";  // Тот самый ключ из сообщества
const VERSION = "5.0"; // Версия API VK

$vk = new vk_api(VK_KEY, VERSION);
$avito = new Avito;

$urlsAll=[];// массив url из бд


// все url из бд записываются в массив $urlsAll
$link = mysqli_connect ("localhost","mysql","mysql","avito");
$sql = mysqli_query($link, "SELECT `url_request` FROM `requests`");
while($row = mysqli_fetch_array($sql))
{
    //echo '<p>'.$row['url_request'].'</p>';
    array_push($urlsAll, $row['url_request']);
}


    $avito->curl->sleepMin = 4;
    $avito->curl->sleepMax = 10;
    $newCount = 0; // счетчик новых объявлений

    // цикл из url, которые из бд

foreach ($urlsAll as $url=>$value)
{
    // для проверки , если url содержит больше 5 страниц или кривой
    $select = mysqli_query($link, "SELECT `user_id` FROM `requests` WHERE `url_request` = '$value'");
    $id = mysqli_fetch_array($select);


    $url = $avito->parseAll($value);
    // если url не подошел то оповестит
    if (empty($url))
    {
        $vk->sendMessage($id['user_id'], "‼Ошибка мониторинга: возможно больше 5 страниц по запросу или неправильный url 
        Проверьте ваш url еще раз - {$value}   
            ");
    }

    foreach ($url as $key=>$ad)
    {
        $urlAd = $url[$key]['url'];
        $link = mysqli_connect ("localhost","mysql","mysql","avito");
        $sql = mysqli_query($link, "SELECT `status` FROM `ads` WHERE `url_ad` = '$urlAd'");
        $row = mysqli_fetch_array($sql); // результат ячейки статуса (new/old) по url_ad
        //echo($row['status']);


        //поля: название $ad['name'], цена substr($ad['price'],0,-6), дата подачи date($ad['date']), год(если авто) $ad['year']
        $price = substr($ad['price'],0,-6); // цена


        if (!(R::findOne('ads','url_ad=?',array( $urlAd))))
        {

            // если не нашел совпадений по url объявления, то добавляем в бд и стату новый
            $newUrl = R::dispense('ads');
            $newUrl->url_request = $value;//url запроса
            $newUrl->url_ad = $urlAd;//url объявления
            $newUrl->status = 'new';
            R::store($newUrl);
            $newCount++;

            $select = mysqli_query($link, "SELECT `user_id` FROM `requests` WHERE `url_request` = '$value'");
            $id = mysqli_fetch_array($select);

            // если больше 10 новых объявлений то не будет присылать
            if ($newCount<10)
            {
            // смс по id челу который мониторит
            $vk->sendMessage($id['user_id'], "🔥🔥🔥Новое объявление🔥🔥🔥 
            ✅ URL запроса - {$value}
            ✅ Название: {$ad['name']}
            ✅ Цена: {$price}
            ✅ Дата подачи: {$ad['date']}
            ✅ Год(если авто): {$ad['year']}
            ✅ URL объявления- {$urlAd},    
            Держим вас в курсе🤙🏻
            ");
            }
            if ($newCount==10)
            {
                $vk->sendMessage($id['user_id'], "У вас больше 10 новых объявлений, загляните на станицу -...     
            ");
            }
        }
        if  ((R::findOne('ads','url_ad=?',array( $urlAd))) && ($row['status'] == 'new'))
        {
            //если уже было , то обновляем статус на old
            $sql = mysqli_query($link, "UPDATE `ads` SET  `status` = 'old' WHERE `url_ad` = '$urlAd'") or die;
        }
        if  ((R::findOne('ads','url_ad=?',array( $urlAd))) && ($row['status'] == 'old'))
        {
            //если old, то пропускаем и не меняем
            continue;
        }
    }
    sleep(rand(10,25));
}
?>
