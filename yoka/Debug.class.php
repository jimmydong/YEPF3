<?php
/**
 * @name Debug.class.php
 * @desc YEPF调试类,基于FirePHP
 * @by jimmy.dong@gmail.com
 * @createtime 2009-01-15 11:18
 * @updatetime 2009-03-30 15:26
 * @usage
 * 	//Debug开关打开
 *	Debug::start();
 *	Debug::stop(); Debug::restart();
 *	//注册shutdown函数用来Debug显示
 *	register_shutdown_function(array('Debug', 'show'));
 * @update by jimmy.dong@gmail.com
 *  增加db记录开关，用于记录数据库修改操作
 * 
 * 通过global.inc.php加载。或手工加载：

	//打开 FireBug 支持 （注意：仅FirePHP标志存在的情况下）
	if(\yoka\Debug::_firephp()){
		//开启ob函数
		ob_start();
		//Debug开关
		\yoka\Debug::start();
		//注册shutdown函数用来Debug显示 : 手工处理显示的场景，请设置： define('MANUAL_DEBUG_SHOW',true);
		if(!defined('MANUAL_DEBUG_SHOW') || MANUAL_DEBUG_SHOW != true) register_shutdown_function(array('\yoka\Debug', 'show'));
	}

 * @update by jimmy.dong@gmail.com
 * 增加页面Debug支持，通过 ?debug=page 或  Debug::debug_page = true 开启
 * 
 * @notice 
 * 留意代码中fastcgi_finish_request()会阻止register_shutdown_function注册的回调
 * 
 * 已知BUG：
 * 当多级数组 + 下级数组仅一个元素 + 元素的key为默认 + 元素的值为0或1 => Variable Viewer 会将 array('0'=>0) 错误显示为 0
 * eg: \yoka\Debug::log('bug',['test'=>[0]])
 */
namespace yoka;
use FirePHP;
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'firephp' . DIRECTORY_SEPARATOR . 'FirePHP.php');
class Debug
{
	//YOKA debug 分级实现		2012-02-01	zqx
	const YEPF_DEBUG_NONE 		= 'yoka';
	const YEPF_DEBUG_WARNING 	= 'yoka-inc';		
	const YEPF_DEBUG_STAT 		= 'yoka-inc2';
	const YEPF_DEBUG_TRACE 		= 'yoka-inc3';
	const YEPF_DEBUG_INFO 		= 'yoka-inc4';
		
	/**
	 * @desc Debug开关,默认为关闭
	 * @var bool
	 */
	static $open = false ;
	/**
	 * @desc Firephp是否开启
	 * @var bool
	 */
	static $firephp = 'suspense';
	/**
	 * @desc Debug类实例化对象
	 * @var bool
	 */
	static $instance = false;
	/**
	 * @desc 运行时间显示数组
	 * @var array
	 */
	static $time_table = array();
	/**
	 * @desc 用户自定义中间变量显示数组
	 * @var array
	 */
	static $log_table = array();
	/**
	 * @desc 数据库查询执行时间数组
	 * @var array
	 */
	static $db_table = array();
	static $db_log	 = false;			//记录数据库操作（insert/update/delete）到文件
	static $debug_log = false;			//记录调试信息到文件
	/** 日志表格式
	 * CREATE TABLE IF NOT EXISTS `debug_log` (
	 `id` bigint(20) NOT NULL,
	 `ip` varchar(200) NOT NULL,
	 `type` varchar(64) NOT NULL,
	 `label` varchar(200) NOT NULL,
	 `results` text NOT NULL,
	 `caller` varchar(200) NOT NULL,
	 `db` varchar(200) NOT NULL,
	 `time` float(10,6) NOT NULL,
	 `query` text NOT NULL,
	 `query_results` text NOT NULL,
	 `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	 `remark` varchar(200) NOT NULL
	 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	 ALTER TABLE `debug_log` ADD PRIMARY KEY (`id`);
	 ALTER TABLE `debug_log` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
	 */
	static $log_mysql = false;			//日志数据库配置 array('db'=>'default', 'master'=>'true', 'skip'=>['admin__logs'])
	static $db_log_mysql = false;		//记录到数据库中 
	static $debug_log_mysql = false;	//记录到数据库中。所需表结构见下：
	/*
	 数据库表结构
	 CREATE TABLE IF NOT EXISTS `debug_log` (
	 `id` bigint(20) NOT NULL,
	 `server` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
	 `ip` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
	 `type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
	 `db` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
	 `time` float(10,6) NOT NULL,
	 `query` text COLLATE utf8_unicode_ci NOT NULL,
	 `query_results` text COLLATE utf8_unicode_ci NOT NULL,
	 `label` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
	 `results` text COLLATE utf8_unicode_ci NOT NULL,
	 `caller` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
	 `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	 `remark` varchar(200) COLLATE utf8_unicode_ci NOT NULL
	 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	
	 ALTER TABLE `debug_log`
	 ADD PRIMARY KEY (`id`),
	 ADD KEY `server` (`server`,`create_time`),
	 ADD KEY `type` (`type`,`create_time`),
	 ADD KEY `ip` (`ip`,`create_time`),
	 ADD KEY `label` (`label`,`create_time`),
	 ADD KEY `create_time` (`create_time`);
	*/	
	
