<?php
/**
 * Demo about YEPF 3.0 MVC
 * Enter description here ...
 * @author "jimmy.dong@gmail.com"
 *
 */
namespace controller;

class IndexController extends BaseController {
	/**
	 * 正式版首页 
	 * Enter description here ...
	 * @param unknown_type $request
	 * @param unknown_type $response
	 */
	public function index($request, $response){
    	//注意新版本的域名空间变化
    	$db = \yoka\DB::getInstance('default');  
		\yoka\Debug::stop();
		
		$response->hello = 'Hello World!';
		    	
		//输出页面
        $tpl = new \yoka\Template('default', $response);
        echo $tpl->r($response->_action);
	}
}