<?php
namespace yoka;
/**
 * Http操作类
 * 
 * 支持两种模式： curl / socket
 * 【注意】 两种模式的配置参数及功能参数不一致！
 * 
 * 使用代理两种方式：
 * 	1， \yoka\Http::curlGet('http://www.baidu.com', 3000, null, null, ['host'=>'docker01','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5]);
 * 	2， \yoka\Http::addProxy('docker01','docker01',11080,'yisheng','yisheng@2015');
		\yoka\Http::curlGet('http://www.baidu.com', 3000, null, null, 'docker01');
 * 
 * @author jimmy.dong@gmail.com
 */
class Http{
	/**
	 * 是否开启调试模式
	 */
	public static $debug = false;
	
	/**
	 * 缺省socket模式超时时间（秒）
	 */
	public static $socket_timeout = 3;
	
	/**
	 * 对cookie进行处理（默认处理。如果获取的是原始cookie值，则需将此开关关闭）
	 */
	public static $cookie_urlencode = true;
	
	/**
	 * 基本认证（请使用 setBasicAuth方法）
	 */
	public static $basic_auth = false;

	/**
	 * 代理设定
	 */
	public static $socks_proxy = [];
	
	/**
	 * 简单模式。高级模式请使用：curlGet
	 * @param unknown $url
	 */
	public static function get($url){
		if(is_callable('curl_init')){
			return self::curlGet($url);
		}else{
			return self::socket('GET', $url);
		}
	}
	
	/**
	 * 简单模式。高级模式请使用：curlPost
	 * @param unknown $url
	 * @param unknown $post
	 */
	public static function post($url, $post){
		if(is_callable('curl_init')){
			return self::curlPost($url, $post);
		}else{
			return self::socket('POST', $url, $post);
		}
	}
	
	/**
	 * 设置基本认证(仅CURL下有效)
	 * @param unknown $user
	 * @param unknown $pass
	 */
	public static function setBasicAuth($user, $pass){
		self::$basic_auth = array('user'=>$user, 'pass'=>$pass);
	}
	
	/**
	 * 设置代理(仅CURL下有效)
	 * @param array $proxy_list
	 * 格式：
	 	array(
			'docker01'=>array('host'=>'docker01','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5),
			'docker02'=>array('host'=>'docker02','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5),
			'docker03'=>array('host'=>'docker03','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5),
		);
	 */
	public static function setProxy($proxy_list){
		self::$socks_proxy = $proxy_list;
	}

	/**
	 * 增加一个代理
	 * @param string $name	代理名称
	 * @param string $host IP
	 * @param number $port	端口	
	 * @param string $user	认证：用户名
	 * @param string $pass	认证：密码
	 * @param int $type 默认：CURLPROXY_SOCKS5
	 */
	public static function addProxy($name, $host, $port=1080, $user='', $pass='', $type = CURLPROXY_SOCKS5){
		$proxy = array(
				'host' => $host,
				'port' => $port,
				'user' => $user,
				'pass' => $pass,
				'type' => $type,
		);
		if($name)self::$socks_proxy[$name] = $proxy;
		else self::$socks_proxy[] = $proxy;
	}

	/**
	 * 利用curl multi实现并发处理
	 * 【注】
	 * 1，可能单个请求堵塞造成整个流程时间长，应合理设置超时时间
	 * 2，更好的多线程解决方案，可参考python::multiprocess\map
	 *
	 */
	public static function curlMultiGet($url_array, $timeout_microsecond = 5000, $proxy = false){
		$begin_micro_time = self::getMicroTime();
		$handles = $contents = array();

		//初始化curl multi对象
		$mh = curl_multi_init();

		//添加curl 批处理会话
		foreach($url_array as $key => $url)
		{
			$handles[$key] = curl_init($url);
			curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($handles[$key], CURLOPT_TIMEOUT, ceil($timeout_microsecond / 1000));

			//proxy
			if($proxy){
				if(is_array($proxy) && $proxy['host'] && $proxy['port']){
					$p = $proxy;
				}elseif(self::$socks_proxy[$proxy]){
					//指定代理
					$p = self::$socks_proxy[$proxy];
				}else{
					//随机代理
					$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

				}
				curl_setopt($handles[$key], CURLOPT_PROXY, $p['host'] .':'. $p['port']);
				if($p['type'])curl_setopt($handles[$key], CURLOPT_PROXYTYPE, $p['type']);
				if($p['user'])curl_setopt($handles[$key], CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
			}

			curl_multi_add_handle($mh, $handles[$key]);
		}

		//执行并行获取
		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);


		while ($active and $mrc == CURLM_OK) {
			if(curl_multi_select($mh) === -1){
				usleep(100);
				if( (self::getMicroTime() - $begin_micro_time) > $timeout_microsecond ) break;
			}
			do {
				$mrc = curl_multi_exec($mh, $active);
				if($counter++>1000){
					$counter = 0;
					if( (self::getMicroTime() - $begin_micro_time) > $timeout_microsecond ) break 2;
				}
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		}

		//获取批处理内容
		foreach($handles as $key => $ch)
		{
			$content = curl_multi_getcontent($ch);
			$contents[$key] = curl_errno($ch) == 0 ? $content : '';
		}

		//移除批处理句柄
		foreach($handles as $ch)
		{
			curl_multi_remove_handle($mh, $ch);
		}
		//关闭批处理句柄
		curl_multi_close($mh);

		return $contents;
	}

	/**
	 * 辅助函数
	 */
	public static function getMicroTime(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec) * 1000;
	}


