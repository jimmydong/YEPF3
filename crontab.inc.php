<?php
/**
 * YOKA后台排程标准格式 PHP版
 *
 * by jimmy
 *
 */
class YokaCronFw
{
	//接收监测数据URL
	const CRON_URL = 'http://monitor.yoka.com:81/pub/YOKA_CCC.php';
	//版本号
	const CRON_VERSION = 1;
	//使用linux命令下curl请求
	const REQ_MED_EXEC = 2;
	//使用php的curl请求
	const REQ_MED_CURL = 1;
	//项目唯一ID
	private $crontab_id;
	//随机字符串
	private $rand_id;
	//curl方式标志
	private $curl_flag;
	//是否为调试状态
	private $debug_flag;
	//调用参数
	private $call_method;
	//防重入标志
	private $collision_flag;
	//维护人邮件
	private $work_email;
	//CURL脚本位置
	private $curl_path;
	//连接超时时间
	private $curl_ctime;
	//传输超时时间
	private $curl_ttime;
	/**
	 * 构造函数
	 *
	 * @param string $crontab_id
	 * @param integer $curl_flag
	 * @param unknown_type $method
	 */
	public function __construct($crontab_id, $curl_flag, $debug_flag, $call_method, $collision_flag, $work_email, $curl_path, $curl_ctime, $curl_ttime)
	{
		$this->crontab_id = $crontab_id;
		$this->rand_id = substr(md5($crontab_id.time()), 0, 16);
		$this->curl_flag = $curl_flag;
		$this->debug_flag = $debug_flag;
		$this->call_method = $call_method;
		$this->collision_flag = $collision_flag;
		$this->work_email = $work_email;
		$this->curl_path = $curl_path;
		$this->curl_ctime = $curl_ctime;
		$this->curl_ttime = $curl_ttime;
	}
	/**
	 * @name start
	 * @desc 开始时请求中控
	 */
	public function start()
	{
		//第一次向中控请求
		$param = "cid=".$this->crontab_id."&rid=".$this->rand_id."&cflag=".$this->collision_flag."&email=".urlencode($this->work_email)."&call=".urlencode(substr($this->call_method, 0, 64))."";
		$req_url = $return_flag = '' ;
		if( $this->curl_flag == self::REQ_MED_EXEC ){
			$req_url = "{$this->curl_path} --connect-timeout {$this->curl_ctime} --max-time {$this->curl_ttime} --data '".$param."' '". self::CRON_URL ."'";
			$this->output("1. exec: $req_url ");
			$return_flag = exec($req_url);
		}elseif( $this->curl_flag == self::REQ_MED_CURL ){
			$req_url = self::CRON_URL . '?' . $param;
			$this->output("1. curl: $req_url ");
			$return_flag = $this->curl($req_url, $this->curl_ctime, $this->curl_ttime);
		}else{
			$req_url = self::CRON_URL . '?' . $param;
			$this->output("1. file: $req_url ");
			$return_flag = file_get_contents($req_url, $this->curl_ctime, $this->curl_ttime);
		}
		$this->output("1. return: $return_flag ");
		switch($return_flag){
			case '':  
			$this->output("返回值异常。仅作记录，继续执行。 ");
			$this->important_log(' crontab return unknown: ' . $req_url);
			break;
			case 0:
			$this->output("传入参数错误。仅作记录，继续执行。 ");
			$this->important_log(' crontab submit param error: ' . $req_url);
			break;
			case 1:
			$this->output("没有冲突或指定了不检查冲突，正常执行 ");
			break;
			case 2:
			$this->output("发现冲突，退出处理 ");
			exit;
			case 3:
			$this->output("发现冲突，已处理冲突，继续执行 ");
			break;
			default:
			$this->output("返回值未定义，继续执行 ");
			break;
		}
		register_shutdown_function(array($this, 'end'));
	}
	/**
	 * @name end 
	 * @desc 结束时请求中控
	 */
	public function end()
	{
		global $ccc_cron_array;
		$param = "cid=". $this->crontab_id . "&rid=". $this->rand_id ."&step=1&eflag=" . $ccc_cron_array['exit_flag'];
		$req_url = $return_flag = '' ;
		if($this->curl_flag == self::REQ_MED_EXEC ){
			$req_url = "{$this->curl_path} --connect-timeout {$this->curl_ctime} --max-time {$this->curl_ttime} --data '{$param}' '". self::CRON_URL ."'";
			$this->output("2. exec: $req_url ");
			$return_flag = exec($req_url);  //or use passthru
		}elseif($this->curl_flag == self::REQ_MED_CURL ){
			$req_url = self::CRON_URL . '?' . $param;
			$this->output("2. curl: $req_url ");
			$return_flag = $this->curl($req_url, $this->curl_ctime, $this->curl_ttime);
		}else{
			$req_url = self::CRON_URL . '?' . $param;
			$this->output("2. file: $req_url ");
			$return_flag = file_get_contents($req_url);
		}
		$this->output("2. return: $return_flag ");
		switch($return_flag){
			case '':  
				$this->output("返回值异常。仅作记录，任务完成。 ");
				$this->important_log(' crontab return unknown: ' . $req_url);
			break;
			case 0:   
				$this->output("传入参数错误。仅作记录，任务完成。 ");
				$this->important_log(' crontab submit param error: ' . $req_url);
			break;
			case 1:		
				$this->output("已确认，完成");
			break;
			case 3:		
				$this->output("未找到匹配项。仅作记录，任务完成。 ");
				$this->important_log(' crontab not found match item: ' . $req_url);
			break;
			default:
				$this->output("返回值未定义，任务完成。 ");
			break;
		}
	}
	/**
	 * @name important_log
	 * @desc 记录至服务器重要日志
	 * @param string $msg 错误信息
	 */
	private function important_log($msg)
	{
		file_put_contents('/YOKA/important_log', date('Y-m-d H:i:s') . $msg . "\n", FILE_APPEND);
	}
	/**
	 * @name curl
	 *
	 * @param string $req_url 请求的url
	 * @param int $curl_ctime 连接超时时间
	 * @param int $curl_ttime 传输超时时间
	 * @return unknown
	 */
	private function curl($req_url, $curl_ctime, $curl_ttime)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $req_url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_ctime);
		curl_setopt($ch, CURLOPT_TIMEOUT, $curl_ttime);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$return = curl_exec($ch);
		curl_close($ch);
		return $return;
	}
	/**
	 * @name output
	 * @desc 输入调试信息
	 */
	private function output($msg)
	{
		if($this->debug_flag == 1)
		{
			echo $msg . "<br>\n";
		}
	}
}

