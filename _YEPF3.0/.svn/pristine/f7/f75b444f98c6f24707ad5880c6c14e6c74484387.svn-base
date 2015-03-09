<?php
/**
 * @name Yinhoomail
 * @desc 通过亿邻（Yinhoo.com）提供的邮件接口来发送邮件
 * @author 阿发@YOKA
 * @createtime 2009-8-12
 * @updatetime
 * 说明：
 *    因为同是发送邮件功能，为了让大家使用习惯，及以后更改方便
 *    大部分函数参数及用法参考并沿用了了Mail.class.php
 *    
 *  使用方法：
 *  
 *  $ToAddress="alfa@yoka.com";
 *  $Subject="This is 测试";
 *  $HtmlCode = <<<HTMLCODE
 *  <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
 *  <html>
 *  <head>
 *  <meta http-equiv="Content-Type" content="text/html; charset=UTF8">
 *  <title>Insert title here</title>
 *  </head>
 *  <body>
 *  测试<img src="http://images.yoka.com/pic/news/2009/0602/logo.gif" />
 *  <img src="http://images.yoka.com/pic/news/2009/0602/logo.gif">
 *  <a href="http://www.yoka.com">YOKA时尚</a>
 *  </body>
 *  </html>
 *  HTMLCODE;
 *  
 *  $SendMailObj = new YinHooMail();
 *  $sendmsg=$SendMailObj->smtp($ToAddress,$Subject,$HtmlCode);
 *   
 *
 *    发送格式能过XMLRPC方式通讯，因为我懒得自己写底层代码，直接从网上找了一个可以用的PHPXMLRPC
 *    网址：http://phpxmlrpc.sourceforge.net/
 *    版本：2.2.2 release
 *    Lib放在了_YEPF/CLASS/xmlrpc里面
 *    XMLRPC的BUG：
 *    xmlrpc原版中使用的是ISO-8859-1编码，我强行改成了UTF-8编码，否则发送的邮件为乱码
 *      更改位置：xmlrpc.inc文件的第232行
 *  232:    ///	$GLOBALS['xmlrpc_internalencoding']='ISO-8859-1';
 *      	$GLOBALS['xmlrpc_internalencoding']='UTF-8';
 *
 *    亿邻给我们的配置信息：
 *    client_id : 10050
 *    user_id : 237
 *    password: asqweURJ5ioer8p52ksADWuiksdu2340
 *    ip: 123.127.90.2 , 119.161.129.213
 *    xmlRpcHost: http://ma2.elink-u.cn:9508/TriggerXmlRpcServlet/
 *    最大并行发送量: 5
 *    日最大发送量: 20000
 *
 **/
namespace ext;

if(!defined('YOKA')) exit('Illegal Request');
include (YEPF_PATH . '/ext/xmlrpc/xmlrpc.inc');

class YinHooMail implements \MailInterface
{
	/**
	 * @name send
	 * @desc 发送邮件函数
	 * @param string $to				接收人email	
	 * @param string $subject			邮件标题
	 * @param string $content			邮件内容
	 * @param string $error				错误编号
	 * @return mixed					是否成功
	 */
	/*
	 * 客户在亿邻智能邮件系统中的客户编号.
	 */
	private $YHClientID;
	/*
	 * 调用者在亿邻智能邮件系统中的用户编号.
	 */
	private $YHUserID;
	/*
	 * 客户调用亿邻邮件代理引擎时的密码
	 */
	private $YHPassword;
	/*
	 * 待发送的邮件标题
	 */
	private $YHSubject;
	/*
	 * 邮件发送名的姓名(名称),如 admin 
	 */
	private $YHFromname;
	/*
	 * 邮件发送地址. 
	 */
	private $YHFromAddress;
	/*
	 * 邮件回复时看到回复接收人姓名
	 */
	private $YHReplyName;
	/*
	 * 邮件的回复地址.
	 */
	private $YHReplyAddress;
	/*
	 * 邮件接收人.如果要发送给多个人是请采用”,”进行分割,最大不能超过 5 个
	 */
	private $YHToAddress;
	/*
	 * 要发送的邮件格式. 0=HTML,1=Text,2=Multipart
	 */
	private $YHFormatID;
	/*
	 *  text 格式的邮件内容,如果是发送 html 格式的邮件,该字段值可以为空.
	 */
	private $YHTextContent;
	/*
	 *  html 格式的邮件内容,如果是发送 text 格式的邮件,该字段值可以为空.
	 */
	private $YHHtmlContent;
	/*
	 *                 邮件发送过程中经常会遇到临时拒绝(如灰名单重试),遇到这种
	 *                 情况的时候.第一发送会发送失败, 但是根据对方邮件服务器的反馈等待一定常的时间(一
	 *                 般需要 1 至 5 分钟)后采取一定的策略重复发送一次,可以发送成功. 该参数定义遇到临时
	 *                 决绝的时候系统如何处理.
	 *                   0=不做任何处理,直接返回第一发送的错误;
	 *                     1=立即返回第一发送的错误信息,但是正常等待和重复发送第二次.发送结束后缓存发
	 *                       送结果.供查询用.
	 *                         2=按要求等待,然后发送第二次, 直到第二次发送结束后再返回结果.
	 */
	private $YHResendEmailhandleStyle;
	/*
	 *     连接对方 smtp 服务器等待超时时长. 如果该值小于等于 0,则采用系统缺省值.
	 */

