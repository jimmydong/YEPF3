<?php
namespace yoka;

use \Mongo as Mongo;
use \MongoCode as MongoCode;
use \MongoId as MongoId;
use \MongoLog;
use \MongoCursorException;
use \Exception;
use \yoka\Debug;

/**
 * 定义Mongo操作接口
 * @author jimmy.dong@gmail.com
 * 
 * 注意：
 * 1，依赖WORK-ENV的配置项: [MONGODB] ...
 * 2，注意数据库授权。eg:db.createUser({user:"yisheng",pwd:"yisheng@2015",roles:[{role:"dbAdmin",db:"health"},{role:"readWrite",db:"health"}]});
 *
 * 类型与传值：
	  	objectid: 字符串 OR MongoId
	  	string	: 字符串
	  	integer, float, double:  数字 OR 字符串
	  	datetime:	date('Y-m-d H:i:s') OR 毫秒时间戳 
	  	array	: ['key'=>'value', ...] 
	  	object	: $obj  OR  json_encode($obj)
 *	  	
 * 使用参考：
 *
		//增/删/改
		$obj = new \MONGODAO\Demo();
		$obj->enableGhost(); //允许动态新增字段
		$obj->name = 'test';
		$obj->save();
		$obj->name = 'new';
		$obj->save();
		//$obj->destroy();
		//$obj->remove(array('_id',$obj->getID()));
		
		//查
		$re = \MONGODAO\Demo::findOne(array('name'=>'new'));
		$info = $re->getEntity();
		var_dump(\MONGODAO\Demo::getEntityById($info['_id']));
		$rows = \MONGODAO\Demo::findAll();
		var_dump($rows);
 *
 */
interface MongoRecord
{
	/**
	 * 设置查询的超时间时间，默认是20000毫秒
	 * @param unknown_type $timeout
	 */
	public static function setFindTimeout($timeout);

	/**
	 *  执行查找操作，返回符合条件的第一个结果的指定字段
	 * @param unknown_type $query
	 * @param unknown_type $fields
	*/
	public static function findOne($query = array(), $fields = array());

	/**
	 * 删除符合条件的数据
	 * @param unknown_type $criteria
	 * @param unknown_type $options
	*/
	public static function remove($criteria = null,$options = array());

	/**
	 * 执行一个查询，返回一个查询结果的迭代器(游标)
	 * （可以当数组使用用for .. as  ...遍历，同时可以用count()方法获得记录总数)
	 * @param unknown_type $query
	 * @param unknown_type $fields
	 * @param unknown_type $options
	*/
	public static function find($query = array(), $fields = array(), $options = array());

	/**
	 * 执行一个查询，并将符合条件的数据做为对象数组返回
	 * Enter description here ...
	 * @param unknown_type $query
	 * @param unknown_type $fields
	 * @param unknown_type $options
	*/
	public static function findAll($query = array(), $fields = array(), $options = array());

	/**
	 * 获取一个查询符合条件的记录总数
	 * @param unknown_type $query
	*/
	public static function count($query = array());


	/**
	 * 批量更新符合query条件的数据，用fields中指定的字段值
	 * @param unknown_type $query  mongo  查询字段 		   array( filed1=>condition1,field2=>condition2,...)
	 * @param unknown_type $new_object  要更新的对象值必须继承自BaseMongoRecord，注：如果_id字段有值有可能导致更新失败
	*/
	public static function updateAll($query = array() , $new_object , $options = null);

}

/**
 * 接口实现：Mongo操作基础类
 * @author jimmy.dong@gmail.com
 *
 */
abstract class BaseMongoRecord implements MongoRecord
{
    public $attributes;
    protected $errors;
    private $new;

    public static $connfail = null;
    public static $database = null;			//字符串型
    public static $connection = null;			//MongoClient
    public static $findTimeout = 20000;		//缺省超时时间
    public static $default_findAll_limit = 20000;	//缺省最大返回记录数
    /**
     * Collection name will be generated automaticaly if setted to null.
     * If overridden in child class, then new collection name uses.
     *
     * @var string
     */
    protected static $collectionName = null;
    public $allowGhost = false; //默认不允许未定义的字段被存储

    /**
     * 定义schema信息，用来完善和约束字段
     * @var Array:
     */
    protected static $schema = null;

    public function __construct($attributes = array(), $new = true)
    {
    	if (!self::$connection){
    		if(self::$connfail){
    			\yoka\Debug::log('Error:connect to mongodb fail', \yoka\Conf::$ENV['MONGODB']);
    			exit;
    		}
    		try{
    			self::connect_mongodb();
    		}catch (Exception $ex){
    			//尝试重新连接
    			sleep(1);
    			try{
    				self::connect_mongodb();
    			}catch (Exception $ex){
    				\yoka\Debug::log('Error:connect to mongodb fail', \yoka\Conf::$ENV['MONGODB']);

    				//这里直接die是为了解决由于连接失败导致的autoload失败的无法正常显示，影响问题分析
    				//self::$connfail = true; //避免再次尝试
    				exit;
    			}
    		}
    	}

        $this->new = $new;
        $this->attributes = $attributes;
        $this->errors = array();

        if ($new)
            $this->afterNew();
    }

    /**
     * 连接mongodb
     * 防止重复inlude导致的多重连接，同一次请求尽可能在一个连接中处理
     * @rewrite jimmy.dong@gmail.com
     */
    public static function connect_mongodb()
    {
    	//初始化数据库连接
    	ini_set('mongo.native_long', 0);  //启用64位整数

    	\MongoLog::setLevel(\MongoLog::WARNING); // all log levels
    	//设置连接池大小（与php进程数一致) DEPRECATED in driver: 1.2.11
    	//\Mongo::setPoolSize(256);
    	if(\yoka\Conf::$ENV['MONGODB']['server']){
    		//replica格式
    		$connectionTimeoutMs = 3000;
    		$socketTimeoutMS = 5000;
    		self::$connection = new \MongoClient(\yoka\Conf::$ENV['MONGODB']['server'], array(
					"replicaSet"=>\yoka\Conf::$ENV['MONGODB']['replicaSet'],
					"readPreference"=>\MongoClient::RP_SECONDARY_PREFERRED,
					"db"=>\yoka\Conf::$ENV['MONGODB']['DATABASE'], 
					"username"=>\yoka\Conf::$ENV['MONGODB']['USER'], 
					"password"=>\yoka\Conf::$ENV['MONGODB']['PASS'],
					"connectTimeoutMS"=>$connectionTimeoutMs,
					"socketTimeoutMS"=>$socketTimeoutMS
			));
    	}elseif(\yoka\Conf::$ENV['MONGODB']['USER'] && \yoka\Conf::$ENV['MONGODB']['PASS']){
    		//单机+用户验证
    		self::$connection = new \MongoClient(
    				'mongodb://'.\yoka\Conf::$ENV['MONGODB']['HOST'].':'.\yoka\Conf::$ENV['MONGODB']['PORT'],
    				array("db"=>\yoka\Conf::$ENV['MONGODB']['DATABASE'], "username"=>\yoka\Conf::$ENV['MONGODB']['USER'], "password"=>\yoka\Conf::$ENV['MONGODB']['PASS'])
    		);
    	}else{
    		//单机免验证
    		self::$connection = new \MongoClient('mongodb://'.\yoka\Conf::$ENV['MONGODB']['HOST'].':'.\yoka\Conf::$ENV['MONGODB']['PORT']);
    	}
    	self::$database = \yoka\Conf::$ENV['MONGODB']['DATABASE'];
    	//启用从库读
    	self::$connection->setReadPreference( Mongo::RP_SECONDARY_PREFERRED );  //by jimmy.dong@gmail.com ??? Deprecated: Function Mongo::setSlaveOkay() is deprecated
    	return true;
    }

    /**
     * 设置是否对启用从库的查询，在复制集中默认为true,会自动将读操作路由到从库
     * @param unknown_type $ok
     */
    public function setSlaveOk($ok  = true){
        //废弃 by jimmy.dong@gmail.com return self::$connection->setSlaveOkay($ok);
    	if($ok){
    		return self::$connection->setReadPreference( Mongo::RP_SECONDARY_PREFERRED );
    	}else{
    		return self::$connection->setReadPreference( Mongo::RP_PRIMARY_PREFERRED );
    	}
    }

