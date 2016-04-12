<?php
namespace mdbao;

class Test extends BaseMongoRecord{
  //定义当前对象的字段结构,由于mongodb是schema free的结构，如果不在此定义字段会导致数据库比较乱
	protected static $schema = array( 
										_id=>'objectid',
										name=>'string',
										data=>'string'
	);
	/**
	 * 扩展声明
	 * by jimmy.dong@gmail.com
	 * 通用扩展属性：
	 * 	jugglin		是否允许自动类型转换
	 *  essential	必须有值(非null)
	 *  default		缺省值
	 * 类型适用属性：
	 * switch(type){
	 *	case 'integer' 	:
	 *  case 'double'	:   min 最小值，max 最大值，break;
	 *  case 'string'	:   min 长度最小  max 长度最大 break;
	 *  case 'boolean'	:	
	 *  case 'object'	:
	 *  case 'resouce'	:
	 *	case 'NULL'		:
	 *	case 'unknown type': break;
	 * }
	 */
	protected static $schema_ext = array(
										name=>array('jugglin'=>1),
												
	);
}