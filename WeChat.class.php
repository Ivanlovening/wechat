<?php
//微信操作
class WeChat {
	protected $_appid;
	protected $_appsecret;
	protected $_token;//公众平台请求开发者时需要标记
	protected $_appkey;//图灵机器人appkey
	//标识qrcodeticket的类型，是永久还是临时
	const QRCODE_TYPE_TEMP = 1;
	const QRCODE_TYPE_LIMIT = 2;
	const QRCODE_TYPE_LIMIT_STR = 3;
	//存储图片media_id的数组
	protected $_img_list = array(
				'填写图片的media_id','填写图片的media_id');
	//初始化
	public function __construct($id,$secret,$_token,$_appkey){
		$this->_appid = $id;
		$this->_appsecret = $secret;
		$this->_token = $_token;
		$this->_appkey = $_appkey;
	}
	private $_msg_template = array(
		'text' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>',//文本回复XML模板
		'image' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>',//图片回复XML模板
		'music' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[music]]></MsgType><Music><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><MusicUrl><![CDATA[%s]]></MusicUrl><HQMusicUrl><![CDATA[%s]]></HQMusicUrl><ThumbMediaId><![CDATA[%s]]></ThumbMediaId></Music></xml>',//音乐模板
		'news' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles></xml>',// 新闻主体
		'news_item' => '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>',//某个新闻模板
	);
	
	/*
	用于第一次验证我们网站url合法性
	 */
	public function firstValid(){
		//检验签名的合法性
		if($this->_checkSignature()){
			//签名合法，告知微信公众平台服务器
			echo $_GET['echostr'];
		}
	}
	//菜单删除
	public function menuDelete(){
		$url ='https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccessToken();
		$result = $this->_request('get',$url);
		$result_obj = json_decode($result);
		if($result_obj->errcode == 0){
			return true;
		}else{
			return false;
		}
	}
	//创建菜单
	public function menuSet($menu) {
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();
		$data = $menu;
		$result_obj = json_decode($this->_request('post',$url, $data));
		if ($result_obj->errcode == 0) {
			return true;
		} else {
			echo $result_obj->errmsg, '<br>';
			return false;
		}
	}
	/*
	响应信息
	 */
	
	public function responseMsg(){
		/*
		获得请求时POST:XML字符串
		不能用$_POST获取，因为没有key
		 */
		$xml_str = $GLOBALS['HTTP_RAW_POST_DATA'];
		if(empty($xml_str)){
			die('');
		}
		if(!empty($xml_str)){
			// 解析该xml字符串，利用simpleXML
			libxml_disable_entity_loader(true);
			//禁止xml实体解析，防止xml注入
      		$request_xml = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
			//判断该消息的类型，通过元素MsgType
			switch ($request_xml->MsgType){
				case 'event':
					//判断具体的时间类型（关注、取消、点击）
					$event = $request_xml->Event;
	      			if ($event=='subscribe') { // 关注事件
	      				$this->_doSubscribe($request_xml);
	      			}elseif ($event=='CLICK') {//菜单点击事件
	      				$this->_doClick($request_xml);
	      			}elseif ($event=='VIEW') {//连接跳转事件
	      				$this->_doView($request_xml);
	      			}else{

	      			}
					break;
				case 'text'://文本消息
					$this->_doText($request_xml);
					break;
				case 'image'://图片消息
					$this->_doImage($request_xml);
					break;
				case 'voice'://语音消息
					$this->_doVoice($request_xml);
					break;
				case 'video'://视频消息
					$this->_doVideo($request_xml);
					break;
				case 'shortvideo'://短视频消息
					$this->_doShortvideo($request_xml);
					break;
				case 'location'://位置消息
					$this->_doLocation($request_xml);
					break;
				case 'link'://链接消息
					$this->_doLink($request_xml);
					break;
			}		
		}		
	}
	/*******************微信事件处理方法************************/
	//点击菜单
	private function _doClick($request_xml) {
		$content = '你点击了菜单，携带的KEY为: ' . $request_xml->EventKey;
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	private function _doSubscribe($request_xml){
		//处理该关注事件，向用户发送关注信息
		$content = '感谢你关注，请输入 帮助，了解公众号基本口令。';
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	/******************用户输入消息********************/
	//文本消息
	private function _doText($request_xml){
		//接受文本信息
		$content = $request_xml->Content;
		
		if($content == '学习'){
			//显示帮助消息
			$response_content = '输入对应序号或名称，获取相应资源' . "\n" . '[1]PHP'."\n". '[2]Java' . "\n" . '[3]C++';
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
		}elseif($content=='帮助'){
			$response_content = '本公众号支持以下口令：' . "\n" . '学习'."\n". '图片' . "\n" . '音乐'."\n".'新闻'."\n".'如果输入其他，则自动调用图灵机器人，跟你愉快地玩耍。';
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
		}elseif (strtolower($content)=='1'||strtolower($content)=='php') {
			$response_content='php官网：'."\n".'http://www.php.com';
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
		}elseif (strtolower($content)=='2'||strtolower($content)=='java') {
			$response_content='java官网：'."\n".'http://www.java.com';
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
		}elseif (strtolower($content)=='3'||strtolower($content)=='c++') {
			$response_content='c++官网：'."\n".'http://www.c.com';
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content);
		}elseif('图片' == $content){
			$imgMediaId = 'UD-4n5YeK6NXhPCOYT_eV4YxYNZqCILemIZuZR3GYmj_AtrqhnHiIUUOQHSi71Ew';
			$this->_msgImage($request_xml->FromUserName, $request_xml->ToUserName,$imgMediaId,true);
		}elseif('音乐' == $content){
			$music_url='音乐地址';
			$ha_music_url='音乐地址';
			$thumb_media_id='缩略图media_id';
			$title = '等你爱我';
			$desc = '等你爱我-等到地老天荒';
			$this->_msgMusic($request_xml->FromUserName, $request_xml->ToUserName, $music_url, $hq_music_url, $thumb_media_id, $title, $desc);
		}elseif('新闻' == $content){
			$item_list = array(
				array('title'=>'生日快乐','desc'=>'其实你应该用母亲的方式回报母亲','picurl'=>'图片地址','url'=>'http://www.baidu.com'),
				array('title'=>'生日快乐','desc'=>'其实你应该用母亲的方式回报母亲','picurl'=>'图片地址','url'=>'http://www.baidu.com'),
				);
			$this->_msgNews($request_xml->FromUserName,$request_xml->ToUserName,$item_list);
		}else{
			//图灵机器人接入
			$url = 'http://www.tuling123.com/openapi/api?key='.$this->_appkey.'&info='.$content.'&userid='.$request_xml->FromUserName;
			// $data['key'] = $this->_appkey;//
			// $data['info'] = $content;//用户输入的内容
			// $data['userid'] = $request_xml->FromUserName;
			$response_content = json_decode($this->_request('get',$url,array(),false));
			//$response_content->code决定返回的是什么
			//100000  文本 text
			//200000 链接  text+url
			//302000   新闻 text +list(新闻列表，里面有：article,source,icon,detailurl)分别是标题、来源、图片、详情地址
			//308000   菜谱  text+name+info+detailurl+icon
			$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $response_content->text);
		}

	}
	//图片消息
	private function _doImage($request_xml){
		$content = '上传图片成功，请输入 图片 随机获取一张图片'.$request_xml->MediaId;
		$this->_img_list[] = $request_xml->MediaId;
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	//视频消息
	private function _doVideo($request_xml){
		$content = '上传视频成功';
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	//语音消息
	private function _doVoice($request_xml){
		$content = '你TM没吃饭吗？，大声点';
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	//短视频
	private function _doShortvideo($request_xml){
		$content = '短视频发送成功';
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	//位置信息
	private function _doLocation($request_xml){
		// $content = '你的坐标为,经度:'.$request_xml->Location_Y.',纬度:'.$request_xml->Location_X . "\n" . '你所在的位置为：' . $request_xml->Label;
		// $this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
		//利用位置获取信息
		//百度地图api获取ak密钥： vh1HHvQ72KOihdCTpPaz0mudbIOGi2os
		//百度LBS圆形范围查询附近的银行
		//mocode：5GgBxyBW409Cy7b2KOcaYIXfYUnGjPxx
		$url = 'http://api.map.baidu.com/place/v2/search?query=%s&page_size=%s&page_num=%s&scope=%s&location=%s&radius=%s&output=%s&ak=%s&mocode=%s';
		$query = '银行';
		$page_size = 5;//记录数
		$page_num = 0;//第几页
		$scope = 1;//1基本信息，2详细信息
		$location = $request_xml->Location_X.','.$request_xml->Location_Y;
		$radius = 2000;//方圆两公里
		$output = 'json';//返回json格式
		$ak = 'vh1HHvQ72KOihdCTpPaz0mudbIOGi2os';//ak密钥
		$mocode = '5GgBxyBW409Cy7b2KOcaYIXfYUnGjPxx';//应用设置里面有
		$url = sprintf($url,urlencode($query),$page_size,$page_num,$scope,$location,$radius,$output,$ak,$mocode);
		$result = $this->_request('get',$url,array(),false);
		$result_obj = json_decode($result);
		$re_list = array();
		foreach($result_obj->results as $re){
			$r['name'] = $re->name;
			$r['address'] = $re->address;
			$re_list[] = implode('-', $r);
		}
		//implode变为字符串
		$re_str = implode("\n",$re_list);
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $re_str);
		
	}
	//链接消息
	private function _doLink($request_xml){
		$content = '你要给宝宝看撒？';
		//$this->_img_list[] = $request_xml->MediaId;
		$this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	/**
	 * 发送文本信息
	 * @param  [type] $to      目标用户ID
	 * @param  [type] $from    来源用户ID
	 * @param  [type] $content 内容
	 * @return [type]          [description]
	 */
	private function _msgText($to, $from, $content) {
		$response = sprintf($this->_msg_template['text'], $to, $from, time(), $content);
		die($response);
	}
	//发送图片消息
	/**
	 * @param   $to [description]
	 * @param [type] $from [description]
	 * @param [type] $file [description]
	 * @return
	 */
	//发送图片
	private function _msgImage($to,$from,$file,$is_id=false){
		//判断是不是media_id
		if($is_id){
			$media_id=$file;
		}else{
			// 上传图片到微信公众服务器，获取mediaID
			$result_obj = $this->uploadTmp($file, 'image');
			$media_id = $result_obj->media_id;
		}
			//拼凑xml图片发给微信平台，然后平台返回图片给用户
			$response = sprintf($this->_msg_template['image'],$to,$from,time(),$media_id);
			die($response);
	}
	//发送音乐
	private function _msgMusic($to, $from, $music_url, $hq_music_url, $thumb_media_id, $title='', $desc='') {
		$response = sprintf($this->_msg_template['music'], $to, $from, time(), $title, $desc, $music_url, $hq_music_url, $thumb_media_id);
		die($response);
	}
	//发送新闻
	private function _msgNews($to,$from,$item_list=array()){
		//拼凑文章部分
		$item_str = '';
		foreach ($item_list as $item) {
			$item_str .= sprintf($this->_msg_template['news_item'],$item['title'],$item['desc'],$item['picurl'],$item['url']);
		}
		//拼凑主体部分
		$response = sprintf($this->_msg_template['news'], $to, $from, time(), count($item_list), $item_str);
		die($response);
	}
	/**
	 * 上传临时素材：图片,语音，视频，缩略图
	 * 储存到微信公众平台服务器，3天
	 * 可通过上传后返回的media_id再次去取得该图片
	 */
	public function uplodeTmp($file,$type){
		$url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type='.$type;
		$data = array(
			'media' => '@'.$file,
			);
		$result = $this->_request('post',$url,$data);
		$result_obj = json_decode($result);
		return $result_obj;
	}
	/**
	 * 验证签名
	 * @return bool 
	 */
	private function _checkSignature(){
		//获得由微信公众平台请求的验证数据
		$signature = $_GET['signature'];
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];
		//将时间戳，随机字符串，token按照字母顺序排序，病并连接
		$tmp_arr = array($this->_token,$timestamp,$nonce);
		sort($tmp_arr,SORT_STRING);//字典顺序
		$tmp_str = implode($tmp_arr);//连接
		$tmp_str = sha1($tmp_str);//sha1加密
		if($signature==$tmp_str){
			return true;
		}else{
			return false;
		}
	}
	//获取access_token,并保存到文件里
	public function getAccessToken($token_file = './access_token'){
		//考虑这个access_token是否过期
		$life_time = 7200;
		//文件存在，并且左后修改时间与当前时间的差小于access_token的有效期，则有效
		if(file_exists($token_file) && time()-filemtime($token_file)<$life_time){
			//得到内容
			return file_get_contents($token_file);
		}

		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";
		//向该地址发送get请求
		$result = $this->_request('get',$url);
		//处理响应结果
		if(!$result){
			return false;
		}
		//存在返回响应结果,返回对象
		$result_obj = json_decode($result);		
		//写入文件
		file_put_contents($token_file, $result_obj->access_token);
		return $result_obj->access_token;
	}

	//根据access_token获取ticket
	//@param $content 内容
	//@param $type qr码类型
	//@param $expire 有效期，如果是临时类型需指定
	//@return string ticket 
	public function getQRCodeTicket($content,$type=2,$expire=604800){
		$access_token = $this->getAccessToken();
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
		$type_list = array(
				self::QRCODE_TYPE_TEMP => 'QR_SCENE',
				self::QRCODE_TYPE_LIMIT=>'QR_LIMIT_SCENE',
				self::QRCODE_TYPE_LIMIT_STR=>'QR_LIMIT_STR_SCENE'
			);
		$action_name = $type_list[$type];
		//post发送的数据
		switch ($type){
			case self::QRCODE_TYPE_TEMP:
				$data_arr['expire_seconds']=$expire;
				$data_arr['action_name'] = $action_name;
				$data_arr['action_info']['scene']['scene_id']=$content;
				break;
			case self::QRCODE_TYPE_LIMIT:
				$data_arr['action_name'] = $action_name;
				$data_arr['action_info']['scene']['scene_str'] = $content;
				break;
			case self::QRCODE_TYPE_LIMIT_STR:
				$data_arr['action_name'] = $action_name;
				$data_arr['action_info']['scene']['scene_id'] = $content;
				break;
		}
		$data = json_encode($data_arr);
		$result = $this->_request('post',$url,$data);
		if(!$result){
			return false;
		}
		$result_obj = json_decode($result);
		return $result_obj->ticket;
	}
	//根据ticket获取二维码
	/**
	  * @param int|string $content qrcode内容标识
	  * @param [type] $file 存储为文件的地址，如果null直接输出
	  * @param integer $type 类型
	  * @param integer $expire 如果是临时，标识有效期
	  * @return  [type]     
	 */
	
	public function getQRCode($content,$file=NULL,$type=2,$expire=604800){
		//获取ticket
		$ticket = $this->getQRCodeTicket($content,$type=2,$expire=604800);
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
		//发送，取得图片数据
		$result = $this->_request('get',$url);
		if($file){
			file_put_contents($file,$result);
		}else{
			header('Content-Type:image/jpeg');
			echo $result;
		}		
	}

	//发送请求方法
	/**
	 * @param  string $method 'get'|'post' 请求的方式
	 * @param  string $url URL
	 * @param  array|json $data post请求需要发送的数据
	 * @param  bool $ssl
	 */
	private function _request($method='get',$url,$data=array(),$ssl=true){
		//curl完成，先开启curl模块
		//初始化一个curl资源
		$curl = curl_init();
		//设置curl选项
		curl_setopt($curl,CURLOPT_URL,$url);//url
		//请求的代理信息
		$user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']: 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
		curl_setopt($curl,CURLOPT_USERAGENT,$user_agent);
		//referer头，请求来源		
		curl_setopt($curl,CURLOPT_AUTOREFERER,true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
		//SSL相关
		if($ssl){
			//禁用后，curl将终止从服务端进行验证;
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
			//检查服务器SSL证书是否存在一个公用名
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,2);
		}
		//判断请求方式post还是get
		if(strtolower($method)=='post') {
			/**************处理post相关选项******************/
			//是否为post请求 ,处理请求数据
			curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		}
		//是否处理响应头
		curl_setopt($curl,CURLOPT_HEADER,false);
		//是否返回响应结果
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		
		//发出请求
		$response = curl_exec($curl);
		if (false === $response) {
			echo '<br>', curl_error($curl), '<br>';
			return false;
		}
		//关闭curl
		curl_close($curl);
		return $response;
	}
}