    /**
     * 获取是否对当前集合启用从库查询
     */
    public function getSlaveOkay(){
        //return self::$connection->getSlaveOkay();
        $re = self::$connection->getReadPreference();
        if($re['type']=='secondary') return true;
        else return false;
    }

    /**
     * 数据宽松：允许操作未经定义的字段
     */
    public function enableGhost(){
    	$this->allowGhost = true;
    	return true;
    }

    /**
     * 关闭数据宽松：不允许操作未经定义的字段
     * [默认为不允许]
     */
    public function disableGhost(){
    	$this->allowGhost = false;
    	return true;
    }

    /**
     * 验证数据是否符合预定义
     * @param string $update
     * @return boolean
     */
    public function validate($update=false)
    {
        $this->beforeValidation();
        $retval = $this->isValid($update);
        $this->afterValidation();
        return $retval;
    }
    
    /**
     * 获取字段定义
     */
    public static function getSchema(){
    	return static::$schema;
    }

    /**
     * 获取字段扩展定义
     */
    public static function getSchemaExt(){
    	return static::$schema_ext;
    }
    
    /**
     * id是否存在
     */
    public static function isExists($id){
    	if(self::findOne(array('_id'=>$id.''),array('_id'=>1))){
    		return true;
    	}else{
    		throw new \Exception(__CLASS__.'->'.__METHOD__." {$id}不存在!");
    	}
    }
    
    /**
     * 取信息
     */
    public function getEntity()
    {
    	if($this->attributes){
    		$re = $this->attributes;
    		//格式转换
    		if($schema = self::getSchema()){
    			foreach($schema as $item=> $type){
    				if($type == 'objectid'){
    					$re[$item] = strval($re[$item]);
    				}elseif($type == 'datetime'){
    					$re[$item] = self::getDateTime($re[$item]);
    				}else{
    					$re[$item] = settype($re[$item], $type);
    				}
    			}
    		}else{
    			//没有schema，默认转换_ID
    			if($id = $this->getId()){
    				$re['_id'] = $id;
    			}
    		}
    		return $re;
    	}else{
    		return false;
    	}
    }
    
    /**
     * 根据Id取信息
     */
    public static function getEntityById($id){
    	if($obj = self::findOne(array('_id'=>self::toMongoId($id)))){
    		return $obj->getEntity();
    	}else{
    		return false;
    	}
    	
    }
    
    /**
     * 毫秒转时间
     * @param $ms 毫秒时间戳
     * @param $full 是否返回完整时间。默认：false
     */
    public static function getDateTime($ms , $full = false){
    	$re = [];
    	$re['timestamp'] = intval($ms / 1000);
    	$re['ms'] = $ms - $re['timestamp'] * 1000;
    	$re['datetime'] = date('Y-m-d H:i:s', $re['timestamp']);
    	if($full) return $re;
    	else return $re['datetime'];
    	
    }
    
    /**
     * 保存一个对象的变更到数据库中
     * 如果相同_id的对象已经存在则执行更新操作覆盖数据库的记录
     * 如果没有设置 _id，则会插入新记录，并将新插入自动生成的_id保存到当前对象上
     * @param array $options
     * @param array $options 选项详情：
     		是否安全插入 safe:true,false
    		是否同步到硬盘 fsync:true,false
    		超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
     * @param boolean $insertonly 是否只执行插入操作。当设置为true时，如果当前数据的_id已经在数据库中，则会抛出异常 (仅当options的safe为true时才会获得异常)
     */
    public function save(array $options = array(),$insert_only = false)
    {
        try{
            if (!$this->validate())
                return false;

            $begin_microtime = \yoka\Debug::getTime();
            $this->beforeSave();

            $collection = self::getCollection();

            if ( $insert_only ){
                $ret = $collection->insert( $this->attributes,  $options );
            }else{
                $ret = $collection->save(  $this->attributes,  $options );
            }
            \yoka\Debug::log('attributes', $this->attributes);
            //added by xwarrior to check insert update and logging at 2012/6/7
            if ( is_array($ret)  ){
                if (isset($ret['err']) && $ret['err'] != null ){
                    \yoka\Debug::log( 'Error' , 'insert ' . self::$collectionName . ' fail,mongo error code:'  . $ret['code'] . ' mongo errormsg:' . $reg['errmsg'] );
                    return false;
                }
            }else{
                if ( $ret == false){
                    \yoka\Debug::log( 'Error' , 'insert doc to ' . self::$collectionName . ' fail!');
                    return false;
                }
            }
            $this->new = false;
            $this->afterSave();

            //add performence log xwarrior  2012/8/8
            self::log_query('save',$this->attributes, $options, $collection, $begin_microtime,$ret);
        }catch(\Exception $err){
            //注意： 到这里通常都是因为传入非UTF8字符造成的！
            if(preg_match('/non-utf8/i',$err->getMessage())){
                //处理非UTF8字符导致出错
                foreach($this->attributes as $key=>$val){
                    if(mb_detect_encoding($str) == 'UTF-8')continue;
                    else $this->attributes[$key] = iconv('GBK','UTF-8//IGNORE',$val);
                }
                try{
                    $collection->save($this->attributes, $options);
                }catch(\Exception $err){
                    \yoka\Debug::log('Error',$err->getMessage());
                    throw $err;
                    return false;
                }
            }else{
                \yoka\Debug::log('Error',$err->getMessage());
                throw $err;
                return false;
            }
        }
        return true;
    }

    /**
     * 保存为新元素 参数见save
     * @param array $options
     * @throws Exception
     * @return boolean
     */
    public function add(array $options = array())
    {
    	unset($this->attributes['_id']);//作为新元素存储
    	try{
    		if (!$this->validate())
    			return false;

    		$begin_microtime = \yoka\Debug::getTime();
    		$this->beforeSave();
    		$collection = self::getCollection();
    		$ret = $collection->insert( $this->attributes,  $options );
    		//\yoka\Debug::log('attributes', $this->attributes);
    		//added by xwarrior to check insert update and logging at 2012/6/7
    		if ( is_array($ret)  ){
    			if (isset($ret['err']) && $ret['err'] != null ){
    				\yoka\Debug::log( 'Error' , 'insert ' . self::$collectionName . ' fail,mongo error code:'  . $ret['code'] . ' mongo errormsg:' . $reg['errmsg'] );
    				return false;
    			}
    		}else{
    			if ( $ret == false){
    				\yoka\Debug::log( 'Error' , 'insert doc to ' . self::$collectionName . ' fail!');
    				return false;
    			}
    		}
    		$this->new = false;
    		$this->afterSave();

    		//add performence log xwarrior  2012/8/8
    		self::log_query('add',$this->attributes, $options, $collection, $begin_microtime,$ret);
    	}catch(\Exception $err){
    		//注意： 到这里通常都是因为传入非UTF8字符造成的！
    		if(preg_match('/non-utf8/i',$err->getMessage())){
    			//处理非UTF8字符导致出错
    			foreach($this->attributes as $key=>$val){
    				if(mb_detect_encoding($str) == 'UTF-8')continue;
    				else $this->attributes[$key] = iconv('GBK','UTF-8//IGNORE',$val);
    			}
    			try{
    				$collection->insert($this->attributes, $options);
    			}catch(\Exception $err){
    				\yoka\Debug::flog('BaseMongo Error',$err->getMessage());
    				throw $err;
    				return false;
    			}
    		}else{
    			\yoka\Debug::flog('BaseMongo Error',$err->getMessage());
    			throw $err;
    			return false;
    		}
    	}
    	return true;
    }

