<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
/*
$proxy = new Proxy;


($proxy -> parseAllProxy());
$a=file("proxy/AllProxies.txt");
($proxy->proxyChecker($a));
*/

//var_dump(strlen(file_get_contents('cash/1444ff9bca8260145ccabcf9c0d92895')));