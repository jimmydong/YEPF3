<?php
namespace yoka\mvc;

/**
 * 简单的控制器基类
 *
 */
class Controller {
	
	//当前controler的默认layout,子类可以重写
	public $_response;
	
	protected $_request;
	protected $_controller;
	protected $_action;
	
	private $_templateDir;
	private $_layoutDir;
	private $_layout;
	
	public function __construct($request, &$response) {
		$this->_request = $request;
		$this->_response = $response;
		$this->_controller = $response->_controller;
		$this->_action = $response->_action;
		$this->_templateDir = '../template/';
		$this->_layoutDir = $this->_templateDir.'layout/';
		$this->_layout = 'default';
	}
	
	/**
	 * 设置默认的布局器
	 * @param unknown_type $layout
	 */
	protected function setLayout($layout){
		$this->_layout = $layout;
	}
	
	
	/**
	 * 前置拦截器
	 */
	//转移至basecontroller中 public function before() {}

	/**
	 * 后置拦截器
	 */
	//转移至basecontroller中 public function after() {}

	
	protected function render($file=NULL) {
		foreach (get_object_vars($this->_response) as $key=>$value) {
			$$key = $value;
		}
		
		$controller = strtolower($this->_controller);
		$action = strtolower($this->_action);
		
		if ($file)
			$action = $file;
		
		$template = $this->_templateDir.$controller.'/'.$action.'.php';
		include $template;
	}
	
	protected function layout($layoutFile=NULL) {
		foreach (get_object_vars($this->_response) as $key=>$value) {
			$$key = $value;
		}

		$controller = strtolower($this->_controller);
		$action = strtolower($this->_action);
		
		if (!$layoutFile)
			$layoutFile = $action;
		
		$_layout_content = $this->_templateDir.$controller.'/'.$layoutFile.'.php';
			
		$template =  $this->_layoutDir.'/'.$this->_layout.'.php';
		include $template;
	}
	
	public function renderJson($array_or_obj) {
		header('Content-type: text/json');
		echo json_encode($array_or_obj);
	}
	
	public function renderString($string) {
		echo $string;
	}

	public function redirect($url) {
		header("Location: $url");
	}
	
	public function renderSmarty($file=NULL) {
		$smarty = new SmartyView($this->_response);
		$smarty->render($file);
	} 
	
	public function layoutSmarty($file=NULL) {
		$smarty = new SmartyView($this->_response);
		$smarty->layout($file);
	}
	
}