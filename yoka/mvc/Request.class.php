<?php
namespace yoka\mvc;

use \Exception;

class Request implements \Iterator{
	private static $_instance;
	
	/**
	 * 允许修改传入参数
	 */
	public $allowModify = false;
	/**
	 * 自动进行add_slashes
	 */
	public static $FLAG_MAGIC_QUOTES = true;
	/**
	 * 是否在YEPF框架中（YEPF框架已自动进行MagicQuote）
	 */
	public static $WITH_YEPF = true;
	/**
	 * for Iterator
	 */
	private $position = 0; 			//for iterator
	private $entity = [];			//for iterator
	
	/**
	 * 防注入(参见方法： checkSafe)
	 */
	private $getfilter = "'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	private $postfilter = "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	private $cookiefilter = "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	
	private function __construct() {
		$this->position = 0;		//for iterator
		foreach($_POST as $k=>$v){
			$this->entity[] = ['key'=>$k, 'val'=>$v];
		}
		foreach($_GET as $k=>$v){
			$this->entity[] = ['key'=>$k, 'val'=>$v];
		}
	}
	
	/**
	 * 单例
	 * @return \yoka\mvc\Request
	 */
	public static function getInstance() {
		if (!self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	public function get($index = '', $default='') {
        if($index === '' || $default === '')
            return $_GET;
		return isset($_GET[$index])? $_GET[$index]:$default;
	}

	public function post($index, $default='') {
		if($index == '' || $default == '')
			return $_POST;
		return isset($_POST[$index])? $_POST[$index]:$default;
	}
	
	/**
	 * 返回magic_quotes处理后的传入
	 * @param unknown $index
	 * @param string $default
	 */
	public function getRequest($index, $default='') {
		$re = isset($_REQUEST[$index])? $_REQUEST[$index]:$default;
		
		if(self::$FLAG_MAGIC_QUOTES){
			//需要安全处理
			if( self::$WITH_YEPF ) return $re; //YEPF已做处理
			elseif(get_magic_quotes_runtime() == true)return $re;	//系统magic_quote开启
			else return $this->_addslashesRecursive($re);
		}else{
			//不需要安全处理
			if( self::$WITH_YEPF) return $this->_stripslashesRecursive($re); //YEPF需要做反处理
			elseif(get_magic_quotes_runtime() == true)return $this->_stripslashesRecursive($re);
			else return $re;
		}
	}
	
	public function cookie($index) {
		return isset($_COOKIE[$index])? $_COOKIE[$index]:'';
	}
	
	/**
	 * 修改值
	 * @param array $array
	 */
	public function set(array $array){
		$this->allowModify = true;
		foreach ($array as $k=>$v) $this->$k = $v;
		$this->allowModify = false;
	}
	/**
	 * 别名函数
	 * @param array $array
	 */
	public function ModifyGet(array $array) {
		$this->set($array);
	}
	
	
	/**
	 * 用于判断参数是否传入
	 * (注意：不同于is_null() —— !isset() 和 null 都返回true)
	 * @param string $key
	 */
	public function isNull($key){
		if($key === '' || $key === '') return true;
		if(isset($_GET[$key]))return false;
		elseif(isset($_POST[$key]))return false;
		else return true;
	}
	public function notNull($key){
		if($this->isNull($key))return false;
		else return true;
	}
	
	/**
	 * 获取非magic_quotes状态的数据
	 * Enter description here ...
	 * @param unknown_type $key
	 * @param unknown_type $default
	 */
	public function getUnMagic($key, $default=''){
		$data = $this->getRequest($key, $default);
		if(self::$FLAG_MAGIC_QUOTES ){
			return $this->_stripslashesRecursive($data);
		}else{
			return $data;
		}
	}
	public function getNoMagic($key, $default=''){
		return $this->getUnMagic($key, $default);
	}
	public function unMagic($key, $default=''){
		return $this->getUnMagic($key, $default);
	}
	public function noMagic($key, $default=''){
		return $this->getUnMagic($key, $default);
	}
	public function _stripslashesRecursive($data){
		if(is_array($data)){
			foreach($data as $key=> $val){
				$re[$key] = self::_stripslashesRecursive($val);
			}
			return $re;
		}else return stripslashes($data);
	}
	public function _addslashesRecursive($data){
		if(is_array($data)){
			foreach($data as $key=> $val){
				$re[$key] = self::_addslashesRecursive($val);
			}
			return $re;
		}else return addslashes($data);
	}
	
	public function __get($key) {
		if(self::$FLAG_MAGIC_QUOTES === false)return $this->getUnMagic($key);
		return $this->getRequest($key);
	}

	public function __set($k, $v) {
		if ($this->allowModify)
			$this->$k = $v;
		else
			throw new Exception('不允许修改!');
		
	}
	
	/**
	 * 检查是否有攻击（SQL-injection, XSS）
	 * @param bool $die 发现有攻击时，是否立即停止
	 */
	static public function checkSafe($die = false){
		foreach($_GET as $key=>$value){self::_checkSafe($key,$value,$this->getfilter);}
		foreach($_POST as $key=>$value){self::_checkSafe($key,$value,$this->postfilter);}
		foreach($_COOKIE as $key=>$value){self::_checkSafe($key,$value,$this->cookiefilter);}
	}
	static protected function _checkSafe($key, $value, $filter){
		if(is_array($value))$value = implode($value);
		if (preg_match("/".$filter."/is", $value, $reg) == 1){
			\yoka\Log::customLog('hack_attack.log', implode(' | ', [
					date('Y-m-d H:i:s'),
					\yoka\Utility::getClientIp(),
					$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'],
					$_SERVER["REQUEST_METHOD"],
					$key,
					$value
			]));
			if(defined('IS_TEST') && IS_TEST)\yoka\Debug::log('Attack Found', $reg);
			if($die)die('Alert: potential damage - by YEPF/mvc/Request');
			else return false;
		}
		return true;
	}
	
	/* Iterator 方法 */
	public function current (){
		return $this->entity[$this->position]['val'];
	}
	public function key (){
		return $this->entity[$this->position]['key'];
	}
	public function next (){
		++$this->position;
	}
	public function rewind (){
		$this->position = 0;
	}
	public function valid (){
		return isset($this->entity[$this->position]);
	}
}
