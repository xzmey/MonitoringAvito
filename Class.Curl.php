<?php



class Curl {

    static $cashDir = 'cash';

    function __construct()
    {
        $this->sleepMin = 0;
        $this->sleepMax = 0;
    }

    function setCache($content, $cacheId)
    {
        if ($content == '') {
            return ;
        }
        $fileName = self::$cashDir.'/'.md5($cacheId);
        if (!file_exists(self::$cashDir)) {
            mkdir(self::$cashDir, 0777);
        }
        $f = fopen($fileName, 'w+');
        fwrite($f, $content);
        fclose($f);
    }

    function getCache($cacheId, $cashExpired=true, &$fileName='')
    {
        if (!$cashExpired) {
            return ;
        }
        $fileName = self::$cashDir.'/'.md5($cacheId);
        if (!file_exists($fileName)) {
            return false;
        }
        $time = time() - filemtime($fileName);
        if ($time > $cashExpired) {
            return false;
        }
        return file_get_contents($fileName);
    }
    /**
     * curlLoad для проксей
     */
    function proxyLoad($url, $cash=0)
    {
        $cacheId = $url;
        if ($content=  $this->getCache($cacheId, $cash))
        {
            return $content;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // таймаут ответа
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвращает веб-страницу
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        $content = curl_exec($ch);
        if (curl_errno($ch))
        {
            echo 'Curl_error:'.curl_error($ch);
        }

        curl_close($ch);

        $this->setCache($content, $cacheId);
        return $content;

    }

    /**
     * curlLoad для АВИТО
     */
    function load($url, $cash=0)
    {
        $proxies=file("proxy/GoodProxies.txt");
        $steps = count($proxies);
        $step = 0;
        $try = true;

        $this->fromCash = false;
        $cacheId = $url;
        if ($content = $this->getCache($cacheId, $cash)) {
            //бан
            if (!strpos($content, 'Location: https://www.avito.ru/blocked')) {
                $this->fromCash = true;
                return $content;
            }
        }
        if (strpos($url, 'http') !== 0) {
            echo 'Неправильный урл запроса "'.$url.'"';
            return false;
        }

        /**
         * Инициализировать поток curl с указанными параметрами
         */
        while($try) {
            $proxy = isset($proxies[$step]) ? $proxies[$step] : null;
            $this->url = $url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);

            $content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код


            curl_close($ch);

            $step++;
            $try = (($step < $steps) && ($http_code != 200));

            if ($this->sleepMin > 0) {
                sleep(rand($this->sleepMin, $this->sleepMax));
            }

            $file = fopen('log.txt', 'a+');
            fwrite($file, "\n" . date('Y-m-d H:i:s') . ' ' . $url);
            fclose($file);

            if (strlen($content) > 1000) {
                $this->setCache($content, $cacheId);
            }
        }
        return $content;
    }

    public function debug($content)
    {
        echo '<p><a href="'.$this->url.'" target="_blank">'.$this->url.'</a></p>';
        if ($this->header) {
            echo '<pre class="text-warning">'.trim($this->header).'</pre>';
        } else {
            echo '<p class="text-danger">Пустая шапка</p>';
        }
        if (empty($content)) {
            echo '<p class="text-danger">Пустой контент</p>';
        } else {
            echo '<p class="text-success">Контент - '.strlen($content).' байт</p>';
        }
        if ($content) {
            $debug = self::$cashDir.'/debug.html';
            fwrite($a = fopen($debug, 'w+'), $content); fclose($a);
            echo '<iframe src="'.$debug.'" style="width:300px; height:200px; border:1px solid #ccc"></iframe>';
        }
    }
}