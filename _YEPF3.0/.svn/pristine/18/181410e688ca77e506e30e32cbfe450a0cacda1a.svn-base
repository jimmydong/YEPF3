<?php
/**
* Smarty plugin
* @package Smarty
* @subpackage plugins
*/
/**
* Smarty cut modifier plugin
*
* Type:    modifier
* Name:    cut
* Purpose: truncat改进，适合切UTF-8中文字符
* @author  jimmy.dong@gmail.com
* @param string
* @param integer
* @param string
* @param boolean
* @return string
*/
function smarty_modifier_cut( $string,$length = 80,$etc='...',$count_words = true ) {
	if(strlen($string) < $length) return $string;
	else return $returnstr =substr_utf8($string, 0, $length).$etc;
}
function substr_utf8($str, $start=0, $length=-1, $return_ary=false) {
	$len = strlen($str);if ($length == -1) $length = $len;
	$r = array();
	$n = 0;
	$m = 0;
	for($i = 0; $i < $len; $i++) {
		$x = substr($str, $i, 1);
		$a = base_convert(ord($x), 10, 2);
		$a = substr('00000000'.$a, -8);
		if ($n < $start) {
			if (substr($a, 0, 1) == 0) {
			}elseif (substr($a, 0, 3) == 110) {
				$i += 1;
			}elseif (substr($a, 0, 4) == 1110) {
				$i += 2;
			}
			$n++;
		}else {
			if (substr($a, 0, 1) == 0) {
				$r[] = substr($str, $i, 1);
			}elseif (substr($a, 0, 3) == 110) {
				$r[] = substr($str, $i, 2);
				$i += 1;
			}elseif (substr($a, 0, 4) == 1110) {
				$r[] = substr($str, $i, 3);
				$i += 2;
			}else {
				$r[] = '';
			}
			if (++$m >= $length) {
			break;
			}
		}
	}
	return $return_ary ? $r : implode("", $r);
}
/* vim: set expandtab: */
?>