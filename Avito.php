<?php

spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});


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

if ($_POST['action'] == 'parseCard')
{
    $avito->curl->sleepMin = 3;
    $avito->curl->sleepMax = 8;
    $avito->parseCard($_POST['url'], $row);
    // описание
    echo '<h3><strong>Описание объявления: </strong></h3>';
    echo $row['text'];
    echo '<h3><strong>Параметры: </strong></h3>';
    // параметры
    foreach ($row['params'] as $key => $name)
    {
        echo '<strong>'.$key.'</strong>'.$name.PHP_EOL.'<br/>';
    }
    // статистика
    echo '<h3><strong>Статистика: </strong></h3>';
    echo '<strong>Всего просмотров: </strong>'.$row['views-total'].'<br/>';
    echo '<strong>Просмотров за сегодня: </strong>'.$row['views-today'];
    // фото
    echo '<h3><strong>Фото: </strong></h3>';
    // скрипт для спойлера фото

    ?>
    <div class="spoiler_links blue">Спойлер (кликните для открытия/закрытия)</div>
    <div class="spoiler_body">
       <?php
       foreach ($row['images'] as $key => $value)
       {
           echo '<img src="'.$value.'"width="300 px">';
       }
       ?>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('.spoiler_links').click(function(){
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

if ($_POST['action'] == 'parsePhone')
{

    $avito->curl->sleepMin = 3;
    $avito->curl->sleepMax = 8;

    // Подготавливаем входные параметры
    $url = $_POST['url'];
    preg_match('~_(\d+)$~i', $url, $a);
    $id = $a[1];
    $cardContent = $avito->curl->phoneLoad($url, 604800);
    // Определяем phoneUrl
    $avitoContact = new AvitoContact;
    $phoneUrl = $avitoContact->getPhoneUrl($cardContent, $id);
    //var_dump($phoneUrl);

    // Грузим картинку по phoneUrl
    $imgContent = $avito->curl->phoneLoad($phoneUrl, 604800);
    // Контент дошел...
    // Разбираем ее и сохраняем в файл
    preg_match('~{(.*?)}~is',$imgContent, $jj);

    $img = json_decode($jj[0]);

    $avitoContact->saveInFile($img->image64, 'phone.png');

    // Распознаем файл
    $result = $avitoContact->recognize('phone.png');


    //echo '<p>URL: '.$phoneUrl.'</p>';

    //echo '<p><img src="'.$img->image64.'" alt="" /></p>';

    //echo '<p><a href="#" onclick="jQuery(\'#debugOutput\').slideToggle(); return false;">Цветовая схема</a></p>';
    echo '<div id="debugOutput" style="display:none;">'.$avitoContact->debugOutput.'</div>';

    if ($result) {
        echo '<h2 class="text-success">Телефон: '.$result.'</h2>';
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

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">


    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
        body {
            background-repeat: no-repeat;
            background-position: 0 0;
            background-size: cover;
            background-image: url(images/avitus5.jpg);
            background-blend-mode: normal;
        }
        h1 {margin:20px 0 15px; font-size:24px;}
        .avito-form > div {margin-right:10px;}
        .form-inline .form-group {margin-bottom:10px;}
        /* лоадер на css */
        #loader {
            border: 5px solid #f3f3f3; /* Light grey */
            border-top: 5px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 52px;
            height: 52px;
            animation: spin 2s linear infinite;
            position:absolute;
            top:7px; left:10px;
            display:none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /*спойлер для фото*/
        .spoiler_body { display: none; font-style: italic; }
        .spoiler_links { cursor: pointer; font-weight: bold; text-decoration: underline; }
        .blue { color: #000099; }

    </style>
</head>
<body>

<div id="loader"></div>

<div class ="container-fluid">


    <h1>Заполните форму URL <span class="glyphicon glyphicon-hand-down" aria-hidden="true"></span></h1>


    <form class="form-inline avito-form" method="post">
        <div class="form-group input-group">
            <span class="input-group-addon"><a href="<?=$_POST['url']?>" target="_blank">URL</a></span>
            <input type="text" class="form-control" name ="url" value ="<?=$_POST['url']?>" style="width: 400px;">
            <button type="submit" class="btn btn-default">Выполнить</button>
        </div>
    </form>

<hr/>



<?php
    if ($_POST['url'])
    {
        $avito->curl->sleepMin = 4;
        $avito->curl->sleepMax = 9;
        $data = $avito->parseAll($_POST['url']);
        //кол-во объявлений
        $count = count($data);
    if (isset($count))
    {
        echo '<h4><strong>Всего объявлений: '.$count.'</strong></h4>';
    }
        //вывод новых объявлений
        //$news=
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
                    <td class="text-right"><?= substr($row['price'],0,-6) ?></td>
                    <td><?= $row['year'] ?></td>
                    <td><?= date($row['date']) ?></td>
                    <td>
                        <a href="#" data-url="<?= $row['url'] ?>" class="label label-info">Просмотр объявления</a>
                        <a href="#" data-phone="<?= $row['url'] ?>" class="label label-warning">Показать телефон</a>
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




} elseif ($_GET['action'] == 'contact') {
    $avitoContact = new AvitoContact;

    $imageScheme = $avitoContact->getImageScheme('phone.png', $_POST['columnFrom'], $_POST['columnTo']);
    ?>
    <h3>Разбор телефона</h3>

    <form method="post" class="form-inline">

        <div class="form-group">
            <label>Показать колонку с индекса</label>
            <input type="text" name="columnFrom" class="form-control" style="width:75px;" value="<?=$_POST['columnFrom']?>" />
            до
            <input type="text" name="columnTo" class="form-control" style="width:75px;" value="<?=$_POST['columnTo']?>" />
        </div>

        <input class="btn btn-info" type="submit" value="Показать">
    </form>

    <hr />

    <?php

    echo $avitoContact->debugOutput;

    if ($_POST['columnTo']) {
    	$textarea = $avitoContact->makeColumnData($imageScheme, $_POST['columnFrom'], $_POST['columnTo']);
        echo '<textarea style="width:100%; height:200px; white-space: nowrap; font-size: 12px;">'.$textarea.'</textarea>';
    }

    $phoneNumber = $avitoContact->recognizeByScheme($imageScheme);

    if ($avitoContact->error) {
        echo '<p class="alert alert-danger">'.$avitoContact->error.'</p>';
    }

    if ($phoneNumber) {
        $phoneNumber = 'Найдено значение - <b>'.$phoneNumber.'</b>';
    } else {
        $phoneNumber = 'Не найдено ни одного символа';
    }


    echo '<p class="badge" style="margin-top:10px; font-size:60px;">'.$phoneNumber.'</p><br /><br />';

} else {
    ?>
    <br />
        <div class="wrap">
        <div class="col-sm-12" >
            <div class="jumbotron" >
            <h1>Привет!</h1>
            <p>Перейти на Авито</p>
            <p><a class="btn btn-primary btn-lg" href="https://www.avito.ru/" role="button">Начать поиск объявлений</a></p>
            </div>
        </div>
    </div>
    <?php
    }

    ?>

</div>



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


<script type="text/javascript">

    $(document).ready(function(){

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
    });
</script>

</body>
</html>