if($ccc_cron_array['curl_flag'] == 1 && !function_exists('curl_init'))
{
	$ccc_cron_array['curl_flag']  = 0;
}
$ccc_cron_array['call_method'] = '' ;
if($argv !== null){ // exec 
	foreach($argv as $key=> $arg){
	    if($key == 0)
	    {
	    	$ccc_cron_array['call_method'] = $_SERVER['PWD'] . ' ' . $arg;
	    }elseif($arg == 'debug')
	    {
	    	$ccc_cron_array['debug_flag'] = 1;
	    }elseif($arg == 'help')
	    {
	    	echo $ccc_cron_array['worker_email']." <hr>\n".$ccc_cron_array['collision_flag']." <hr>\n".$ccc_cron_array['crontab_intro']." <hr>\n".$ccc_cron_array['crontab_demo']." <hr>\n".date("Y-m-d H:i:s");
	    	exit;
	    }elseif ($arg == 'crontab')
	    {
	    	$ccc_cron_array['skip_fw'] = 0 ;
	    }else 
	    {
	    	$ccc_cron_array['call_method'] .= " ".$arg;
	    }
  	}
}else{// url
	if($_REQUEST['debug'] == 1)
	{
		$ccc_cron_array['debug_flag'] = 1;
	}
	if($_REQUEST['help'] == 1)
	{
    	echo $ccc_cron_array['worker_email']." <hr>\n".$ccc_cron_array['collision_flag']." <hr>\n".$ccc_cron_array['crontab_intro']." <hr>\n".$ccc_cron_array['crontab_demo']." <hr>\n".date("Y-m-d H:i:s");
    	exit;
	}elseif ($_REQUEST['crontab'] == 1)
	{
		$ccc_cron_array['skip_fw'] = 0 ;
	}
	$ccc_cron_array['call_method'] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
}

if(empty($ccc_cron_array['skip_fw']))
{
	$yokacronfw_obj = new YokaCronFw($ccc_cron_array['crontab_id'], $ccc_cron_array['curl_flag'], $ccc_cron_array['debug_flag'], $ccc_cron_array['call_method'], $ccc_cron_array['collision_flag'], $ccc_cron_array['worker_email'], $ccc_cron_array['curl_path'], $ccc_cron_array['curl_ctime'], $ccc_cron_array['curl_ttime']);
	$yokacronfw_obj->start();
}

?>
