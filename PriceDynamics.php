<?php
include 'Class.Avito.php';
include 'Class.Curl.php';
include 'pChart/pData.class.php';
include 'pChart/pCache.class.php';
include 'pChart/pChart.class.php';
require 'db.php';

session_start();
if(!$_SESSION['login'])
{
    header('location:/auth.php'); //переадресация на страницу входа
    exit();
}

$myData = new pData();
$rows=[];
$link = mysqli_connect ("localhost","mysql","mysql","avito");
$selectId =  mysqli_query($link, "SELECT `user_id` FROM `vk_users` WHERE `vk_id`='{$_SESSION['login']['id']}'");
$user_id = mysqli_fetch_array($selectId);
//$user_id['user_id'] - user_id пользователя в сессии
$sql = mysqli_query($link, "SELECT `parse_date`,`price` FROM `avg_price` WHERE `user_id`='{$user_id['user_id']}'");

while($row = mysqli_fetch_array($sql))
{   /*
    var_dump($row['price']);
    var_dump($row['parse_date']);
   */
    $myData->AddPoint($row['price'],"price");
    $myData->AddPoint($row['parse_date'],"date");
}

// x — это ось абсцисс, а y —  ось ординат

//устанавливаем точки с датами
//на ось абсцисс
$myData->SetAbsciseLabelSerie("date");
//помечаем данные как предназначеные для
//отображения
$myData->AddSerie("price");
//создаем график шириной в 1000 и высотой в 500 px
$graph = new pChart(1000,500);
//устанавливаем шрифт и размер шрифта
$graph->setFontProperties("Fonts/tahoma.ttf",8);
//устанавливаем имена
$myData->SetSerieName(
    mb_convert_encoding("Сумма",'utf-8','utf-8'),
    "price");
//координаты левой верхней вершины и правой нижней
//вершины графика
$graph->setGraphArea(85,30,950,400);
//рисуем заполненный четырехугольник
$graph->drawFilledRoundedRectangle(7,7,993,493,5,240,
    240,240);
//теперь незаполненный для эффекта тени
$graph->drawRoundedRectangle(5,5,995,495,5,230,
    230,230);
//устанавливаем данные для графиков
$graph->drawScale($myData->GetData(),
    $myData->GetDataDescription(),
    SCALE_NORMAL,150,150,150,true,0,2);
//рисуем сетку для графика
$graph->drawGrid(4,TRUE,230,230,230,50);
//прорисовываем линейные графики
$graph->drawLineGraph($myData->GetData(),
    $myData->GetDataDescription());
// рисуем точки на графике
$graph->drawPlotGraph($myData->GetData(),
    $myData->GetDataDescription(),4,0,255,255,255);
//ложим легенду
$graph->drawLegend(90,35,$myData->GetDataDescription(),150,150,150);
//Пишем заголовок
$graph->setFontProperties("Fonts/tahoma.ttf",13);
$graph->drawTitle(480,22,
    mb_convert_encoding("Динамика цены",
        'utf-8','utf-8'),
    50,50,50,-1,-1,false);
//выводим в браузер
//$graph->Stroke();
$graph->Render("graph.png");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style type="text/css">
        body
        {
            background-repeat: no-repeat;
            background-position: 0 0;
            background-size: cover;
            background-image: url(images/avitus5.jpg);
            background-blend-mode: normal;
        }
        .fig
        {
            text-align: center; /* Выравнивание по центру */
            margin-top: 10%; /* Отступ сверху */
        }

        </style>
</head>
<body>
<p class="fig"><img src="graph.png"></p>
</body>
</html>

