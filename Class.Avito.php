<?php


class Avito
{


    function __construct()
    {
        $this->curl = new Curl;
    }


    function parsePage($url)
    {
        // кэш месяц для теста
        $content = $this->curl->load($url, $cash = 7200);

        preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="js-pages pagination-pagination-2j5na">~is', $content, $a);
        $innerContent = $a[0];
        if ($innerContent == null)
        {
            preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="index-center-2ZEUx">~is', $content, $a);
            $innerContent = $a[0];
        }


        $rows = preg_split($rx = '~<div class="snippet-horizontal~is', $innerContent);

        // если реклама снизу другая ,то и регулярка другая
        if (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="avito-ads-container avito-ads-container_context_12">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        }
        // если нет рекламы ,тогда цепляюсь к випкам
        elseif (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="serp-vips ">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        }
        // если ниодна регулярка не подошла
        elseif (count($rows) == 1) {
            throw new Exception('Ошибка регулярки "' . htmlspecialchars($rx) . '"');
        }

        array_shift($rows);
        $data = [];

        foreach ($rows as $key => $rowContent) {
            //можно посмотреть весь контент товара/товаров
            //echo '<pre>'.htmlspecialchars($rowContent).'<pre>';
            $row = [];

            preg_match('~target="_blank"\s*title="(.*?)"~is', $rowContent, $a);
            $row['name'] = $a[1];
            preg_match('~data-tooltip="(.*?)"~is', $rowContent, $a);
            $row['date'] = $a[1];
            preg_match('~\d{4}~i', $row['name'], $a);
            $row['year'] = $a[0];
            preg_match('~ href="\s*([^"]+)"~i', $rowContent, $a);
            $row['url'] = 'https://www.avito.ru' . $a[1];
            preg_match('~\n\s*>\n(.*?)<~is', $rowContent, $a);
            $price= $a[1];
            $row['price']=preg_replace("/[^,.0-9]/", '', $price);

            Log::get()->log($row['name']);

            if ($this->loadCard) {
                $this->parseCard($row['url'], $row);
            }

            //exit;
            $data [] = $row;
        }
        return $data;
    }

    function parseAll($url)
        //$fromPage=1, $maxPage=false
    {
        $content = $this->curl->load($url, $cash = 7200);
        preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="js-pages pagination-pagination-2j5na">~is', $content, $a);
        $innerContent = $a[0];

        //у url не больше 5 страниц, так как возможен бан(и какой смысл мониторить если и так много вариантов)
        $pagination = preg_split($pg = '~<span data-marker="page~is',$content);

        if (count($pagination) < 6)
        {
            // если есть пагинация на странице
            if (isset($innerContent)) {
                $dataAll = [];
                $page = 1;
                $maxPage = false;
                while (true) {

                    if ($page == 1) {
                        $urlCurrent = $url;
                    } else {
                        if (strpos($url, '?')) {
                            $urlCurrent = str_replace('?', '?p=' . $page . '&', $url);
                        } else {
                            $urlCurrent = $url . '?=' . $page;
                        }
                    }

                    //echo '<br />'.$urlCurrent;

                    $data = $this->parsePage($urlCurrent);

                    //var_dump(count($data));

                    if (!count($data)) {
                        break;
                    }
                    $dataAll = array_merge($dataAll, $data);

                    if ($maxPage && $page == $maxPage) {
                        break;
                    }
                    $page++;
                }
                return $dataAll;
            } // если нет пагинации на странице
            else {
                $urlCurrent = $url;
                $data = $this->parsePage($urlCurrent);
            }
            return $data;
        }
    }


