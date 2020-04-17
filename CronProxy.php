<?php
spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});
$proxy = new Proxy;


($proxy -> parseAllProxy());
$a=file("proxy/AllProxies.txt");
($proxy->proxyChecker($a));