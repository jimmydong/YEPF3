<?php
/**
 * 企业微信
 * @author	jimmy.dong@gmail.com 2017.3.4
 *
 */

namespace controller\qy;
use yoka\mvc\Controller;
use yoka\Debug;
use yoka\Log;
use yoka\DB;
use yoka\Template;

class BaseController extends Controller {
	static public $admin_id;
	static public $_hasBuild = array(
			'index_index',
	);
	
	public function __construct($request, &$response) {
		parent::__construct($request, $response);
	}

	public function before($_controller, $_action){
	}

	public function after($_controller, $_action){
	}
	/**
	 * 输出内容
	 * 选择性调用模板： common.html / build.html / router.html
	 * @param object $response
	 * 		$response->pageTitle  页面标题
	 * 		$response->style 额外样式
	 * 		$response->prepose 前置HTML内容 (不建议使用)
	 * 		$response->html 后置HTML内容
	 * @param array $data 输出数据 
	 */
	public function display($response, $data = array(), $tplname = '', $menu_id = false) {
		//准备菜单
		if(! $noMenu){
			$id = 1;
			foreach(\qyweixin\QyWeixin::$menuOA as $v){
				$m[] = array('id'=> $id++, 'name'=>$v['name'], 'link'=> $v['url']);
			}
			$data['header'] = array(
					'pageTitle'=>$response->page_title,
					'menuList'=> $m,
			);
		}
		$response->data = json_encode($data, JSON_UNESCAPED_UNICODE);
		
		//输出模板
		if ($tplname == '') {
			if($response->build || in_array($response->_c . '_' . $response->_a, self::$_hasBuild)) $tplname = 'build';
			elseif ($response->router) $tplname = 'router';
			else $tplname = 'common';
		}
		$tpl = new Template('qy', $response);
		echo $tpl->r($tplname);
	}

}
