<?php
/**
* Mongodb 相关操作类
* A，DbMongo    part1:类SQL操作
*								part2:类Memcache操作
* 	 						part3:辅助操作
* B，MongoAdmin 管理操作
* C, MongoFile  GridFS操作
*
* 注意： 非UTF8字符需要用MongoBinData()进行处理（仅类Memcache操作做了自动处理） 
* 注意： 连接符-是不安全的，应避免在表名、字段名中使用
* 说明： 
	【常用数据操作】
		针对初级、中级用户
		面向简单数据结构，存储为类似SQL的二维表形式
		提供功能分为三组：
				类PDO： insertOne, insertAll, replaceOne, fetchOne, fetchAll, fetchLimit, fetchSince, count, updateOne, updateById, updateAll, deleteOne, deleteAll ...
				类API： query, nextRecord
				特殊：  increase, execute
	【类Memcache操作】
		方法名同Memcache类，便于开发人员使用: add, get, set, update, delete, increment, decrement
		扩展了参数，用前缀区别命名空间，用集合区别不同应用
		默认集合名称为'key_value'。
	【管理操作】
		将库表创建、索引、直接运行原始代码等功能单独封装，避免编程人员手误导致严重后果
	【GridFS操作】
		简单的文件存储

wait: 
	1，完善管理操作、GridFS操作
	2，？用try封装操作
	3，改写所有die -》 $this->onError(...)
	4，整合YEPF的DEBUG  （监控操作时间？）
* 
* by jimmy
* 2010-12-10
*/
namespace ext;

class DbMongo
{
	static $instance = array();
	
	protected $db_host;
	protected $db_user;
	protected $db_password;
	protected $db_name;
	protected $db_prefix;
	protected $is_close = true;
	protected $mongodb_connection = null;	//数据库连接实例
	protected $db;												//数据库操作实例
	protected $result;
	
	protected $limit_fetchAll = 300;
	protected $default_key_value = 'key_value';

	public 	$cursor;
	public  $count;
	
	//用外部定义的变量初始类，并连接数据库
	function __construct($dbconfig){    
		$this->_init_mongodb($dbconfig);
	}

	protected function _init_mongodb($dbconfig){
		$this->_init_db_config($dbconfig);
		$this->open();        
	}
	
	protected function _init_db_config($dbconfig){
		$this->db_host = $dbconfig['host'];
		$this->db_user = $dbconfig['user'];
		$this->db_password = $dbconfig['password'];
		$this->db_name = $dbconfig['db'];
	}

	/**
     * @name getInstance
     * @desc 单件模式调用入口
     * @param string $item    项目类型名称
     * @return object instance of Cache
     * @access public
     **/
	public function getInstance($item, $master = true)
	{
    	global $CACHE;
    	$obj = DbMongo::$instance;
			$db_key = $item.''.$master;
    	if(!isset($obj[$db_key]))
    	{
				if(isset($CACHE['mongodb'][$item]))
				{
					$key = $master === true ? 'master' : 'slave';
					$max = count($CACHE['db'][$item][$key]);
					$rand = rand(0, $max - 1);
					$config = $CACHE['mongodb'][$item][$key][$rand];
				}
				$obj[$db_key] = new DbMongo($config);
				DbMongo::$instance = $obj;
			}
    	return $obj[$db_key];
	}
	
	/* ----------------------------------------------- Part1: 模拟数据库操作 -----------------------------------------*/
	
	/**
	* 插入单条数据
	* @param string $collections_name    集合名称(相当于关系数据库中的表)
	* @param array $data_array
	*/
	public function insertOne($collections_name,$data_array){
	$this->_auto_connection_mongondb();
	$collection = $this->_select_collection($collections_name);
	return $collection->insert($data_array, array('safe'=>0));
	}

	/**
	* 插入多数据
	* @param string $collections_name    集合名称(相当于关系数据库中的表)
	* @param array $data_array						二级数组
	*/
	public function insertAll($collections_name,$data_array){
		foreach($data_array as $a){
			if(!is_array($a))die('InsertAll Data format error!');
		}
		$this->_auto_connection_mongondb();
		$collection = $this->_select_collection($collections_name);
		foreach($data_array as $data)	$collection->insert($data, array('safe'=>1));
	}
	
