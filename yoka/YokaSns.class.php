<?php
/**
 * http://space.yoka.com/plugin/plugin.php?appkey=10001
 * 跳转到 http://space.yoka.com/test/test_api.php?top_params=dWlkPTI3OTAwNSZ1c2VybmFtZT13YW5nX3lpJnRydWVuYW1lPSVFNyU4RSU4QiVFNiVBRiU4NSZhdmF0YXI9aHR0cCUzQSUyRiUyRnNwMi55b2thY2RuLmNvbSUyRnBob3RvcyUyRjMzJTJGYjAlMkYyNzkwMDUlMkZhdmF0YXIlMkYxMjQ3MDMyMDM1XzUwLmpwZyZ0PTEyNTkyMTIzNjE=&top_session=279005&top_sign=TkIIirgBF9JINln0Y8H71A==&appkey=10001
 * @name YokaInterface.class.php
 * @desc Yoka接口类
 * @author wangyi yz124s@hotmail.com
 * @createdate 2009-4-15
 */
namespace yoka;

class YokaSns{
	
	private $expire = 86400;	// 缓存有效时间
	private $mem = null;		// 缓存实例对象
	private $secret = '';	// 接口的密钥
	public $appkey = 0;								// 接口的标识
	private $service = 'http://space.yoka.com/api/rest.php';
	private $cookie_name = 'YOKA_SPACE_COOKIE';					// YOKA插件的cookie
	private $user_cache_pre = 'yoka_userinfo_';
	private $friend_cache_pre = 'yoka_friendinfo_';
	private $yoka_user_list = array();	// 当前已经读取的用户信息
	private $yoka_friend_list = array();	// 当前已经读取的好友信息
	
	function __construct($appkey, $secret)
	{
		$this->appkey = $appkey;
		$this->secret = $secret;
		// TODO:
//		$this->mem = Cache::getInstance('default');
	}

	/**
	 * 根据YOKA的接口，处理登录信息
	 * 首先找地址栏中的参数(top_parameters,top_session,top_sign)，然后查找本地的Cookie
	 * 如果本地Cookie与地址栏参数不一致，以地址栏为标准，重写本地Cookie
	 * @return array [login_uid, login_name, login_truename, login_top_session]
	 */
	public function getLoginUser()
	{
		// 从地址栏，Cookie中读取用户登录的数据
		$cookie_value = $_COOKIE[$this->cookie_name];
		if ($_GET['top_params'] && $_GET['top_session'] && $_GET['top_sign'])
		{	// 存在地址栏的参数
			$top_params = $_GET['top_params'];
			$top_session = $_GET['top_session'];
			$top_sign = $_GET['top_sign'];
			if (base64_encode(md5($this->appkey.$top_params.$top_session.$this->secret, true)) == $top_sign)
			{	// 地址栏验证通过，再次验证本地Cookie值，如果不相同，重新写本地Cookie
				if ($cookie_value != $top_params.'&'.$top_session.'&'.$top_sign)
				{
					header('P3P: CP="ALL ADM DEV PSAi COM OUR OTRo STP IND ONL"');
					setcookie($this->cookie_name, $top_params.'&'.$top_session.'&'.$top_sign);
				}
			}
			else
			{
				if ($cookie_value && strlen($cookie_value) > 10)
				{
					$cookie_params = explode('&', $cookie_value);
					if ($cookie_params && count($cookie_params) == 3)
					{
						$top_params = $cookie_params[0];
						$top_session = $cookie_params[1];
						$top_sign = $cookie_params[2];
					}
				}
			}
		}
		else if ($cookie_value && strlen($cookie_value) > 10)
		{
			$cookie_params = explode('&', $cookie_value);
			if ($cookie_params && count($cookie_params) == 3)
			{
				$top_params = $cookie_params[0];
				$top_session = $cookie_params[1];
				$top_sign = $cookie_params[2];
			}
		}
		if ($_GET['target_url'])
		{	// 如果设置了目标地址，则直接跳转
			header('location: '.urldecode($_GET['target_url']));
			exit();
		}
		$params = base64_decode($top_params);
		// 解析参数
		if (base64_encode(md5($this->appkey.$top_params.$top_session.$this->secret, true)) == $top_sign)
		{	// 参数验证通过
			if ($params && strpos($params, '&') && strpos($params, '='))
			{
				$param_arr = explode('&', $params);
				foreach ($param_arr as $param)
				{
					$param_data = explode('=', $param);
					$key = $param_data[0];
					$value = urldecode($param_data[1]);
					if ($key == 'ts')
					{
						$ts = $value;
					}
					else if ($key == 'uid')
					{
						$uid = $value;
					}
					else if ($key == 'username')
					{
						$name = $value;
					}
					else if ($key == 'truename')
					{
						$truename = $value;
					}
					else if ($key == 'avatar')
					{
						$avatar = $value;
					}
				}
				$user = array(
					'login_uid'			=> $uid,
					'login_name'		=> $name,
					'login_truename'	=> $truename,
					'login_avatar'		=> $avatar,
					'login_top_session'	=> $top_session,
					'login_top_sign'		=> $top_sign
				);
				return $user;
			}
		}
		else
		{
		}
		return null;
	}
	
