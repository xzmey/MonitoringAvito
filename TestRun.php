
<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;
$avito = new Avito;

//правильная ссылка!
$url ='https://www.avito.ru/izhevsk/avtomobili/honda/accord-ASgBAgICAkTgtg2ymCjitg30nSg?radius=300';

//url2   https://www.avito.ru/izhevsk/kvartiry/prodam/1-komnatnye-ASgBAQICAUSSA8YQAUDKCBSAWQ?district=166

/*
($proxy -> parseAllProxy());
$a=file("proxy/AllProxies.txt");
($proxy->proxyChecker($a));
*/

/*
$proxies=file("proxy/GoodProxies.txt");
$steps = count($proxies);
echo $steps;
*/

/*
$avito->curl->sleepMin = 3;
$avito->curl->sleepMax = 6;
$data = $avito->parseAll($url);

echo '<pre>'; print_r($data); echo '<pre>';
echo '<hr />';
*/
/*

$a=file_get_contents('cash/5057ae166afef3e10dcdad5416900a53');
preg_match('~<title>(.*?)</title>~is',$a, $b);
$c='Доступ с вашего IP-адреса временно ограничен &mdash; Авито';
var_dump($b[1]);
var_dump($c);
if ($b[1]==$c)
{
    echo 'Забанили';
}
else echo 'xz';
*/


//echo (strlen(file_get_contents('cash/4dd511879408a1d06f33fa4d6b184e19')));