	/**
	* 插入或更新数据
	* @param string    $collection_name    集合名称|表名
	* @param mixed     $key                查询条件，用作唯一的键名，或多个键名的数组
	* @param array     $update_data        要更新的数据
	* @return bool
	* 注意： 为防止出错，限制仅更新一条。请在条件中指明主键或唯一值。（否则多条匹配，则无法确定更新的是哪一条）
	*/
	public function replaceOne($collection_name,$key,$update_data){
		if(is_array($key)){
			foreach($key as $k=> $v){
				if(!isset($update_data[$k])) die('replaceOne error: key('.$k.') is not in data. update_data must be full.');
				$query[$k]=$update_data[$k];
			}
		}else{
				if(!isset($update_data[$key])) die('replaceOne error: key('.$k.') is not in data. update_data must be full.');
				$query[$key]=$update_data[$key];
		}
		if(count($query)<1) die('replaceOne error: where the key?');
		$collection = $this->_select_collection($collection_name);
		$result = $collection->update($query,$update_data,array('upsert'=>1,'multiple'=>0,'safe'=>0));
		return $result;
	}
	
	/**
	* 查询一条记录
	* @param string $collections_name    集合名称(相当于关系数据库中的表)
	* @param array $query                查询的条件array(key=>value) 相当于key=value
	* @param array $filed                 需要列表的字段信息array(filed1,filed2)
	* @返回数组中 [_id] => MongoId Object
	*/
	public function fetchOne($collections_name,$query=array(),$filed=array()){    
	$this->_auto_connection_mongondb();        
	$collection = $this->db->selectCollection($collections_name);        
	$result = $collection->findOne($query,$filed);
	return $result;
	}

	/**
	* 查询全部记录
	* @param string $collections_name    集合名称(相当于关系数据库中的表)
	* @param array $query                查询的条件array(key=>value) 相当于key=value
	* @param array $field                 需要列表的字段信息array(filed1,filed2)
	* @param int $force										返回条数限制。默认限制300条
	*/
	public function fetchAll($collection_name,$query=array(),$field=array(),$force=0){
		//if(count($query) == 0) die('FetchAll without condition is forbidden!'); 
		$this->_auto_connection_mongondb();
		$result = array();
		$collection = $this->_select_collection($collection_name);    
		$cursor = $collection->find($query,$field);
		$count = $cursor->count();
		if(!$force){
			if( $count > $this->limit_fetchAll) die('Records over limit({$this->limit_fetchAll}). Please use force=1');
		}
		if($force && $force < $count)$cursor->limit($force);
		while ($cursor->hasNext()){
		$result[] = $cursor->getNext();
		}
		return $result;
	}
	
	/**
	* 查询全部记录并排序限制 ORDER BY xxxx LIMIT xxx,xxx
	* @param string $collection_name    集合名称(相当于关系数据库中的表)
	* @param array $query                查询的条件array(key=>value) 相当于key=value
	* @param array $filed                 需要列表的字段信息array(filed1,filed2)
	* @param mixed $sort									排序条件
	* @param mixed	$limit									''等效于限制默认条数内 | 5等效limit 5 | array(100,20)等效于limit 100,20
	*/
	public function fetchLimit($collection_name,$query=array(),$field=array(),$sort=array(),$limit=''){
		//debug if(count($query) == 0 && $limit == '') die('FetchLimit without condition nor limit is forbidden!'); 
		$this->_auto_connection_mongondb();
		$result = array();
		$collection = $this->_select_collection($collection_name);    
		$cursor = $collection->find($query,$field);
		if(is_array($sort) && count($sort) > 0)$cursor->sort($sort);
		elseif(is_string($sort))$cursor->sort(array($sort=>1));
		if($limit == ''){
			$count = $cursor->count();
			if( $count > $this->limit_fetchAll) die('Records over limit({$this->limit_fetchAll}). Please use force=1');
		}elseif(intval($limit)==$limit){
			$cursor->limit($limit);
		}elseif(is_array($limit)){
			list($begin, $limit_i) = $limit; $begin=intval($begin);$limit=intval($limit_i);
			if($begin < 0 || $limit < 1) die('Param limit error!');
			$cursor->skip($begin);
			$cursor->limit($limit);
		}else die('Param limit error!');
		while ($cursor->hasNext()){
		$result[] = $cursor->getNext();
		}
		return array('count'=>$count,'data'=>$result);
	}
	
	/**
	* 查询记录集的条数
	* @param string $collection_name    集合名称(相当于关系数据库中的表)
	* @param array $query
	* @return int
	*/    
	public function count($collection_name,$query=array()){
		$this->_auto_connection_mongondb();
		$collection = $this->_select_collection($collection_name);
		return $collection->count($query);
	}
	
