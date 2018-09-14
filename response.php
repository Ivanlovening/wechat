<?php

require './WeChat.class.php';
define('APPID','');
define('APPSECRET','');
define('TOKEN','');
//
define('APPKEY','');
$wechat = new WeChat(APPID,APPSECRET,TOKEN,APPKEY);
//$access_token = $wechat->getAccessToken();
//$qrcode = $wechat->getQRCode(124);
//第一次验证调用
//$wechat->firstValid();
$wechat->responseMsg();

