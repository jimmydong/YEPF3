<?
/**
 * YEPF3.0框架代码(2.0版本改进)
 * @name global.inc.php
 * @desc 通用文件包
 * @author jimmy.dong@gmail.com
 * @createtime 2014-09-07
 * 
 * 【 新版本变化】
 *  全局配置格式改变，使用 /WORK/CONF/WORK-ENV-3.0.ini
 *  取消core分类，公用类在 \yoka下， 工具类在 \ext下
 *  取消_AUTOLOAD，合并到 _LOCAL
 *  精简全局函数，转移至 \yoka\Utililty下
 *  合并spring的MVC，参见DEMO
 *  增加对多站点支持
 *  Smarty类升级，保持兼容
 *  DB类升级为PDO，部分函数（update,delete）需通过参数指定兼容
 *  memcache类升级，保持兼容
 *  Debug类升级，增加stop, start, log_db
 *  
 *  原有项目向YEPF3迁移建议：
	1，确认PHP版本高于5.3
	2，部署/YOKA/CONF/YOKA-ENV-3.0.php （可使用 demo/DocumentRoot/tools/ENV2to3.php转换）
	3，搜索代码： \Debug , Debug, \DB, DB, \Template, Template, \Cache, Cache 等原core定义类，
		页面上部对应添加： use yoka\Debug等，代码中去除\。
	4，修改_LOCAL/local.inc.php中YEPF定义路径为_YEPF3.0
	5，调试运行，针对出现问题参照demo/init.php, demo/_LOCAL/local.inc.php进行修改
 **/
if(PHP_VERSION < '5.3.0')
{
	echo 'PHP VERSION MUST > 5.3.x';
	exit;
}
define('YEPF_VERSION','3.0');

if(!defined('YEPF_PATH'))define('YEPF_PATH', dirname(__FILE__));

//默认将显示错误关闭
ini_set('display_errors', false);
//默认将读外部文件的自动转义关闭
ini_set("magic_quotes_runtime", 0);
//定义开始时间常量
define("YEPF_BEGIN_TIME",microtime());
//设置默认时区
date_default_timezone_set('PRC');
include YEPF_PATH.'/function.inc.php';
//默认自动转义,可能会对html及其它正则带来影响
if(!get_magic_quotes_gpc() && (!defined('YEPF_FORCE_CLOSE_ADDSLASHES') || YEPF_FORCE_CLOSE_ADDSLASHES !== true))
{
	foreach (array('_REQUEST', '_GET', '_POST', '_FILES', '_COOKIE') as $_v)
	{
		$$_v = yaddslashes($$_v );
	}
}
include YEPF_PATH.'/const.inc.php';
class YEPFCore {
    public static function registerAutoload($class = 'YEPFCore') {
        spl_autoload_register(array($class, 'autoload'));
    }

    public static function unregisterAutoload($class) {
    	spl_autoload_unregister(array($class, 'autoload'));
    }

	public static function my_callback($match){
		return DIRECTORY_SEPARATOR. $match[0];
	}
				
    public static function autoload($class_name) {
		$class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
		if (strpos($class_name, 'ext'.DIRECTORY_SEPARATOR) === 0 || strpos($class_name, 'yoka'.DIRECTORY_SEPARATOR) === 0)
		{
			return include YEPF_PATH . DIRECTORY_SEPARATOR . $class_name.'.class.php';
		}
		elseif(defined('CUSTOM_CLASS_PATH'))
        {
      		/**
      		 * update by jimmy.dong@gmail.com
      		 * 支援命名空间, 支援驼峰规则
      		 * 注意： 目录需按首字母大写
      		 */
			$class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        	$class_path = getCustomConstants('CUSTOM_CLASS_PATH') . DIRECTORY_SEPARATOR . $class_name.'.class.php';
        	$class_path2 = getCustomConstants('CUSTOM_CLASS_PATH') . DIRECTORY_SEPARATOR . $class_name.'.lib.php';
        	if(file_exists($class_path)) {
        		return include_once($class_path);
        	}elseif(file_exists($class_path2)){
        		return include_once($class_path2);
        	}else{
        		//支援将驼峰转变为目录结构
        		$lastdspos = strripos($class_name, DIRECTORY_SEPARATOR);
        		if(false !== $lastpos){
	        		$prepath = substr($class_name, 0, $lastdspos+1); 
		      		$rlname = substr($class_name, $lastdspos+1);
				}else{
					$prepath = '';
					$rlname = $class_name;
				}
        		$result = preg_replace_callback('/[A-Z][^A-Z]+/','YEPFCore::my_callback',substr($rlname,1));
				$class_path = getCustomConstants('CUSTOM_CLASS_PATH') . DIRECTORY_SEPARATOR . $prepath . substr($rlname,0,1) . $result .'.class.php';
				if(file_exists($class_path))return include_once($class_path);
        	}
          }
        return false;
    }
}
YEPFCore::registerAutoload();

//判断是否开启Debug
if((defined('YEPF_IS_DEBUG') && YEPF_IS_DEBUG) || (isset($_REQUEST['debug']) && strpos($_REQUEST['debug'], YEPF_DEBUG_PASS) !== false))
{
	//Debug模式将错误打开
	ini_set('display_errors', true);
	//设置错误级别
	error_reporting(YEPF_ERROR_LEVEL);
	//开启ob函数
	ob_start();
	//Debug开关打开
	\yoka\Debug::start();
	//注册shutdown函数用来Debug显示
	register_shutdown_function(array('\yoka\Debug', 'show'));
}
//读取系统配置文件
if(!defined('ENV_PATH'))define('ENV_PATH','/WORK/CONF');
if(file_exists(ENV_PATH . DIRECTORY_SEPARATOR . 'WORK-ENV.ini')){
	try{
		$env = parse_ini_file(ENV_PATH . DIRECTORY_SEPARATOR . 'WORK-ENV.ini', true);
		\yoka\Conf::$ENV = $env;
		//\yoka\Debug::log('ENV', $env);
	}catch (Exception $e){
		\yoka\Debug::log('Error:', 'load WORK-ENV failed!');
	}
}