	private $YHConnectTimeout;
	/*
	 * 对方 smtp 响应等待时长, 如果该值小于等于 0 则采用缺省值.
	 */
	private $YHSMTPTimeOut;
	
	/*
	 * XMLRPC地址
	 */
	private 	$YHMAIL_XMLRPC;
	/*
	 *  HOST
	 */
	private $YHMAIL_HOST;
	/*
	 * PORT
	 */
	private $YHMAIL_PORT;




	public function __construct($ClientID,$UserID,$Password,$Fromname,$FromAddress,$ReplyName,$ReplyAddress,$FormatID,$ResendEmailhandleStyle,$ConnectTimeout,$SMTPTimeOut,$XMLRPC, $HOST, $PORT)
	{
		$this->YHClientID=$ClientID;
		$this->YHUserID=$UserID;
		$this->YHPassword=$Password;
///		$this->YHSubject=$Subject;
		$this->YHFromname=$Fromname;
		$this->YHFromAddress=$FromAddress;
		$this->YHReplyName=$ReplyName;
		$this->YHReplyAddress=$ReplyAddress;
///		$this->YHToAddress=$ToAddress;
		$this->YHFormatID=$FormatID;
///		$this->YHTextContent=$TextContent;
///		$this->YHHtmlContent=$HtmlContent;
		$this->YHResendEmailhandleStyle=$ResendEmailhandleStyle;
		$this->YHConnectTimeout=$ConnectTimeout;
		$this->YHSMTPTimeOut=$SMTPTimeOut;
		
		$this->YHMAIL_XMLRPC=$XMLRPC;
		$this->YHMAIL_HOST=$HOST;
		$this->YHMAIL_PORT=$PORT;
	}

	public function send($to, $subject, $content, &$error = '')
	{
		$PARAClientID=new xmlrpcval($this->YHClientID,'string');
		$PARAUserID=new xmlrpcval($this->YHUserID,'string');
		$PARAPassword=new xmlrpcval($this->YHPassword,'string');
		$PARASubject=new xmlrpcval($subject,'string');
		$PARAFromname=new xmlrpcval($this->YHFromname,'string');
		$PARAFromAddress=new xmlrpcval($this->YHFromAddress,'string');
		$PARAReplyName=new xmlrpcval($this->YHReplyName,'string');
		$PARAReplyAddress=new xmlrpcval($this->YHReplyAddress,'string');
		$PARAToAddress=new xmlrpcval($to,'string');
		$PARAFormatID=new xmlrpcval($this->YHFormatID,'string');
		$PARATextContent=new xmlrpcval($content,'string');
		$PARAHtmlContent=new xmlrpcval($content,'string');
		$PARAResendEmailhandleStyle=new xmlrpcval($this->YHResendEmailhandleStyle,'string');
		$PARAConnectTimeout=new xmlrpcval($this->YHConnectTimeout,'string');
		$PARASMTPTimeOut=new xmlrpcval($this->YHSMTPTimeOut,'string');
		
		$YHParameters=array(
		$PARAClientID,
		$PARAUserID,
		$PARAPassword,
		$PARASubject,
		$PARAFromname,
		$PARAFromAddress,
		$PARAReplyName,
		$PARAReplyAddress,
		$PARAToAddress,
		$PARAFormatID,
		$PARATextContent,
		$PARAHtmlContent,
		$PARAResendEmailhandleStyle,

		$PARAConnectTimeout,
		$PARASMTPTimeOut,
		);
		
		$YHMsg=new xmlrpcmsg("TriggerHandler.sendMail", $YHParameters);

		$YHXMLRPCClient=new xmlrpc_client($this->YHMAIL_XMLRPC, $this->YHMAIL_HOST, $this->YHMAIL_PORT);
		///  alfa 设置调试等级
		///		$YHXMLRPCClient->setDebug(2);

		$YHXMLRPCReturn=$YHXMLRPCClient->send($YHMsg);
		if(!$YHXMLRPCReturn->faultCode())
		{
			/*
			 * 亿邻返回的信息格式：
			 *
			 *
			 * <?xml version="1.0" encoding="GBK"?>
			 * <result>
			 * <unique-id>000000000W9C9ZPU</unique-id>			/// 每封邮件的ID
			 * <result-flag>1</result-flag>						/// 发送状态 1=成功,0=失败,2=暂停
			 * <error-type-id>-1</error-type-id>				/// 错误代码
			 * <error-info></error-info>						/// 错误信息
			 * <begin-time>2009-08-14 18:03:09.509</begin-time>	/// 开始发送时间
			 * <finish-time>2009-08-14 18:03:15.9</finish-time>	/// 结束时间
			 * <delay-time>0</delay-time></result>				/// 暂停时间，只有前面的flag为2时，此值才有用
			 *
			 *
			 */
			$Tmpxml = simplexml_load_string($YHXMLRPCReturn->value()->scalarval());
			foreach($Tmpxml as $key=>$value)
			{
				$TmpData[$key]=$value;
			}
			/*
			 * 失败返回错误信息，成功返回邮件ID
			 */
			$TmpResult=(int)$TmpData['result-flag'];
			if( $TmpResult != 1)
			{
				$error=$TmpData['error-info'];
				return false;
			}
			else
			{
				$YHMailID=$TmpData['unique-id'];
				return $YHMailID;
			}
		}
		else
		{
			$error = $YHXMLRPCReturn->faultCode() . $YHXMLRPCReturn->faultString();
			return false;
		}		
	}
}
?>