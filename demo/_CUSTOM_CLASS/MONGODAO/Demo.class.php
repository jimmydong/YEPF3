<?php
namespace MONGODAO;
/**
 * MONGODAO操作使用示例
 * @author jimmy.dong@gmail.com
 *

		//增/删/改
		$obj = new \MONGODAO\Demo();
		$obj->enableGhost(); //允许动态新增字段
		$obj->name = 'test';
		$obj->save();
		$obj->name = 'new';
		$obj->save();
		//$obj->remove(array('_id',$obj->getID()));
		
		//查
		$re = \MONGODAO\Demo::findOne(array('name'=>'new'));
		$info = $re->getEntity();
		var_dump(\MONGODAO\Demo::getEntityById($info['_id']));
		$rows = \MONGODAO\Demo::findAll();
		var_dump($rows);

 * 
 */
class Demo extends \yoka\BaseMongoRecord {
	/*
	 * 定义当前对象的字段结构
	 * mongodb是schema free的结果，在此定义字段避免数据库结构混乱
	 * 
	 * 类型与传值：
	  	objectid: 字符串 OR MongoId
	  	string	: 字符串
	  	integer, float, double:  数字 OR 字符串
	  	datetime:	date('Y-m-d H:i:s') OR 毫秒时间戳 
	  	array	: ['key'=>'value', ...] 
	  	object	: $obj  OR  json_encode($obj)
	  	
	 */
	protected static $schema = array(
			'_id'    			=> 'objectid',	//[不建议使用无_id的特殊结构]
			'_last_update_ms'	=> 'datetime',
			'name'		  		=> 'string',  
	);
	
	/*
	 * 字段扩展定义：	
			jugglin 自动转义
			essential 必填
			default 默认值
			min 最小值
			max 最大值
	 */
	protected static $schema_ext = array(
			'_id'	 			=> array( 'jugglin'=>1, 'essential'=>1 ),
			'_last_update_ms'	=> array( 'jugglin'=>1 ),
			'name'	 			=> array( 'jugglin'=>1 ),
	);
	
	/*----------------------------- 基础方法，请保留 -------------------------*/
	public function __construct($attributes = array(), $new = true){
		parent::__construct($attributes, $new);
	}
	public function beforeUpdate() {
		$this->_last_update_ms = doubleval($this->Millseconds()); //更新戳记 
	}
	public function beforeSave() {
		$this->_last_update_ms = doubleval($this->Millseconds()); //更新戳记
	}
	
	/*----------------------------- 自定义扩展方法 ---------------------------*/
	
	
}