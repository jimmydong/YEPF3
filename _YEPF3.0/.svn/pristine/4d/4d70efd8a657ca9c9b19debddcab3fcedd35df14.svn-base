<?php
/**
 * YOKA应用服务的客户端端基础类，各个应用的客户端继承这个类即可完成REST请求的各项处理以及结果数据的处理。
 * 开发者需要关注的方法：
 * @method request $url 参数不能有参数，所有的参数都要在 $params 中定义，其中特殊的几个参数为[method, appkey, secret, format]，需要和服务器端的method, appkey, secret对应
 */

namespace yoka;

if (! defined ( 'YOKA' ))
	exit ( 'Illegal Request' );

class YokaRestClient
{
	
//	public function __construct()
//	{
//	}

	public function defaultMethod($params)
	{	
		// 如果内部有对参数进行处理调整，则还需要调用一次下面的语句
		// $params = $this->authParams($params, $params['method'], $params['appkey'], $params['secret'], $params['format'])
		$url = 'http://www.yoka.com/';
		$params['method'] = 'defaultMethod';
		$params['appkey'] = 'test';
		$params['secret'] = 'test.yoka.com';
		$params['format'] = 'php';
		$data = $this->request($url, $params, 'post');
		// TODO: $data['errorinfo'] 错误信息处理
		return $data['content'];
	}

	/**
	 * REST方式请求服务器端的资源，同时会完成参数的授权处理以及返回内容的格式化返回
	 * @params String $url
	 * @params Array $params 包含参数[method, appkey, secret, format]
	 * @params Boolean $is_post 是否以post方法请求
	 * @return Array [content, errorinfo, params]
	 */
	protected function request($url, $params, $secret, $is_post = true)
	{
		$params = $this->authParams($params, $secret);
		$curl = new \Curl($url);
		if ($is_post)
		{
			$curl->setOptions(array('timeout' => $this->curl_timeout, 'connecttimeout' => $this->curl_connecttimeout));
//			$curl->setHeader(true);
			$curl->followLocation = 1;
			$data = $curl->post($params);
		}
		else
		{
			if ($params && is_array($params))
			{
				if (strpos($url, '?') === false)
				{
					$url .= '?';
				}
				foreach ($params as $key => $value)
				{
					$url .= '&'.$key.'='.urlencode($value);
				}
			}
			\yoka\Debug::log('request_url', $url);
			$curl->setOptions(array('timeout' => $this->curl_timeout, 'connecttimeout' => $this->curl_connecttimeout));
//			$curl->setHeader(true);
			$curl->followLocation = 1;
			$data = $curl->get();
		}
		return $this->parseOutput($data, $params['format']);
	}

	private function authParams($params, $secret)
	{
		$appsign = $this->createSign($secret, $params);
		$params['appsign'] = $appsign;
		return $params;
	}

	private function parseOutput($data, $format)
	{
		$outdata = null;
		if ($data)
		{
			switch ($format)
			{	// TODO:
				case 'xml' :
					$list = $this->getXmlData($data);
					if (is_array($list))
					{
						$list = $this->filterXmlIndex($list);
					}
					break;
				case 'json' :
					$outdata = json_decode($data);
					break;
				case 'php' :
					$outdata = unserialize($data);
					break;
				default:
					$outdata = unserialize($data);
					break;
			}
		}
		else
		{
			$outdata = array('errorinfo' => array('errno' => '111', 'errmsg' => '服务端返回数据不能为空'));
		}
		if (empty($outdata))
		{
			$outdata = array('content' => $data, 'errorinfo' => array('errno' => '112', 'errmsg' => '服务端返回数据格式不正确，无法正常解析。data='.$data));
		}
		return $outdata;
	}

	private function createSign($secret, $params)
	{
		if ($params && is_array($params))
		{
			ksort($params);
			$str = date('Ymd').$secret;
			foreach ($params as $key => $value)
			{
				if ($key != 'appsign')
				{
					$str .= $key.$value;
				}
			}
			return strtoupper(md5($str));
		}
		return '';
	}

	/**
	 * 过滤从XML中“item_1”这样的索引，只留下索引号1
	 * @param Array $data
	 * @return Array
	 */
	private function filterXmlIndex($data)
	{
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$value = $this->filterXmlIndex($value);
			}
			else
			{
				$value = htmlspecialchars_decode($value, ENT_QUOTES);
			}
			if (preg_match('/^item_[\\d]+$/', $key))
			{
				unset($data[$key]);
				$key = str_replace('item_', '', $key);
			}
			$data[$key] = $value;
		}
		return $data;
	}
	
	/**
	 * 解析xml
	 */
	private function getXmlData($strXml) {
		$pos = strpos($strXml, 'xml');
		if ($pos) {
			$xmlCode = @simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
			$arrayCode = $this->getObjectVarsFinal($xmlCode);
			return $arrayCode ;
		} else {
			return '';
		}
	}
	
	/*
	 * 解析XML节点
	 */
	private function getObjectVarsFinal($obj){
		if(is_object($obj)){
			$obj=get_object_vars($obj);
		}
		
		if(is_array($obj)){
			foreach ($obj as $key=>$value){
				$obj[$key] = $this->getObjectVarsFinal($value);
			}
		}
		return $obj;
	}



}

?>