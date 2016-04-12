<?php
/**
 * @name SmtpMail.class.php
 * @desc Smtp邮件发送类
 * @author 曹晓冬整理 & 刘礼强创建
 * @createtime 2009-08-17 10:35:39
 * @updatetime 2010-01-04 13:25 增加HTML信件内容中嵌入图片的功能
 * @usage
 * $smtp_obj = new SmtpMail($smtp_hostname, $smtp_serverip, $smtp_port);
 * $smtp_obj->setFromEnv($smtp_service_email, $smtp_service_username, $smtp_service_password, $smtp_service_display_name );
 * $smtp_obj->send($to, $subject, $content, $att_images, $error);
 */
namespace yoka;

class SmtpMail implements yoka\MailInterface {
    /**
     * smtp字符集
     * @var string
     */
    private $charset;
    /**
     * smtp服务器域名
     * @var string
     */
    private $hostname;
    /**
     * smtp服务器ip
     * @var string
     */
    private $serverip;
    /**
     * smtp服务器端口
     * @var string
     */
    private $port;
    /**
     * 发送邮件使用用户
     * @var string
     */
    private $username ;
    /**
     * 发送邮件密码
     * @var string
     */
    private $password ;
    /**
     * 超时时间
     * @var int
     */
    private $timeout;
    /**
     * 发送者email
     * @var string
     */
    private $from;
    /**
     * 发送者显示名
     *
     * @var string
     */
    private $displayname;
	/**
	 * 附件
	 * @var array
	 */
	private $attachment = array();
    /**
     * @name __construct
     * @desc 构造函数
     */
    public function __construct($hostname, $ip, $port, $charset = 'GB2312', $timeout = 20) {
		$this->hostname = $hostname;
        $this->serverip = $ip;
        $this->port = $port;
        $this->charset = $charset;
        $this->timeout = $timeout;
    }
    /**
     * 设置与发送相关的环境变量
     *
     * @param string $from				发送所使用的email
     * @param string $username			发送所使用email的用户名
     * @param string $password			发送邮件的密码
     * @param string $displayname		显示名,默认为email名称
     */
    public function setFromEnv($from, $username, $password, $displayname = '') {
        $this->from = $from;
        $this->username = $username;
        $this->password = $password;
        if(empty($displayname)) {
            $this->displayname = $this->from;
        } else {
        	$this->displayname = $displayname;
        }
        
    }
    /**
     * @name send
     * @desc 发送邮件函数
     * @param string $to				接收人email
     * @param string $subject			邮件标题
     * @param string $content			邮件内容
     * @param string $error				错误编号
     * @return mixed					是否成功
     */
    public function send($to, $subject, $content, $attachment_images = array(), &$error = '') {
        $html = true;
        $error = '';
		
        return $this->_smtp($to, $this->from, $this->displayname, $subject, $content, $error, $html, $attachment_images);
    }
    
