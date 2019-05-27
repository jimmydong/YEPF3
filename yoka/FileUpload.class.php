<?php
namespace yoka;
/**
 * 上传文件的管理
 * @author jimmy.dong@gmail.com 2012.3.27
 * 
 * 注意：
 * 1，建议提前定义
 * 		FILE_PATH_UPLOAD 文件上传目录 
 * 		URL_PATH_UPLOAD	 上传目录访问URL
 * 2，也可以手工调用init设定目录和URL
 * 		\yoka\FileUpload::init('/WORK/HTML/upload', 'http://cdn.yishengdaojia.cn/upload');
 * 3，如果上传目录未设置，检查以下默认值
 * 		/DocumentRoot/storage
 * 		/DocumentRoot/upload
 * 4，如果以上目录都不存在，使用时会抛出异常
 * 5，如果上传目录自定义，需要设置访问URL，否则getUrl抛出异常
 * 
 * 使用：
 *   $file_name = \yoka\FileUpload::create($_FILES['logo']['name'], $_FILES['logo']['tmp_name']);
 *   $url = \yoka\FileUpload::getUrl($file_name);
 */
use yoka\Debug;
use yoka\Cache;
use yoka\Log;
use yoka\DB;

class FileUpload{
	/**
	 * 上传文件目录的绝对路径
	 * @var string
	 */
	static public $file_path_upload;
	
	/**
	 * 上传文件目录的URL路径
	 * @var string
	 */
	static public $url_path_upload;
	
	/**
	 * 可识别的文件扩展名
	 */
	static public $file_ext_allowed = array(
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
		'log'=>'/\.log$/i',
		'pdf'=>'/\.pdf$/i',
		'docx'=>'/\.docx$/i',
		'doc'=>'/\.doc$/i',
		'xlsx' => '/\.xlsx$/i',
		'xls' => '/\.xls$/i',
		'txt'=> '/\.txt$/i',
		'zip'=> '/\.zip$/i',
	);
	/**
	 * 头信息
	 */
	static public $file_ext_header = array(
			'/\.avi/i'=>'video/x-msvideo',
			'/\.biz/i'=>'text/xml',
			'/\.bmp/i'=>'image/bmp',
			'/\.css/i'=>'text/css',
			'/\.doc/i'=>'application/msword',
			'/\.gif/i'=>'image/gif',
			'/\.htm/i'=>'text/html',
			'/\.html/i'=>'text/html',
			'/\.log/i'=>'text/html',
			'/\.ico/i'=>'image/x-icon',
			'/\.jpeg/i'=>'image/jpeg',
			'/\.jpg/i'=>'image/jpeg',
			'/\.js/i'=>'application/x-javascript',
			'/\.m3u/i'=>'audio/mpegurl',
			'/\.mid/i'=>'audio/mid',
			'/\.mov/i'=>'video/quicktime',
			'/\.mp3/i'=>'audio/mp3',
			'/\.mp4/i'=>'video/mpeg4',
			'/\.mpa/i'=>'video/x-mpg',
			'/\.mpeg/i'=>'video/mpg',
			'/\.mpg/i'=>'video/mpg',
			'/\.pdf/i'=>'application/pdf',
			'/\.png/i'=>'image/png',
			'/\.ppt/i'=>'application/x-ppt',
			'/\.qt/i'=>'video/quicktime',
			'/\.svg/i'=>'text/xml',
			'/\.txt/i'=>'text/html',
			'/\.text/i'=>'text/html',
			'/\.tif/i'=>'image/tiff',
			'/\.tiff/i'=>'image/tiff',
			'/\.vml/i'=>'text/xml',
			'/\.vsd/i'=>'application/x-vsd',
			'/\.wmv/i'=>'video/x-ms-wmv',
			'/\.xml/i'=>'text/xml',
			'/\.xsl/i'=>'text/xml',
			'/\.xslt/i'=>'text/xml',
			'/\.zip/i'=>'application/x-zip-compressed',
			'/\..*/i'=>'application/octet-stream',
	);
	
