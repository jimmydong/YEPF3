<?php
/**
 * @name YokaMail.class.php
 * @desc YOKA邮件发送类
 * @author 曹晓冬(caotian2000@sohu.com)
 * @createtime 2009-08-17
 */
namespace yoka;

class YokaMail
{
	const SMTP_HOSTNAME = 'mail.yoka.com';
	const SMTP_SERVERIP = '211.151.225.230';
	const SMTP_PORT = 25;
	const SMTP_SERVICE_USERNAME = 'newspace';
	const SMTP_SERVICE_PASSWORD = 'B4MI5xe33pZ';
	const SMTP_SERVICE_EMAIL = 'newspace@yoka.com';
	const SMTP_SERVICE_DISPLAY_NAME = 'YOKA时尚网';


	const YHMAIL_HOST="ma2.elink-u.cn";
	const YHMAIL_PORT=9508;
	const YHMAIL_XMLRPC="/TriggerXmlRpcServlet/";

	const YHMAIL_ClientID="10050";
	const YHMAIL_UserID="237";
	const YHMAIL_Password="asqweURJ5ioer8p52ksADWuiksdu2340";
	///		const YHMAIL_Subject=$subject;
	const YHMAIL_Fromname=self::SMTP_SERVICE_DISPLAY_NAME;
	const YHMAIL_FromAddress=self::SMTP_SERVICE_EMAIL;
	const YHMAIL_ReplyName=self::SMTP_SERVICE_DISPLAY_NAME;  /// 这个在mail.class里面没有定义，一会儿问一下他们是否为必填项
	const YHMAIL_ReplyAddress=self::SMTP_SERVICE_EMAIL;
	///		const YHMAIL_ToAddress=$to;
	const YHMAIL_FormatID="0";
	///		const YHMAIL_TextContent=$content;
	///		const YHMAIL_HtmlContent=$content;
	const YHMAIL_ResendEmailhandleStyle="1";

	const YHMAIL_ConnectTimeout="20000"; // unit:million seconds
	const YHMAIL_SMTPTimeOut="60000";



	/**
	 * @name sendFromSmtpService
	 * @desc 使用 service@yoka.com 通过smtp方式发送邮件
	 * @param string $to				接收人email	
	 * @param string $subject			邮件标题
	 * @param string $content			邮件内容
	 * @param string $attachment_images 要嵌入的图片数组
	 * @param string $error				错误编号
	 * @return mixed					是否成功
	 */
	public static function sendFromSmtpService($to, $subject, $content, $attachment_images = array())
	{
		$smtp_obj = new \SmtpMail(self::SMTP_HOSTNAME, self::SMTP_SERVERIP, self::SMTP_PORT);
		$smtp_obj->setFromEnv(self::SMTP_SERVICE_EMAIL, self::SMTP_SERVICE_USERNAME, self::SMTP_SERVICE_PASSWORD, self::SMTP_SERVICE_DISPLAY_NAME );
		return $smtp_obj->send($to, $subject, $content, $attachment_images);
	}
	public static function sendFromYinHooService($to, $subject, $content, &$error = '')
	{
		$YHMail_obj=new YinHooMail(self::YHMAIL_ClientID,self::YHMAIL_UserID,self::YHMAIL_Password,self::YHMAIL_Fromname,self::YHMAIL_FromAddress,self::YHMAIL_ReplyName,self::YHMAIL_ReplyAddress,self::YHMAIL_FormatID,self::YHMAIL_ResendEmailhandleStyle,self::YHMAIL_ConnectTimeout,self::YHMAIL_SMTPTimeOut,self::YHMAIL_XMLRPC,self::YHMAIL_HOST,self::YHMAIL_PORT);
		return $YHMail_obj->send($to, $subject, $content, $error );

	}
}
?>