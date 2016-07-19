<?php
namespace tools;
/**
 * Http操作类
 * @author jimmy.dong@gmail.com
 */
class Http{
	/**
	 * 是否开启调试模式
	 */
	public static $debug = false;
	/**
	 * 代理设定
	 */
	public static $socks_proxy = array(
			'db01'=>array('host'=>'db01','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5),
			'db02'=>array('host'=>'db02','port'=>11080,'user'=>'yisheng','pass'=>'yisheng@2015','type'=>CURLPROXY_SOCKS5),
	);
	
	
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
				$c[]= urlencode($k) . "=" . urlencode($v);
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
		\yoka\Debug::log('get_result', $data);
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
	 * 毫秒级超时 Curl Post
	 * @param string $url
	 * @param array $data
	 * @param int $timeout_microsecond
	 * @param array $header
	 * @param array $cookie
	 * @param mixed $proxy
	 * @return boolean|string
	 */
	public static function curlPost($url, $data=array(), $timeout_microsecond = 3000, $header = null, $cookie = null, $proxy = null){
		\yoka\Debug::log('curlPost', $url);
		$ch = curl_init(trim($url));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_NOSIGNAL,true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_microsecond);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout_microsecond);
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
				$c[]= urlencode($k) . "=" . urlencode($v);
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
		file_put_contents('/tmp/curl.log', 'curl_result:'. $data . "\n", FILE_APPEND);
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
	 * 使用Socket模拟HTTP请求
	 * @param unknown $host
	 * @param unknown $port
	 * @param unknown $method
	 * @param unknown $file
	 * @param unknown $data
	 */
	public static function socket($host, $port, $method, $file, $data){
		//TODO:: ...
	}
}
