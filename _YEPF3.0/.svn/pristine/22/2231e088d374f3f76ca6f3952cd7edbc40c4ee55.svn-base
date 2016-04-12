<?php
/**
 * YOKA的服务器端数据交换辅助类
 * HTTP方式的请求，支持POST,GET方法
 * 
 * 依赖：curl.class.php
 * 
 * 公共属性列表
 * 1 $request_start_time
 * 2 $request_end_time
 * 3 $curl_timeout
 * 4 $curl_connecttimeout
 * 
 * 公共方法列表
 * 1 processRequest()
 * 2 output($content, $result_id = null, $result_msg = null)
 * 3 analyse($data)
 * 4 request($url, $params = null, $http_method = 'post')
 * 5 resultDo()
 * 6 createXml($data, $result_id = null, $result_msg = null)
 * 7 createJson($data, $result_id = null, $result_msg = null)
 * 8 parseXml($data)
 * 9 parseJson($data)
 * 10 createSign($params = null)
 * 11 transferOutContent($content, $charset = 'utf-8')
 * 12 setParams
 * 13 getParams
 * 
 * @author wangyi yz124s@hotmail.com
 * @date 2009-05-21
 */
namespace yoka;
use \yoka\Debug as Debug;

class YokaServiceUtility
{
	private $result_id = null;			// 操作代码，String
	private $result_msg = null;			// 操作描述信息，String
	private $start_time = 0;			// 处理的开始时间，UNIX_TIMESTAMP
	private $end_time = 0;				// 处理的结束时间，UNIX_TIMESTAMP
	private $content = null;			// 主体内容，String
	private $http_method = null;		// 请求的HTTP方法(post/get)，String
	private $params = null;				// 请求的参数集合，Array
	private $url = null;				// 请求的地址，String
	public $request_start_time = 0;		// 请求的开始时间，UNIX_TIMESTAMP
	public $request_end_time = 0;		// 请求的结束时间，UNIX_TIMESTAMP
	public $show_tag = 0;				// 是否显示标记，默认不显示(把标记输出到 header 中)，显示的话标记有如：<!-- result_id=00 --> 
	public $secret = 'YOKA.com';		// 默认的密钥
	public $curl_timeout = 30;			// 默认的网络请求时间30秒
	public $curl_connecttimeout = 5;	// 默认的网络链接时间5秒
	
	/**
	 * 系统级的操作代码
	 */
	public $code_list = array(
		'normal'	=> '00',		// 正常
		'data_static'	=> '10',	// 数据无更新
		'data_restore'	=> '11',	// 数据自动恢复
		'data_lost'		=> '12',	// 数据不存在
		'data_format'	=> '13',	// 数据格式错误
		'param_forbid'	=> '20',	// 参数非法
		'param_lost'	=> '21',	// 参数缺失
		'param_wrong'	=> '22',	// 参数不正确
		'net_timeout'	=> '30',	// 请求超时
		'service_exception'	=> '40',	// 程序异常
		'service_error'		=> '41',	// 服务器异常
		'database_error'	=> '42',	// 数据库异常
		'server_load'		=> '50',	// 服务器负载大
		'other'		=> '90'				// 未知异常
	);
	
	public function YokaServiceUtility()
	{
		$this->start_time = time();
	}
	
	/**
	 * 设置参数
	 * @param $key 参数的键
	 * @param $value 参数的内容
	 */
	public function setParams($key, $value)
	{
		$this->params[$key] = $value;
	}
	
