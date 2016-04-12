<?
/**
 * @name Template.class.php
 * @desc 模板操作类
 * @author caoxd
 * @createtime 2008-9-9 03:08
 * @updatetime
 * @usage
 **/
namespace yoka;
use \Smarty;
include dirname(__FILE__) . "/smarty/Smarty.class.php";

class Template extends Smarty
{

	private $tpl_type ;  //静态文件的类型
	private $directory;  //模板子目录
	public function __construct($directory = 'default', $response = null)
	{
        parent::__construct();

		//定义定界符
		$this->left_delimiter = "<{";
		$this->right_delimiter = "}>";
		
		//定义模板目录
		$this->directory = $directory;
		if($directory == ''){
			$this->setTemplateDir(getCustomConstants('TEMPLATE_PATH'));
			$this->setCompileDir(getCustomConstants('COMPILER_PATH'));
		}else{
			$this->setTemplateDir(getCustomConstants('TEMPLATE_PATH') . DIRECTORY_SEPARATOR . $directory);
			$this->setCompileDir(getCustomConstants('COMPILER_PATH') . DIRECTORY_SEPARATOR . $directory);
		}
		$this->setCacheDir(getCustomConstants('COMPILER_PATH') . DIRECTORY_SEPARATOR . 'cache');
		//if(!file_exists($this->template_dir))mkdir($this->template_dir);
		//if(!file_exists($this->compile_dir))mkdir($this->compile_dir);
		
		$this->setCacheDir(getCustomConstants('COMPILER_PATH'));
		$this->tpl_type = getCustomConstants('TEMPLATE_TYPE');

		//注册静态化函数
		if(function_exists('template_url_encode')){
			$this->registerPlugin('function', 'url', 'template_url_encode');
		}
		if(function_exists('template_nicetime_encode')){
			$this->registerPlugin('function', 'nicetime', 'template_nicetime_encode');
		}
		if(function_exists('template_cutstr_encode')){
			$this->registerPlugin('function', 'cutstr', 'template_cutstr_encode');
		}

	        if(function_exists('template_thumb_encode')){
        	    $this->registerPlugin('function', 'thumb', 'template_thumb_encode');
        	}   
       		if(function_exists('template_widget_encode')){
            		$this->registerPlugin('function', 'widget', 'template_widget_encode');
		}

		//传入变量
		if($response) $this->fit_sprite($response);
		
		if(class_exists('\yoka\Debug')){
			if(\yoka\Debug::$open) $this->error_reporting = E_ALL & ~E_NOTICE;
		}
	}
	/**
	 * @name d
	 * @desc 模板显示
	 **/
	public function d($resource_name, $cache_id = null, $compile_id = null)
	{
		$this->r($resource_name, $cache_id , $compile_id ,true);
	}
	/**
	 * @name r
	 * @desc 将模板值返回
	 **/
	public function r($resource_name, $cache_id = null, $compile_id = null, $display = false)
	{
		global $CFG, $YOKA, $TEMPLATE, $DEFINE ;
		$begin_microtime = \yoka\Debug::getTime();
		$this->assign('CFG', $CFG);
		$this->assign('YOKA', $YOKA);
		$this->assign('TEMPLATE', $TEMPLATE);
		$this->assign('DEFINE', $DEFINE);
		if(class_exists('\yoka\Debug') && \yoka\Debug::$open){
			$t = debug_backtrace(1);
			$caller = $t[0]['file'].':'.$t[0]['line'];
			\yoka\Debug::template($resource_name, \yoka\Debug::getTime() - $begin_microtime, $caller);
		}
		if($this->debugging || $display)return $this->display($resource_name.".".$this->tpl_type, $cache_id, $compile_id);
        return $this->fetch($resource_name.".".$this->tpl_type, $cache_id , $compile_id);
	}
	/**
	 * @name fix_sprite
	 * @desc 传入$response，自动转为内部变量
	 */
	public function fit_sprite($response){
		if(is_array(get_object_vars($response)))foreach (get_object_vars($response) as $key=>$value) {
			$this->assign($key, $value);
		}
	}
	/**
	 * 简易Layout实现
	 * Enter description here ...
	 * @param string layout 布局文件（layout目录下）
	 * @param mix $resource  文件名，则默认嵌套入 LAYOUT_CONTENT ，
	 * @param unknown_type $cache_id
	 * @param unknown_type $compile_id
	 * @param unknown_type $display
	 */
	public function layout($layout, $resource, $cache_id = null, $compile_id = null, $display = false)
	{
		global $CFG, $YOKA, $TEMPLATE, $DEFINE ;
		$directory = $this->directory;
		if($directory == ''){
			$this->template_dir = getCustomConstants('TEMPLATE_PATH');
			$this->compile_dir = getCustomConstants('COMPILER_PATH');
		}else{
			$this->template_dir = getCustomConstants('TEMPLATE_PATH') . DIRECTORY_SEPARATOR . $directory;
			$this->compile_dir = getCustomConstants('COMPILER_PATH') . DIRECTORY_SEPARATOR . $directory;
		}
		if(!file_exists($this->compile_dir))mkdir($this->compile_dir);
		
		$this->cache_dir = getCustomConstants('COMPILER_PATH');
		$this->tpl_type = getCustomConstants('TEMPLATE_TYPE');
		$this->assign('CFG', $CFG);
		$this->assign('YOKA', $YOKA);
		$this->assign('TEMPLATE', $TEMPLATE);
		$this->assign('DEFINE', $DEFINE);
		if(is_array($resource)){
			foreach($resource as $key=>$val){
				$this->assign($key, $val.".".$this->tpl_type);
			}
		}else $this->assign('LAYOUT_CONTENT', $resource.".".$this->tpl_type);
		if($this->debugging || $display)return $this->display("layout/".$layout.".".$this->tpl_type, $cache_id, $compile_id);
		return $this->fetch("layout/".$layout.".".$this->tpl_type, $cache_id , $compile_id);
	}
	

}

?>
