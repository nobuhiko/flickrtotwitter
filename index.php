<?php
define('CHECK_RSS_COUNT', 1); // 投稿するRSSのチェックする数
require("twitteroauth.php"); // OAuthライブラリ

// Consumer keyの値
$consumer_key           = "";
// Consumer secretの値
$consumer_secret        = "";
// Access Tokenの値
$access_token           = "";
// Access Token Secretの値
$access_token_secret    = "";

//最後につぶやいたid
$file                   = 'lastupdate.txt';

//flickrのid
$f_id   = "";

// OAuthオブジェクト生成
$to     = new TwitterOAuth($consumer_key,$consumer_secret,$access_token,$access_token_secret);


$url    = 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$f_id.'&lang=en-us&format=rss_200'; // 投稿するRSS

$rss    = file_get_contents($url);
$oXML   = simplexml_load_string($rss);
$oItems = $oXML->channel->item;

$i = 0;
foreach ($oItems as $Item) {
    if ($i >= CHECK_RSS_COUNT) {
        break 1;
    }
    if ( !PostCheck($Item->link, $file) ){
        $sLink  = Shorten($Item->link);
        $text   = $Item->title . " " . $sLink; // ツイート文言
        $res    = $to->OAuthRequest("http://api.twitter.com/1/statuses/update.xml","POST",array("status" => $text));
        // 成功したらfileに書き込む
        file_put_contents($file, (string) $Item->link);
    }
    $i++;
}

// twitterAPIの返りオブジェクト,RSSオブジェクトのタイトル
function PostCheck($sHead, $file){
    $sHead = (string) $sHead;
    // fileから最後に投稿したlinkとマッチするかチェック
    if(mb_strpos(file_get_contents($file), $sHead) !== false){
        return 1; // 投稿済み
    }

    return 0; // 未投稿
}

// http://goo.gl/Zt4vK
function base58_encode($num) {
    $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $base_count = strlen($alphabet);
    $encoded = '';

    while ($num >= $base_count) {
        $div = $num / $base_count;
        $mod = ($num - ($base_count * intval($div)));
        $encoded = $alphabet[$mod] . $encoded;
        $num = intval($div);
    }

    if ($num) {
        $encoded = $alphabet[$num] . $encoded;
    }

    return $encoded;
}

function base58_decode($num) {
    $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $len = strlen($num);
    $decoded = 0;
    $multi = 1;

    for ($i = $len - 1; $i >= 0; $i--) {
        $decoded += $multi * strpos($alphabet, $num[$i]);
        $multi = $multi * strlen($alphabet);
    }

    return $decoded;
}

// flickr短縮urlの作成
function Shorten($link) {
    preg_match ( "#/(\d+)/#", $link, $matches );
    return 'http://flic.kr/p/'.base58_encode($matches[1]);
}
