<?php
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
