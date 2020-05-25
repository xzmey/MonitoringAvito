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
const ACCESS_KEY = "6306747e";  // ключ из сообщества
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

    // цикл из url, которые из бд

foreach ($urlsAll as $url=>$value)
{
    // для проверки , если url содержит больше 5 страниц или кривой
    $select = mysqli_query($link, "SELECT `user_id` FROM `requests` WHERE `url_request` = '$value'");
    $id = mysqli_fetch_array($select);
    $newCount = 0; // счетчик новых объявлений
    $url = $avito->parseAll($value);
    // если url не подошел то оповестит
    if (empty($url))
    {
        $vk->sendMessage($id['user_id'], "‼Ошибка мониторинга: возможно больше 5 страниц по запросу или неправильный url 
        Проверьте ваш url еще раз - {$value}   
            ");
    }

    /*для задания*/
    $date = date("Y-m-d");// дата парсинга

    // если дата не сегодняшняя, то сделать insert с новыми данными
    $selectUserId = mysqli_query($link, "SELECT `user_id` FROM `vk_users` WHERE `vk_id` = '{$id['user_id']}'");
    $user_id = mysqli_fetch_array($selectUserId); // user_id из vk_users
    $median = "SET @row_number:=0;";
    $median .= "SET @median_group:='';";
    $median .= "
  SELECT median FROM (SELECT
      median_group, AVG(value) AS median
  FROM
      (SELECT
          @row_number:=CASE
                  WHEN @median_group = user_id THEN @row_number + 1
                  ELSE 1
              END AS count_of_group,
              @median_group:=user_id AS median_group,
              user_id,
              value,
              (SELECT
                      COUNT(*)
                  FROM
                      prices
                  WHERE
                      a.user_id = user_id) AS total_of_group
      FROM
          (SELECT
          user_id, value
      FROM
          prices
      ORDER BY user_id , value) AS a) AS b
  WHERE
      count_of_group BETWEEN total_of_group / 2.0 AND total_of_group / 2.0 + 1
  GROUP BY median_group)AS result
  WHERE median_group='{$user_id['user_id']}';";
    if ( mysqli_multi_query( $link, $median ) )
    {
        do {
            if ( $result = mysqli_store_result( $link ) ) {
                while ( $row = mysqli_fetch_row( $result ) ) {

                    $medianPrice=$row;
                }
                mysqli_free_result( $result );
            }
        }
        while ( mysqli_more_results( $link ) && mysqli_next_result( $link ) );
    }
    //$medianPrice[0] - тут лежит медиана для юзера
    $priceResult=(double)$medianPrice[0];
    /*
    var_dump($priceResult);
    var_dump($user_id['user_id']);
    */
    // последняя дата для юзера..
    $maxDate = mysqli_fetch_array((mysqli_query($link, "SELECT MAX(`parse_date`) FROM `avg_price` WHERE `user_id`='{$user_id['user_id']}'")));
    //$maxDate[0]- псоледняя дата парсинга для user_id
    if($date>$maxDate[0])
    {
        $insertData = mysqli_query($link, "INSERT INTO `avg_price`(user_id,parse_date,price) VALUES('{$user_id['user_id']}','$date','$priceResult')");
    }
    /*для задания*/

    foreach ($url as $key=>$ad)
    {
        $urlAd = $url[$key]['url'];
        $link = mysqli_connect ("localhost","mysql","mysql","avito");
        $sql = mysqli_query($link, "SELECT `status` FROM `ads` WHERE `url_ad` = '$urlAd'");
        $row = mysqli_fetch_array($sql); // результат ячейки статуса (new/old) по url_ad
        //echo($row['status']);


        //поля: название $ad['name'], цена substr($ad['price'],0,-6), дата подачи date($ad['date']), год(если авто) $ad['year']

        $price = substr($ad['price'],0,-6); // цена
        $intPrice = preg_replace("/\s+/", "", $price);// убираем пробелы для записи в бд с полем типа int
        $selectUserId = mysqli_query($link, "SELECT `user_id` FROM `vk_users` WHERE `vk_id` = '{$id['user_id']}'");
        $user_id = mysqli_fetch_array($selectUserId); // user_id из vk_users
        if (!(R::findOne('prices','url_ad=?',array( $urlAd))))// для задания
        {// для задания
            $insert = mysqli_query($link, "INSERT INTO `prices`(value,url_ad,user_id) VALUES('$intPrice','$urlAd','{$user_id['user_id']}')");// для задания
        }// для задания


        if (!(R::findOne('ads','url_ad=?',array( $urlAd))))
        {

            // если не нашел совпадений по url объявления, то добавляем в бд и статус новый
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
            ✅ URL объявления- {$urlAd} 
            ✅ Посмотреть остальные объявления тут -  http://82f6f616.ngrok.io
            Держим вас в курсе🤙🏻
            ");
            sleep(rand(1,4));
            }
            if ($newCount==10)
            {
                $vk->sendMessage($id['user_id'], "У вас больше 10 новых объявлений, загляните на станицу -  http://82f6f616.ngrok.io/avito.php     
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