	/**
	 * 基础参数初始化
	 * @param string $file_path_upload eg: /WORK/storage
	 * @param string $url_path_upload  eg: http://xxx.xxx.com/storage
	 */
	public static function init($file_path_upload='', $url_path_upload=''){
		//去除末尾/号
		if(substr($file_path_upload, -1) == "/") $file_path_upload = substr($file_path_upload, 0, -1);
		if(substr($url_path_upload, -1) == "/") $url_path_upload = substr($url_path_upload, 0, -1);
		
		//上传相对路径
		if($file_path_upload && file_exists($file_path_upload)){
			self::$file_path_upload = $file_path_upload;
		}elseif(self::$file_path_upload){
			//已有设置，不做处理
		}elseif(defined('FILE_PATH_UPLOAD') && file_exists(FILE_PATH_UPLOAD)){
			self::$file_path_upload = FILE_PATH_UPLOAD;
		}elseif(defined('ROOT_PATH') && file_exists(ROOT_PATH . '/DocumentRoot/storage')){
			self::$file_path_upload = ROOT_PATH . '/DocumentRoot/storage';
		}elseif(defined('ROOT_PATH') && file_exists(ROOT_PATH . '/DocumentRoot/upload')){
			self::$file_path_upload = ROOT_PATH . '/DocumentRoot/upload';
		}else{
			throw(new \Exception('FILE_UPLOAD_PATH not defined!'));
			return false;
		}
		
		//上传URL地址
		if($url_path_upload){
			self::$url_path_upload = $url_path_upload;
		}elseif(self::$url_path_upload){
			//已有设置，不做处理
		}elseif(defined('URL_PATH_UPLOAD')){
			self::$url_path_upload = URL_PATH_UPLOAD;
		}elseif(preg_match('/storage/',self::$file_path_upload)){
			self::$url_path_upload = '//storage';
		}elseif(preg_match('/upload/',self::$file_path_upload)){
			self::$url_path_upload = '//upload';
		}else{
			return false;
		}
		
		return true;
	}
	/**
	 * 上传文件的快捷方法
	 * @param string $name   <input type="file" name=xxx>
	 * @param bool $full 返回全格式: [file_path, real_path, url]
	 * @return string
	 *
	 * 注意form表单： enctype="multipart/form-data"
	 */
	public static function uploadFile($name, $full = false){
		if(! $file_path	= self::create($_FILES['upload']['name'], $_FILES['upload']['tmp_name'])) return \yoka\YsError::error('上传失败：无上传文件');
		if(! $real_path = self::getRealPath($file_path_name)) return \yoka\YsError::error('上传失败：文件大小异常？');
		if(! in_array(pathinfo($real_path, PATHINFO_EXTENSION), self::$file_ext_allowed)) return \yoka\YsError::error('上传失败：文件类型错误');
		
		if($full) return ['file_path'=>$file_path, 'real_path'=>$real_path, 'url'=>self::getUrl($file_path)];
		else return $file_path;
	}
	/**
	 * 上传图片的快捷方法
	 * @param string $name   <input type="file" name=xxx>
	 * @param bool $full 返回全格式: [file_path, real_path, url, width, height, mime]
	 * @return string
	 * 
	 * 注意form表单： enctype="multipart/form-data"
	 */
	public static function uploadImage($name, $full = false){
		if(! $file_path	= self::create($_FILES['upload']['name'], $_FILES['upload']['tmp_name'])) return \yoka\YsError::error('上传失败：无上传文件'); 
		if(! $real_path = self::getRealPath($file_path_name)) return \yoka\YsError::error('上传失败：文件大小异常？');
		if(! $info = getimagesize($real_path)) return \yoka\YsError::error('上传失败：不是图形文件');
		if($full) return [
				'file_path'=>$file_path, 
				'real_path'=>$real_path, 
				'url'=>self::getUrl($file_path),
				'width' => $info[0],
				'height' => $info[1],
				'mime'	=> $info['mime'],
		];
		else return $file_path;
	}
	