    /**
     * 把_id按递增数字存储
     * @param array $options  同save的选项
     *            是否安全插入 safe:true,false
    是否同步到硬盘 fsync:true,false
    超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
     * @return 保存成功，返回true
     */
    public function numsave(array $options = array())
    {
        $max_error_limit = 100;

        $options['safe'] = true;

        $maxiddoc	=	self::findAll(	array(),
            array( '_id' ),
            array( 'sort' => array('_id' => -1) ,
                   'limit' => 1)
        );
        if( $maxiddoc && count($maxiddoc) > 0 ){

            $this->_id	=	intval( $maxiddoc[0]->_id ) + 1;
        }else{
            $this->_id	=	1;
        }

        $i = 0;
        while( $i < $max_error_limit){
            try {

                $success = $this->save( $options,true );
            } catch (MongoCursorException $e) {
                if ( $e->getCode() == 11000){ //continue when exception is :key duplicate
                    if ($i >= $max_error_limit){
                        throw new Exception('递增插入主键失败，超过最大冲突尝试次数');
                    }
                    $success = false;
                    $this->_id += 1;
                }else{
                    throw $e;
                }
            }
            if( $success ){
                return true;
            }
            $i++;
        }

        return false;
    }

    /**
     * 获得当前对象的数组表示形式
     */
    public function toArray(){
        return $this->attributes;
    }

    /**
     * 从库中删除当前对象
     */
    public function destroy($options = array())
    {
    	$begin_microtime = \yoka\Debug::getTime();
        $this->beforeDestroy();

        if (!$this->new)
        {
            $collection = self::getCollection();
            $re = $collection->remove(array('_id' => $this->attributes['_id']),$options);
        }else{
            $re = false;
        }
        self::log_query('destroy',$criteria, $options, $collection, $begin_microtime,$re);
        return $re;
    }

