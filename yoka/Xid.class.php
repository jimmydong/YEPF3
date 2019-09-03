<?php
namespace yoka;
/**
 * 整数混淆算法
* 该算法仅支持混淆整数，适用于数据库中自增id字段的加密解密
* 【注意】 10亿以下数字安全。超过10亿，需要另外处理。（受整数32位限制）
* @author jimmy.dong@gmail.com
*/
class Xid{
	private static $cv = 2.54319876;		//散列因子（1到3之间）

	/**
	 * 设置随机种子
	 */
	public static function setCv($cv){
		if(! $cv) self::$cv = rand(2000000,2999999) / 1000000;
		else self::$cv = $cv;
	}
	
	/**
	 * 生成XID
	 * @param int $number
	 * @param bool $uncertain 转化结果保持一致（默认一致）
	 * @return boolean|mixed
	 */
	public static function encode($number, $uncertain = false) {
		if(! is_numeric($number)) return false;

		$txt = strval(intval(($number + 110000) * self::$cv));
		if(! $uncertain) srand($number); //使每次的转换结果保持不变
		$encrypt_key = md5(rand(0, 32000));
		srand(); //恢复随机种子
		$ctr = 0;
		$tmp = '';
		// for 循环，$i 为从 0 开始，到小于 $txt 字串长度的整数
		for($i = 0; $i < strlen($txt); $i++) {
			// 如果 $ctr = $encrypt_key 的长度，则 $ctr 清零
			$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
			// $tmp 字串在末尾增加两位，其第一位内容为 $encrypt_key 的第 $ctr 位，
			// 第二位内容为 $txt 的第 $i 位与 $encrypt_key 的 $ctr 位取异或。然后 $ctr = $ctr + 1
			$tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
		}
		$tmp = str_replace('=','0',base64_encode($tmp));
		//\yoka\Debug::log("Xid encode: $number", $tmp);
		return $tmp;
	}

	/**
	 * 还原ID
	 * @param string $txt
	 * @return number
	 */
	public static function decode($txt) {
		if(! $txt) return 0;

		$txt = base64_decode($txt);
		$tmp = '';
		// for 循环，$i 为从 0 开始，到小于 $txt 字串长度的整数
		for ($i = 0; $i < strlen($txt); $i++) {
			// $tmp 字串在末尾增加一位，其内容为 $txt 的第 $i 位，
			// 与 $txt 的第 $i + 1 位取异或。然后 $i = $i + 1
			$tmp .= $txt[$i] ^ $txt[++$i];
		}
		$number = ceil($tmp / self::$cv) - 110000;
		return $number;
	}
}