	/**
	 * 创建新文件，支持的文件扩展请参照 file_ext_allowed 变量定义
	 * 适用于
	 * 1 form表单上传二进制图片；
	 * 2 直接下载网络资源地址；
	 * 指定抓取网络文件时，仅需要$tmp_file_path_name为URL，参数$src_filename无意义
	 * 如： create('', http://p3.yokacdn.com/pic/idx/2012/0406/U372P9T16D1F233DT20120329101051.jpg)
	 *
	 * @param string $src_filename 原始文件名。 eg:$_FILES['upload']['name']
	 * @param string $tmp_file_path_name 上传临时文件。eg: $_FILES['upload']['tmp_name']
	 * @return 保存后的文件相对路径
	 *
	 * 获取绝对路径，请使用 getRealPath($file_path_name)方法
	 */
	public static function create($src_filename, $tmp_file_path_name, $check_image = false){
		self::init();
		
		$from_net = false;	
		if(preg_match('/^http:\/\/|https:\/\//i', $tmp_file_path_name)){
			//网络文件
			$from_net = true;
			$t = explode('?', basename($tmp_file_path_name));
			$src_filename = $t[0];
		}elseif(!file_exists($tmp_file_path_name) || filesize($tmp_file_path_name)<10){
			//throw(new \Exception('文件处理出错，是不是填错啦？'));
			return \yoka\YsError::error('文件处理出错：是不是填错啦？');
		}
		$ext = 'jpg';	// 默认设置为.jpg文件扩展
		foreach(self::$file_ext_allowed as $key=> $reg){
			if(preg_match($reg, $src_filename)){
				$ext = $key;
				break;
			}
		}
// 		if($ext == ''){
// 			throw(new \Exception('不被允许的文件扩展名'));
// 			return false;
// 		}
		$base_filename = md5(basename($src_filename) . time() . rand(0,99));
		$file_path_name = date('Ymd') . '/' . $base_filename . '.' . $ext;
		if(!self::_mkdirs(self::$file_path_upload . '/' . $file_path_name)){
			throw(new \Exception('创建子目录失败:' . self::$file_path_upload . '/' . $file_path_name));
		}
		Debug::flog('flog:upload', self::$file_path_upload . '/' . $file_path_name);
		self::mkdirs(self::$file_path_upload . '/' . $file_path_name);
		if($from_net){
			//模拟浏览器抓取
			$referer_url = str_replace('sinaimg.cn','sina.com.cn',$tmp_file_path_name); //新浪referer限制
			$cu = new \tools\MultiProcess(array(CURLOPT_REFERER=>$referer_url, CURLOPT_SSL_VERIFYHOST=>false, CURLOPT_SSL_VERIFYPEER=>false));
			if($content = $cu->execOne($tmp_file_path_name)){
				file_put_contents(self::$file_path_upload . '/' . $file_path_name, $content);
				Debug::log('curl get', self::$file_path_upload . '/' . $file_path_name);
			}
		}else{
			Debug::flog('flog:upload', filesize($tmp_file_path_name));
			Debug::flog('flog:upload', self::$file_path_upload . '/' . $file_path_name);
			$t = copy($tmp_file_path_name, self::$file_path_upload . '/' . $file_path_name);
			Debug::flog('flog:upload', $t);
		}
		if($check_image && !getimagesize(self::$file_path_upload . '/' . $file_path_name)){
			Debug::log('Error copy', $tmp_file_path_name .'==>'. $file_path_name);
			return false;
		}
		if(!file_exists(self::$file_path_upload . '/' . $file_path_name)){
			throw(new \Exception('文件处理出错，是不是填错啦？'));
			return false;
		}
		\yoka\Debug::log('fileupload', self::$file_path_upload . '/' . $file_path_name);
		return $file_path_name;
	}
	/**
	 * 快捷将文件放入upload
	 * @param unknown $file_path_name
	 * @return string 相对于upload的路径
	 */
	public static function put($file_path_name){
		return self::create(basename($file_path_name), $file_path_name);
	}
	/**
	 * 读取文件
	 * Enter description here ...
	 * @param string $file_path_name 相对路径
	 */
	public static function get($file_path_name){
		self::init();
		if(!file_exists(self::$file_path_upload . '/' . $file_path_name)){
			throw(new \Exception('文件不存在'));
			return false;
		}
		$re = file_get_contents(self::$file_path_upload . '/' . $file_path_name);
		return $re; 
	}
	/**
	 * 获取文件的URL地址
	 * Enter description here ...
	 * @param string $file_path_name
	 */
	public static function getUrl($file_path_name){
		self::init();
		if(! self::$url_path_upload){
			throw(new \Exception('URL_PATH_UPLOAD not defined!'));
			return false;
		}
		if($file_path_name == '') return self::$url_path_upload . '/404.jpg';
		if(preg_match('/^http/i', $file_path_name))return $file_path_name; //已经是全路径URL格式
		if(preg_match('/(^\/storage\/)|(^\/upload\/)/', $file_path_name)) return $file_path_name; //相对路径URL形式，不做修正
		//\yoka\Debug::log(URL_PATH_UPLOAD, $file_path_name);
		return self::$url_path_upload . '/' . $file_path_name;
	}
	/**
	 * 由相对路径获取绝对路径
	 * 注意： 直接返回计算结果，未做 file_exists 检查
	 */
	public static function getRealPath($file_path_name){
		self::init();
		//URL处理，防止用户误传入URL（注意：可能导致类似/upload/upload/下文件处理异常）
		$file_path_name = self::getPath($file_path_name);
		if(substr($file_path_name, 0, 1) == '/') $re = self::$file_path_upload . $file_path_name;
		$re = self::$file_path_upload . '/' . $file_path_name;
		return $re;
	}
	/**
	 * 由URL或绝对路径逆向计算文件地址（相对路径）
	 * @param unknown_type $file_url_path
	 */
	public static function getPath($file_url_path)
	{
		self::init();
		
		//传入的是符合URL_PATH_UPLOAD格式的URL
		if(self::$url_path_upload && strpos(self::$url_path_upload, $file_url_path) === 0){
			$file_url_path = substr($file_url_path, strlen(self::$url_path_upload));
		}
		//不符合URL_PATH_UPLOAD格式的URL，去除http标记
		if(preg_match('/(^http)|(^\/\/)/i',$file_url_path)){
			$t = parse_url($file_url_path);
			$file_url_path = $t['path'];
		}
		//相对路径的URL
		if(strpos($file_url_path, '/storage/') === 0){
			$file_url_path = substr($file_url_path, strlen('/storage/'));
		}
		if(strpos($file_url_path, '/upload/') === 0){
			$file_url_path = substr($file_url_path, strlen('/upload/'));
		}
		//绝对地址的路径
		if(strpos($file_url_path, self::$file_path_upload) === 0){
			$file_url_path = substr($file_url_path, strlen(self::$file_path_upload)+1);
		}
		return $file_url_path;
	}
	/**
	 * 更新文件
	 * Enter description here ...
	 * @param string $tmp_file_path_name 新文件（绝对地址）
	 * @param string $file_path_name 旧文件（相对地址）
	 */
	public static function update($tmp_file_path_name, $file_path_name){
		self::init();
		
		if(!file_exists($tmp_file_path_name)){
			throw(new \Exception('上传文件不存在'));
			return false;
		}
		if(file_exists(self::$file_path_upload . '/' . $file_path_name)){
			//已有文件备份
			rename(self::$file_path_upload . '/' . $file_path_name, self::$file_path_upload . '/' . $file_path_name . '_' . date('YmdHis'));
		}
		copy($tmp_file_path_name, ROOT_PATH . 'DocumentRoot/upload/' . $file_path_name);
		return true;
	}
	/**
	 * 删除文件
	 * Enter description here ...
	 * @param unknown_type $file_path_name
	 */
	public static function delete($file_path_name){
		self::init();
		
		if(! file_exists(self::$file_path_upload . '/' . $file_path_name)) return false;
		
		//不做真实物理删除
		rename(self::$file_path_upload . '/' . $file_path_name, self::$file_path_upload . '/' . $file_path_name . '_' . date('YmdHis'));
		return true;
	}
	/**
	 * 列表目录下文件
	 * Enter description here ...
	 * @param string $file_path 相对目录
	 * @return array 目录下文件
	 */
	public static function dir($file_path){
		self::init();
		$real_path = self::$file_path_upload . '/' . $file_path;
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
	static function _mkdirs($pathStr,$mod=0755,$own='www'){
		$path = dirname($pathStr);
		@mkdir($path, $mod, true);
		@chown($path, $own);
		if(!is_dir($path))return false;
		else return true;
	}
	
	/**
	 * 处理有防盗链的网站网版
	 * @param url[str] $url
	 * @param referer[str] $referer
	 * @param cookie[str] $cookie
	 */
	public static function get_net_pic($url, $referer='', $cookie='') {
		self::init();
		
		if (!$url)
			return false;
		
		list($src, $tmp) = explode('?', basename($url));
		$ext = 'jpg';	// 默认设置为.jpg文件扩展
		foreach(self::$file_ext_allowed as $key=> $reg){
			if(preg_match($reg, $src_filename)){
				$ext = $key;
				break;
			}
		}
		\yoka\Debug::log('src', $src);
		$base_filename = md5(basename($src));//1000秒内同url图片认为一致
		$file_path_name = date('Ymd') . '/' . substr($base_filename,0,2) . '/' . $base_filename . '.' . $ext;
		$real_path = self::$file_path_upload . '/' . $file_path_name;
		if (file_exists($real_path))
			return $file_path_name;
		self::_mkdirs($real_path);
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($referer)
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		if ($cookie)
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		$content = curl_exec($ch);
		if(!$content){
			//未获取到内容
			return false;
		}
		file_put_contents($real_path, $content);
		
		return $file_path_name;	
	}
	
	/**
	 * 由文件内容创建文件
	 * @param unknown $image_data
	 * @param string $ext
	 * @return string file_path_name
	 */
	public static function createByImgData($image_data,$ext='jpg')
	{
		self::init();
		
		$base_filename = md5( $image_data. time() . rand(0,99));
		$file_path_name = date('Ymd') . '/' . substr($base_filename,0,2) . '/' . $base_filename . '.' . $ext;
		self::_mkdirs(self::$file_path_upload . '/' . $file_path_name);
	
		file_put_contents(self::$file_path_upload . '/' . $file_path_name, $image_data);
	
		return $file_path_name;
	}
	
	/**
	 * 创建目录
	 * Enter description here ...
	 * @param unknown_type $pathStr
	 * @param unknown_type $mod
	 */
	static function mkdirs($pathStr,$mod=0755,$own='www'){
		$path = dirname($pathStr);
		@mkdir($path, $mod, true);
		@chown($path, $own);
		if(!is_dir($path))return false;
		else return true;
		/*if(is_file($pathStr) || is_dir($pathStr)) return true;
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
		 */
	}

	/**
	 * 二进制内容创建文件
	 * @param unknown $content
	 * @param unknown $ext
	 * @return boolean|string
	 */
	public static function createByContent($content, $ext = null){
		self::init();
	
		$ext = strtolower($ext);
		if(substr($ext,0,1) == '.')$ext = substr($ext,1); //去除 .
		if(! $ext) $ext = 'jpg';	// 默认设置为.jpg文件扩展
		
		if(! self::$file_ext_allowed[$ext]){
			throw(new \Exception('禁止使用的扩展名:' . $ext));
			return false;
		}
		
		$base_filename = md5($ext . time() . rand(0,999));
		$file_path_name = date('Ymd') . '/' . $base_filename . '.' . $ext;
		if(!self::_mkdirs(self::$file_path_upload . '/' . $file_path_name)){
			throw(new \Exception('创建子目录失败:' . self::$file_path_upload . '/' . $file_path_name));
		}
		Debug::flog('flog:upload', self::$file_path_upload . '/' . $file_path_name);
		file_put_contents(self::$file_path_upload . '/' . $file_path_name, $content);
		if(!file_exists(self::$file_path_upload . '/' . $file_path_name)){
			throw(new \Exception('文件处理出错，是不是填错啦？'));
			return false;
		}
		\yoka\Debug::log('fileupload ok', self::$file_path_upload . '/' . $file_path_name);
		return $file_path_name;
	}
	
	/**
	 * 图片压缩
	 * 注意： 
	 * 1，依赖 imagemagick （路径 /usr/bin/convert）
	 * 2，覆盖原文件
	 * @param string $file_path_name
	 */
	public static function compressImage($file_path_name){
		if(! $source = self::get($file_path_name))return false;
		/**
		 * PNG压缩 每月500次处理配额
		 * 账号已过期
		\Tinify\setKey("ug8AGI7-NDl18olZUqba3stfRlc9e7ZL");
		try{
			\Tinify\fromBuffer($sourceData)->toFile(self::getRealPath($file_path_name));
			$re = true;
		}catch(\Exception $e){
			$re = false;
			//do nothing;
		}
		*/
		
		//TODO::判断图片大小，超过1600宽高的等比例放缩到1600
		
		$file = self::getRealPath($file_path_name);
		$cmd = "/usr/bin/convert -quality 85 {$file} {$file}";
		ob_start();
		passthru($cmd);
		$re = ob_get_contents();
		ob_end_clean();
		if(preg_match('/No|error/i', $re)) return false;
		else return $re;
	}
}