	/**
	* 从特定位置开始显示查询结果 xxx AND _id > '$_id' ORDER BY _id LIMIT $limit
	* @param string $collection_name    集合名称(相当于关系数据库中的表)
	* @param _id 	$since									起始的_id （注意： mongo_id类型 或 string） 返回结果不包含此行
	* @param mixed	$limit								限制条数 | 5等效limit 5 
	* @param string	$sort								 是否倒序(desc) 
	* @param array $query                查询的条件array(key=>value) 相当于key=value
	* @param array $field                 需要列表的字段信息array(filed1,filed2) 
	*/
	public function fetchSince($collection_name,$since='',$limit='',$sort='',$query=array(),$field=array()){
		//if($field && !in_array('_id',$field))$field[]='_id';
		if(gettype($since)=='string')$since = new MongoID($since);
		$this->_auto_connection_mongondb();
		$result = array();
		//计算总数
		$collection = $this->_select_collection($collection_name);    
		$cursor = $collection->find($query,$field);
		$count = $cursor->count();
		
		//查询数据
		if($since){
			if(strtolower($sort)=='desc')$query['_id'] = array('$lt'=>$since);
			else $query['_id'] = array('$gt'=>$since);
			$cursor = $collection->find($query,$field);
		}
		if(strtolower($sort)=='desc')$cursor->sort(array('_id'=>-1));
		else $cursor->sort(array('_id'=>1));
		if($limit == ''){
			$count = $cursor->count();
			if( $count > $this->limit_fetchAll) die('Records over limit({$this->limit_fetchAll}). Please use force=1');
		}else{
			$cursor->limit($limit);
		}
		while ($cursor->hasNext()){
			$result[] = $cursor->getNext();
		}
		return array('count'=>$count,'data'=>$result);
	}

	
	/**
	* 更新数据一条记录
	* @param string     $collection_name    集合名称|表名
	* @param array     $query                查询条件array(key=>value)
	* @param array     $update_data        要更新的数据
	* @return bool
  *	注意： 与MongoCollection::update不同，并不是用$update_data全部取代原有内容，而是叠覆到旧数据上
	* 参考： mongodb::update 限制 Modifiers and non-modifiers cannot be mixed
	*/
	public function updateOne($collection_name,$query,$update_data){
		$collection = $this->_select_collection($collection_name);
		$old_data = $collection->findOne($query);
		if(!$old_data) return false;
		foreach($update_data as $k=> $v){
			$old_data[$k] = $v;
		}
		$update_data = $old_data;
		$result = $collection->save($update_data);
		return $result;
	}

	/**
	* 用主键更新一条记录 
	* @param string     $collection_name    集合名称|表名
	* @param mixed     		$id                ID(24位16进制 或 MongoID)
	* @param array     $update_data        要更新的数据
	* @return bool
  *	注意： 与MongoCollection::update不同，并不是用$update_data全部取代原有内容，而是叠覆到旧数据上
	* 参考： mongodb::update 限制 Modifiers and non-modifiers cannot be mixed
	*/
	public function updateById($collection_name,$id,$update_data){
		if(gettype($id)=='string')$id = new MongoID($id);
		$collection = $this->_select_collection($collection_name);
		$old_data = $collection->findOne(array('_id'=>$id));
		if(!$old_data) return false;
		foreach($update_data as $k=> $v){
			$old_data[$k] = $v;
		}
		$update_data = $old_data;
		$result = $collection->save($update_data);
		return $result;
	}

	/**
	* 更新全部满足条件的记录
	* @param string $collection_name
	* @param array $query
	* @param array $update_data
	*/
	public function updateAll($collection_name,$query,$update_data){
		$exec = "db.{$collection_name}.update(" . json_encode($query) . " , {\$set:" . json_encode($update_data) . "} , 0, 1)";
		$result = $this->db->execute($exec);
		return $result['ok'];
	}
	/**
	* 数值增加
	* @param string $collection_name	集合名称|表名
	* @param array  $query					条件
	* @param string $field_name			字段名称，如果是数组的话，多个字段都会执行自增、自减的操作
	* @param int 		$inc						增加的数值，如果是数组的话，需要对应field_name的字段
	*/
	public function increase($collection_name,$query,$field_name,$inc=1){
		if($field_name == '')die('increase must assign field_name!');
		if (is_array($field_name))
		{
			if (is_array($inc))
			{
				foreach ($field_name as $k => $v)
				{
					$inc_list[$v] =  $inc[$k];
				}
			}
			else
			{
				foreach ($field_name as $k => $v)
				{
					$inc_list[$v] =  $inc;
				}
			}
			
		}
		else
		{
			$inc_list[$field_name] = $inc;
		}
		$exec = "db.{$collection_name}.update(" . json_encode($query) . " , {\$inc: ".json_encode($inc_list)." } , 0, 1)";
		$result = $this->db->execute($exec);
		return $result['ok'];
	}
	/**
	* 删除一条记录
	* @param string $collection_name    集合名称(相当于关系数据库中的表)
	* @param array $query                删除条件
	* @return unknown
	*/
	public function deleteOne($collection_name,$query){
		$collection = $this->_select_collection($collection_name);
		$result = $collection->remove($query,array("justOne"=>1,'safe'=>0));
		return $result;    
	}
	
