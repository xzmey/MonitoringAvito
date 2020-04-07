
<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;
$avito = new Avito;

$url='https://www.sslproxies.org/';
//правильная ссылка!
$url2 ='https://www.avito.ru/izhevsk/avtomobili?radius=200&q=audi';

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


$avito->curl->sleepMin = 2;
$avito->curl->sleepMax = 5;
$data = $avito->parseAll($url2);

echo '<pre>'; print_r($data); echo '<pre>';
echo '<hr />';