	/**
	 * 读取用户信息
	 *
	 * @param Long $uid
	 * @return Array [uid,nick,real_name,pic_url]
	 */
	public function getUserInfo($uid = 0)
	{
		//从当前环境中获取
		if($this->yoka_user_list[$uid])
		{
			return $this->yoka_user_list[$uid];
		}
		
		if($this->mem)
		{
			//从缓存中获取
			$yoka_userinfo = $this->mem->get($this->user_cache_pre.$uid);
			if($yoka_userinfo)
			{
				$this->yoka_user_list[$uid] = $yoka_userinfo;
				return $yoka_userinfo;
			}
		}
		
		//从yoka接口中读取
		$topParamArr = array(
			'appkey' => $this->appkey,
			'method' => 'yoka.space.user.get',
			'format' => 'xml',
			'v' => '1.0',
			'timestamp' => date('Y-m-d H:i:s')
		);
	
		//API用户参数
		$userParamArr = array(
			'uid' => $uid
		);
	
		//总参数数组
		$paramArr = $topParamArr + $userParamArr;
		ksort($paramArr);
		$sign = $this->createSign($paramArr);
		$params = $this->createStrParam($paramArr);

		$url = $this->service.'?'.$params.'sign='.$sign;
		$curl_obj = new Curl($url);
		$xml = $curl_obj->get();
		if ($xml)
		{
			$data = $this->getXmlData($xml);
			// $data['user'] 中包含有[uid,username,truename,avatar];
			if ($data && is_array($data) && is_array($data['content']) && $data['content']['uid'] && $data['content']['username'] && $data['content']['truename'])
			{
				$userinfo = $data['content'];
				if($this->mem)
				{
					$this->mem->set($this->user_cache_pre.$uid, $userinfo, $this->expire);
				}
				return $userinfo;
			}
		}
		return null;
	}
		
	/**
	 * 获取用户的好友信息
	 * @param Integer $uid 用户的标识
	 * @return Array 返回用户的所有好友,[uid,nick,real_name,pic_url]
	 */
	public function getUserFriends($uid)
	{
		//从当前环境中获取
		if($this->yoka_friend_list[$uid])
		{
			return $this->yoka_friend_list[$uid];
		}
		
		if($this->mem)
		{
			//从缓存中获取
			$yoka_userinfo = $this->mem->get($this->friend_cache_pre.$uid);
			if($yoka_userinfo)
			{
				$this->yoka_friend_list[$uid] = $yoka_userinfo;
				return $yoka_userinfo;
			}
		}
		
		//从yoka接口中读取
		
		$topParamArr = array(
			'appkey' => $this->appkey,
			'method' => 'yoka.space.friends.get',
			'format' => 'xml',
			'v' => '1.0',
			'timestamp' => date('Y-m-d H:i:s')
		);
	
		//API用户参数
		$userParamArr = array(
			'uid'		=> $uid,
			'start_row'	=> 1,
			'count'		=> 1000
		);
	
		//总参数数组
		$paramArr = $topParamArr + $userParamArr;
		ksort($paramArr);
		$sign = $this->createSign($paramArr);
		$params = $this->createStrParam($paramArr);

		$url = $this->service.'?'.$params.'sign='.$sign;
		$curl_obj = new Curl($url);
		$xml = $curl_obj->get();
		if ($xml)
		{
			$data = $this->getXmlData($xml);
			// $data['user'] 中包含有[uid,nick,real_name,pic_url];
			if ($data && is_array($data) && is_array($data['content']) && $data['content']['item_0'])
			{
				$frineds = $data['content'];
				$datalist = array();
				foreach ($frineds as $friend)
				{
					$datalist[$friend['uid']] = $friend;
				}
				//缓存数据
				$this->yoka_friend_list[$uid] = $datalist;
				if($this->mem)
				{
					$this->mem->set($this->friend_cache_pre.$uid, $datalist, $this->expire);
				}
				return $datalist;
			}
		}
		return null;
	}
	
