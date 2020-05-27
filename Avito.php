<?php
/*
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
*/
include 'Class.Avito.php';
include 'Class.AvitoContact.php';
include 'Class.Curl.php';
include 'Class.Log.php';
include 'Class.Proxy.php';
require $_SERVER['DOCUMENT_ROOT'].'/db.php';
error_reporting(0); // отключаем вывод ошибки


function POST($key, $default='')
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}

function GET($key, $default='')
{
    if (array_key_exists($key, $_GET)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}

$avito = new Avito;


session_start();
if(!$_SESSION['login'])
{
    header('location:/auth.php'); //переадресация на страницу входа
    exit();
}
// если не нашли в бд, то офаем

if  (R::findOne('users', 'user_id = ?', array($_SESSION['login']['id'])))
{


if ($_POST['action'] == 'parseCard')
{
    $avito->curl->sleepMin = 3;
    $avito->curl->sleepMax = 8;
    $avito->parseCard($_POST['url'], $row);
    // описание
    echo '<hr/><h3><strong>Описание объявления: </strong></h3>';
    echo $row['text'];
    echo '<h3><strong>Параметры: </strong></h3>';
    // параметры
    foreach ($row['params'] as $key => $name)
    {
        echo '<strong>' . $key . '</strong>' . $name . PHP_EOL . '<br/>';
    }
    // статистика
    echo '<h3><strong>Статистика: </strong></h3>';
    echo '<strong>Всего просмотров: </strong>' . $row['views-total'] . '<br/>';
    //echo '<strong>Просмотров за сегодня: </strong>'.$row['views-today'];
    // фото
    echo '<h3><strong>Фото: </strong></h3>';
    // скрипт для спойлера фото

    ?>
    <div class="spoiler_links blue">Спойлер (кликните для открытия/закрытия)</div>
    <div class="spoiler_body">
        <?php
        foreach ($row['images'] as $key => $value) {
            echo '<img src="' . $value . '"width="300 px">';
        }
        ?>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.spoiler_links').click(function () {
                $(this).next('.spoiler_body').toggle('normal');
                return false;
            });
        });
    </script>

    <?php
    /*
    //echo '<pre>'; print_r($row); echo '</pre>';
    */
    exit;
}


if ($_POST['action'] == 'new')
{
    $allNew = [];
    $data = $avito->parseAllForUser($_POST['url']);
    foreach ($data as $key => $value) {
        $urlAd = $data[$key]['url'];
        $link = mysqli_connect("localhost", "mysql", "mysql", "avito");
        $sql = mysqli_query($link, "SELECT `status` FROM `ads` WHERE `url_ad` = '$urlAd'");
        $row = mysqli_fetch_array($sql); // результат ячейки статуса (new/old) по url_ad
        //echo($row['status'])
        if ($row['status'] == 'new')
        {
            // засунем все новые объявления в массив, чтобы потом вывести его при клике
            array_push($allNew, $data[$key]);
        }
    }
    ?>
    <table class="table table-condensed table-bordered table-hover" style="width:auto">
        <tr>
            <th>Название</th>
            <th>Цена</th>
            <th>Год</th>
            <th>Дата</th>
        </tr>
        <?php
        foreach ($allNew as $k => $row) {
            ?>
            <tr>
                <td><a href="<?= $row['url'] ?>" target="_blank"><?= $row['name'] ?></a></td>
                <td class="text-right"><?= substr($row['price'], 0, -6) ?></td>
                <td><?= $row['year'] ?></td>
                <td><?= date($row['date']) ?></td>
            </tr>
            <?php
        }
        ?>
    </table>


    <div class="col-md-6" id="results">

    </div>

    <?php
    exit;
}


//фото из сессии
$photo = $_SESSION['login']['ph'];

