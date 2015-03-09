<?php
/**
 * @name SearchEngine.class.php
 * @desc YEPF搜索引擎操作类
 * @author yangguang
 * @createtime 2009-07-30
 * @updatetime
 * 
 * @usage 
 * 简单使用:
 * $cls = new SearchEngine();
 * $result = $cls->Search("star_id:[0 TO *]");
 * print_r($result) ;
 * echo "记录数:{$cls->GetResultCount()}" ;
 * 
 * 翻页:
 * $result = $cls->Search("star_id:[0 TO *]", $page*$pagesize);
 * 
 * 高级使用:
 * $cls = new SearchEngine($searchServer);
 * //$cls->SetServer($searchServer);
 * $cls->SetResultFormat(array('result_key'=>'[category]'));
 * $cls->SetResultFormat('sub_as_array', true);
 * $cls->SetResultFormat('result_full', true);
 * $cls->SetParam('sort', 'view_count desc');
 * $result = $cls->Search("star_id:[0 TO *]");
 * print_r($result) ;
 * echo "记录数:{$cls->GetResultCount()}" ;
 *
 **/
namespace yoka;

class SearchEngine
{
	private $config ;
	private $dict ;
	public $result ;
	private $param ;
	private $resultFormat ;

	public function __construct($url='')
	{
		$this->Init();
		$this->Reset();
		$this->SetServer($url);
	}

	/**
	 * 初始化
	 *
	 */
	private function Init()
	{
		$this->config['server'] = "http://119.161.129.213:8080/star/select/" ;
		$this->config['page_size_min'] = 10 ;
		$this->config['page_size_max'] = 5000 ;

		$this->dict['valid_query_key'] = array(
		'query', 		//查询关键词
		'start',		//开始记录位置
		'rows' , 		//每页记录数
		'sort' , 		//排序方式
		'fl' , 			//返回字段
		) ;

		$this->dict['valid_result_format_key'] = array(
		'result_key', 		//做为结果集主键的字段, 可用"[$key1][$key2]"的方式使用多列主键
		'sub_as_array',		//true/false, 默认为false,当设置result_key时, 把此键的结果都存为数组 $result[$key] = array([]=>array(), []=>array()),
		'sub_only_first' , 	//true/false, 默认为false,当设置result_key时, 只保留第一条记录
		'sub_only_last' , 	//true/false, 默认为true, 当设置result_key时, 只保留最后一条记录
		'result_full' , 	//true/false, 默认为false,true=返回所有结果, 包括记录数和原始xml, false=只返回结果集
		'result_count_only' , 	//true/false, result_full=false时生效,默认为false,false=返回所有结果, true=只返回结果集
		) ;
	}
	
	/**
	 * 重置所有查询参数
	 *
	 */
	function Reset()
	{
		$this->result = array() ;
		$this->resultFormat = array() ;
		$this->param = array() ;
	}

	/**
	 * 设置搜索引擎地址
	 *
	 * @param string $url
	 */
	public function SetServer($url)
	{
		$url = trim($url) ;
		if(strlen($url))
		{
			$this->config['server'] = $url ;
		}
	}

	/**
	 * 设置查询参数
	 *
	 * @param string $key
	 * @param mix $value
	 */
	public function SetParam($key, $value)
	{
		$this->param[$key] = $value ;
	}

	/**
	 * 设置返回结果的格式
	 *
	 * @param string/array $mixKey
	 * @param [optional]string $value
	 * 
	 * 参数定义见init()
	 */
	public function SetResultFormat($mixKey, $value='')
	{
		if(is_string($mixKey))
		{
			if(in_array($mixKey, $this->dict['valid_result_format_key']))
			{
				$this->resultFormat[$mixKey] = $value ;
			}
		}
		elseif (is_array($mixKey))
		{
			foreach ($mixKey as $mKey=>$mValue)
			{
				if(in_array($mKey, $this->dict['valid_result_format_key']))
				{
					$this->resultFormat[$mKey] = $mValue ;
				}
			}
		}
	}

	/**
	 * 搜索结果
	 *
	 * @param string $query
	 * @param [optional]int $start
	 * @param [optional]int $rows
	 * @param [optional]string $sort
	 * @param [optional]string $fl
	 * @return array
	 */
	public function Search($query, $start=0, $rows=10, $sort='', $fl='')
	{
		$query = trim($query) ;
		if( strlen($query) > 0)
		{
			$queryParam = $this->GetSearchParam($query, $start, $rows, $sort, $fl) ;

			$this->result['result_raw'] = file_get_contents("{$this->config['server']}?{$queryParam}") ;
			if( $this->IsValidResult())
			{
				$this->ParseResult();
			}
			else 
			{
				
			}

		}
		else
		{
			// 关键词不能为空
			$this->result['result_list'] = false;
			$this->result['status'] = false ;
			$this->result['status_str'] = "关键词不能为空" ;
		}

		if( $this->resultFormat['result_full'])
		{
			return $this->result ;
		}
		elseif ( $this->resultFormat['result_count_only'])
		{
			return $this->result['result_count'] ;
		}
		else
		{
			return $this->result['result_list'] ;
		}
	}



