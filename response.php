<?php

require './WeChat.class.php';
define('APPID','wxc46d6f0b058b3f04');
define('APPSECRET','06ddde3f8edb345eb0ee71609b82c1d3');
define('TOKEN','yinfei_weixin');
//
define('APPKEY','07a14173c45e4d58d94a0781953bdd03');
$wechat = new WeChat(APPID,APPSECRET,TOKEN,APPKEY);
//$access_token = $wechat->getAccessToken();
//$qrcode = $wechat->getQRCode(124);
//第一次验证调用
//$wechat->firstValid();
$wechat->responseMsg();

