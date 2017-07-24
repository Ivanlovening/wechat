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
//上传文件路径
$file = './b.jpg';
$result = $wechat->uplodeTmp($file,'image');
var_dump($result);
