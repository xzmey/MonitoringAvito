
<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;
$avito = new Avito;
$url='https://www.sslproxies.org/';
$url2 ='https://www.avito.ru/izhevsk/avtomobili/audi-ASgBAgICAUTgtg3elyg?radius=200';

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


$data = $avito->parseAll($url2);
echo '<pre>'; print_r($data); echo '<pre>';
echo '<hr />';
