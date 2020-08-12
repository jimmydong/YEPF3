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
	public function logAdmin($type, $name, $ip, $data){
		return $this->_call('log','admin',[
				'type'	=> $type,
				'name'	=> $name,
				'ip'	=> $ip,
				'json'	=> json_encode($data, JSON_UNESCAPED_UNICODE)
		]);
	}

}