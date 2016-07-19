<?php
namespace MONGODAO;
class Test extends \yoka\BaseMongoRecord {

	//protected  static $collectionName='user';
	//定义当前对象的字段结构,由于mongodb是schema free的结果，如果不在此定义字段会导致数据库比较乱
	protected static $schema =	array(       
										'_id'				=>'objectid',
										'name'				=> 'string',
									);
	
	//jugglin 自动转义
	//essential 必填
	//default 默认值
	//min 最小值
	//max 最大值
	
	protected static $schema_ext =	array(	
											'_id'				=> array('jugglin'=>1,	'essential'=>1),
											'name'				=> array('jugglin'=>1	),
	);
	
	
	public function beforeUpdate() {
		$this->last_update = $this->Millseconds();
	}
	public function beforeSave() {
		$this->last_update = $this->Millseconds();
	}
	

}
?>