if ($_POST['action'] == 'parsePhone') {

    $avito->curl->sleepMin = 3;
    $avito->curl->sleepMax = 8;

    // Подготавливаем входные параметры

    // кэш месяц в обоих случаях, тк телефон не поменяется
    $url = $_POST['url'];
    preg_match('~_(\d+)$~i', $url, $a);
    $id = $a[1];
    $cardContent = $avito->curl->phoneLoad($url, 2419200);
    // Определяем phoneUrl
    $avitoContact = new AvitoContact;
    $phoneUrl = $avitoContact->getPhoneUrl($cardContent, $id);
    //var_dump($phoneUrl);

    // Грузим картинку по phoneUrl
    $imgContent = $avito->curl->phoneLoad($phoneUrl, 2419200);
    // Контент дошел...
    // Разбираем ее и сохраняем в файл
    preg_match('~{(.*?)}~is', $imgContent, $jj);

    $img = json_decode($jj[0]);

    $avitoContact->saveInFile($img->image64, 'phone.png');

    // Распознаем файл
    $result = $avitoContact->recognize('phone.png');


    //echo '<p>URL: '.$phoneUrl.'</p>';

    //echo '<p><img src="'.$img->image64.'" alt="" /></p>';

    //echo '<p><a href="#" onclick="jQuery(\'#debugOutput\').slideToggle(); return false;">Цветовая схема</a></p>';
    echo '<div id="debugOutput" style="display:none;">' . $avitoContact->debugOutput . '</div>';

    if ($result) {
        echo '<h2 class="text-success">Телефон: ' . $result . '</h2>';
    } else {
        echo '<h2 class="text-danger">Ничего не получилось</h2>';
    }
    exit;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Парсер объявлений Авито</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
          integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">


    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
        body
        {
            background-repeat: no-repeat;
            background-position: 0 0;
            background-size: cover;
            background-image: url(images/avitus5.jpg);
            background-blend-mode: normal;
        }

        h1
        {
            margin: 20px 0 15px;
            font-size: 24px;
        }

        .avito-form > div
        {
            margin-right: 10px;
        }

        .form-inline .form-group
        {
            margin-bottom: 10px;
        }

        /*аватар юзера*/
        .avatar {
            margin: 5px;
            width: 85px;
            height: 85px;
            border-radius: 50%;
        }

        /* лоадер на css */
        #loader
        {
            border: 5px solid #f3f3f3; /* Light grey */
            border-top: 5px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 52px;
            height: 52px;
            animation: spin 2s linear infinite;
            position: fixed;
            top: 7px;
            left: 10px;
            display: none;
        }

        /*анимация новых объявлений*/
        .area {
            font-size: 2.5em;
            color: #00a86b;
            letter-spacing: -7px;
            font-weight: 400;
            text-transform: uppercase;
            animation: blur .75s ease-out infinite;
            text-shadow: 0px 0px 5px #fff, 0px 0px 7px #fff;
        }

        @keyframes blur {
            from {
                text-shadow: 0px 0px 10px #fff,
                0px 0px 10px #fff,
                0px 0px 25px #fff,
                0px 0px 25px #fff,
                0px 0px 25px #fff,
                0px 0px 25px #fff,
                0px 0px 25px #fff,
                0px 0px 25px #fff,
                0px 0px 50px #fff,
                0px 0px 50px #fff,
                0px 0px 50px #7B96B8,
                0px 0px 150px #7B96B8,
                0px 10px 100px #7B96B8,
                0px 10px 100px #7B96B8,
                0px 10px 100px #7B96B8,
                0px 10px 100px #7B96B8,
                0px -10px 100px #7B96B8,
                0px -10px 100px #7B96B8;
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        /*тэг для изменения междустрочечного расстояния*/
        p
        {
            line-height: 1.5;
        }
        /*спойлер для фото*/
        .spoiler_body
        {
            display: none;
            font-style: italic;
        }

        .spoiler_links
        {
            cursor: pointer;
            font-weight: bold;
            text-decoration: underline;
        }

        .blue
        {
            color: #006699;
        }

        select
        {
            width: 250px; /* Ширина списка в пикселах */
            height: 30px;
        }
        button
        {
            text-align: center;
            height: 35px;
        }

        #floatBarsG{
            position:relative;
            width:234px;
            height:28px;
            margin:auto;
        }

        .floatBarsG{
            position:absolute;
            top:0;
            background-color:rgb(0,0,0);
            width:28px;
            height:28px;
            animation-name:bounce_floatBarsG;
            -o-animation-name:bounce_floatBarsG;
            -ms-animation-name:bounce_floatBarsG;
            -webkit-animation-name:bounce_floatBarsG;
            -moz-animation-name:bounce_floatBarsG;
            animation-duration:1.5s;
            -o-animation-duration:1.5s;
            -ms-animation-duration:1.5s;
            -webkit-animation-duration:1.5s;
            -moz-animation-duration:1.5s;
            animation-iteration-count:infinite;
            -o-animation-iteration-count:infinite;
            -ms-animation-iteration-count:infinite;
            -webkit-animation-iteration-count:infinite;
            -moz-animation-iteration-count:infinite;
            animation-direction:normal;
            -o-animation-direction:normal;
            -ms-animation-direction:normal;
            -webkit-animation-direction:normal;
            -moz-animation-direction:normal;
            transform:scale(.3);
            -o-transform:scale(.3);
            -ms-transform:scale(.3);
            -webkit-transform:scale(.3);
            -moz-transform:scale(.3);
        }

        #floatBarsG_1{
            left:0;
            animation-delay:0.6s;
            -o-animation-delay:0.6s;
            -ms-animation-delay:0.6s;
            -webkit-animation-delay:0.6s;
            -moz-animation-delay:0.6s;
        }

        #floatBarsG_2{
            left:29px;
            animation-delay:0.75s;
            -o-animation-delay:0.75s;
            -ms-animation-delay:0.75s;
            -webkit-animation-delay:0.75s;
            -moz-animation-delay:0.75s;
        }

        #floatBarsG_3{
            left:58px;
            animation-delay:0.9s;
            -o-animation-delay:0.9s;
            -ms-animation-delay:0.9s;
            -webkit-animation-delay:0.9s;
            -moz-animation-delay:0.9s;
        }

        #floatBarsG_4{
            left:88px;
            animation-delay:1.05s;
            -o-animation-delay:1.05s;
            -ms-animation-delay:1.05s;
            -webkit-animation-delay:1.05s;
            -moz-animation-delay:1.05s;
        }

        #floatBarsG_5{
            left:117px;
            animation-delay:1.2s;
            -o-animation-delay:1.2s;
            -ms-animation-delay:1.2s;
            -webkit-animation-delay:1.2s;
            -moz-animation-delay:1.2s;
        }

        #floatBarsG_6{
            left:146px;
            animation-delay:1.35s;
            -o-animation-delay:1.35s;
            -ms-animation-delay:1.35s;
            -webkit-animation-delay:1.35s;
            -moz-animation-delay:1.35s;
        }

        #floatBarsG_7{
            left:175px;
            animation-delay:1.5s;
            -o-animation-delay:1.5s;
            -ms-animation-delay:1.5s;
            -webkit-animation-delay:1.5s;
            -moz-animation-delay:1.5s;
        }

        #floatBarsG_8{
            left:205px;
            animation-delay:1.64s;
            -o-animation-delay:1.64s;
            -ms-animation-delay:1.64s;
            -webkit-animation-delay:1.64s;
            -moz-animation-delay:1.64s;
        }



        @keyframes bounce_floatBarsG{
            0%{
                transform:scale(1);
                background-color:rgb(0,0,0);
            }

            100%{
                transform:scale(.3);
                background-color:rgb(255,255,255);
            }
        }

        @-o-keyframes bounce_floatBarsG{
            0%{
                -o-transform:scale(1);
                background-color:rgb(0,0,0);
            }

            100%{
                -o-transform:scale(.3);
                background-color:rgb(255,255,255);
            }
        }

        @-ms-keyframes bounce_floatBarsG{
            0%{
                -ms-transform:scale(1);
                background-color:rgb(0,0,0);
            }

            100%{
                -ms-transform:scale(.3);
                background-color:rgb(255,255,255);
            }
        }

        @-webkit-keyframes bounce_floatBarsG{
            0%{
                -webkit-transform:scale(1);
                background-color:rgb(0,0,0);
            }

            100%{
                -webkit-transform:scale(.3);
                background-color:rgb(255,255,255);
            }
        }

        @-moz-keyframes bounce_floatBarsG{
            0%{
                -moz-transform:scale(1);
                background-color:rgb(0,0,0);
            }

            100%{
                -moz-transform:scale(.3);
                background-color:rgb(255,255,255);
            }
        }

        aside
        {
            padding: 0px;
            width: 325px;
            float: right;
            font-size: 20px;
        }

        /*баннер со слайдами*/



        #slider { /*положение слайдера*/
            position: relative;
            text-align: center;
            top: 10px;
        }

        #slider{ /*центровка слайдера*/
            margin: 0 auto;
        }

        #slides article{ /*все изображения справа друг от доруга*/
            width: 20%;
            float: left;
        }

        #slides .image{ /*устанавливает общий размер блока с изображениями*/
            width: 500%;
            line-height: 0;
        }

        #overflow{ /*сркывает все, что находится за пределами этого блока*/
            width: 100%;
            overflow: hidden;
        }

        article img{ /*размер изображений слайдера*/
            width: 100%;
        }

        #desktop:checked ~ #slider{ /*размер всего слайдера*/
            max-width: 960px; /*максимальнная длинна*/
        }

        /*настройка переключения и положения для левой стрелки*/
        /*если свич1-5 активны, то идет обращение к лейблу из блока с id контролс*/
        #switch1:checked ~ #controls label:nth-child(3),
        #switch2:checked ~ #controls label:nth-child(1),
        #switch3:checked ~ #controls label:nth-child(2)
        {
            background: url('images/prev.png') no-repeat; /*заливка фона картинкой без повторений*/
            float: left;
            margin: 0 0 0 -84px; /*сдвиг влево*/
            display: block;
            height: 68px;
            width: 68px;
        }

        /*настройка переключения и положения для правой стрелки*/
        #switch1:checked ~ #controls label:nth-child(2),
        #switch2:checked ~ #controls label:nth-child(3),
        #switch3:checked ~ #controls label:nth-child(1)
        {
            background: url('images/next.png') no-repeat; /*заливка фона картинкой без повторений*/
            float: right;
            margin: 0 -84px 0 0; /*сдвиг вправо*/
            display: block;
            height: 68px;
            width: 68px;
        }

        label, a{ /*при наведении на стрелки или переключатели - курсор изменится*/
            cursor: pointer;
        }

        .all input{ /*скрывает стандартные инпуты (чекбоксы) на странице*/
            display: none;
        }

        /*позиция изображения при активации переключателя*/
        #switch1:checked ~ #slides .image{
            margin-left: 0;
        }

        #switch2:checked ~ #slides .image{
            margin-left: -100%;
        }

        #switch3:checked ~ #slides .image{
            margin-left: -200%;
        }

        #controls{ /*положение блока всех управляющих элементов*/
            margin: -25% 0 0 0;
            width: 100%;
            height: 50px;
        }

        #active label{ /*стиль отдельного переключателя*/
            border-radius: 10px; /*скругление углов*/
            display: inline-block; /*расположение в строку*/
            width: 15px;
            height: 15px;
            background: #bbb;
        }

        #active{ /*расположение блока с переключателями*/
            margin: 23% 0 0;
            text-align: center;
        }

        #active label:hover{ /*поведение чекбокса при наведении*/
            background: #76c8ff;
            border-color: #777 !important; /*выполнение в любом случае*/
        }

        /*цвет активного лейбла при активации чекбокса*/
        #switch1:checked ~ #active label:nth-child(1),
        #switch2:checked ~ #active label:nth-child(2),
        #switch3:checked ~ #active label:nth-child(3)

        {
            background: #18a3dd;
            border-color: #18a3dd !important;
        }

        #slides .image{ /*анимация пролистывания изображений*/
            transition: all 800ms cubic-bezier(0.770, 0.000, 0.175, 1.000);
        }

        #controls label:hover{ /*прозрачность стрелок при наведении*/
            opacity: 0.6;
        }

        #controls label{ /*прозрачность стрелок при отводе курсора*/
            transition: opacity 0.2s ease-out;
        }

    </style>
