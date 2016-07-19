<?php
/**
 * Model使用示例
 * @author jimmy.dong@gmail.com
 *
 */
namespace DAO;
use yoka\Debug;
use yoka\Cache;
use yoka\Log;
use yoka\DB;

class Test extends  \yoka\BaseModel{
	public static $table = 'test';

	/**
	 * 实例化
	 * @param int $id 主键ID
	 */
	function __construct($id = null){
		parent::__construct($id);
	}

}
