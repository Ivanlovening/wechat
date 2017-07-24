<?php
//生成二维码
require './WeChat.class.php';
//appId
define('APPID','');
//appsecret
define('APPSECRET','');
//token
define('TOKEN','');
//appkey图灵机器人官网获得
define('APPKEY',' ');
$wechat = new WeChat(APPID,APPSECRET,TOKEN,APPKEY);
// $access_token = $wechat->getAccessToken();
$result = $wechat->getQRCodeTicket('124');
