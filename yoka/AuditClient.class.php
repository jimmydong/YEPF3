<?php
/**
 * 向审计平台提交数据
*/
namespace yoka;
class AuditClient{
	static $auditUrl = "http://audit.yishengdaojia.com";
	static $clientPool = [];
	public $secuKey;
	public $platform;

	/**
	 * 平台定义
	 */
	const PLATFORM_YISHENG = 1;
	const PLATFORM_SAAS = 2;
	const PLATFORM_AIBANGMANG = 3;
	const PLATFORM_ZHAOPIN = 4;
	public static $platformDes = [
			self::PLATFORM_YISHENG 		=> '到家后台',
			self::PLATFORM_SAAS			=> '宜店宝后台',
			self::PLATFORM_AIBANGMANG	=> '无忧后台',
			self::PLATFORM_ZHAOPIN		=> '微猎后台',
	];

	/**
	 * 获取实例
	 * @param unknown $platform 平台
	 * @param unknown $secukey 保密码
	 */
	static public function getInstance($platform, $secukey){
		if($client = self::$clientPool[$platform] && $client->secuKey == $secukey){
			return $client;
		}
		$client = new self;
		$client->platform = $platform;
		$client->secuKey = $secukey;
		self::$clientPool[$platform] = $client;
		return $client;
	}
	/**
	 * 签名处理
	 * @param unknown $data
	 */
	public function _sign($data){
		$sign = md5(json_encode($data) . $this->secuKey);
		return $sign;
	}
	public function _call($_c, $_a, $data){
		//if(defined('IS_TEST') && IS_TEST) return true; //测试环境不做记录
		
		$url = self::$auditUrl . "/?_c={$_c}&_a={$_a}";
		$param['sign'] = $this->_sign($data);
		$param['platform'] = $this->platform;
		$param['data'] = $data;
		$ret = \yoka\Http::curlPost($url, $param, 2000); //设置超时
		//TODO:: $ret错误时报警
		return $ret;
	}
	/**
	 * 后台关键数据
	 * @param unknown $type
	 * @param unknown $name
	 * @param unknown $ip
	 * @param unknown $data
	 */
	public function logAdmin($type, $name, $data){
		return $this->_call('log','admin',[
				'type'	=> $type,
				'name'	=> $name,
				'ip'	=> \yoka\Util::getIp(),
				'json'	=> json_encode($data, JSON_UNESCAPED_UNICODE)
		]);
	}
	/**
	 * 跟踪数据
	 * @param string $label 显示名称
	 * @param mixed $result 内容结果
	 * @param string $caller 默认为报错的位置，可选： full 全报错栈 | pre 前一个（用于错误统一处理函数内）| 其他自定义字符串
	 * @param string $url 链接
	 * @return null
	 * @access public
	 */
	public function debug($label, $result = '', $caller = '', $url = '')
	{
		if($caller == 'full'){
			$caller = debug_backtrace(5);
		}elseif($caller == 'pre'){
			$t = debug_backtrace(1);
			$caller = $t[1]['file'].':'.$t[1]['line'];
		}elseif($caller === ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}
		if(is_string($result) && strlen($result)>120 && strpos(' ', substr($result,0,120))===false){
			//超长且没有空格
			$result = chunk_split($result, 120, ' ');
		}
		if(is_string($result) && mb_detect_encoding($string, 'UTF-8') !== 'UTF-8') {
			$result = '非UTF-8编码，长度：' . strlen($result);
		}
		if(! $url){
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //TODO::未考虑 https 和 port
		}
		return $this->_call('log','debug',[
				'label'		=> label,
				'result'	=> result,
				'ip'		=> \yoka\Util::getIp(),
				'caller'	=> $caller,
				'url'		=> $url
		]);
	}

}