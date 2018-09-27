<?php
/**
 * 全局错误类
 * 
 * 保存错误信息，便于追查
 * 
 * 
 */
namespace yoka;
class YsError{
	public static $last_error;
	public static $last_error_no;
	public static $history = [];
	
	/**
	 * 记录错误并返回false
	 * @param string $msg 错误信息
	 * @param int $no 错误编号
	 * @param mixed $data 辅助信息
	 */
	static public function error($msg='error', $no=0, $data=null){
		self::$last_error = $msg;
		self::$last_error_no = $no;
		$t = debug_backtrace(1);
		$caller = $t[0]['file'].':'.$t[0]['line'];
		\yoka\Debug::log('YsError: ' . $msg, $caller);
		array_push(self::$history, ['error'=>$msg, 'error_no'=>$no, 'data'=>$data, 'caller'=>$caller]);
		return false;
	}
	
	/**
	 * 只做错误记录，不做其他处理
	 * 与error方法基本相同 。调用时不要使用return
	 * @param string $msg
	 * @param number $no
	 * @param unknown $data
	 * @return boolean
	 */
	static public function logError($msg='error', $no=0, $data=null){
		self::error($msg, $no, $data);
		return true;
	}
	
	/**
	 * 返回上一个错误信息（实际上就是什么也不做）
	 * @return boolean
	 */
	static public function lastError(){
		\yoka\Debug::log($caller, 'last_error: ' . $msg);
		return false;
	}
	
	static public function getError($detail = false){
		if($detail) return self::$history;
		else return self::$last_error;
	}
	
	/**
	 * 记录错误并退出(exit)
	 * @param string $msg 错误信息
	 * @param int $no 错误编号
	 * @param mixed $data 辅助信息
	 */
	static public function danger($msg='error', $no=0, $data=null){
		$t = debug_backtrace(1);
		$caller = $t[0]['file'].':'.$t[0]['line'];
		if($no) $msg = "[{$no}]" . $msg;
		if($data)var_dump($msg, $data);
		else var_dump($msg);
		echo "<br/>\nYsError::exit()  ---- from " . $caller;
		exit;
	}
}