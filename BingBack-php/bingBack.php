<?php
header("Content-Type: text/plain");
set_time_limit(0);
error_reporting(0);
chdir(dirname(__FILE__));
date_default_timezone_set('America/Denver'); // Server TimeZone (https://bingwallpaper.com/)
echo "-={Author: Hadi Abedzadeh}=-\n-={Public date: 2017/09/20}=-\n\n";
$dir       = "img";
$listImg   = (glob("$dir/*"));
$countImg  = count($listImg);
$infImg    = pathinfo($listImg[$countImg - 1]);
$startDate = $infImg['filename'];
$increment = 1;

while (true) {
    $date = strtotime("+$increment day", strtotime($startDate));
    $ret  = array();
    $dom  = new domDocument;
    $url  = "https://bingwallpaper.com/" . date('Ymd', $date) . ".html";
    @$dom->loadHTML(mb_convert_encoding(file_get_contents($url), 'HTML-ENTITIES', 'UTF-8'));
    $dom->preserveWhiteSpace = true;
    $links                   = $dom->getElementsByTagName('img');
    foreach ($links as $tag)
        @$ret[urldecode($tag->getAttribute('src'))] = $tag->childNodes->item(0)->nodeValue;
    $ret = (array_keys($ret));
    flush();
    if (strlen($ret[1]) <= 1)
        break;
    file_put_contents("$dir/" . date('Ymd', $date) . ".jpg", file_get_contents(str_replace('//', 'https://', $ret[1])), LOCK_EX);
    print("$dir/" . date('Ymd', $date) . ".jpg\t" . date('Y/m/d H:i:s ') . microtime() . PHP_EOL);
    $increment++;
    
    if (date('Ymd', $date) >= date('Ymd'))
        break;
}
echo "\tOperation successfully\n\n\n";
