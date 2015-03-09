<?php
namespace yoka\mvc;
use \Exception;

/**
 * 简单的mvc实现
 *
 */
class App {

	private $_controller = 'index';
	private $_action = 'index';
	private $_model = '';

	public function __construct($controller, $action, $model = '') {
		if ($controller)
			$this->_controller = $controller;
		if ($action)
			$this->_action = $action;
		if ($model)
			$this->_model = $model;
	}

	/**
	 * 启动一个controller
	 */
	public function run() {
		$request = Request::getInstance();
		$response = new Response();
		$response->_c = $response->_controller = $this->_controller;
		$response->_a = $response->_action = $this->_action;

		if($this->_model)$controller = "\\controller\\".  $this->_model . "\\"  . $this->_controller.'controller';
		else $controller = "\\controller\\". $this->_controller.'controller';
		if (!class_exists($controller))
			throw new Exception("no controller called $controller ");
		$obj = new $controller($request, $response);
		if (!method_exists($obj, $this->_action) && $this->_controller != 'index') //允许index控制器使用魔术方法
			throw new Exception("'$controller' has not method '{$this->_action}' ");
		try {
			$obj->before($this->_controller, $this->_action);
			$obj->{$this->_action}($request, $response);
			$obj->after($this->_controller, $this->_action);
		} catch (Exception $e) {
			if (method_exists($obj, 'error')) {
				$obj->error($request, $response, $e);
			}
			\yoka\Debug::log('Error',$e);
			echo "Error. Please check the debug info.";
			
		}
	}


}
