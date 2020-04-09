
<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;
$avito = new Avito;

$url='https://www.sslproxies.org/';
//правильная ссылка!
$url2 ='https://www.avito.ru/izhevsk/gruzoviki_i_spetstehnika/avtodoma-ASgBAgICAURUkk8?cd=1&radius=300';

/*
($proxy -> parseProxy($url));
$a=file("proxy/AllProxies.txt");
($proxy->proxyChecker($a));
*/

/*
$proxies=file("proxy/GoodProxies.txt");
$steps = count($proxies);
echo $steps;
*/


//$avito->curl->sleepMin = 3;
//$avito->curl->sleepMax = 6;
$data = $avito->parseAll($url2);

echo '<pre>'; print_r($data); echo '<pre>';
echo '<hr />';