	/**
	 * 用户是否存在好友关系
	 * @param Integer $uid 用户的标识
	 * @param Integer $friend_uid 好友的标识
	 * @return Boolean
	 */
	public function isUserFriend($uid, $friend_uid)
	{
		if($this->yoka_friend_list[$uid])
		{//从当前环境中获取
			$friend_list = $this->yoka_friend_list[$uid];
		}
		else if ($this->mem) 
		{//从缓存中获取
			$friend_list = $this->getUserFriends($uid);	
		}
		if($friend_list)
		{
			foreach ($friend_list as $key=>$friend)
			{
				if($friend_uid == $friend['uid'])
				{
					return true;
					break;
				}
			}
			return false;
		}
		
		$topParamArr = array(
			'appkey'	=> $this->appkey,
			'method'	=> 'yoka.space.friends.areFriends',
			'format'	=> 'xml',
			'v'			=> '1.0',
			'timestamp'	=> date('Y-m-d H:i:s')
		);
	
		//API用户参数
		$userParamArr = array(
			'uid'	=> $uid,
			'uid2'	=> $friend_uid
		);
	
		//总参数数组
		$paramArr = $topParamArr + $userParamArr;
		ksort($paramArr);
		$sign = $this->createSign($paramArr);
		$params = $this->createStrParam($paramArr);

		$url = $this->service.'?'.$params.'sign='.$sign;
		$curl_obj = new Curl($url);
		$xml = $curl_obj->get();
		if ($xml)
		{
			$data = $this->getXmlData($xml);
			if($data['content'] && $data['content']['AreFriends'] == '1')
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
		return false;
	}
	
	/**
	 * 生成签名
	 * @param Array $paramArr：api参数数组
	 * @return String $sign
	 */
	private function createSign($paramArr)
	{
		$sign = $this->secret;
		ksort($paramArr);
		foreach ($paramArr as $key => $val) {
			if ($key !='' && $val !='') {
				$sign .= $key.$val;
			}
		}
		$sign = strtoupper(md5($sign));
		return $sign;
	}
	
	/**
	 * 生成字符串参数 
	 * @param Array $paramArr：api参数数组
	 * @return String $strParam 以字符“&”结尾
	 */
	private function createStrParam($paramArr) {
		$strParam = '';
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val !='') {
				$strParam .= $key.'='.urlencode($val).'&';
			}
		}
		return $strParam;
	}
	
	/**
	 * 解析xml
	 */
	private function getXmlData($strXml) {
		$pos = strpos($strXml, 'xml');
		if ($pos) {
			$xmlCode = simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
			$arrayCode = $this->get_object_vars_final($xmlCode);
			return $arrayCode ;
		} else {
			return '';
		}
	}
	
	private function get_object_vars_final($obj){
		if(is_object($obj)){
			$obj=get_object_vars($obj);
		}
		
		if(is_array($obj)){
			foreach ($obj as $key=>$value){
				$obj[$key] = $this->get_object_vars_final($value);
			}
		}
		return $obj;
	}
	
}
?>