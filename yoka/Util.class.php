<?php
namespace yoka;

/**
 * 通用处理类
 * Enter description here ...
 * @author sam
 *
 */
class Util{
	//获取客户端IP
	public static function getIp(){
		/* 
		 * 访问时用localhost访问的，读出来的是“::1”是正常情况。 
		 * ：：1说明开启了ipv6支持,这是ipv6下的本地回环地址的表示。 
		 * 使用ip地址访问或者关闭ipv6支持都可以不显示这个。 
		 * */  
		if (isset($_SERVER)) {  
			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {  
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];  
			} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {  
				$ip = $_SERVER["HTTP_CLIENT_IP"];  
			} else {  
				$ip = $_SERVER["REMOTE_ADDR"];  
			}  
		} else {  
			if (getenv('HTTP_X_FORWARDED_FOR')) {  
				$ip = getenv('HTTP_X_FORWARDED_FOR');  
			} elseif (getenv('HTTP_CLIENT_IP')) {  
				$ip = getenv('HTTP_CLIENT_IP');  
			} else {  
				$ip = getenv('REMOTE_ADDR');  
			}  
		}  
		if(trim($ip)=="::1"){  
			$ip="127.0.0.1";  
		}  
		return $ip;   
	}
	
	public static function e404(){
		$url = $_SERVER['REDIRECT_SCRIPT_URL']?:$_SERVER['SCRIPT_RUL'];
		if($url){
			//upload|storage
			if(strpos($url, '/storage/') === 0){
				//upload转storage兼容处理
				$real_filename = \yoka\FileUpload::getRealPath(substr($url,9)); //文件绝对路径
				$file = str_replace('/storage/', '/upload/', $real_filename);
				if(file_exists($file)){
					if(preg_match('/\.jpg|\.png|\.gif|\.bmp/i', $url)){
						header("HTTP/1.1 200 OK");
						header("Content-type: image/jpg");
						echo file_get_contents($file);
					}elseif(preg_match('/\.mp3|\.wav|\.mp4/i', $url))
					{
						header("HTTP/1.1 301 Moved Permanently");
						header('location: ' . str_replace('/storage/','/upload/', $url));
					}else{
						header("HTTP/1.1 200 OK");
						echo file_get_contents($file);
					}
					exit;
				}
				echo $url;
				//图片自动缩放处理
				if(preg_match('/(storage|upload)\/([0-9]+\/[0-9a-f]+)_([0-9A]+)x([0-9A]+)(\.jpg|\.png|\.gif|\.bmp)/i', $url, $reg)){
					$real_filename = \yoka\FileUpload::getRealPath($reg['2'].$reg[5]); //获取绝对路径
					if(file_exists($real_filename)){
						$file = $real_filename;
					}else{
						$file = str_replace('/storage/', '/upload/', $real_filename);
					}
					if(file_exists($file)){
						//进行缩放处理
						if($new_file = \tools\ImgTools::resize($file, $reg[3], $reg[4], ['cut'=>1])){
							header("HTTP/1.1 200 OK");
							echo file_get_contents($file);
						}
					}
				}
			}elseif(strpos($url, '/upload/') === 0){
				$file = str_replace('/upload/', '/storage/',\yoka\FileUpload::getRealPath(substr($url,8)));
				if(file_exists($file)){
					if(preg_match('/\.jpg|\.png|\.gif|\.bmp/i', $url)){
						header("HTTP/1.1 200 OK");
						header("Content-type: image/jpg");
						echo file_get_contents($file);
					}elseif(preg_match('/\.mp3|\.wav|\.mp4/i', $url))
					{
						header("HTTP/1.1 301 Moved Permanently");
						header('location: ' . str_replace('/upload/','/storage/', $url));
					}else{
						header("HTTP/1.1 200 OK");
						echo file_get_contents($file);
					}
					exit;
				}
			}

			//404 图片
			if(preg_match('/\.jpg|\.png|\.gif|\.bmp/i', $url)){
				//header("location: http://cdn.yishengdaojia.cn/upload/404.jpg");
				header("location: http://saascdn.yishengdaojia.cn/upload/404.jpg");
				exit;
			}
			//资源文件
			if(preg_match('/\.ico|\.js|\.css|\.eot|\.svg|\.ttf|\.woff/i', $url)){
				echo '';
				exit;
			}
		}
	}
	
	/**
	 * 验证邮箱
	 */
	public static function checkEmail($email){
		if($email=='请输入您的常用邮箱')
			return false;
		if(preg_match("/^[0-9a-zA-Z]+(?:[\.\_\-][a-z0-9\-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]+$/i", $email)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 是否存在特殊字符
	 */
	public static function isExistsSpecialChar($nick){
		$string=$nick;
		$re="/[\?#%^&*@$!`~\/\s\.\",，()\\\<>+=]/isu";
		$m=preg_match($re,$string);
		return $m;
	}

	/**
	 * 批量删除数组中的key和值
	 * @static
	 * @param $arr  处理的数组  注意这里是引用传递
	 * @param $keys<array(k1,k2,k3,kn....)>	删除的键列表
	 * @return  void
	 */
	public static function  batRemoveArrKeys(&$arr,$keys){
		foreach($keys as $k){
			if(isset($arr[$k])||$arr[$k]===null){
				unset($arr[$k]);
			}
		}
	}

	/**
	 * 加权随机数
	 */
	public static function prand($start, $end){
		$start = intval($start); $end = intval($end);
		$dis = $end - $start;
		if($dis < 0)return false;
		if($dis == 0)return $start;
		for($i=1;$i<($dis+1);$i++){
			$max += $i;
		}
		$s = rand(1,$max);
		$j = 0;
		for($i=($dis+1);$i>1;$i--){
			$max = $max - $i;
			if($s > $max)return $start + $j;
			$j++;
		}
		return $end;
	}
	
	/**
	 * 保留数组中指定的字段
	 * @param array $array 目标数组
	 * @param array $filter 过滤设定 eg:['name','age']
	 */
	public static function array_filter($array,$filter){
		$re = array();
		foreach($filter as $key){
			if(isset($array[$key]))$re[$key]=$array[$key];
		}
		return $re;
	}
	
	/**
	 * 数组转为table便于显示
	 * 页面中定制.table样式来控制显示
	 * @param array $array
	 */
	public static function array_table($array){
		foreach($array as $key=>$val){
			$th .="<th>{$key}</th>";
			$td .="<td>{$val}</td>";
		}
		$re = "
		<table class='table table-bordered'>
		<tr>{$th}</tr>
		<tr>{$td}</tr>
		</table>";
		return $re;
	}
	
	/**
	 * 上个月的信息
	 * @param number $last 回溯N月
	 * @param date $date 回溯起始日期（默认为今天） 格式：YYYY-mm-dd
	 * @return [year, month, begin_date, end_date, begin, end]
	 */
	public static function getLastMonth($last = 1, $date = null){
		if($date) $t = strtotime($date . ' 00:00:00');
		else $t = strtotime(date('Y-m', time()) . '-01 00:00:00');
		$begin = strtotime("-{$last} month", $t);
		$end = strtotime("+1 month", $begin) - 1;
		return array(
			'year' => date('Y', $begin),
			'month'=> date('m', $begin),
			'begin_date'=> date('Y-m-d', $begin),
			'end_date'=>date('Y-m-d', $end),
			'begin' => $begin,
			'end'	=> $end
		);
	}
	
	/**
	 * 获取月信息
	 * @param string $month 格式:2018-09
	 */
	public static function getMonthInfo($month){
		if(strpos($month, '-') != 4){
			//日期格式不标准
			if(strpos($month, '-') == 2){
				//兼容  “18-12” 格式
				$month = '20' . $month;
			}elseif(strlen($month) == 6){
				//兼容 “201812” 格式
				$month = substr($month, 0, 4) . '-' . substr($month, 4, 2);
			}elseif(strlen($month) == 4){
				//兼容 “1812” 格式
				$month = '20' . substr($month, 0, 2) . '-' . substr($month, 2, 2);
			}else{
				return false; //未知格式
			}
		}
		
		$begin = strtotime($month . '-01 00:00:00');
		$end = strtotime("+1 month", $begin) - 1;
		return array(
				'year'		=> date('Y', $begin),
				'month'		=> date('m', $begin),
				'begin_date'=> date('Y-m-d', $begin),
				'end_date'	=> date('Y-m-d', $end),
				'begin'		=> $begin,
				'end'		=> $end
		);
	}

	/**
	 * 下个月的信息
	 * @param number $next
	 * @param date $date 回溯起始日期（默认为今天） 格式：YYYY-mm-dd
	 * @return [year, month, begin_date, end_date, begin, end]
	 */
	public static function getNextMonth($next = 1, $date = null){
		if($date) $t = strtotime($date . ' 00:00:00');
		else $t = strtotime(date('Y-m', time()) . '-01 00:00:00');
		$begin = strtotime("+{$next} month", $t);
		$end = strtotime("+1 month", $begin) - 1;
		return array(
				'year' => date('Y', $begin),
				'month'=> date('m', $begin),
				'begin_date'=> date('Y-m-d', $begin),
				'end_date'=>date('Y-m-d', $end),
				'begin' => $begin,
				'end'	=> $end
		);
	}
	
	/**
	 * 获取某个月的每一天
	 * @param string $month 格式：YYYY-MM
	 * @param bool $workDayOnly 仅工作日
	 * @return string['01','02' ... ]
	 */
	public static function getMonthDay($month, $workDayOnly = false){
		$begin = strtotime($month."-01 00:00:00");
		$end = strtotime("+1 month", $begin) -1;
		$re = [];
		while($begin < $end){
			if($workDayOnly && (date('w')==0 || date('w')==6)) continue; //非工作日
			$re[] = date('d', $begin);
			$begin += 24*3600;
		}
		return $re;
	}
	
	/**
	 * 上周的时间信息
	 * 【注意】
	 * 		供统计使用，周定义为：周一~周日
	 * @param number $last  回溯N周
	 * @param date $date 回溯起始日期（默认为今天） 格式：YYYY-mm-dd
	 * @return [begin_date, end_date, begin, end]
	 */
	public static function getLastWeek($last = 1, $date = null){
		if($date) $t = strtotime($date . ' 00:00:00');
		else $t = strtotime(date('Y-m-d') . ' 00:00:00');
		$weekday = date('N', $t); // 1（表示星期一）到 7（表示星期天）
		$begin = $t - (7*$last + $weekday - 1) * 86400; // 周的开始点
		$end = strtotime("+1 week", $begin) - 1;
		return array(
				'begin_date'=> date('Y-m-d', $begin),
				'end_date'=>date('Y-m-d', $end),
				'begin' => $begin,
				'end'	=> $end
		);
	}

	/**
	 * 下周的时间信息
	 * @param number $next
	 * @param date $date 回溯起始日期（默认为今天） 格式：YYYY-mm-dd
	 * @return [begin_date, end_date, begin, end]
	 */
	public static function getNextWeek($next = 1, $date = null){
		if($date) $t = strtotime($date . ' 00:00:00');
		else $t = strtotime(date('Y-m-d') . ' 00:00:00');
		$weekday = date('N', $t); // 1（表示星期一）到 7（表示星期天）
		$begin = $t + (7*$last - $weekday + 1) * 86400; // 周的开始点
		$end = strtotime("+1 week", $begin) - 1;
		return array(
				'begin_date'=> date('Y-m-d', $begin),
				'end_date'=>date('Y-m-d', $end),
				'begin' => $begin,
				'end'	=> $end
		);
	}
	
	/**
	 * 保留N位小数
	 * @param unknown $number
	 * @param number $deci 小数位数
	 * @return number
	 */
	public static function toDecimal($number, $deci = 2){
		for($i=0;$i<$deci;$i++) $number = $number * 10;
		$number = round($number);
		for($i=0;$i<$deci;$i++) $number = $number / 10;
		return $number;
	}
	
	/**
	 * 将数字按十万进制格式化
	 * 【注意：小于1亿！】
	 * @param unknown $number
	 * @param int detail 小数位数.
	 * @param bool $chinese 转为中文大写  //TODO:: wait ...
	 */
	public static function to10k($number, $detail=null, $chinese = false){
		if(!is_numeric($number)) return false;
		 
		if($chinese){
			//TODO:: ... wait
		}else{
			//切分整数小数 
			$format = intval($number);
			$other = $number - $format;
	
			//分段(仅到亿)
			$wan = intval($number / 10000);
			$low = $format - 10000*$wan;
			$yi = intval($wan / 10000);
			if($yi > 0){
				$wan = $wan - 10000*$yi;
				$re = $yi . ',' . self::_to4bit($wan) . ',' . self::_to4bit($low);
			}elseif($wan > 0){
				$re = $wan . ',' . self::_to4bit($low);
			}else{
				$re = $low;
			}
			//补全小数
			if($detail){
				$other = number_format($other, $detail);
			}
			if($other) $re .= '.' . substr($other,2);
		}
		
		return $re;
	}
	public static function _to4bit($number){
		if($number > 9999) $number = $number % 10000; //超过4位
		
		if($number == 0) 	return '0000';
		if($number < 10) 	return '000' . $number;
		if($number < 100)	return '00' . $number;
		if($number < 1000)	return '0' . $number;
		return $number;
	}
	public static function _to3bit($number){
		$number = intval($number);
		if($number > 999) $number = $number % 1000; //超过3位
		
		if($number > 99) return '' . $number;
		elseif($number > 9) return '0' . $number;
		else return '00' . $number;
	}
	public static function _to2bit($number){
		$number = intval($number);
		if($number > 99) $number = $number % 100; //超过2位
		
		if($number > 9) return '' . $number;
		else return '0' . $number;
	}
	
	/**
	 * 验证手机号是否正确
	 * @param $mobile
	 * @return bool
	 */
	public static function isMobile($mobile) {
		if (!is_numeric($mobile)) {
			return false;
		}
		return preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^16[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^19[\d]{9}$#', $mobile) ? true : false;
	}
	
	/**
	 * 加星星安全显示
	 * 
	 * 默认规则： 
	 * 1, 1-2位：全部隐藏
	 * 2, 3-4位：前一显示，其他隐藏
	 * 3, 5-8位： 前1后2显示，中间隐藏
	 * 4, 9-16：  前3后4显示，中间隐藏（适用手机号）
	 * 5, 16以上：前3后4，中间固定 ***...***
	 * 
	 * @param string $str
	 * @param int $bit  隐藏的位数
	 * @param string $encode 编码（默认UTF8）
	 */
	public static function hideMiddle($str, $bit=null, $encode='utf-8'){
		$len = mb_strlen($str, $encode);
		if(is_numeric($bit)){
			$begin = intval(($len - $bit)/2);
			$end = $len - $bit - $begin;
			if($bit > 5) $re = mb_substr($str, 0, $begin) . '******' . mb_substr($str, 0-$end);
			else{
				$re = mb_substr($str, 0, $begin);
				for($i=0;$i<$bit;$i++) $re .= '*';
				$re .= mb_substr($str, 0-$end);
			}
		}else{
			$t = '';
			if($len < 3) {
				$re = '**';
			}elseif($len < 5){
				for($i=0;$i<$len-1;$i++)$t.= '*';
				$re = mb_substr($str, 0, 1, $encode) . $t;
			}elseif($len < 9){
				for($i=0;$i<$len-3;$i++)$t.= '*';
				$re = mb_substr($str, 0, 1, $encode) . $t . mb_substr($str, -2, 2, $encode);
			}elseif($len < 17){
				for($i=0;$i<$len-7;$i++)$t.= '*';
				$re = mb_substr($str, 0, 3, $encode) . $t . mb_substr($str, -4, 4, $encode);
			}else{
				$re = mb_substr($str, 0, 3, $encode) . '***...***' . mb_substr($str, -4, 4, $encode);
			}
		}
		return $re;
	}
	
	/**
	 * 隐藏最后N位
	 * @param unknown $str
	 * @param number $bit
	 * @param string $cncode
	 * @return string
	 */
	public static function hideLast($str, $bit=1, $encode='utf-8'){
		$re = mb_substr($str, 0, mb_strlen($str)-$bit, $encode);
		if($re > 5) $re .= '******';
		else for($i=0;$i<$bit;$i++) $re .= '*';
		
		return $re;
	}
	
	/**
	 * 手机号加*
	 * 
	 * 规则：
	 * 1，至少隐藏两位
	 * 2，结尾最多隐藏四位
	 * 3，开头最多隐藏三位
	 * 4，优先显示后四位
	 * @param unknown $mobile
	 * @param string $encode
	 * @return string
	 */
	public static function hideMobile($mobile, $encode='utf-8'){
		$len = mb_strlen($mobile, $encode);
		if($len < 7){
			$re = '**' . mb_substr($str, 2, $len-2, $encode);
		}elseif($len < 11){
			$re = mb_substr($mobile, 0, $len-7, $encode) . '***' . mb_substr($mobile, -4, 4, $encode);
		}else{
			if($len > 16) $len = 16;
			$re = mb_substr($mobile, 0, 3, $encode) . '****' . mb_substr($mobile, 7-$len, $len-7, $encode);
		}
		return $re;
	}
	
	/**
	 * <br>转换行
	 * nl2br的逆函数
	 * @param unknown $text
	 * @return mixed
	 */
	static public function br2nl($text){
		return preg_replace('/<br\\s*?\/??>/i','',$text);
	}

	/**
	 * 截短字符串
	 * @param unknown $string
	 */
	static public function short($string, $length = 80, $elips = '..', $charset='utf8'){
		if(is_array($string)){
			foreach($string as $k=>$v){
				$re[$k] = self::short($v, $length, $elips, $charset);
			}
			return $re;
		}
		if(mb_strlen($string, $charset) <= $length) return $string;
		$re = mb_substr($string, 0, $length-mb_strlen($elips, $charset)) . $elips;
		return $re;
	}
	
	/**
	 * 获取通行码
	 * 注意： 需配合 checkPassCode 使用
	 * @param number $type  0:当天有效, 1:1小时内有效, 2:10分钟有效 
	 * @param string $passCodeKey 自定义秘钥
	 * @param int $time 时间戳（用于验证调用） 
	 * @return boolean
	 */
	public static function getPassCode($type = 0, $passCodeKey = null, $time = null) {
		$_Default_PassCode_Key = $passCodeKey?:'YEPF:Util:v1';	//建议定期修改提高安全性
		$time = $time?:time();
		
		switch($type){
			case 0: //当天有效
				$t = md5($_Default_PassCode_Key . date('Y-m-d', $time));
				$t = substr($t, -4);
				$re = hexdec($t) | 1000;
				break;
			case 1:	//1小时有效 （必须通过 checkPassCode 检验，否则跨小时会导致出错）
				$t = md5($_Default_PassCode_Key . date('Y-m-d H', $time));
				$t = substr($t, -4);
				$t = hexdec($t) % 100000;
				if($t < 10000) $t += 10000;	//凑齐5位
				$re = $t;
				break;
			case 2: // 10分钟有效
				//当前时间按10分钟取整
				
				$slot = intval($time / 600);
				$t = md5($_Default_PassCode_Key . $slot);
				$t = substr($t, -4);
				$t = hexdec($t) % 100000;
				if($t < 10000) $t += 10000; //凑齐5位
				$re = $t;
				break;
			default: 
				$re = false;
				break;
		}
		\yoka\Debug::log('getPassCode: ' . $type, $re);
		return $re;
	}
	
	/**
	 * 检查通行码
	 * @param unknown $code
	 * @param number $type
	 * @param unknown $passCodeKey
	 */
	static public function checkPassCode($code, $type = 0, $passCodeKey = null){
		$_Default_PassCode_Key = $passCodeKey?:'YEPF:Util:v1';	//建议定期修改提高安全性
		
		if(self::getPassCode($type, $passCodeKey) == $code) return true;	//验证正确
		
		//处理跨时段问题
		switch($type){
			case 1:
				$time = time() - 3600;		//上一个小时
				if(self::getPassCode($type, $passCodeKey, $time) == $code) return true;
				break;
			case 2:
				$time = time() - 60*10;		//上个10分钟
				if(self::getPassCode($type, $passCodeKey, $time) == $code) return true;
				break;
			default:
				break;
		}
		return false;
	}
	
	/**
	 * 转百分比
	 * @param number $detail 保留小数
	 */
	static public function percent($number, $detail = 2){
		$m = 1;
		for($i=0;$i<$detail;$i++){
			$m = $m * 10;
		}
		$t = round($number * 100 * $m) / $m;
		return $t;
	}
	
	/**
	 * 四舍六入五成双（银行家算法）
	 * @param unknown $num
	 * @param number $precision 精度
	 * @return number
	 */
	static public function round($num,$precision = 0){
		$pow = pow(10,$precision);
		if(  (floor($num * $pow * 10) % 5 == 0) && (floor( $num * $pow * 10) == $num * $pow * 10) && (floor($num * $pow) % 2 ==0) ){//舍去位为5 && 舍去位后无数字 && 舍去位前一位是偶数    =》 不进一
			return floor($num * $pow)/$pow;
		}else{//四舍五入
			return round($num,$precision);
		}
	}

	/**
	 * 根据月份返回第几季度
	 * @param int $month
	 * @return int 1~4
	 */
	static public function getQuarter($month) {
		$month = (int)$month;
		if ($month > 12 || $month < 1) {
			return false;
		}
		if ($month%3 == 0) {
			$quarter = (int)($month / 3);
		} else  {
			$quarter = (int)($month / 3) + 1;
		}
		
		return $quarter;
	}
	
	/**
	 * 下划线转驼峰
	 */
	static public function underlineToHump($str)
	{
		$str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
			return strtoupper($matches[2]);
		},$str);
		return $str;
	}
	
	/**
	 * 驼峰转下划线
	 */
	static public function humpToUnderline($str){
		$str = preg_replace_callback('/([A-Z]{1})/',function($matches){
			return '_'.strtolower($matches[0]);
		},$str);
		return $str;
	}
}