	/**
	* 删除全部符合条件记录
	* @param string $collection_name    集合名称(相当于关系数据库中的表)
	* @param array $query                删除条件
	* @return unknown
	*/
	public function deleteAll($collection_name,$query){
		$collection = $this->_select_collection($collection_name);
		$result = $collection->remove($query,$option,array("justOne"=>0,'safe'=>1));
		return $result;    
	}
	
	/**
	* 执行一个查询，返回游标
	* @param string $collection_name 表名
	* @param array 
	*/
	public function query($collection_name,$query=array(),$field=array(),$sort=array(),$limit=''){
		$this->_auto_connection_mongondb();
		$result = array();
		$collection = $this->_select_collection($collection_name);    
		$cursor = $collection->find($query,$field);
		if(count($sort)>0)$cursor->sort($sort);
		if($limit == ''){
		}elseif(intval($limit)==$limit){
			$cursor->limit($limit);
		}elseif(is_array($limit)){
			list($begin, $limit) = $limit; $begin=intval($begin);$limit=intval($limit);
			if($begin < 0 || $limit < 1) die('Param limit error!');
			$cursor->skip($begin);
			$cursor->limit($limit);
		}else die('Param limit error!');
		$this->cursor = $cursor;
		return $this->cursor;
	}
	/**
	* 移动游标（配合query）
	* @param MongoCursor $cursor
	*/
	public function nextRecord($cursor = ''){
		if(!$cursor)$cursor = $this->cursor;
		if($cursor->hasNext())return $cursor->getNext();
		else return false;
	}

	/**
	* MongoDB::execute
	* @param mixed $jscode	原始命令
	* @param array $args		参数
	* 注意： 仅供开发期使用，正式上线前应关闭，改为使用MongoAdmin类。
	*/
	public function execute($jscode){
		$this->_auto_connection_mongondb();
		return $this->execute($jscode);
	}
	

	/* ----------------------------------------------- Part2: 模拟键值对操作 -----------------------------------------*/

	/**
	* 基本键值对处理 （与Memcache类方法名保持一致）
	* @param string $collections_name    集合名称(相当于关系数据库中的表)
	* @param string $key				主键
	* @param mixed  $value			数据
	* @param string $prefix			前缀（用于区别业务）
	* 注意： 1，默认集合为default 2，请手工建立唯一索引 3，请勿向处理键值对的集合中存入其他结构数据
	*/
	public function add($key, $value, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$this->_auto_connection_mongondb();        
		$collection = $this->db->selectCollection($collections_name);
		if($collection->findOne(array('key'=> $key), array())) die('can not add because already have this key: '. $key);
		if(!is_numeric($value))$value=new MongoBinData(serialize($value));
		return $collection->insert(array('key'=> $key,'value'=> $value), array('safe'=>0));	
	}
	public function get($key, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$this->_auto_connection_mongondb();        
		$collection = $this->db->selectCollection($collections_name);   		
		$result = $collection->findOne(array('key'=> $key), array('value'));	
		$value = $result['value'];
		if(!is_numeric($value))$value = unserialize($value->bin);
		return $value;
	}
	public function set($key, $value, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$this->_auto_connection_mongondb();        
		$collection = $this->db->selectCollection($collections_name);
		if(!is_numeric($value))$value=new MongoBinData(serialize($value));		
		return $collection->update(array('key'=> $key), array('key'=> $key, 'value'=> $value) , array('upsert'=>1,'multiple'=>0,'safe'=>0));
	}
	public function update($key, $value, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$this->_auto_connection_mongondb();        
		$collection = $this->db->selectCollection($collections_name);
		if(!is_numeric($value))$value=new MongoBinData(serialize($value));		
		$result = $collection->update(array('key'=> $key), array('key'=> $key, 'value'=> $value) , array('upsert'=>0,'multiple'=>0,'safe'=>1));
		return $result['n'];
	}
	public function delete($key, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$this->_auto_connection_mongondb();        
		$collection = $this->db->selectCollection($collections_name='');
		return $collection->remove(array('key'=> $key), array("justOne"=>1,'safe'=>0));
	}
	public function increment($key, $prefix='DFT_', $collections_name=''){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$exec = "db.{$collections_name}.update({\"key\":\"{$key}\"} , {\$inc: {'value' : 1} } , 0, 1)";
		$result = $this->db->execute($exec);
		return	$result['ok'];	
	}
	public function decrement($key, $prefix='DFT_', $collections_name){
		if($collections_name == '')$collections_name = $this->default_key_value;;
		if($key == '') die('add without a validate key!');
		$key = $prefix . $key;
		$exec = "db.{$collections_name}.update({\"key\":\"{$key}\"} , {\$inc: {'value' : 1} } , 0, 1)";
		$result = $this->db->execute($exec);
		return	$result['ok'];		
	}

