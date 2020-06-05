<?php
namespace yoka;
/**
 * UtilArray
 * 常用数组处理类
 *
 *  $Id$
 */
class UtilArray {
	
	/**
	 * 排序字段
	 */
	private static $__sort_field;

	/**
	 * 排序类型
	 */
	private static $__sort_type;

	/**
	 * 排序Flag
	 */
	private static $__sort_flag;

	/**
	 * 转码
	 * @param unknown $data
	 * @param string $from
	 * @param string $to
	 * @return string[]|unknown[]|string[][]|NULL[][]|unknown[][]
	 */
	static public function iconv($data, $from='gbk', $to='utf-8'){
		$re = array();
		foreach($data as $k=>$v){
			$k = iconv($from, $to, $k);
			if(is_array($v)) {
				$re[$k] = self::iconv($v, $from, $to);
			}
			elseif(is_string($v)){
				$t = iconv($from, $to, $v);
				$re[$k] = $t;
			}
			else $re[$k] = $v;
		}
		return $re;
	}
	/**
	 * get_col 获取二维数组中指定的列
	 *
	 * @param  array  $data	必须为二维数组
	 * @param  string $key_word 所要列的键名
	 * @param  string $key	 列键名
	 * @return array
	 */
	static public function get_col($data, $keyword, $key = null) {
		if (!is_array($data)) {
			return false;
		}
		$result = array();
		if ($key && is_string($key)) {
			foreach ($data as $value) {
				$result[$value[$key]] = $value[$keyword];
			}
		} else {
			foreach ($data as $value) {
				$result[] = $value[$keyword];
			}
		}
		return $result;
	}

	/**
	 * get_property
	 * 获取数组中每一个对象的属性
	 *
	 * @param  array $data 数组的每一项必须为对象
	 * @param  string $property 对象的属性名
	 * @param  string $key 同级的属性名
	 * @return array
	 */
	static public function get_property($data, $property, $key = null) {
		if (!is_array($data)) {
			return false;
		}
		$result = array();
		if ($key && is_string($key)) {
			foreach ($data as $object) {
				$result[$object->$key] = $object->$property;
			}
		} else {
			foreach ($data as $object) {
				$result[] = $object->$property;
			}
		}
		return $result;
	}

	/**
	 * rebuild_by_col
	 * 根据某个字段把该字段的值当数组的KEY重组数组
	 * 例如 $a = array(
	 *				 array('uId' => '1', 'data' => 'test'),
	 *				 array('uId' => '2', 'data' => 'test2')
	 *				)
	 * Util_Array::rebuild_by_col($a, 'uId');
	 * array(
	 *	   '1' => array('uId' => '1', 'data' => 'test'),
	 *	   '2' => array('uId' => '2', 'data' => 'test2')
	 *	  )
	 *
	 * @param  array $data 二维数组
	 * @param  string $keyword 字段名
	 * @return array
	 */
	static public function rebuild_by_col($data, $keyword) {

		// 无数据原样返回
		if (!$data) {
			return $data;
		}

		$result = array();

		foreach ($data as $value) {
			$result[$value[$keyword]] = $value;
		}

		return $result;
	}

	/**
	 * rebuild_by_property
	 * 根据数组中对象的属性重组数组
	 * 用法类似于rebuild_by_col
	 *
	 * @param  array $data 对象数组
	 * @param  string $property 对象的属性名
	 * @return array
	 */
	static public function rebuild_by_property($data, $property) {

		// 无数据原样返回
		if (!$data) {
			return $data;
		}

		$result = array();

		foreach ($data as $object) {
			$result[$object->$property] = $object;
		}

		return $result;
	}