    /**
     * smtp实际发送邮件函数
     *
     */
    private function _smtp($to, $from, $displayname, $subject, $content, &$err, $html = false, $attachment_images = array()) {
        //----------------------------------
        // Wipe ya face
        //----------------------------------

        $displayname = str_replace("\"", "", $displayname);
        $content = trim($content);

        $to   = preg_replace( "/[ \t]+/" , ""  , $to   );
        $from = preg_replace( "/[ \t]+/" , ""  , $from );

        $to = preg_replace("/[^@._a-zA-Z0-9]/", "", $to);
        $from = preg_replace("/[^@._a-zA-Z0-9]/", "", $from);

        $to   = preg_replace( "/,,/"     , ","  , $to );
        $from = preg_replace( "/,,/"     , ","  , $from );

        $to     = preg_replace( "#\#\[\]'\"\(\):;/\$!?\^&\*\{\}#" , "", $to  );
        $from   = preg_replace( "#\#\[\]'\"\(\):;/\$!?\^&\*\{\}#" , "", $from);

        $subject = str_replace("\n", "", trim($subject));
        $subject = $this->_clean_message($subject);

        $subject = iconv("UTF-8", "GB18030", $subject);
        $content = iconv("UTF-8", "GB18030", $content);
        $displayname = iconv("UTF-8", "GB18030", $displayname);
		
		if (!is_array($attachment_images) || count($attachment_images) < 1) {
			$is_att_images = false;
		} else {
			$is_att_images = true;
			foreach($attachment_images as $attachments) {
				$this->AddEmbeddedImage($attachments['path'], $attachments['name'], $attachments['name'], 'base64', $attachments['type']);
			}
		}
		
		//----------------------------------
        // Build content
        //----------------------------------
		$basedir = '';
		if (true === $is_att_images) {
		preg_match_all("/(src|background)=\"(.*)\"/Ui", $content, $images);
		if(isset($images[2])) {
			foreach($images[2] as $i => $url) {
				if (!preg_match('#^[A-z]+://#',$url)) {
					$filename = basename($url);
					$directory = dirname($url);
					($directory == '.')?$directory='':'';
					$cid = 'cid:' . md5($filename);
					$ext = pathinfo($filename, PATHINFO_EXTENSION);
					$mimeType  = self::_mime_types($ext);
					if ( strlen($basedir) > 1 && substr($basedir,-1) != '/') { $basedir .= '/'; }
					if ( strlen($directory) > 1 && substr($directory,-1) != '/') { $directory .= '/'; }
					if ( $this->AddEmbeddedImage($basedir.$directory.$filename, md5($filename), $filename, 'base64',$mimeType) ) {
					$content = preg_replace("/".$images[1][$i]."=\"".preg_quote($url, '/')."\"/Ui", $images[1][$i]."=\"".$cid."\"", $content);
					}
				}
			}
		}
		}
		if (false === $html) {
			$content = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s','',$content)));
		}
		
		$altbody = 'To view the message, please use an HTML compatible email viewer!';
	
        //----------------------------------
        // Build headers
        //----------------------------------
		
		$uniq_id = md5(uniqid(date("Ymd")));
		$boundary1 = 'b1_' . $uniq_id . "_YOKA";
		$boundary2 = 'b2_' . $uniq_id . "_YOKA";
		
        $mail_headers = "";
        $mail_headers .= "MIME-Version: 1.0\n";
		// 是否有嵌入图片
		if(!$this->InlineImageExists()){
          $mail_headers .= "Content-Type: " . ($html ? "text/html" : "text/plain") . "; charset=\"" . $this->charset ."\"\n";
        }
        $mail_headers  .= "From: \"" . $displayname . "\"<" . $from . ">\n";
        $mail_headers .= "To: ".$to."\n";
        $mail_headers .= "Subject: ".$subject."\n";

        //-----------------------------------------
        // we're not spam, really!
        //-----------------------------------------

        $mail_headers .= "Return-Path: ".$from."\n";
        $mail_headers .= "X-Priority: 3\n";
        $mail_headers .= "X-Mailer: Love21cn.com Mailer\n";
		
		$body = '';
		if (true === $is_att_images) {
			$body .= sprintf("Content-Type: %s;%s\ttype=\"text/html\";%s\tboundary=\"%s\"%s", 'multipart/related', "\n", "\n", $boundary1, "\n\n\n");
			$body .= sprintf("--%s%s", $boundary1, "\n");
			$body .= sprintf("Content-Type: %s;%s" . "\tboundary=\"%s\"%s", 'multipart/alternative', "\n", $boundary2, "\n"."\n");
			$body .= $this->GetBoundary($boundary2, '', 'text/plain', '') . "\n"; // Create text body
			$body .= $this->EncodeString($altbody, '8bit');
			$body .= "\n\n";
			$body .= $this->GetBoundary($boundary2, '', 'text/html', '') . "\n"; // Create the HTML body
			$body .= $this->EncodeString($content, '8bit');
			$body .= "\n\n";
			$body .= "\n" . '--' . $boundary2 . '--' . "\n";
			$body .= $this->AttachAll($boundary1);
		} else {
			$body .= $content;
		}

        $smtp_fp = @fsockopen( $this->serverip, $this->port, $errno, $errstr, $this->timeout );

        if ( ! $smtp_fp ) {
            $err = "Could not open a socket to the SMTP server";
            return false;
        }

        $smtpmsg = $this->_smtp_get_line($smtp_fp);

        $smtpcode = substr( $smtpmsg, 0, 3 );

        if ( $smtpcode == 220 ) {
            $data = $this->_smtp_crlf_encode($mail_headers . $body);

            //---------------------
            // HELO!, er... HELLO!
            //---------------------

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp, "HELO " . $this->hostname);
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code != 250 ) {
                $err = "HELO";
                return false;
            }

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp, "AUTH LOGIN");
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code == 334 ) {
                $smtp_msg = $this->_smtp_send_cmd( $smtp_fp, base64_encode($this->username) );
                $smtp_code = substr( $smtp_msg, 0, 3 );

                if ( $smtp_code != 334  ) {
                    $err = "Username not accepted from the server";
                    return false;
                }

                $smtp_msg = $this->_smtp_send_cmd( $smtp_fp, base64_encode($this->password) );
                $smtp_code = substr( $smtp_msg, 0, 3 );

                if ( $smtp_code != 235 ) {
                    $err = "Password not accepted from the server";
                    return false;
                }
            }

            //---------------------
            // We're from MARS!
            //---------------------

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp, "MAIL FROM:".$from);
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code != 250 ) {
                $err = $smtp_msg;
                return false;
            }

            //---------------------
            // You are from VENUS!
            //---------------------

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp, "RCPT TO:<".$to.">");
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code != 250 ) {
                $err = "Incorrect email address: $to_email";
                return false;
                break;
            }

            //---------------------
            // SEND MAIL!
            //---------------------

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp,"DATA");
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code == 354 ) {
                //$this->smtp_send_cmd( $data );
                fputs( $smtp_fp, $data."\r\n" );
            }
            else {
                $err = "Error on write to SMTP server";
                return false;
            }

            //---------------------
            // GO ON, NAFF OFF!
            //---------------------

            $smtp_msg = $this->_smtp_send_cmd($smtp_fp,".");
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( $smtp_code != 250 ) {
                $err = "Error on send '.'";
                return false;
            }

            $smtp = $this->_smtp_send_cmd($smtp_fp,"quit");
            $smtp_code = substr( $smtp_msg, 0, 3 );

            if ( ($smtp_code != 250) and ($smtp_code != 221) ) {
                $err = "Error on send 'quit'";
                return false;
            }

            //---------------------
            // Tubby-bye-bye!
            //---------------------

            @fclose( $smtp_fp );
        }
        else {
            $err =  "Error on smtp code ne 220";
            return false;
        }

        return true;
    }
    
	/**
   * 添加一个内嵌的附件.  可以包含图片、声音或其它类型的文档
   * 请确认设定了一个正确的$type类型，例如：image/jpeg" 或者 
   * "image/gif",请根据图片类型确认。
   * @param string $path 嵌入图片的路径
   * @param string $cid 内容中与嵌入图片的关联ID.  使用这个ID
						来替换HTML中的图片数据
   * @param string $name 图片名称.
   * @param string $encoding 文件编码，默认为base64
   * @param string $type 文件的mime类形
   * @param string $err  错语信息
   * @return bool
   */
  public function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream', &$err='') {

    if ( !@is_file($path) ) {
      $this->err = $path . ' file does not exist';
      return false;
    }

    $filename = basename($path);
    if ( $name == '' ) {
      $name = $filename;
    }

    // Append to $attachment array
    $this->attachment[] = array(
      0 => $path,
      1 => $filename,
      2 => $name,
      3 => $encoding,
      4 => $type,
      5 => false,  // isStringAttachment
      6 => 'inline',
      7 => $cid
    );

    return true;
  }
  private function GetBoundary($boundary, $charSet, $contentType, $encoding) {
    $result = '';
    if($charSet == '') {
      $charSet = $this->charset;
    }
    if($encoding == '') {
      $encoding = '8bit';
    }
    $result .= '--' . $boundary . "\n";
    $result .= sprintf("Content-Type: %s; charset = \"%s\"", $contentType, $charSet);
    $result .= "\n";
    $result .= 'Content-Transfer-Encoding' . ': ' . $encoding . "\n";
    $result .= "\n";

    return $result;
  }

  /**
   * 如果附件中有内嵌的数据，则返回TRUE
   * @access public
   * @return bool
   */
  public function InlineImageExists() {
    foreach($this->attachment as $attachment) {
      if ($attachment[6] == 'inline') {
        return true;
      }
    }
    return false;
  }
    
    /**
   * Gets the 获取图片和其它文件的MIME类型
   * @param string 文件扩展名
   * @access public
   * @return string 文件MIME类型或文件扩展名
   * @static
   */
  public static function _mime_types($ext = '') {
    $mimes = array(
      'hqx'   =>  'application/mac-binhex40',
      'cpt'   =>  'application/mac-compactpro',
      'doc'   =>  'application/msword',
      'bin'   =>  'application/macbinary',
      'dms'   =>  'application/octet-stream',
      'lha'   =>  'application/octet-stream',
      'lzh'   =>  'application/octet-stream',
      'exe'   =>  'application/octet-stream',
      'class' =>  'application/octet-stream',
      'psd'   =>  'application/octet-stream',
      'so'    =>  'application/octet-stream',
      'sea'   =>  'application/octet-stream',
      'dll'   =>  'application/octet-stream',
      'oda'   =>  'application/oda',
      'pdf'   =>  'application/pdf',
      'ai'    =>  'application/postscript',
      'eps'   =>  'application/postscript',
      'ps'    =>  'application/postscript',
      'smi'   =>  'application/smil',
      'smil'  =>  'application/smil',
      'mif'   =>  'application/vnd.mif',
      'xls'   =>  'application/vnd.ms-excel',
      'ppt'   =>  'application/vnd.ms-powerpoint',
      'wbxml' =>  'application/vnd.wap.wbxml',
      'wmlc'  =>  'application/vnd.wap.wmlc',
      'dcr'   =>  'application/x-director',
      'dir'   =>  'application/x-director',
      'dxr'   =>  'application/x-director',
      'dvi'   =>  'application/x-dvi',
      'gtar'  =>  'application/x-gtar',
      'php'   =>  'application/x-httpd-php',
      'php4'  =>  'application/x-httpd-php',
      'php3'  =>  'application/x-httpd-php',
      'phtml' =>  'application/x-httpd-php',
      'phps'  =>  'application/x-httpd-php-source',
      'js'    =>  'application/x-javascript',
      'swf'   =>  'application/x-shockwave-flash',
      'sit'   =>  'application/x-stuffit',
      'tar'   =>  'application/x-tar',
      'tgz'   =>  'application/x-tar',
      'xhtml' =>  'application/xhtml+xml',
      'xht'   =>  'application/xhtml+xml',
      'zip'   =>  'application/zip',
      'mid'   =>  'audio/midi',
      'midi'  =>  'audio/midi',
      'mpga'  =>  'audio/mpeg',
      'mp2'   =>  'audio/mpeg',
      'mp3'   =>  'audio/mpeg',
      'aif'   =>  'audio/x-aiff',
      'aiff'  =>  'audio/x-aiff',
      'aifc'  =>  'audio/x-aiff',
      'ram'   =>  'audio/x-pn-realaudio',
      'rm'    =>  'audio/x-pn-realaudio',
      'rpm'   =>  'audio/x-pn-realaudio-plugin',
      'ra'    =>  'audio/x-realaudio',
      'rv'    =>  'video/vnd.rn-realvideo',
      'wav'   =>  'audio/x-wav',
      'bmp'   =>  'image/bmp',
      'gif'   =>  'image/gif',
      'jpeg'  =>  'image/jpeg',
      'jpg'   =>  'image/jpeg',
      'jpe'   =>  'image/jpeg',
      'png'   =>  'image/png',
      'tiff'  =>  'image/tiff',
      'tif'   =>  'image/tiff',
      'css'   =>  'text/css',
      'html'  =>  'text/html',
      'htm'   =>  'text/html',
      'shtml' =>  'text/html',
      'txt'   =>  'text/plain',
      'text'  =>  'text/plain',
      'log'   =>  'text/plain',
      'rtx'   =>  'text/richtext',
      'rtf'   =>  'text/rtf',
      'xml'   =>  'text/xml',
      'xsl'   =>  'text/xml',
      'mpeg'  =>  'video/mpeg',
      'mpg'   =>  'video/mpeg',
      'mpe'   =>  'video/mpeg',
      'qt'    =>  'video/quicktime',
      'mov'   =>  'video/quicktime',
      'avi'   =>  'video/x-msvideo',
      'movie' =>  'video/x-sgi-movie',
      'doc'   =>  'application/msword',
      'word'  =>  'application/msword',
      'xl'    =>  'application/excel',
      'eml'   =>  'message/rfc822'
    );
    return (!isset($mimes[strtolower($ext)])) ? 'application/octet-stream' : $mimes[strtolower($ext)];
  }

    /*-------------------------------------------------------------------------*/
    // clean_message: (Mainly used internally)
    // Ensures that \n and <br> are converted into CRLF (\r\n)
    // Also unconverts some BBCode
    /*-------------------------------------------------------------------------*/

    private function _clean_message($message = "" ) {
        $message = preg_replace( "/^(\r|\n)+?(.*)$/", "\\2", $message );
        $message = str_replace( "\n"          , "<br />", $message );
        $message = str_replace( "\r"          , ""      , $message );
        $message = str_replace( "<br>" , "\r\n", $message );
        $message = str_replace( "<br />"      , "\r\n", $message );
        $message = preg_replace( "#<.+?".">#" , "" , $message );
        $message = str_replace( "&quot;", "\"", $message );
        $message = str_replace( "&#092;", "\\", $message );
        $message = str_replace( "&#036;", "\$", $message );
        $message = str_replace( "&#33;" , "!", $message );
        $message = str_replace( "&#39;" , "'", $message );
        $message = str_replace( "&lt;"  , "<", $message );
        $message = str_replace( "&gt;"  , ">", $message );
        $message = str_replace( "&#124;", '|', $message );
        $message = str_replace( "&amp;" , "&", $message );
        $message = str_replace( "&#58;" , ":", $message );
        $message = str_replace( "&#91;" , "[", $message );
        $message = str_replace( "&#93;" , "]", $message );
        $message = str_replace( "&#064;", '@', $message );
        $message = str_replace( "&#60;", '<', $message );
        $message = str_replace( "&#62;", '>', $message );
        $message = str_replace( "&nbsp;" , ' ' , $message );

        return $message;
    }
	
	public function EncodeString ($str, $encoding = 'base64') {
		$encoded = '';
		switch(strtolower($encoding)) {
		case 'base64':
			$encoded = chunk_split(base64_encode($str), 76, "\n");
			break;
		case '7bit':
		case '8bit':
			$encoded = $this->_smtp_crlf_encode($str);
			//Make sure it ends with a line break
			if (substr($encoded, -(strlen("\n"))) != "\n")
			$encoded .= "\n";
			break;
		}
		return $encoded;
  }
  
  /**
   * 把所有的附件文件转换成二进制文本
   * 返回一个空的字符串或是错误文本
   * @access private
   * @return string
   */
  private function AttachAll($boundary1, &$err='') {
    // Return text of body
    $mime = array();
    $cidUniq = array();
    $incl = array();

    // Add all attachments
    foreach ($this->attachment as $attachment) {
      // Check for string attachment
      $bString = $attachment[5];
      if ($bString) {
        $string = $attachment[0];
      } else {
        $path = $attachment[0];
      }

      if (in_array($attachment[0], $incl)) { continue; }
      $filename    = $attachment[1];
      $name        = $attachment[2];
      $encoding    = $attachment[3];
      $type        = $attachment[4];
      $disposition = $attachment[6];
      $cid         = $attachment[7];
      $incl[]      = $attachment[0];
      if ( $disposition == 'inline' && isset($cidUniq[$cid]) ) { continue; }
      $cidUniq[$cid] = true;

      $mime[] = sprintf("--%s%s", $boundary1, "\n");
      $mime[] = sprintf("Content-Type: %s; name=\"%s\"%s", $type, trim($this->EncodeHeader($this->_smtp_crlf_encode($name))), "\n");
      $mime[] = sprintf("Content-Transfer-Encoding: %s%s", 'base64', "\n");

      if($disposition == 'inline') {
        $mime[] = sprintf("Content-ID: <%s>%s", $cid, "\n");
      }

      $mime[] = sprintf("Content-Disposition: %s; filename=\"%s\"%s", $disposition, trim($this->EncodeHeader($this->_smtp_crlf_encode($name))), "\n\n");

      // Encode as string attachment
      if($bString) {
        $mime[] = $this->EncodeString($string, $encoding);
        $mime[] = "\n\n";
      } else {
        $mime[] = $this->EncodeFile($path, $encoding);
        $mime[] = "\n\n";
      }
    }

    $mime[] = sprintf("--%s--%s", $boundary1, "\n");

    return join('', $mime);
  }
  
  private function EncodeFile($path, $encoding = 'base64', &$err='') {
      if (!is_readable($path)) {
		$err = 'Cannot read files';
        return false;
      }
      if (function_exists('get_magic_quotes')) {
        function get_magic_quotes() {
          return false;
        }
      }
      if (PHP_VERSION < 6) {
        $magic_quotes = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);
      }
      $file_buffer  = file_get_contents($path);
      $file_buffer  = $this->EncodeString($file_buffer, $encoding);
      if (PHP_VERSION < 6) { set_magic_quotes_runtime($magic_quotes); }
      return $file_buffer;
  }
  
  public function EncodeHeader($str, $position = 'text') {
    $x = 0;

    switch (strtolower($position)) {
      case 'phrase':
        if (!preg_match('/[\200-\377]/', $str)) {
          // Can't use addslashes as we don't know what value has magic_quotes_sybase
          $encoded = addcslashes($str, "\0..\37\177\\\"");
          if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
            return ($encoded);
          } else {
            return ("\"$encoded\"");
          }
        }
        $x = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
        break;
      case 'comment':
        $x = preg_match_all('/[()"]/', $str, $matches);
        // Fall-through
      case 'text':
      default:
        $x += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
        break;
    }

    if ($x == 0) {
      return ($str);
    }

    $maxlen = 75 - 7 - strlen($this->charset);
    // Try to select the encoding which should produce the shortest output
    if (strlen($str)/3 < $x) {
      $encoding = 'B';
      if (function_exists('mb_strlen') && $this->HasMultiBytes($str)) {
        // Use a custom function which correctly encodes and wraps long
        // multibyte strings without breaking lines within a character
        $encoded = $this->Base64EncodeWrapMB($str);
      } else {
        $encoded = base64_encode($str);
        $maxlen -= $maxlen % 4;
        $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
      }
    }

    $encoded = preg_replace('/^(.*)$/m', " =?".$this->charset."?$encoding?\\1?=", $encoded);
    $encoded = trim(str_replace("\n", "\n", $encoded));

    return $encoded;
  }
  public function HasMultiBytes($str) {
    if (function_exists('mb_strlen')) {
      return (strlen($str) > mb_strlen($str, $this->charset));
    } else { // Assume no multibytes (we can't handle without mbstring functions anyway)
      return false;
    }
  }
  private function Base64EncodeWrapMB($str) {
    $start = "=?".$this->charset."?B?";
    $end = "?=";
    $encoded = "";

    $mb_length = mb_strlen($str, $this->charset);
    // Each line must have length <= 75, including $start and $end
    $length = 75 - strlen($start) - strlen($end);
    // Average multi-byte ratio
    $ratio = $mb_length / strlen($str);
    // Base64 has a 4:3 ratio
    $offset = $avgLength = floor($length * $ratio * .75);

    for ($i = 0; $i < $mb_length; $i += $offset) {
      $lookBack = 0;

      do {
        $offset = $avgLength - $lookBack;
        $chunk = mb_substr($str, $i, $offset, $this->charset);
        $chunk = base64_encode($chunk);
        $lookBack++;
      }
      while (strlen($chunk) > $length);

      $encoded .= $chunk . "\n";
    }

    // Chomp the last linefeed
    $encoded = substr($encoded, 0, -strlen("\n"));
    return $encoded;
  }

    //+------------------------------------
    //| get_line()
    //|
    //| Reads a line from the socket and returns
    //| CODE and message from SMTP server
    //|
    //+------------------------------------

    private function _smtp_get_line($smtp_fp) {
        $smtp_msg = "";

        while ( $line = fgets( $smtp_fp, 515 ) ) {
            $smtp_msg .= $line;

            if ( substr($line, 3, 1) == " " ) {
                break;
            }
        }

        return $smtp_msg;
    }

    //+------------------------------------
    //| send_cmd()
    //|
    //| Sends a command to the SMTP server
    //| Returns TRUE if response, FALSE if not
    //|
    //+------------------------------------

    private function _smtp_send_cmd($smtp_fp,$cmd) {
        fputs( $smtp_fp, $cmd."\r\n" );

        $smtp = array();

        $smtp_msg = $this->_smtp_get_line($smtp_fp);

        return $smtp_msg;
    }

    //+------------------------------------
    //| crlf_encode()
    //|
    //| RFC 788 specifies line endings in
    //| \r\n format with no periods on a
    //| new line
    //+------------------------------------

    private function _smtp_crlf_encode($data) {
        $data .= "\n";
        $data  = str_replace( "\n", "\r\n", str_replace( "\r", "", $data ) );
        $data  = str_replace( "\n.\r\n" , "\n. \r\n", $data );

        return $data;
    }
}
?>