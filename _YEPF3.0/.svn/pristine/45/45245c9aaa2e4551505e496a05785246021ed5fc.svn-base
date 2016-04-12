<?php
/**
 * 上传文件的管理
 * @author jimmy.dong@gmail.com 2012.3.27
 * 
 * 简化处理，日期为目录，不再进行拆分
 * 
 * 注意： URL_PATH_UPLOAD 在_LOCAL/local.inc.php和init.php中
 */
class FileUpload{
	/**
	 * 允许传入的文件格式
	 */
	static $file_ext_allowed = array(
		'jpg'=>'/\.jpg$/i',
		'jpeg'=>'/\.jpeg$/i',
		'png'=>'/\.png$/i',
		'gif'=>'/\.gif$/i',
		'wmv'=>'/\.wmv$/i',
		'avi'=>'/\.avi$/i',
		'swf'=>'/\.swf$/i',
		'mp3'=>'/\.mp3$/i',
		'wav'=>'/\.wav$/i',
		'mp4'=>'/\.mp4$/i',
		'm4a'=>'/\.m4a$/i',
	);
	
	/**
	 * 创建新文件，支持的文件扩展请参照 file_ext_allowed 变量定义
	 * 适用于
	 * 1 form表单上传二进制图片；
	 * 2 直接下载网络资源地址；
	 * @param string $src_filename 原始上传文件名
	 * @param string $tmp_file_path_name 待存入临时文件，1 绝对路径（适用于二进制文件上传时服务器上的临时存储文件）；2 网络图片，参数$src_filename就不需要啦，如：http://p3.yokacdn.com/pic/idx/2012/0406/U372P9T16D1F233DT20120329101051.jpg
	 * return 保存后的文件（以相对路径记录，操作时请使用 ROOT_PATH . '/Document/upload/' . $file_path_name）
	 */
	public static function create($src_filename, $tmp_file_path_name, $check_image = false){
		$from_net = false;	
		if(preg_match('/^http:\/\/|https:\/\//i', $tmp_file_path_name)){
			//网络文件
			$from_net = true;
			$t = explode('?', basename($tmp_file_path_name));
			$src_filename = $t[0];
		}elseif(!file_exists($tmp_file_path_name) || filesize($tmp_file_path_name)<10){
			throw(new \Exception('文件处理出错，是不是填错啦？'));
			return false;
		}
		$ext = 'jpg';	// 默认设置为.jpg文件扩展
		foreach(\FileUpload::$file_ext_allowed as $key=> $reg){
			if(preg_match($reg, $src_filename)){
				$ext = $key;
				break;
			}
		}
		if($ext == ''){
			throw(new \Exception('不被允许的文件扩展名'));
			return false;
		}
		//$base_filename = md5(basename($src_filename) . time() . rand(0,99));
		$base_filename = date("His"). rand(10000, 99999);
		$file_path_name = date('Ymd') . '/' . $base_filename . '.' . $ext;
		self::mkdirs(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
		if($from_net){
			//模拟浏览器抓取
			$referer_url = str_replace('sinaimg.cn','sina.com.cn',$tmp_file_path_name); //新浪referer限制
			$cu = new \tools\MultiProcess(array(CURLOPT_REFERER=>$referer_url, CURLOPT_SSL_VERIFYHOST=>false, CURLOPT_SSL_VERIFYPEER=>false));
			if($content = $cu->execOne($tmp_file_path_name)){
				file_put_contents(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name, $content);
				\Debug::log('curl get', ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
			}
		}else{
			\Debug::flog('flog:upload', filesize($tmp_file_path_name));
			\Debug::flog('flog:upload', ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
			$t = copy($tmp_file_path_name, ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
			\Debug::flog('flog:upload', $t);
		}
		if($check_image && !getimagesize(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name)){
			\Debug::log('Error copy', $tmp_file_path_name .'==>'. $file_path_name);
			return false;
		}
		if(!file_exists(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name)){
			throw(new \Exception('文件处理出错，是不是填错啦？'));
			return false;
		}
		return $file_path_name;
	}
	/**
	 * 读取文件
	 * Enter description here ...
	 * @param string $file_path_name 相对路径
	 */
	public static function get($file_path_name){
		if(!file_exists(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name)){
			throw(new \Exception('文件不存在'));
			return false;
		}
		$re = file_get_contents(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
		return $re; 
	}
	/**
	 * 获取文件的URL地址
	 * Enter description here ...
	 * @param string $file_path_name
	 */
	public static function getUrl($file_path_name){
		return URL_PATH_UPLOAD . '/' . $file_path_name;
	}
	/**
	 * 由相对路径获取绝对路径
	 */
	public static function getRealPath($file_path_name){
		return ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name;
	}
	/**
	 * 由URL逆向计算文件地址（相对路径）
	 * Enter description here ...
	 * @param unknown_type $file_url_path
	 */
	public static function getPath($file_url_path)
	{
	    if(preg_match('/http:\/\/at\.yoka\.com\/upload\/(.*)/i',$file_url_path,$matches) || preg_match('/http:\/\/atp[1-4]\.yokacdn\.com\/(.*)/i', $file_url_path,$matches))
	        return self::getRealPath($matches[1]);
	    else return $file_url_path;
	}
	/**
	 * 更新文件
	 * Enter description here ...
	 * @param string $tmp_file_path_name 新文件（绝对地址）
	 * @param string $file_path_name 旧文件（相对地址）
	 */
	public static function update($tmp_file_path_name, $file_path_name){
		if(!file_exists($tmp_file_path_name)){
			throw(new \Exception('上传文件不存在'));
			return false;
		}
		rename(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name, ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name . '_' . date('YmdHis'));
		copy($tmp_file_path_name, ROOT_PATH . 'DocumentRoot/upload/' . $file_path_name);
		return true;
	}
	/**
	 * 删除文件
	 * Enter description here ...
	 * @param unknown_type $file_path_name
	 */
	public static function delete($file_path_name){
		rename(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name, ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name . '_' . date('YmdHis'));
		return true;
	}
	/**
	 * 列表目录下文件
	 * Enter description here ...
	 * @param string $file_path 相对目录
	 * @return array 目录下文件
	 */
	public static function dir($file_path){
		$real_path = URL_PATH_UPLOAD . '/' . $file_path;
		$d = opendir($real_path);
		while(false === ($file = readdir($d))){
			if(is_dir($file)) $re[] = '[dir]' . $file;
			else $re[] = '$file';
		}
		return $re;
	}
	/**
	 * 创建目录
	 * Enter description here ...
	 * @param unknown_type $pathStr
	 * @param unknown_type $mod
	 */
	public static function mkdirs($pathStr,$mod=0755,$own='www'){
	    if(is_file($pathStr) || is_dir($pathStr)) return true;
	    $pieces = explode("/", $pathStr);
	    $tmpdir = "";
	    for($mm=1;$mm<(count($pieces)-1);$mm++){
	        $tmpdir      .= "/".$pieces[$mm];
	        if(!is_dir($tmpdir)){
	        	if (!mkdir($tmpdir,$mod)) return false;
	        	chown($tmpdir, $own);
	    	}
	    }
	    return true;
	}
	
	/**
	 * 处理有防盗链的网站网版
	 * @param url[str] $url
	 * @param referer[str] $referer
	 * @param cookie[str] $cookie
	 */
	public static function get_net_pic($url, $referer='', $cookie='') {
		if (!$url)
			return false;
		
		list($src_filename, $tmp) = explode('?', basename($url));
		$ext = 'jpg';	// 默认设置为.jpg文件扩展
		foreach(self::$file_ext_allowed as $key=> $reg){
			if(preg_match($reg, $src_filename)){
				$ext = $key;
				break;
			}
		}
		
//		$base_filename = md5(basename($url));//同一天同url图片认为一致
		$base_filename = md5($url);//同一天同url图片认为一致
		$file_path_name = date('Ymd') . '/' . $base_filename . '.' . $ext;
		$real_path = ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name;
		if (file_exists($real_path) && filesize($real_path)>0 )
			return $file_path_name;
		self::mkdirs($real_path);
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($referer)
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		if ($cookie)
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		$content = curl_exec($ch);
		file_put_contents($real_path, $content);
		
		return $file_path_name;		
	}
	
	public static function createByImgData($image_data,$ext='jpg')
	{
		$base_filename = md5( $image_data. time() . rand(0,99));
		//$file_path_name = date('Ymd') . '/' . substr($base_filename,0,2) . '/' . $base_filename . '.' . $ext;
		$file_path_name = date('Ymd') . '/' . $base_filename . '.' . $ext;
		self::mkdirs(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name);
	
		file_put_contents(ROOT_PATH . '/DocumentRoot/upload/' . $file_path_name, $image_data);
	
		return $file_path_name;
	}
}
