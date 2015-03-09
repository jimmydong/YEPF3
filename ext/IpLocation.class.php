<?php
/**
 * @name IpLocation.class.php
 * @desc ip转换真实地址类
 * @author 马秉尧
 * @修改&整理 曹晓冬
 * @createtime 2009-04-03 11:42 
 * @updatetime 2009-04-07
 * @usage 
 * $iplocation_obj = new IpLocation();
 * 普通类型返回
 * $info = $iplocation_obj->getLocation($ip); 
 * 返回符合province类格式
 * $info = $iplocation_obj->detailLocation($ip);
 * 
 * update by jimmy.dong@gmail
 * add: getFromQq($ip)
 */
namespace ext;

class IpLocation {
	/**
	* QQWry.Dat文件指针
	* @var resource
	*/
	var $fp;
	/**
	* 第一条IP记录的偏移地址
	* @var int
	*/
	var $firstip;
	/**
	* 最后一条IP记录的偏移地址
	* @var int
	*/
	var $lastip;
	/**
	* IP记录的总条数（不包含版本信息记录）
	* @var int
	*/
	var $totalip;
	/**
	 * @name __construct
	 * @desc 构造函数，打开 QQWry.Dat 文件并初始化类中的信息
	 * @param string $filename
	 * @return IpLocation
	 */
	public function __construct() {
		$filename = YEPF_PATH . '/ext/ip/QQWry.Dat';
		if (($this->fp = @fopen($filename, 'rb')) !== false) {
			$this->firstip = $this->getlong();
			$this->lastip = $this->getlong();
			$this->totalip = ($this->lastip - $this->firstip) / 7;
		}
	}
	/**
	 * @name getlong
	 * @desc 返回读取的长整型数
	 * @access private
	 * @return int
	 */
	private function getlong() {
		//将读取的little-endian编码的4个字节转化为长整型数
		$result = unpack('Vlong', fread($this->fp, 4));
		return $result['long'];
	}

	/**
	 * @name getlong3
	 * @desc 返回读取的3个字节的长整型数
	 * @access private
	 * @return int
	 */

	private function getlong3() {
		//将读取的little-endian编码的3个字节转化为长整型数
		$result = unpack('Vlong', fread($this->fp, 3).chr(0));
		return $result['long'];
	}
	/**
	 * @name packip
	 * @desc 返回压缩后可进行比较的IP地址
	 * @access private
	 * @param string $ip
	 * @return string
	 */
	private function packip($ip) {
		// 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，
		// 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
		return pack('N', intval(ip2long($ip)));
	}
	/**
	 * @name getstring
	 * @desc 返回读取的字符串
	 * @access private
	 * @param string $data
	 * @return string
	 */
	private function getstring($data = "") {
		$char = fread($this->fp, 1);
		while (ord($char) > 0) { // 字符串按照C格式保存，以\0结束
			$data .= $char; // 将读取的字符连接到给定字符串之后
			$char = fread($this->fp, 1);
		}
		return $data;
	}
	/**
	 * @name getarea
	 * @desc 返回地区信息
	 * @access private
	 * @return string
	 */
	private function getarea() {
		$byte = fread($this->fp, 1); // 标志字节
		switch (ord($byte)) {
			case 0: // 没有区域信息
			$area = "";
			break;
			case 1:
			case 2: // 标志字节为1或2，表示区域信息被重定向
			fseek($this->fp, $this->getlong3());
			$area = $this->getstring();
			break;
			default: // 否则，表示区域信息没有被重定向
			$area = $this->getstring($byte);
			break;
		}
		return $area;
	}
	/**
	 * @name getLocation
	 * @desc 根据所给 IP 地址或域名返回所在地区信息,原始值
	 * @access public
	 * @param string $ip 源IP
	 * @return array
	 */
	public function getLocation($ip) {
		if (!$this->fp) return null; // 如果数据文件没有被正确打开，则直接返回空
		$location['ip'] = gethostbyname($ip); // 将输入的域名转化为IP地址
		$ip = $this->packip($location['ip']); // 将输入的IP地址转化为可比较的IP地址
		// 不合法的IP地址会被转化为255.255.255.255
		// 对分搜索
		$l = 0; // 搜索的下边界
		$u = $this->totalip; // 搜索的上边界
		$findip = $this->lastip; // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息）
		while ($l <= $u) { // 当上边界小于下边界时，查找失败
			$i = floor(($l + $u) / 2); // 计算近似中间记录
			fseek($this->fp, $this->firstip + $i * 7);
			$beginip = strrev(fread($this->fp, 4)); // 获取中间记录的开始IP地址
			// strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式
			// 以便用于比较，后面相同。
			if ($ip < $beginip) { // 用户的IP小于中间记录的开始IP地址时
				$u = $i - 1; // 将搜索的上边界修改为中间记录减一
			}
			else {
				fseek($this->fp, $this->getlong3());
				$endip = strrev(fread($this->fp, 4)); // 获取中间记录的结束IP地址
				if ($ip > $endip) { // 用户的IP大于中间记录的结束IP地址时
				$l = $i + 1; // 将搜索的下边界修改为中间记录加一
				}
				else { // 用户的IP在中间记录的IP范围内时
				$findip = $this->firstip + $i * 7;
				break; // 则表示找到结果，退出循环
				}
			}
		}
		//获取查找到的IP地理位置信息
		fseek($this->fp, $findip);
		$location['beginip'] = long2ip($this->getlong()); // 用户IP所在范围的开始地址
		$offset = $this->getlong3();
		fseek($this->fp, $offset);
		$location['endip'] = long2ip($this->getlong()); // 用户IP所在范围的结束地址
		$byte = fread($this->fp, 1); // 标志字节
		switch (ord($byte)) 
		{
			case 1: // 标志字节为1，表示国家和区域信息都被同时重定向
			$countryOffset = $this->getlong3(); // 重定向地址
			fseek($this->fp, $countryOffset);
			$byte = fread($this->fp, 1); // 标志字节
			switch (ord($byte)) 
			{
				case 2: // 标志字节为2，表示国家信息又被重定向
				fseek($this->fp, $this->getlong3());
				$location['country'] = $this->getstring();
				fseek($this->fp, $countryOffset + 4);
				$location['area'] = $this->getarea();
				break;
				default: // 否则，表示国家信息没有被重定向
				$location['country'] = $this->getstring($byte);
				$location['area'] = $this->getarea();
				break;
			}
			break;
			case 2: // 标志字节为2，表示国家信息被重定向
			fseek($this->fp, $this->getlong3());
			$location['country'] = $this->getstring();
			fseek($this->fp, $offset + 8);
			$location['area'] = $this->getarea();
			break;
			default: // 否则，表示国家信息没有被重定向
			$location['country'] = $this->getstring($byte);
			$location['area'] = $this->getarea();
			break;
		}
		if ($location['country'] == " CZ88.NET") { // CZ88.NET表示没有有效信息
			$location['country'] = "未知";
		}
		if ($location['area'] == " CZ88.NET") {
			$location['area'] = "";
		}
		//将GBK编码转换成UTF-8
		$location['country'] = iconv('GB2312', 'UTF-8', $location['country']);
		$location['area'] = iconv('GB2312', 'UTF-8', $location['area']);
		return $location;
	}
	
