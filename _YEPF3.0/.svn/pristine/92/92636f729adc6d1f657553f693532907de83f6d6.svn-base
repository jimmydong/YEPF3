<?php
/**
 * @name Page.class.php
 * @desc 分页类
 * @author zhangping
 * @createtime 2009-03-23 12:05
 * @updatetime
 */
namespace ext;

if(!defined('YOKA')) exit('Illegal Request');

class Page
{
	/**
	 * 生成分页的字符串,页码的url参数如下： &p=1,2,3
	 *
	 * @param int $total_rows 总记录数
	 * @param int $currentpage	当前第几页
	 * @param int $page_size	每页几条
	 * @param int $page_numbers 页码数 10个数字or 20个数字
	 * @param string $href	链接字符串
	 * @return  a string of pages.	
	 */
	function fun_get_pages($total_rows, $current_page, $page_size=10, $page_numbers=10, $class='', $href_string='')
	{
		//total pages
		if($total_rows <= $page_size)
		{
			return '';
		}
		$current_page = $current_page == 0 ? 1 : $current_page;
		$pages = intval(($total_rows / $page_size));
		if($total_rows % $page_size > 0)
		{
			$pages ++;
		}
		
		//home_page & end_page
		$start = $current_page - intval($page_numbers / 2);
		$end = $current_page + intval($page_numbers / 2);
		if($start <= 0)
		{
			$start = 1;
			$end = $start + $page_numbers -1;
		}
		if($end > $pages)
		{
			$end = $pages;
			$start = $end - $page_numbers +1;
		}
		if($start <=0)
		{
			$start = 1;
		}
		// current_url, remove p=1,2,3
		if($href_string == '')
		{
			$href_string = $_SERVER['PHP_SELF'] . '?';
			$params = $_SERVER['QUERY_STRING'];
			$params = str_replace('&amp;', '&', $params);
			$params_array = explode('&', $params);
			foreach($params_array as $param)
			{
				$index = strpos($param, '=');
				$key = substr($param, 0, $index);
				if($key != 'p')
				{
					$href_string .= $param . '&';
				}
			}
			$href_string = rtrim($href_string,'&');
		}
		$str = "";
		
		//build home
		if($current_page > 1)
		{
			$str .= "<a href='{$href_string}&p=1' class='{$class}' title='首页'>首页</a>&nbsp;";
		}
		else 
		{
			//$str .= '<font color="#808080">首页</font>&nbsp;';
		}
		// build prev page
		if($current_page > 1)
		{
			$prev_page = $current_page -1 ;
			$str .= "<a href='{$href_string}&p={$prev_page}' class='{$class}' title='上页'>上页</a>&nbsp;";
		}
		else 
		{
			//$str .= '<font color="#808080">上页</font>&nbsp;';
		}
		
		// build page
		for($i=$start;$i<=$end;$i++)
		{
			if($i == $current_page)
			{
				$str .= "<span>{$i}</span> ";
			}
			else
			{
				$str .= " <a href='{$href_string}&p={$i}' class='{$class}' title='第 $i 页'>[{$i}]</a> ";
			}
		}
		
		if($current_page < $pages)
		{
			$next_page = $current_page + 1 ;
			if($next_page > $pages)
			{
				$next_page = $pages;
			}
			$str .= "<a href='{$href_string}&p={$next_page}' class='{$class}' title='下页'>下页</a>&nbsp;";
		}
		else 
		{
			//$str .= '<font color="#808080">下页</font>&nbsp;';
		}
		
		if($current_page < $pages)
		{
			$str .= "<a href='{$href_string}&p={$pages}' class='{$class}' title='末页'>末页</a>";
		}
		else 
		{
			//$str .= '<font color="#808080">末页</font>';
		}
		
		/*
		// page infomation
		$str .= <<<EOF
		<!--
		当前是第<font color="red">{$current_page}</font>页,
		共有<font color="red">{$pages}</font>页,
		<font color="red">{$total_rows}</font>条记录
		-->
	EOF;
		*/
		return '<font face="宋体">' . $str . '</font>';
	}


//数字不带[] 中括号
function fun_get_pages_new($total_rows, $current_page, $page_size=10, $page_numbers=10, $class='', $href_string='')
{
	//total pages
	if($total_rows <= $page_size)
	{
		return '';
	}
	$pages = intval(($total_rows / $page_size));
	if($total_rows % $page_size > 0)
	{
		$pages ++;
	}
	
	//home_page & end_page
	$start = $current_page - intval($page_numbers / 2);
	$end = $current_page + intval($page_numbers / 2);
	if($start <= 0)
	{
		$start = 1;
		$end = $start + $page_numbers -1;
	}
	if($end > $pages)
	{
		$end = $pages;
		$start = $end - $page_numbers +1;
	}
	if($start <=0)
	{
		$start = 1;
	}
	// current_url, remove p=1,2,3
	if($href_string == '')
	{
		$href_string = $_SERVER['PHP_SELF'] . '?';
		$params = $_SERVER['QUERY_STRING'];
		$params = str_replace('&amp;', '&', $params);
		$params_array = explode('&', $params);
		foreach($params_array as $param)
		{
			$index = strpos($param, '=');
			$key = substr($param, 0, $index);
			if($key != 'p')
			{
				$href_string .= $param . '&';
			}
		}
		$href_string = rtrim($href_string,'&');
	}
	$str = "";
	
	//build home
	if($current_page > 1)
	{
		$str .= "<a href='{$href_string}&p=1' class='{$class}' title='首页'>首页</a>&nbsp;";
	}
	else 
	{
		//$str .= '<font color="#808080">首页</font>&nbsp;';
	}
	
	// buile prev page
	if($current_page > 1)
	{
		$prev_page = $current_page -1 ;
		if($prev_page < 1)
		{
			$prev_page = 1;
		}
		$str .= "<a href='{$href_string}&p={$prev_page}' class='{$class}' title='上一页'>上一页</a>";
	}
	//else 
	{
		//$str .= '<span><a>上一页</a></span>&nbsp;';
	}
	
	// build page
	for($i=$start;$i<=$end;$i++)
	{
		if($i == $current_page)
		{
			$str .= "<span>{$i}</span>";
		}
		else
		{
			$str .= " <a href='{$href_string}&p={$i}' class='{$class}' title='第 $i 页'>{$i}</a> ";
		}
	}
	
	if($current_page < $pages)
	{
		$next_page = $current_page + 1 ;
		if($next_page > $pages)
		{
			$next_page = $pages;
		}
		$str .= "<a href='{$href_string}&p={$next_page}' class='{$class}' title='下一页'>下一页</a>";
	}
	//else 
	{
		//$str .= '&nbsp;<span><a>下一页</a></span>';
	}
	
	if($current_page < $pages)
	{
		$str .= "<a href='{$href_string}&p={$pages}' class='{$class}' title='末页'>末页</a>";
	}
	else 
	{
		//$str .= '<font color="#808080">末页</font>';
	}
	
	/*
	// page infomation
	$str .= <<<EOF
	<!--
	当前是第<font color="red">{$current_page}</font>页,
	共有<font color="red">{$pages}</font>页,
	<font color="red">{$total_rows}</font>条记录
	-->
EOF;
	*/
	
	// page infomation
	$str .= <<<EOF
	<a>共有<font color="red">{$pages}</font>页</a>
EOF;
	
	return '<font face="宋体">' . $str . '</font>';
}
/**
 * 不刷新翻页
 *
 * @param int $total_rows 总记录数
 * @param int $current_page 当前页
 * @param int $page_size 每页几条
 * @param int $page_numbers 共显示多少个页码
 * @param string $function_name js函数名
 * @param string $div_id 显示结果的div的ID
 * @param string $class 链接样式
 * @param string $href_string 链接地址，如果为空，则取当前地址栏中的地址
 * @return string 
 */
function fun_get_ajax_pages($total_rows, $current_page, $page_size=10, $page_numbers=10, $function_name, $div_id, $class='', $href_string='')
{
	//total pages
	if($total_rows <= $page_size)
	{
		return '';
	}
	$pages = intval(($total_rows / $page_size));
	if($total_rows % $page_size > 0)
	{
		$pages ++;
	}
	
	//home_page & end_page
	$start = $current_page - intval($page_numbers / 2);
	$end = $current_page + intval($page_numbers / 2);
	if($start <= 0)
	{
		$start = 1;
		$end = $start + $page_numbers -1;
	}
	if($end > $pages)
	{
		$end = $pages;
		$start = $end - $page_numbers +1;
	}
	if($start <=0)
	{
		$start = 1;
	}
	// current_url, remove p=1,2,3
	if($href_string == '')
	{
		$href_string = $_SERVER['PHP_SELF'] . '?';
		$params = $_SERVER['QUERY_STRING'];
		$params = str_replace('&amp;', '&', $params);
		$params_array = explode('&', $params);
		foreach($params_array as $param)
		{
			$index = strpos($param, '=');
			$key = substr($param, 0, $index);
			if($key != 'p')
			{
				$href_string .= $param . '&';
			}
		}
		$href_string = rtrim($href_string,'&');
	}
	$str = "";
	
	// buile prev page
	if($current_page > 1)
	{
		$prev_page = $current_page -1 ;
		$str .= "<a href=\"javascript:{$function_name}('{$href_string}p={$prev_page}', '{$div_id}');\" class='{$class}' title='上一页'>上一页</a>";
	}
	else 
	{
		$str .= '<font color="#808080">上一页</font>&nbsp;';
	}
	
	// build page
	for($i=$start;$i<=$end;$i++)
	{
		if($i == $current_page)
		{
			$str .= "<span>{$i}</span>";
		}
		else
		{
			$str .= " <a href=\"javascript:{$function_name}('{$href_string}p={$i}', '{$div_id}');\" class='{$class}' title='第 $i 页'>[{$i}]</a> ";
		}
	}
	
	if($current_page < $pages)
	{
		$next_page = $current_page + 1 ;
		if($next_page > $pages)
		{
			$next_page = $pages;
		}
		$str .= "<a href=\"javascript:{$function_name}('{$href_string}p={$next_page}', '{$div_id}');\" class='{$class}' title='下一页'>下一页</a>";
	}
	else 
	{
		$str .= '&nbsp;<font color="#808080">下一页</font>';
	}
	/*
	// page infomation
	$str .= <<<EOF
	<!--
	当前是第<font color="red">{$current_page}</font>页,
	共有<font color="red">{$pages}</font>页,
	<font color="red">{$total_rows}</font>条记录
	-->
EOF;
	*/
	return '<font face="宋体">' . $str . '</font>';
}

/**
 * 同fun_get_pages 函数，只是去掉了中间的页码显示
 */
function fun_get_prev_next_pages($total_rows, $current_page, $page_size=10, $class='', $href_string='')
{
	//total pages
	if($total_rows <= $page_size)
	{
		return '';
	}
	$pages = intval(($total_rows / $page_size));
	if($total_rows % $page_size > 0)
	{
		$pages ++;
	}
	
	// current_url, remove p=1,2,3
	if($href_string == '')
	{
		$href_string = $_SERVER['PHP_SELF'] . '?';
		$params = $_SERVER['QUERY_STRING'];
		$params = str_replace('&amp;', '&', $params);
		$params_array = explode('&', $params);
		foreach($params_array as $param)
		{
			$index = strpos($param, '=');
			$key = substr($param, 0, $index);
			if($key != 'p')
			{
				$href_string .= $param . '&';
			}
		}
		$href_string = rtrim($href_string,'&');
	}
	$str = "";
	
	// buile prev page
	if($current_page > 1)
	{
		$prev_page = $current_page -1 ;
		$str .= "<a href='{$href_string}&p={$prev_page}' class='{$class}' title='上一页'>上一页</a>";
	}
	else 
	{
		$str .= '<font color="#808080">上一页</font>&nbsp;';
	}
	
		
	if($current_page < $pages)
	{
		$next_page = $current_page + 1 ;
		if($next_page > $pages)
		{
			$next_page = $pages;
		}
		$str .= "<a href='{$href_string}&p={$next_page}' class='{$class}' title='下一页'>下一页</a>";
	}
	else 
	{
		$str .= '&nbsp;<font color="#808080">下一页</font>';
	}
	/*
	// page infomation
	$str .= <<<EOF
	<!--
	当前是第<font color="red">{$current_page}</font>页,
	共有<font color="red">{$pages}</font>页,
	<font color="red">{$total_rows}</font>条记录
	-->
EOF;
	*/
	return '<font face="宋体">' . $str . '</font>';
}
}
?>