    /**
     * 删除当前集合符合查询条件的数据
     * @see http://cn.php.net/manual/en/mongocollection.remove.php
     * @param   $criteria 要删除的查询条件
     * @param   $options 删除选项
     */
    public static function remove($criteria = null,$options = array()){
        $begin_microtime = \yoka\Debug::getTime();
        if ( $criteria == null ){
            throw new Exception('$criteria 未提供，禁止无条件删除。[参考drop]');
        }

        $collection = self::getCollection();
        $ret =  $collection->remove( $criteria ,$options);

        self::log_query('remove',$criteria, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }

    /**
     * 删除当前集合
     */
    public static function drop ( $are_you_sure = 'no' )
    {
    	//保护：防止误删除
    	if($are_you_sure != 'yes'){
    		return false;
    	}
    	$collection = self::getCollection();
    	$collection->drop();
    }

    /**
     * 获取当前集合的名称
     */
    public function getName(){
        if (null  !== static::$collectionName)
        {
            $collectionName = static::$collectionName;
        }
        else
        {
            $className = get_called_class();
            //hack by jimmy.dong@gmail.com
            $t = explode('\\',$className);
            $real_className = array_pop($t);
            $collectionName = self::tableize($real_className);
            self::$collectionName = $collectionName;
        }
        return $collectionName;
    }


    /**
     * 执行一个查询，并返回所有的文档结果数组
     * @param   $query   查询条件  array( field1=>condition,field2=>array($op=>condition),field3...)
     * @param   $fields  查询字段  array( 'field1','field2',... )
     * @param   $options  查找选项 array ( sort=>array(field1=>1,field2=>-1,...),skip=>int,limit=>int )
     */
    public static function findAll($query = array(), $fields = array(), $options = array())
    {
        $begin_microtime = \yoka\Debug::getTime();

        if ( null === $query ){
            $query == array();
        }
        if ( null === $fields){
            $fields = array();
        }
        if ( null === $options  ){
            $options = array('limit'=> self::$default_findAll_limit);
            $use_default_limit = true;
        }

        $query = self::merge_in($query);
        $query = self::prepareQuery($query);

        $collection = self::getCollection();

        if( NULL !=$fields && count( $fields ) > 0){
            $documents = $collection->find($query,$fields);
        }else{
            $documents = $collection->find($query);
        }

        $className = get_called_class();

        if (isset($options['sort']))
            $documents->sort($options['sort']);

        if (isset($options['skip']))
            $documents->skip($options['skip']);

        if (isset($options['limit']))
            $documents->limit($options['limit']);

        if (isset($options['asArray']) && $options['asArray'] == 1){
            $flag_as_array = true;
        }else $flag_as_array = false;

        $ret = array();
        $documents->timeout($className::$findTimeout);

        //mongodb fetrue :when set batchsize,cusor will close after read batchsize rows
        if (isset($options['limit'])) {
            $documents->batchSize($options['limit']);  //optimize for read performence
        }

        while ( ($document = $documents->getNext()) != null)
        {
            if($flag_as_array)
                $ret[] = $document;
            else
                $ret[] = self::instantiate($document);
        }
        if($use_default_limit && count($ret) == self::$default_findAll_limit){
        	Debug::log('Warning: findAll meet max limit!','set limit youself, or use `find` method');
        }
        self::log_query('findAll',$query, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }
    /**
     * find别名函数
     */
    public static function fetchAll($query = array(), $fields = array(), $options = array()){
    	return self::findAll($query, $fields, $options);
    }

    /**
     * 合并in查询中的重复条件
     * xwarrior 2012/8/8
     * @param unknown_type $query
     */
    private static function merge_in($query){
        foreach($query as $key => &$value){
            if ( is_array($value) && isset( $value['$in'] ) ){
                $value['$in'] = array_unique(  $value['$in'] );
            }
        }
        return $query;
    }

    /**
     * 按照预定义进行转义处理
     * 【注意】目前仅处理简单的键值对关系
     * @param unknown $query
     */
    private static function prepareQuery($query){
    	$schema = self::getSchema(); 
    	$schemaExt = self::getSchemaExt();
    	foreach($query as $k=>$v){
    	 	//简单的 col=>value 格式
    		if($schemaExt[$k]['jugglin']){
    	 		$type = $schema[$k];
    	 		switch($type){
    	 			case 'string':
    	 			case 'integer':
    	 			case 'float':
    	 			case 'double':
    	 				settype($query[$k], $type);
    	 				break;
    	 			case 'datatime':
    	 				if(! is_numeric($v)) $query[$k] = strtotime($v) * 1000;
    	 				break;
    	 			default:
    	 				//TODO:: 数组的处理
    	 				break;
    	 		}
    	 	}
    	 	//TODO:: 待处理 '$lt','$gt','$in' ...
    	}
    	return $query;
    }
    
    /**
     * 记录查询日志
     * xwarrior @ 2012/8/8
     * @param unknown_type $query
     * @param unknown_type $options
     * @param unknown_type $collection
     * @param unknown_type $begin_microtime
     */
    private  static function  log_query($method,$query,$options,$collection,$begin_microtime,$ret){
        $stacktrace = '';

        /* TODO: 根据日志级别决定是否显示调用堆栈 */
        $stack_list = debug_backtrace() ;
        $stacks = array();
        foreach($stack_list as $stack){
            $stacks[] = $stack['file']  . ':' . $stack['line'];
        }
        $stacktrace = implode(' <-- ',$stacks);
        $colname = '';
        if( is_string($collection) ){
        	$colname = $collection;
        }else{
            $colname = $collection->getName();
        }
        $logquery = '[' . $method . '] query:' . json_encode( $query, JSON_UNESCAPED_UNICODE ) . ',  options:'.  json_encode($options)  . "	###stack### $stacktrace";
        \yoka\Debug::db('mongodb://' . BaseMongoRecord::$connection ,  BaseMongoRecord::$database . ':' . $colname ,$logquery, Debug::getTime() - $begin_microtime, $ret);

    }



    /**
     * 给collection中的某个字段+1
     * @param   $query     查询条件  array( field1=>condition,field2=>array($op=>condition),field3...)
     * @param   $fields    需要增加数值的字段  string OR array( 'field1','field2',... )
     *
     * @return  bool
     */
    public static function inc( $query	=	array(),$fields	=	array()	,$incnum	=	1,	$upsert = false,	$safe = false){
        if ( null === $query ){
            return false;
        }
        if ( null === $fields){
            return false;
        }
        $begin_microtime = \yoka\Debug::getTime();
        $collection = self::getCollection();
        $options = array( 'upsert' => $upsert, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

        /* inc不需要执行先查询再更新，如果不想没数据的时候插入，设置$upsert=false即可    xwarrior 2012/8/8
       if( NULL !=$fields){
           $documents = $collection->findOne($query);
       }else{
           return false;
       }
       */
        if(is_array($fields)){
            foreach($fields as $v){
                $new_fields[$v]	=	$incnum;
            }
        }else{
            $new_fields[$fields] = $incnum;
        }

        $addok	=	$collection->update($query,  array( '$inc' => $new_fields),$options);

        //add performence log xwarrior  2012/8/8
        self::log_query('inc',$query, $options, $collection, $begin_microtime,$addok);
        return $addok;

    }
    /**
     * 求collection中某字段的和	相当于mysql 的 sum
     * @param   $group_by		用来group by 的字段数组  array(id => true,name => true)
     * @param   $where			查询条件  常规的where数组  array('user_id' => '1260858')
     * @param   $sub_columns	可选，默认1时求count，不为1就只能设为integer类型的字段名称的字符串或数组,
     *                          如:  'field_name' or array('field_name_1','field_name_2')
     * @return  array			Array ( [retval] => Array ( [0] => Array ( [user_id] => 1260858 [count] => 6 ) ) [count] => 2 [keys] => 1 [ok] => 1 )
     */
    public static function sum($group_by = NULL,$where = NULL,$sub_columns=1){
        if ( $group_by == NULL && $sub_columns == 1 ){
            $ret =  self::count($where);
            return $ret;
        }

        $begin_microtime = \yoka\Debug::getTime();
        $collection = self::getCollection();
        //added by xwarrior at 2012/4/21 for suport no field to group by
        if( $group_by == NULL ){
            $group_by= new MongoCode('function(doc) { return {any:1}; }');
        }
		//by jimmy.dong@gmail.com 注意： 数值 + null = NaN。必须保证数字累加。
        if ( is_array($sub_columns)){  //added by xwarrior for suport multi field sum
            $sum = 'prev.count +=1 ;';
            $initial = array('count' => 0);
            foreach( $sub_columns as $field_name  ){
                $initial[ $field_name ] = 0;
                $sum .= "if( doc.$field_name * 1 == doc.$field_name )prev.$field_name += doc.$field_name;";
            }
            $reduce = new MongoCode("function(doc, prev) { $sum }");
        }else if ($sub_columns==1){
            $initial =  array('count' => 0);
            $reduce = new MongoCode('function(doc, prev) { prev.count +=1; }');
        }else{
            $initial =  array('count' => 0,'sum' => 0);
            $reduce = new MongoCode(  'function(doc, prev) { prev.count +=1 ; if( doc.'.$sub_columns.' * 1 == doc.'.$sub_columns.')prev.sum += doc.'.$sub_columns.'; }' );
        }

        $ret =	$collection->group(	$group_by,		// fields to group by
            $initial,								// initial value of the aggregation counter object.
            $reduce,								// a function that takes two arguments (the current document and the aggregation to this point) and does the aggregation
            array('condition'=>$where)				// condition for including a document in the aggregation
        );

        //add performence log xwarrior  2012/8/8
        if(!$where){
            $where = array();
        }
        if(!$group_by){
            $group_by = array();
        }
        self::log_query('sum',$where, array(), $collection, $begin_microtime,$ret);

        return $ret;

    }

	/**
     * mapReduce分组
     *
     * @param string $map 映射函数(生成键值对序列,作为 reduce 函数参数)
     * @param string $reduce 统计处理函数
     * @param array  $query 过滤条件 如：array('uid'=>123)
     * @param array  $sort 排序
     * @param number $limit 限制的目标记录数
     * @param string $out 统计结果存放集合 (不指定则使用tmp_mr_res_$table_name, 1.8以上版本需指定)
     * @param bool   $keeptemp 是否保留临时集合
     * @param string $finalize 最终处理函数 (对reduce返回结果进行最终整理后存入结果集合)
     * @param string $scope 向 map、reduce、finalize 导入外部js变量
     * @param bool   $jsMode 是否减少执行过程中BSON和JS的转换，默认true(注：false时 BSON-->JS-->map-->BSON-->JS-->reduce-->BSON,可处理非常大的mapreduce,//true时BSON-->js-->map-->reduce-->BSON)
     * @param bool   $verbose 是否产生更加详细的服务器日志
     * @return array
     */
    function mapReduce($map, $reduce, $query=null, $sort=null, $limit=null, $out=null, $keeptemp=false, $finalize=null, $scope=null, $jsMode=true, $verbose=false){
    	$begin_microtime = \yoka\Debug::getTime();
    	$table_name = $this->getName();

    	$map = new MongoCode($map);
    	$reduce = new MongoCode($reduce);
    	if(!$out){
    		$out = 'tmp_mr_res_'.$table_name;
    	}
    	$cmd = array(
    			'mapreduce' => $table_name,
    			'map'       => $map,
    			'reduce'    => $reduce,
    			'out'		=> $out,
    			'keeptemp'	=> true
    	);
    	if(is_array($query)){
    		$cmd['query']=$query;
    	}
    	if(is_array($sort)){
    		$cmd['sort']=$sort;
    	}
    	if($limit && $limit>0){
    		$cmd['limit']=$limit;
    	}
    	if($finalize){
    		$finalize = new Mongocode($finalize);
    		$cmd['finalize']=$finalize;
    	}
    	if($scope){
    		$cmd['scope']=$scope;
    	}
    	if(!empty($jsMode) && is_bool($jsMode)){
    		$$cmd['jsMode']=$jsMode;
    	}
    	if(!empty($verbose) && is_bool($verbose)){
    		$cmd['verbose']=$verbose;
    	}
    	\yoka\Debug::log('map-reduce cmd', $cmd);
    	$mongodb = self::getDatabase();
    	$cmdresult = $mongodb->command($cmd);
    	\yoka\Debug::log('map-reduce result', $cmdresult);
   		if($cmdresult && $cmdresult['ok']==1){
   			$collection = $mongodb->selectCollection($out);
   			$cursor = $collection->find(array());
   			foreach($cursor as $document){
				$ret[] = $document;
   			}
   		}
    	if($keeptemp==false){
    		//删除集合
    		//$mongodb->dropCollection($out);
    	}
    	self::log_query('map-reduce',$query, $table_name, $collection, $begin_microtime,$ret);
    	return $ret;
    }

    /**
     * 关联多个集合
     * @param   $coll_a	=	array(	'column',		array('a'=>'b')集合A中需要查出的列,key为数据库查询字段，val为查处数据字短
    'where',		集合A的查询条件
    'join_a',		向后关联时用的字段
    'join_type',	集合A关联后面数据的方法 left/inner/none
    )
     * @param   $coll_b	=	array(	'collection'	集合B的名称,key为数据库查询字段，val为查处数据字短
    'column',		array('a'=>'b')集合B中需要查出的列，前面为数据库字段名，后面为希望的名称
    'where',		集合B的查询条件
    'join_b',		向前关联时用的字短
    'join_a',		向后关联时用的字短
    'join_type',	集合B关联后面数据的方法 left/inner/none  左关联后面数据/inner关联后面数据/后面不关联数据
    'join_next'		下一个需要关联的集合
    )
    )
    注意　coll_b 的ｃｏｌｕｍｎ不能为＊，必须为数组array('a'=>'b')格式
    　　　ｃｏｌｌｅｃｔｉｏｎ　　需要带命名空间
    　多个集合关联时，集合ａ关联到集合Ｃ的字段时，集合Ｂ中的ｊｏｉｎ＿ｂ应写为ｊｏｉｎ＿ｃ中字段对应的新名称，不应为ｊｏｉｎ＿ｃ的数据库字段


     * @return  和findAll返回相同
     */
    public static function ajoinb($coll_a,$coll_b){
        //递归调用多个集合
        if($coll_b['join_type']	==	'left' || $coll_b['join_type']	==	'inner'){
            $b_data	=	$coll_b['collection']::ajoinb($coll_b,$coll_b['join_next']);
            return	$b_data;
        }else{
            $b_data	=	$coll_b['collection']::findAll($coll_b['where'],array_keys($coll_b['column']));
        }

        if($coll_a['join_type'] == 'inner'){
            $in_array	=	array();
            $data_array	=	array();
            if($b_data){
                foreach($b_data	as $val){

                    if($coll_b['join_b'] == '_id'){
                        $in_array[]	=	strval($val->attributes[$coll_b['join_b']]);
                        $b_data_array[strval($val->attributes[$coll_b['join_b']])]	=	$val->attributes;
                    }else{
                        $in_array[]	=	$val->attributes[$coll_b['join_b']];
                        $b_data_array[$val->attributes[$coll_b['join_b']]]	=	$val->attributes;
                    }
                }
            }
            $coll_a['where'][$coll_a['join_a']]	=	array('$in'=>$in_array);

            if($coll_a['column'][0] == '*'){
                $a_data	=	self::findAll($coll_a['where'],array());
            }else{
                $a_data	=	self::findAll($coll_a['where'],array_keys($coll_a['column']));
            }

            if($a_data){
                foreach($a_data as &$val){
                    foreach($coll_b['column'] as $k=>$v){
                        if($k == '_id'){
                            $val->attributes[$v]	=	strval($b_data_array[$val->attributes[$coll_a['join_a']]][$k]);
                        }else{
                            $val->attributes[$v]	=	$b_data_array[$val->attributes[$coll_a['join_a']]][$k];
                        }
                    }
                    if($coll_a['column'][0] != '*'){
                        foreach($coll_a['column'] as $k=>$v){
                            $val->attributes[$v]	=	$val->attributes[$k];
                        }
                    }
                }
            }
        }elseif($coll_a['join_type'] == 'left'){
            if($coll_a['column'][0] == '*'){
                $a_data	=	self::findAll($coll_a['where'],array());
            }else{
                $a_data	=	self::findAll($coll_a['where'],array_keys($coll_a['column']));
            }
            if($b_data){
                foreach($a_data as &$val){
                    if($coll_a['column'][0] != '*'){
                        foreach($coll_a['column'] as $kc=>$vc){
                            $val->attributes[$vc]	=	$val->attributes[$kc];
                        }
                    }
                    if($coll_b['join_b'] == '_id'){
                        foreach($b_data as $k=>$v){
                            if(isset($val->attributes[$coll_a['join_a']]) && isset($v->attributes[$coll_b['join_b']]) && $val->attributes[$coll_a['join_a']] == strval($v->attributes[$coll_b['join_b']])){
                                foreach($coll_b['column'] as $ka=>$va){
                                    if($ka == '_id'){
                                        $val->attributes[$va]	=	strval($v->attributes[$ka]);
                                    }else{
                                        $val->attributes[$va]	=	$v->attributes[$ka];
                                    }
                                }
                                break;
                            }
                        }
                    }else{
                        foreach($b_data as $k=>$v){
                            if(isset($val->attributes[$coll_a['join_a']]) && isset($v->attributes[$coll_b['join_b']]) && $val->attributes[$coll_a['join_a']] == $v->attributes[$coll_b['join_b']]){
                                foreach($coll_b['column'] as $ka=>$va){
                                    if($ka == '_id'){
                                        $val->attributes[$va]	=	strval($v->attributes[$ka]);
                                    }else{
                                        $val->attributes[$va]	=	$v->attributes[$ka];
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }else{
                foreach($a_data as &$val){
                    if($coll_a['column'][0] != '*'){
                        foreach($coll_a['column'] as $kc=>$vc){
                            $val->attributes[$vc]	=	$val->attributes[$kc];
                        }
                    }
                }
            }
        }
        if($a_data){
            return $a_data;
        }else{
            return false;
        }
    }

    /**
     * leftjoin 左关联
     * @param   $data_list		基数据  findAll返回结果
     * @param   $join_column	关联时的左右字段	目前只有一个	array('create_user_id' => array('column' => '_id','mongoid' => false))
     * @param   $columns		需关联进来的集合字段  array( '_id' => 'mag_id')
     * @param   $where			集合数据查询条件
     * @return  和findAll返回相同
     * 还需做的事情 join_column 需要能按 array('mag_id' => '_id','or'=>array(),'and'=>array()) 处理
    用法$products	=	ActiProd::findAll(array('product_code'=>array('$in' => $product_ids)),array());
    $products	=	Activity::leftjoin($products,array('activity_id'=>'_id'),array('_id' => 'act_id'),array());
     */
    public static function leftjoin($data_list,$join_column = array(),$columns = array(),$where = array()){
        if(!$data_list)
            return false;
        $in_array	=	array();
        $join_a		=	key($join_column);
        $join_b		=	current($join_column);
        if($join_b['mongoid']){
            foreach($data_list as $val){
                $in_array[]	=	new MongoId($val->$join_a);
            }
        }else{
            foreach($data_list as $val){
                $in_array[]	=	$val->$join_a;
            }
        }
        $where[$join_b['column']]	=	array('$in' => $in_array);
        $data_b		=	self::findAll($where,array_keys($columns));
        $in_data	=	array();
        if($data_b ){
            foreach($data_b as $v){
                if(is_object($v->$join_b['column'])){
                    $in_data[$v->$join_b['column']->{'$id'}]	=	$v;
                }else{
                    $in_data[$v->$join_b['column']]	=	$v;
                }
            }
        }
        foreach($data_list as &$val){
            if($val->$join_a){
                if(!is_object($val->$join_a)){
                    foreach($columns as $k=>$v){
                        if(isset($in_data[$val->$join_a])){
                            $val->attributes[$v]	=	$in_data[$val->$join_a]->$k;
                        }
                    }
                }else{
                    foreach($columns as $k=>$v){
                        if(isset($in_data[strval($val->$join_a)])){
                            $val->attributes[$v]	=	$in_data[strval($val->$join_a)]->$k;
                        }
                    }
                }
            }
        }
        return $data_list;
    }

    /**
     * innerjoin 关联
     * @param   $data_list		基数据  findAll返回结果
     * @param   $join_column	关联时的左右字段	目前只有一个
     * @param   $columns		需关联进来的集合字段  array( '_id' => 'mag_id')
     * @param   $where			集合数据查询条件
     * @param   $sort_limit		排序，分页等
     * @return  和findAll返回相同
     * 还需做的事情 join_column 需要能按 array('mag_id' => '_id','or'=>array(),'and'=>array()) 处理
    用法$products	=	ActiProd::findAll(array('product_code'=>array('$in' => $product_ids)),array());
    $products	=	Activity::innerjoin($products,array('activity_id'=>'_id'),array('_id' => 'act_id'),array());
     */
    public static function innerjoin($data_list,$join_column = array(),$columns = array(),$where = array(),$sort_limit = array()){
        if(!$data_list)
            return false;
        $in_array	=	array();
        $join_a		=	key($join_column);
        $join_b		=	current($join_column);
        if($join_b['mongoid']){
            foreach($data_list as $val){
                $in_array[]	=	new MongoId($val->$join_a);
            }
        }else{
            foreach($data_list as $val){
                $in_array[]	=	$val->$join_a;
            }
        }
        $where[$join_b['column']]	=	array('$in' => $in_array);
        $data_b		=	self::findAll($where,array_keys($columns),$sort_limit);
        $in_data	=	array();
        if($data_b ){
            foreach($data_b as $v){
                if(is_object($v->$join_b['column'])){
                    $in_data[$v->$join_b['column']->{'$id'}]	=	$v;
                }else{
                    $in_data[$v->$join_b['column']]	=	$v;
                }
            }
        }
        foreach($data_list as $key=>&$val){
            if($val->$join_a){
                if(!is_object($val->$join_a)){
                    foreach($columns as $k=>$v){
                        if(isset($in_data[$val->$join_a])){
                            $val->attributes[$v]	=	$in_data[$val->$join_a]->$k;
                        }else{
                            unset($data_list[$key]);
                        }
                    }
                }else{
                    foreach($columns as $k=>$v){
                        if(isset($in_data[strval($val->$join_a)])){
                            $val->attributes[$v]	=	$in_data[strval($val->$join_a)]->$k;
                        }else{
                            unset($data_list[$key]);
                        }
                    }
                }
            }else{
                unset($data_list[$key]);
            }
        }
        return $data_list;
    }



    /**
     * 执行一个查询，返回查询游标
     * @param   $query    查询条件 array( field1=>condition,field2=>array($op=>condition),field3...)
     * @param   $fields     查询字段 array( 'field1','field2',... )
     * @param   $options   查找选项 array ( sort=>array,skip=>int,limit=>int )
     */
    public static function find($query = array(), $fields = array(), $options = array())
    {
        $begin_microtime = \yoka\Debug::getTime();
        $collection = self::getCollection();

        $query = self::merge_in($query);
        $query = self::prepareQuery($query);
        
        if( NULL !=$fields && count( $fields ) > 0){
            $documents = $collection->find($query,$fields);
        }else{
            $documents = $collection->find($query);
        }

        $className = get_called_class();

        if (isset($options['sort']))
            $documents->sort( $options['sort'] );

        if (isset($options['skip']))
            $documents->skip($options['skip']);

        if (isset($options['limit']))
            $documents->limit($options['limit']);

        $documents->timeout($className::$findTimeout);

        $ret = new MongoRecordIterator($documents, $className);

        //add performence log xwarrior  2012/8/8
        self::log_query('find',$query, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }

    /**
     * 查找一条单独的记录，如果有多条结果，只返回第一条
     * @param array $query   查询条件array( field1=>condition1,field2=>condition2);
     * @param array $fields　  查询字段列表 array('fild1','field2')
     * @return 数据对象，未查找到返回null
     */
    public static function findOne($query = array(), $fields = array())
    {

        $begin_microtime = \yoka\Debug::getTime();

        $query = self::merge_in($query);
        $query = self::prepareQuery($query);
        
        $collection = self::getCollection();
        if( $fields != null && count( $fields) > 0 ){
            $document = $collection->findOne($query,$fields);
        }else{
            $document = $collection->findOne($query);
        }
        $ret =  self::instantiate($document);

        //add performence log xwarrior  2012/8/8
        self::log_query('findOne',$query, array(), $collection, $begin_microtime,$ret);

        return $ret;
    }
    /**
     * findOne别名函数
     * @param array $query   查询条件array( field1=>condition1,field2=>condition2);
     * @param array $fields　  查询字段列表 array('fild1','field2')
     * @return 数据对象，未查找到返回null

     */
    public static function fetchOne($query = array(), $fields = array()){
    	return self::findOne($query, $fields);
    }

    /**
     * 查找指定id的数据
     * @param string or objectid or array $arr_ids  要查找的id数组(string or MongoId) 如: array('4f92a1768749160f74000001' ,  '4f92a1768749160f74000001' ,  '4f92a1768749160f74000001')
     * @param array $other_query_condtion   ids之外的其它查询条件  如 : array( 'visibility' => 0  , owner_id => '2233334' )
     * @param $conver_to_key_value 是否转换为array( _id=>array() ,_id=array())的关联数组
     * @param   $fields    查询字段  array( 'field1','field2',... )
     * @param   $options  查找选项 array ( sort=>array(field1=>1,field2=>-1,...),skip=>int,limit=>int )
     * @return 返回符合条件文档的数组结果
     */
    public static function findByIds($arr_ids = array(),$other_query_condtion = array() , $conver_to_key_value = false,
                                     $fields = array(), $options = array()){
        $begin_microtime = \yoka\Debug::getTime();
        if ( count($arr_ids) == 0 ){
            return array();
        }
        $or_ids = array();

        foreach($arr_ids as $id){

            if ( $id instanceof MongoId  ){
                $_id = $id;
            }else{
                $_id = new MongoId( strval($id) );  //try convert to mongoid
                if (  strval($_id) !=  $id){
                    $_id = $id;
                }
            }
            $or_ids[] =  $_id;
        }
        $query = array( '_id' => array( '$in' => $or_ids ) );
        if ( $other_query_condtion ){
            foreach($other_query_condtion as $key => $value ){
                $query[$key] = $value;
            }
        }
        $ret = self::findAll($query,$fields,$options);
        //转换为key,value关联数组
        if ( $ret && $conver_to_key_value ){
            $newret = array();
            foreach($ret as $item){
                $newret[ $item->getId() ] = $item;
            }
            $ret =  $newret;
        }

        //add performence log xwarrior  2012/8/8
        self::log_query('findByIds',$query, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }


    /**
     * 获取指定查询的记录总数
     * @param unknown_type $query
     */
    public static function count($query = array())
    {
        $begin_microtime = \yoka\Debug::getTime();
        $collection = self::getCollection();
        $documents = $collection->count($query);
        $ret =  $documents;

        //记录sql执行时间  xwarrior 2012/8/10
        self::log_query('count',$query, array(), $collection, $begin_microtime,$ret);
        return $ret;
    }

    private static function instantiate($document)
    {
        if ($document)
        {
            $className = get_called_class();
            return new $className($document, false);
        }
        else
        {
            return null;
        }
    }

    /**
     * 获取对象主键字符串形式的值
     */
    public function getId()
    {
        if ( isset( $this->attributes['_id'] ) ){
            return strval( $this->attributes['_id'] );
        }else{
            return NULL;
        }
    }

    /**
     * 获取对象主键的值 MongoId,当前没有主键则返回null
     * @param $_id  可以为MongoId或字符串，如果字符串代表的是MongoId,则必须初始化为MongoId
     */
    public function setId($_id)
    {

        $this->attributes['_id'] = $_id;
    }

    /**
     * 允许$->获取属性值
     * @param unknown_type $property_name
     */
    public function __get($property_name){

        $property = self::tableize($property_name );
        /*if (  static::$schema != null &&
                array_key_exists($property, static::$schema) ) */  //changed by xwarrior@2012.5.2 for performence
        if ( isset( static::$schema[$property] ) )
        {
            /*if (   array_key_exists($property, $this->attributes) ){*/
            if  (isset( $this->attributes[$property]) ){
                return $this->attributes[$property];
            }else{
                return null;
            }
        }else{
            if ( isset(  $this->$property_name ) ){
                return $this->$property_name ;
            }else{
                return null;
            }
        }
    }

    public function __set($property_name,$property_value){
        if(!$property_name)return false;
    	$property = self::tableize( $property_name );
        /*if(  static::$schema != null &&
                array_key_exists($property, static::$schema) ){ */  //changed by xwarrior@2012.5.2 for performence
        if ( isset(  static::$schema[$property] ) ){
            $this->attributes[$property] = $property_value;
            return $this;
        }elseif($this->allowGhost == true){
        	//允许未经定义的字段
        	$this->attributes[$property] = $property_value;
        	return $this;
        }else{
			//注意：仅在原型中保存数据，不会被存储
			\yoka\Debug::log('mdbao: warning',"$property will not be saved");
            $this->$property_name = $property_value;
            return $this;
        }
    }
    public function __isset($property_name){
    	$property = self::tableize( $property_name );
    	if ( isset( static::$schema[$property]) ){
    		return isset($this->attributes[$property]);
    	}else return isset($this->property_name);
    }
    public function __unset($property_name){
    	$property = self::tableize( $property_name );
    	if ( isset( static::$schema[$property]) ){
    		unset($this->attributes[$property]);
    	}else unset($this->property_name);
    }

    public function __call($method, $arguments)
    {
        //get or set must  len > 3
        if( strlen($method) <= 3 ){
            return ;
        }
        // Is this a get or a set
        $prefix = strtolower(substr($method, 0, 3));

        if ($prefix != 'get' && $prefix != 'set')
            return;

        if ( static::$schema == null){
            $className = get_called_class();
            throw new Exception("$className schema undefined!");
        }



        // What is the get/set class attribute
        $property = self::tableize(substr($method, 3));
        if ( empty($prefix) || empty($property) )
        {
            // Did not match a get/set call
            throw New Exception("Calling a non get/set method that does not exist: $method");
        }

        /*if ( !array_key_exists($property, static::$schema) ){ */
        if ( !isset( static::$schema[$property]  ) ){
            return;
        }


        // Get
        //if ($prefix == "get"  && array_key_exists($property, $this->attributes))
        if ($prefix == "get"  && isset( $this->attributes[$property] ) )
        {
            return $this->attributes[$property];
        }
        else if ($prefix == "get")
        {
            return null;
        }

        // Set
        //if ($prefix == "set" && array_key_exists(0, $arguments))
        if ($prefix == "set" && isset($arguments[0] ) )
        {
            $this->attributes[$property] = $arguments[0];
            return $this;
        }
        else
        {
            throw new \Exception("Calling a get/set method that does not exist: $property");
        }
    }


    // framework overrides/callbacks:
    public function beforeUpdate() {}
    public function beforeSave() {}
    public function afterSave() {}
    public function beforeValidation() {}
    public function afterValidation() {}
    public function beforeDestroy() {}
    public function afterNew() {}


    protected function isValid($update = false)
    {
        $className = get_called_class();
        $methods = get_class_methods($className);

        foreach ($methods as $method)
        {
            if (substr($method, 0, 9) == 'validates')
            {
                $propertyCall = 'get' . substr($method, 9);
                if(true == $update && !$this->$propertyCall())continue; //update操作时，空值的字段忽略检查。by jimmy, for update
                if (!$className::$method($this->$propertyCall()))
                {
                    return false;
                }
            }
        }
        /**
         * Auto validate
         * @author jimmy.dong@gmail.com
         * 根据schema与 schema_ext对类型进行检查
         *
         * 【schema扩展】
         * by jimmy.dong@gmail.com
         * 通用扩展属性：
         *  default		缺省值
         * 	jugglin		是否允许自动类型转换
         *  essential	必须有值(非null)
         * 类型适用属性：
         * switch(type){
         *	case 'integer' 	:   min 最小值，max 最大值，break;
         *  case 'float'	:   min 最小值，max 最大值，break;
         *  case 'string'	:   min 长度最小  max 长度最大 break;
         *  case 'boolean'	:
         *  case 'array'	:
         *  case 'object'	:
         *  case 'resouce'	:
         *	case 'NULL'		:
         *	case 'unknown type': break;
         * }
         * eg:
        protected static $schema = array(
        _id=>'objectid',
        name=>'string',
        key=>'integer',
        data=>'array'
        );
        protected static $schema_ext = array(
        key=>array('jugglin'=>1,'essential'=>1,'min'=>10,'max'=>100),

        );
         */
        $ext_schema = array();
        if(isset(static::$schema_ext) && is_array(static::$schema_ext))foreach(static::$schema_ext as $key=>$val){
            if(is_array($val))foreach($val as $key2=>$val2){
                $ext_schema[$key][strtolower($key2)] = $val2;
            }
        }
        foreach( static::$schema as $k=> $v){
            if($k=='_id'){
                //wait...
                continue;
            }
            $current_var	= $this->$k;

            //if(true == $update && !$current_var)continue; //update操作时，不存在的字段忽略检查。by jimmy, for update
            //如果current_var=0|'' 会缺少类型检查
            if(true == $update && $current_var ===null)
                continue;
            $ext = isset($ext_schema[$k])?$ext_schema[$k]:array();
            //检查是否必须非空值
            if(isset($ext['essential']) && $ext['essential'] && $current_var===null){
                throw new \Exception("BaseMongoRecord: $k must have value!");
                break;
            }

            switch($v){
                //注意： 自定义类型请在此补充检查条件
                case 'datetime':
                    //日期自动转换为整数（注：毫秒数) 
                    if ( $this->$k ){
                    	if(is_numeric($this->$k)) $this->$k = doubleval( $this->$k );
                    	elseif(is_string($this->$k)) $this->$k = doubleval( strtotime($this->k) * 1000);
                    	else {
                    		//不是数字、不是日期字符串，怎么办？ 暂时用当前时间戳
                    		$this->$k = $this->Millseconds(); 
                    	}
                    }else{
                        $this->$k = $this->Millseconds();  //set default current
                    }
                    break;
                case 'boolean':
                case 'integer':
                case 'float':
                case 'double':
                case 'string':
                case 'array':
                case 'object':
                    if(isset($ext['default']) && $current_var===null){
                        //缺省值处理
                        $this->$k = $ext['default'];
                    }elseif(isset($ext['jugglin']) && $ext['jugglin'] && (gettype($current_var) != $v)){
                        //自动转义处理
                        if($v == 'object' && gettype($current_var) == 'string' && $tmp = json_decode($current_var)){
                        		$this->$k = $tmp;
                        }else{
	                        $tmp = $current_var;
	                        settype($tmp, $v);
	                        $this->$k = $tmp;
                        }
                    }elseif(gettype($current_var) != $v && $current_var !== null) {
                        throw new \Exception("BaseMongoRecord: '$k' must be $v !");
                        return false;
                    }
                    //长度检查
                    if(isset($ext['min'])){
                        if('string' == $v) $l=strlen($current_var);
                        else $l=$current_var;
                        if($l < $ext['min']){
                            throw new \Exception("BaseMongoRecord: '$k' = $current_var length not enough!");
                            return false;
                        }
                    }
                    if(isset($ext['max'])){
                        if('string' == $v) $l=strlen($current_var);
                        else $l=$current_var;
                        if($l > $ext['max']){
                            throw new \Exception("BaseMongoRecord: '$k' => $current_var length over max!");
                            return false;
                        }
                    }
                    break;
                case 'unkonw':
                default:
                    break;
            }
        }

        return true;
    }

    /**
     * 标准化表名
     * @static
     * @param $camelCasedWord
     * @return string
     */
    protected static function tableize($camelCasedWord) {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
    }

    //by jimmy
    protected static function getDatabase()
    {
    	$db = self::$connection->selectDB(self::$database);
    	return $db;
    }

    // core conventions
    protected static function getCollection()
    {
        $className = get_called_class();
        if (null !== static::$collectionName)
        {
            $collectionName = static::$collectionName;
        }
        else
        {
            //hack by jimmy.dong@gmail.com
            $t = explode('\\',$className);
            $real_className = array_pop($t);
            $collectionName = self::tableize($real_className);
            self::$collectionName = $collectionName;
        }
		//没有使用过，初始化
        if ($className::$database == null){
        	new $className;
        }
        //配置检查
        if ($className::$database == null)
            throw new \Exception("BaseMongoRecord::database must be initialized to a proper database string");
        if ($className::$connection == null)
            throw new \Exception("BaseMongoRecord::connection must be initialized to a valid Mongo object");

        $className::$connection->connect();
        return $className::$connection->selectCollection($className::$database, $collectionName);
    }

    public static function setFindTimeout($timeout)
    {
        $className = get_called_class();
        $className::$findTimeout = $timeout;
    }

    /**
     * 建立当前集合上的索引，或确保索引建立,如果已经有索引则忽略
     * 警告：在繁忙的生产系统上对大集合数据调用ensureIndex会导致系统阴塞
     * @param array $keys
     * @param array $options
     */
    public static function ensureIndex(array $keys, array $options = array())
    {
        return self::getCollection()->ensureIndex($keys, $options);
    }

    /**
     * 删除集合上的索引
     * @param unknown_type $keys
     */
    public static function deleteIndex($keys)
    {
        return self::getCollection()->deleteIndex($keys);
    }

    /**
     * 将当前对象的变更保存到数据库中
     * @param boolean $overite_all   是否用当前对象完全替换数据库记录，
     *                                           例如数据库中为: array(a=>1,b=>2),对象的值为array(c=>1)
     *                                           当$overite_all=true,则保存后数据库中为 array(c=>1),a和b字段的值将丢失
     *                                           当$overite_all=false,则保存后数据库中为array(a=>1,b=>2,c=>1),新增了一个c字段
     * @param boolean $upsert  如果不存在，是否插入
     * @return 如果设置了safe=true,返回了包含 status的数组，如果safe=false,只要$new_object的值不是空就返回true
     * @see http://www.php.net/manual/en/mongocollection.update.php
     * 	@author xwarrior 2012/3/8
     */
    public function Update($overite_all = false,$upsert = false,$safe = false){
        /**
         * 执行数据验证规则
         */
        if ( !$this->validate(true) ){
            throw new \Exception( get_called_class() . '对象schema验证失败');
        }

        if( !isset($this->attributes['_id'])  ){
            throw new \Exception(get_called_class() .'对象没有指定 _id 属性，无法执行更新');
        }

        $begin_microtime = \yoka\Debug::getTime();
        $this->beforeUpdate();
        //added by Kit: for trace update
        //throw new \Exception('this is:'.$this->attributes['visibility']);

        $options = array( 'upsert' => $upsert, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

        $critera = array('_id' => $this->attributes['_id'] );

        //added by Kit: bug report:mod on _id not allowed, _id should be removed from attributes before doing update
        $_id = $this->attributes['_id']; /* 保存_id属性后边再恢复  */
        unset( $this->attributes['_id'] );

        //覆盖替换所有字段
        $ret = null;
        $collection = $this->getCollection();
        if ( $overite_all ){
            $ret = $collection->update($critera,  $this->attributes, $options);
            $this->_id = $_id;
        }else{
            //只执行几个字段变更
            $ret = $collection->update($critera,  array( '$set' => $this->attributes),$options);
            $this->_id = $_id;
        }
        //add performence log xwarrior  2012/8/8
        self::log_query('Update',$critera, $options, $collection, $begin_microtime,$ret);



        return $ret;
    }

    /**
     * 往对象的array类型字段中追加一条数据
     * @param $field_values   array( field => array( sub_field1 => values, sub_field2 => value ...)
     *
     */
    public function Push($field_values,$safe = false){
        $begin_microtime = \yoka\Debug::getTime();
        $options = array( 'upsert' => false, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

        $critera = array('_id' => $this->attributes['_id'] );
        $ret = null;
        $collection = $this->getCollection();

        $ret = $collection->update($critera,  array('$push' => $field_values), $options);

        self::log_query('Push',$critera, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }

    /**
     * 在内嵌文档中删除指定条件的数据
     * $remove_query  要移除的内嵌文档的数据条件 如 array( 'comments'=> array( comment_id: ’xxxxxxxxxxxxxxxx' ));
     */
    public function Pull($remove_query,$safe = false){
        $begin_microtime = \yoka\Debug::getTime();
        $options = array( 'upsert' => false, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

        $critera = array('_id' => $this->attributes['_id'] );
        $ret = null;
        $collection = $this->getCollection();

        $ret = $collection->update($critera,  array('$pull' => $remove_query), $options);

        $query = array_merge($critera,$remove_query);
        self::log_query('Pull',$query, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }

    /**
     * 在内嵌文档中删除第一个（or 最后一个）
     * $remove_query  条件项 eg: array( comment=> 1 )  1:删除最后一个  -1:删除第一个
     */
    public function Pop($remove_query,$safe = false){
        $begin_microtime = \yoka\Debug::getTime();
        $options = array( 'upsert' => false, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

        $critera = array('_id' => $this->attributes['_id'] );
        $ret = null;
        $collection = $this->getCollection();

        $ret = $collection->update($critera,  array('$pop' => $remove_query), $options);

        $query = array_merge($critera,$remove_query);
        self::log_query('Pop',$query, $options, $collection, $begin_microtime,$ret);
        return $ret;
    }

    /**
     * 批量更新符合query条件的一批数据，用fields中指定的字段值
     * @param array   $query  mongo  查询字段 		   array( filed1=>condition1,field2=>condition2,...)
     * @param object $new_object  要更新的对象值必须继承自BaseMongoRecord (为了数据验证需要)
     * @param  array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
     * //废弃 @return 如果设置了safe=true,返回了包含 status的数组，如果safe=false,只要$new_object的值不是空就返回true
     * @return 默认返回更新条数 //by jimmy
     * @see http://www.php.net/manual/en/mongocollection.update.php
     */
    public static function updateAll($query = array() , $new_object ,
                                     $options = array( 'upsert' => false, 'multiple' => true,'safe' => true,'fsync' =>false, 'timeout' => 20000  )){
        $attrs = $new_object->toArray();
        unset($attrs['_id']);  //防止传入_id

        if ( !$attrs || count($attrs) == 0 ){
            throw new Exception( gettype($new_object). '要保存的对象值不能为空');
        }

        if ( !$query || count($query) == 0 ){
            throw new Exception(gettype($new_object).'更新的查询条件不能为空');
        }

        /**
         * 执行数据验证规则
         */
        if ( !$new_object->validate(true) ){
            throw new \Exception(gettype($new_object).'对象schema验证失败');
        }

        $ret =  self::getCollection()->update($query,array('$set'=>$attrs),$options);

        if($options['safe']) return $ret['n'];
        else return $ret;

    }

    /**
     * 批量清除某个属性（主要用于内嵌格式的字段清除）
     * @param
 */
    public static function unsetAll($query = array(), $field,
    					$options = array( 'upsert' => false, 'multiple' => true,'safe' => true,'fsync' =>false, 'timeout' => 20000  )){

    	if ( !$field ){
            throw new Exception( '目标字段不能为空');
        }
    	if ( !$query || count($query) == 0 ){
            throw new Exception('清除的查询条件不能为空');
        }
    	$ret = self::getCollection()->update($query, array('$unset'=> array($field=> 1)), $options);

    	if($options['safe']) return $ret['n'];
        else return $ret;
    }



    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 检查一个给定的mongoid是否是有效的格式
     * @author xwarrior at 2012.5.17
     * @param unknown_type $mongoid
     */
    public static function isMongoIdValid($mongoid){
        if ( !$mongoid ){
            return false;
        }

        if ( $mongoid instanceof MongoId  ){
            return true;
        }

        $oid = new MongoId( strval($mongoid) );  //try convert to mongoid
        if (  strval($oid) !=  $mongoid){
            return false;
        }
        return True;
    }

    /**
     * 读取当前时间的整数millsecond表示形式
     * yoka at项目建议统一使用该以毫秒ms为单位的时间
     * @see 只支持64位系统下的php
     */
    public function Millseconds(){

        list($mills,$seconds )  =   explode( ' ',microtime()) ;
        $microtime =  $seconds .  substr(  $mills ,2,3);

        return doubleval($microtime);
    }

    /**
     * 20120718 by sam add
     * 字符串或字符串id数组,转换到,mongoId或mongoId数组
     * @param $ids 值格式：'id' 或 'id1,id2,idN' 或  array(id1,id2,id3,idN)
     * @return new MongoId() 或  array(new MongoId,new MongoId,....);
     */
    public static function toMongoId($ids){
        if(!is_array($ids)){
            if(strstr($ids,',')){
                $ids=explode(",",$ids);
            }else{
                return new MongoId($ids);
            }
        }
        $c=count($ids);
        if($c<1)
            return null;
        else if($ids[$c-1]==''){
            array_pop($ids);
        }
        foreach ($ids as &$id){
            $id=new MongoId(trim($id));
        }
        return $ids;
    }

    /**
     * 地球经纬度距离计算
     * @desc Mongo原生指令： db.runCommand({ geoNear : “collectionName” , near : [120.123456,30.654321], distanceMultiplier: 6378137, num : 10, spherical:true , query:{Name:”肯德基”}}
     * @param float $lng
     * @param float $lat
     * @param float $max_distance
     * @param number $limit
     * @return array
     */
    public function geoNear($lng, $lat, $max_distance=null, $query=null, $limit=200){
    	$begin_microtime = \yoka\Debug::getTime();
    	$mongodb = self::getDatabase();
    	$collection = $this->getName();
		$cmd = array(
    			"geoNear"=>$collection,
    			"near"=>[$lng, $lat],
				"limit"=>$limit,
				"distanceMultiplier"=>6378137,
				"spherical"=>true,
    	);
		if($max_distance)$cmd["maxDistance"] = $max_distance / 6378137;
		if($query)$cmd["query"] = $query;
    	$cmdresult = $mongodb->command($cmd);
    	$ret = $cmdresult['results'];
    	self::log_query('geoNear',$cmd, $options, $collection, $begin_microtime,$ret);
    	return $ret;
    }


}