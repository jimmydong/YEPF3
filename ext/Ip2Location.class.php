<?php
/**
 * Adapted by jimmy.dong@gmail.com 2012.12.13
 *  �����ܳ� 40000 ����
 *  ÿ����/γ�� Լ�� 111.11����
 *  ÿ���ﾭ��/γ�� Լ�� 0.009
 */
namespace ext;
class Ip2Location{
	protected $errors = array();
	protected $service = 'api.ipinfodb.com';
	protected $version = 'v3';
	protected $apiKey = '3732cda528b75d9b04882b858ac27b37db1b7b43420e7653de382bfbfa77e805';

	public function __construct(){}

	public function __destruct(){}

	public function setKey($key){
		if(!empty($key)) $this->apiKey = $key;
	}

	public function getError(){
		return implode("\n", $this->errors);
	}

	public function getCountry($host){
		return $this->getResult($host, 'ip-country');
	}
	
	public function get($host){
		$t= $this->getResult($host, 'ip-city');
		return array('lat'=>$t['latitude'], 'lng'=>$t['longitude']);
	}

	public function getCity($host){
		return $this->getResult($host, 'ip-city');
	}

	private function getResult($host, $name){
		$ip = @gethostbyname($host);
		if(1 || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){ //��ʱ������
			$url = 'http://' . $this->service . '/' . $this->version . '/' . $name . '/?key=' . $this->apiKey . '&ip=' . $ip . '&format=xml';
			$xml = file_get_contents($url);

			if (get_magic_quotes_runtime()){
				$xml = stripslashes($xml);
			}

			try{
				$response = @new \SimpleXMLElement($xml);

				foreach($response as $field=>$value){
					$result[(string)$field] = (string)$value;
				}

				return $result;
			}
			catch(Exception $e){
				$this->errors[] = $e->getMessage();
				return;
			}
		}else{
			$this->errors[] = '"' . $host . '" is not a valid IP address or hostname.';
		}
		return;
	}
}
?>