<?php
/**
 * YOKA应用服务的服务端基础类，各个应用的服务端继承这个类即可完成输入和输出的各项处理。
 * 对外部只提供一个运行方法 run() ，实际处理的方法只提供了一个示例的 defaultMethod ， 其他更多方法可以自己来实现
 * 外部服务器接口的入口页代码实例： 
 *	$service = new YokaRestServer();
 *	$service->run();
 * 开发者需要了解的特定配置
 * @info config_secret 客户端的验证密钥和可调用方法
 * @info config_methods 外部可访问的方法
 * 开发这需要了解的内部方法
 * @method getParam
 * @method setError
 * 调用方需要了解的特定参数
 * @params appkey 客户端标识，由服务端配置，在 config_secret 中配置
 * @params appsign 验证字符串, createSign() 方法创建
 * @params method 调用方法， config_methods 中配置
 * @params format 格式化输出， xml|json|php，默认是php格式输出
 * 
 */

namespace yoka;

if (! defined ( 'YOKA' ))
	exit ( 'Illegal Request' );

class YokaRestServer
{
	private $errorinfo = array();			// 错误信息，包括 [errno, errmsg]，通过 getError()/ setError() 方法读取和设置错误信息
	protected $params = array();				// 调用的参数，支持 _GET 和 _POST，通过 getParam() 方法获取指定的参数
	protected $config_secret = array(		// 客户端的验证密钥和可调用方法，针对各个应用客户端可以设置，系统默认有一个 default 应用
		'default' => array('key' => 'yoka.com', 'methods' => array('defaultMethod')),
	);
	protected $config_methods = array('defaultMethod');		// 外部可访问的方法

	/**
	 * 方法调用的运行方法，里面封装了参数处理，权限验证，系统错误处理以及操作方法调用和结果上格式化输出
	 */
	public function run()
	{
		// TODO: 日志
		$output_data = null;
		$this->processParams();				// 参数处理
		$ok = $this->checkAppAuth();		// 授权验证
		if ($ok)
		{
			$method = $this->getParam('method');
			$output_data = $this->$method();	// 实际处理
		}
		$content = $this->output($output_data);	// 格式化输出
		echo $content;
		// TODO: 日志
	}

	
	/**
	 * 数据以简单类型（Integer,String等）或者数组（Array）返回
	 * @return Integer|String|Array 不要使用XML,JSON格式的数据作为返回
	 */
	protected function defaultMethod()
	{
		$test = $this->getParam('test');
		return 'default method with param testt='.$test;
	}


	/**
	 * 设置错误信息
	 * @params Integer $errno 错误代码，其中101_~199是系统内部的错误代码，不要重复使用
	 * @params String $errmsg 错误详情
	 */
	protected function setError($errno, $errmsg)
	{
		$this->errorinfo['errno'] = $errno;
		$this->errorinfo['errmsg'] = $errmsg;
	}

	/**
	 * 获取参数信息
	 * @params String $key 参数的名称
	 * @return String
	 */
	protected function getParam($key)
	{
		return $this->params[$key];
	}

	/**
	 * 参数处理
	 * 从 _GET, _POST 中读取， _POST中的参数优先
	 */
	private function processParams()
	{
		$this->params = $_GET;
		if ($_POST)
		{
			foreach ($_POST as $k => $v)
			{
				$this->params[$k] = $v;
			}
		}
	}

	/**
	 * 验证客户端调用的权限
	 * 根据appkey验证appsign以及method
	 */
	private function checkAppAuth()
	{
		// 验证接口字符串
		$appkey = $this->getParam('appkey');
		if (empty($appkey))
		{
			$appkey = 'default';
		}
		if (isset($this->config_secret[$appkey]))
		{	// 存在这个客户端配置
			$appsign = $this->createSign($this->config_secret[$appkey]['key'], $this->params);
			if ($appsign == strtoupper($this->getParam('appsign')))
			{
				// 验证调用的方法
				$method = $this->getParam('method');
				if (in_array($method, $this->config_methods))
				{
					if (empty($this->config_secret[$appkey]['methods']) || in_array($method, $this->config_secret[$appkey]['methods']))
					{	// 没有限定操作方法或者执行的方法在操作方法中
						return true;
					}
					else
					{	// 没有权限操作此方法
						$this->setError('101', '没有权限操作此方法');
					}
				}
				else
				{	// 操作方法不存在
					$this->setError('102', '操作方法不存在');
				}
			}
			else
			{	// 验证字符串不合法
				$this->setError('103', '验证字符串不合法');
			}
		}
		else
		{	// 客户端应用不存在
			$this->setError('104', '客户端应用不存在');
		}
		return false;
	}
	
	/**
	 * 将数据进行格式化输出，识别参数 format ，返回的数据中包含有 [content, errorinfo, params]
	 */
	private function output($data = null)
	{
		$format = $this->getParam('format');
		$errorinfo = $this->getError();
		$output_content = array('content' => $data);
		if ($errorinfo)
		{
			$output_content['errorinfo'] = $errorinfo;
			$output_content['params'] = $this->params;
		}
		$content = null;
		switch ($format)
		{
			case 'xml' : 
				$doc = new DOMDocument('1.0', $charset);
				$parent = $doc->createElement('yoka');
				$doc->appendChild($parent);
				$node = $this->createChildNode($doc, $parent, $output_content);
				$parent->appendChild($node);
				$content = $doc->saveXML();
				break;
			case 'json' :
				$content = json_encode($output_content);
				break;
			case 'php' :
				$content = serialize($output_content);
				break;
			default :
				$content = serialize($output_content);
				break;
		}
		return $content;
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
	 * 在节点node下面增加data中的数据
	 * @param XmlDocument $doc XML文档对象
	 * @param XmlElement $node XML节点对象
	 * @param Array $data 数据
	 * @return XmlElement
	 */
	private function createChildNode($doc, $node, $data)
	{
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
	 * 获取错误信息
	 * @return Array [errno, errmsg]
	 */
	private function getError()
	{
		return $this->errorinfo;
	}


}

?>