    function parsePageForUser($url)
    {
        // поменял с 3600 чтобы тестить
        $content = $this->curl->loadPageForUser($url);

        preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="js-pages pagination-pagination-2j5na">~is', $content, $a);
        $innerContent = $a[0];
        if ($innerContent == null)
        {
            preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="index-center-2ZEUx">~is', $content, $a);
            $innerContent = $a[0];
        }


        $rows = preg_split($rx = '~<div class="snippet-horizontal~is', $innerContent);

        // если реклама снизу другая ,то и регулярка другая
        if (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="avito-ads-container avito-ads-container_context_12">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        }
        // если нет рекламы ,тогда цепляюсь к випкам
        elseif (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="serp-vips ">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        }
        // если ниодна регулярка не подошла
        elseif (count($rows) == 1) {
            throw new Exception('Ошибка регулярки "' . htmlspecialchars($rx) . '"');
        }

        array_shift($rows);
        $data = [];

        foreach ($rows as $key => $rowContent) {
            //можно посмотреть весь контент товара/товаров
            //echo '<pre>'.htmlspecialchars($rowContent).'<pre>';
            $row = [];

            preg_match('~target="_blank"\s*title="(.*?)"~is', $rowContent, $a);
            $row['name'] = $a[1];
            preg_match('~data-tooltip="(.*?)"~is', $rowContent, $a);
            $row['date'] = $a[1];
            preg_match('~\d{4}~i', $row['name'], $a);
            $row['year'] = $a[0];
            preg_match('~ href="\s*([^"]+)"~i', $rowContent, $a);
            $row['url'] = 'https://www.avito.ru' . $a[1];
            preg_match('~\n\s*>\n(.*?)<~is', $rowContent, $a);
            $price= $a[1];
            $row['price']=preg_replace("/[^,.0-9]/", '', $price);
            Log::get()->log($row['name']);

            if ($this->loadCard) {
                $this->parseCard($row['url'], $row);
            }

            //exit;
            $data [] = $row;
        }
        return $data;
    }

    function parseAllForUser($url)
        //$fromPage=1, $maxPage=false
    {
        $content = $this->curl->loadPageForUser($url);
        preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="js-pages pagination-pagination-2j5na">~is', $content, $a);
        $innerContent = $a[0];

        //у url не больше 5 страниц, так как возможен бан(и какой смысл мониторить если и так много вариантов)
        $pagination = preg_split($pg = '~<span data-marker="page~is',$content);
        if (count($pagination) < 6)
        {
            // если есть пагинация на странице
            if (isset($innerContent)) {
                $dataAll = [];
                $page = 1;
                $maxPage = false;
                while (true) {

                    if ($page == 1) {
                        $urlCurrent = $url;
                    } else {
                        if (strpos($url, '?')) {
                            $urlCurrent = str_replace('?', '?p=' . $page . '&', $url);
                        } else {
                            $urlCurrent = $url . '?=' . $page;
                        }
                    }

                    //echo '<br />'.$urlCurrent;

                    $data = $this->parsePageForUser($urlCurrent);

                    //var_dump(count($data));

                    if (!count($data)) {
                        break;
                    }
                    $dataAll = array_merge($dataAll, $data);

                    if ($maxPage && $page == $maxPage) {
                        break;
                    }
                    $page++;
                }
                return $dataAll;
            } // если нет пагинации на странице
            else {
                $urlCurrent = $url;
                $data = $this->parsePageForUser($urlCurrent);
            }
            return $data;
        }
    }


    function parseCard($url, &$row)
    {
        // тут всегда кэш месяц, тк не изменят описание
        $cardContent = $this->curl->load($url, 2419200);
        Log::get()->log(' card['.strlen($cardContent).']', 0);

        //var_dump(strlen($cardContent));

        // Извлекаем статистику
        $row['views-total'] = $row['views-today'] = 0;
        if (preg_match('~<i class="title-info-icon-views"></i>(.*?)</div>~i', $cardContent, $a))
        {
            $statValues = $a[0];

            $row['views-total'] = ($statValues);

        }


        preg_match('~<div class="item-description-text" itemprop="description">(.*?)</div>~is', $cardContent, $a);
        $row['text'] = $a[1];

        preg_match_all('~data-url="(//\d+.img.avito.st/1280[^"]+jpg)"~i', $cardContent, $a);
        $row['images'] = $a[1];

        preg_match_all('~<span class="item-params-label">(.*?)</span>(.*?)</li>~is', $cardContent, $a);
        //параметры

        $row['params'] = [];
        foreach ($a[1] as $k => $name) {
            $row['params'][$name] = trim($a[2][$k]);
        }
    }
}




