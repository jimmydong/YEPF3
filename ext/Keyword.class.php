<?php
/**
 * @name Keyowrd.class.php
 * @desc 关键词的(查找,标红,替换)。适用于关键词很多的情况，比如审核系统中。30000个关键词时效率是str_replace的几百倍
 * @author 曹晓冬
 * @createtime 2009-04-02 13:35
 * @updatetime 
 * @usage 
 * $word = array('世上很少有人', '观音法门','金瓶梅','催眠药','假的','锦淘');
 * $str = <<<ITEM
 * 这样形成的。你说：每想我一次你就放一个屁，臭氧层就是这样形成的。观音法门
 * ITEM;
 * $words = Keyword::formatKeyword($word);//可以将格式化好的关键词数组缓存,效果更加明显
 * Keyword::signKeyword($words, $str , 'red', $count);
 * Keyword::searchKeyword($words, $str);
 */
namespace ext;

class Keyword
{
	/*项目为UTF8编码请填2,GBK编码请填1*/
	const HY_LEN = 2 ;
	/**
	 * @name findKeyword
	 * @desc 查找指定的关键词是否存在
	 * @param array $words  	格式化后的关键字数组
	 * @param string $string	待匹配的正文内容
	 * @param bool $combine		是否合并相同的关键字
	 * @return mixed  array or false
	 */
	public function searchKeyword($words, $string, $combine = true)
	{
		$keywords = self::findKeyword($words, $string, false);
		if(true === $combine)
		{
			$keywords = array_unique($keywords);	
		}
		return $keywords;
	}
	/**
	 * @name replaceKeyword
	 * @desc 将指定的字符串替换
	 * @param array $searep		需要替换的数组, key为查找的词, value为替换的值
	 * @param string $string	待匹配的正文内容
	 * @param array $words		格式化后的关键字数组
	 * @return string
	 */
	public function replaceKeyword($searep, $string, $words = '')
	{
		if(empty($words))
		{
			$words = self::formatKeyword(array_keys($searep));
		}
		$keywords = self::searchKeyword($words, $string, true);
		if(empty($keywords))
		{
			return $string ;
		}
		$replace = array();
		foreach ($keywords as $k => $v)
		{
			$replace[$k] = $searep[$v];	
		}
		return str_replace($keywords, $replace, $string);
	}
	/**
	 * @name signKeyword
	 * @desc  标记存在的关键词
	 * @param string $words		格式化后的关键字数组
	 * @param string $string	待匹配的正文内容
	 * @param string $prefix	关键词标记的前缀
	 * @param string $suffix	关键词标记的后缀
	 * @param int $count		关键词总数
	 */
	public function signKeyword($words, $string, $prefix = '<font color="red">', $suffix = '</font>', &$count = 0)
	{
		$keywords = self::findKeyword($words, $string, true, $count);
		if($count == 0)
		{
			return $string ;
		}
		$keywords = array_flip(array_reverse($keywords, true));
		foreach ($keywords as $k => $v)
		{
			$string = substr($string, 0, $k) . $suffix . substr($string, $k);
			$string = substr($string, 0, $v) . $prefix . substr($string, $v);
		}
		return $string ;
	}
	/**
	 * @name findKeyword
	 * @desc 查找指定的字符串
	 * @param string $words 格式化后的关键字数组
	 * @param string $str   待匹配的正文内容
	 * @param bool $pt		返回位置信息还是关键字列表
	 * @param int $count	关键词数量
	 * @return array
	 */
	public function findKeyword($words, $str, $pt = true, &$count = 0)
	{
		$queue = $position =array();
		$len = strlen($str);
		$code = '' ;
		$offset = 0;
		$count = 0 ;
		for ($i = 0 ; $i < $len ; $i++)
		{
			//寻找到需要处理的ord
			$ord = ord($str[$i]);
			if($ord >= 128)
			{
				$code .= $ord ;
				if($offset < self::HY_LEN)
				{
					$offset ++ ;
					continue;
				}else 
				{
					$offset = 0 ;
				}
			}else 
			{
				$code = $ord;
			}
			if(!empty($queue))
			{
				foreach ($queue as $k => $v)
				{
					if(!isset($v[$code]))
					{
						unset($queue[$k]);
						continue;
					}
					$next = $v[$code] ;
					if(!isset($next['value']))
					{
						$queue[$k] = $next;
					}else 
					{
						$length = $next['value'] ;
						$begin = $i - $length  + 1 ;
						$count ++ ;
						if($pt === true)
						{
							$position[$begin] = $i + 1 ;
						}else 
						{
							$position[] = substr($str, $begin, $length) ;
						}
						if(count($next) > 1)
						{
							$queue[$k] = $next ;
							unset($queue[$k]['value']);
						}else 
						{
							unset($queue[$k]);	
						}
					}
				}
			}
			//如果在新需要查找的字符,放入对列
			if(isset($words[$code]))
			{
				$queue[] = $words[$code];
			}
			$code = '' ;
		}
		return $position;
	}
	/**
	 * @name formatKeyword
	 * @desc 将关键词格式化
	 * @param array $word	待格式化的关键词数组
	 * @return array
	 */
	public function formatKeyword($word)
	{
		$separator = "|";
		$ascii = array();
		foreach ($word as $k => $v)
		{
			//todo 需要过滤掉所有特殊字符,允许的字符只包括,标点符号、大小写字母、数字及汉字
			$code = '' ;
			$offset = 0;
			//生成需要的格式
			$len = strlen($v);
			$tmp = &$ascii ;
			for ($i = 0 ; $i < $len; $i++)
			{
				$ord = ord($v[$i]);
				$code .= $ord;
				if($ord >= 128)
				{
					if($offset == self::HY_LEN)
					{
						if(!isset($tmp[$code]))
						{
							$tmp[$code] = array();
						}
						$tmp = &$tmp[$code];
						$code = "" ;
						$offset = 0 ;
						continue;
					}
					$offset ++ ;
				}else
				{
					if(!isset($tmp[$code]))
					{
						$tmp[$code] = array();
					}
					$tmp = &$tmp[$code];
					$code = "" ;
				}
			}
			$tmp['value'] = $len ;
		}
		return $ascii;
	}
}
?>