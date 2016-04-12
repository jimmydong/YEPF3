<?php
/**
 * Demo for YEPF 3.0 MVC - sub site
 * Enter description here ...
 * @author "jimmy.dong@gmail.com"
 *
 */
namespace controller\admin;
use \yoka\Debug;
use \yoka\Cache;
use \yoka\Log;
use \yoka\DB;

class IndexController extends BaseController {
	public function __construct($request, $response){
		parent::__construct($request, $response);
		$response->page_title = '管理后台';
	}
	
    public function index($request, $response){
		Debug::log('test','hello world');
		$db = DB::getInstance('default');
		$db->fetchOne("select * from user limit 1");
		$cache = Cache::getInstance('default');
		$cache->set('jimmy', 'hello');
		$re = $cache->get('jimmy');
    	
		$response->hello = 'hello world!';
		    	
		//输出页面
        $tpl = new \yoka\Template('admin', $response);
        echo $tpl->r($response->_action);
	}
}
