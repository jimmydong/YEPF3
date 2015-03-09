<?php
/**
 * YOKA的手机短信发送接口，需要提供一个验证码
 * @author wangyi yz124s@hotmail.com
 * @date 2009-12-31
 */
namespace yoka;

class YokaMobileMessage
{ 
//	var $msg_key = '14f0f5b1366026fd727c58bdc8393e4b';	// 线上的59.151.9.80验证代码
//	var $msg_key = '827050cac8e1b81e815793e2d53bdf74';	// 123.127.90.2 测试环境中的IP验证
	
	/**
	 * 发送短消息
	 * @param Integer $mobile 手机号
	 * @param String $msg 消息内容
	 * @param String key 验证代码，和服务器的IP有关系，默认是支持59.151.9.80服务器
	 * @return String 空字符串表示成功，其他情况表示失败
	 */
	public function sendMessage($mobile, $msg, $key = '14f0f5b1366026fd727c58bdc8393e4b')
	{
		//$msg_url = 'http://utility.yoka.com/PublicService/SMS/Sms.aspx?key='.$key.'&action=send&mobile='.$mobile.'&msg='.urlencode($msg);
		
		$msg_url = 'http://utility.yoka.com/smssend/sms/SendMore.aspx?key='.$key.'&action=mt&sn=SDK-NWW-010-00001&mobile='.$mobile.'&content='.urlencode($msg);
		\yoka\Debug::log('sendMessage_URL', $msg_url);
		
		
		$rs = file_get_contents($msg_url);
		return $rs;
	}

}
?>