<?php
require_once('twitteroauth/twitteroauth/twitteroauth.php');

$context = stream_context_create(
    array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true, // エラーページも内容取得
            'timeout' => 10,
        )
    )
);

require('config.php');
$config = Config::getConfig();

/********************************************************/

// Chatwork KDDI版をチェック
$res = file_get_contents('https://kcw.kddi.ne.jp', false, $context);
// 200で'<h1>ログイン</h1>'があれば正常としておく

file_put_contents(dirname(__FILE__) . '/tmp/res_header_page', print_r($http_response_header, true));
file_put_contents(dirname(__FILE__) . '/tmp/content_page', $res);

preg_match_all('/^HTTP\/[^ ]+ ([0-9]{3})/m', implode("\n" ,$http_response_header), $matches);
$status_code = array_pop($matches[1]); // リダイレクトとかされる場合もあるので最後のstatusを取る
if (!$res || $status_code != 200) {
    tweet('チャットワーク(KDDI版)は死んだ', 'page');
} else if (preg_match('|<h2 class="new-theme_section__title">Log in with email address</h2>|', $res) === false) {
    tweet('チャットワーク(KDDI版)は死んだかもしれない', 'page');
} else {
    tweet('チャットワーク(KDDI版)は蘇った！！！', 'page');
}

/********************************************************/

// Chatwork本家をチェック
$res = file_get_contents('https://www.chatwork.com', false, $context);
// 200で'<h1>ログイン</h1>'があれば正常としておく

file_put_contents(dirname(__FILE__) . '/tmp/res_header_www', print_r($http_response_header, true));
file_put_contents(dirname(__FILE__) . '/tmp/content_www', $res);

preg_match_all('/^HTTP\/[^ ]+ ([0-9]{3})/m', implode("\n" ,$http_response_header), $matches);
$status_code = array_pop($matches[1]); // リダイレクトとかされる場合もあるので最後のstatusを取る
if (!$res || $status_code != 200) {
    tweet('チャットワークは死んだ', 'www');
} else if (preg_match('|<h2 class="new-theme_section__title">Log in with email address</h2>|', $res) === false) {
    tweet('チャットワークは死んだかもしれない', 'www');
} else {
    tweet('チャットワークは蘇った！！！', 'www');
}

/********************************************************/

$res = file_get_contents('https://api.chatwork.com/', false, $context);
// 401で'{"errors":["Invalid API token"]}'なら正常としておく。

file_put_contents(dirname(__FILE__) . '/tmp/res_header_api', print_r($http_response_header, true));
file_put_contents(dirname(__FILE__) . '/tmp/content_api', $res);

preg_match_all('/^HTTP\/[^ ]+ ([0-9]{3})/m', implode("\n" ,$http_response_header), $matches);
$status_code = array_pop($matches[1]); // リダイレクトとかされる場合もあるので最後のstatusを取る

if (!$res || $status_code != 401) {
    tweet('チャットワークAPIは死んだ', 'api');
} else if ($res != '{"errors":["Invalid API token"]}') {
    tweet('チャットワークAPIは死んだかもしれない', 'api');
} else {
    tweet('チャットワークAPIは蘇った！！！', 'api');
}

/********************************************************/

// つぶやく
function tweet($message, $type = '') {
    $message .= ' #chatwork';
    $status = @file_get_contents(dirname(__FILE__) . '/tmp/status_' . $type);

    if ($status && $message == $status) {
        return;
    }

    file_put_contents(dirname(__FILE__) . '/log/tweet_' . $type . '_log', date('Y-m-d H:i:s', time()) . ' ' . $message . "\n", FILE_APPEND);
    file_put_contents(dirname(__FILE__) . '/tmp/status_' . $type, $message);

    $config = Config::getConfig();
    $connection = new TwitterOAuth(
        $config['consumer_key'],
        $config['consumer_secret'],
        $config['access_token'],
        $config['access_token_secret']
    );

//    return; // for debug
    $req = $connection->OAuthRequest('https://api.twitter.com/1.1/statuses/update.json', 'POST', array('status' => $message ));

    // ついでに自分にメール
    mb_internal_encoding('UTF-8');
    mb_send_mail('ishikawam@nifty.com', $message, $message . "\n\nhttps://twitter.com/chatwork_isdead\n");
}
