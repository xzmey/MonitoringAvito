<?php



class Proxy
{

    function __construct()
    {
        $this->curl = new Curl;
    }

    //https://www.sslproxies.org/
    function parseProxySSL($url)
    {
        $fileName = 'proxy/AllProxies.txt';
        $f = fopen($fileName, 'w+');
        // каждые 20 мин обновляем кэш
        $content = $this->curl->proxyLoad($url, $cash = 1200);
        //регулярка
        preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*?<td>(\d{2,5})</is', $content, $a);
        //a[1]-ip,a[2]-port,в a[2]добавить в начало : чтобы было возможно отличить порт
        //чтобы перед портом было :
        foreach ($a[2] as $key => $value) {
            $a[2][$key] = ':' . $value;
            $proxy_list[] = $a[1][$key] . $a[2][$key];
            file_put_contents($fileName, $a[1][$key] . $a[2][$key] . PHP_EOL, FILE_APPEND);
        }
        fclose($f);
        return $proxy_list;
    }
     //http://givemeproxy.com/
    function parseProxyGMP($url)
    {
        $fileName = 'proxy/AllProxies.txt';
        $f = fopen($fileName, 'a+');
        // каждые 20 мин обновляем кэш
        $content = $this->curl->proxyLoad($url, $cash = 1200);

        //регулярка
        preg_match_all('~<td class="column-1">.*?</td><td class="column-2">~is', $content, $a);
        preg_match_all('~</td><td class="column-2">.*?</td><td class="column-3">~is', $content, $b);
        //var_dump($a);
        //var_dump($b);
        //a[1]-ip,a[2]-port,в a[2]добавить в начало : чтобы было возможно отличить порт
        //чтобы перед портом было :
        foreach ($b[0] as $key => $value) {
            $b[0][$key] = ':' . $value;
            $proxy_list[] = $a[0][$key] . $b[0][$key];
            file_put_contents($fileName, strip_tags($proxy_list[$key]) . PHP_EOL, FILE_APPEND);
        }
        fclose($f);
        return $proxy_list;
    }


    function parseAllProxy()
    {
        $url1 = 'https://www.sslproxies.org/';
        $url2 = 'http://givemeproxy.com/';

        $proxylist = array_merge($this -> parseProxySSL($url1),$this -> parseProxyGMP($url2));

        return $proxylist;
    }

    function proxyChecker($proxies)
    {
        //если редиректит с http на https, т е ответ сервера 301, то прокси валидный!
        $mc = curl_multi_init();
        $fileName = 'proxy/GoodProxies.txt';
        $f = fopen($fileName, 'w+');
        for ($thread_no = 0; $thread_no < count($proxies); $thread_no++) {
            $c [$thread_no] = curl_init();
            curl_setopt($c [$thread_no], CURLOPT_URL, "http://google.com");
            curl_setopt($c [$thread_no], CURLOPT_HEADER, 0);
            curl_setopt($c [$thread_no], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c [$thread_no], CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($c [$thread_no], CURLOPT_TIMEOUT, 10);
            curl_setopt($c [$thread_no], CURLOPT_PROXY, trim($proxies [$thread_no]));
            curl_setopt($c [$thread_no], CURLOPT_PROXYTYPE, 0);
            curl_multi_add_handle($mc, $c [$thread_no]);
        }

        do {
            while (($execrun = curl_multi_exec($mc, $running)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($execrun != CURLM_OK) break;
            while ($done = curl_multi_info_read($mc)) {
                $info = curl_getinfo($done ['handle']);
                if ($info ['http_code'] == 301) {
                    file_put_contents($fileName, trim($proxies [array_search($done['handle'], $c)]) . PHP_EOL, FILE_APPEND);
                    $data[]=trim($proxies [array_search($done['handle'], $c)]);
                }
                curl_multi_remove_handle($mc, $done ['handle']);
            }
        } while ($running);
        fclose($f);
        curl_multi_close($mc);
        return $data;
    }
}