	/**
	 * 获取当前的所有参数
	 * @return Array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * 帮助数据提供者处理从POST,GET过来的参数，如果参数中指定了输入编码，则参数的内容已经做了编码转换。
	 * @return Array 参数集合
	 */
	public function processRequest()
	{
		$params = array();
		if ($_GET)
		{
			if ($_GET['in_charset'] && strtolower($_GET['in_charset']) != 'utf-8')
			{
				foreach ($_GET as $key => $value)
				{
					if($value && is_string($value))
					{
						$value = iconv($_GET['in_charset'], 'utf-8//IGNORE', urldecode($value));
					}
					$params[$key] = $value;
					$_GET[$key] = $value;
				}
			}
			else
			{
				foreach ($_GET as $key => $value)
				{
					if($value && is_string($value))
					{
						$value = urldecode($value);
					}
					$params[$key] = $value;
					$_GET[$key] = $value;
				}
			}
		}
		if ($_POST)
		{
			if ($_POST['in_charset'] && strtolower($_POST['in_charset']) != 'utf-8')
			{
				foreach ($_POST as $key => $value)
				{
					if($value && is_string($value))
					{
						$value = iconv($_POST['in_charset'], 'utf-8//IGNORE', $value);
					}
					$params[$key] = $value;
					$_POST[$key] = $value;
				}
			}
			else
			{
				foreach ($_POST as $key => $value)
				{
					$params[$key] = $value;
				}
			}
		}
		$this->params = $params;
		return $params;
	}
	
	/**
	 * 帮助数据提供者输出标准格式的内容。
	 * 方法返回的内容就是输出给数据使用者的全部数据（封装后的新数据或者在header中写入了标准格式）。
	 * 默认是把标准格式写入到 header 中，这样就不会改变输出的主体内容。（注：在 header 方法前不能有数据输出）
	 * @param String $content 输出的主体内容
	 * @param String $result_id 操作代码
	 * @param String $result_msg 操作描述信息
	 * @return String 把数据进行格式化封装后的新数据
	 */
	public function output($content, $result_id = null, $result_msg = null)
	{
		$this->end_time = time();
		if ($this->show_tag)
		{	// 可视化的输出标准格式
			$header = "<!-- {$result_id} -->\n";
			$header .= "<!-- {$result_msg} -->\n";
			$header .= '<!-- START '.date('Y-m-d H:i:s', $this->start_time).' -->';
			$footer = '<!-- END '.date('Y-m-d H:i:s', $this->end_time).' -->';
			$output = $header."\n".$content."\n".$footer;
		}
		else
		{	// 把标准格式通过 header 输出
			header("yokaservice_result_id: {$result_id}");
			header("yokaservice_result_msg: {$result_msg}");
			header('yokaservice_start: '.date('Y-m-d H:i:s', $this->start_time));
			header('yokaservice_end: '.date('Y-m-d H:i:s', $this->end_time));
			$output = $content;
		}
		return $output;
	}
	
