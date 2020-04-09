<?php



class Avito
{
    function __construct()
    {
        $this->curl = new Curl;
    }

    function parsePage($url)
    {

        $content = $this->curl->load($url, $cash = 3600);
        preg_match('~<div class="snippet-list js-catalog_serp".*?<div class="js-pages pagination-pagination-2j5na">~is', $content, $a);
        $innerContent = $a[0];

        $rows = preg_split($rx = '~<div class="snippet-horizontal~is', $innerContent);

// если реклама снизу другая ,то и регулярка другая
        if (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="avito-ads-container avito-ads-container_context_12">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        } // если нет рекламы ,тогда цепляюсь к випкам
        elseif (count($rows) == 1) {
            preg_match('~<div class="js-catalog_serp".*?<div class="serp-vips ">~is', $content, $a);
            $innerContent = $a[0];
            $rows = preg_split($rx = '~<div class="snippet-horizontal item item_table~is', $innerContent);
        } // если ниодна регулярка не подошла
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
            if (empty($row['date']))
            {
                preg_match('~data-tooltip="" flow="down">="(.*?)"~is', $rowContent, $a);
                $row['date'] = $a[1];
            }
            preg_match('~\d{4}~i', $row['name'], $a);
            $row['year'] = $a[0];
            preg_match('~ href="\s*([^"]+)"~i', $rowContent, $a);
            $row['url'] = 'https://www.avito.ru' . $a[1];
            preg_match('~data-marker="item-price"\n\s*>\n(.*?)<~is', $rowContent, $a);
            $row['price'] = preg_replace('~[^\d]~i', '', $a[0]);

            //exit;
            $data [] = $row;
        }
        return $data;
    }

    function parseAll($url)
        //$fromPage=1, $maxPage=false
    {
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

            $data =  $this->parsePage($urlCurrent);

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
    }

}