	/**
	 * 以keys的值为下标, 以value的值为值，组合成新的数组
	 *
	 * 例如：
	 * 		$a = array(
	 * 				array('uId' => 'b', 'name' => 'aa'),
	 * 				array('uId' => 'cc', 'name' => 'dd')
	 * 				);
	 * 		$b = array('a' => 'b', 'cc' => 'd');
	 *
	 * Util_Array::combine($a, $b, 'uId', true, false);
	 * 			返回 array(
	 * 					'd' => array('uId' => 'cc', 'name' => 'dd')
	 * 					);
	 * Util_Array::combine($a, $b, 'uId', false, false);
	 * 			返回 array(
	 * 					'b' => array('uId' => 'b', 'name' => 'aa');
	 * 					);
	 *
	 * @param array $values 值数组
	 * @param array $keys 键数组 必需是一维数组,且不能有重复值
	 * @param string $field 以$values数组中的$field字段为比较字段
	 * @param boolean $cmp_with_key 以$keys的键或值为比较对象
	 * @param boolean $key_as_index 以$keys的键或值为返回值的索引
	 * @return array
	 */
	static public function combine($values, $keys, $field, $cmp_with_key = true, $key_as_index = false) {
		$result = array();
		if (!$keys || !$values || !$field) {
			return $result;
		}

		if (!$cmp_with_key) {
			// 如果是比较值的话 反转一下数组, 方便后面比较
			$keys = array_flip($keys);
			// 键和值也要反转
			$key_as_index = !$key_as_index;
		}

		foreach ($values as $k => $value) {
			// 检查指定字段在不在$value中
			if (!array_key_exists($field, $value)) {
				continue;
			}

			// 取出value中指定字段的值
			if (!($idx = $value[$field])) {
				continue;
			}

			// 检查键在keys数组中是否存在
			if (array_key_exists($idx, $keys)) {
				if ($key_as_index) {
					$result[$idx] = $value;
				} else {
					$result[$keys[$idx]] = $value;
				}
			}
		}
		return $result;
	}

