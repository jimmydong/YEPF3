<?php
/**
 * 格式化结果传递类
 * 目的：标准化方法执行后返回值的表述。
 * @author jimmy.dong@gmail.com
 * DEMO：
 * function xxxx(){
 	return \YokaResult::ok('hello world!');
   }
 *
 */
class YokaResult {
	/**
	 * 存放处理状态
	 * @var Boolean
	 */
	private $status;

	/**
	 * 存放数据
	 * @var mixed
	 */
	private $data;

	/**
	 * 构造函数私有，这个类不允许从外部实例化
	 */
	private function __construct() {

	}


	/**
	 * 表示处理成功
	 * @param mixed $data
	 * @return InternalResultTransfer
	 */
	static public function ok($data = null) {
		$objInternalResultTransfer = new self();
		$objInternalResultTransfer->status = true;
		$objInternalResultTransfer->data = $data;
		return $objInternalResultTransfer;
	}

	/**
	 * 表示处理失败
	 * @param mixed $data
	 * @return InternalResultTransfer
	 */
	static public function fail($data = null) {
		$objInternalResultTransfer = new self();
        $objInternalResultTransfer->status = false;
        $objInternalResultTransfer->data = $data;
		//\Debug::log('InternalResult fail', $data);
        return $objInternalResultTransfer;
	}

	/**
	 * 判断处理是否成功
	 * @return Boolean
	 */
	public function isOk() {
		return $this->status === true;
	}

	/**
	 * 获取数据
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}