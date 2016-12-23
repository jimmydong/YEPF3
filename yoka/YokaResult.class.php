<?php
/**
 * ��ʽ�����������
 * Ŀ�ģ���׼������ִ�к󷵻�ֵ�ı�����
 * @author jimmy.dong@gmail.com
 * DEMO��
 * function xxxx(){
 	return \YokaResult::ok('hello world!');
   }
 *
 */
class YokaResult {
	/**
	 * ��Ŵ���״̬
	 * @var Boolean
	 */
	private $status;

	/**
	 * �������
	 * @var mixed
	 */
	private $data;

	/**
	 * ���캯��˽�У�����಻������ⲿʵ����
	 */
	private function __construct() {

	}


	/**
	 * ��ʾ����ɹ�
	 * @param mixed $data
	 * @return InternalResultTransfer
	 */
	public static function ok($data = null) {
		$objInternalResultTransfer = new self();
		$objInternalResultTransfer->status = true;
		$objInternalResultTransfer->data = $data;
		return $objInternalResultTransfer;
	}

	/**
	 * ��ʾ����ʧ��
	 * @param mixed $data
	 * @return InternalResultTransfer
	 */
	public static function fail($data = null) {
		$objInternalResultTransfer = new self();
        $objInternalResultTransfer->status = false;
        $objInternalResultTransfer->data = $data;
		//\Debug::log('InternalResult fail', $data);
        return $objInternalResultTransfer;
	}

	/**
	 * �жϴ����Ƿ�ɹ�
	 * @return Boolean
	 */
	public function isOk() {
		return $this->status === true;
	}

	/**
	 * ��ȡ����
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}