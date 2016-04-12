<?php
namespace yoka\mvc;

/**
 * 简单身份验证
 *
 */
class Auth {
	
	public static function check($msg='') {
		if ( empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER']!=$_SERVER['AUTH_USER'] || $_SERVER['PHP_AUTH_PW']!=$_SERVER['AUTH_PWD'] ) {
			self::authenticate($msg);
			exit;
		}
	}
	
	public static function authenticate($msg) {
		header('WWW-Authenticate: Basic realm="'.$msg.'"');
    	header('HTTP/1.0 401 Unauthorized');
	}
}