</head>
<body>

<div id="loader"></div>

<div class="container-fluid">

    <h2 align="right"><strong>Выполнен вход:
            <?php echo $photo;?>
            <img src="<?
            $photo ?>" align="right" alt="Avatar" class="avatar">
        </strong></h2>

    <h2 align="right"><strong><?php
            $userName= $_SESSION['login']['name'];
            echo $userName;
            ?>
        </strong></h2>
    <h2><strong>Выберите ваш url <span class="glyphicon glyphicon-hand-down" aria-hidden="true"></span></strong></h2>

    <?php
    // выводим url для юзера из бд
    $sessionId = $_SESSION['login']['id'];
    $selectLink = mysqli_connect ("localhost","mysql","mysql","avito");
    $sqlSelectUrl = mysqli_query($selectLink, "SELECT `url_request` FROM `requests` WHERE `user_id` = '$sessionId'") or die;
    ?>
    <form class="form-inline avito-form" method="post">
        <div class="form-group input-group">

            <?php
            echo "<select name=\"url\">";
            while($rowUrl = mysqli_fetch_array($sqlSelectUrl))
            {
                echo "<option>".$rowUrl['url_request']."</option>";
            }
            echo "</select>";

            ?>
            <span class="input-group-addon"><a href="<?= $_POST['url'] ?>" target="_blank">URL</a></span>
            <span class="input-group-addon"><a href="/PriceDynamics.php?url_req='<?=urlencode($_POST['url'])?>" target="_blank">Показать динамику цены</a></span>
            <button type="submit" class="btn btn-default">Выполнить</button>
        </div>
    </form>
