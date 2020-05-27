<?php

$client_id = 7442721; // ID приложения
$client_secret = 'E4qC2Wh6iGGkZvQkIDMe'; // Защищённый ключ
// меняется, тк нету хоста
$redirect_uri = 'http://ae839705.ngrok.io/auth.php'; // Адрес сайта

$url = 'http://oauth.vk.com/authorize'; // Ссылка для авторизации на стороне ВК

$params = [ 'client_id' => $client_id, 'redirect_uri'  => $redirect_uri, 'response_type' => 'code']; // Массив данных, который нужно передать для ВК содержит ИД приложения код, ссылку для редиректа и запрос code для дальнейшей авторизации токеном

session_start();

/**
 * Получаем и обрабатываем ответ от вк
 */


if (isset($_GET['code'])) {
    $result = true;
    $params = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $_GET['code'],
        'redirect_uri' => $redirect_uri
    ];

    $token = json_decode(file_get_contents('https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params))), true);

    if (isset($token['access_token'])) {
        $params = [
            'uids' => $token['user_id'],
            'fields' => 'uid,first_name,last_name,screen_name,sex,bdate,photo_big',
            'access_token' => $token['access_token'],
            'v' => '5.101'];

        $userInfo = json_decode(file_get_contents('https://api.vk.com/method/users.get' . '?' . urldecode(http_build_query($params))), true);
        if (isset($userInfo['response'][0]['id'])) {
            $userInfo = $userInfo['response'][0];
            $result = true;
        }
    }

    if ($result)
    {
        $_SESSION['login']=
            [
                'id' => $userInfo['id'],
                'name' => $userInfo['first_name'],
                'ph' => '<img src="' . $userInfo['photo_big']
            ];
    }
}



if(isset($_SESSION['login']))
{
    header('location:/avito.php'); //переадресация на страницу входа
    exit();
}
else
{
//echo $link = '<p><a href="' . $url . '?' . urldecode(http_build_query($params)) . '">Аутентификация через ВКонтакте</a></p>';
//echo $link ='<p><a class="btn btn-primary btn-lg" href="' . $url . '?' . urldecode(http_build_query($params)) . '" role="button">Аутентификация через ВКонтакте</a></p>';


// сделаю условие, пока в сессии, по id будет вывод контента

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <style type="text/css">
        body
        {
            background-repeat: no-repeat;
            background-position: 0 0;
            background-size: cover;
            background-image: url(images/avitus5.jpg);
            background-blend-mode: normal;
        }



        .button
        {
            margin-bottom:550px;
            text-decoration: none;
            font: 4.5em 'Trebuchet MS', Arial, Helvetica; /*Change the em value to scale the button*/
            display: inline-block;
            text-align: center;
            color: #fff;

            border: 1px solid #9c9c9c; /* Fallback style */
            border: 1px solid rgba(0, 0, 0, 0.3);

            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.4);

            box-shadow: 0 0 .05em rgba(0, 0, 0, 0.4);
            -moz-box-shadow: 0 0 .05em rgba(0, 0, 0, 0.4);
            -webkit-box-shadow: 0 0 .05em rgba(0, 0, 0, 0.4);

        }

        .button, .button span {
            -moz-border-radius: .3em;
            border-radius: .2em;
        }

        .button span {
            border-top: 1px solid #fff; /* Fallback style */
            border-top: 1px solid rgba(255, 255, 255, 0.5);
            display: block;
            padding: 0.5em 2.5em;
            position: fixed; top: 50%; left: 50%;

            /* The background pattern */

            background-image: -webkit-gradient(linear, 0 0, 100% 100%, color-stop(.25, rgba(0, 0, 0, 0.05)), color-stop(.25, transparent), to(transparent)),
            -webkit-gradient(linear, 0 100%, 100% 0, color-stop(.25, rgba(0, 0, 0, 0.05)), color-stop(.25, transparent), to(transparent)),
            -webkit-gradient(linear, 0 0, 100% 100%, color-stop(.75, transparent), color-stop(.75, rgba(0, 0, 0, 0.05))),
            -webkit-gradient(linear, 0 100%, 100% 0, color-stop(.75, transparent), color-stop(.75, rgba(0, 0, 0, 0.05)));
            background-image: -moz-linear-gradient(45deg, rgba(0, 0, 0, 0.05) 25%, transparent 25%, transparent),
            -moz-linear-gradient(-45deg, rgba(0, 0, 0, 0.05) 25%, transparent 25%, transparent),
            -moz-linear-gradient(45deg, transparent 75%, rgba(0, 0, 0, 0.05) 75%),
            -moz-linear-gradient(-45deg, transparent 75%, rgba(0, 0, 0, 0.05) 75%);

            /* Pattern settings */

            -moz-background-size: 3px 3px;
            -webkit-background-size: 3px 3px;
        }

        .button:hover {
            box-shadow: 0 0 .1em rgba(0, 0, 0, 0.4);
            -moz-box-shadow: 0 0 .1em rgba(0, 0, 0, 0.4);
            -webkit-box-shadow: 0 0 .1em rgba(0, 0, 0, 0.4);
        }

        .button:active {
            /* When pressed, move it down 1px */
            position: relative;
            top: 1px;
        }

        .button-blue {
            background: #4477a1;
            background: -webkit-gradient(linear, left top, left bottom, from(#81a8cb), to(#4477a1));
            background: -moz-linear-gradient(-90deg, #81a8cb, #4477a1);
            filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr='#81a8cb', endColorstr='#4477a1');
        }

        .button-blue:hover {
            background: #81a8cb;
            background: -webkit-gradient(linear, left top, left bottom, from(#4477a1), to(#81a8cb));
            background: -moz-linear-gradient(-90deg, #4477a1, #81a8cb);
            filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr='#4477a1', endColorstr='#81a8cb');
        }

        .button-blue:active {
            background: #4477a1;
        }
    </style>
</head>
<body>
<?php

echo $link ='<p><a class="button button-blue" href="' . $url . '?' . urldecode(http_build_query($params)) . '" role="button">Войти через vk</a></p>';
}
?>
</body>
</html>