<?php
/**
 * @name Debug.class.php
 * @desc YEPF调试类,基于FirePHP
 * @author 曹晓冬
 * @update by jimmy.dong@gmail.com
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
	static $db_log	 = false;		//记录数据库操作（insert/update/delete）到文件
	static $debug_log = false;		//记录调试信息到文件
	/**
	 * @desc 缓存查询执行时间数组
	 * @var array
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
	 * @var int
	 */
	static $begin_time;
	/**
	 * @desc debug显示级别
	 * @var string
	 */
	static $debug_level;
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
	static public function setOptions($arr){
		$instance = FirePHP::getInstance(true);
		$instance->setOptions($arr);
	}
	/**
	 * @name start
	 * @desc 启动debug类
	 * @return null
	 */
	static public function start()
	{
		self::$open = true;
		self::$begin_time = microtime();
		self::$time_table = array(array('Description', 'Time', 'Caller'));
		self::$log_table = array(array('Label', 'Results', 'Caller'));
		
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
		
		//设置为none,关闭所有输出信息
		if(self::$debug_level == self::YEPF_DEBUG_NONE)	error_reporting(0);
		
		$instance = FirePHP::getInstance(true);
		$instance->registerErrorHandler(false);
		$instance->registerExceptionHandler();
		$instance->registerAssertionHandler(true, false);
	}
	
	static public function stop(){
		self::$open = false;
	}
	static public function restart(){
		self::$open = true;
	}
	
	/**
	 * 启动或关闭数据库日志(默认为关闭)
	 * 开启 - true
	 * 关闭 - false
	 */
	static public function log_db($flag){
		self::$db_log = $flag;
	}

	/**
	 * 启动或关闭调试信息日志(默认为关闭)
	 * 开启 - true
	 * 关闭 - false
	 */
	static public function log_debug($flag){
		self::$debug_log = $flag;
	}
	
	/**
	 * @name getTime
	 * @desc 获得从起始时间到目前为止所花费的时间
	 * @return int
	 */
	static public function getTime()
	{
		if(false === self::$open)
		{
			return ;
		}
    	list($pusec, $psec) = explode(" ", self::$begin_time);
    	list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec - (float)$pusec) + ((float)$sec - (float)$psec);
	}
	/**
	 * @name getInstance
	 * @desc 返回debug类的实例
	 * @return object
	 */
	static public function getInstance()
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
	 * @param string $callfile 调用记录用户自定义变量的文件名
	 * @return null
	 * @access public
	 */
	static public function log($label, $results = 'Temporary Value', $caller = '')
	{
		if(false === self::$open || (defined('DEBUG_SHOW_LOG') && !DEBUG_SHOW_LOG))
		{
			return ;
		}
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
		}elseif($caller == 'full'){
			$caller = debug_backtrace(5);
		}
		if($results == 'Temporary Value'){
           array_push(self::$log_table, array('[临时调试]', $label, $caller));
        }else array_push(self::$log_table, array($label, $results, $caller));
	}
	/**
	 * 即时写入日志文件（在无法正常输出Debug回调时使用）
	 * Enter description here ...
	 * @param unknown_type $label
	 * @param unknown_type $results
	 * @param unknown_type $caller
	 */
	static public function flog($label, $results = '', $caller = '')
	{
		if(!$results){
			$results = $label;
			$label = 'Debug:flog';
		}
		if(false === self::$open) return false;
		$string 	= 	"Debug::flog: ".$_SERVER['REQUEST_URI'];
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0][file].':'.$t[0][line];
		}
		$string		.=	"\nCalled in ". $caller;
		$string		.=	"\n[{$label}]" . var_export($results, true);
		$string		.=	"\n";
		$filename = "debug_" . date("Ymd") . ".log";
		
		Log::customLog($filename, $string);
		return true;
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
	static public function db($ip, $database ,$sql, $times, $results)
	{
		if(false === self::$open || (defined('DEBUG_SHOW_DB') && !DEBUG_SHOW_DB))
		{
			return ;
		}
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
	static public function thrift($service, $method, $args, $times, $results)
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
	static public function template($name, $times, $caller)
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
	static public function cache($server, $key, $times, $results, $method = null)
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
	static public function time($desc='', $caller='')
	{
		if(false === self::$open || (defined('DEBUG_SHOW_TIME') && !DEBUG_SHOW_TIME))
		{
			return ;
		}
		if($desc == '')$desc = 'run-time';
		if($caller == ''){
			$t = debug_backtrace(1);
			$caller = $t[0][file].':'.$t[0][line];
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
	static public function form($label, $action, $params = array(),$method='post', $times = 0, $results = '', $caller = __FILE__)
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
	 * @name fb
	 * @desc 调用FirePHP函数
	 * @return mixed
	 * @access public
	 */
	static public function fb()
	{
		if(self::$open === false)return false;
		
		//判断FirePHP是否开启 by jimmy.dong@gmail.com
		if(self::$firephp == 'suspense'){
			if(preg_match('/FirePHP/i',$_SERVER['HTTP_USER_AGENT']))self::$firephp = true;
			else self::$firephp = false;
		}	
		if(self::$firephp === false)return false;
		
		$instance = FirePHP::getInstance(true);
		$args = func_get_args();
		return call_user_func_array(array($instance,'fb'),$args);
	}
	/**
	 * @name show
	 * @desc 显示调试信息
	 * @todo 目前只实现了在FirePHP中显示结果.NON/WARNING/STAT状态不记录LOG日志
	 * @return null
	 * @access public
	 */
	static public function show()
	{
		global $YOKA, $TEMPLATE, $CFG;

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
				self::fb(array('Custom Log Object', self::$log_table), FirePHP::TABLE );
				break;
			default: 
				break;
		}
		//数据库执行时间
		switch(self::$debug_level)
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
		//Cache执行时间
		switch(self::$debug_level)
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
					array_unshift(self::$cache_table, array('Server', 'Cache Key', 'Time','Method','Results'));
					self::fb(array($i.' Cache queries took '.$cache_total_times.' seconds', self::$cache_table), FirePHP::TABLE );
				}
			default: 
				break;
		}
		//Thrift执行时间
/*
		switch(self::$debug_level)
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
		}*/
		//Template执行时间
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
		
		/*---------记录数据库改变情况-------------------------*/
		if(self::$db_log){
			$string = '';
			if(!empty(self::$db_table))
			{
				foreach (self::$db_table as $v)
				{
					if(preg_match('/insert|update|delete/i',$v[3])) $string .= "|----  ".$v[1]."  ".$v[2]."  ".$v[3]."  ".$v[4]."  ----|\n";
				}
				if($string){
					$t = debug_backtrace(1);
					$caller = $t[0][file].':'.$t[0][line];
					$string = 	"Request: " . $_SERVER['REQUEST_URI'] . "\nCalled in ". $caller . "\n" . $string;
					$filename = "debug_db_" . date("Ymd") . ".log";
					Log::customLog($filename, $string);
				}
			}				
		}
		

		/*---------记录用户定制调试信息至日志文件中------------*/
		if(self::$debug_log)
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
}