	/**
	 * @desc 缓存查询执行时间数组
	 */
	static $cache_table = array();
	/**
	 * @desc 表单方式的接口
	 */
	static $form_table = array();
	/**
	 * @desc ThriftClient调用
	 */
	static $thrift_table = array();
	/**
	 * @desc Template调用
	 */
	static $template_table = array();
	/**
	 * @desc 起始时间
	 */
	static $begin_time;
	/**
	 * @desc debug显示级别
	 */
	static $debug_level;
	/**
	 * 页面方式显示DebugBar
	 */
	static $debug_page = false;
	/**
	 * 返回Header
	 */
	static $fb_return = false;
	
	/**
	 * 默认chrome调试不显示DB信息
	 */
	static $chrome_force_db = false;
	
	/**
	 * @name __construct
	 * @desc 构造函数
	 */
	protected function __construct()
	{

	}
	/**
	 * 设置参数
	 * 常用： array('maxDepth'=>8, 'maxLength'=>2048);
	 */
	public static function setOptions($arr){
		$instance = FirePHP::getInstance(true);
		$instance->setOptions($arr);
	}
	/**
	 * @name start
	 * @desc 启动debug类
	 * @param $debug_level 调试级别
	 * @param $return 返回所有header为数组（需要手工维护header时，如：非fpm/wcgi环境）
	 * @return null
	 */
	public static function start($debug_level = null, $return = false)
	{
		if($return) self::$fb_return = $return;
		
		//初始化
		self::$open = true;
		self::$begin_time = microtime();
		self::$time_table = [['Description', 'Time', 'Caller']];
		self::$log_table = [['Label', 'Results', 'Caller']];
		self::$cache_table = [];
		self::$db_table = [];
		self::$thrift_table = [];
		
		if($debug_level){
			self::$debug_level = $debug_level;
		}else{
			//检测传递参数
			$req_level = '';
			if( isset($_REQUEST['debug']) && strpos($_REQUEST['debug'], YEPF_DEBUG_PASS) !== false ){
				switch($_REQUEST['debug'])
				{
					case self::YEPF_DEBUG_NONE:
					case self::YEPF_DEBUG_WARNING:
					case self::YEPF_DEBUG_STAT:
					case self::YEPF_DEBUG_TRACE:
					case self::YEPF_DEBUG_INFO:
						$req_level = $_REQUEST['debug'];
						break;
					default:
						$req_level = self::YEPF_DEBUG_NONE;
						break;
				}
			}elseif( YEPF_IS_DEBUG === true )
				$sys_level = self::YEPF_DEBUG_WARNING;
			else 
				$sys_level = YEPF_IS_DEBUG;
			
			self::$debug_level =$req_level ? $req_level : $sys_level; //传参优先
		}
		
		//设置为none,关闭所有输出信息
		if(self::$debug_level == self::YEPF_DEBUG_NONE)	error_reporting(0);
		
		$instance = FirePHP::getInstance(true);
		$instance->reset(); //支援非fpm/cgi模式
		
		if(!$return){
			//返回模式不处理异常
			$instance->registerErrorHandler(false);
			$instance->registerExceptionHandler();
			$instance->registerAssertionHandler(true, false);
		}
	}
	
	/**
	 * 关闭调试
	 * @return bool 返回修改之前的状态值
	 */
	public static function stop(){
		$old = self::$open;
		self::$open = false;
		return $old;
		
	}
	
	/**
	 * 重新开启调试
	 */
	public static function restart(){
		self::$open = true;
	}
	
	/**
	 * 配合 stop 使用，恢复到之前的设定值
	 * 
	 * eg: 
	 * $old = \yoka\Debug::stop();
	 * //do something ....
	 * \yoka\Debug::reset($old)
	 * 
	 * @param unknown $flag
	 */
	public static function reset($flag){
		self::$open = $flag;
	}
	
	/**
	 * 切换调试状态
	 */
	public static function toggle(){
		if(self::$open) self::$open = false;
		else self::$open = true;
	}
	
