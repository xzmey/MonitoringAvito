<?php
require 'db.php';
include "vk_api.php";
const VK_KEY = "4dec5adac64862cecd0ebf2cef7e2aa01bb1e86b42abf2df5731c299d7d1204b80173798e8458dc7243b1";  // Токен сообщества
const ACCESS_KEY = "6306747e";  // Тот самый ключ из сообщества
const VERSION = "5.0"; // Версия API VK
error_reporting(0);
$vk = new vk_api(VK_KEY, VERSION);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Проверка оплаты</title>
    <style>

    </style>
</head>

<body>
<form action="AdminPage.php" method="POST" >
<br>
    <input type="text" name="id" value="" placeholder="Введите id пользователя">
    <p>
        <button type="submit" name="Status">Оплатил</button>
    </p>
</form>
</body>
</html>

<?php

$data = $_POST; //Записали данные из поста. Можно вместо $data использольвать $_POST['Status'] и т.п. но через $data лучше выглядит))

if(isset($data['Status']))
{ // По названию кнопки определяем пришло ли наши данные из формы
    $id = $data['id'];
    if(!empty($data['id']))
    {
        if (R::findOne('users','user_id=?',array($id)))
        {
            $link = mysqli_connect("localhost", "mysql", "mysql", "avito");
            $sql = mysqli_query($link, "UPDATE `users` SET  `status` = 'оплатил' WHERE `user_id` = '$id'") or die;
            echo'<h2 style="color: green;">Статус оплаты изменен!</h2>';

            $vk->sendMessage($id, "
        Статус оплаты подтвержден✅ 
        💬Вы можете мониторить до 2 url.
        На странице поиска объявлений должны соблюдаться критерии парсинга, то есть обязательно должны 
        быть заполнены поля: “Категория”, “Название”, “Локация”, “Радиус”. Далее нужно найти объявления на сайте и скопировать url. Потом напишите команду URL и после пробела вставьте его.
            ");
        }
        else
        {
            echo '<h2 style="color: red;">Не правильный id пользователя</h2>';
        }
    }

}

?>