</div>


<?php

if ($_POST['url'])
{
    $avito->curl->sleepMin = 4;
    $avito->curl->sleepMax = 9;
    $data = $avito->parseAllForUser($_POST['url']);
    if (empty($data))
    {
        echo '<h1><strong>Ваш запрос еще обрабытывается, ждите уведомление от бота&nbsp;
<span id="floatBarsG">
	<div id="floatBarsG_1" class="floatBarsG"></div>
	<div id="floatBarsG_2" class="floatBarsG"></div>
	<div id="floatBarsG_3" class="floatBarsG"></div>
	<div id="floatBarsG_4" class="floatBarsG"></div>
	<div id="floatBarsG_5" class="floatBarsG"></div>
	<div id="floatBarsG_6" class="floatBarsG"></div>
	<div id="floatBarsG_7" class="floatBarsG"></div>
	<div id="floatBarsG_8" class="floatBarsG"></div>
</span>
</strong></h1>';
        exit;
    }
    //кол-во объявлений
    $count = count($data);

    // запись в бд объявлений
    // если такого объявления нет в бд, то записывает его и ставим статус new

    $newCount = 0; // счетчик новых объявлений
    foreach ($data as $key => $value) {

        $urlAd = $data[$key]['url'];
        $link = mysqli_connect("localhost", "mysql", "mysql", "avito");
        $sql = mysqli_query($link, "SELECT `status` FROM `ads` WHERE `url_ad` = '$urlAd'");
        $row = mysqli_fetch_array($sql); // результат ячейки статуса (new/old) по url_ad
        //echo($row['status'])
        if ($row['status'] == 'new')
        {
            // засунем все новые объявления в массив, чтобы потом вывести его при клике
            $newCount++;
        }

        /*
        if (!(R::findOne('ads', 'url_ad=?', array($urlAd)))) {
            // если не нашел совпадений по url объявления, то добавляем в бд и стату новый
            $newUrl = R::dispense('ads');
            $newUrl->url_request = $_POST['url'];//url запроса
            $newUrl->url_ad = $urlAd;//url объявления
            $newUrl->status = 'new';
            R::store($newUrl);
            $newCount++;
        }
        if ((R::findOne('ads', 'url_ad=?', array($urlAd))) && ($row['status'] == 'new')) {
            //если уже было , то обновляем статус на old
            $sql = mysqli_query($link, "UPDATE `ads` SET  `status` = 'old' WHERE `url_ad` = '$urlAd'") or die;
        }
        if ((R::findOne('ads', 'url_ad=?', array($urlAd))) && ($row['status'] == 'old')) {
            //если old, то пропускаем и не меняем
            continue;
        }

        */
    }
    // если есть новые объявления анимирует текст и выведет кол-во
    if ($newCount > 0)
    {
        echo '<h3><strong>Всего объявлений: ' . $count.'</strong></h3>';
        echo '<h3><strong> Новых объявлений:<a class="area">' . $newCount . '</a></strong></h3>';
        ?>
        <aside>
            <p><a href="#" data-new="<?= $_POST['url']  ?>" class="label label-warning">Показать новые объявления</a>
            </p>
        </aside>

        <?php
    } else // просто выведет кол-во объявлений
    {
        echo '<h3><strong>Всего объявлений: ' . $count . '</strong></h3>';
    }
    ?>
    <hr/>
    <div class="row">
        <div class="col-md-6">


            <table class="table table-condensed table-bordered table-hover" style="width:auto">
                <tr>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Год</th>
                    <th>Дата</th>
                    <th>&nbsp;</th>
                </tr>
                <?php
                foreach ($data as $k => $row) {
                    ?>
                    <tr>
                        <td><a href="<?= $row['url'] ?>" target="_blank"><?= $row['name'] ?></a></td>
                        <td class="text-right"><?= $row['price']?></td>
                        <td><?=$row['year']?></td>
                        <td><?= date($row['date']) ?></td>
                        <td>
                            <p><a href="#" data-url="<?= $row['url'] ?>" class="label label-info">Просмотр
                                    объявления</a>
                            <p><a href="#" data-phone="<?= $row['url'] ?>" class="label label-warning">Показать
                                    телефон</a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

        </div>
        <div class="col-md-6" id="results">
        </div>
    </div>
    <?php


}
elseif ($_GET['action'] == 'contact')
{
    $avitoContact = new AvitoContact;

    $imageScheme = $avitoContact->getImageScheme('phone.png', $_POST['columnFrom'], $_POST['columnTo']);
    ?>
    <h3>Разбор телефона</h3>

    <form method="post" class="form-inline">

        <div class="form-group">
            <label>Показать колонку с индекса</label>
            <input type="text" name="columnFrom" class="form-control" style="width:75px;"
                   value="<?= $_POST['columnFrom'] ?>"/>
            до
            <input type="text" name="columnTo" class="form-control" style="width:75px;"
                   value="<?= $_POST['columnTo'] ?>"/>
        </div>

        <input class="btn btn-info" type="submit" value="Показать">
    </form>

    <hr/>

    <?php

    echo $avitoContact->debugOutput;

    if ($_POST['columnTo']) {
        $textarea = $avitoContact->makeColumnData($imageScheme, $_POST['columnFrom'], $_POST['columnTo']);
        echo '<textarea style="width:100%; height:200px; white-space: nowrap; font-size: 12px;">' . $textarea . '</textarea>';
    }

    $phoneNumber = $avitoContact->recognizeByScheme($imageScheme);

    if ($avitoContact->error) {
        echo '<p class="alert alert-danger">' . $avitoContact->error . '</p>';
    }

    if ($phoneNumber) {
        $phoneNumber = 'Найдено значение - <b>' . $phoneNumber . '</b>';
    } else {
        $phoneNumber = 'Не найдено ни одного символа';
    }


    echo '<p class="badge" style="margin-top:10px; font-size:60px;">' . $phoneNumber . '</p><br /><br />';

}
else
{
    ?>
    <br/>
    <div class="all">
        <input checked type="radio" name="respond" id="desktop">
        <article id="slider">
            <input checked type="radio" name="slider" id="switch1">
            <input type="radio" name="slider" id="switch2">
            <input type="radio" name="slider" id="switch3">
            <div id="slides">
                <div id="overflow">
                    <div class="image">
                        <article><img src="images/1avito.jpg"></article>
                        <article><img src="images/2avito.jpg"></article>
                        <article><img src="images/3avito.jpg"></article>
                    </div>
                </div>
            </div>
            <div id="controls">
                <label for="switch1"></label>
                <label for="switch2"></label>
                <label for="switch3"></label>
            </div>
            <div id="active">
                <label for="switch1"></label>
                <label for="switch2"></label>
                <label for="switch3"></label>
            </div>
        </article>
    </div>
    <footer>
        <h2 align = "right"><strong>По всем вопросам
                <a href="https://vk.com/xzm3y">сюда</a></strong></h2>
    </footer>
    <?php
}
}
else
{
    echo'<h1><div style="color: red;">Доступ ограничен, напишите боту СТАРТ, чтобы вас внесли в базу данных</div></h1>';
}
?>
<meta charset="utf-8">



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>



<script type="text/javascript">

    $(document).ready(function()
    {

        $(document).ajaxStart(function() {
            $('#loader').show();
        });
        $(document).ajaxStop(function() {
            $('#loader').hide();
        });

        $('[type="submit"]').click(function() {
            setTimeout(function(obj) {
                obj.disabled = true;
            }, 100, this);
        })

        $('[data-url]').click(function(e) {
            e.preventDefault()
            var url = $(this).data('url')
            $.post('', 'action=parseCard&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })

        $('[data-phone]').click(function(e) {
            e.preventDefault()
            var url = $(this).data('phone')
            $.post('', 'action=parsePhone&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })

        $('[data-new]').click(function(e) {
            e.preventDefault()
            var url = $(this).data('new')
            $.post('', 'action=new&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })
    });
</script>
</body>
</html>