	/* ----------------------------------------------- Part3: 辅助操作函数 -----------------------------------------*/
		
	/**
	* 错误处理
	* jimmy: 输出调用log
	*/
	public function onError($err_type, $err_msg, $line=''){
		if(get_cfg_var('display_errors')){
			echo "MongoDB $err_type : $err_msg  <br>\n at line: $line";
			return; 
		}else $this->log('Error', $err_type . ':' . $err_msg, 'at line:'. $line);
		return;
	}
	
	/**
	* 日志处理
	* jimmy: 结合debug类
	*/
	public function log($type, $message, $other=''){
		/* YEPF版本 */
		switch($type){
			case 'Error':
				\yoka\Debug::log('Mongo Err', $message , $other);
			break;
			case 'Run':
				//记录每次执行的时间消耗
				//wait ...
			break;
			case 'Log':
			default:
				\yoka\Debug::log('Mongo', $message , $other);
			break;
		}
		return;
		/* 非YEPF版本 */
		var_dump($type . $message . $other);
		return;
	}
	
	/* ------ 基础服务函数 -------- */
	protected  function open(){
		try {
			$this->mongodb_connection = new Mongo("mongodb://" . ($this->db_user ? "$this->db_user:$this->db_password@" : '') . $this->db_host);
		}catch (Exception $e){
			die("Mongodb Connection Fail:　<h3>"."mongodb://" . ($this->db_user ? "$this->db_user:$this->db_password@" : '') . $this->db_host."</h3>");    
		}        
		if($this->db_name){
			$this->selectDb($this->db_name);
			$this->is_close = false;
		}
		return $this->is_close;	
	}    
	protected function _select_collection($collection_name){
		if(!$this->db)die('Please assign db first!');
		$cos = $this->db->listCollections();  
		//debug if(!in_array($collection_name, $cos)) die('Can not find the table: $collection_name'); //禁止私建collection。请用MongoAdmin类。
		$collection = $this->db->selectCollection($collection_name);
		return $collection;
	}
	public function selectDb($db_name){
		$dbs = $this->mongodb_connection->listDBs();
		//debug if(!in_array($db_name, $dbs)) die('Can not find the DB: $db_name'); //禁止私建DB。强制使用MongoAdmin类进行结构管理
		$this->db = $this->mongodb_connection->selectDB($db_name);
		$this->_auth();
		return $this->db;
	}    
	protected  function _auth(){
		$result = $this->db->authenticate($this->db_user,$this->db_password);        
		return $result['ok'];
	}
	protected function _close(){
		if(!$this->is_close){
			$this->mongodb_connection->close();
			$this->is_close = true;    
		}
	}
	protected function _auto_connection_mongondb(){
		if($this->is_close){
			$this->open();        
		}
	}
	public function __destruct(){
		$this->_close();
	}
} //end MongoDb Class

/* -----------------------------------------------------------可爱的分割线------------------------------------------------------------- */

/**
 * 管理操作类（防止程序员操作失误造成数据损失）
 * @author jimmy.dong@gmail.com
 */
