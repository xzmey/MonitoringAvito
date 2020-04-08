<?php

spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;
$avito = new Avito;

$avito->curl->sleepMin = 2;
$avito->curl->sleepMax = 5;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Парсер Авито</title>

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
         </style>
  </head>
  <body>

  <div id="loader"></div>

  <div class ="container-fluid">

      <h1>Парсер Авито</h1>

       <form class="form-inline" method="post">
           <div class="form-group">
               <label>URL</label>
               <input type="text" class="form-control" name ="url" value ="<?=$_POST['url']?>" style="width: 400px;">
           </div>
           &nbsp;
           <button type="submit" class="btn btn-default">Выполнить</button>
       </form>

      <form method="POST">
         <input type="submit" name="Proxy" value="Парсить список прокси">
      </form>
  </div>
<hr/>



  <?php
  if ($_POST['url'])
  {
    $data = $avito->parseAll($_POST['url']);
    echo '<pre>'; print_r($data); echo '<pre>';
    echo '<hr />';
  }
  if ( isset($_POST['Proxy']) )
  {
    ($proxy -> parseProxy('https://www.sslproxies.org/'));
    $a=file("proxy/AllProxies.txt");
    ($proxy->proxyChecker($a));
  }


?>

  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </body>
</html>