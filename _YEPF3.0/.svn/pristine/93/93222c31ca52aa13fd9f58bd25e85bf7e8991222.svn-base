<?php
/**
 * @name MailInterface.class.php
 * @desc 发送邮件接口类
 * @author 曹晓冬(caotian2000@sohu.com)
 * @createtime 2009-08-17 10:11
 */
namespace yoka;

interface MailInterface
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
    public function send($to, $subject, $content, $attachment_images = array(), &$error = '');
}
?>