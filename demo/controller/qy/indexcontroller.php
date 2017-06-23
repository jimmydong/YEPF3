<?php
/**
 * 企业微信站点首页
 * @author "jimmy.dong@gmail.com"
 *
 */
namespace controller\qy;
use yoka\Debug;
use yoka\Cache;
use yoka\Log;
use yoka\DB;
use yoka\Template;
use \Exception;

class IndexController extends BaseController {
	public function __construct($request, $response){
		parent::__construct($request, $response);
		$response->page_title = '宜生OA系统';
	}
	
	//默认模式
	public function index($request, $response){
		$response->build = true;	//已编译
		$this->display($response, $data);
	}
	
	//指定模板
	public function index2($request, $response){
		$this->display($response, $data, "index/index2");
	}
	
}