class MongoAdmin extends Mongodb
{
	public function listDbs(){
		$this->_auto_connection_mongondb();
		return $this->mongodb_connection->listDBs();	
	}
	public function listCollections($db_name){
		$this->_auto_connection_mongondb();
		if($db_name)$this->selectDb($db_name);
		if(!$this->db) die('listCollections: selectDb first!');
		return $this->db->listCollections();
	}
	public function listIndexes($collection_name){
		$this->_auto_connection_mongondb();
		$collection = $this->_select_collection($collection_name); 
		return $collection->getIndexInfo();
	}
	/**
	* 创建数据库
	* @param string $db_name 库名
	* @param boolean $force  强制: 0-如已存在终止 1-如已存在使用已存在 2-如已存在删除并新建
	*/
	public function newDb($db_name, $force=0){
		$this->_auto_connection_mongondb();
		if($force == 0)if(in_array($db_name, $this->listDbs()))die('newDb: $db_name already exists.');
		if($force == 2)$this->mongodb_connection->dropDB($db_name);
		return $this->mongodb_connection->selectDB($db_name);
	}
	/**
	* 删除数据库
	* @param string $db_name 库名
	* @param boolean $force  强制: 0-如不存在终止 1-简单删除并返回
	*/
	public function dropDb($db_name, $force=0){
		$this->_auto_connection_mongondb();
		if($force == 0)if(!in_array($db_name, $this->listDbs()))die('dropDb: $db_name not exists.');
		return $this->mongodb_connection->dropDB($db_name);
	}
	/**
	* 创建表
	* @param string $collection_name 表名
	* @param boolean $force  强制: 0-如已存在终止 1-如已存在使用已存在 2-如已存在删除并新建
	*/
	public function newCollection($collection_name, $force=0){
		$this->_auto_connection_mongondb();
		if(!$this->db)die('newCollection: selectDb first!');
		if($force == 0) if(in_array($collection_name, $this->listCollections())) die('newCollection: collection already exists!');
		if($force == 2) $this->db->dropCollection($collection_name);
		return $this->db->selectCollection($collection_name);
	}
	/**
	* 删除表
	* @param string $collection_name 表名
	* @param boolean $force  强制: 0-如不存在终止 1-简单删除并返回
	*/
	public function dropCollection($collection_name, $force=0){
		$this->_auto_connection_mongondb();
		if($force == 0) if(!in_array($collection_name, $this->listCollections())) die('newCollection: collection not exists!');
		$this->db->dropCollection($collection_name);
	}
	/**
	* 创建索引
	* 模拟二维表建立索引，暂不支持复杂数据结构
	* @param string collection_name 表名
	* @param mix collumn 						默认升序。或指定： 字段名=>升降序  eg:array('threadid'=>1, 'lastpost'=>-1) 主题升序、最后时间倒序
	* @param array	option					可选参数
	* @param boolean background 		执行后立即返回
	*	@param boolean safe 					返回详细信息
	* @param string  name						索引别名
	*/
	public function newIndex($collection_name, $collumn, $options=array('unique'=>0, 'dropDups'=>0, 'background'=>1, 'safe'=>0, 'name'=>'')){
		$this->_auto_connection_mongondb();
		$collection = $this->_select_collection($collection_name); 
		if(is_string($collumn))$collumn = array($collumn => 1);
		return $collection->ensureIndex($collumn, $option);
	}
	public function dropIndex($index_name){
		$this->_auto_connection_mongondb();
		$collection = $this->_select_collection($collection_name); 
		return $collection->deleteIndex($index_name);	
	}
	public function execute($code , $args){
		$this->_auto_connection_mongondb();
		if(!$this->db)die('newCollection: selectDb first!');
		return $this->db->execute($code, $args);
	}
	public function setProfile(){ return 'wait...'; }
	public function getProfile(){ return 'wait...'; }
	public function explain(){ return 'wait...'; }
	public function repair(){ return 'wait...'; }
}


/* -----------------------------------------------------------可爱的分割线------------------------------------------------------------- */

/**
 * GridFS操作类
 * 目标： 通过传入文件名或者URL来存储文件，通过文件名或URL取出文件
 * 困难： PHP的MongoGridFS并不友好，只能通过_ID操作，还需要再加一个表才行？不太爽。 referer: http://www.mongodb.org/display/DOCS/GridFS+Tools
 * 等有了好的办法再补充完善吧
 */
class MongoFile extends DbMongo
{
	public function putFile(){}
	public function getFile(){}
	public function updateFile(){}
	public function deleteFile(){}
	public function findFile(){}
	public function listFile(){}
}
?>