	/**
	 * 毫秒级超时 Curl Get
	 * @param string $url
	 * @param int $timeout_microsecond
	 * @param array $header
	 * @param array $cookie
	 * @param mixed $proxy
	 * @return boolean|string
	 */
	public static function curlGet($url, $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		\yoka\Debug::log('get_url', $url);
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if(self::$basic_auth) curl_setopt($ch, CURLOPT_USERPWD, self::$basic_auth['user'] . ":" . self::$basic_auth['pass']);
		if (stripos($url, 'https://') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		if($header){
			$h = array();
			foreach($header as $k=>$v){
				$h[]= urlencode($k) . ":" . urlencode($v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		if($cookie){
			$c = array();
			foreach($cookie as $k=>$v){
				if(self::$cookie_urlencode)$c[]= urlencode($k) . "=" . urlencode($v);
				else $c[]= $k . "=" . $v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $c));
		}

		//proxy
		if($proxy){
			if(is_array($proxy) && $proxy['host'] && $proxy['port']){
				$p = $proxy;
			}elseif(self::$socks_proxy[$proxy]){
				//指定代理
				$p = self::$socks_proxy[$proxy];
			}else{
				//随机代理
				$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

			}
			curl_setopt($ch, CURLOPT_PROXY, $p['host'] .':'. $p['port']);
			if($p['type'])curl_setopt($ch, CURLOPT_PROXYTYPE, $p['type']);
			if($p['user'])curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
		}

		$data = curl_exec($ch);
		\yoka\Debug::log('get_result', (mb_detect_encoding($data, 'UTF-8') === 'UTF-8')?$data:'非UTF8字符，长度:'.strlen($data));
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if($curl_errno >0){
			\yoka\Debug::log('Http::curlGet Error',$curl_error);
			return false;
		}else{
			return $data;
		}
	}
	/**
	 * Curl Get Header 取头信息【不转跳】
	 * @param string $url
	 * @param int $timeout_microsecond
	 * @param array $header
	 * @param array $cookie
	 * @param mixed $proxy
	 * @return boolean|string
	 */
	public static function curlGetWithHeader($url, $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		\yoka\Debug::log('get_url', $url);
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		if(self::$basic_auth) curl_setopt($ch, CURLOPT_USERPWD, self::$basic_auth['user'] . ":" . self::$basic_auth['pass']);
		if (stripos($url, 'https://') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		if($header){
			$h = array();
			foreach($header as $k=>$v){
				$h[]= urlencode($k) . ":" . urlencode($v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		if($cookie){
			$c = array();
			foreach($cookie as $k=>$v){
				if(self::$cookie_urlencode)$c[]= urlencode($k) . "=" . urlencode($v);
				else $c[]= $k . "=" . $v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $c));
		}

		//proxy
		if($proxy){
			if(is_array($proxy) && $proxy['host'] && $proxy['port']){
				$p = $proxy;
			}elseif(self::$socks_proxy[$proxy]){
				//指定代理
				$p = self::$socks_proxy[$proxy];
			}else{
				//随机代理
				$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

			}
			curl_setopt($ch, CURLOPT_PROXY, $p['host'] .':'. $p['port']);
			if($p['type'])curl_setopt($ch, CURLOPT_PROXYTYPE, $p['type']);
			if($p['user'])curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
		}

		$response = curl_exec($ch);
		\yoka\Debug::log('get_result', $response);

		//头部与主体分离
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);

		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if($curl_errno >0){
			\yoka\Debug::log('Http::curlGetWithHead Error',$curl_error);
			return false;
		}else{
			return array('header'=>$header, 'body'=>$body);
		}
	}
	
	/**
	 * 上传文件
	 * @param unknown $url
	 * @param array $file 数组或字符串
	 * eg: [ 'file1'=>'/tmp/test1.jpg', 'file2'='/tmp/test2.jpg']
	 * @param array $data
	 * @param number $timeout_microsecond
	 * @param unknown $header
	 * @param unknown $cookie
	 * @param unknown $proxy
	 */
	public static function curlPostFile($url, $file, $data=array(), $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		if(is_array($file)){
			foreach($file as $k=>$v){
				$data[$k] = new \CURLFile(realpath($v));
			}
		}else{
			$data['upload'] = new \CURLFile(realpath($file));
		}
		\yoka\Debug::log('curlPostFile', $url);
		\yoka\Debug::log('curlPost:file', $file);
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
		if(self::$basic_auth) curl_setopt($ch, CURLOPT_USERPWD, self::$basic_auth['user'] . ":" . self::$basic_auth['pass']);
		if (stripos($url, 'https://') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		if($header){
			$h = array();
			foreach($header as $k=>$v){
				$h[]= urlencode($k) .":" . urlencode($v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		if($cookie){
			$c = array();
			foreach($cookie as $k=>$v){
				if(self::$cookie_urlencode)$c[]= urlencode($k) . "=" . urlencode($v);
				else $c[]= $k . "=" . $v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $c));
		}
		//proxy
		if($proxy){
			if(is_array($proxy) && $proxy['host'] && $proxy['port']){
				$p = $proxy;
			}elseif(self::$socks_proxy[$proxy]){
				//指定代理
				$p = self::$socks_proxy[$proxy];
			}else{
				//随机代理
				$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

			}
			curl_setopt($ch, CURLOPT_PROXY, $p['host'] .':'. $p['port']);
			if($p['type'])curl_setopt($ch, CURLOPT_PROXYTYPE, $p['type']);
			if($p['user'])curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$ret = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);
		if($curl_errno >0){
			\yoka\Debug::log('Http::curlPostFile Error',$curl_error);
			return false;
		}else{
			return $ret;
		}
	}
	
	/**
	 * 毫秒级超时 Curl Post
	 * @param string $url
	 * @param string|array $data 字符串不做处理
	 * @param int $timeout_microsecond
	 * @param array $header
	 * @param array $cookie
	 * @param mixed $proxy
	 * @return boolean|string
	 */
	public static function curlPost($url, $data=array(), $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		\yoka\Debug::log('curlPost', $url);
		\yoka\Debug::log('curlPost:param', is_string($data)?$data:http_build_query($data));
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
		if(self::$basic_auth) curl_setopt($ch, CURLOPT_USERPWD, self::$basic_auth['user'] . ":" . self::$basic_auth['pass']);
		if (stripos($url, 'https://') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		if($header){
			$h = array();
			foreach($header as $k=>$v){
				$h[]= urlencode($k) .":" . urlencode($v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		if($cookie){
			$c = array();
			foreach($cookie as $k=>$v){
				if(self::$cookie_urlencode)$c[]= urlencode($k) . "=" . urlencode($v);
				else $c[]= $k . "=" . $v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $c));
		}
		//proxy
		if($proxy){
			if(is_array($proxy) && $proxy['host'] && $proxy['port']){
				$p = $proxy;
			}elseif(self::$socks_proxy[$proxy]){
				//指定代理
				$p = self::$socks_proxy[$proxy];
			}else{
				//随机代理
				$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

			}
			curl_setopt($ch, CURLOPT_PROXY, $p['host'] .':'. $p['port']);
			if($p['type'])curl_setopt($ch, CURLOPT_PROXYTYPE, $p['type']);
			if($p['user'])curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
		}

		if (is_array($data)) {
			$data = http_build_query($data);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$data = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);
		if($curl_errno >0){
			\yoka\Debug::log('Http::curlPost Error',$curl_error);
			return false;
		}else{
			return $data;
		}
	}
	/**
	 * 毫秒级超时 Curl Post （返回含头信息）
	 * @param unknown $url
	 * @param array $data
	 * @param number $timeout_microsecond
	 * @param unknown $header
	 * @param unknown $cookie
	 * @param unknown $proxy
	 */
	public static function curlPostWithHeader($url, $data=array(), $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		\yoka\Debug::log('curlPost', $url);
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		if(self::$basic_auth) curl_setopt($ch, CURLOPT_USERPWD, self::$basic_auth['user'] . ":" . self::$basic_auth['pass']);
		if (stripos($url, 'https://') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}

		if($header){
			$h = array();
			foreach($header as $k=>$v){
				$h[]= urlencode($k) .":" . urlencode($v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		if($cookie){
			$c = array();
			foreach($cookie as $k=>$v){
				if(self::$cookie_urlencode)$c[]= urlencode($k) . "=" . urlencode($v);
				else $c[]= $k . "=" . $v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $c));
		}
		//proxy
		if($proxy){
			if(is_array($proxy) && $proxy['host'] && $proxy['port']){
				$p = $proxy;
			}elseif(self::$socks_proxy[$proxy]){
				//指定代理
				$p = self::$socks_proxy[$proxy];
			}else{
				//随机代理
				$p = self::$socks_proxy[array_rand(self::$socks_proxy)];

			}
			curl_setopt($ch, CURLOPT_PROXY, $p['host'] .':'. $p['port']);
			if($p['type'])curl_setopt($ch, CURLOPT_PROXYTYPE, $p['type']);
			if($p['user'])curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p['user'] .':'. $p['pass']);
		}

		if (is_array($data)) {
			$data = http_build_query($data);
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);

		//头部与主体分离
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);

		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if($curl_errno >0){
			\yoka\Debug::log('Http::curlGetWithHead Error',$curl_error);
			return false;
		}else{
			return array('header'=>$header, 'body'=>$body);
		}

	}

	/**
	 * 使用Socket模拟HTTP请求
	 * @param string $method GET/POST
	 * @param string $url
	 * @param string|array $postData
	 * @param string header 自定义头部
	 * @param boolean $returnHeader 返回头部
	 */
	public static function socket($method='GET', $url, $postData='', $header='', $returnHeader=false){
		$sPatternUrlPart = '/http:\/\/([a-z-\.0-9]+)(:(\d+)){0,1}(.*)/i';
		preg_match($sPatternUrlPart, $url, $reg);
		$host = gethostbyname($reg[1]);
		if (empty($reg[4])){
			$uri = '/';
		}else{
			$uri = $reg[4];
		}
		if (empty($reg[3])){
			$port = 80;
		}else{
			$port = $reg[3];
		}
		if(self::$debug)var_dump($host, $port, $uri);
		
		$request = "{$method} {$uri} HTTP/1.1\r\n";
		$request .= "HOST:{$host}\r\n";
		$request .= "Accept:*/*\r\n";
		if($header) $request .= trim($header) . "\r\n";
		if ($method == 'POST'){
			$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			if(is_array($postData)){
				foreach($postData as $k=>$v){
					$param[] = urlencode($k) . '=' . urlencode($v);
				}
				$postData = implode('&', $param);
			}
			$request .= "Content-Length: ".strlen($postData)."\r\n";
			$request .= "\r\n";
			$request .= $postData."\r\n";
		}
		$request .= "\r\n";
		
		if(self::$debug)var_dump($request);
		
		$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$sock){
			\yoka\Debug::log('Http::socket', 'socket_create() failed!');
			return false;
		}
		$resSockHttp = socket_connect($sock, $host, $port);
		if (!$resSockHttp){
			\yoka\Debug::log('Http::socket', 'socket_connect() failed!');
			return false;
		}
		socket_write($sock, $request, strlen($request));
		socket_set_timeout($sock, self::$socket_timeout);
		
		$response = ''; $header= ''; $flag = true;
		while ($sRead = socket_read($sock, 4096)){
			if(self::$debug) var_dump2($sRead);
			if($flag){
				if($pos = strpos("\r\n\r\n", $sRead) !== false){
					$flag = false;
					$header .= substr($sRead, 0, $pos+1);
					$response = substr($sRead, $pos);
				}else{
					$hader .= $sRead;
				}
			}else{
				$response .= $sRead;
			}
		}
		socket_close($sock);
		if($returnHeader){
			return array('header'=>$header, 'body'=>$response);
		}else{
			return $response;
		}
	}
}
