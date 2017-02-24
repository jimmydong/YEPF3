<?php
/**
 *
 * 线索（演示使用自定义主键）
 * @author jimmy.dong@gmail.com
 *
 */
namespace DAO;
use yoka\Debug;
use yoka\Cache;
use yoka\Log;
use yoka\DB;

class Leads extends \model\BaseModel{
	static $table = 'crm_leads';
	static $pkey = 'leads_id';	//主键

	/**
	 * 实例化
	 * @param int $id ID
	 */
	public function __construct($id = null){
		parent::__construct($id);
	}
	
	/**
	 * 用名字取
	 */
	public static function getByName($name){
		$obj = new self;
		return $obj->fetchOne(array('name'=>$name, 'is_deleted'=>0));
	}
	
}