	/**
	 * merge_by_key 按某一指定列进行合并
	 *
	 * 本函数约定 $data2 和 $data1 根据指定列，可以是一对多的关系也可是一对一的关系
	 * 			  但不能是多对多的关系。
	 * 			  使用本函数请注意参数顺序,$data2所指定列的值是没有重复的请注意。
	 *
	 * @param  mixed $data
	 * @param  mixed $data2
	 * @param  mixed $key
	 * @return void
	 */
	static public function merge_by_key($data, $data2, $keyword, $keyword2 = null) {
		if (is_array($data) && is_array($data2)) {
			$result = array();
			$data_map = array();
			// index
			$tmp_keyword = $keyword;
			if ($keyword2) {
				$tmp_keyword = $keyword2;
			}
			foreach ($data2 as $value) {
				$data_map[$value[$tmp_keyword]] = $value;
			}
			foreach ($data as $value) {
				if (is_array($data_map[$value[$keyword]])) {
					$result[] = array_merge($value, $data_map[$value[$keyword]]);
				} else {
					$result[] = $value;
				}
			}
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * build_array_query
	 * 将数组变成HTTP QUERY形试
	 *
	 * @param  mixed $data
	 * @param  string $key
	 * @return void
	 */
	static public function build_array_query($data, $key = '') {

		$query = $data;

		if ($key) {
			$query = array($key => $data);
		}

		return http_build_query($query, '', '&');
	}

	/**
	 * list_cols 行列转置
	 * 从数组中获取特定的一组值
	 * (说明: 返回的各项数组中的值没有去重, 如有需要请自行处理)
	 *
	 * @param array $array 源数组 array(
	 *								  array('uId' => 1, 'gId'=>2, 'tId' => null),
	 *								  array('uId' => 2, 'gId'=>3, 'tId' => null),
	 *								  array('uId' => 3, 'gId'=>4, 'tId' => null),
	 *								  array('uId' => 4, 'gId'=>5, 'tId' => null),
	 *								  ....
	 *							  );
	 * @param array $keywords 需要获取数据的键 array('uId', 'gId', 'tId');
	 * @return array(
	 *			  'uId' => array(1, 2, 3, 4, 5, ...),
	 *			  'gId' => array(2, 3, 4, 5, ...),
	 *			  'tId' => array(),
	 *		  );
	 */
	static public function list_cols($data, $keywords = array()) {

		$return_data = array();

		if (!$keywords) {
			return $return_data;
		}

		// 生成容器数组
		foreach ($keywords as $keyword) {
			$return_data[$keyword] = array();
		}

		// 往已经生成的容器数组里塞值
		foreach ($data as $col) {
			foreach ($col as $key => $value) {
				if (in_array($key, $keywords) && $value) {
					$return_data[$key][] = $value;
				}
			}
		}

		return $return_data;
	}

	/**
	 * sort_by_field
	 * 对二维数组，按指定的字段值排序 【主键丢失】
	 * 特别适用于MySQL select IN 条件按照指定字段排序
	 *
	 * 例如 $data = array(
	 *					array('uId' => '9', 'data' => 'test1'),
	 *					array('uId' => '3', 'data' => 'test2'),
	 *					array('uId' => '2', 'data' => 'test3'),
	 *					array('uId' => '5', 'data' => 'test4'),
	 *				   )
	 *
	 * Util_Array::sort_by_field($data, 'uId', 'ASC', 'NATURAL');
	 *
	 *
	 * return array(
	 *			  array('uId' => '2', 'data' => 'test3'),
	 *			  array('uId' => '3', 'data' => 'test2'),
	 *			  array('uId' => '5', 'data' => 'test4'),
	 *			  array('uId' => '9', 'data' => 'test1')
	 *			 )
	 *
	 * @param  array $data 二维数组数据
	 * @param  string $sort_field 排序字段名
	 * @param  string $sort_type ASC, DESC 排序类型，升序降序
	 * @param  string $sort_flag REGULAR, NUMERIC, STRING, NATURAL 比较类型，通常比较、数字比较、字符串比较、自然比较，参见php sort函数
	 * @return array
	 */
	static public function sort_by_field($data, $sort_field, $sort_type = 'ASC', $sort_flag = 'REGULAR') {

		if (!$data || !is_array($data)) {
			return false;
		}

		if (!$sort_field) {
			return false;
		}

		$sort_type = strtoupper($sort_type);
		switch ($sort_type) {
			case 'DESC':
				break;
			case 'ASC':
			default:
				$sort_type = 'ASC';
		}

		$sort_flag = strtoupper($sort_flag);
		switch ($sort_flag) {
			case 'NUMERIC':
				break;
			case 'STRING':
				break;
			case 'NATURAL':
				break;
			case 'REGULAR':
			default:
				$sort_flag = 'REGULAR';
		}

		self::$__sort_field = $sort_field;
		self::$__sort_type = $sort_type;
		self::$__sort_flag = $sort_flag;

		usort($data, array(self, '__sort_by_field'));

		// 清理
		self::$__sort_field = '';
		self::$__sort_type = '';
		self::$__sort_flag = '';

		return $data;
	}

	/**
	 * __sort_by_field
	 * usort回调，自定义按行的排序方式
	 * add by jc : 该函数必须设置为静态函数，才能被回调使用
	 *
	 * @param  array $row1 第一条记录
	 * @param  array $row2 第二条记录
	 * @return integer row1 < row2 返回-1，row1 = row2 返回0，row1 > row2 返回1。降序排列相反
	 */
	private static function __sort_by_field($row1, $row2) {

		$sort_field = self::$__sort_field;
		$sort_type = self::$__sort_type;
		$sort_flag = self::$__sort_flag;

		// 默认认为两个数据相等
		$res = 0;

		if ($sort_flag == 'REGULAR') {
			if ($row1[$sort_field] < $row2[$sort_field]) {
				$res = -1;
			} elseif ($row1[$sort_field] > $row2[$sort_field]) {
				$res = 1;
			}
		} elseif ($sort_flag == 'NUMERIC') {
			$res = bccomp($row1[$sort_field], $row2[$sort_field]);
		} elseif ($sort_flag == 'STRING') {
			$res = strcmp($row1[$sort_field], $row2[$sort_field]);
		} elseif ($sort_flag == 'NATURAL') {
			$res = strnatcmp($row1[$sort_field], $row2[$sort_field]);
		}

		if ($sort_type == 'DESC') {
			$res *= -1;
		}

		return $res;
	}

	/**
	 * sort_by_value
	 * 对二维数组，按指定的sort数组的值排序
	 * 特别适用于MySQL select IN 条件按照指定KEY的顺序排序
	 *
	 * 特别提醒，sortValue和排序字段值都不能有重复的数据出现
	 * 允许 data 数据比 sortValue 少，但不能反之
	 *
	 * 例如 $data = array(
	 *					array('uId' => '9', 'data' => 'test1'),
	 *					array('uId' => '3', 'data' => 'test2')
	 *					array('uId' => '2', 'data' => 'test3')
	 *					array('uId' => '5', 'data' => 'test4')
	 *				   )
	 *	  $sort_value = array('2', '5', '3', '9');
	 *
	 * Util_Array::sort_by_value($data, 'uId', $sort_value);
	 *
	 *
	 * return array(
	 *			  array('uId' => '2', 'data' => 'test3'),
	 *			  array('uId' => '5', 'data' => 'test4')
	 *			  array('uId' => '3', 'data' => 'test2')
	 *			  array('uId' => '9', 'data' => 'test1')
	 *			 )
	 *
	 * @param  array $data 数据
	 * @param  string $sort_field 排序字段名
	 * @param  array $sort_value 排序的顺序
	 * @return array
	 */
	static public function sort_by_value($data, $sort_field, $sort_value) {

		if (!$data || !is_array($data)) {
			return false;
		}

		if (!$sort_field || !$sort_value || !is_array($sort_value)) {
			return false;
		}

		// 反转排序key=>value便于查找
		$sort_flip = array_flip($sort_value);

		// 排序后的数据
		$sorted = array();

		foreach ($data as $value) {
			if ($value && isset($value[$sort_field]) && isset($sort_flip[$value[$sort_field]])) {
				$index = $sort_flip[$value[$sort_field]];

				$sorted[$index] = $value;
			}
		}

		if ($sorted) {
			ksort($sorted, SORT_NUMERIC);
		}

		return $sorted;
	}

	/**
	 * merge
	 * 合并数组，去重，过滤空值，intval
	 *
	 * notice: 此方法会去除所有关联key，如果需要保留key请勿使用此方法
	 *
	 * 例如：
	 * Util_Array::merge($array1, $array2, $array3, $array4);
	 * Util_Array::merge($array1, $array2, $array3, $array4, true);
	 * Util_Array::merge($array1, $array2, $array3, $array4, false);
	 *
	 * array $array1 [, array $array2, array $array3 ... ] [$intval]
	 * @param  array $array1, $array2 多个需要merge的数组
	 * @param  boolean $intval 是否自动处理成整型
	 * @return array
	 */
	static public function merge() {

		// 默认需要intval处理
		$intval = true;

		// 获取所有参数
		$function_args = func_get_args();

		if (!$function_args) {
			return array();
		}

		// 取出最后一个参数
		$last_arg = end($function_args);

		if ($last_arg === true || $last_arg === false) {
			// 传了$intval参数
			array_pop($function_args);

			$intval = $last_arg;
		}

		foreach ($function_args as $key => $arg) {
			// 整理每个参数，保证每个参数均为数组
			if (is_string($arg)) {
				$arg = array(trim($arg));
			} elseif (is_numeric($arg)) {
				$arg = array($arg);
			} elseif (is_array($arg)) {
				$arg = $arg;
			} else {
				$arg = array();
			}

			$function_args[$key] = $arg;
		}

		// 合并
		$result = call_user_func_array('array_merge', $function_args);

		// 整型化处理
		if ($intval) {
			$result = array_map('intval', $result);
		}

		// 过滤空值
		$result = array_filter($result);

		// 去重
		$result = array_unique($result);

		// 重新排序
		if ($intval) {
			sort($result, SORT_NUMERIC);
		} else {
			sort($result);
		}

		return $result;
	}

	/**
	 * 对索引数组(无指定key的)数据进行分页
	 * @param  array $data		  索引数组
	 * @param  array & $page_options 分页参数
	 * @return array				按页分隔的数组数据
	 */
	static public function page($data, & $page_options) {

		if (!$data || !is_array($data)) {
			return $data;
		}

		if ($page_options && !$page_options['total_items']) {
			$page_options['total_items'] = count($data);
		}
		$page_options = Pager::resolve_options($page_options);

		if ($page_options) {
			return array_slice($data, $page_options['start'], $page_options['per_page']);
		} else {
			return $data;
		}

	}


	/**
	 * filter_by_keyword
	 * 对数组按关键字进行过滤
	 * @param  array $data		  索引数组
	 * @param  array $field		 字段
	 * @param  array $keyword		   关键字
	 * @return array				按页分隔的数组数据
	 */
	static public function filter_by_keyword($data, $field, $keyword) {
		if (!$data || !is_array($data)) {
			return array();
		}

		if (!$field || !is_string($field)) {
			return $data;
		}

		if (!$keyword || !is_string($keyword)) {
			return $data;
		}

		$result = array();

		foreach ($data as $value) {
			if (!$value || !is_array($value)) {
				continue;
			}
			if (!$value[$field] || !is_string($value[$field])) {
				continue;
			}
			if (strpos($value[$field], $keyword) === false) {
				continue;
			}

			$result[] = $value;
		}

		return $result;
	}

	/**
	 * search_by_keyword
	 * 对数组进行指定关键字查找并分页
	 *
	 *	示例:
	 *	$data = array(
	 *				  array('cId' => 100016, 'uId' => 105124, 'uName' => 'aaa', 'uCreated' => '2013-07-04 11:22:33'),
	 *				  array('cId' => 100016, 'uId' => 105135, 'uName' => 'bab', 'uCreated' => '2013-07-02 11:22:33'),
	 *				  array('cId' => 100016, 'uId' => 105147, 'uName' => 'ccc', 'uCreated' => '2013-07-01 11:22:33'),
	 *				  array('cId' => 100016, 'uId' => 105159, 'uName' => 'ddd', 'uCreated' => '2013-07-03 11:22:33'),
	 *				  array('cId' => 100016, 'uId' => 105161, 'uName' => 'eee', 'uCreated' => '2013-07-05 11:22:33'),
	 *				 );
	 *	$page_options = array('page' => 1, 'per_page' => 5);
	 *	$result = Util_Array::search_by_keyword($data, 'uName', 'aa', $page_options, 'uCreated', 'DESC');
	 *
	 * @param  array $data		  索引数组
	 * @param  array $search_field   查询字段
	 * @param  array $search_keyword	 查询关键字
	 * @param  array & $page_options 分页参数
	 * @param  array $sort_field	 排序字段
	 * @param  array $sort_type	  排序方式 ASC/DESC
	 * @param  array $sort_flag	  排序模式
	 * @return array				按页分隔的数组数据
	 */
	static public function search_by_keyword($data, $search_field, $search_keyword, &$page_options = array(), $sort_field = '', $sort_type = 'ASC', $sort_flag = 'REGULAR') {

		$result = array();
		if (!$data || !is_array($data)) {
			return $result;
		}

		// 过滤
		$result = self::filter_by_keyword($data, $search_field, $search_keyword);
		if (!$result || !is_array($result)) {
			return $result;
		}

		// 排序
		if ($sort_field) {
			$result = self::sort_by_field($result, $sort_field, $sort_type, $sort_flag);
			if (!$result || !is_array($result)) {
				return $result;
			}
		}

		// 分页
		$result = self::page($result, $page_options);
		return $result;
	}

	/**
	 * filter_by_conditions
	 * 根据条件筛选数组
	 *
	 * @param  array $data		原始数据
	 * @param  array $conditions  筛选条件
	 * @return array			  返回结果
	 *
	 * 示例:
	 *	$data = array(
	 *		array('user_id'=> 101, 'name' => 'halo', 'age' => 19, 'skills' => 'c,php,java,c++'),
	 *		array('user_id'=> 102, 'name' => 'check', 'age' => 18, 'skills' => 'php'),
	 *		array('user_id'=> 103, 'name' => 'joshua', 'age' => 12, 'skills' => 'php,java,c++'),
	 *		array('user_id'=> 104, 'name' => 'jimmy', 'age' => 29, 'skills' => 'c,c++'),
	 *		array('user_id'=> 105, 'name' => 'cony', 'age' => 49, 'skills' => 'c,c++'),
	 *		array('user_id'=> 106, 'name' => 'monkey', 'age' => 13, 'skills' => 'net,php,java,c++'),
	 *		array('user_id'=> 107, 'name' => 'wilson', 'age' => 82, 'skills' => 'sql,java,'),
	 *		array('user_id'=> 108, 'name' => '', 'age' => 82, 'skills' => 'sql,java,'),
	 *		array('user_id'=> 109, 'name' => 'nnn', 'age' => 82, 'skills' => ''),
	 *	);
	 *
	 *	$conditions = array(
	 *		array('user_id', '!=', 105),
	 *		array('name', 'like', 'y'),
	 *		array('skills', 'in', array('c', 'd')),
	 *	);
	 *
	 *	$result = Util_Array::filter_by_conditions($data, $conditions);
	 *
	 */
	static public function filter_by_conditions($data, $conditions) {

		$result = array();
		if (!$data || !is_array($data)) {
			return $result;
		}

		if (!$conditions || !is_array($conditions)) {
			return $data;
		}

		foreach ($data as $key => $row) {
			if ($row = self::__filter_row_by_conditions($row, $conditions)) {
				$result[$key] = $row;
			}
		}

		return $result;

	}

	/**
	 * __filter_row_by_conditions
	 *
	 * @param  array $row
	 * @param  array $conditions
	 * @return mixed
	 */
	private static function __filter_row_by_conditions($row, $conditions) {
		if (!$row || !is_array($row)) {
			return false;
		}

		foreach ($conditions as $condition) {
			list($field, $method, $value) = $condition;

			$row_field = $row[$field];
			if (!$row_field) {
				return false;
			}

			switch ($method) {
				case '>':
				case '<':
				case '=':
				case '>=':
				case '<=':
				case '!=':
					if (!self::__filter_row_compare($row_field, $method, $value)) {
						return false;
					}
					break;
				case 'like':
					if (strpos($row_field, $value) === false) {
						return false;
					}
					break;
				case 'between':
					if (is_array($row_field) && array_key_exists('from', $row_field)) {
						$compare_value_from = $row_field['from'];
						$compare_value_to = $row_field['to'];
					} else {
						$compare_value_from = $row_field;
						$compare_value_to = $row_field;
					}

					if ($compare_value_from > $value['to']) {
						return false;
					}

					if ($compare_value_to < $value['from']) {
						return false;
					}
					break;
				case 'find_in_set':
					if (!array_intersect($value, explode(',', $row_field))) {
						return false;
					}

					break;
				default:
					break;
			}
		}

		return $row;
	}

	/**
	 * __filter_row_compare
	 *
	 * @param  integer $from_value
	 * @param  string $compare
	 * @param  integer $to_value
	 * @return boolean
	 */
	private static function __filter_row_compare($from_value, $compare, $to_value) {

		switch ($compare) {
			case '>':
				return $from_value > $to_value;
			case '<':
				return $from_value < $to_value;
			case '=':
				return $from_value == $to_value;
			case '>=':
				return $from_value >= $to_value;
			case '<=':
				return $from_value <= $to_value;
			case '!=':
				return $from_value != $to_value;
			default :
				return false;
		}

		return false;
	}

	/**
	 * identical_values
	 * 比较两个数组是否完全相等
	 *
	 * <example>
	 * $array1 = array("red", "green", "blue");
	 * $array2 = array("green", "red", "blue");
	 * $array3 = array("red", "green", "blue", "yellow");
	 * $array4 = array("red", "yellow", "blue");
	 * $array5 = array("x" => "red", "y" => "green", "z" => "blue");
	 *
	 * identical_values($array1, $array2);  // true
	 * identical_values($array1, $array3);  // false
	 * identical_values($array1, $array4);  // false
	 * identical_values($array1, $array5);  // true
	 * </example>
	 *
	 * @param  array $array1
	 * @param  array $array2
	 * @return boolean
	 */
	static public function identical_values($array1, $array2) {

		if (count($array1) != count($array2)) {
			return false;
		}

		sort($array1);
		sort($array2);

		return $array1 == $array2;
	}
	
	/**
	 * 数组重整
	 * 1， 消除所有的key，Json格式化为 [] 
	 * 2， 所有数字类型转为字符串类型
	 * @param unknown $array
	 * @param string $keep_type 是否保留数字类型
	 */
	static public function rebuildForApp($array, $keep_type = false){
		$re = [];
		foreach($array as $key=>$val){
			if(is_array($val)){
				$re[] = self::rebuildForApp($val);
			}elseif(is_numeric($var)){
				$re[] = "" . $val;
			}else{
				$re[] = $val;
			}
		}
		return $re;
	}

	/**
	 * 根据 keyList 精简 array 中的字段
	 * @param unknown $array
	 * @param unknown $keyList
	 * @param bool $stripNull 如果array中不存在keyList中的字段，结果中是否保留该字段
	 * @return NULL[]
	 */
	static public function slim($array, $keyList, $stripNull = false, $defaultVal = null){
		$re = array();
		foreach($keyList as $key){
			if(isset($array[$key]))$re[$key] = $array[$key];
			else{
				if(! $stripNull) $re[$key] = $defaultVal;
			}
		}
		return $re;
	}
	
	/**
	 * 从 $request 中构造 array
	 * @param unknown $request
	 * @param unknown $keyList
	 * @param string $stripNull
	 * @return NULL[]
	 */
	static public function fromRequest($request, $keyList, $stripNull=false){
		$re = array();
		foreach($keyList as $key){
			if(!$request->isNull($key))$re[$key] = $request->$key;
			else{
				if(! $stripNull) $re[$key] = null;
			}
		}
		return $re;
	}
	
	/**
	 * 根据数组的某个字段进行排序 【主键不丢失】
	 * @param unknown $array
	 * @param unknown $item
	 * @param string $desc 是否倒序
	 * @param string $mode  'int':整数, 'float':小数, 'string':字符串 
	 * @return unknown
	 */
	static public function sortArray($array, $item, $desc=false, $mode='string'){
		foreach($array as $k=>$v){
			switch($mode){
				case 'string': $sort[$k] = strval($v[$item]);break;
				case 'int': $sort[$k] = intval($v[$item]);break;
				case 'float': $sort[$k] = floatval($v[$item]);break;
				default: $sort[$k] = $v[$item];break;
			}
		}
		if($desc) arsort($sort);
		else asort($sort);
		foreach($sort as $k=>$v){
			$re[$k] = $array[$k];
		}
		return $re;
	}
	
	/**
	 * 主键名改小写
	 * @param unknown $array
	 * @param unknown $map
	 */
	static public function keyToLower($array){
		$re = [];
		foreach($array as $k=>$v){
			$re[strtolower($k)] = $v;
		}
		return $re;
	}
	/**
	 * 主键 驼峰转下划线
	 * @param unknown $array
	 * @return unknown[]
	 */
	static public function keyToUpper($array){
		$re = [];
		foreach($array as $k=>$v){
			$re[\tools\Util::humpToUnderline($number)] = $v;
		}
		return $re;
	}
	/**
	 * 主键替换
	 * @param unknown $array
	 * @param unknown $map
	 * @return unknown[]
	 */
	static public function keyToChange($array, $map){
		$re = [];
		foreach($array as $k=>$v){
			if($map[$k]) $k = $map[$k];
			$re[$k] = $v;
		}
		return $re;
	}
	/**
	 * 值转字符串
	 * @param unknown $array
	 * @return unknown
	 */
	static public function toString($array){ 
		if(!is_array($array)) return $array;
		$re = [];
		foreach($array as $k=>$v){
			if(is_array($v)) $re[$k] = self::toString($v);
			elseif($v === null || $v === false) $v = '';
			else $re[$k] = strval($v);
		}
		return $re;
	}

	/**
	 * 去掉数字键的值（例如：用于 mysql->query返回的结果）
	 * @param unknown $array
	 */
	static public function stripNumberKey($array){
		$key = 0;
		while($array[$key]){
			unset($array[$key]);
			$key++;
		}
		return $array;
	}

    /**
     * 根据列分组
     * - 需求需要一个key(默认列值),但是的有重复项
     * @param $array
     * @param null $group_key
     * @param $assoc 关联key,php习惯
     * @return array
     */
    static function group($array, $group_key_name = null, $assoc = false)
    {
        $rt = [];
        foreach ((array)$array as $key => $item) {
            if (isset($item[$group_key_name])) {
                $group_key=$item[$group_key_name];
                if ($assoc) {
                    $rt[$group_key][$key] = $item;
                } else {
                    $rt[$group_key][] = $item;
                }
            }
        }
        return $rt;
    }

}