	/**
	 * 帮助数据使用者分析内容。
	 * 如果指定了 $this->params['format'] == 'json' 则使用 /** 前缀，*\/ 后缀。
	 * 根据result_id的值，数据使用者就可以做一些特殊处理或者默认处理。
	 * @param $data 数据提供者输出的全部数据（封装后的新数据，包括 header 信息）
	 * @return Array [result_id, result_msg, content, start_time, end_time]
	 */
	public function analyse($data)
	{
		// 1 处理内容的头部信息
		$page_header_info = $this->get_header_info($data);
		if ($page_header_info['http_code']{0} == '2')
		{
			if ($page_header_info['result_id'] && $page_header_info['start_time'] && $page_header_info['end_time'])
			{	// 2 头部信息已经包含了正确的接口数据格式，直接输出内容
				$this->result_id = $page_header_info['result_id'];
				$this->result_msg = $page_header_info['result_msg'];
				$this->start_time = $page_header_info['start_time'];
				$this->end_time = $page_header_info['end_time'];
			}
			$this->content = $page_header_info['content'];
		}
		else if ($page_header_info['http_code']{0} == '4')
		{
				$this->result_id = $this->code_list['data_lost'];
				$this->result_msg = '网页不存在';
				$this->content = '';
		}
		else if ($page_header_info['http_code']{0} == '5')
		{
				$this->result_id = $this->code_list['service_exception'];
				$this->result_msg = '服务出现异常';
				$this->content = '';
		}
		else
		{
			$this->content = $data = $page_header_info['content'];
		}
		if ($this->result_id)
		{	// 有返回结果了，直接返回数据
			return array(
				'result_id' => $this->result_id, 
				'result_msg' => $this->result_msg, 
				'content' => $this->content, 
				'start_time' => $this->start_time, 
				'end_time' => $this->end_time, 
				'request_start_time' => $this->request_start_time, 
				'request_end_time' => $this->request_end_time);
		}
		$data = $this->content;
		$data_list = explode("\n", $data);
		$line_num = count($data_list);
		if ($line_num < 5)
		{
			return array('result_id' => $this->code_list['data_format'], 
					'result_msg' => '数据格式不正确', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		$str_result_id = $data_list[0];
		$str_result_msg = $data_list[1];
		$str_check_start = $data_list[2];
		$str_check_end = $data_list[$line_num - 1];
		// 3.1 数据头部的操作代码
		if (preg_match('/^<!\-\-[ ]?([0-9]{2,9})[ ]?\-\->$/', $str_result_id, $matchs))
		{
			$this->result_id = $matchs[1] ? $matchs[1] : $this->code_list['other'];
		}
		else
		{
			return array('result_id' => $this->code_list['data_format'], 
					'result_msg' => '数据头部的操作代码，数据格式不正确。', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		// 3.2 数据头部的操作描述信息
		if (preg_match('/^<!\-\-[ ]?([\s\S]*?)[ ]?\-\->$/', $str_result_msg, $matchs))
		{
			$this->result_msg = $matchs[1];
		}
		else
		{
			return array('result_id' => $this->code_list['data_format'], 
					'result_msg' => '数据头部的操作描述信息，数据格式不正确。', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		// 3.3 数据头部的数据校验头信息
		if (preg_match('/^<!\-\-[ ]?START ([0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})[ ]?\-\->$/', $str_check_start, $matchs))
		{
			$this->start_time = strtotime($matchs[1]);
		}
		else
		{
			return array('result_id' => $this->code_list['data_format'], 
					'result_msg' => '数据头部的数据校验头信息，数据格式不正确。', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		// 3.4 数据头部的数据校验尾信息
		if (preg_match('/^<!\-\-[ ]?END ([0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})[ ]?\-\->$/', $str_check_end, $matchs))
		{
			$this->end_time = strtotime($matchs[1]);
		}
		else
		{
			return array('result_id' => $this->code_list['data_format'], 
					'result_msg' => '数据头部的数据校验尾信息，数据格式不正确。', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		// 3.5 主体内容
		$this->content = implode("\n", array_slice($data_list, 3, $line_num - 4));
		$request_result = array(
				'result_id' => $this->result_id, 
				'result_msg' => $this->result_msg, 
				'content' => $this->content, 
				'start_time' => $this->start_time, 
				'end_time' => $this->end_time, 
				'request_start_time' => $this->request_start_time, 
				'request_end_time' => $this->request_end_time);
		Debug::log('request_result', $request_result);
		return $request_result;		
	}
	
	/**
	 * 帮助数据使用者简便地请求数据提供者，并且分析返回后的数据。
	 * 使用GET方式请求的时候，所有的参数都会使用 urlencoding 转码
	 * 依赖 curl 类进行HTTP请求，调用 analyse 方法分析数据。
	 * @param String $url 请求的地址
	 * @param Array $params 请求的参数
	 * @param String $http_method 请求的方法，post | get
	 * @return Array [result_id, result_msg, content, start_time, end_time]
	 */
	public function request($url, $params = null, $http_method = 'post')
	{
		if (class_exists('Debug'))
		{
			Debug::log('interface: '.$url, $params);
		}
		$this->url = $url;
		$this->params = $params;
		$this->http_method = $http_method;
		if (isset($params['sign']))
		{
			$params['sign'] = $this->createSign($params);
			Debug::log('sign', $params['sign']);
		}
		$this->request_start_time = time();
		if (strtolower($http_method) == 'post')
		{
			$curl = new Curl($url);
			$curl->setOptions(array('timeout' => $this->curl_timeout, 'connecttimeout' => $this->curl_connecttimeout));
			$curl->setHeader(true);
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
			Debug::log('request_url', $url);
			$curl = new Curl($url);
			$curl->setOptions(array('timeout' => $this->curl_timeout, 'connecttimeout' => $this->curl_connecttimeout));
			$curl->setHeader(true);
			$curl->followLocation = 1;
			$data = $curl->get();
		}
		$this->request_end_time = time();
		if (empty($data))
		{	// 没有返回任何数据
			return array('result_id' => $this->code_list['other'], 
					'result_msg' => '未知异常，返回了空数据。', 
					'content' => $data,
					'request_start_time' => $this->request_start_time, 
					'request_end_time' => $this->request_end_time);
		}
		else
		{
			return $this->analyse($data);
		}
	}
	
	/**
	 * // TODO: 数据分析完成后帮助数据使用者的默认结果处理方法，如：
	 * 超时后的自动重试，
	 * 数据提供者压力过大时的数据缓存和延时访问数据提供者
	 * 
	 * 必须调用request方法后才能够执行该方法，返回数据的格式与request方法一致。 
	 * @return Array
	 */
	public function resultDo()
	{
		if ($this->url)
		{
			return array('result_id' => $this->result_id, 'result_msg' => $this->result_msg, "content" => $this->content, "start_time" => $this->start_time, "end_time" => $this->end_time);
		}
		else
		{
			return array('result_id' => $this->code_list['other'], 'result_msg' => '无法请求服务器，请先执行 request 方法。', null);
		}
	}
	
	/**
	 * 将参数data数组转换为XML格式，XML字符串，如果指定了输出编码，则内容已经执行了编码转换。
	 * 如果指定了result_id,result_msg，则会把这两个数据也写入到Xml数据中。
	 * @param Array $data 数据体
	 * @param String $result_id 操作代码
	 * @param String $result_msg 操作描述信息
	 * @return String
	 */
	public function createXml($data, $result_id = null, $result_msg = null)
	{
		$charset = 'UTF-8';
		if ($this->params && $this->params['out_charset'] && strtolower($this->params['out_charset']) != 'utf-8')
		{
			$charset = strtoupper($this->params['out_charset']);
		}
		$doc = new \DOMDocument('1.0', $charset);
		$parent = $doc->createElement('yoka');
		$doc->appendChild($parent);
		if (is_array($data))
		{
			$node = $doc->createElement('content');
			$node = $this->createChildNode($doc, $node, $data);
		}
		else
		{
			$node = $doc->createElement('content', htmlspecialchars($data, ENT_QUOTES));
		}
		$parent->appendChild($node);
		if ($result_id)
		{
			$node = $doc->createElement('result_id', htmlspecialchars($result_id, ENT_QUOTES));
			$parent->appendChild($node);
		}
		if ($result_msg)
		{
			$node = $doc->createElement('result_msg', htmlspecialchars($result_msg, ENT_QUOTES));
			$parent->appendChild($node);
		}
		$this->end_time = time();
		$node = $doc->createElement('start_time', date('Y-m-d H:i:s', $this->start_time));
		$parent->appendChild($node);
		$node = $doc->createElement('end_time', date('Y-m-d H:i:s', $this->end_time));
		$parent->appendChild($node);
		$output = $doc->saveXML();
		if (ord($output[strlen($output) - 1]) == 10)
		{
			$output = substr($output, 0, strlen($output) - 1);
		}
		return $output;
	}

	/**
	 * 将参数data数组转换为JSON格式，返回字符串，如果指定了输出编码，则内容已经执行了编码转换。
	 * 如果指定了result_id,result_msg，则会把这两个数据也写入到JSON数据中。
	 * @param Array $data 数据体
	 * @param String $result_id 操作代码
	 * @param String $result_msg 操作描述信息
	 * @return String JSON格式的数据
	 */
	public function createJson($data, $result_id = null, $result_msg = null)
	{
		$content = array();
		$content['content'] = $data;
		$this->end_time = time();
		if ($result_id)
		{
			$content['result_id'] = $result_id;
		}
		if ($result_msg)
		{
			$content['result_msg'] = $result_msg;
		}
		$content['start_time'] = date('Y-m-d H:i:s', $this->start_time);
		$content['end_time'] = date('Y-m-d H:i:s', $this->end_time);
		$output = json_encode($content);
		return $output;
	}
	
	/**
	 * 将XML格式的参数data字符串解析为数组。
	 * XML字符串“<?xml”前面的字符会被忽略 
	 * @param String $data XML格式的数据
	 * @return Array
	 */
	public function parseXml($data)
	{
		$xml_pos = strpos($data, '<?xml ');
		if ($xml_pos > 0)
		{
			$data = substr($data, $xml_pos);
		}
		$list = $this->getXmlData($data);
		if (is_array($list))
		{
			$list = $this->filterXmlIndex($list);
		}
		if (empty($list) && $data)
		{	// 数据为空，很可能是解析出现异常
//			@error_log('parseXml - URL:[{'.$this->url.'}], HOST:[{'.$_SERVER['HTTP_HOST'].'}], URI:[{'.$_SERVER['REQUEST_URI'].'}]', 0);
		}
		return $list;
	}
	
	/**
	 * 将JSON格式的参数data字符串解析为数组。
	 * JSON字符串不能出现 /** *\/ 之类的字符 
	 * @param String $data JSON格式的数据
	 * @return Array
	 */
	public function parseJson($data)
	{
/*
		$json_pos = strpos($data, '{"');
		if ($json_pos > 0)
		{
			$data = substr($data, $json_pos);
		}
*/
		$list = json_decode($data, true);
		if (empty($list) && $data)
		{	// 数据为空，很可能是解析出现异常
//			@error_log('parseJson - URL:[{'.$this->url.'}], HOST:[{'.$_SERVER['HTTP_HOST'].'}], URI:[{'.$_SERVER['REQUEST_URI'].'}]', 0);
		}
		return $list;
	}
	
	/**
	 * 从操作代码中获取到系统层级的操作代码
	 * @param String $result_id 操作代码
	 * @return String 
	 */
	public function getSystemCode($result_id)
	{
		if ($result_id && strlen($result_id) >= 2 && is_string($result_id))
		{
			return substr($result_id, 0, 2);
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 从操作代码中获取到产品层级的操作代码
	 * @param String $result_id 操作代码
	 * @return String 
	 */
	public function getProductCode($result_id)
	{
		if ($result_id && strlen($result_id) >= 4 && is_string($result_id))
		{
			return substr($result_id, 2, 2);
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 从操作代码中获取到自定义层级的操作代码
	 * @param String $result_id 操作代码
	 * @return String 
	 */
	public function getCustomCode($result_id)
	{
		if ($result_id && strlen($result_id) == 7 && is_string($result_id))
		{
			return substr($result_id, 4, 3);
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 根据现有的参数和密钥计算出sign值
	 * @param Array $params 参数集合
	 * @return String
	 */
	public function createSign($params = null)
	{
		if ($params && is_array($params))
		{
			ksort($params);
			$str = $this->secret;
			foreach ($params as $key => $value)
			{
				if ($key != 'sign')
				{
					$str .= $key.$value;
				}
			}
			return strtoupper(md5($str));
		}
		return '';
	}
	
	/**
	 * 指定的sign值是否正确
	 * @param String $sign 给定需要验证的sign值
	 * @param Array $params 用来计算sign值的参数集合
	 * @return Boolean
	 */
	public function checkSign($sign, $params = null)
	{
		$sign2 = $this->createSign($params);
		return ($sign2 == $sign);
	}
	
	/**
	 * 在节点node下面增加data中的数据
	 * @param XmlDocument $doc XML文档对象
	 * @param XmlElement $node XML节点对象
	 * @param Array $data 数据
	 * @return XmlElement
	 */
	private function createChildNode($doc, $node, $data)
	{
		$charset = 'UTF-8';
		if ($this->params && $this->params['out_charset'] && strtolower($this->params['out_charset']) != 'utf-8')
		{
			$charset = strtoupper($this->params['out_charset']);
		}
		foreach ($data as $key => $value)
		{
			if (is_numeric($key))
			{
				$key = 'item_'.$key;
			}
			if (is_array($value))
			{
				$child = $doc->createElement($key);
				$child = $this->createChildNode($doc, $child, $value);
			}
			else
			{
				$child = $doc->createElement($key, htmlspecialchars($value, ENT_QUOTES));
			}
			if ($child)
			{
				$node->appendChild($child);
			}
		}
		return $node;
	}
	
	/**
	 * 过滤从XML中“item_1”这样的索引，只留下索引号1
	 * @param Array $data
	 * @return Array
	 */
	private function filterXmlIndex($data)
	{
		$charset = 'UTF-8';
		if ($this->params && $this->params['out_charset'] && strtolower($this->params['out_charset']) != 'utf-8')
		{
			$charset = strtoupper($this->params['out_charset']);
		}
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

	/**
	 * 对输出的内容进行编码转换
	 * @param String $content 需要输出的内容
	 * @param String $charset 编码，默认是 utf-8
	 * @return String
	 */
	public function transferOutContent($content, $charset = 'utf-8')
	{
		if (!empty($charset) && $charset != 'utf-8')
		{
			return iconv('utf-8', strtolower($charset.'//IGNORE'), $content);
		}
		else
		{
			return $content;
		}
	}
	
	/**
	 * 分析页面的头信息，返回页面中的数据，如果页面中没有头信息，则只返回数据
	 * @param String $data 包含有页头信息的网页数据
	 * @return Array [http_code, content, result_id, result_msg, start_time, end_time]
	 */
	private function get_header_info($data)
	{
		$page_infos = explode("\n", $data);
		$first_line = $page_infos[0];
		$result = array();
//		echo $first_line."\n";
		if (preg_match('/HTTP\/[\d]{1}\.[\d]{1} ([\d]{3}) /i', $first_line, $matches))
		{
			$http_code = $matches[1];
		}
	//	echo $http_code[0]."\n";
		$result['http_code'] = $http_code;
		if ($http_code[0] == '1' || $http_code[0] == '3')
		{	// code=1**,继续处理，code=3**,跳转
			foreach ($page_infos as $line_index => $line)
			{
	//			echo $line."\n";
				unset($page_infos[$line_index]);
				$line = trim($line);
				if (empty($line))
				{
					return $this->get_header_info(implode("\n", $page_infos));
				}
			}
		}
		else if ($http_code[0] == '2')
		{	// code=2**,成功，分析页头中的 result_id 等的信息
			foreach ($page_infos as $line_index => $line)
			{
				unset($page_infos[$line_index]);
				$line = trim($line);
	//			echo $line."\n";
				if (empty($line))
				{
					$content = implode("\n", $page_infos);
					$result['content'] = $content;
					break;
				}
				else 
				{
					$pos = strpos($line, ':');
					if ($pos)
					{
						$header_key = substr($line, 0, $pos);
						$header_value = substr($line, $pos + 1);
						if ($header_key == 'yokaservice_result_id')
						{
							$result['result_id'] = $header_value ? $header_value : $this->code_list['other'];
						}
						else if ($header_key == 'yokaservice_result_msg')
						{
							$result['result_msg'] = $header_value;
						}
						else if ($header_key == 'yokaservice_start')
						{
							$result['start_time'] = strtotime($header_value);
						}
						else if ($header_key == 'yokaservice_end')
						{
							$result['end_time'] = strtotime($header_value);
						}
					}
				}
			}
		}
		else if ($http_code[0] == '4' || $http_code[0] == '5')
		{	// code=4**,未找到，code=5**,内部异常
			$result['content'] = '';			// 出现异常，把内容设置为空
			$result['error_content'] = $data;
		}
		else
		{
			$result['content'] = $data;
		}
		return $result;
	}
}
?>