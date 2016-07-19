<?php
/**
 * @name init.php
 * @desc YEPF3.0 文件初始化设置,包含此目录包需要的文件及变量声明
 * @author jimmy.dong@gmail.com
 * @updatetime 2014-09-20
 * 【注意】建议目录结构
 *  _CUSTOM_CLASS 项目类文件目录
 *  _DOC 文档目录
 *  _LOCAL 保存本地配置信息
 *  _TEMPLATE 放置模板
 *  _TEMPLATE_C 模板编译目录(需可写)
 *  _LOG 日志目录(需可写)
 *  controller 控制器目录
 *  DocumentRoot 网站根目录
 *  AdminRoot 子站点目录
 *  PythonRoot OR Other 协同语言目录
 */
if(!defined('INCLUDE_INIT')){ //防止重复加载
	define('INCLUDE_INIT', true);

	//加载Composer框架
	include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
	
	include dirname(__FILE__) . DIRECTORY_SEPARATOR .'_LOCAL'. DIRECTORY_SEPARATOR . 'local.inc.php'; //请将路径配置相关信息放入此文件
	if(!defined('YEPF_PATH')){
		if($_SERVER['YEPF_PATH_3']) define('YEPF_PATH',$_SERVER['YEPF_PATH_3']);
		else define('YEPF_PATH', '/YOKA/HTML/_YEPF3.0');
	} 
	
	include YEPF_PATH . '/global.inc.php';
	include dirname(__FILE__) . DIRECTORY_SEPARATOR .'_LOCAL'. DIRECTORY_SEPARATOR . 'autoload.inc.php'; //应用环境变量
	
	//定义缺省值
	define(DefaultPageLimit, 24);
	
	if($_REQUEST['refresh']==1 || $_REQUEST['_c']=='order')
		define('SiteCacheForceRefresh', 1);	//强制刷新缓存
	else define('SiteCacheForceRefresh', 0);
	
	//不使用mvc可去除此项
	$autoPath = dirname(__FILE__);$path = get_include_path();
	if (strpos($path.PATH_SEPARATOR, $autoPath.PATH_SEPARATOR) === false) set_include_path($path.PATH_SEPARATOR.$autoPath);
	spl_autoload_register('spl_autoload');
	
	//预加载类（避免程序中使用use出错）
	if(class_exists('\yoka\DB') && class_exists('\yoka\Log') && class_exists('\yoka\Template') && class_exists('\yoka\Cache')){}
	
	//记录调试日志到文件（默认关闭）
	\yoka\Debug::log_debug(false);
	
	//记录数据库操作到文件（默认关闭）
	if(method_exists('yoka\Debug', 'log_db')){
	//	\yoka\Debug::log_db(true); //by jimmy.dong@gmail.com 2014.3.24
	}
	//记录到数据库
	if(isset(\yoka\Debug::$log_mysql)){ //by jimmy.dong@gmail.com 2015.10.16
	//	\yoka\Debug::$log_mysql = array('db'=>'log', 'master'=>true);
	//	\yoka\Debug::$db_log_mysql = true;		//记录数据库操作到数据库
	//	\yoka\Debug::$debug_log_mysql = true;	//记录日志到数据库。建议试用 \yoka\Debug::dlog() 方法单独记录
	}
	
	//记录运行性能（默认关闭）
	if (mt_rand(1, 1) == 0 && function_exists('xhprof_enable') ) {
	 xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	 $xhprof_on = true;
	}
	///////////////////////////////////////////////////////////////////////////////////
	// SMARTY使用函数
	// 注册到Smarty。注意：增加函数 需同步修改YEPF的Template类
	///////////////////////////////////////////////////////////////////////////////////

	/**
	 * 取Url
	 * Enter description here ...
	 * @update 新增参数 _t ， 当 _t 有效时，自动添加时间戳，以防止客户端缓冲
	 * @param array $args
	 */
	function template_url_encode($args){
		if(0 && is_array($args)){	//地址静态化（默认关闭）
			ksort($args);
			$must_dynamic = false;
			foreach($args as $key=>$val){
				if($key == '_t')continue;
				if($val === '' || $val === null){
					$must_dynamic = true; 
				}
				$keystring.=$key.',';
			}
            extract($args);
			$page = intval($page);
			if ($page < 1) $page = 1;
			if($must_dynamic != true){
				if($_a == 'index') $_a = '';
				if($_c == 'index' || $_c == '' || $_c == null)switch ($keystring){
					case '_a,':
					case '_a,_c,':
						$re = $root_domain . DIRECTORY_SEPARATOR . 'index'.($_a?('_'.$_a):'').'.html';
						//RewriteRule /index_([^_]*)\.html /index.php?_c=index&_a=$1&uw=1&%{QUERY_STRING} [L]
						break;
					break;
						$must_dynamic = true;
						default:
				}
				/* ... 逐个配置转换规则 */
				
                if($re && $_t){
                	//_t： 防止客户端缓存
                	$re .= '?_t=' . substr(time(),-6);
                }elseif($re == '') {
				/*通用转换。支持0-2个参数。
				 	RewriteRule /common_([^_]*)_([^_\/]*)\/([0-9]+)\.html /index.php?_c=$1&_a=$2&page=$3&%{QUERY_STRING} [L]
					RewriteRule /common_([^_]*)_([^_\/]*)\/([^\/]*)\/([^\/]*)\/([0-9]+)\.html /index.php?_c=$1&_a=$2&page=$5&$3=$4&%{QUERY_STRING} [L]
					RewriteRule /common_([^_]*)_([^_\/]*)\/([^\/]*)\/([^\/]*)\/([^\/]*)\/([^\/]*)\/([0-9]+)\.html /index.php?_c=$1&_a=$2&page=$7&$3=$4&$5=$6&%{QUERY_STRING} [L]
				 */
					if($_a == '') $_a = 'index';
					if(in_array($_c, array('tools','upload'))) $must_dynamic = true; //注意，不能使用通用转化的control，需要在这里进行注册
					else{
						$param = array();$param_ext = array();
						$param_count = 0;
						foreach($args as $key=>$val){
							if($key == '_a' || $key == '_c' || $key == 'page') continue;
							if($param_count++<2){
								$param[] = urlencode($key);
								$param[] = urlencode($val);
							}else{
								$param_ext[]= urlencode($key) . "=" . urlencode($val);
							}
						}
						$re = ROOT_DOMAIN . DIRECTORY_SEPARATOR . 'common_' . $_c . '_' . $_a . '_' . $page;
						foreach($param as $val) $re .= '_' . $val;
						$re .= '.html';
						if($param_ext){
							$re .= "?" . implode('&', $param_ext);
						}
						if($_t){
							if($param_count <= 2) $re .= '?_t=' . substr(time(),-6);
							else $re .= '&_t=' . substr(time(),-6);
						}
					}			
				}
			}
			if($must_dynamic != true){
				return $re;
			}
		}
		
		//动态地址
		$t = '';
		if(is_array($args))foreach($args as $key=>$val){
			if($key == '_domain' || $key == '_site')continue;
			if($key == '_t') $t .= '_t=' . substr(time(),-6) .'&';
			else $t .= urlencode($key) . '=' . urlencode($val) .'&';
		}else{
			$t = $args;
		}
		if(substr($t,-1,1)=='&')$t=substr($t,0,strlen($t)-1);
    	$new_url = $root_domain . DIRECTORY_SEPARATOR . "index.php?".$t;
		return $new_url;
	}

	/**
	 * 传入unix_timestamp，输出友好格式时间
	 * Enter description here ...
	 * @param array $params = array('time'=>$time) 毫秒时间戳格式
	 */
	function template_nicetime_encode($params){
		extract($params);
		$now = time();
		$diff = $now - ceil($time);
		$hours = ceil($diff/3600);

		if($diff<300)
		{
			return '刚刚';
		}
		if($diff<3600 && $diff>=300)
		{
			return ceil($diff/60).'分钟前';
		}
		else if($hours <24)
		{
			return $hours.'小时前';
		}
		else if($hours<=(3*24) && $hours>=24)
		{
			return round($hours/24).'天前';
		}
		else
		{
			return date('Y年n月j日',ceil($time/1000));
		}
	}
	
	/**
	 * UTF8切字符（按照指定宽度，ASCII每字符宽度为1，非ASCII宽度为2）
	 * Enter description here ...
	 * @param array $params = array('str'=>$str, 'length'=>$len, 'suffix'=>'...')
	 */
	function template_cutstr_encode($params)
	{
		extract($params);
		if(mb_strwidth($str,'utf8') < $length) return $str;
		if($suffix == '...') return mb_strimwidth($str,0,$length-2,'','utf8') . $suffix;
		else return mb_strimwidth($str,0,$length,$suffix,'utf8');
	}
}else{
	//\yoka\Debug::log('Warning', 'Load init.php more than one time!');
}