	/**
	 * @name detailLocation
	 * @desc 返回详细的省市信息,符合Province.class.php标准
	 * @param string $ip
	 * @return array
	 */
	public function detailLocation($ip)
	{
		$province_short = Province::getProvinceList();
	    //获取基本信息
	    $ipinfo = $this->getLocation($ip);
	    $location = str_replace('/','',trim($ipinfo['country']));
	    $ipinfo['raw'] = $location;
	    $pro_flag = 0;
	    foreach($province_short as $p_key=> $p_short)
	    {
	        if(ereg($p_short, $ipinfo['country']))
	        {
	            $pro_flag = $p_key;
	            break;
	        }
	    }
        $ipinfo['country'] = '';
        $ipinfo['province'] = '';
        $ipinfo['provinceid'] = 0 ;
        $ipinfo['city'] = '';
        $ipinfo['cityid'] = 0;
        
	    if($pro_flag > 0)
	    {
	        $ipinfo['country'] = '中国';
	        $ipinfo['province'] = $province_short[$p_key];
	        $ipinfo['provinceid'] = $pro_flag;
	        if(substr($ipinfo['province'], 6, 9) == "市")//直辖市
	        {
	        	$ipinfo['city'] = substr($location, 9, strpos($location, '区') - 6);
	        }else 
	        {
	        	$cposition = strpos($location, '市');
	        	if($cposition !== false)
	        	{
		        	$position = strpos($location, '省');
		        	if(false === $position)
		        	{
		        		$position = strlen($ipinfo['province']);
		        	}else 
		        	{
		        		$position += 3 ;
		        	}
		        	$ipinfo['city'] = substr($location, $position , $cposition - $position + 3);
	        	}
	        }
	        if(!empty($ipinfo['city']))
	        {
	        	$city_lists = Province::getCityList($ipinfo['provinceid']);
	        	foreach ($city_lists as $k => $v)
	        	{
	        		if(strpos($ipinfo['city'], $v) !== false)
	        		{
	        			$ipinfo['cityid'] = $k;
	        			break;
	        		}
	        	}
	        }
	    }
	    if($ipinfo['area'] == '')
	    {
	    	$ipinfo['area']='不详';
	    }
	    else
	    {
	    	$ipinfo['area'] = str_replace('/','',trim($ipinfo['area']));
	    }
	    return $ipinfo;
	}
	/**
	 * @name getFromQq
	 * @param string $ip
	 */
	public function getFromQq($ip){
		$ip = gethostbyname($ip);
		$url = 'http://ip.qq.com/cgi-bin/searchip?searchip1=' . urlencode($ip);
		if(! $c = file_get_contents($url)){return false;}
		$c = iconv('GBK','UTF-8',$c);
		if(! preg_match('/<p>该IP所在地为：<span>(.*?)&nbsp;&nbsp;&nbsp;(.*?)<\/span><\/p>/', $c, $re)){return false;}
		$ipinfo['city'] = $re[1];
		$ipinfo['area'] = $re[2];
		return $ipinfo;
	}
	/**
	 * @name __destruct
	 * @desc 析构函数，用于在页面执行结束后自动关闭打开的文件。
	 */
	public function __destruct()
	{
		fclose($this->fp);
	}
}
?>