	/**
	 * 启动或关闭数据库日志(默认为关闭)
	 * 开启 - true
	 * 关闭 - false
	 */
	public static function log_db($flag){
		self::$db_log = $flag;
	}

	/**
	 * 启动或关闭调试信息日志(默认为关闭)
	 * 开启 - true
	 * 关闭 - false
	 */
	public static function log_debug($flag){
		self::$debug_log = $flag;
	}
	
	/**
	 * @name getTime
	 * @desc 获得从起始时间到目前为止所花费的时间
	 * @return int
	 */
	public static function getTime()
	{
    	list($pusec, $psec) = explode(" ", self::$begin_time);
    	list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec - (float)$pusec) + ((float)$sec - (float)$psec);
	}
	/**
	 * @name getInstance
	 * @desc 返回debug类的实例
	 * @return object
	 */
	public static function getInstance()
	{
		if(false === self::$instance)
		{
			self::$instance = new Debug();
		}
		return self::$instance;
	}
	/**
	 * @name log
	 * @desc 记录用户自定义变量
	 * @param string $label 自定义变量显示名称
	 * @param mixed $results 自定义变量结果
	 * @param string $caller 默认为报错的位置，可选： full 全报错栈 | pre 前一个（用于错误统一处理函数内）| 其他自定义字符串 
	 * @return null
	 * @access public
	 */
	public static function log($label, $results = 'Temporary Value', $caller = '')
	{
		if(false === self::$open || (defined('DEBUG_SHOW_LOG') && !DEBUG_SHOW_LOG))
		{
			return ;
		}
		if($caller == 'full'){
			$caller = debug_backtrace(5);
		}elseif($caller == 'pre'){
			$t = debug_backtrace(1);
			$caller = $t[1]['file'].':'.$t[1]['line'];
		}elseif($caller === ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}
// 		if(is_string($results) && strlen($results)>120 && strpos(' ', substr($results,0,120))===false){
// 			//超长且没有空格
// 			$results = chunk_split($results, 120, ' ');
// 		}
		if($results === 'Temporary Value'){
           array_push(self::$log_table, array('[临时调试]', $label, $caller));
        }else{
        	if(is_string($results) && mb_detect_encoding($string, 'UTF-8') !== 'UTF-8') {
        		$results = '非UTF-8编码，长度：' . strlen($results);
        	}
        	array_push(self::$log_table, array($label, $results, $caller));
        }
	}
	/**
	 * 即时写入日志文件（在无法正常输出Debug回调时使用）
	 * Enter description here ...
	 * @param string $label
	 * @param mixed $results
	 * @param string $caller
	 */
	public static function flog($label, $results = '', $caller = '')
	{
		if(!$results){
			$results = $label;
			$label = 'Debug:flog';
		}
		if(false === self::$open) return false;
		$string 	= 	"Debug::flog: " . addslashes($_SERVER['REQUEST_URI']);
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}
		$string		.=	"\nCalled in ". $caller;
		$string		.=	"\n[{$label}]" . var_export($results, true);
		$string		.=	"\n";
		$filename = "debug_" . date("Ymd") . ".log";
		
		Log::customLog($filename, $string);
		return true;
	}
	
	/**
	 * 即时写入日志数据库（self::$debug_log_mysql必须已有设置）
	 * @param string $label
	 * @param string $result
	 * @param string $caller
	 */
	public static function dlog($label, $result = '', $caller = '')
	{
		if(is_array($result)) $result = json_encode($result, JSON_UNESCAPED_UNICODE);
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}
		if(! is_array(self::$log_mysql)) {
		 	self::log('dlog Error','Not define Debug::$log_mysql, called by ' . $caller);
		 	return;
		}else{
		 	$sql = ''; $values = array();
		 	try{
		 		$db = \yoka\DB::getInstance(self::$log_mysql['db'], self::$log_mysql['master']);
		 		$sql = "INSERT INTO debug_log SET 
		 					`ip` = '".self::get_real_ip()."',
		 					`server` = '".$_SERVER["SERVER_ADDR"]."',
		 					`type` = 'dlog',
		 					`label` = '".addslashes($label)."',
		 					`results` = '".addslashes($result)."',
		 					`caller` = '{$caller}'";
		 		$db->query($sql);
		 	}catch(\Exception $e){
		 		//do nothing
		 		$filename = "debug_db_" . date("Ymd") . ".log";
		 		Log::customLog($filename, "[Debug Error] write to mysql_log fail. " . json_encode($values, JSON_UNESCAPED_UNICODE));
		 	}
		}
	}
	
	/**
	 * @name db
	 * @desc 记录数据库查询操作执行时间
	 * @param string $ip 数据库IP
	 * @param int $port 数据库端口
	 * @param string $sql 执行的SQL语句
	 * @param float $times 花费时间
	 * @param mixed $results 查询结果
	 * @return null
	 * @access public
	 */
	public static function db($ip, $database ,$sql, $times, $results)
	{
		if(false === self::$open || (defined('DEBUG_SHOW_DB') && !DEBUG_SHOW_DB))
		{
			return ;
		}
		if(is_string($ip) && strlen($ip)>32)$ip = substr($ip, 0, 30) . '..';
// 		if(is_string($sql) && strlen($sql)>120 && strpos(' ', substr($sql,0,120))===false){
// 			//超长且没有空格
// 			$sql = chunk_split($sql, 120, ' ');
// 		}
		if(is_string($results) && strlen($results)>256)$results = substr($results,0,256) . '...(length:'.strlen($results).')';
		array_push(self::$db_table, array($ip, $database, $times, $sql, $results));
	}
	
	/**
	 * 记录thrift调用情况（注意：ThriftClient类尚未加入YEPF）
	 * Enter description here ...
	 * @param unknown_type $service
	 * @param unknown_type $method
	 * @param unknown_type $args
	 * @param unknown_type $times
	 * @param unknown_type $result
	 */
	public static function thrift($service, $method, $args, $times, $results)
	{
		if(false === self::$open || (defined('DEBUG_SHOW_THRIFT') && !DEBUG_SHOW_THRIFT))
		{
			return ;
		}
		array_push(self::$thrift_table, array($service, $method, $args, $times, $results));		
	}
	/**
	 * 记录template调用情况
	 * Enter description here ...
	 * @param unknown_type $name
	 * @param unknown_type $times
	 * @param unknown_type $caller
	 */
	public static function template($name, $times, $caller)
	{
		if(false === self::$open || (defined('DEBUG_SHOW_TEMPLATE') && !DEBUG_SHOW_TEMPLATE))
		{
			return ;
		}
		array_push(self::$template_table, array($name, $times, $caller));		
	}
	/**
	 * @name cache
	 * @desc 缓存查询执行时间
	 * @param array $server 缓存服务器及端口列表
	 * @param string $key 缓存所使用的key
	 * @param float $times 花费时间
	 * @param mixed $results 查询结果
	 * @return null
	 * @access public
	 */
	public static function cache($server, $key, $times, $results, $method = null)
	{
		if(false === self::$open || (defined('DEBUG_SHOW_CACHE') && !DEBUG_SHOW_CACHE))
		{
			return ;
		}
		if(is_string($results) && strlen($results)>256)$results = substr($results,0,256) . '...(length:'.strlen($results).')';
		array_push(self::$cache_table, array($server ,$key, $times, $results, $method));
	}
	/**
	 * @name time
	 * @desc 记录程序执行时间
	 * @param string $desc 描述
	 * @param mixed $results 结果
	 * @return null
	 * @access public
	 */
	public static function time($desc='', $caller='')
	{
		if(false === self::$open || (defined('DEBUG_SHOW_TIME') && !DEBUG_SHOW_TIME))
		{
			return ;
		}
		if($desc == '')$desc = 'run-time';
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}elseif($caller == 'full'){
			$caller = debug_backtrace(5);
		}
		array_push(self::$time_table, array($desc, self::getTime(), $caller));
	}
	/**
	 * 记录form表单的方式接口请求
	 * @param label 说明标签
	 * @param action 表单的请求地址
	 * @param params 表单的数据项
	 * @param caller 处理程序
	 */
	public static function form($label, $action, $params = array(),$method='post', $times = 0, $results = '', $caller = __FILE__)
	{
		if (false === self::$open || (defined('DEBUG_SHOW_FORM') && !DEBUG_SHOW_FORM))
		{
			return ;
		}
		$form_html = '<html><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /><title>Debug Form</title></head><body><form action="'.$action.'" method="'.$method.'">';
		if ($params)
		{
			foreach ($params as $k => $v)
			{
				$form_html .= $k.': <input type="text" name="'.$k.'" value="'.$v.'" /><br/>';
			}
		}
		$form_html .= '<input type="submit" value="submit" /></form></body></html>';
		array_push(self::$form_table, array($label, $form_html, $times, $results, $caller));
	}
	
	/**
	 * 判断客户端
	 */
	public static function _firephp(){
		if(preg_match('/FirePHP/i',$_SERVER['HTTP_USER_AGENT'])){
			return 'FirePHP';
		}if(preg_match('/PageBar/i',$_SERVER['HTTP_USER_AGENT'])){
			return 'PageBar';
		}elseif($_SERVER['HTTP_X_YEPF'] != ''){
			if(preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/Chrome/i', $_SERVER['HTTP_X_YEPF'])){
				return 'chrome';
			}else{
				return 'firefox';
			}
		}else{
			return false;
		}
	}
	/**
	 * @name fb
	 * @desc 调用FirePHP函数
	 * @return mixed
	 * @access public
	 */
	public static function fb($array, $type)
	{
		if(self::$open === false)return false;
		
		if(self::$fb_return){
			//返回header。调用时需要用fb截取
			$instance = FirePHP::getInstance(true);
			$instance->fb_return = true;
			$args = func_get_args();
			call_user_func_array(array($instance,'fb'),$args);
			return;
		}
		
		//判断FirePHP是否开启 by jimmy.dong@gmail.com
		if(self::$firephp == 'suspense'){
			self::$firephp = self::_firephp();
		}	
		if(self::$firephp === false)return false;
		
		if(self::$firephp == 'FirePHP'){
			$instance = FirePHP::getInstance(true);
			$args = func_get_args();
			return call_user_func_array(array($instance,'fb'),$args);
		}elseif(self::$firephp == 'PageBar'){
			//nothing
		}else{
			//FireDebug停止维护，启用ChromeLogger模式
			$title = $array[0];
			$keys = $array[1][0];
			$data = array();
			for($i=1;$i<count($array[1]);$i++){
				$tmp = array();
				foreach($keys as $j=>$key){
					if(self::$firephp == 'chrome'){
						//解决 array 不显示。长字符串被截断问题仍然无解。
						if(is_array($array[1][$i][$j]) || is_object($array[1][$i][$j])){
							$array[1][$i][$j] = str_replace("\n",'',var_export($array[1][$i][$j], true));
						}
					}
					$tmp[$key] = $array[1][$i][$j];
				}
				if(self::$firephp == 'chrome'){
					//去除索引，节省显示空间
					$index = array_shift($tmp);
					$data[$index] = $tmp;
				}else{
					$data[] = $tmp;
				}
			}
			if(count($data) == 0) return;
			if(self::$firephp == 'chrome')\yoka\ChromePhp::groupCollapsed($title);
			\yoka\ChromePhp::table($data);
			if(self::$firephp == 'chrome')\yoka\ChromePhp::groupEnd();
		}
	}
	/**
	 * @name show
	 * @desc 显示调试信息
	 * @todo 目前只实现了在FirePHP中显示结果.NON/WARNING/STAT状态不记录LOG日志
	 * @return null
	 * @access public
	 */
	public static function show()
	{
		global $YOKA, $TEMPLATE, $CFG;

		/*-----------【Debug::$open无关】 记录数据库改变情况到日志文件 ----------*/
		if(self::$db_log){
			$string = '';
			if(!empty(self::$db_table))
			{
				foreach (self::$db_table as $v)
				{
					if(is_array($v[4])) $result = substr(var_export($v[4], true), 0, 32); //限制长度
					else $result = $v[4];
					if(is_string($v[3]) && preg_match('/insert|update|delete/i',$v[3])) $string .= "|----  ".$v[1]."  ".$v[2]."  ".$v[3]."  ". $result ."  ----|\n";
				}
				if($string){
					$string = 	"Request: " . addslashes($_SERVER['REQUEST_URI']) . "\n" . $string;
					$filename = "debug_db_" . date("Ymd") . ".log";
					Log::customLog($filename, $string);
				}
			}
		}
				
		/*------------------【Debug::$open关闭则不执行】 -----------------------*/
		if(self::$open === false)return;
		else self::$open == false; //防止再次输出
		
		//页面调试开启方法： 1，设置$debug_page； 2，传入参数(仅限测试)； 3，通过插件修改头信息(Ajax除外)
		if(self::$debug_page || (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'page' && defined('IS_TEST') && IS_TEST) || (isset($_SERVER['HTTP_X_YEPF']) && $_SERVER['HTTP_X_YEPF'] == 'PageBar') && !preg_match('/json|javascript/i', $_SERVER['HTTP_ACCEPT'])){
			//页面方式调试
			if(class_exists('\DebugBar\DebugBar')){
				$debugbar = new \yoka\DebugBar();
				$debugbarRenderer = $debugbar->getJavascriptRenderer("/Resources");
				for($i=1;$i<count(self::$log_table);$i++){
					$t = self::$log_table[$i];
					$debugbar->getCollector('Costom Log')->add($t[0], $t[1], $t[2]);
				}
				for($i=0;$i<count(self::$db_table);$i++){
					$t = self::$db_table[$i];
					$debugbar->getCollector('DB Log')->add($t[0].' ['.$t[1].']', $t[4], $t[3].', time:'.$t[2]);
				}
				for($i=0;$i<count(self::$cache_table);$i++){
					$t = self::$cache_table[$i];
					$debugbar->getCollector('Cache Log')->add($t[0].' ['.$t[3].'] ', $t[4], 'key:'.$t[1].', time:'.$t[2]);
				}
				for($i=1;$i<count(self::$time_table);$i++){
					$t = self::$time_table[$i];
					$debugbar->getCollector('time')->add($t[0], $t[1], $t[2]);
				}
				echo '
					<link rel="stylesheet" type="text/css" href="//cdn.yirenjiankang.org/Resources/vendor/font-awesome/css/font-awesome.min.css">
					<link rel="stylesheet" type="text/css" href="//cdn.yirenjiankang.org/Resources/vendor/highlightjs/styles/github.css">
					<link rel="stylesheet" type="text/css" href="//cdn.yirenjiankang.org/Resources/debugbar.css">
					<link rel="stylesheet" type="text/css" href="//cdn.yirenjiankang.org/Resources/widgets.css">
					<link rel="stylesheet" type="text/css" href="//cdn.yirenjiankang.org/Resources/openhandler.css">
					<script type="text/javascript" src="//cdn.yirenjiankang.org/Resources/vendor/jquery/dist/jquery.min.js"></script>
					<script type="text/javascript" src="//cdn.yirenjiankang.org/Resources/vendor/highlightjs/highlight.pack.js"></script>
					<script type="text/javascript" src="//cdn.yirenjiankang.org/Resources/debugbar.js"></script>
					<script type="text/javascript" src="//cdn.yirenjiankang.org/Resources/widgets.js"></script>
					<script type="text/javascript" src="//cdn.yirenjiankang.org/Resources/openhandler.js"></script>
					<script type="text/javascript">jQuery.noConflict(true);</script>
				';
				echo $debugbarRenderer->render();
			}
		}else try{
			//页面执行时间
			switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
					break;
				case self::YEPF_DEBUG_WARNING:
				case self::YEPF_DEBUG_STAT:
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					self::fb(array('This Page Spend Times ' . self::getTime(), self::$time_table), FirePHP::TABLE );
					break;
				default: 
					break;
			}
			//用户记录变量
			switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
					break;
				case self::YEPF_DEBUG_WARNING:
				case self::YEPF_DEBUG_STAT:
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					self::fb(array('Custom Log Object '. count(self::$log_table), self::$log_table), FirePHP::TABLE );
					break;
				default: 
					break;
			}
			//数据库执行信息  (chrome限制头大小)
			if(self::_firephp() != 'chrome' || self::$chrome_force_db)switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
					break;
				case self::YEPF_DEBUG_STAT:
					if(count(self::$db_table) > 0)
					{
						$i = 0 ;
						$db_total_times = 0 ;
						foreach (self::$db_table as $v)
						{
							$db_total_times += $v[2];
							$i++;
						}
						self::fb($i . ' SQL queries took '.$db_total_times.' seconds', FirePHP::INFO );
					}
					break;
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					if(count(self::$db_table) > 0)
					{
						$i = 0 ;
						$db_total_times = 0 ;
						foreach (self::$db_table as $v)
						{
							$db_total_times += $v[2];
							$i++;
						}
						array_unshift(self::$db_table, array('IP', 'Database', 'Time', 'SQL Statement','Results'));
						self::fb(array($i . ' SQL queries took '.$db_total_times.' seconds', self::$db_table), FirePHP::TABLE );
					}
				default: 
					break;
			}
			//Cache执行信息
			if(self::_firephp() != 'chrome')switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
					break;
				case self::YEPF_DEBUG_STAT:
					if(count(self::$cache_table) > 0)
					{
						$i = 0 ;
						$cache_total_times = 0 ;
						foreach (self::$cache_table as $v)
						{
							$cache_total_times += $v[2];
							$i++;
						}
						self::fb($i.' Cache queries took '.$cache_total_times.' seconds', FirePHP::INFO );
					}
					break;
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					if(count(self::$cache_table) > 0)
					{
						$i = 0 ;
						$cache_total_times = 0;
						foreach (self::$cache_table as $v)
						{
							$cache_total_times += $v[2];
							$i++;
						}
						array_unshift(self::$cache_table, array('Server', 'Cache Key', 'Time', 'Method', 'Results'));
						self::fb(array($i.' Cache queries took '.$cache_total_times.' seconds', self::$cache_table), FirePHP::TABLE );
					}
				default: 
					break;
			}
			//Thrift执行时间
			if(self::_firephp() != 'chrome')switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
					break;
				case self::YEPF_DEBUG_STAT:
					if(count(self::$thrift_table) > 0)
					{
						$i = 0 ;
						$thrift_total_times = 0 ;
						foreach (self::$thrift_table as $v)
						{
							$thrift_total_times += $v[3];
							$i++;
						}
						self::fb($i . ' thrift took '.$thrift_total_times.' seconds', FirePHP::INFO );
					}
					break;
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					if(count(self::$thrift_table) > 0)
					{
						$i = 0 ;
						$thrift_total_times = 0 ;
						$thrift_service = array();
						foreach (self::$thrift_table as $v)
						{
							$thrift_total_times += $v[3];
							$i++;
						}
						array_unshift(self::$thrift_table, array('Service', 'Methof', 'Args', 'Times', 'Results'));
						self::fb(array($i . ' thrift took '.$thrift_total_times.' seconds', self::$thrift_table), FirePHP::TABLE );
					}
				default: 
					break;
			}
			//Template执行时间
			/*
			switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
					break;
				case self::YEPF_DEBUG_STAT:
					if(count(self::$template_table) > 0)
					{
						$i = 0 ;
						$template_total_times = 0 ;
						foreach (self::$template_table as $v)
						{
							$template_total_times += $v[3];
							$i++;
						}
						self::fb($i . ' template took '.$template_total_times.' seconds', FirePHP::INFO );
					}
					break;
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					if(count(self::$template_table) > 0)
					{
						$i = 0 ;
						$template_total_times = 0 ;
						$template_service = array();
						foreach (self::$template_table as $v)
						{
							$template_total_times += $v[3];
							$i++;
						}
						array_unshift(self::$template_table, array('Name', 'Times', 'Caller'));
						self::fb(array($i . ' template took '.$template_total_times.' seconds', self::$template_table), FirePHP::TABLE );
					}
				default: 
					break;
			}
			//Form执行时间
			switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
					break;
				case self::YEPF_DEBUG_STAT:
					if(count(self::$form_table) > 0)
					{
						$i = 0;
						$form_total_times = 0;
						foreach (self::$form_table as $v)
						{
							$form_total_times += $v[2];
							$i++;
						}
						self::fb( $i.' Form action request took '.$form_total_times.' seconds', FirePHP::INFO);
					}
					break;
				case self::YEPF_DEBUG_TRACE:
				case self::YEPF_DEBUG_INFO:
					if(self::$form_table)
					{
						$i = 0;
						$form_total_times = 0;
						foreach (self::$form_table as $v)
						{
							$form_total_times += $v[2];
							$i++;
						}
						array_unshift(self::$form_table, array('Label', 'FormHtml', 'Times', 'Results', 'Caller'));
						self::fb(array($i.' Form action request took '.$form_total_times.' seconds', self::$form_table), FirePHP::TABLE );
					}
				default: 
					break;
			}
			//函数及变量
			switch(self::$debug_level)
			{
				case self::YEPF_DEBUG_NONE:
				case self::YEPF_DEBUG_WARNING:
				case self::YEPF_DEBUG_STAT:
				case self::YEPF_DEBUG_TRACE:
					break;
				case self::YEPF_DEBUG_INFO:
					//自定义函数
					$functions = get_defined_functions();
					//定义的常量
					$constants = get_defined_constants(true);
					$sessions = isset($_SESSION) ? $_SESSION : array();
					self::fb(array('Utility Variables',
							array(
									array('name', 'values'),
									//array('YOKA-ENV.ini',\yoka\Conf::$ENV),
									array('GET Variables', $_GET),
									array('POST Variables', $_POST),
									array('SESSION Variables', $sessions),
									//array('Custom Defined Functions', $functions['user']),
									//array('Include Files', get_included_files()),
									array('Defined Constants', $constants['user']),
									//array('SERVER Variables', $_SERVER),
							)
					), FirePHP::TABLE );
				default: 
					break;
			}
			*/
		}catch(\Exception $e){
			//防止报错信息，暂无进一步处理
		}

		/*---------记录调试信息至日志文件中------------*/
		if(self::$debug_log){
			if(false !== self::$open &&(count(self::$log_table) > 1 || count(self::$time_table) > 1))
			{
				if(isset($_SERVER['TERM']))
				{
					$string = "PWD：" . $_SERVER['PWD'] . "\n";
					$string .= "SCRIPT_NAME：" . $_SERVER['SCRIPT_NAME'] . "\n";
					$string .= "ARGV：" . var_export($_SERVER['argv'], true) . "\n";
				}else
				{
					$string = "HTTP_HOST：" . $_SERVER['HTTP_HOST'] . "\n";
					$string .= "SCRIPT_NAME：" . $_SERVER['SCRIPT_NAME'] . "\n";
					$string .= "QUERY_STRING：" . $_SERVER['QUERY_STRING'] . "\n";
				}
				$string .= 'This Page Spend Times：' . self::getTime() . "\n";
				array_shift(self::$log_table);
				array_shift(self::$time_table);
				if(!empty(self::$time_table))
				{
					$string .= "\n";
					foreach (self::$time_table as $v)
					{
						$string .= "|--  ".$v[0]."  ".$v[1]."  ".$v[2]."  --|\n";
					}
				}
				if(!empty(self::$log_table) && self::$debug_level != YEPF_DEBUG_NONE && self::$debug_level != YEPF_DEBUG_WARNING && self::$debug_level != YEPF_DEBUG_STAT)
				{
					$string .= "\n";
					foreach (self::$log_table as $v)
					{
						$string .= "|----  ".$v[0]."  ".$v[2]."  ----|\n";
						$string .= var_export($v[1], true) . "\n";
					}
				}
				$filename = "debug_" . date("Ymd") . ".log";
				Log::customLog($filename, $string);
			}
		}
		
		/*---------记录调试信息至日志数据库中------------*/
		if(is_array(self::$log_mysql)){
			$sql = ''; $values = array();
			try{
				$db = \yoka\DB::getInstance(self::$log_mysql['db'], self::$log_mysql['master']);
				if($db){
				    //准备SQL数据
				    if(self::$db_log_mysql){
				        foreach (self::$db_table as $v){
    						if(! preg_match('/insert|update|delete/i',$v[3])) continue; //只记录数据变动
    						if(preg_match('/^INSERT INTO debug_log SET/i',$v[3])) continue; //dlog不做重复记录
    						if(preg_match('/^INSERT INTO `admin__logs` SET/i',$v[3])) continue; //admin__logs不做重复记录
    						if(self::$log_mysql['skip']){
    						    if(is_array(self::$log_mysql['skip'])){
    						        $find_flag = false;
    						        foreach(self::$log_mysql['skip'] as $skip){
    						            if(strpos($v[3], $skip) !== false){
    						                $find_flag = true;
    						                break;
    						            }
    						        }
    						        if($find_flag) continue;
    						    }else{
    						        if(strpos($v[3], self::$log_mysql['skip']) !== false) continue;
    						    }
    						}
    						$values[] = "('".self::get_real_ip()."', '".$_SERVER["SERVER_ADDR"]."', 'db', '', '', '".addslashes($_SERVER["REQUEST_URI"])."', '" .addslashes($v[0]). ":" .addslashes($v[1]). "', '" .addslashes($v[2]). "', '" .addslashes($v[3]). "','" .addslashes(var_export($v[4], true)). "')";
    					}
				    }
				    //准备调试数据
				    if(self::$debug_log_mysql){
				        foreach (self::$log_table as $v){
    						$values[] = "('".self::get_real_ip()."','".$_SERVER["SERVER_ADDR"]."','log','".addslashes($v[0])."','".addslashes(var_export($v[1], true))."','".addslashes($v[2])."','','','','')";
    					}
				    }
				    //写入日志库
					if($values){
						$sql = "INSERT INTO debug_log (`ip`,`server`,`type`,`label`,`results`,`caller`,`db`,`time`,`query`,`query_results`) VALUES " . implode(',', $values);
						$db->query($sql);
					}
				}
			}catch(\Exception $e){
				$filename = "debug_db_" . date("Ymd") . ".log";
				Log::customLog($filename, "[Debug Error] write to mysql_log fail. " . json_encode($values, JSON_UNESCAPED_UNICODE));
			}
		}
	}
	/**
	 * 获取真实IP
	 * @return string|unknown
	 */
	public static function get_real_ip() {
		if ($HTTP_SERVER_VARS[ "HTTP_X_FORWARDED_FOR"]){
			$ip = $HTTP_SERVER_VARS[ "HTTP_X_FORWARDED_FOR"];
		}elseif ($HTTP_SERVER_VARS[ "HTTP_CLIENT_IP"]){
			$ip = $HTTP_SERVER_VARS[ "HTTP_CLIENT_IP"];
		}elseif ($HTTP_SERVER_VARS[ "REMOTE_ADDR"]){
			$ip = $HTTP_SERVER_VARS[ "REMOTE_ADDR"];
		}elseif (getenv( "HTTP_X_FORWARDED_FOR")){
			$ip = getenv( "HTTP_X_FORWARDED_FOR");
		}elseif (getenv( "HTTP_CLIENT_IP")){
			$ip = getenv( "HTTP_CLIENT_IP");
		}elseif (getenv( "REMOTE_ADDR")){
			$ip = getenv( "REMOTE_ADDR");
		}else{
			$ip = "Unknown";
		}
		return $ip;
	}
}