	/**
	 * 读取结果总数
	 *
	 * @return int
	 */
	public function GetResultCount()
	{
		return (int)$this->result['result_count'] ;
	}

	/**
	 * 参数使用顺序: 函数调用是的参数>预先set的参数>默认参数
	 *
	 * @param string $query
	 * @param int $start
	 * @param int $rows
	 * @param string $sort
	 * @param string $fl
	 */
	private function GetSearchParam($query, $start, $rows, $sort, $fl)
	{
		// 查询关键词
		$query = (strlen(trim($query))>0) ? trim($query) : trim($this->param['query']) ;
		$searchParam['query'] = "q=".urlencode($query) ;

		// 起始记录
		$searchParam['start'] = "start=".max(0, (intval($start)>0) ? intval($start) : intval($this->param['start']) ) ;

		// 每页数量
		$searchParam['rows']  = "rows=".min($this->config['page_size_max'], max($this->config['page_size_min'], (intval($rows)>0) ? intval($rows) : intval($this->param['rows']) )) ;

		// 排序方式
		$sort = (strlen(trim($sort))>0) ? trim($sort) : trim($this->param['sort']) ;
		if(strlen($sort)>0)
		{
			$searchParam['sort'] = "sort=".urlencode($sort) ;
		}

		// 返回字段
		$fl = (strlen(trim($fl))>0) ? trim($fl) : trim($this->param['fl']) ;
		if(strlen($fl)>0)
		{
			$searchParam['fl'] = "fl=".urlencode($fl) ;
		}
		return implode("&", $searchParam) ;
	}
	
	/**
	 * 对结果进行解析
	 *
	 */
	private function ParseResult ()
	{
		$xml = new DOMDocument();
		$xml->loadXML($this->result['result_raw']);

		$resultDom = $xml->getElementsByTagName("result");
		$this->result['result_count'] = $resultDom->item(0)->attributes->getNamedItem('numFound')->nodeValue ;

		// 得到状态
		$pResultStatus = "/<int name=\"status\">([0-9-]+)<\/int>/is" ;
		preg_match($pResultStatus, $this->result['result_raw'], $resultStatusRet) ;
		$this->result['status'] = ! $resultStatusRet[1] ;

		if( ! $this->resultFormat['result_count_only'] )
		{
			// 得到结果
			$resultDocList = $resultDom->item(0)->getElementsByTagName("doc");

			$resultCnt = 0 ;
			foreach ($resultDocList as $resultItem)
			{
				$resultId = false ;
				$useResultKey = false ;
				$resultKey = $this->resultFormat['result_key'] ;

				$resultValues = $resultItem->getElementsByTagName('*') ;
				$tmpValues = array();
				foreach ($resultValues as $item)
				{
					// 对结果键值进行处理
					$resultKey = str_replace("[{$item->attributes->item(0)->nodeValue}]", $item->nodeValue, $resultKey) ;

					// 把结果暂存
					$tmpValues[$item->attributes->item(0)->nodeValue] = $this->FormatResultValue($item->nodeValue, $item->nodeName) ;
				}

				if( $resultKey == $this->resultFormat['result_key'])
				{
					// 未被替换, 不使用键值.
					$resultId = $resultCnt ;
					$useResultKey = false ;
				}
				else
				{
					$resultId = $resultKey ;
					$useResultKey = true ;
				}

				// 只保留最后一个同键记录
				if( $useResultKey )
				{
					if( $this->resultFormat['sub_as_array'])
					{
						$this->result['result_list'][$resultId][] = $tmpValues ;
					}
					elseif ($this->resultFormat['sub_only_first'])
					{
						if( ! isset($this->result['result_list'][$resultId]))
						{
							$this->result['result_list'][$resultId] = $tmpValues ;
						}
					}
					else
					{
						$this->result['result_list'][$resultId] = $tmpValues ;
					}
				}
				else
				{
					$this->result['result_list'][$resultId] = $tmpValues ;
				}

				$resultCnt ++ ;
			}
		}

	}


	/**
	 * 判断查询是否成功. 
	 *
	 * @return bool
	 */
	private function IsValidResult()
	{
		$pValieResult = "/^<\?xml version=\"1.0\" encoding=\"UTF-8\"\?>.*<\/response>$/is" ;
		return preg_match($pValieResult, $this->result['result_raw']) ;
		
		
	}

	/**
	 * 格式化输出结果
	 *
	 * @param string $value
	 * @param string $type
	 * @return mix
	 */
	private function FormatResultValue($value, $type)
	{
		switch ($type)
		{
			case 'str':
				return (string)$value ;
				break;
			case 'int':
				return (int)$value;
				break;
			case 'date' :
				// 暂不操作
				return $value;
				break;
			default:
				return $value;
		}
	}
}
?>